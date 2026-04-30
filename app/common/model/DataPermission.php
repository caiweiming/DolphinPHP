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

use Exception;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\Model;
use think\model\relation\BelongsTo;

/**
 * 自定义数据权限模型
 * @package app\common\model
 */
class DataPermission extends Base
{
    /**
     * 表名
     * @var string
     */
    protected $name = 'admin_data_permission';

    /**
     * 自动写入时间戳
     * @var bool|string
     */
    protected $autoWriteTimestamp = 'timestamp';

    /**
     * 创建时间字段
     * @var string
     */
    protected $createTime = 'create_time';

    /**
     * 更新时间字段
     * @var string
     */
    protected $updateTime = 'update_time';

    /**
     * 类型转换
     * @var array
     */
    protected $type = [
        'id'          => 'integer',
        'role_id'     => 'integer',
        'create_time' => 'timestamp',
        'update_time' => 'timestamp',
    ];

    /**
     * 获取角色关联
     * @return BelongsTo
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /**
     * 获取部门ID列表数组
     * @param $value
     * @return array
     */
    public function getDepartmentIdsAttr($value): array
    {
        if (empty($value)) {
            return [];
        }
        return explode(',', $value);
    }

    /**
     * 设置部门ID列表
     * @param array|string $value
     * @return string
     */
    public function setDepartmentIdsAttr(array|string $value): string
    {
        if (is_array($value)) {
            return implode(',', array_filter($value));
        }
        return $value;
    }

    /**
     * 根据角色和资源类型获取自定义数据权限
     * @param int $roleId 角色ID
     * @param string $resourceType 资源类型
     * @return DataPermission|array|Model|null
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getByRoleAndResource(int $roleId, string $resourceType): DataPermission|array|Model|null
    {
        return $this->where('role_id', $roleId)
            ->where('resource_type', $resourceType)
            ->find();
    }

    /**
     * 保存或更新自定义数据权限
     * @param int $roleId 角色ID
     * @param string $resourceType 资源类型
     * @param array $departmentIds 部门ID列表
     * @return bool
     */
    public function saveCustomPermission(int $roleId, string $resourceType, array $departmentIds): bool
    {
        try {
            $data = [
                'role_id'        => $roleId,
                'resource_type'  => $resourceType,
                'department_ids' => $departmentIds,
            ];

            // 查找是否已存在
            $existing = $this->getByRoleAndResource($roleId, $resourceType);

            if ($existing) {
                // 更新
                $existing->save($data);
            } else {
                // 新增
                $this->save($data);
            }

            return true;
        } catch (Exception $e) {
            $this->error = '保存自定义数据权限失败: ' . $e->getMessage();
            return false;
        }
    }

    /**
     * 删除角色的自定义数据权限
     * @param int $roleId 角色ID
     * @param string|null $resourceType 资源类型,null表示删除该角色所有自定义权限
     * @return bool
     */
    public function deleteByRole(int $roleId, ?string $resourceType = null): bool
    {
        try {
            $where = ['role_id' => $roleId];

            if ($resourceType !== null) {
                $where['resource_type'] = $resourceType;
            }

            $this->where($where)->delete();
            return true;
        } catch (Exception $e) {
            $this->error = '删除自定义数据权限失败: ' . $e->getMessage();
            return false;
        }
    }

    /**
     * 获取角色的所有自定义数据权限
     * @param int $roleId 角色ID
     * @return array
     */
    public function getRoleCustomPermissions(int $roleId): array
    {
        return $this->where('role_id', $roleId)
            ->select()
            ->toArray();
    }
}
