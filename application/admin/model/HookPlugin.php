<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2019 广东卓锐软件有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://dolphinphp.com
// +----------------------------------------------------------------------

namespace app\admin\model;

use think\Model;
use app\admin\model\Hook as HookModel;

/**
 * 钩子-插件模型
 * @package app\admin\model
 */
class HookPlugin extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $name = 'admin_hook_plugin';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    /**
     * 启用插件钩子
     * @param string $plugin 插件名称
     * @author 蔡伟明 <314013107@qq.com>
     * @return bool
     */
    public static function enable($plugin = '')
    {
        return self::where('plugin', $plugin)->setField('status', 1);
    }

    /**
     * 禁用插件钩子
     * @param string $plugin 插件名称
     * @author 蔡伟明 <314013107@qq.com>
     * @return int
     */
    public static function disable($plugin = '')
    {
        return self::where('plugin', $plugin)->setField('status', 0);
    }

    /**
     * 添加钩子-插件对照
     * @param array $hooks 钩子
     * @param string $plugin_name 插件名称
     * @author 蔡伟明 <314013107@qq.com>
     * @return bool|int|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function addHooks($hooks = [], $plugin_name = '')
    {
        if (!empty($hooks) && is_array($hooks)) {
            // 添加钩子
            if (!HookModel::addHooks($hooks, $plugin_name)) {
                return false;
            }

            $data = [];
            foreach ($hooks as $name => $description) {
                if (is_numeric($name)) {
                    $name = $description;
                }
                $data[] = [
                    'hook'        => $name,
                    'plugin'      => $plugin_name,
                    'create_time' => request()->time(),
                    'update_time' => request()->time(),
                ];
            }

            return self::insertAll($data);
        }
        return false;
    }

    /**
     * 删除钩子
     * @param string $plugin_name 钩子名称
     * @author 蔡伟明 <314013107@qq.com>
     * @return bool
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public static function deleteHooks($plugin_name = '')
    {
        if (!empty($plugin_name)) {
            // 删除钩子
            if (!HookModel::deleteHooks($plugin_name)) {
                return false;
            }
            if (false === self::where('plugin', $plugin_name)->delete()) {
                return false;
            }
        }
        return true;
    }

    /**
     * 钩子插件排序
     * @param string $hook 钩子
     * @param string $plugins 插件名
     * @author 蔡伟明 <314013107@qq.com>
     * @return bool
     */
    public static function sort($hook = '', $plugins = '')
    {
        if ($hook != '' && $plugins != '') {
            $plugins = is_array($plugins) ? $plugins : explode(',', $plugins);

            foreach ($plugins as $key => $plugin) {
                $map = [
                    'hook'   => $hook,
                    'plugin' => $plugin
                ];
                self::where($map)->setField('sort', $key + 1);
            }
        }

        return true;
    }
}
