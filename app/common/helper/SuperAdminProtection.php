<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2026 广东卓锐软件有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://www.dolphinphp.com
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\helper;

use app\common\model\Role;
use app\common\model\User;
use Exception;
use Throwable;

/**
 * 超级管理员保护辅助类
 * 提供统一的超级管理员权限检查和保护机制
 *
 * @package app\common\helper
 */
class SuperAdminProtection
{
    /**
     * 检查用户是否超级管理员(通过ID)
     * @param int $userId 用户ID
     * @return bool
     */
    public static function checkUserById(int $userId): bool
    {
        $superAdminIds = config('system.super_admin.user_ids', [1]);
        return in_array($userId, $superAdminIds);
    }

    /**
     * 检查用户是否超级管理员(通过标识字段)
     * @param User $user 用户模型实例
     * @return bool
     */
    public static function checkUserByFlag(User $user): bool
    {
        return $user['is_super_admin'] == 1;
    }

    /**
     * 检查用户是否超级管理员(通过角色)
     * @param User $user 用户模型实例
     * @return bool
     */
    public static function checkUserByRole(User $user): bool
    {
        try {
            $superRoleIds = config('system.super_admin.role_ids', [1]);
            $userRoleIds  = $user->getRoleIds();
            return !empty(array_intersect($userRoleIds, $superRoleIds));
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * 综合判断用户是否超级管理员
     * 使用多重检查机制,三种方式任一通过即为超级管理员
     * @param User|int $user 用户模型实例或用户ID
     * @return bool
     */
    public static function isSuperAdmin(User|int $user): bool
    {
        // 如果传入的是ID,先获取用户模型
        if (is_int($user)) {
            // ID检查
            if (self::checkUserById($user)) {
                return true;
            }

            // 获取用户模型进行进一步检查
            try {
                $user = (new User())->find($user);
                if (!$user) {
                    return false;
                }
            } catch (Throwable) {
                return false;
            }
        }

        // 标识字段检查
        if (self::checkUserByFlag($user)) {
            return true;
        }

        // 角色检查
        return self::checkUserByRole($user);
    }

    /**
     * 检查角色是否超级管理员角色(通过ID)
     * @param int $roleId 角色ID
     * @return bool
     */
    public static function checkRoleById(int $roleId): bool
    {
        $superRoleIds = config('system.super_admin.role_ids', [1]);
        return in_array($roleId, $superRoleIds);
    }

    /**
     * 检查角色是否超级管理员角色(通过标识字段)
     * @param Role $role 角色模型实例
     * @return bool
     */
    public static function checkRoleByFlag(Role $role): bool
    {
        return $role['is_super_role'] == 1;
    }

    /**
     * 综合判断角色是否超级管理员角色
     * @param Role|int $role 角色模型实例或角色ID
     * @return bool
     */
    public static function isSuperRole(Role|int $role): bool
    {
        // 如果传入的是ID
        if (is_int($role)) {
            // ID检查
            if (self::checkRoleById($role)) {
                return true;
            }

            // 获取角色模型进行进一步检查
            try {
                $role = (new Role())->find($role);
                if (!$role) {
                    return false;
                }
            } catch (Throwable) {
                return false;
            }
        }

        // 标识字段检查
        return self::checkRoleByFlag($role);
    }

    /**
     * 检查角色是否系统内置角色
     * @param Role|int $role 角色模型实例或角色ID
     * @return bool
     */
    public static function isSystemRole(Role|int $role): bool
    {
        // 如果传入的是ID
        if (is_int($role)) {
            $systemRoleIds = config('system.super_admin.system_roles', [1]);
            if (in_array($role, $systemRoleIds)) {
                return true;
            }

            // 获取角色模型
            try {
                $role = (new Role())->find($role);
                if (!$role) {
                    return false;
                }
            } catch (Throwable) {
                return false;
            }
        }

        return $role['is_system'] == 1;
    }

    /**
     * 检查用户是否可删除
     * @param User|int $user 用户模型实例或用户ID
     * @return bool
     */
    public static function canDeleteUser(User|int $user): bool
    {
        // 超级管理员不可删除
        if (self::isSuperAdmin($user)) {
            return false;
        }

        // 检查受保护用户列表
        $userId         = is_int($user) ? $user : $user['id'];
        $protectedUsers = config('system.super_admin.protected_users', [1]);
        return !in_array($userId, $protectedUsers);
    }

    /**
     * 检查角色是否可删除
     * @param Role|int $role 角色模型实例或角色ID
     * @return bool
     */
    public static function canDeleteRole(Role|int $role): bool
    {
        // 超级管理员角色不可删除
        if (self::isSuperRole($role)) {
            return false;
        }

        // 系统内置角色不可删除
        if (self::isSystemRole($role)) {
            return false;
        }

        // 检查受保护角色列表
        $roleId         = is_int($role) ? $role : $role['id'];
        $protectedRoles = config('system.super_admin.protected_roles', [1]);
        return !in_array($roleId, $protectedRoles);
    }

    /**
     * 检查当前用户是否有权分配超级管理员角色
     * @return bool
     */
    public static function canAssignSuperRole(): bool
    {
        try {
            $currentUser = session(config('system.admin_session'));
            if (!$currentUser) {
                return false;
            }

            $userId = $currentUser['id'] ?? 0;
            return self::isSuperAdmin($userId);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * 检查是否允许降权超级管理员
     * @param int $targetUserId 目标用户ID
     * @param int|null $operatorUserId 操作者用户ID,null表示当前用户
     * @return bool
     */
    public static function canDemoteSuperAdmin(int $targetUserId, ?int $operatorUserId = null): bool
    {
        // 获取操作者ID
        if ($operatorUserId === null) {
            $currentUser    = session(config('system.admin_session'));
            $operatorUserId = $currentUser['id'] ?? 0;
        }

        // 不允许自我降权(配置控制)
        if ($targetUserId == $operatorUserId) {
            return config('system.permission.allow_self_demote', false);
        }

        // 不允许降权其他超级管理员(配置控制)
        return config('system.permission.allow_demote_super_admin', false);
    }

    /**
     * 记录安全事件日志
     * @param string $event 事件名称
     * @param array $context 上下文数据
     * @param string $level 日志级别
     * @return void
     * @throws Throwable
     */
    public static function logSecurityEvent(string $event, array $context = [], string $level = 'warning'): void
    {
        dp_log($event)
            ->type(Logger::TYPE_SECURITY)
            ->context($context)
            ->level($level);
    }

    /**
     * 获取超级管理员角色ID列表
     * @return array
     */
    public static function getSuperRoleIds(): array
    {
        return config('system.super_admin.role_ids', [1]);
    }

    /**
     * 获取超级管理员用户ID列表
     * @return array
     */
    public static function getSuperAdminIds(): array
    {
        return config('system.super_admin.user_ids', [1]);
    }

    /**
     * 批量检查角色ID列表中是否包含超级管理员角色
     * @param array $roleIds 角色ID数组
     * @return bool
     */
    public static function hasSuperRole(array $roleIds): bool
    {
        $superRoleIds = self::getSuperRoleIds();
        return !empty(array_intersect($roleIds, $superRoleIds));
    }

    /**
     * 防护检查:尝试删除用户前的检查
     * @param User|int $user 用户模型或ID
     * @param bool $throwException 是否抛出异常,false则返回错误信息
     * @return true|string true表示可以删除,string表示不能删除的原因
     * @throws Exception
     */
    public static function checkBeforeDeleteUser(User|int $user, bool $throwException = true): true|string
    {
        if (!self::canDeleteUser($user)) {
            $message = self::isSuperAdmin($user)
                ? '超级管理员账号不能删除'
                : '该用户账号不允许删除';

            if ($throwException) {
                throw new Exception($message);
            }
            return $message;
        }

        return true;
    }

    /**
     * 防护检查:尝试删除角色前的检查
     * @param Role|int $role 角色模型或ID
     * @param bool $throwException 是否抛出异常,false则返回错误信息
     * @return true|string true表示可以删除,string表示不能删除的原因
     * @throws Exception
     */
    public static function checkBeforeDeleteRole(Role|int $role, bool $throwException = true): true|string
    {
        if (!self::canDeleteRole($role)) {
            if (self::isSuperRole($role)) {
                $message = '超级管理员角色不能删除';
            } elseif (self::isSystemRole($role)) {
                $message = '系统内置角色不能删除';
            } else {
                $message = '该角色不允许删除';
            }

            if ($throwException) {
                throw new Exception($message);
            }
            return $message;
        }

        return true;
    }
}
