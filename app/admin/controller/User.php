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

namespace app\admin\controller;

use app\common\annotation\Permission as AccessPermission;
use app\common\attribute\Permission;
use app\common\model\Department as DepartmentModel;
use app\common\model\Role as RoleModel;
use app\common\model\User as UserModel;
use app\common\interface\PermissionService;
use Exception;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\Paginator;
use think\response\Json;
use Throwable;

/**
 * 用户管理控制器
 * @package app\admin\controller
 */
#[Permission('用户管理', icon: 'ti ti-users', sort: 30)]
class User extends Auth
{
    /**
     * 用户模型
     * @var UserModel
     */
    protected UserModel $model;

    /**
     * 权限服务
     * @var PermissionService
     */
    protected PermissionService $permissionService;

    /**
     * 允许快速编辑的字段
     * @var array
     */
    protected array $quickEditFields = ['status'];

    /**
     * 初始化
     * @return void
     * @throws Throwable
     */
    protected function initialize(): void
    {
        parent::initialize();
        $this->model             = new UserModel();
        $this->permissionService = app(PermissionService::class);
    }

    /**
     * 用户列表
     * @return string|Json
     * @throws Exception
     */
    public function index(): string|Json
    {
        // 配置表格
        $this->table
            ->search('username,nickname,mobile,status,create_time')
            ->columns([
                ['id', 'ID', '', [], ['width' => 40]],
                ['username', '用户名', '', [], ['minWidth' => 120]],
                ['nickname', '昵称', '', [], ['minWidth' => 120]],
                ['department_id', '部门', DepartmentModel::column('name', 'id'), [], ['minWidth' => 150]],
                ['roles', '角色', 'callback', 'dp_join_array_column:name', ['minWidth' => 200]],
                ['mobile', '手机号', '', [], ['minWidth' => 120]],
                ['status', '状态', 'switch', ['1' => '启用', '0' => '禁用'], ['width' => 80]],
                ['last_login_time', '最后登录', 'datetime', [], ['width' => 160]],
                ['create_time', '创建时间', 'datetime', [], ['width' => 160]],
                ['right_button', '操作', 'actions', [
                    'edit',
                    [
                        'title' => '角色',
                        'url'   => dp_url('assignRole', ['id' => '__id__']),
                        'pop'   => true
                    ],
                    [
                        'title' => '密码',
                        'url'   => dp_url('resetPassword', ['id' => '__id__']),
                        'pop'   => true,
                    ],
                    'delete'
                ], ['width' => 200]]
            ])
            ->toolbar(['add', 'enable', 'disable', 'delete'])
            ->render();

        // 页面组装
        $this->page->row($this->table);

        return $this->fetch();
    }

    /**
     * 用户列表数据
     * @return Paginator
     * @throws DbException
     */
    protected function data(): Paginator
    {
        // 构建基础查询
        $query = $this->model->where($this->getSearchWhere());

        // 应用数据权限过滤
        $query = $this->permissionService->applyDataScope(
            $query,
            $this->getCurrentUserId()
        );

        // 查询数据
        return $query
            ->with([
                'department',
                'roles' => function ($query) {
                    $query->where('status', 1);
                }
            ])
            ->order('id', 'desc')
            ->paginate(dp_get_list_rows());
    }

