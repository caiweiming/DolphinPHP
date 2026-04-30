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

namespace app\common\model;

use app\admin\model\File;
use app\common\helper\Logger;
use app\common\interface\PermissionService as PermissionServiceInterface;
use Exception;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\exception\ValidateException;
use think\Model;
use think\model\relation\BelongsTo;
use think\model\relation\BelongsToMany;
use Throwable;

/**
 * 用户模型
 * @package app\common\model
 */
class User extends Base
{
    /**
     * 用户表名
     * @var string
     */
    protected $name = 'admin_user';

    /**
     * 类型转换
     * @var array
     */
    protected $type = [
        'id'                 => 'integer',
        'avatar'             => 'integer',
        'department_id'      => 'integer',
        'status'             => 'integer',
        'login_count'        => 'integer',
        'last_login_time'    => 'integer',
        'remember_expires_at'=> 'integer',
        'create_time'        => 'integer',
        'update_time'        => 'integer',
        'delete_time'        => 'integer',
    ];

    /**
     * 隐藏字段
     * @var array
     */
    protected $hidden = ['password', 'remember_selector', 'remember_token_hash', 'remember_expires_at'];

    /**
     * 获取部门关联
     * @return BelongsTo
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    /**
     * 获取角色关联(多对多)
     * @return BelongsToMany
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            'admin_user_role',
            'role_id',
            'user_id'
        );
    }

    /**
     * 获取头像文件关联
     * @return BelongsTo
     */
    public function avatarFile(): BelongsTo
    {
        return $this->belongsTo(File::class, 'avatar');
    }

    /**
     * 获取用户信息
     * @param string $username 用户名/手机号/邮箱
     * @param bool $withPassword 是否包含密码字段（用于登录验证）
     * @return Model|array|null|User
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getInfo(string $username = '', bool $withPassword = false): Model|array|null|User
    {
        // TODO:增加手机号、邮箱登录
        $query = $this->where('username', $username);

        // 默认不输出password字段
        if (!$withPassword) {
            $query = $query->withoutField(['password']);
        }

        return $query->find();
    }

    /**
     * 根据用户id获取用户信息
     * @param int $id
     * @return User|array|Model|null
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getInfoById(int $id): User|array|Model|null
    {
        return $this->where('id', $id)->find();
    }

    /**
     * 根据 remember selector 获取用户信息
     * @param string $selector
     * @return User|array|Model|null
     */
    public function getInfoByRememberSelector(string $selector): User|array|Model|null
    {
        return $this->where('remember_selector', $selector)->find();
    }

    /**
     * 根据用户id更新数据
     * @param int $id 用户id
     * @param array $data 要更新的用户数据
     * @return bool|int
     */
    public function updateById(int $id, array $data): bool|int
    {
        if (empty($id) || empty($data)) {
            throw new ValidateException('dp#invalid parameter');
        }

        return $this->where('id', $id)->save($data);
    }

    /**
     * 更新 remember-me 令牌
     * @param int $id
     * @param string $selector
     * @param string $tokenHash
     * @param int $expiresAt
     * @return bool|int
     */
    public function updateRememberTokenById(int $id, string $selector, string $tokenHash, int $expiresAt): bool|int
    {
        return $this->updateById($id, [
            'remember_selector'   => $selector,
            'remember_token_hash' => $tokenHash,
            'remember_expires_at' => $expiresAt,
        ]);
    }

    /**
     * 清理 remember-me 令牌
     * @param int $id
     * @return bool|int
     */
    public function clearRememberTokenById(int $id): bool|int
    {
        return $this->updateById($id, [
            'remember_selector'   => '',
            'remember_token_hash' => '',
            'remember_expires_at' => 0,
        ]);
    }

    /**
     * 获取用户的所有角色
     * @param int|null $userId 用户ID,null表示当前模型实例
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getRoles(?int $userId = null): array
    {
        if ($userId === null) {
            $userId = $this['id'];
        }

        if (!$userId) {
            return [];
        }

        $user = $this->with(['roles' => function ($query) {
            $query->where('status', 1);
        }])->find($userId);
        return $user && $user['roles'] ? $user['roles']->toArray() : [];
    }

    /**
     * 获取用户的角色ID列表
     * @param int|null $userId 用户ID,null表示当前模型实例
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getRoleIds(?int $userId = null): array
    {
        $roles = $this->getRoles($userId);
        return array_column($roles, 'id');
    }

    /**
     * 获取用户的所有权限(通过角色聚合)
     * @param int|null $userId 用户ID,null表示当前模型实例
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getPermissions(?int $userId = null): array
    {
        $roles = $this->getRoles($userId);
        if (empty($roles)) {
            return [];
        }

        // 收集所有角色ID
        $roleIds = array_column($roles, 'id');

        // 批量查询所有权限（避免N+1问题）
        return Role::getPermissionsByRoleIds($roleIds);
    }

    /**
     * 获取用户的权限ID列表
     * @param int|null $userId 用户ID,null表示当前模型实例
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getPermissionIds(?int $userId = null): array
    {
        $permissions = $this->getPermissions($userId);
        return array_column($permissions, 'id');
    }

    /**
     * 获取用户的权限标识列表
     * @param int|null $userId 用户ID,null表示当前模型实例
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getPermissionCodes(?int $userId = null): array
    {
        $permissions = $this->getPermissions($userId);
        return array_column($permissions, 'code');
    }

    /**
     * 检查用户是否拥有指定权限
     * @param string|array $permissionCode 权限标识,支持数组
     * @param int|null $userId 用户ID,null表示当前模型实例
     * @param string $logic 逻辑关系: and(全部满足) 或 or(满足任一)
     * @return bool
     */
    public function hasPermission(string|array $permissionCode, ?int $userId = null, string $logic = 'or'): bool
    {
        $targetUserId = $userId ?? (int)($this['id'] ?? 0);
        if ($targetUserId <= 0) {
            return false;
        }

        return app(PermissionServiceInterface::class)->hasPermission($targetUserId, $permissionCode, $logic);
    }

