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

use app\common\interface\PermissionService as PermissionServiceInterface;
use Exception;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\model\relation\BelongsToMany;
use think\model\relation\HasMany;
use app\common\helper\Logger;
use Throwable;

/**
 * 角色模型
 * @package app\common\model
 */
class Role extends Base
{
    /**
     * 表名
     * @var string
     */
    protected $name = 'admin_role';

    /**
     * 类型转换
     * @var array
     */
    protected $type = [
        'id'          => 'integer',
        'data_scope'  => 'integer',
        'status'      => 'integer',
        'sort'        => 'integer',
        'create_time' => 'integer',
        'update_time' => 'integer',
        'delete_time' => 'integer',
    ];

    /**
     * 数据范围常量
     */
    const DATA_SCOPE_ALL = 1;           // 全部数据权限
    const DATA_SCOPE_DEPARTMENT_AND_CHILDREN = 2;    // 本部门及以下数据权限
    const DATA_SCOPE_DEPARTMENT = 3; // 本部门数据权限
    const DATA_SCOPE_SELF = 4;          // 仅本人数据权限
    const DATA_SCOPE_CUSTOM = 5;        // 自定义数据权限

    /**
     * 获取数据范围列表
     * @return array
     */
    public static function getDataScopeList(): array
    {
        return [
            self::DATA_SCOPE_ALL                     => '全部数据',
            self::DATA_SCOPE_DEPARTMENT_AND_CHILDREN => '本部门及下级数据',
            self::DATA_SCOPE_DEPARTMENT              => '本部门数据',
            self::DATA_SCOPE_SELF                    => '仅本人数据',
            self::DATA_SCOPE_CUSTOM                  => '自定义数据权限',
        ];
    }

    /**
     * 获取 【角色id-> 角色名称】的数组
     * @return array
     */
    public function getList(): array
    {
        return $this->where('status', 1)
            ->column('name', 'id');
    }

    /**
     * 获取权限关联(多对多)
     * @return BelongsToMany
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            'admin_role_permission',
            'permission_id',
            'role_id'
        );
    }

    /**
     * 获取用户关联(多对多)
     * @return BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'admin_user_role',
            'user_id',
            'role_id'
        );
    }

    /**
     * 获取自定义数据权限关联
     * @return HasMany
     */
    public function dataPermissions(): HasMany
    {
        return $this->hasMany(DataPermission::class, 'role_id');
    }

    /**
     * 获取角色的所有权限
     * @param int $roleId 角色ID
     * @param bool $onlyEnabled 是否只获取启用的权限
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getPermissions(int $roleId, bool $onlyEnabled = true): array
    {
        $query = $this->with(['permissions' => function ($query) use ($onlyEnabled) {
            if ($onlyEnabled) {
                $query->where('status', 1);
            }
        }])->find($roleId);

        if (!$query) {
            return [];
        }

        return $query['permissions'] ? $query['permissions']->toArray() : [];
    }

    /**
     * 批量获取多个角色的权限
     * @param array $roleIds 角色ID数组
     * @param bool $onlyEnabled 是否只获取启用的权限
     * @return array
     */
    public static function getPermissionsByRoleIds(array $roleIds, bool $onlyEnabled = true): array
    {
        if (empty($roleIds)) {
            return [];
        }

        // 一次性查询所有角色的权限
        $query = Permission::alias('p')
            ->join('admin_role_permission rp', 'p.id = rp.permission_id')
            ->whereIn('rp.role_id', $roleIds)
            ->field('p.*');

        if ($onlyEnabled) {
            $query->where('p.status', 1);
        }

        $permissions = $query->select()->toArray();

        // 去重（同一个权限可能被多个角色拥有）
        $uniquePermissions = [];
        $permissionIds     = [];

        foreach ($permissions as $permission) {
            if (!in_array($permission['id'], $permissionIds)) {
                $uniquePermissions[] = $permission;
                $permissionIds[]     = $permission['id'];
            }
        }

        // 按sort排序
        usort($uniquePermissions, function ($a, $b) {
            return $a['sort'] <=> $b['sort'];
        });

        return $uniquePermissions;
    }

    /**
     * 获取角色的权限ID列表
     * @param int $roleId 角色ID
     * @param bool $onlyEnabled 是否只获取启用的权限
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getPermissionIds(int $roleId, bool $onlyEnabled = true): array
    {
        $permissions = $this->getPermissions($roleId, $onlyEnabled);
        return array_column($permissions, 'id');
    }

    /**
     * 为角色分配权限
     * @param int $roleId 角色ID
     * @param array $permissionIds 权限ID数组
     * @return bool
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException|Throwable
     */
    public function assignPermissions(int $roleId, array $permissionIds): bool
    {
        $role = $this->find($roleId);
        if (!$role) {
            $this->error = '角色不存在';
            return false;
        }

        try {
            // 获取原有权限ID列表（用于日志记录）
            $oldPermissionIds = $role->permissions()->column('permission_id');

            // 先删除原有权限
            $role->permissions()->detach();

            // 分配新权限
            if (!empty($permissionIds)) {
                $role->permissions()->attach($permissionIds);
            }

            // 清除角色权限缓存
            $this->clearRolePermissionCache($roleId);

            // 记录操作日志
            $addedCount   = count(array_diff($permissionIds, $oldPermissionIds));
            $removedCount = count(array_diff($oldPermissionIds, $permissionIds));
            dp_log_user_action(
                "为角色「{$role['name']}」分配权限",
                [
                    'role_id'           => $roleId,
                    'role_name'         => $role['name'],
                    'total_permissions' => count($permissionIds),
                    'added_count'       => $addedCount,
                    'removed_count'     => $removedCount,
                    'permission_ids'    => $permissionIds
                ]
            );

            return true;
        } catch (Exception $e) {
            $this->error = '权限分配失败: ' . $e->getMessage();

            // 记录错误日志
            dp_log_user_action(
                "为角色「{$role['name']}」分配权限失败",
                [
                    'role_id'   => $roleId,
                    'role_name' => $role['name'],
                    'error'     => $e->getMessage()
                ],
                'error'
            );

            return false;
        }
    }