    /**
     * 新增用户
     * @return string|Json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws Throwable
     */
    public function create(): string|Json
    {
        if ($this->request->isPost()) {
            $data = $this->request->post('', null, 'trim');

            // 自动验证
            $this->autoValidate('User.create');

            // 密码加密
            if ($data['password'] != '') {
                $data['password'] = dp_password_hash($data['password']);
            }

            // 处理角色ID（先保存，后面再关联）
            $roleIds = $data['role_ids'] ?? [];
            unset($data['role_ids']);

            // 保存数据
            $user = $this->model->create($data);

            // 分配角色(使用assignRoles方法进行权限检查)
            if (!empty($roleIds)) {
                $result = $this->model->assignRoles($user['id'], $roleIds);
                if (!$result) {
                    // 回滚用户创建
                    $user->delete();
                    $this->error($this->model->getError() ?: '角色分配失败');
                }
            }

            // 记录用户创建日志
            dp_log_user_action('创建用户', [
                'target_user_id' => $user['id'],
                'username'       => $user['username'],
                'nickname'       => $user['nickname'] ?? '',
                'department_id'  => $user['department_id'] ?? 0,
                'roles'          => $roleIds,
                'status'         => $user['status']
            ]);

            $this->success('新增成功', '', 'reload-table');
        }

        // 构建表单
        $this->form
            ->items([
                [
                    'type'     => 'text',
                    'name'     => 'username',
                    'label'    => '用户名',
                    'tips'     => '登录账号，唯一标识',
                    'required' => true
                ],
                [
                    'type'  => 'text',
                    'name'  => 'nickname',
                    'label' => '昵称',
                    'tips'  => '用户昵称，用于显示'
                ],
                [
                    'type'     => 'password',
                    'name'     => 'password',
                    'label'    => '密码',
                    'tips'     => '至少6个字符',
                    'switch'   => true,
                    'strength' => true,
                    'required' => true
                ],
                [
                    'type'  => 'text',
                    'name'  => 'email',
                    'label' => '邮箱',
                    'tips'  => '用户邮箱地址'
                ],
                [
                    'type'  => 'text',
                    'name'  => 'mobile',
                    'label' => '手机号',
                    'tips'  => '11位手机号码'
                ],
                [
                    'type'    => 'select2',
                    'name'    => 'department_id',
                    'label'   => '所属部门',
                    'tips'    => '选择用户所属部门',
                    'options' => dp_tree_to_options((new DepartmentModel())->getTree(), '无部门'),
                    'value'   => 0
                ],
                [
                    'type'    => 'checkbox',
                    'name'    => 'role_ids',
                    'label'   => '分配角色',
                    'tips'    => '可选择多个角色',
                    'inline'  => true,
                    'options' => (new RoleModel())->getList(),
                ],
                [
                    'type'  => 'switch',
                    'name'  => 'status',
                    'label' => '状态',
                    'tips'  => '是否启用该用户',
                    'value' => 1
                ],
                [
                    'type'  => 'textarea',
                    'name'  => 'remark',
                    'label' => '备注',
                    'tips'  => '用户备注信息',
                    'rows'  => 3
                ]
            ]);

        // 页面组装
        $this->page->row($this->form);

        return $this->fetch();
    }