    /**
     * 获取用户的数据权限范围
     * @param int|null $userId 用户ID,null表示当前模型实例
     * @return int 返回最大的数据范围
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getDataScope(?int $userId = null): int
    {
        $roles = $this->getRoles($userId);
        if (empty($roles)) {
            // 默认仅本人
            return Role::DATA_SCOPE_SELF;
        }

        // 取所有角色中最大的数据范围
        $maxScope = Role::DATA_SCOPE_SELF;
        foreach ($roles as $role) {
            if ($role['data_scope'] < $maxScope) {
                $maxScope = $role['data_scope'];
            }
        }

        return $maxScope;
    }

    /**
     * 为用户分配角色
     * @param int $userId 用户ID
     * @param array $roleIds 角色ID数组
     * @return bool
     * @throws Throwable
     */
    public function assignRoles(int $userId, array $roleIds): bool
    {
        try {
            $user = $this->find($userId);
            if (!$user) {
                $this->error = '用户不存在';
                return false;
            }

            // 获取当前操作用户
            $currentUser      = session(config('system.admin_session'));
            $currentUserId    = $currentUser['id'] ?? 0;
            $currentUserModel = $currentUserId ? $this->find($currentUserId) : null;

            // 获取目标用户的原有角色
            $oldRoleIds = $user->getRoleIds();

            // 检查是否尝试分配超级管理员角色
            $superRoleIds    = config('system.super_admin.role_ids', [1]);
            $hasNewSuperRole = !empty(array_intersect($roleIds, $superRoleIds));
            $hadOldSuperRole = !empty(array_intersect($oldRoleIds, $superRoleIds));

            // 如果尝试分配超级管理员角色
            if ($hasNewSuperRole) {
                // 只有超级管理员才能分配超级管理员角色
                if (!$currentUserModel || !$currentUserModel->isSuperAdmin()) {
                    dp_log('尝试分配超级管理员角色被阻止')
                        ->type(Logger::TYPE_SECURITY)
                        ->context([
                            'target_user_id'  => $userId,
                            'target_username' => $user['username'],
                            'role_ids'        => $roleIds,
                        ])
                        ->error();

                    $this->error = '您没有权限分配超级管理员角色';
                    return false;
                }

                // 记录分配超级管理员角色的日志
                dp_log('分配超级管理员角色')
                    ->type(Logger::TYPE_SECURITY)
                    ->context([
                        'target_user_id'  => $userId,
                        'target_username' => $user['username'],
                        'role_ids'        => $roleIds,
                    ])
                    ->warning();
            }

            // 如果目标用户是超级管理员,检查是否尝试移除超级管理员角色
            if ($hadOldSuperRole && !$hasNewSuperRole) {
                // 检查配置是否允许降权
                $allowDemote     = config('system.permission.allow_demote_super_admin', false);
                $allowSelfDemote = config('system.permission.allow_self_demote', false);

                // 不允许自己移除自己的超级管理员权限
                if ($userId == $currentUserId && !$allowSelfDemote) {
                    dp_log('尝试自我降权被阻止')
                        ->type(Logger::TYPE_SECURITY)
                        ->context([
                            'user_id'  => $userId,
                            'username' => $user['username'],
                        ])
                        ->error();

                    $this->error = '不能移除自己的超级管理员权限';
                    return false;
                }

                // 不允许降权其他超级管理员(配置控制)
                if (dp_current_user_id() != 1 && $userId != $currentUserId && !$allowDemote) {
                    dp_log('尝试降权超级管理员被阻止')
                        ->type(Logger::TYPE_SECURITY)
                        ->context([
                            'target_user_id'  => $userId,
                            'target_username' => $user['username'],
                        ])
                        ->error();

                    $this->error = '不允许移除超级管理员的权限';
                    return false;
                }

                // 记录降权日志
                dp_log('移除超级管理员角色')
                    ->type(Logger::TYPE_SECURITY)
                    ->context([
                        'target_user_id'  => $userId,
                        'target_username' => $user['username'],
                        'old_role_ids'    => $oldRoleIds,
                        'new_role_ids'    => $roleIds,
                    ])
                    ->warning();
            }

            // 先删除原有角色
            $user->roles()->detach();

            // 分配新角色
            if (!empty($roleIds)) {
                $user->roles()->attach($roleIds);
            }

            // 清除用户权限缓存
            $this->clearUserPermissionCache($userId);

            // 记录角色分配日志
            dp_log('分配用户角色')
                ->type(Logger::TYPE_USER_ACTION)
                ->context([
                    'target_user_id'  => $userId,
                    'target_username' => $user['username'],
                    'old_role_ids'    => $oldRoleIds,
                    'new_role_ids'    => $roleIds,
                ])
                ->info();

            return true;
        } catch (Exception $e) {
            $this->error = '角色分配失败: ' . $e->getMessage();
            return false;
        }
    }

