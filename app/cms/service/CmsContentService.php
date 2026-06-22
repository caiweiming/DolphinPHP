<?php
declare(strict_types=1);

namespace app\cms\service;

use app\cms\model\Article;
use app\cms\model\Category;
use think\db\Query;

/**
 * CMS 内容查询服务
 */
class CmsContentService
{
    /**
     * 补齐前台文章展示字段
     * @param array<string, mixed> $article
     * @return array<string, mixed>
     */
    private function decorateArticle(array $article): array
    {
        $category = is_array($article['category'] ?? null) ? $article['category'] : [];
        $publishedTime = (int)($article['published_time'] ?? 0);
        $source = trim((string)($article['source'] ?? ''));

        $article['category_name'] = trim((string)($category['name'] ?? '')) ?: '未分类';
        $article['source_text'] = $source !== '' ? $source : 'CMS 示例内容';
        $article['published_time_text'] = $publishedTime > 0
            ? date('Y-m-d H:i:s', $publishedTime)
            : '未设置发布时间';

        return $article;
    }

    /**
     * 前台文章列表统一排序规则
     * @param Query|Article $query
     * @return Query|Article
     */
    private function applyPublishedArticleOrder(Query|Article $query): Query|Article
    {
        return $query
            ->order('sort', 'asc')
            ->order('published_time', 'desc')
            ->order('id', 'desc');
    }

    /**
     * 获取前台分类列表
     * @return array<int, array<string, mixed>>
     */
    public function listVisibleCategories(): array
    {
        return (new Category())
            ->enabled()
            ->order('sort', 'asc')
            ->order('id', 'desc')
            ->select()
            ->toArray();
    }

    /**
     * 获取前台文章列表
     * @param int $limit
     * @param int $categoryId
     * @return array<int, array<string, mixed>>
     */
    public function listPublishedArticles(int $limit = 10, int $categoryId = 0): array
    {
        $query = $this->applyPublishedArticleOrder(
            (new Article())
                ->with(['category'])
                ->published()
        );

        if ($categoryId > 0) {
            $query->where('category_id', $categoryId);
        }

        $articles = $query
            ->limit(max(1, $limit))
            ->select()
            ->toArray();

        return array_map(fn (array $article): array => $this->decorateArticle($article), $articles);
    }

    /**
     * 获取已发布文章详情
     * @param int $id
     * @return array<string, mixed>
     */
    public function findPublishedArticle(int $id): array
    {
        if ($id <= 0) {
            return [];
        }

        $articleModel = new Article();
        $article = $articleModel
            ->with(['category'])
            ->published()
            ->find($id);

        if (!$article) {
            return [];
        }

        $articleModel->where('id', $id)->inc('view_count', 1)->update();
        $article = $articleModel
            ->with(['category'])
            ->published()
            ->find($id);

        return $article ? $this->decorateArticle($article->toArray()) : [];
    }

    /**
     * 获取上一篇已发布文章
     * @param int $id
     * @return array<string, mixed>
     */
    public function findPrevArticle(int $id): array
    {
        if ($id <= 0) {
            return [];
        }

        $current = (new Article())
            ->published()
            ->find($id);

        if (!$current) {
            return [];
        }

        $article = (new Article())
            ->published()
            ->where(function ($query) use ($current): void {
                $query
                    ->where('sort', '<', (int)$current->getAttr('sort'))
                    ->whereOr(function ($subQuery) use ($current): void {
                        $subQuery
                            ->where('sort', '=', (int)$current->getAttr('sort'))
                            ->where('published_time', '>', (int)$current->getAttr('published_time'));
                    })
                    ->whereOr(function ($subQuery) use ($current): void {
                        $subQuery
                            ->where('sort', '=', (int)$current->getAttr('sort'))
                            ->where('published_time', '=', (int)$current->getAttr('published_time'))
                            ->where('id', '>', (int)$current->getAttr('id'));
                    });
            })
            ->order('sort', 'desc')
            ->order('published_time', 'asc')
            ->order('id', 'asc')
            ->find();

        return $article?->toArray() ?? [];
    }

    /**
     * 获取下一篇已发布文章
     * @param int $id
     * @return array<string, mixed>
     */
    public function findNextArticle(int $id): array
    {
        if ($id <= 0) {
            return [];
        }

        $current = (new Article())
            ->published()
            ->find($id);

        if (!$current) {
            return [];
        }

        $article = (new Article())
            ->published()
            ->where(function ($query) use ($current): void {
                $query
                    ->where('sort', '>', (int)$current->getAttr('sort'))
                    ->whereOr(function ($subQuery) use ($current): void {
                        $subQuery
                            ->where('sort', '=', (int)$current->getAttr('sort'))
                            ->where('published_time', '<', (int)$current->getAttr('published_time'));
                    })
                    ->whereOr(function ($subQuery) use ($current): void {
                        $subQuery
                            ->where('sort', '=', (int)$current->getAttr('sort'))
                            ->where('published_time', '=', (int)$current->getAttr('published_time'))
                            ->where('id', '<', (int)$current->getAttr('id'));
                    });
            })
            ->order('sort', 'asc')
            ->order('published_time', 'desc')
            ->order('id', 'desc')
            ->find();

        return $article?->toArray() ?? [];
    }
}
