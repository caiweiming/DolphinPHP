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

// 门户模块公共函数库
use think\Db;

if (!function_exists('get_column_name')) {
    /**
     * 获取栏目名称
     * @param int $cid 栏目id
     * @author 蔡伟明 <314013107@qq.com>
     * @return string
     */
    function get_column_name($cid = 0)
    {
        $column_list = model('cms/column')->getList();
        return isset($column_list[$cid]) ? $column_list[$cid]['name'] : '';
    }
}

if (!function_exists('get_model_name')) {
    /**
     * 获取内容模型名称
     * @param string $id 内容模型id
     * @author 蔡伟明 <314013107@qq.com>
     * @return string
     */
    function get_model_name($id = '')
    {
        $model_list = model('cms/model')->getList();
        return isset($model_list[$id]) ? $model_list[$id]['name'] : '';
    }
}

if (!function_exists('get_model_title')) {
    /**
     * 获取内容模型标题
     * @param string $id 内容模型标题
     * @author 蔡伟明 <314013107@qq.com>
     * @return string
     */
    function get_model_title($id = '')
    {
        $model_list = model('cms/model')->getList();
        return isset($model_list[$id]) ? $model_list[$id]['title'] : '';
    }
}

if (!function_exists('get_model_type')) {
    /**
     * 获取内容模型类别：0-系统，1-普通，2-独立
     * @param int $id 模型id
     * @author 蔡伟明 <314013107@qq.com>
     * @return string
     */
    function get_model_type($id = 0)
    {
        $model_list = model('cms/model')->getList();
        return isset($model_list[$id]) ? $model_list[$id]['type'] : '';
    }
}

if (!function_exists('get_model_table')) {
    /**
     * 获取内容模型附加表名
     * @param int $id 模型id
     * @author 蔡伟明 <314013107@qq.com>
     * @return string
     */
    function get_model_table($id = 0)
    {
        $model_list = model('cms/model')->getList();
        return isset($model_list[$id]) ? $model_list[$id]['table'] : '';
    }
}

if (!function_exists('is_default_field')) {
    /**
     * 检查是否为系统默认字段
     * @param string $field 字段名称
     * @author 蔡伟明 <314013107@qq.com>
     * @return bool
     */
    function is_default_field($field = '')
    {
        $system_fields = cache('cms_system_fields');
        if (!$system_fields) {
            $system_fields = Db::name('cms_field')->where('model', 0)->column('name');
            cache('cms_system_fields', $system_fields);
        }
        return in_array($field, $system_fields, true);
    }
}

if (!function_exists('table_exist')) {
    /**
     * 检查附加表是否存在
     * @param string $table_name 附加表名
     * @author 蔡伟明 <314013107@qq.com>
     * @return string
     */
    function table_exist($table_name = '')
    {
        return true == Db::query("SHOW TABLES LIKE '{$table_name}'");
    }
}

if (!function_exists('time_tran')) {
    /**
     * 转换时间
     * @param int $timer 时间戳
     * @author 蔡伟明 <314013107@qq.com>
     * @return string
     */
    function time_tran($timer)
    {
        $diff = $_SERVER['REQUEST_TIME'] - $timer;
        $day  = floor($diff / 86400);
        $free = $diff % 86400;
        if ($day > 0) {
            return $day . " 天前";
        } else {
            if ($free > 0) {
                $hour = floor($free / 3600);
                $free = $free % 3600;
                if ($hour > 0) {
                    return $hour . " 小时前";
                } else {
                    if ($free > 0) {
                        $min = floor($free / 60);
                        $free = $free % 60;
                        if ($min > 0) {
                            return $min . " 分钟前";
                        } else {
                            if ($free > 0) {
                                return $free . " 秒前";
                            } else {
                                return '刚刚';
                            }
                        }
                    } else {
                        return '刚刚';
                    }
                }
            } else {
                return '刚刚';
            }
        }
    }
}