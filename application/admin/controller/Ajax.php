<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2017 河源市卓锐科技有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://dolphinphp.com
// +----------------------------------------------------------------------
// | 开源协议 ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------

namespace app\admin\controller;

use app\common\controller\Common;
use app\admin\model\Menu as MenuModel;
use app\admin\model\Attachment as AttachmentModel;
use think\Cache;
use think\Db;

/**
 * 用于处理ajax请求的控制器
 * @package app\admin\controller
 */
class Ajax extends Common
{
    /**
     * 获取联动数据
     * @param string $token token
     * @param int $pid 父级ID
     * @param string $pidkey 父级id字段名
     * @author 蔡伟明 <314013107@qq.com>
     * @return \think\response\Json
     */
    public function getLevelData($token = '', $pid = 0, $pidkey = 'pid')
    {
        if ($token == '') {
            return json(['code' => 0, 'msg' => '缺少Token']);
        }

        $token_data = session($token);
        $table      = $token_data['table'];
        $option     = $token_data['option'];
        $key        = $token_data['key'];

        $data_list = Db::name($table)->where($pidkey, $pid)->column($option, $key);

        if ($data_list === false) {
            return json(['code' => 0, 'msg' => '查询失败']);
        }

        if ($data_list) {
            $result = [
                'code' => 1,
                'msg'  => '请求成功',
                'list' => format_linkage($data_list)
            ];
            return json($result);
        } else {
            return json(['code' => 0, 'msg' => '查询不到数据']);
        }
    }

    /**
     * 获取筛选数据
     * @param string $token
     * @param array $map 查询条件
     * @param string $options 选项，用于显示转换
     * @param string $list 选项缓存列表名称
     * @author 蔡伟明 <314013107@qq.com>
     * @return \think\response\Json
     */
    public function getFilterList($token = '', $map = [], $options = '', $list = '')
    {
        if ($list != '') {
            $result = [
                'code' => 1,
                'msg'  => '请求成功',
                'list' => Cache::get($list)
            ];
            return json($result);
        }
        if ($token == '') {
            return json(['code' => 0, 'msg' => '缺少Token']);
        }

        $token_data = session($token);
        $table = $token_data['table'];
        $field = $token_data['field'];

        if ($field == '') {
            return json(['code' => 0, 'msg' => '缺少字段']);
        }
        if (!empty($map) && is_array($map)) {
            foreach ($map as &$item) {
                if (is_array($item)) {
                    foreach ($item as &$value) {
                        $value = trim($value);
                    }
                } else {
                    $item = trim($item);
                }
            }
        }

        if (strpos($table, '/')) {
            $data_list = model($table)->where($map)->group($field)->column($field);
        } else {
            $data_list = Db::name($table)->where($map)->group($field)->column($field);
        }

        if ($data_list === false) {
            return json(['code' => 0, 'msg' => '查询失败']);
        }

        if ($data_list) {
            if ($options != '') {
                // 从缓存获取选项数据
                $options = cache($options);
                if ($options) {
                    $temp_data_list = [];
                    foreach ($data_list as $item) {
                        $temp_data_list[$item] = isset($options[$item]) ? $options[$item] : '';
                    }
                    $data_list = $temp_data_list;
                } else {
                    $data_list = parse_array($data_list);
                }
            } else {
                $data_list = parse_array($data_list);
            }

            $result = [
                'code' => 1,
                'msg'  => '请求成功',
                'list' => $data_list
            ];
            return json($result);
        } else {
            return json(['code' => 0, 'msg' => '查询不到数据']);
        }
    }

    /**
     * 获取指定模块的菜单
     * @param string $module 模块名
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function getModuleMenus($module = '')
    {
        $menus = MenuModel::getMenuTree(0, '', $module);
        $result = [
            'code' => 1,
            'msg'  => '请求成功',
            'list' => format_linkage($menus)
        ];
        return json($result);
    }

    /**
     * 设置配色方案
     * @param string $theme 配色名称
     * @author 蔡伟明 <314013107@qq.com>
     */
    public function setTheme($theme = '') {
        $map['name'] = 'system_color';
        $map['group'] = 'system';
        if (Db::name('admin_config')->where($map)->setField('value', $theme)) {
            $this->success('设置成功');
        } else {
            $this->error('设置失败，请重试');
        }
    }

    /**
     * 获取侧栏菜单
     * @param string $module_id 模块id
     * @param string $module 模型名
     * @param string $controller 控制器名
     * @author 蔡伟明 <314013107@qq.com>
     * @return string
     */
    public function getSidebarMenu($module_id = '', $module = '', $controller = '')
    {
        role_auth();
        $menus = MenuModel::getSidebarMenu($module_id, $module, $controller);

        $output = '';
        foreach ($menus as $key => $menu) {
            if (!empty($menu['url_value'])) {
                $output = $menu['url_value'];
                break;
            }
            if (!empty($menu['child'])) {
                $output = $menu['child'][0]['url_value'];
                break;
            }
        }
        return $output;
    }

    /**
     * 检查附件是否存在
     * @param string $md5 文件md5
     * @author 蔡伟明 <314013107@qq.com>
     * @return \think\response\Json
     */
    public function check($md5 = '')
    {
        $md5 == '' && $this->error('参数错误');

        // 判断附件是否已存在
        if ($file_exists = AttachmentModel::get(['md5' => $md5])) {
            if ($file_exists['driver'] == 'local') {
                $file_path = PUBLIC_PATH.$file_exists['path'];
            } else {
                $file_path = $file_exists['path'];
            }
            return json([
                'code'   => 1,
                'info'   => '上传成功',
                'class'  => 'success',
                'id'     => $file_exists['id'],
                'path'   => $file_path
            ]);
        } else {
            $this->error('文件不存在');
        }
    }
}