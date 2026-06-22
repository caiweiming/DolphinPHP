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

namespace app\cms\controller;

use app\common\attribute\LoginCheck;
use app\cms\service\CmsContentService;

/**
 * CMS 前台文章展示
 */
#[LoginCheck(false)]
class Article extends Base
{
    /**
     * 文章列表（公开访问）
     */
    public function index(): string
    {
        $categoryId = $this->request->param('category_id/d', 0);
        $service = app(CmsContentService::class);

        $this->assign('categories', $service->listVisibleCategories());
        $this->assign('articles', $service->listPublishedArticles(20, $categoryId));
        $this->assign('currentCategoryId', $categoryId);

        return $this->fetch('article/index');
    }

    /**
     * 文章详情（公开访问）
     */
    public function detail(): string
    {
        $id = $this->request->param('id/d', 0);
        $service = app(CmsContentService::class);
        $article = $service->findPublishedArticle($id);
        if ($article === [] || (int)($article['id'] ?? 0) <= 0) {
            $this->error('文章不存在');
        }

        $prevArticle = $service->findPrevArticle((int)$article['id']);
        $nextArticle = $service->findNextArticle((int)$article['id']);

        $this->assign('article', $article);
        $this->assign('prevArticle', $prevArticle);
        $this->assign('nextArticle', $nextArticle);
        return $this->fetch('article/detail');
    }

    /**
     * 文章分类（公开访问）
     */
    public function category(): string
    {
        return $this->index();
    }
}
