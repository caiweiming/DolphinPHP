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

namespace app\admin\controller;

use app\common\service\AdminNavigationService;
use app\common\service\AdminWorkspaceContextBuilder;
use app\common\service\AdminWorkspaceQuickLinkService;
use Exception;
use InvalidArgumentException;
use think\response\Json;

/**
 * 后台首页控制器
 */
class Index extends Auth
{
    /**
     * 后台首页
     * @return string
     * @throws Exception
     * @author 蔡伟明 <314013107@qq.com>
     */
    public function index(): string
    {
        /** @var AdminWorkspaceContextBuilder $workspaceContextBuilder */
        $workspaceContextBuilder = app(AdminWorkspaceContextBuilder::class);

        $navigationContext = $this->buildNavigationContext();
        $workspaceContext  = $workspaceContextBuilder->buildContext($this->getCurrentUserId(), $navigationContext);
        $assetVars         = dp_collect_view_asset_vars();

        $this->assign('navigationContext', $navigationContext);
        $this->assign('dp_navigation_context', $navigationContext);
        $this->assign('dp_admin_workspace_template', root_path() . 'app/admin/view/index/_workspace.html');
        $this->assign('dp_admin_workspace_card_template', root_path() . 'app/admin/view/index/_workspace_card.html');
        $this->assign('dp_admin_workspace_quick_links_template', root_path() . 'app/admin/view/index/_workspace_quick_links.html');
        $this->assign('dp_admin_workspace_quick_links_offcanvas_template', root_path() . 'app/admin/view/index/_workspace_quick_links_offcanvas.html');
        $this->assign('workspaceContext', $workspaceContext);
        $this->assign('dp_file_css', (array)($assetVars['dp_file_css'] ?? []));
        $this->assign('dp_file_js', (array)($assetVars['dp_file_js'] ?? []));
        $this->assign('dp_extra_css', (array)($assetVars['dp_extra_css'] ?? []));
        $this->assign('dp_extra_js', (array)($assetVars['dp_extra_js'] ?? []));
        $this->assign('dp_init_js', (array)($assetVars['dp_init_js'] ?? []));

        return $this->fetch('layout');
    }

    /**
     * 获取当前用户快捷入口状态
     *
     * @return Json
     */
    public function workspaceQuickLinks(): Json
    {
        return json([
            'code' => 1,
            'msg'  => '获取成功',
            'data' => $this->resolveQuickLinkService()->getWorkspaceCardState(
                $this->getCurrentUserId(),
                $this->buildNavigationContext()
            ),
        ]);
    }

    /**
     * 新增快捷入口
     *
     * @return Json
     */
    public function addWorkspaceQuickLink(): Json
    {
        try {
            $state = $this->resolveQuickLinkService()->add(
                $this->getCurrentUserId(),
                (string)$this->request->post('key', ''),
                (array)($this->buildNavigationContext()['searchableMenus'] ?? [])
            );
        } catch (InvalidArgumentException $exception) {
            return json([
                'code' => 0,
                'msg'  => $exception->getMessage(),
                'data' => [],
            ]);
        }

        return json([
            'code' => 1,
            'msg'  => '添加成功',
            'data' => $state,
        ]);
    }

    /**
     * 删除快捷入口
     *
     * @return Json
     */
    public function removeWorkspaceQuickLink(): Json
    {
        $state = $this->resolveQuickLinkService()->remove(
            $this->getCurrentUserId(),
            (string)$this->request->post('key', ''),
            (array)($this->buildNavigationContext()['searchableMenus'] ?? [])
        );

        return json([
            'code' => 1,
            'msg'  => '移除成功',
            'data' => $state,
        ]);
    }

    /**
     * 清空快捷入口
     *
     * @return Json
     */
    public function clearWorkspaceQuickLinks(): Json
    {
        $state = $this->resolveQuickLinkService()->clear(
            $this->getCurrentUserId(),
            (array)($this->buildNavigationContext()['searchableMenus'] ?? [])
        );

        return json([
            'code' => 1,
            'msg'  => '已清空快捷入口',
            'data' => $state,
        ]);
    }

    /**
     * 保存快捷入口排序
     *
     * @return Json
     */
    public function sortWorkspaceQuickLinks(): Json
    {
        try {
            $state = $this->resolveQuickLinkService()->saveOrder(
                $this->getCurrentUserId(),
                (array)$this->request->post('keys/a', []),
                (array)($this->buildNavigationContext()['searchableMenus'] ?? [])
            );
        } catch (InvalidArgumentException $exception) {
            return json([
                'code' => 0,
                'msg'  => $exception->getMessage(),
                'data' => [],
            ]);
        }

        return json([
            'code' => 1,
            'msg'  => '排序已保存',
            'data' => $state,
        ]);
    }

    /**
     * 记忆当前应用选择
     *
     * @return Json
     */
    public function rememberApp(): Json
    {
        /** @var AdminNavigationService $navigationService */
        $navigationService = app(AdminNavigationService::class);
        $appName           = (string)$this->request->post('app', '');

        if ($navigationService->rememberCurrentApp($this->getCurrentUserId(), $appName, $this->request)) {
            return json([
                'code' => 1,
                'msg'  => 'ok',
            ]);
        }

        return json([
            'code' => 0,
            'msg'  => '无效的应用标识',
        ]);
    }

    /**
     * 构建后台导航上下文
     *
     * @return array<string, mixed>
     */
    protected function buildNavigationContext(): array
    {
        /** @var AdminNavigationService $navigationService */
        $navigationService = app(AdminNavigationService::class);

        return $navigationService->buildContext($this->getCurrentUserId(), $this->request);
    }

    /**
     * 解析快捷入口服务
     *
     * @return AdminWorkspaceQuickLinkService
     */
    protected function resolveQuickLinkService(): AdminWorkspaceQuickLinkService
    {
        return app(AdminWorkspaceQuickLinkService::class);
    }
}
