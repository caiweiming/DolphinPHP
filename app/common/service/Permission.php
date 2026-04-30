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

namespace app\common\service;

use app\common\interface\PermissionService as PermissionServiceInterface;
use app\common\model\User;
use app\common\model\Role;
use app\common\model\Permission as PermissionModel;
use app\common\model\Department;
use app\common\model\DataPermission;
use Exception;
use think\facade\Cache;
use think\facade\Log;
use Throwable;

/**
 * 权限服务实现类
 * @package app\common\service
 */
class Permission implements PermissionServiceInterface
{
    /**
     * 缓存前缀
     */
    private const CACHE_PREFIX = 'permission:';
    private const CACHE_VERSION_KEY = 'permission:cache_version';
    private const CACHE_VERSION_DEFAULT = 1;

    /**
     * 缓存时间(秒)
     */
    private const CACHE_TTL = 3600;

    /**
     * 缓存版本（进程内缓存，减少重复读取）
     * @var int|null
     */
    private ?int $cacheVersion = null;

    /**
     * 权限可访问性缓存（当前请求内）
     * @var array<string, bool>
     */
    private array $permissionAccessibilityCache = [];

    /**
     * 用户模型
     * @var User
     */
    protected User $userModel;

    /**
     * 角色模型
     * @var Role
     */
    protected Role $roleModel;

    /**
     * 权限模型
     * @var PermissionModel
     */
    protected PermissionModel $permissionModel;

    /**
     * 部门模型
     * @var Department
     */
    protected Department $departmentModel;

    /**
     * 数据权限模型
     * @var DataPermission
     */
    protected DataPermission $dataPermissionModel;