    /**
     * 清除用户权限缓存
     * @param int $userId 用户ID
     * @return void
     */
    public function clearUserPermissionCache(int $userId): void
    {
        app(PermissionServiceInterface::class)->clearUserCache($userId);
    }

    /**
     * 检查用户状态是否正常
     * @return bool
     */
    public function isActive(): bool
    {
        return $this['status'] === 1 && $this['delete_time'] === null;
    }

    /**
     * 更新最后登录信息
     * @param string $ip 登录IP
     * @return bool
     */
    public function updateLoginInfo(string $ip): bool
    {
        $this['login_count']     = $this['login_count'] + 1;
        $this['last_login_time'] = time();
        $this['last_login_ip']   = $ip;
        return $this->save();
    }

    /**
     * 判断是否超级管理员
     * 使用多重检查机制确保安全性
     * @return bool
     */
    public function isSuperAdmin(): bool
    {
        // 检查1: 标识字段(优先级最高)
        if ($this['is_super_admin'] == 1) {
            return true;
        }

        // 检查2: ID检查
        $superAdminIds = config('system.super_admin.user_ids', [1]);
        if (in_array($this['id'], $superAdminIds)) {
            return true;
        }

        // 检查3: 角色检查
        try {
            $superRoleIds = config('system.super_admin.role_ids', [1]);
            $userRoleIds  = $this->getRoleIds();
            if (!empty(array_intersect($userRoleIds, $superRoleIds))) {
                return true;
            }
        } catch (Throwable) {
            // 如果角色检查失败,记录日志但不影响其他检查
        }

        return false;
    }

    /**
     * 判断用户是否受保护(不可删除)
     * @return bool
     */
    public function isProtected(): bool
    {
        // 超级管理员不可删除
        if ($this->isSuperAdmin()) {
            return true;
        }

        // 检查配置的受保护用户列表
        $protectedUsers = config('system.super_admin.protected_users', [1]);
        return in_array($this['id'], $protectedUsers);
    }

    /**
     * 删除前事件
     * 用于处理用户删除前的业务逻辑
     * @param User $model
     * @return true 返回false会中止删除
     * @throws Exception|Throwable
     */
    public static function onBeforeDelete(User $model): true
    {
        // 获取当前登录用户ID
        $currentUser   = session(config('system.admin_session'));
        $currentUserId = $currentUser['id'] ?? 0;

        // 检查是否是当前登录用户
        if ($model['id'] == $currentUserId) {
            throw new Exception('不能删除自己的账号');
        }

        // 检查是否是超级管理员
        if ($model->isSuperAdmin()) {
            dp_log('尝试删除超级管理员被阻止')
                ->type(Logger::TYPE_SECURITY)
                ->context([
                    'target_user_id'  => $model['id'],
                    'target_username' => $model['username'],
                ])
                ->error();
            throw new Exception('超级管理员账号不能删除');
        }

        // 检查是否是受保护用户
        if ($model->isProtected()) {
            dp_log('尝试删除受保护用户被阻止')
                ->type(Logger::TYPE_SECURITY)
                ->context([
                    'target_user_id'  => $model['id'],
                    'target_username' => $model['username'],
                ])
                ->error();
            throw new Exception('该用户账号受保护,不允许删除');
        }

        // 解除所有角色关联
        $model->roles()->detach();

        // 记录删除前的日志(包含用户信息)
        dp_log('删除用户')
            ->type(Logger::TYPE_USER_ACTION)
            ->context([
                'user_id'  => $model['id'],
                'username' => $model['username'],
                'nickname' => $model['nickname'] ?: '-'
            ])
            ->warning();

        return true;
    }

    /**
     * 删除后事件
     * 用于处理用户删除后的业务逻辑
     * @param User $model
     * @return void
     * @throws Throwable
     */
    public static function onAfterDelete(User $model): void
    {
        // 清除用户权限缓存
        $model->clearUserPermissionCache($model['id']);

        // 记录删除成功日志
        dp_log('删除用户成功')
            ->type(Logger::TYPE_USER_ACTION)
            ->context([
                'target_user_id' => $model['id']
            ])
            ->success();
    }
}