    /**
     * 编辑用户
     * @return string|Json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws Throwable
     */
    public function edit(): string|Json
    {
        $id = $this->request->param('id/d', 0);

        // 获取用户信息
        $user = $this->model->with(['roles' => function ($query) {
            $query->where('status', 1);
        }])->find($id);

        if (!$user) {
            $this->error('用户不存在');
        }

        if ($this->request->isPost()) {
            $data = $this->request->post('', null, 'trim');

            // 自动验证
            $this->autoValidate('User.edit');

            // 移除验证用的ID字段
            unset($data['id']);

            // 如果是编辑自己的账号，移除状态字段（防止误禁用自己）
            if ($id == $this->getCurrentUserId()) {
                unset($data['status']);
                dp_log_user_action('编辑自己账号（状态字段已忽略）', [
                    'user_id' => $id,
                    'note'    => '编辑自己账号时不允许修改状态字段'
                ]);
            }

            // 保存前获取原始数据（用于日志记录）
            $oldData    = $user->toArray();
            $oldRoleIds = array_column($user['roles']->toArray(), 'id');

            // 处理密码（如果提供了新密码）
            $passwordChanged = false;
            if (!empty($data['password'])) {
                $data['password'] = dp_password_hash($data['password']);
                $passwordChanged  = true;
            } else {
                unset($data['password']);
            }

            // 处理角色ID
            $roleIds = $data['role_ids'] ?? [];
            unset($data['role_ids']);

            // 处理switch类型（只在非编辑自己时处理）
            if ($id != $this->getCurrentUserId()) {
                $data['status'] = $data['status'] ?? 0;
            }

            // 保存数据
            $user->save($data);

            // 更新角色关联(使用assignRoles方法进行权限检查)
            $result = $this->model->assignRoles($id, $roleIds);
            if (!$result) {
                $this->error($this->model->getError() ?: '角色分配失败');
            }

            // 定义字段标签
            $fieldLabels = [
                'username'      => '用户名',
                'nickname'      => '昵称',
                'password'      => '密码',
                'email'         => '邮箱',
                'mobile'        => '手机号',
                'department_id' => '所属部门',
                'status'        => '状态',
                'remark'        => '备注',
                'roles'         => '角色'
            ];

            // 比对字段变化
            $changes = dp_compare_data_changes($oldData, $data, $fieldLabels);

            // 如果密码被修改，单独标记
            if ($passwordChanged) {
                $changes['password'] = [
                    'label' => '密码',
                    'old'   => '***',
                    'new'   => '***'
                ];
            }

            // 检查角色是否变更
            if ($roleIds != $oldRoleIds) {
                $changes['roles'] = [
                    'label' => '角色',
                    'old'   => $oldRoleIds,
                    'new'   => $roleIds
                ];
            }

            // 记录日志（只在有实际变化时记录）
            if (!empty($changes)) {
                $logData = [
                    'target_user_id' => $id,
                    'username'       => $user['username'],
                    'changes'        => $changes
                ];

                dp_log_user_action('编辑用户信息', $logData);
            }

            $this->success('编辑成功', '', 'reload-table');
        }

        // 获取用户已有角色ID
        $userRoleIds = array_column($user['roles']->toArray(), 'id');

        // 准备数据
        $userData             = $user->toArray();
        $userData['role_ids'] = $userRoleIds;

        // 获取当前登录用户ID，判断是否编辑自己
        $isEditingSelf = ($id == $this->getCurrentUserId());

        // 构建表单
        $this->form
            ->data($userData)
            ->items([
                ['type' => 'hidden', 'name' => 'id'],
                [
                    'type'     => 'text',
                    'name'     => 'username',
                    'label'    => '用户名',
                    'tips'     => '登录账号，唯一标识',
                    'required' => true
                ],
                [
                    'type'  => 'text',
                    'name'  => 'nickname',
                    'label' => '昵称',
                    'tips'  => '用户昵称，用于显示'
                ],
                [
                    'type'     => 'password',
                    'name'     => 'password',
                    'label'    => '密码',
                    'switch'   => true,
                    'strength' => true,
                    'tips'     => '不修改请留空，修改请输入至少6个字符'
                ],
                [
                    'type'    => 'select2',
                    'name'    => 'department_id',
                    'label'   => '所属部门',
                    'tips'    => '选择用户所属部门',
                    'options' => dp_tree_to_options((new DepartmentModel())->getTree(), '无部门')
                ],
                [
                    'type'    => 'checkbox',
                    'name'    => 'role_ids',
                    'label'   => '分配角色',
                    'tips'    => '可选择多个角色',
                    'inline'  => true,
                    'options' => (new RoleModel())->getList()
                ],
                [
                    'type'  => 'text',
                    'name'  => 'email',
                    'label' => '邮箱',
                    'tips'  => '用户邮箱地址'
                ],
                [
                    'type'  => 'text',
                    'name'  => 'mobile',
                    'label' => '手机号',
                    'tips'  => '11位手机号码'
                ],
                [
                    'type'     => 'switch',
                    'name'     => 'status',
                    'label'    => '状态',
                    'tips'     => $isEditingSelf ? '编辑自己的账号时不能修改状态（防止误禁用）' : '是否启用该用户',
                    'disabled' => $isEditingSelf
                ],
                [
                    'type'  => 'textarea',
                    'name'  => 'remark',
                    'label' => '备注',
                    'tips'  => '用户备注信息',
                    'rows'  => 3
                ]
            ]);

        // 页面组装
        $this->page->row($this->form);

        return $this->fetch();
    }

    /**
     * 分配角色
     * @return string|Json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws Throwable
     */
    #[AccessPermission('admin.user.edit', '编辑')]
    public function assignRole(): string|Json
    {
        $id = $this->request->param('id/d', 0);

        // 获取用户信息
        $user = $this->model->with(['roles' => function ($query) {
            $query->where('status', 1);
        }])->find($id);

        if (!$user) {
            $this->error('用户不存在');
        }

        if ($this->request->isPost()) {
            $roleIds = $this->request->post('role_ids/a', []);

            // 使用模型方法分配角色
            if ($user->assignRoles($id, $roleIds)) {
                $this->success('角色分配成功', '', 'close-pop');
            } else {
                $this->error($user->getError());
            }
        }

        // 获取用户已有角色ID
        $userRoleIds = array_column($user['roles']->toArray(), 'id');

        // 构建表单
        $this->form
            ->data(['role_ids' => $userRoleIds])
            ->items([
                [
                    'type'  => 'html',
                    'name'  => 'user_info',
                    'label' => '用户信息',
                    'value' => '<div class="alert alert-info">
                        <strong>用户名：</strong>' . htmlspecialchars($user['username']) . '<br>
                        <strong>昵称：</strong>' . htmlspecialchars($user['nickname'] ?: '-') . '
                    </div>'
                ],
                [
                    'type'    => 'checkbox',
                    'name'    => 'role_ids',
                    'label'   => '选择角色',
                    'tips'    => '可选择多个角色',
                    'inline'  => true,
                    'options' => (new RoleModel())->getList()
                ],
            ]);

        // 页面组装
        $this->page->row($this->form);

        return $this->fetch();
    }

