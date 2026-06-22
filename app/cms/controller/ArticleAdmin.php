<?php
declare(strict_types=1);

namespace app\cms\controller;

/**
 * CMS 文章后台兼容控制器
 *
 * 兼容旧入口 `cms/article_admin/*`，实际逻辑统一复用
 * `app\cms\controller\admin\Article`。
 */
class ArticleAdmin extends \app\cms\controller\admin\Article
{
}
