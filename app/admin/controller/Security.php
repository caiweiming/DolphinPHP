<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\model\User as UserModel;
use app\common\render\Form;
use think\response\Json;

/**
 * 后台安全流程控制器
 */
class Security extends Auth
{
    /**
     * 密码过期后的独立改密页
     * @return string
     */
    public function passwordExpired(): string
    {
        $user = $this->getCurrentUser();

        $form = Form::make('admin_security_password_expired', '立即修改密码')
            ->action((string)dp_url('admin/security/updateExpiredPassword'))
            ->alert('密码已过期，请修改密码', '', 'warning')
            ->header(false)
            ->items([
                [
                    'type'  => 'html',
                    'name'  => 'username_static',
                    'label' => '用户名',
                    'value' => '<div class="form-control-plaintext">' . htmlspecialchars((string)$user->getAttr('username')) . '</div>',
                ],
                ['type' => 'password', 'name' => 'current_password', 'label' => '当前密码', 'switch' => false, 'required' => true],
                ['type' => 'password', 'name' => 'password', 'label' => '新密码', 'switch' => false, 'strength' => true, 'required' => true],
                ['type' => 'password', 'name' => 'confirm_password', 'label' => '确认新密码', 'switch' => false, 'required' => true],
            ]);

        $this->page->row($form);

        return $this->fetch();
    }

    /**
     * 提交密码过期后的新密码
     * @return Json
     */
    public function updateExpiredPassword(): Json
    {
        $user = $this->getCurrentUser();
        $data = [
            'current_password' => (string)$this->request->post('current_password', ''),
            'password'         => (string)$this->request->post('password', ''),
            'confirm_password' => (string)$this->request->post('confirm_password', ''),
        ];

        $this->autoValidate(\app\admin\validate\Profile::class . '.password', $data);

        if (!dp_password_check($data['current_password'], (string)$user->getAttr('password'))) {
            return json([
                'code' => 0,
                'msg'  => '当前密码不正确',
            ]);
        }

        $user->save([
            'password'              => dp_password_hash($data['password']),
            'password_updated_time' => time(),
        ]);

        dp_clear_admin_password_expiry_required();
        dp_clear_admin_password_expiry_shell_target();

        dp_log_user_action('强制修改过期密码', [
            'target_user_id' => $user->getAttr('id'),
            'username'       => $user->getAttr('username'),
        ], 'warning');

        return json([
            'code' => 1,
            'msg'  => '密码修改成功',
            'url'  => '/admin/index/index.html',
            'data' => [
                'clear_password_form' => true,
            ],
        ]);
    }

    /**
     * 获取当前登录用户
     * @return UserModel
     */
    protected function getCurrentUser(): UserModel
    {
        /** @var UserModel $user */
        $user = UserModel::findOrFail($this->getCurrentUserId());

        return $user;
    }
}
