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

use app\common\trait\TreeModelTrait;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\Model;
use think\model\relation\BelongsTo;
use think\model\relation\BelongsToMany;
use think\model\relation\HasMany;

/**
 * 权限模型
 * @package app\common\model
 */
class Permission extends Base
{
    use TreeModelTrait;

    /**
     * 表名
     * @var string
     */
    protected $name = 'admin_permission';

    /**
     * 类型转换
     * @var array
     */
    protected $type = [
        'id'          => 'integer',
        'parent_id'   => 'integer',
        'level'       => 'integer',
        'sort'        => 'integer',
        'status'      => 'integer',
        'visible'     => 'integer',
        'cache'       => 'integer',
        'create_time' => 'integer',
        'update_time' => 'integer',
    ];

    /**
     * 权限类型常量
     */
    const TYPE_MENU = 'menu';      // 菜单权限
    const TYPE_BUTTON = 'button';  // 按钮权限
    const TYPE_API = 'api';        // API权限

    /**
     * 获取权限类型列表
     * @return array
     */
    public static function getTypeList(): array
    {
        return [
            self::TYPE_MENU   => '菜单权限',
            self::TYPE_BUTTON => '按钮权限',
            self::TYPE_API    => 'API权限',
        ];
    }

    /**
     * 获取角色关联(多对多)
     * @return BelongsToMany
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            'admin_role_permission',
            'role_id',
            'permission_id'
        );
    }

    /**
     * 获取父权限关联
     * @return BelongsTo
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * 获取子权限关联
     * @return HasMany
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * 获取权限树
     * @param int $parentId 父权限ID,0表示获取所有
     * @param string|null $type 权限类型,null表示所有类型
     * @param bool $onlyEnabled 是否只获取启用的权限
     * @return array
     */
    public function getTree(int $parentId = 0, ?string $type = null, bool $onlyEnabled = true): array
    {
        // 构建查询条件
        $where = [];

        if ($type !== null) {
            $where[] = ['type', '=', $type];
        }

        if ($onlyEnabled) {
            $where[] = ['status', '=', 1];
        }

        // 一次性查询所有权限数据
        $permissions = $this->where($where)
            ->order('sort', 'asc')
            ->order('id', 'asc')
            ->select()
            ->toArray();

        // 使用 TreeBuilder 构建树形结构
        return dp_tree_builder($permissions)
            ->setRootId($parentId)
            ->build();
    }

    /**
     * 获取所有子权限ID(递归)
     * @param int $parentId 父权限ID
     * @param bool $includeSelf 是否包含自身
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getChildrenIds(int $parentId, bool $includeSelf = false): array
    {
        $ids = $includeSelf ? [$parentId] : [];

        // 查询所有子权限
        $children = $this->where('parent_id', $parentId)->column('id');

        if (!empty($children)) {
            foreach ($children as $childId) {
                // 递归获取子权限的子权限
                $ids = array_merge($ids, $this->getChildrenIds($childId, true));
            }
        }

        return array_unique($ids);
    }

    /**
     * 根据权限标识获取权限
     * @param string $code 权限标识
     * @return Permission|array|Model|null
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getByCode(string $code): Permission|array|Model|null
    {
        return $this->where('code', $code)->find();
    }

    /**
     * 根据路由匹配权限
     * @param string $route 路由地址
     * @param string $method 请求方法
     * @param bool $onlyEnabled
     * @return Permission|array|Model|null
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function matchRoute(string $route, string $method = 'GET', bool $onlyEnabled = true): Permission|array|Model|null
    {
        $query = $this->where('route', $route)
            ->where(function ($query) use ($method) {
                $query->whereNull('method')
                    ->whereOr('method', $method)
                    ->whereOr('method', '');
            });

        if ($onlyEnabled) {
            $query->where('status', 1);
        }

        return $query->find();
    }

    /**
     * 获取菜单权限树
     * @param bool $onlyVisible 是否只获取可见的
     * @return array
     */
    public function getMenuTree(bool $onlyVisible = true): array
    {
        $where = [
            ['type', '=', self::TYPE_MENU],
            ['status', '=', 1]
        ];

        if ($onlyVisible) {
            $where[] = ['visible', '=', 1];
        }

        $menus = $this->where($where)
            ->order('sort', 'asc')
            ->order('id', 'asc')
            ->select()
            ->toArray();

        // 使用 TreeBuilder 构建树形结构
        return dp_tree_builder($menus)->build();
    }