    /**
     * 检查角色是否拥有指定权限
     * @param int $roleId 角色ID
     * @param string $permissionCode 权限标识
     * @return bool
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function hasPermission(int $roleId, string $permissionCode): bool
    {
        $permissions = $this->getPermissions($roleId);
        $codes       = array_column($permissions, 'code');
        return in_array($permissionCode, $codes);
    }

    /**
     * 获取角色的自定义数据权限部门ID列表
     * @param int $roleId 角色ID
     * @param string $resourceType 资源类型
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getCustomDataPermissionDepartments(int $roleId, string $resourceType): array
    {
        $dataPermission = (new DataPermission())
            ->where('role_id', $roleId)
            ->where('resource_type', $resourceType)
            ->find();

        if (!$dataPermission || empty($dataPermission->department_ids)) {
            return [];
        }

        return explode(',', $dataPermission->department_ids);
    }

    /**
     * 清除角色权限缓存
     * @param int $roleId 角色ID
     * @return void
     */
    private function clearRolePermissionCache(int $roleId): void
    {
        app(PermissionServiceInterface::class)->clearRoleCache($roleId);
    }

    /**
     * 检查角色是否有关联用户
     * @param int $roleId 角色ID
     * @return bool
     */
    public function hasUsers(int $roleId): bool
    {
        return (new User())
                ->alias('u')
                ->join('admin_user_role ur', 'u.id = ur.user_id')
                ->where('ur.role_id', $roleId)
                ->where('u.delete_time', null)
                ->count() > 0;
    }

    /**
     * 获取数据范围文本
     * @param int $dataScopeValue 数据范围值
     * @return string
     */
    public static function getDataScopeText(int $dataScopeValue): string
    {
        $list = self::getDataScopeList();
        return $list[$dataScopeValue] ?? '未知';
    }

    /**
     * 判断是否超级管理员角色
     * 使用多重检查机制确保安全性
     * @return bool
     */
    public function isSuperRole(): bool
    {
        // 检查1: 标识字段(优先级最高)
        if ($this['is_super_role'] == 1) {
            return true;
        }

        // 检查2: ID检查(向后兼容)
        $superRoleIds = config('system.super_admin.role_ids', [1]);
        return in_array($this['id'], $superRoleIds);
    }

    /**
     * 判断是否系统内置角色
     * @return bool
     */
    public function isSystemRole(): bool
    {
        // 检查1: 标识字段
        if ($this['is_system'] == 1) {
            return true;
        }

        // 检查2: ID检查(向后兼容)
        $systemRoleIds = config('system.super_admin.system_roles', [1]);
        return in_array($this['id'], $systemRoleIds);
    }

    /**
     * 判断角色是否受保护(不可删除)
     * @return bool
     */
    public function isProtected(): bool
    {
        // 超级管理员角色不可删除
        if ($this->isSuperRole()) {
            return true;
        }

        // 系统内置角色不可删除
        if ($this->isSystemRole()) {
            return true;
        }

        // 检查配置的受保护角色列表
        $protectedRoles = config('system.super_admin.protected_roles', [1]);
        return in_array($this['id'], $protectedRoles);
    }

    /**
     * 删除前检查
     * @param Role $model
     * @return bool
     * @throws Exception|Throwable
     */
    public static function onBeforeDelete(Role $model): bool
    {
        // 检查是否是超级管理员角色
        if ($model->isSuperRole()) {
            dp_log('尝试删除超级管理员角色被阻止')
                ->type(Logger::TYPE_SECURITY)
                ->context([
                    'role_id'   => $model['id'],
                    'role_name' => $model['name'],
                ])
                ->error();
            throw new Exception('超级管理员角色不能删除');
        }

        // 检查是否是系统内置角色
        if ($model->isSystemRole()) {
            dp_log('尝试删除系统内置角色被阻止')
                ->type(Logger::TYPE_SECURITY)
                ->context([
                    'role_id'   => $model['id'],
                    'role_name' => $model['name'],
                ])
                ->error();
            throw new Exception('系统内置角色不能删除');
        }

        // 检查是否有关联用户
        $userCount = (new User())
            ->alias('u')
            ->join('admin_user_role ur', 'u.id = ur.user_id')
            ->where('ur.role_id', $model['id'])
            ->where('u.delete_time', null)
            ->count();

        if ($userCount > 0) {
            throw new Exception("该角色下还有 $userCount 个用户,不能删除");
        }

        // 记录删除前的日志
        dp_log('删除角色')
            ->type(Logger::TYPE_USER_ACTION)
            ->context([
                'role_id'   => $model['id'],
                'role_name' => $model['name'],
            ])
            ->warning();

        return true;
    }

    /**
     * 删除后清理关联数据
     * @param Role $model
     * @return void
     * @throws Throwable
     */
    public static function onAfterDelete(Role $model): void
    {
        // 删除角色权限关联
        $model->permissions()->detach();

        // 删除自定义数据权限
        (new DataPermission())->where('role_id', $model['id'])->delete();

        // 清除缓存
        app(PermissionServiceInterface::class)->clearRoleCache((int)$model['id']);

        // 记录删除成功日志
        dp_log('删除角色成功')
            ->type(Logger::TYPE_USER_ACTION)
            ->context([
                'role_id' => $model['id'],
            ])
            ->success();
    }
}
