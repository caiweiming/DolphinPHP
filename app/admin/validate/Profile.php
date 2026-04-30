<?php
declare(strict_types=1);

namespace app\admin\validate;

use think\Validate;

/**
 * 后台账号管理验证器
 */
final class Profile extends Validate
{
    /**
     * 验证规则
     * @var array<string, string>
     */
    protected $rule = [
        'nickname'         => 'max:50',
        'email'            => 'email|max:100',
        'mobile'           => 'mobile',
        'avatar'           => 'integer|egt:0',
        'current_password' => 'require',
        'password'         => 'require|min:6|max:32',
        'confirm_password' => 'require|confirm:password',
    ];

    /**
     * 错误消息
     * @var array<string, string>
     */
    protected $message = [
        'nickname.max'          => '昵称最多50个字符',
        'email.email'           => '邮箱格式不正确',
        'email.max'             => '邮箱最多100个字符',
        'mobile.mobile'         => '手机号格式不正确',
        'avatar.integer'        => '头像参数不正确',
        'avatar.egt'            => '头像参数不正确',
        'current_password.require' => '请输入当前密码',
        'password.require'      => '请输入新密码',
        'password.min'          => '密码至少6个字符',
        'password.max'          => '密码最多32个字符',
        'confirm_password.require' => '请输入确认密码',
        'confirm_password.confirm' => '两次输入的密码不一致',
    ];

    /**
     * 场景
     * @var array<string, array<int, string>>
     */
    protected $scene = [
        'edit'     => ['nickname', 'email', 'mobile', 'avatar'],
        'password' => ['current_password', 'password', 'confirm_password'],
    ];
}
