<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2019 广东卓锐软件有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://dolphinphp.com
// +----------------------------------------------------------------------

namespace app\common\controller;

use think\Controller;

/**
 * 项目公共控制器
 * @package app\common\controller
 */
class Common extends Controller
{
    /**
     * 初始化
     * @author 蔡伟明 <314013107@qq.com>
     */
    protected function initialize()
    {
        // 后台公共模板
        $this->assign('_admin_base_layout', config('admin_base_layout'));
        // 当前配色方案
        $this->assign('system_color', config('system_color'));
        // 输出弹出层参数
        $this->assign('_pop', $this->request->param('_pop'));
    }

    /**
     * 获取筛选条件
     * @author 蔡伟明 <314013107@qq.com>
     * @alter 小乌 <82950492@qq.com>
     * @return array
     */
    final protected function getMap()
    {
        $search_field     = input('param.search_field/s', '', 'trim');
        $keyword          = input('param.keyword/s', '', 'trim');
        $filter           = input('param._filter/s', '', 'trim');
        $filter_content   = input('param._filter_content/s', '', 'trim');
        $filter_time      = input('param._filter_time/s', '', 'trim');
        $filter_time_from = input('param._filter_time_from/s', '', 'trim');
        $filter_time_to   = input('param._filter_time_to/s', '', 'trim');
        $select_field     = input('param._select_field/s', '', 'trim');
        $select_value     = input('param._select_value/s', '', 'trim');
        $search_area      = input('param._s', '', 'trim');
        $search_area_op   = input('param._o', '', 'trim');

        $map = [];

        // 搜索框搜索
        if ($search_field != '' && $keyword !== '') {
            $map[] = [$search_field, 'like', "%$keyword%"];
        }

        // 下拉筛选
        if ($select_field != '') {
            $select_field = array_filter(explode('|', $select_field), 'strlen');
            $select_value = array_filter(explode('|', $select_value), 'strlen');
            foreach ($select_field as $key => $item) {
                if ($select_value[$key] != '_all') {
                    $map[] = [$item, '=', $select_value[$key]];
                }
            }
        }

        // 时间段搜索
        if ($filter_time != '' && $filter_time_from != '' && $filter_time_to != '') {
            $map[] = [$filter_time, 'between time', [$filter_time_from.' 00:00:00', $filter_time_to.' 23:59:59']];
        }

        // 表头筛选
        if ($filter != '') {
            $filter         = array_filter(explode('|', $filter), 'strlen');
            $filter_content = array_filter(explode('|', $filter_content), 'strlen');
            foreach ($filter as $key => $item) {
                if (isset($filter_content[$key])) {
                    $map[] = [$item, 'in', $filter_content[$key]];
                }
            }
        }

        // 搜索区域
        if ($search_area != '') {
            $search_area = explode('|', $search_area);
            $search_area_op = explode('|', $search_area_op);
            foreach ($search_area as $key => $item) {
                list($field, $value) = explode('=', $item);
                $value = trim($value);
                $op    = explode('=', $search_area_op[$key]);
                if ($value != '') {
                    switch ($op[1]) {
                        case 'like':
                            $map[] = [$field, 'like', "%$value%"];
                            break;
                        case 'between time':
                        case 'not between time':
                            $value = explode(' - ', $value);
                            if ($value[0] == $value[1]) {
                                $value[0] = date('Y-m-d', strtotime($value[0])). ' 00:00:00';
                                $value[1] = date('Y-m-d', strtotime($value[1])). ' 23:59:59';
                            }
                        default:
                            $map[] = [$field, $op[1], $value];
                    }
                }
            }
        }
        return $map;
    }

    /**
     * 获取字段排序
     * @param string $extra_order 额外的排序字段
     * @param bool $before 额外排序字段是否前置
     * @author 蔡伟明 <314013107@qq.com>
     * @return string
     */
    final protected function getOrder($extra_order = '', $before = false)
    {
        $order = input('param._order/s', '');
        $by    = input('param._by/s', '');
        if ($order == '' || $by == '') {
            return $extra_order;
        }
        if ($extra_order == '') {
            return $order. ' '. $by;
        }
        if ($before) {
            return $extra_order. ',' .$order. ' '. $by;
        } else {
            return $order. ' '. $by . ',' . $extra_order;
        }
    }

    /**
     * 渲染插件模板
     * @param string $template 模板名称
     * @param string $suffix 模板后缀
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    /**
     * 渲染插件模板
     * @param string $template 模板文件名
     * @param string $suffix 模板后缀
     * @param array $vars 模板输出变量
     * @param array $config 模板参数
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    final protected function pluginView($template = '', $suffix = '', $vars = [], $config = [])
    {
        $plugin_name = input('param.plugin_name');

        if ($plugin_name != '') {
            $plugin = $plugin_name;
            $action = 'index';
        } else {
            $plugin = input('param._plugin');
            $action = input('param._action');
        }
        $suffix = $suffix == '' ? 'html' : $suffix;
        $template = $template == '' ? $action : $template;
        $template_path = config('plugin_path'). "{$plugin}/view/{$template}.{$suffix}";
        return parent::fetch($template_path, $vars, $config);
    }
}