<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2019 广东卓锐软件有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://dolphinphp.com
// +----------------------------------------------------------------------

namespace app\user\validate;

use think\Validate;

/**
 * 角色验证器
 * @package app\admin\validate
 * @author 蔡伟明 <314013107@qq.com>
 */
class Role extends Validate
{
    // 定义验证规则
    protected $rule = [
        'pid|所属角色'   => 'require',
        'name|角色名称' => 'require|unique:admin_role',
    ];
}
