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

use app\common\attribute\LoginCheck;
use app\common\interface\PermissionService;
use app\common\service\AdminNavigationService;
use app\common\service\AdminShellContextBuilder;
use app\common\model\User as UserModel;
use app\common\trait\PermissionCheck;
use Exception;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use Throwable;

/**
 * 后台权限认证控制器
 * @package app\admin\controller
 */
#[LoginCheck]
class Auth extends Common
{
    use PermissionCheck;

    /**
     * 初始化
     * @throws Throwable
     */
    protected function initialize(): void
    {
        parent::initialize();

        // 判断是否登录并设置用户上下文
        $userId = $this->isLogin();
        $this->getUserContext()->setUserId($userId);

        // 【常规模式】如果未启用注解认证中间件，则在此验证登录
        if (!isset($this->request->annotationAuth) || !$this->request->annotationAuth) {
            $this->checkLoginInNormalMode();
        }

        // 验证用户状态（防止被禁用后仍能访问）
        if ($this->getUserContext()->isLoggedIn()) {
            $this->checkUserStatus();
            $this->assignMenusToLayout();
        }
    }

    /**
     * 向布局统一注入菜单数据
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    protected function assignMenusToLayout(): void
    {
        // 异步请求通常不渲染布局，跳过可减少无效开销
        if ($this->request->isAjax()) {
            return;
        }

        if (!$this->permissionService) {
            $this->permissionService = app(PermissionService::class);
        }

        /** @var AdminNavigationService $navigationService */
        $navigationService = app(AdminNavigationService::class);
        /** @var AdminShellContextBuilder $shellContextBuilder */
        $shellContextBuilder = app(AdminShellContextBuilder::class);
        $navigationContext   = $navigationService->buildContext($this->getCurrentUserId(), $this->request);
        $adminShellContext   = $shellContextBuilder->buildContext($this->adminUser);

        dp_register_page_assets((array)($adminShellContext['assets'] ?? []));
        $assetVars = dp_collect_view_asset_vars();

        $this->assign('navigationContext', $navigationContext);
        $this->assign('dp_navigation_context', $navigationContext);
        $this->assign('dp_admin_topbar_slots', (array)($adminShellContext['topbar_slots'] ?? []));
        $this->assign('dp_admin_user_menu_slots', (array)($adminShellContext['user_menu_slots'] ?? []));
        $this->assign('dp_admin_current_user', (array)($adminShellContext['current_user'] ?? []));
        $this->assign('dp_admin_topbar_slots_template', root_path() . 'app/admin/view/layout/_plugin_topbar_slots.html');
        $this->assign('dp_admin_current_user_dropdown_template', root_path() . 'app/admin/view/layout/_current_user_dropdown.html');
        $this->assign('dp_admin_user_menu_slots_template', root_path() . 'app/admin/view/layout/_plugin_user_menu_slots.html');
        $this->assign(
            'dp_admin_can_clear_cache',
            dp_is_super_admin($this->getCurrentUserId()) || dp_has_permission('admin.system.clear_cache')
        );
        $this->assign('dp_admin_clear_cache_url', (string)dp_url('admin/system/clearCache'));
        $this->assign('dp_file_css', (array)($assetVars['dp_file_css'] ?? []));
        $this->assign('dp_file_js', (array)($assetVars['dp_file_js'] ?? []));
        $this->assign('dp_extra_css', (array)($assetVars['dp_extra_css'] ?? []));
        $this->assign('dp_extra_js', (array)($assetVars['dp_extra_js'] ?? []));
        $this->assign('dp_init_js', (array)($assetVars['dp_init_js'] ?? []));
        $this->assign('menus', $navigationContext['sidebarMenus'] ?? []);

    }

    /**
     * 构建后台当前登录用户摘要
     * @param int $userId
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    private function buildAdminUserSummary(int $userId): array
    {
        return app(AdminShellContextBuilder::class)->buildCurrentUserSummary($this->adminUser);
    }

    /**
     * 常规模式：验证登录
     * 仅在未启用注解认证中间件时调用
     */
    private function checkLoginInNormalMode(): void
    {
        if (!$this->adminUser) {
            $this->redirect($this->buildLoginUrlWithRedirect());
        }
    }

    /**
     * 检查用户状态
     * 如果用户被禁用，强制退出登录
     * @throws Throwable
     */
    protected function checkUserStatus(): void
    {
        try {
            $user = UserModel::find($this->getUserContext()->getUserId());

            if (!$user) {
                // 用户不存在，清除会话
                $this->forceLogout('您的账号已不存在');
            }

            if ($user['status'] == 0) {
                // 用户被禁用，清除会话并记录日志
                dp_log_security('已禁用用户尝试访问系统', [
                    'user_id'  => $this->getUserContext()->getUserId(),
                    'username' => $user['username'],
                    'action'   => '强制退出登录',
                    'reason'   => '账号已被禁用'
                ]);

                $this->forceLogout('您的账号已被禁用，请联系管理员');
            }
        } catch (Exception) {
            // 查询失败，为安全起见，强制退出
            $this->forceLogout('系统异常，请重新登录');
        }
    }

    /**
     * 强制退出登录
     * @param string $message 提示消息
     */
    protected function forceLogout(string $message): void
    {
        dp_revoke_admin_remember_login($this->getUserContext()->getUserId());
        dp_clear_admin_session();

        // 提示错误信息，并重定向到登录页
        $this->error($message, $this->buildLoginUrlWithRedirect());
    }

    /**
     * 构建携带回跳地址的登录 URL
     * @return string
     */
    private function buildLoginUrlWithRedirect(): string
    {
        $currentUrl = $this->request->url();
        if ($currentUrl === '' || str_contains($currentUrl, '/admin/login/index')) {
            return (string)dp_url(config('system.login_url'));
        }

        return (string)dp_url(config('system.login_url'), ['redirect' => $currentUrl]);
    }

    /**
     * 获取当前用户ID
     * 供子类使用的辅助方法
     * @return int
     */
    protected function getCurrentUserId(): int
    {
        return $this->getUserContext()->getUserId();
    }
}
