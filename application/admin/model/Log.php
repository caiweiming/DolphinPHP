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
 * 日志记录模型
 * @package app\admin\model
 */
class Log extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $name = 'admin_log';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    /**
     * 获取所有日志
     * @param array $map 条件
     * @param string $order 排序
     * @author 蔡伟明 <314013107@qq.com>
     * @return \think\Paginator
     * @throws \think\exception\DbException
     */
    public static function getAll($map = [], $order = '')
    {
        $data_list = self::view('admin_log', true)
            ->view('admin_action', 'title,module', 'admin_action.id=admin_log.action_id', 'left')
            ->view('admin_user', 'username', 'admin_user.id=admin_log.user_id', 'left')
            ->view('admin_module', ['title' => 'module_title'], 'admin_module.name=admin_action.module')
            ->where($map)
            ->order($order)
            ->paginate();
        return $data_list;
    }
}
