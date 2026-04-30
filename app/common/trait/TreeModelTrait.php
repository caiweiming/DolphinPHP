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

namespace app\common\trait;

use think\Model;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 树形模型 Trait
 *
 * 为具有树形结构的模型提供通用的 level 和 path 自动维护功能
 *
 * @package app\common\trait
 */
trait TreeModelTrait
{
    /**
     * 插入前置操作,自动计算 level 和 path
     *
     * @param Model $model
     * @return void
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function onBeforeInsert(Model $model): void
    {
        if ($model['parent_id'] > 0) {
            $parent = (new static())->find($model['parent_id']);
            if ($parent) {
                $model['level'] = $parent['level'] + 1;
                $model['path'] = $parent['path'] . ',' . $model['id'];
            }
        } else {
            $model['level'] = 1;
            $model['path'] = (string)$model['id'];
        }
    }

    /**
     * 更新后置操作,更新子节点的 path 和 level
     *
     * @param Model $model
     * @return void
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function onAfterUpdate(Model $model): void
    {
        // 如果 parent_id 改变了,需要更新所有子节点的 path 和 level
        if ($model->isAutoWriteTimestamp('parent_id')) {
            $children = (new static())->where('parent_id', $model['id'])->select();
            foreach ($children as $child) {
                $child['level'] = $model['level'] + 1;
                $child['path'] = $model['path'] . ',' . $child['id'];
                $child->save();
            }
        }
    }
}
