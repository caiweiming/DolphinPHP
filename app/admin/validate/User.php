<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2026 广东卓锐软件有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://www.dolphinphp.com
// +----------------------------------------------------------------------
// | 作者: 蔡伟明 <314013107@qq.com>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\admin\validate;

use think\Validate;

/**
 * 用户验证器
 * @package app\admin\validate
 */
class User extends Validate
{
    /**
     * 验证规则
     * @var array
     */
    protected $rule = [
        'username'      => 'require|max:50|unique:admin_user',
        'nickname'      => 'max:50',
        'password'      => 'require|min:6|max:32',
        'email'         => 'email|max:100|unique:admin_user',
        'mobile'        => 'mobile',
        'department_id' => 'integer',
        'status'        => 'in:0,1',
        'captcha'       => 'min:4|max:4',
    ];

    /**
     * 验证提示
     * @var array
     */
    protected $message = [
        'username.require'      => '用户名不能为空',
        'username.max'          => '用户名最多50个字符',
        'username.unique'       => '用户名已存在',
        'nickname.max'          => '昵称最多50个字符',
        'password.require'      => '密码不能为空',
        'password.min'          => '密码至少6个字符',
        'password.max'          => '密码最多32个字符',
        'email.email'           => '邮箱格式不正确',
        'email.max'             => '邮箱最多100个字符',
        'email.unique'          => '邮箱已被使用',
        'mobile.mobile'         => '手机号格式不正确',
        'department_id.integer' => '部门ID必须是整数',
        'status.in'             => '状态值不正确',
    ];

    /**
     * 验证场景
     * @var array
     */
    protected $scene = [
        'create' => ['username', 'nickname', 'password', 'email', 'mobile', 'department_id', 'status'],
        'edit'   => ['username', 'nickname', 'email', 'mobile', 'department_id', 'status'],
        'login'  => ['captcha'],
    ];

    /**
     * 登录场景定义
     * @return User
     */
    public function sceneLogin(): User
    {
        // 登录时只需要验证用户名格式，不需要检查唯一性
        return $this->only(['username', 'password', 'captcha'])
            ->remove('username', 'unique')
            ->remove('password', 'min|max');
    }
}
