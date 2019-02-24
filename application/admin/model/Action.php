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
 * 日志模型
 * @package app\admin\model
 */
class Action extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $name = 'admin_action';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;
}