    /**
     * 应用服务
     * @var AppService
     */
    protected AppService $appService;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->userModel           = new User();
        $this->roleModel           = new Role();
        $this->permissionModel     = new PermissionModel();
        $this->departmentModel     = new Department();
        $this->dataPermissionModel = new DataPermission();
        $this->appService          = app(AppService::class);
    }

    /**
     * 判断是否有权限
     * @param int $userId
     * @param string|array $permission
     * @param string $logic
     * @return bool
     * @throws Throwable
     */
    public function hasPermission(int $userId, string|array $permission, string $logic = 'or'): bool
    {
        $startTime = microtime(true);

        try {
            if (is_string($permission)) {
                $permission = [$permission];
            }

            $requestedPermissions = array_values(array_filter(array_map(
                static fn(mixed $item): string => trim((string)$item),
                $permission
            )));

            if ($requestedPermissions === []) {
                return false;
            }

            $accessiblePermissions = array_values(array_filter(
                $requestedPermissions,
                fn(string $code): bool => $this->isPermissionCodeAccessible($code)
            ));

            if ($accessiblePermissions === []) {
                return false;
            }

            if ($logic === 'and' && count($accessiblePermissions) !== count($requestedPermissions)) {
                return false;
            }

            // 超级管理员拥有所有权限
            if (dp_is_super_admin($userId)) {
                return true;
            }

            $userPermissions = $this->getUserPermissionCodes($userId);

            if ($logic === 'and') {
                // 必须拥有所有权限
                $result = !array_diff($accessiblePermissions, $userPermissions);
            } else {
                // 只需拥有任一权限
                $result = !empty(array_intersect($accessiblePermissions, $userPermissions));
            }

            // 记录性能日志
            $duration = (microtime(true) - $startTime) * 1000;
            if ($duration > 5) {
                Log::warning("权限检查耗时过长: {$duration}ms, 用户ID: $userId");
            }

            return $result;
        } catch (Exception $e) {
            Log::error("权限检查失败: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 获取用户权限
     * @param int $userId
     * @param string|null $type
     * @return array
     * @throws Throwable
     */
    public function getUserPermissions(int $userId, ?string $type = null): array
    {
        $cacheKey = $this->buildCacheKey("user_permissions:$userId");
        if ($type !== null) {
            $cacheKey .= ":$type";
        }

        return Cache::remember($cacheKey, function () use ($userId, $type) {
            try {
                $permissions = $this->userModel->getPermissions($userId);

                if ($type !== null) {
                    $permissions = array_filter($permissions, function ($permission) use ($type) {
                        return $permission['type'] === $type;
                    });
                }

                return $this->filterAccessiblePermissions(array_values($permissions));
            } catch (Exception $e) {
                Log::error("获取用户权限失败: " . $e->getMessage());
                return [];
            }
        }, self::CACHE_TTL);
    }

    /**
     * 获取用户权限代码
     * @param int $userId
     * @param string|null $type
     * @return array
     * @throws Throwable
     */
    public function getUserPermissionCodes(int $userId, ?string $type = null): array
    {
        // 直接缓存权限代码，避免重复的 array_column 操作
        $cacheKey = $this->buildCacheKey("user_permission_codes:$userId");

        if ($type !== null) {
            $cacheKey .= ":$type";
        }

        return Cache::remember($cacheKey, function () use ($userId, $type) {
            $permissions = $this->getUserPermissions($userId, $type);
            return array_column($permissions, 'code');
        }, self::CACHE_TTL);
    }

    /**
     * 获取用户角色
     * @param int $userId
     * @return array
     * @throws Throwable
     */
    public function getUserRoles(int $userId): array
    {
        $cacheKey = $this->buildCacheKey("user_roles:$userId");

        return Cache::remember($cacheKey, function () use ($userId) {
            try {
                return $this->userModel->getRoles($userId);
            } catch (Exception $e) {
                Log::error("获取用户角色失败: " . $e->getMessage());
                return [];
            }
        }, self::CACHE_TTL);
    }

    /**
     * 获取用户角色id集合
     * @param int $userId
     * @return array
     * @throws Throwable
     */
    public function getUserRoleIds(int $userId): array
    {
        $roles = $this->getUserRoles($userId);
        return array_column($roles, 'id');
    }

    /**
     * 获取用户数据权限范围
     * @param int $userId
     * @param string $resourceType
     * @return int
     * @throws Throwable
     */
    public function getDataScope(int $userId, string $resourceType = ''): int
    {
        try {
            $roles = $this->getUserRoles($userId);

            if (empty($roles)) {
                return Role::DATA_SCOPE_SELF;
            }

            // 取所有角色中权限最大的数据范围(数值越小权限越大)
            $minScope = Role::DATA_SCOPE_SELF;
            foreach ($roles as $role) {
                if ($role['data_scope'] < $minScope) {
                    $minScope = $role['data_scope'];
                }
            }

            return $minScope;
        } catch (Exception $e) {
            Log::error("获取数据权限范围失败: " . $e->getMessage());
            return Role::DATA_SCOPE_SELF;
        }
    }

    /**
     * 应用数据权限范围
     * @param object $query
     * @param int $userId
     * @param string $resourceType
     * @param string $userIdField
     * @param string $departmentIdField
     * @return object
     * @throws Throwable
     */
    public function applyDataScope(
        object $query,
        int    $userId,
        string $resourceType = '',
        string $userIdField = 'user_id',
        string $departmentIdField = 'department_id'
    ): object
    {
        try {
            $dataScope = $this->getDataScope($userId, $resourceType);
            $user      = $this->userModel->find($userId);

            if (!$user) {
                throw new Exception("用户不存在");
            }

            switch ($dataScope) {
                case Role::DATA_SCOPE_ALL:
                    // 全部数据权限,不添加任何过滤
                    break;

                case Role::DATA_SCOPE_DEPARTMENT:
                    // 本部门数据权限
                    if ($user['department_id']) {
                        $query->where($departmentIdField, $user['department_id']);
                    } else {
                        $query->where($userIdField, $userId);
                    }
                    break;

                case Role::DATA_SCOPE_DEPARTMENT_AND_CHILDREN:
                    // 本部门及下级数据权限
                    if ($user['department_id']) {
                        $departmentIds = $this->getDataScopeDepartmentIds($userId, $resourceType);
                        $query->whereIn($departmentIdField, $departmentIds);
                    } else {
                        $query->where($userIdField, $userId);
                    }
                    break;

                case Role::DATA_SCOPE_SELF:
                    // 仅本人数据权限
                    $query->where($userIdField, $userId);
                    break;

                case Role::DATA_SCOPE_CUSTOM:
                    // 自定义数据权限
                    $departmentIds = $this->getDataScopeDepartmentIds($userId, $resourceType);
                    if (!empty($departmentIds)) {
                        $query->whereIn($departmentIdField, $departmentIds);
                    } else {
                        $query->where($userIdField, $userId);
                    }
                    break;
            }

            return $query;
        } catch (Exception $e) {
            Log::error("应用数据权限过滤失败: " . $e->getMessage());
            // 发生错误时,默认只显示本人数据,避免越权
            return $query->where($userIdField, $userId);
        }
    }

    /**
     * 获取用户菜单
     * @param int $userId
     * @param bool $onlyVisible
     * @return array
     * @throws Throwable
     */
    public function getUserMenus(int $userId, bool $onlyVisible = true): array
    {
        $cacheKey = $this->buildCacheKey("user_menus:$userId:" . ($onlyVisible ? '1' : '0'));

        return Cache::remember($cacheKey, function () use ($userId, $onlyVisible) {
            try {
                $menuTree = $this->filterAccessibleMenuTree($this->permissionModel->getMenuTree($onlyVisible));

                // 超级管理员拥有所有菜单
                if (dp_is_super_admin($userId)) {
                    return $menuTree;
                }

                // 普通用户: 根据权限过滤菜单
                $userPermissions = $this->getUserPermissions($userId, PermissionModel::TYPE_MENU);
                $permissionIds   = array_column($userPermissions, 'id');

                // 根据用户权限过滤菜单
                return $this->permissionModel->filterMenuTreeByPermissions($menuTree, $permissionIds);
            } catch (Exception $e) {
                Log::error("获取用户菜单失败: " . $e->getMessage());
                return [];
            }
        }, self::CACHE_TTL);
    }

    /**
     * 获取用户菜单id集合
     * @param int $userId
     * @return array
     * @throws Throwable
     */
    public function getUserMenuIds(int $userId): array
    {
        $permissions = $this->getUserPermissions($userId, PermissionModel::TYPE_MENU);
        return array_column($permissions, 'id');
    }

    /**
     * 是否可访问路由
     * @param int $userId
     * @param string $route
     * @param string $method
     * @return bool
     * @throws Throwable
     */
    public function canAccessRoute(int $userId, string $route, string $method = 'GET'): bool
    {
        try {
            if (!$this->appService->isRouteAccessible($route)) {
                return false;
            }

            // 查找路由对应的权限（含禁用状态），统一走最终可访问判定
            $permission = $this->permissionModel->matchRoute($route, $method, false);

            if (!$permission) {
                // 路由未配置权限,默认允许访问
                return true;
            }

            $permissionData = $permission instanceof PermissionModel ? $permission->toArray() : (array)$permission;
            if (!$this->isPermissionRecordAccessible($permissionData)) {
                return false;
            }

            // 检查用户是否拥有该权限
            return $this->hasPermission($userId, (string)($permissionData['code'] ?? ''));
        } catch (Exception $e) {
            Log::error("路由权限检查失败: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 清除用户缓存
     * @param int $userId
     */
    public function clearUserCache(int $userId): void
    {
        $cacheKeys = $this->buildUserCacheKeys($userId);

        foreach ($cacheKeys as $key) {
            Cache::delete($key);
        }

        Log::info("已清除用户权限缓存, 用户ID: $userId");
    }

    /**
     * 清除角色缓存
     * @param int $roleId
     */
    public function clearRoleCache(int $roleId): void
    {
        Cache::delete($this->buildCacheKey("role_permissions:$roleId"));

        // 清除所有拥有该角色的用户缓存
        try {
            $role = $this->roleModel->find($roleId);
            if ($role && $role['users']) {
                foreach ($role['users'] as $user) {
                    $this->clearUserCache($user->id);
                }
            }
        } catch (Exception $e) {
            Log::error("清除角色权限缓存失败: " . $e->getMessage());
        }

        Log::info("已清除角色权限缓存, 角色ID: $roleId");
    }

    /**
     * 清除所有权限缓存
     */
    public function clearAllCache(): void
    {
        try {
            $version = $this->bumpCacheVersion();
            Log::info('已清除所有权限缓存', ['version' => $version]);
        } catch (Exception $e) {
            Log::error('清除所有权限缓存失败: ' . $e->getMessage());
        }
    }

    /**
     * 授权角色给用户
     * @param int $userId
     * @param array $roleIds
     * @return bool
     * @throws Throwable
     */
    public function assignRolesToUser(int $userId, array $roleIds): bool
    {
        try {
            $result = $this->userModel->assignRoles($userId, $roleIds);

            if ($result) {
                $this->clearUserCache($userId);
            }

            return $result;
        } catch (Exception $e) {
            Log::error("分配用户角色失败: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 授权权限给角色
     * @param int $roleId
     * @param array $permissionIds
     * @return bool
     * @throws Throwable
     */
    public function assignPermissionsToRole(int $roleId, array $permissionIds): bool
    {
        try {
            $result = $this->roleModel->assignPermissions($roleId, $permissionIds);

            if ($result) {
                $this->clearRoleCache($roleId);
            }

            return $result;
        } catch (Exception $e) {
            Log::error("分配角色权限失败: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 获取数据权限范围的部门id集合
     * @param int $userId
     * @param string $resourceType
     * @return array
     * @throws Throwable
     */
    public function getDataScopeDepartmentIds(int $userId, string $resourceType = ''): array
    {
        try {
            $user = $this->userModel->find($userId);
            if (!$user || !$user['department_id']) {
                return [];
            }

            $dataScope = $this->getDataScope($userId, $resourceType);

            switch ($dataScope) {
                case Role::DATA_SCOPE_ALL:
                    // 全部数据,返回空数组表示不限制
                    return [];

                case Role::DATA_SCOPE_DEPARTMENT:
                    // 本部门
                    return [$user['department_id']];

                case Role::DATA_SCOPE_DEPARTMENT_AND_CHILDREN:
                    // 本部门及下级
                    return $this->departmentModel->getChildrenIds($user['department_id'], true);

                case Role::DATA_SCOPE_CUSTOM:
                    // 自定义数据权限
                    $departmentIds = [];
                    $roles         = $this->getUserRoles($userId);

                    foreach ($roles as $role) {
                        if ($role['data_scope'] == Role::DATA_SCOPE_CUSTOM) {
                            $customDepartments = $this->roleModel->getCustomDataPermissionDepartments(
                                $role['id'],
                                $resourceType
                            );
                            $departmentIds     = array_merge($departmentIds, $customDepartments);
                        }
                    }

                    return array_unique(array_filter($departmentIds));

                default:
                    return [];
            }
        } catch (Exception $e) {
            Log::error("获取数据权限部门ID失败: " . $e->getMessage());
            return [];
        }
    }

    /**
     * 获取角色权限树
     * @param int $roleId
     * @return array
     */
    public function getPermissionTreeForRole(int $roleId = 0): array
    {
        try {
            // 获取所有权限树
            $permissionTree = $this->permissionModel->getTree();

            // 如果指定了角色,标记已选中的权限
            if ($roleId > 0) {
                $rolePermissionIds = $this->roleModel->getPermissionIds($roleId);
                $permissionTree    = $this->markCheckedPermissions($permissionTree, $rolePermissionIds);
            }

            return $permissionTree;
        } catch (Exception $e) {
            Log::error("获取权限树失败: " . $e->getMessage());
            return [];
        }
    }

    /**
     * 标记已选中的权限
     * @param array $tree 权限树
     * @param array $checkedIds 已选中的权限ID
     * @return array
     */
    private function markCheckedPermissions(array $tree, array $checkedIds): array
    {
        foreach ($tree as &$item) {
            $item['checked'] = in_array($item['id'], $checkedIds);

            if (!empty($item['children'])) {
                $item['children'] = $this->markCheckedPermissions($item['children'], $checkedIds);
            }
        }

        return $tree;
    }

    /**
     * 获取性能指标
     * @param int $userId
     * @return array
     * @throws Throwable
     */
    public function getPerformanceMetrics(int $userId): array
    {
        $startTime = microtime(true);

        // 执行一次权限检查
        $this->hasPermission($userId, 'test.permission');
        $checkDuration = (microtime(true) - $startTime) * 1000;

        // 检查缓存命中率
        $cacheKey = $this->buildCacheKey("user_permissions:$userId");
        $isCached = Cache::has($cacheKey);

        return [
            'check_duration_ms' => round($checkDuration, 2),
            'cache_hit'         => $isCached,
            'cache_ttl'         => self::CACHE_TTL,
            'timestamp'         => time(),
        ];
    }

    /**
     * 构建带版本号的权限缓存key
     * @param string $suffix
     * @return string
     */
    private function buildCacheKey(string $suffix): string
    {
        return self::CACHE_PREFIX . 'v' . $this->getCacheVersion() . ':' . ltrim($suffix, ':');
    }

    /**
     * 获取当前权限缓存版本
     * @return int
     */
    private function getCacheVersion(): int
    {
        if ($this->cacheVersion !== null) {
            return $this->cacheVersion;
        }

        $version = Cache::get(self::CACHE_VERSION_KEY, self::CACHE_VERSION_DEFAULT);
        $version = is_numeric($version) ? (int)$version : self::CACHE_VERSION_DEFAULT;
        if ($version < self::CACHE_VERSION_DEFAULT) {
            $version = self::CACHE_VERSION_DEFAULT;
        }

        $this->cacheVersion = $version;
        return $this->cacheVersion;
    }

    /**
     * 递增权限缓存版本，实现 O(1) 全量失效
     * @return int
     */
    private function bumpCacheVersion(): int
    {
        $currentVersion = $this->getCacheVersion();

        try {
            $version = Cache::inc(self::CACHE_VERSION_KEY);
            if (is_numeric($version) && (int)$version > $currentVersion) {
                $this->cacheVersion = (int)$version;
                return $this->cacheVersion;
            }
        } catch (Throwable) {
            // 部分驱动不支持 inc，走时间戳兜底
        }

        $fallbackVersion = max($currentVersion + 1, self::CACHE_VERSION_DEFAULT + 1);
        Cache::set(self::CACHE_VERSION_KEY, $fallbackVersion);
        $this->cacheVersion = $fallbackVersion;
        return $this->cacheVersion;
    }

    /**
     * 构建用户缓存key集合（含版本化与旧key兜底）
     * @param int $userId
     * @return array
     */
    private function buildUserCacheKeys(int $userId): array
    {
        return [
            $this->buildCacheKey("user_permissions:$userId"),
            $this->buildCacheKey("user_permissions:$userId:menu"),
            $this->buildCacheKey("user_permissions:$userId:button"),
            $this->buildCacheKey("user_permissions:$userId:api"),
            $this->buildCacheKey("user_permission_codes:$userId"),
            $this->buildCacheKey("user_permission_codes:$userId:menu"),
            $this->buildCacheKey("user_permission_codes:$userId:button"),
            $this->buildCacheKey("user_permission_codes:$userId:api"),
            $this->buildCacheKey("user_roles:$userId"),
            $this->buildCacheKey("user_menus:$userId:1"),
            $this->buildCacheKey("user_menus:$userId:0"),
        ];
    }

    /**
     * 过滤已停用应用的权限
     * @param array $permissions
     * @return array
     */
    private function filterAccessiblePermissions(array $permissions): array
    {
        return array_values(array_filter($permissions, function (array $permission): bool {
            return $this->isPermissionRecordAccessible($permission);
        }));
    }

    /**
     * 过滤最终不可访问的菜单树
     * @param array $menuTree
     * @return array
     */
    private function filterAccessibleMenuTree(array $menuTree): array
    {
        $filtered = [];

        foreach ($menuTree as $menu) {
            if (!$this->isPermissionRecordAccessible($menu)) {
                continue;
            }

            if (!empty($menu['children']) && is_array($menu['children'])) {
                $menu['children'] = $this->filterAccessibleMenuTree($menu['children']);
            }

            $filtered[] = $menu;
        }

        return $filtered;
    }

    /**
     * 判断权限记录是否最终可访问
     * @param array $permission
     * @return bool
     */
    private function isPermissionRecordAccessible(array $permission): bool
    {
        if ((int)($permission['status'] ?? 1) !== 1) {
            return false;
        }

        return $this->appService->isPermissionAccessible(
            (string)($permission['code'] ?? ''),
            (string)($permission['route'] ?? '')
        );
    }

    /**
     * 判断权限标识是否最终可访问
     * @param string $code
     * @return bool
     */
    private function isPermissionCodeAccessible(string $code): bool
    {
        $code = trim($code);
        if ($code === '') {
            return false;
        }

        $cacheKey = 'code:' . $code;
        if (array_key_exists($cacheKey, $this->permissionAccessibilityCache)) {
            return $this->permissionAccessibilityCache[$cacheKey];
        }

        try {
            $permission = $this->permissionModel->getByCode($code);
            if ($permission) {
                $permissionData = $permission instanceof PermissionModel ? $permission->toArray() : (array)$permission;
                return $this->permissionAccessibilityCache[$cacheKey] = $this->isPermissionRecordAccessible($permissionData);
            }
        } catch (Throwable $e) {
            Log::warning('根据权限标识判断可访问性失败: ' . $e->getMessage(), ['code' => $code]);
        }

        return $this->permissionAccessibilityCache[$cacheKey] = $this->appService->isPermissionAccessible($code);
    }
}
