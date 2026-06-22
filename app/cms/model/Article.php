<?php
declare(strict_types=1);

namespace app\cms\model;

use app\common\model\Base;
use think\db\Query;
use think\model\relation\BelongsTo;

/**
 * CMS 文章模型
 */
class Article extends Base
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
    protected $name = 'cms_article';

    /**
     * 所属分类
     * @return BelongsTo
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }

    /**
     * 已发布范围查询
     * @param Query $query
     * @return void
     */
    public function scopePublished(Query $query): void
    {
        $query->where('status', 1);
    }
}
