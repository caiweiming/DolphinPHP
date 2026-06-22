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
declare (strict_types=1);

namespace app\cms\controller;

use app\common\attribute\LoginCheck;
use app\cms\service\CmsContentService;

/**
 * CMS 前台首页
 */
#[LoginCheck(false)]
class Index extends Base
{
    /**
     * 首页
     * @return string
     */
    public function index(): string
    {
        $service = app(CmsContentService::class);
        $frontListRows = (int)dp_app_setting('cms', 'content.front_list_rows', 6);

        $this->assign('categories', $service->listVisibleCategories());
        $this->assign('articles', $service->listPublishedArticles($frontListRows));
        $this->assign('frontListRows', $frontListRows);

        return $this->fetch('index/index');
    }
}