    /**
     * 重置密码
     * @return string|Json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws Throwable
     */
    #[AccessPermission('admin.user.edit', '编辑')]
    public function resetPassword(): string|Json
    {
        $id = $this->request->param('id/d', 0);

        // 获取用户信息
        $user = $this->model->find($id);

        if (!$user) {
            $this->error('用户不存在');
        }

        // 检查是否是超级管理员
        if ($user->isSuperAdmin()) {
            // 只有超级管理员才能重置超级管理员的密码
            $currentUser = $this->model->find($this->getCurrentUserId());
            if (!$currentUser || !$currentUser->isSuperAdmin()) {
                dp_log_security('尝试重置超级管理员密码被阻止', [
                    'target_user_id'  => $id,
                    'target_username' => $user['username'],
                    'reason'          => '只有超级管理员才能重置超级管理员的密码'
                ], 'error');
                $this->error('只有超级管理员才能重置超级管理员的密码');
            }
        }

        if ($this->request->isPost()) {
            $password        = $this->request->post('password', '');
            $confirmPassword = $this->request->post('confirm_password', '');

            // 验证密码
            if (empty($password)) {
                $this->error('请输入新密码');
            }

            if (strlen($password) < 6) {
                $this->error('密码长度至少6个字符');
            }

            if ($password !== $confirmPassword) {
                $this->error('两次输入的密码不一致');
            }

            // 更新密码
            $user['password'] = dp_password_hash($password);
            $user->save();

            // 记录日志
            dp_log_user_action('重置用户密码', [
                'target_user_id'  => $id,
                'target_username' => $user['username'],
                'operator_id'     => $this->getCurrentUserId(),
                'note'            => '管理员重置了用户密码'
            ], 'warning');

            $this->success('密码重置成功', '', 'close-pop');
        }

        // 构建表单
        $this->form
            ->items([
                [
                    'type'  => 'html',
                    'name'  => 'user_info',
                    'label' => '用户信息',
                    'value' => '<div class="alert alert-warning">
                        <strong>用户名：</strong>' . htmlspecialchars($user['username']) . '<br>
                        <strong>昵称：</strong>' . htmlspecialchars($user['nickname'] ?: '-') . '
                    </div>',
                    'tips'  => '密码重置后，该用户需要使用新密码登录'
                ],
                [
                    'type'     => 'password',
                    'name'     => 'password',
                    'label'    => '新密码',
                    'tips'     => '至少6个字符',
                    'switch'   => false,
                    'strength' => true,
                    'required' => true
                ],
                [
                    'type'     => 'password',
                    'name'     => 'confirm_password',
                    'label'    => '确认密码',
                    'tips'     => '请再次输入新密码',
                    'switch'   => false,
                    'required' => true
                ]
            ]);

        // 页面组装
        $this->page->row($this->form);

        return $this->fetch();
    }

    /**
     * 编辑前的钩子
     * @param $id
     * @param $field
     * @return true
     * @throws Throwable
     */
    protected function beforeQuickEdit($id, $field): bool
    {
        if ($field == 'status' && $this->getCurrentUserId() == $id) {
            dp_log_security('尝试修改自身账号状态', [
                'target_id' => $id,
                'reason'    => '用户不应该修改自己的状态（无论启用还是禁用）'
            ]);
            $this->error('不能修改自己的账号状态');
        }
        return true;
    }

