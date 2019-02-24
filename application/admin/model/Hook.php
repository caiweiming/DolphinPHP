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

/**
 * 钩子模型
 * @package app\admin\model
 */
class Hook extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $name = 'admin_hook';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    /**
     * 添加钩子
     * @param array $hooks 钩子
     * @param string $plugin_name
     * @author 蔡伟明 <314013107@qq.com>
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function addHooks($hooks = [], $plugin_name = '')
    {
        if (!empty($hooks) && is_array($hooks)) {
            $data = [];
            foreach ($hooks as $name => $description) {
                if (is_numeric($name)) {
                    $name = $description;
                    $description = '';
                }
                if (self::where('name', $name)->find()) {
                    continue;
                }
                $data[] = [
                    'name'        => $name,
                    'plugin'      => $plugin_name,
                    'description' => $description,
                    'create_time' => request()->time(),
                    'update_time' => request()->time(),
                ];
            }
            if (!empty($data) && false === self::insertAll($data)) {
                return false;
            }
        }
        return true;
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
            if (false === self::where('plugin', $plugin_name)->delete()) {
                return false;
            }
        }
        return true;
    }
}
