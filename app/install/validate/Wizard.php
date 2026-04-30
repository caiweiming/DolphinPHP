<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2026 广东卓锐软件有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://www.dolphinphp.com
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\install\validate;

use think\Validate;

/**
 * 安装向导校验器
 */
class Wizard extends Validate
{
    protected $rule = [
        'hostname'         => 'require',
        'hostport'         => 'require|number',
        'database'         => 'require',
        'username'         => 'require',
        'prefix'           => 'require',
        'email'            => 'email',
        'nickname'         => 'max:64',
        'password'         => 'require|min:6|max:64|confirm:password_confirm',
        'password_confirm' => 'require|min:6|max:64',
    ];

    protected $message = [
        'password.require'         => '登录密码不能为空',
        'password.min'             => '登录密码不能少于6个字符',
        'password.max'             => '登录密码不能超过64个字符',
        'password.confirm'         => '两次输入的登录密码不一致',
        'password_confirm.require' => '确认密码不能为空',
        'password_confirm.min'     => '确认密码不能少于6个字符',
        'password_confirm.max'     => '确认密码不能超过64个字符',
    ];

    protected $scene = [
        'database' => ['hostname', 'hostport', 'database', 'username', 'prefix'],
        'admin'    => ['email', 'nickname', 'password', 'password_confirm'],
    ];
}