    /**
     * 根据用户角色过滤菜单树
     * @param array $menuTree 完整菜单树
     * @param array $permissionIds 用户拥有的权限ID列表
     * @return array
     */
    public function filterMenuTreeByPermissions(array $menuTree, array $permissionIds): array
    {
        $filtered = [];

        foreach ($menuTree as $menu) {
            // 检查用户是否有该菜单权限 或 拥有该菜单的任何子孙权限
            if (in_array($menu['id'], $permissionIds) || $this->hasAnyChildPermission($menu, $permissionIds)) {
                // 递归过滤子菜单
                if (!empty($menu['children'])) {
                    $menu['children'] = $this->filterMenuTreeByPermissions($menu['children'], $permissionIds);
                }
                $filtered[] = $menu;
            }
        }

        return $filtered;
    }

    /**
     * 检查是否拥有任何子孙权限
     * @param array $menu 菜单节点
     * @param array $permissionIds 用户权限ID列表
     * @return bool
     */
    private function hasAnyChildPermission(array $menu, array $permissionIds): bool
    {
        // 如果没有子节点，返回false
        if (empty($menu['children'])) {
            return false;
        }

        // 检查所有子节点
        foreach ($menu['children'] as $child) {
            // 如果子节点直接匹配
            if (in_array($child['id'], $permissionIds)) {
                return true;
            }

            // 递归检查子节点的子孙节点
            if ($this->hasAnyChildPermission($child, $permissionIds)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 获取权限完整路径
     * @param int $permissionId 权限ID
     * @param string $separator 分隔符
     * @return string
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getPermissionPath(int $permissionId, string $separator = ' > '): string
    {
        $permission = $this->find($permissionId);
        if (!$permission) {
            return '';
        }

        $path = [$permission->name];

        // 递归获取父权限
        if ($permission['parent_id'] > 0) {
            $parentPath = $this->getPermissionPath($permission['parent_id'], $separator);
            if ($parentPath) {
                array_unshift($path, $parentPath);
            }
        }

        return implode($separator, $path);
    }

    /**
     * 检查权限是否有子权限
     * @param int $permissionId 权限ID
     * @return bool
     */
    public function hasChildren(int $permissionId): bool
    {
        return $this->where('parent_id', $permissionId)->count() > 0;
    }

    /**
     * 检查权限是否有关联角色
     * @param int $permissionId 权限ID
     * @return bool
     */
    public function hasRoles(int $permissionId): bool
    {
        return (new Role())
                ->alias('r')
                ->join('admin_role_permission rp', 'r.id = rp.role_id')
                ->where('rp.permission_id', $permissionId)
                ->where('r.delete_time', null)
                ->count() > 0;
    }

    /**
     * 删除前检查
     * @return bool
     */
    public function beforeDelete(): bool
    {
        // 检查是否有子权限
        if ($this->hasChildren($this['id'])) {
            $this->error = '该权限下存在子权限,无法删除';
            return false;
        }

        // 检查是否有关联角色
        if ($this->hasRoles($this['id'])) {
            $this->error = '该权限已分配给角色,无法删除';
            return false;
        }

        return true;
    }

    /**
     * 删除后清理关联数据
     * @param Permission $model
     * @return void
     */
    public static function onAfterDelete(Permission $model): void
    {
        // 删除权限角色关联
        $model->roles()->detach();
    }

    /**
     * 获取权限类型文本
     * @param string $typeValue 权限类型值
     * @return string
     */
    public static function getTypeText(string $typeValue): string
    {
        $list = self::getTypeList();
        return $list[$typeValue] ?? '未知';
    }
}
