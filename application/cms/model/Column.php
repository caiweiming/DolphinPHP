<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2019 广东卓锐软件有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://dolphinphp.com
// +----------------------------------------------------------------------

namespace app\cms\model;

use think\Model as ThinkModel;
use util\Tree;

/**
 * 栏目模型
 * @package app\cms\model
 */
class Column extends ThinkModel
{
    // 设置当前模型对应的完整数据表名称
    protected $name = 'cms_column';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    /**
     * 标题获取器
     * @param $value
     * @param $data
     * @author 蔡伟明 <314013107@qq.com>
     */
    protected function getTitleAttr($value, $data) {
        switch ($data['type']) {
            case 0: // 栏目
                break;
            case 1: // 单页
                break;
        }
    }

    /**
     * 获取栏目列表
     * @author 蔡伟明 <314013107@qq.com>
     * @return array|mixed
     */
    public static function getList()
    {
        $data_list = cache('cms_column_list');
        if (!$data_list) {
            $data_list = self::where('status', 1)->column(true, 'id');
            // 非开发模式，缓存数据
            if (config('develop_mode') == 0) {
                cache('cms_column_list', $data_list);
            }
        }
        return $data_list;
    }

    /**
     * 获取树状栏目
     * @param int $id 需要隐藏的栏目id
     * @param string $default 默认第一个节点项，默认为“顶级栏目”，如果为false则不显示，也可传入其他名称
     * @author 蔡伟明 <314013107@qq.com>
     * @return array|mixed
     */
    public static function getTreeList($id = 0, $default = '')
    {
        $result[0] = '顶级栏目';

        // 排除指定节点及其子节点
        $where = [
            ['status', '=', 1]
        ];
        if ($id !== 0) {
            $hide_ids = array_merge([$id], self::getChildsId($id));
            $where[]  = ['id', 'not in', $hide_ids];
        }

        $data_list = Tree::config(['title' => 'name'])->toList(self::where($where)->order('pid,id')->column('id,pid,name'));
        foreach ($data_list as $item) {
            $result[$item['id']] = $item['title_display'];
        }

        // 设置默认节点项标题
        if ($default != '') {
            $result[0] = $default;
        }

        // 隐藏默认节点项
        if ($default === false) {
            unset($result[0]);
        }

        return $result;
    }

    /**
     * 获取所有子栏目id
     * @param int $pid 父级id
     * @author 蔡伟明 <314013107@qq.com>
     * @return array
     */
    public static function getChildsId($pid = 0)
    {
        $ids = self::where('pid', $pid)->column('id');
        foreach ($ids as $value) {
            $ids = array_merge($ids, self::getChildsId($value));
        }
        return $ids;
    }

    /**
     * 获取指定栏目数据
     * @param int $cid 栏目id
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed|static
     */
    public static function getInfo($cid = 0)
    {
        $result = cache('cms_column_info_'. $cid);
        if (!$result) {
            $result = self::get($cid);
            // 非开发模式，缓存数据
            if (config('develop_mode') == 0) {
                cache('cms_column_info_'. $cid, $result);
            }
        }
        return $result;
    }
}