    /**
     * 批量启用前的钩子
     * @param array $ids
     * @return bool
     * @throws Throwable
     */
    protected function beforeEnable(array $ids): bool
    {
        // 检查是否包含当前用户（不允许启用自己）
        if (in_array($this->getCurrentUserId(), $ids, true)) {
            dp_log_security('尝试在批量启用中启用自己', [
                'target_ids' => $ids,
                'reason'     => '用户不应该修改自己的状态（无论启用还是禁用）'
            ]);
            $this->error('不能在批量操作中修改自己的账号状态');
        }

        // 检查是否尝试启用超级管理员（虽然超级管理员不应该被禁用）
        $superAdminIds = config('system.super_admin.user_ids', [1]);
        $hasSuperAdmin = array_intersect($ids, $superAdminIds);
        if (!empty($hasSuperAdmin)) {
            // 检查这些超级管理员是否真的被禁用了
            $disabledSuperAdmins = $this->model
                ->whereIn('id', $hasSuperAdmin)
                ->where('status', 0)
                ->column('username', 'id');

            if (!empty($disabledSuperAdmins)) {
                dp_log_security('发现被禁用的超级管理员', [
                    'disabled_super_admins' => $disabledSuperAdmins,
                    'alert'                 => '超级管理员不应该被禁用，这可能是数据异常'
                ], 'error');
            }
        }

        return true;
    }

    /**
     * 批量启用后的钩子
     * @param array $ids 操作的用户ID数组
     * @param int|false $count 实际受影响的记录数，失败时为false
     * @return void
     * @throws Throwable
     */
    protected function afterEnable(array $ids, int|false $count): void
    {
        if (false === $count) {
            // 操作失败，记录错误日志
            dp_log_user_action('批量启用用户失败', [
                'target_ids'      => $ids,
                'total_requested' => count($ids),
                'error'           => '数据库操作失败'
            ], 'error');
        } elseif ($count > 0) {
            // 操作成功且有记录被修改
            $users = $this->model->whereIn('id', $ids)->column('username', 'id');

            dp_log_user_action('批量启用用户', [
                'target_ids'      => $ids,
                'target_users'    => $users,
                'affected_count'  => $count,
                'total_requested' => count($ids)
            ]);
        }
    }

    /**
     * 批量禁用前的钩子
     * @param array $ids
     * @return bool
     * @throws Throwable
     */
    protected function beforeDisable(array $ids): bool
    {
        // 检查是否包含当前用户（使用严格模式）
        if (in_array($this->getCurrentUserId(), $ids, true)) {
            dp_log_security('尝试在批量禁用中禁用自己', [
                'target_ids' => $ids
            ]);
            $this->error('批量禁用中包含了自己的账号，操作已取消');
        }

        // 检查是否包含超级管理员
        $superAdminIds = config('system.super_admin.user_ids', [1]);
        $hasSuperAdmin = array_intersect($ids, $superAdminIds);
        if (!empty($hasSuperAdmin)) {
            dp_log_security('尝试在批量禁用中禁用超级管理员', [
                'target_ids'      => $ids,
                'super_admin_ids' => $hasSuperAdmin
            ], 'error');
            $this->error('不能禁用超级管理员账号');
        }

        return true;
    }

    /**
     * 批量禁用后的钩子
     * @param array $ids 操作的用户ID数组
     * @param int|false $count 实际受影响的记录数，失败时为false
     * @return void
     * @throws Throwable
     */
    protected function afterDisable(array $ids, int|false $count): void
    {
        if (false === $count) {
            // 操作失败，记录错误日志
            dp_log_user_action('批量禁用用户失败', [
                'target_ids'      => $ids,
                'total_requested' => count($ids),
                'error'           => '数据库操作失败'
            ], 'error');
        } elseif ($count > 0) {
            // 操作成功且有记录被修改
            $users = $this->model->whereIn('id', $ids)->column('username', 'id');

            dp_log_user_action('批量禁用用户', [
                'target_ids'      => $ids,
                'target_users'    => $users,
                'affected_count'  => $count,
                'total_requested' => count($ids)
            ], 'warning');
        }
    }

    /**
     * 批量删除前的钩子（模型事件会额外检查）
     * @param array $ids
     * @return bool
     * @throws Throwable
     */
    protected function beforeDelete(array $ids): bool
    {
        // 检查是否包含当前用户
        if (in_array($this->getCurrentUserId(), $ids, true)) {
            dp_log_security('尝试在批量删除中删除自己', [
                'target_ids' => $ids
            ]);
            $this->error('批量删除中包含了自己的账号，操作已取消');
        }

        return true;
    }
}
