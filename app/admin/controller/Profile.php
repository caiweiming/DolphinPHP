<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\model\User as UserModel;
use app\common\render\Form;
use think\response\Json;

/**
 * 后台账号管理控制器
 */
class Profile extends Auth
{
    /**
     * 账号管理页
     * @return string
     */
    public function index(): string
    {
        $user = $this->getCurrentProfileUser();
        $this->page->title('账号管理');

        $profileForm = Form::make('admin_profile_edit', '基础资料')
            ->action((string)dp_url('admin/profile/edit'))
            ->header(false)
            ->items([
                [
                    'type'  => 'html',
                    'name'  => 'username_static',
                    'label' => '用户名',
                    'value' => '<div class="form-control-plaintext">' . htmlspecialchars((string)$user->getAttr('username')) . '</div>',
                ],
                ['type' => 'image', 'name' => 'avatar', 'label' => '头像'],
                ['type' => 'text', 'name' => 'nickname', 'label' => '昵称'],
                ['type' => 'text', 'name' => 'email', 'label' => '邮箱'],
                ['type' => 'text', 'name' => 'mobile', 'label' => '手机号'],
            ])
            ->data($user);

        $passwordForm = Form::make('admin_profile_password', '安全设置')
            ->action((string)dp_url('admin/profile/password'))
            ->header(false)
            ->items([
                ['type' => 'password', 'name' => 'current_password', 'label' => '当前密码', 'switch' => false, 'required' => true],
                ['type' => 'password', 'name' => 'password', 'label' => '新密码', 'switch' => false, 'strength' => true, 'required' => true],
                ['type' => 'password', 'name' => 'confirm_password', 'label' => '确认新密码', 'switch' => false, 'required' => true],
            ]);

        $this->page->tabs([
            'base' => [
                'title' => '基础资料',
                'form'  => $profileForm,
            ],
            'security' => [
                'title' => '安全设置',
                'form'  => $passwordForm,
            ],
        ], [
            'id'       => 'admin-profile-tabs',
            'active'   => $this->resolveActiveTab(),
            'remember' => true,
        ]);

        return $this->fetch();
    }

    /**
     * 保存基础资料
     * @return Json
     */
    public function edit(): Json
    {
        $user = $this->getCurrentProfileUser();
        $data = $this->extractEditableProfileInput();

        $this->autoValidate(\app\admin\validate\Profile::class . '.edit', $data);

        $oldData  = $user->toArray();
        $saveData = $this->buildProfileUpdatePayload($data, $oldData);
        $user->save($saveData);

        dp_log_user_action('更新个人资料', [
            'target_user_id' => $user->getAttr('id'),
            'changes'        => dp_compare_data_changes($oldData, $saveData, [
                'nickname' => '昵称',
                'email'    => '邮箱',
                'mobile'   => '手机号',
                'phone'    => '手机号',
                'avatar'   => '头像',
            ]),
        ]);

        return json([
            'code' => 1,
            'msg'  => '资料保存成功',
            'data' => [
                'refresh_user_summary' => true,
                'current_user_summary' => dp_build_admin_current_user_summary($user->toArray()),
            ],
        ]);
    }

    /**
     * 修改个人密码
     * @return Json
     */
    public function password(): Json
    {
        $user = $this->getCurrentProfileUser();
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

        dp_log_user_action('修改个人密码', [
            'target_user_id' => $user->getAttr('id'),
            'username'       => $user->getAttr('username'),
        ], 'warning');

        return json([
            'code' => 1,
            'msg'  => '密码修改成功',
            'data' => [
                'clear_password_form' => true,
            ],
        ]);
    }

    /**
     * 获取当前登录用户
     * @return UserModel
     */
    protected function getCurrentProfileUser(): UserModel
    {
        /** @var UserModel $user */
        $user = UserModel::findOrFail($this->getCurrentUserId());

        return $user;
    }

    /**
     * 提取允许修改的资料字段
     * @return array<string, mixed>
     */
    protected function extractEditableProfileInput(): array
    {
        return [
            'nickname' => trim((string)$this->request->post('nickname', '')),
            'email'    => trim((string)$this->request->post('email', '')),
            'mobile'   => trim((string)$this->request->post('mobile', $this->request->post('phone', ''))),
            'avatar'   => (int)$this->request->post('avatar', 0),
        ];
    }

    /**
     * 构建最终保存字段
     * @param array<string, mixed> $input
     * @param array<string, mixed> $currentData
     * @return array<string, mixed>
     */
    protected function buildProfileUpdatePayload(array $input, array $currentData): array
    {
        $payload = [
            'nickname' => $input['nickname'],
            'email'    => $input['email'],
            'avatar'   => $input['avatar'],
        ];

        if (array_key_exists('mobile', $currentData)) {
            $payload['mobile'] = $input['mobile'];
        } elseif (array_key_exists('phone', $currentData)) {
            $payload['phone'] = $input['mobile'];
        } else {
            $payload['mobile'] = $input['mobile'];
        }

        return $payload;
    }

    /**
     * 解析当前激活页签
     * @return string
     */
    protected function resolveActiveTab(): string
    {
        $tab = trim((string)$this->request->param('tab', 'base'));
        return in_array($tab, ['base', 'security'], true) ? $tab : 'base';
    }
}
