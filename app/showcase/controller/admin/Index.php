<?php
declare(strict_types=1);

namespace app\showcase\controller\admin;

use app\common\attribute\Permission;
use app\showcase\service\registry\ShowcaseRegistryService;

/**
 * Showcase 首页控制器
 */
#[Permission('渲染器示例首页', code: 'admin.index', icon: 'ti ti-components', sort: 10)]
final class Index extends Auth
{
    /**
     * 首页
     */
    public function index(): string
    {
        $this->assign('renderers', app(ShowcaseRegistryService::class)->renderers());

        return $this->fetch('index/index');
    }
}
