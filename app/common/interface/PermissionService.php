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

namespace app\common\interface;

/**
 * 权限服务接口
 *
 * 定义了权限系统的核心服务方法,包括权限检查、数据权限过滤、
 * 用户权限获取、菜单生成等功能
 *
 * @package app\common\interface
 */
interface PermissionService
{
    /**
     * 检查用户是否拥有指定权限
     *
     * @param int $userId 用户ID
     * @param string|array $permission 权限标识,支持字符串或数组
     * @param string $logic 逻辑关系: and(全部满足) 或 or(满足任一)
     * @return bool 是否拥有权限
     */
    public function hasPermission(int $userId, string|array $permission, string $logic = 'or'): bool;

    /**
     * 获取用户的所有权限
     *
     * @param int $userId 用户ID
     * @param string|null $type 权限类型: menu/button/api,null表示所有类型
     * @return array 权限列表,包含权限完整信息
     */
    public function getUserPermissions(int $userId, ?string $type = null): array;

    /**
     * 获取用户的所有权限标识
     *
     * @param int $userId 用户ID
     * @param string|null $type 权限类型: menu/button/api,null表示所有类型
     * @return array 权限标识数组
     */
    public function getUserPermissionCodes(int $userId, ?string $type = null): array;

    /**
     * 获取用户的所有角色
     *
     * @param int $userId 用户ID
     * @return array 角色列表,包含角色完整信息
     */
    public function getUserRoles(int $userId): array;

    /**
     * 获取用户的角色ID列表
     *
     * @param int $userId 用户ID
     * @return array 角色ID数组
     */
    public function getUserRoleIds(int $userId): array;

    /**
     * 获取用户的数据权限范围
     *
     * @param int $userId 用户ID
     * @param string $resourceType 资源类型,如 user/order/product
     * @return int 数据范围: 1全部,2本部门,3本部门及下级,4仅本人,5自定义
     */
    public function getDataScope(int $userId, string $resourceType = ''): int;

    /**
     * 应用数据权限过滤到查询
     *
     * 根据用户的数据权限范围,自动为查询添加WHERE条件
     *
     * @param object $query ThinkPHP查询对象
     * @param int $userId 用户ID
     * @param string $resourceType 资源类型
     * @param string $userIdField 用户ID字段名,默认 'user_id'
     * @param string $departmentIdField 部门ID字段名,默认 'department_id'
     * @return object 应用过滤后的查询对象
     */
    public function applyDataScope(
        object $query,
        int    $userId,
        string $resourceType = '',
        string $userIdField = 'user_id',
        string $departmentIdField = 'department_id'
    ): object;

    /**
     * 获取用户的菜单树
     *
     * 根据用户权限过滤菜单,返回树形结构
     *
     * @param int $userId 用户ID
     * @param bool $onlyVisible 是否只获取可见菜单
     * @return array 菜单树数组
     */
    public function getUserMenus(int $userId, bool $onlyVisible = true): array;

    /**
     * 获取用户有权访问的菜单ID列表
     *
     * @param int $userId 用户ID
     * @return array 菜单ID数组
     */
    public function getUserMenuIds(int $userId): array;

    /**
     * 检查用户是否可以访问指定路由
     *
     * @param int $userId 用户ID
     * @param string $route 路由地址
     * @param string $method 请求方法,默认 GET
     * @return bool 是否有权访问
     */
    public function canAccessRoute(int $userId, string $route, string $method = 'GET'): bool;

    /**
     * 清除用户权限缓存
     *
     * 在用户角色或权限发生变更时调用,清除相关缓存
     *
     * @param int $userId 用户ID
     * @return void
     */
    public function clearUserCache(int $userId): void;

    /**
     * 清除角色权限缓存
     *
     * 在角色权限发生变更时调用,清除相关缓存
     *
     * @param int $roleId 角色ID
     * @return void
     */
    public function clearRoleCache(int $roleId): void;

    /**
     * 清除所有权限缓存
     *
     * 在权限表发生重大变更时调用
     *
     * @return void
     */
    public function clearAllCache(): void;

    /**
     * 为用户分配角色
     *
     * @param int $userId 用户ID
     * @param array $roleIds 角色ID数组
     * @return bool 是否成功
     */
    public function assignRolesToUser(int $userId, array $roleIds): bool;

    /**
     * 为角色分配权限
     *
     * @param int $roleId 角色ID
     * @param array $permissionIds 权限ID数组
     * @return bool 是否成功
     */
    public function assignPermissionsToRole(int $roleId, array $permissionIds): bool;

    /**
     * 获取数据权限过滤的部门ID列表
     *
     * 根据用户的数据范围,返回用户可以访问的部门ID列表
     *
     * @param int $userId 用户ID
     * @param string $resourceType 资源类型
     * @return array 部门ID数组
     */
    public function getDataScopeDepartmentIds(int $userId, string $resourceType = ''): array;

    /**
     * 获取角色的权限树(用于权限分配界面)
     *
     * @param int $roleId 角色ID,0表示获取所有权限
     * @return array 权限树,包含已选中状态
     */
    public function getPermissionTreeForRole(int $roleId = 0): array;

    /**
     * 检查权限性能
     *
     * 返回权限检查的性能指标(用于监控)
     *
     * @param int $userId 用户ID
     * @return array 包含执行时间、缓存命中率等信息
     */
    public function getPerformanceMetrics(int $userId): array;
}
