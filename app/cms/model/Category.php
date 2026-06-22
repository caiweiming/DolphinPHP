<?php
declare(strict_types=1);

namespace app\cms\model;

use app\common\model\Base;
use think\db\Query;
use think\model\relation\HasMany;

/**
 * CMS 分类模型
 */
class Category extends Base
{
    /**
     * 自动时间戳写入类型
     * @var string
     */
    protected $autoWriteTimestamp = 'int';

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
     * 模型名称
     * @var string
     */
    protected $name = 'cms_category';

    /**
     * 分类文章关联
     * @return HasMany
     */
    public function articles(): HasMany
    {
        return $this->hasMany(Article::class, 'category_id', 'id');
    }

    /**
     * 启用状态范围查询
     * @param Query $query
     * @return void
     */
    public function scopeEnabled(Query $query): void
    {
        $query->where('status', 1);
    }
}
