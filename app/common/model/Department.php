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
use think\model\relation\BelongsTo;
use think\model\relation\HasMany;

/**
 * 部门模型
 * @package app\common\model
 */
class Department extends Base
{
    use TreeModelTrait;
    /**
     * 表名
     * @var string
     */
    protected $name = 'admin_department';

    /**
     * 类型转换
     * @var array
     */
    protected $type = [
        'id'          => 'integer',
        'parent_id'   => 'integer',
        'level'       => 'integer',
        'leader_id'   => 'integer',
        'sort'        => 'integer',
        'status'      => 'integer',
        'create_time' => 'integer',
        'update_time' => 'integer',
        'delete_time' => 'integer',
    ];

    /**
     * 获取部门负责人关联
     * @return BelongsTo
     */
    public function leader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    /**
     * 获取父部门关联
     * @return BelongsTo
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * 获取子部门关联
     * @return HasMany
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * 获取部门下的用户关联
     * @return HasMany
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'department_id');
    }

    /**
     * 获取所有子部门ID(递归)
     * @param int $parentId 父部门ID
     * @param bool $includeSelf 是否包含自身
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getChildrenIds(int $parentId, bool $includeSelf = false): array
    {
        $ids = $includeSelf ? [$parentId] : [];

        // 查询所有子部门
        $children = $this->where('parent_id', $parentId)
            ->where('delete_time', null)
            ->column('id');

        if (!empty($children)) {
            foreach ($children as $childId) {
                // 递归获取子部门的子部门
                $ids = array_merge($ids, $this->getChildrenIds($childId, true));
            }
        }

        return array_unique($ids);
    }

    /**
     * 获取部门树
     * @param int $parentId 父部门ID,0表示获取所有
     * @param bool $onlyEnabled 是否只获取启用的部门
     * @return array
     */
    public function getTree(int $parentId = 0, bool $onlyEnabled = true): array
    {
        // 构建查询条件
        $where = [['delete_time', '=', null]];

        if ($onlyEnabled) {
            $where[] = ['status', '=', 1];
        }

        // 一次性查询所有部门数据
        $departments = $this->where($where)
            ->order('sort', 'asc')
            ->order('id', 'asc')
            ->select()
            ->toArray();

        // 使用 TreeBuilder 构建树形结构
        return dp_tree_builder($departments)
            ->setRootId($parentId)
            ->build();
    }

    /**
     * 获取部门完整路径
     * @param int $departmentId 部门ID
     * @param string $separator 分隔符
     * @return string
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getDepartmentPath(int $departmentId, string $separator = ' > '): string
    {
        $department = $this->find($departmentId);
        if (!$department) {
            return '';
        }

        $path = [$department->name];

        // 递归获取父部门
        if ($department['parent_id'] > 0) {
            $parentPath = $this->getDepartmentPath($department['parent_id'], $separator);
            if ($parentPath) {
                array_unshift($path, $parentPath);
            }
        }

        return implode($separator, $path);
    }

    /**
     * 获取部门完整路径数组
     * @param int $departmentId 部门ID
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getDepartmentPathArray(int $departmentId): array
    {
        $department = $this->find($departmentId);
        if (!$department) {
            return [];
        }

        $path = [$department->toArray()];

        // 递归获取父部门
        if ($department['parent_id'] > 0) {
            $parentPath = $this->getDepartmentPathArray($department['parent_id']);
            if ($parentPath) {
                $path = array_merge($parentPath, $path);
            }
        }

        return $path;
    }

    /**
     * 检查部门是否有子部门
     * @param int $departmentId 部门ID
     * @return bool
     */
    public function hasChildren(int $departmentId): bool
    {
        return $this->where('parent_id', $departmentId)
                ->where('delete_time', null)
                ->count() > 0;
    }

    /**
     * 检查部门是否有用户
     * @param int $departmentId 部门ID
     * @return bool
     */
    public function hasUsers(int $departmentId): bool
    {
        return (new User())
                ->where('department_id', $departmentId)
                ->where('delete_time', null)
                ->count() > 0;
    }

    /**
     * 删除部门前检查
     * 注意：子部门的级联删除由控制器的 beforeDelete 钩子处理
     * 这里只检查是否有用户，不检查子部门
     * @return bool
     */
    public function beforeDelete(): bool
    {
        if ($this->hasUsers($this['id'])) {
            $this->error = '该部门下存在用户,无法删除';
            return false;
        }

        return true;
    }
}
