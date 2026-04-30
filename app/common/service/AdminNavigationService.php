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

namespace app\common\service;

use app\common\interface\PermissionService as PermissionServiceInterface;
use think\Request;
use Throwable;

/**
 * 后台应用导航上下文服务
 *
 * 统一生成后台壳层需要的当前应用、应用切换器、当前应用菜单与公共菜单数据。
 */
class AdminNavigationService
{
    /**
     * 当前应用记忆会话键
     */
    private const SESSION_KEY = 'admin_navigation.current_app';

    /**
     * 工作台路由
     */
    private const WORKSPACE_ROUTE = 'admin/index/index';

    /**
     * 工作台文案
     */
    private const WORKSPACE_LABEL = '工作台';

    /**
     * 公共入口文案
     */
    private const PUBLIC_MENU_LABEL = '公共入口';

    /**
     * @param AppService $appService
     * @param PermissionServiceInterface $permissionService
     */
    public function __construct(
        private readonly AppService                 $appService,
        private readonly PermissionServiceInterface $permissionService
    )
    {
    }

    /**
     * 构建后台导航上下文
     *
     * @param int $userId
     * @param Request $request
     * @return array
     */
    public function buildContext(int $userId, Request $request): array
    {
        $currentRoute = $this->buildCurrentRoute($request);
        $currentShort = $this->buildCurrentShortRoute($request);
        $workspace    = $this->buildWorkspaceMenu($currentRoute, $currentShort);

        try {
            $rawMenus = $this->permissionService->getUserMenus($userId);
        } catch (Throwable) {
            $rawMenus = [];
        }

        $normalizedMenus = $this->normalizeMenus($rawMenus, $currentRoute, $currentShort);
        $partitioned     = $this->partitionMenus($normalizedMenus);
        $switcherApps    = $this->buildAppSwitcherApps($userId, $partitioned['appMenus'], $partitioned['rootMenus']);
        $currentAppState = $this->resolveCurrentApp($request, $switcherApps, $currentRoute);

        $currentAppName  = (string)($currentAppState['app']['name'] ?? '');
        $currentAppMenus = $currentAppName !== '' ? ($partitioned['appMenus'][$currentAppName] ?? []) : [];
        $publicMenus     = $partitioned['publicMenus'];

        if ($currentAppName !== '') {
            session(self::SESSION_KEY, $currentAppName);
        }

        $currentApp = $currentAppState['app'];
        if (is_array($currentApp)) {
            $currentApp['has_menu_root'] = isset($partitioned['rootMenus'][$currentAppName]);
            $currentApp['is_fallback']   = $currentAppState['resolved_by'] === 'fallback';
            $currentApp['is_current']    = true;
        }
        $switcherApps = array_map(
            static function (array $item) use ($currentAppName): array {
                $item['is_current'] = (string)($item['name'] ?? '') === $currentAppName;
                return $item;
            },
            $switcherApps
        );

        $sidebarMenusByApp = [];
        foreach ($partitioned['appMenus'] as $appName => $menus) {
            $sidebarMenusByApp[$appName] = $this->buildSidebarMenus($workspace, $menus, $publicMenus);
        }

        $sidebarMenus = $currentAppName !== ''
            ? ($sidebarMenusByApp[$currentAppName] ?? $this->buildSidebarMenus($workspace, [], $publicMenus))
            : $this->buildSidebarMenus($workspace, [], $publicMenus);

        return [
            'currentApp'        => $currentApp,
            'appSwitcherApps'   => $switcherApps,
            'currentAppMenus'   => $currentAppMenus,
            'publicMenus'       => $publicMenus,
            'workspaceMenu'     => $workspace,
            'searchableMenus'   => $this->buildSearchableMenus(
                $workspace,
                $partitioned['appMenus'],
                $publicMenus,
                $this->buildSearchableAppMetaMap($switcherApps),
                $currentAppName
            ),
            'sidebarMenus'      => $sidebarMenus,
            'sidebarMenusByApp' => $sidebarMenusByApp,
            'navigationMeta'    => [
                'resolved_by'     => $currentAppState['resolved_by'],
                'requested_app'   => $currentAppState['requested_app'],
                'remembered_app'  => $this->normalizeAppName((string)session(self::SESSION_KEY)),
                'current_route'   => $currentRoute,
                'current_short'   => $currentShort,
                'tab_mode'        => strtolower((string)config('system.tab_mode', 'iframe')),
                'workspace_label' => self::WORKSPACE_LABEL,
                'workspace_route' => self::WORKSPACE_ROUTE,
                'workspace_url'   => $workspace['url'],
            ],
        ];
    }

    /**
     * 记忆当前应用选择
     *
     * @param int $userId
     * @param string $app
     * @param Request $request
     * @return bool
     */
    public function rememberCurrentApp(int $userId, string $app, Request $request): bool
    {
        $targetApp = $this->normalizeAppName($app);
        if ($targetApp === '') {
            return false;
        }

        $originalRememberedApp = $this->normalizeAppName((string)session(self::SESSION_KEY));
        $context               = $this->buildContext($userId, $request);
        session(self::SESSION_KEY, $originalRememberedApp);

        foreach ($context['appSwitcherApps'] as $item) {
            if ((string)($item['name'] ?? '') === $targetApp) {
                session(self::SESSION_KEY, $targetApp);
                return true;
            }
        }

        return false;
    }

    /**
     * 构建当前请求路由
     *
     * @param Request $request
     * @return string
     */
    private function buildCurrentRoute(Request $request): string
    {
        $appName    = strtolower((string)app('http')->getName());
        $controller = strtolower($request->controller());
        $action     = strtolower($request->action());

        return $this->normalizeRoute($appName . '/' . $controller . '/' . $action);
    }

    /**
     * 构建当前请求短路由
     *
     * @param Request $request
     * @return string
     */
    private function buildCurrentShortRoute(Request $request): string
    {
        return $this->normalizeRoute(
            strtolower($request->controller()) . '/' . strtolower($request->action())
        );
    }

    /**
     * 构建工作台菜单
     *
     * @param string $currentRoute
     * @param string $currentShort
     * @return array
     */
    private function buildWorkspaceMenu(string $currentRoute, string $currentShort): array
    {
        $isActive = $currentRoute === self::WORKSPACE_ROUTE || $currentShort === 'index/index';

        return [
            'id'       => 'workspace',
            'name'     => self::WORKSPACE_LABEL,
            'code'     => 'workspace',
            'route'    => self::WORKSPACE_ROUTE,
            'url'      => (string)dp_url(self::WORKSPACE_ROUTE),
            'icon'     => 'ti ti-layout-dashboard',
            'active'   => $isActive,
            'children' => [],
        ];
    }

    /**
     * 规范化菜单树
     *
     * @param array $menus
     * @param string $currentRoute
     * @param string $currentShort
     * @return array
     */
    private function normalizeMenus(array $menus, string $currentRoute, string $currentShort): array
    {
        $normalized = [];

        foreach ($menus as $menu) {
            if (!is_array($menu)) {
                continue;
            }

            $children = $this->normalizeMenus((array)($menu['children'] ?? []), $currentRoute, $currentShort);
            $route    = $this->normalizeRoute((string)($menu['route'] ?? ''));
            $active   = $this->isMenuRouteActive($route, $currentRoute, $currentShort);

            if (!$active && $children !== []) {
                foreach ($children as $child) {
                    if (!empty($child['active'])) {
                        $active = true;
                        break;
                    }
                }
            }

            $normalized[] = [
                'id'       => $menu['id'] ?? uniqid('menu_', true),
                'name'     => trim((string)($menu['name'] ?? '')),
                'code'     => trim((string)($menu['code'] ?? '')),
                'route'    => $route,
                'url'      => $route !== '' ? (string)dp_url((string)$menu['route']) : '#',
                'icon'     => trim((string)($menu['icon'] ?? 'ti ti-point')),
                'active'   => $active,
                'children' => $children,
            ];
        }

        return $normalized;
    }

    /**
     * 按应用拆分菜单
     *
     * @param array $menus
     * @return array{appMenus: array<string, array>, rootMenus: array<string, array>, publicMenus: array}
     */
    private function partitionMenus(array $menus): array
    {
        $appMenus    = [];
        $rootMenus   = [];
        $publicMenus = [];

        foreach ($menus as $menu) {
            $owner = $this->resolveBranchOwner($menu);
            if ($owner === '') {
                $publicMenus[] = $menu;
                continue;
            }

            if ($this->isAppRootMenu($menu, $owner)) {
                $rootMenus[$owner] = $menu;
                if ($menu['children'] !== []) {
                    $appMenus[$owner] = array_values($menu['children']);
                    continue;
                }
            }

            $appMenus[$owner]   = $appMenus[$owner] ?? [];
            $appMenus[$owner][] = $menu;
        }

        return [
            'appMenus'    => $appMenus,
            'rootMenus'   => $rootMenus,
            'publicMenus' => $publicMenus,
        ];
    }

    /**
     * 构建顶部搜索专用菜单注册表
     *
     * @param array $workspace
     * @param array $appMenus
     * @param array $publicMenus
     * @param array $appMetaMap
     * @param string $currentAppName
     * @return array
     */
    private function buildSearchableMenus(
        array  $workspace,
        array  $appMenus,
        array  $publicMenus,
        array  $appMetaMap,
        string $currentAppName
    ): array
    {
        $items    = [];
        $seen     = [];
        $sequence = 0;

        $appendMenu = function (
            array  $menu,
            string $appName = '',
            string $appTitle = '',
            bool   $isPublic = false,
            bool   $isWorkspace = false
        ) use (&$appendMenu, &$items, &$seen, &$sequence, $currentAppName): void {
            $url   = trim((string)($menu['url'] ?? ''));
            $route = $this->normalizeRoute((string)($menu['route'] ?? ''));
            $title = trim((string)($menu['name'] ?? ''));

            if ($title !== '' && $url !== '' && $url !== '#') {
                $normalizedPath = $this->normalizeMenuUrlPath($url);
                $iframeUrl      = $this->buildIframeUrlFromMenuUrl($url);
                $scopeKey       = $isWorkspace ? 'workspace' : ($isPublic ? 'public' : $appName);
                $uniqueKey      = strtolower($scopeKey . '|' . $route . '|' . $normalizedPath . '|' . $title);

                if (!isset($seen[$uniqueKey])) {
                    $items[]          = [
                        'id'              => (string)($menu['id'] ?? uniqid('search_', true)),
                        'title'           => $title,
                        'route'           => $route,
                        'url'             => $url,
                        'iframe_url'      => $iframeUrl,
                        'icon'            => trim((string)($menu['icon'] ?? 'ti ti-point')),
                        'normalized_path' => $normalizedPath,
                        'app_name'        => $appName,
                        'app_title'       => $appTitle,
                        'is_current_app'  => $appName !== '' && $appName === $currentAppName,
                        'is_public'       => $isPublic,
                        'is_workspace'    => $isWorkspace,
                        'sort_order'      => $sequence++,
                    ];
                    $seen[$uniqueKey] = true;
                }
            }

            foreach ((array)($menu['children'] ?? []) as $child) {
                if (!is_array($child)) {
                    continue;
                }

                $appendMenu($child, $appName, $appTitle, $isPublic, $isWorkspace);
            }
        };

        $appendMenu($workspace, '', self::WORKSPACE_LABEL, false, true);

        foreach ($publicMenus as $menu) {
            if (!is_array($menu)) {
                continue;
            }

            $appendMenu($menu, '', self::PUBLIC_MENU_LABEL, true, false);
        }

        foreach ($appMenus as $appName => $menus) {
            $title = trim((string)($appMetaMap[$appName]['title'] ?? $appName));
            foreach ($menus as $menu) {
                if (!is_array($menu)) {
                    continue;
                }

                $appendMenu($menu, (string)$appName, $title, false, false);
            }
        }

        return $items;
    }

    /**
     * 构建应用展示元数据映射
     *
     * @param array $switcherApps
     * @return array
     */
    private function buildSearchableAppMetaMap(array $switcherApps): array
    {
        $map = [];

        foreach ($this->appService->getApps(true) as $app) {
            $appName = $this->normalizeAppName((string)($app['name'] ?? ''));
            if ($appName === '') {
                continue;
            }

            $map[$appName] = [
                'title' => trim((string)($app['title'] ?? $appName)),
            ];
        }

        foreach ($switcherApps as $app) {
            $appName = $this->normalizeAppName((string)($app['name'] ?? ''));
            if ($appName === '') {
                continue;
            }

            $map[$appName] = [
                'title' => trim((string)($app['title'] ?? ($map[$appName]['title'] ?? $appName))),
            ];
        }

        return $map;
    }

    /**
     * 构建应用切换器列表
     *
     * @param int $userId
     * @param array $appMenus
     * @param array $rootMenus
     * @return array
     */
    private function buildAppSwitcherApps(int $userId, array $appMenus, array $rootMenus): array
    {
        $items = [];

        foreach ($this->appService->getApps(true) as $app) {
            $appName = $this->normalizeAppName((string)($app['name'] ?? ''));
            if ($appName === '') {
                continue;
            }

            $launcher = $this->resolveLauncher($userId, $appName, $appMenus[$appName] ?? [], $rootMenus[$appName] ?? null);
            if ($launcher === null) {
                continue;
            }

            $items[] = [
                'name'        => $appName,
                'code'        => trim((string)($this->appService->getAppDefinition($appName)['code'] ?? $appName)),
                'title'       => trim((string)($app['title'] ?? $appName)),
                'icon'        => trim((string)($app['icon'] ?? 'ti ti-apps')),
                'image'       => $this->detectAppImage($appName),
                'url'         => $launcher['url'],
                'route'       => $launcher['route'],
                'resolved_by' => $launcher['resolved_by'],
                'is_current'  => false,
            ];
        }

        return $items;
    }

    /**
     * 解析当前应用
     * @param Request $request
     * @param array $switcherApps
     * @param string $currentRoute
     * @return array
     */
    private function resolveCurrentApp(Request $request, array $switcherApps, string $currentRoute): array
    {
        $switcherMap = [];
        foreach ($switcherApps as $app) {
            $switcherMap[(string)($app['name'] ?? '')] = $app;
        }

        $requestedApp  = $this->normalizeAppName((string)$request->param('app', ''));
        $rememberedApp = $this->normalizeAppName((string)session(self::SESSION_KEY));

        if (!$this->isWorkspaceRoute($currentRoute)) {
            $routeApp = $this->normalizeAppName((string)($this->resolveAppFromRoute($currentRoute)['name'] ?? ''));
            if ($routeApp !== '' && isset($switcherMap[$routeApp])) {
                return [
                    'app'           => $switcherMap[$routeApp],
                    'resolved_by'   => 'route',
                    'requested_app' => $requestedApp,
                ];
            }
        }

        if ($requestedApp !== '' && isset($switcherMap[$requestedApp])) {
            return [
                'app'           => $switcherMap[$requestedApp],
                'resolved_by'   => 'param',
                'requested_app' => $requestedApp,
            ];
        }

        if ($rememberedApp !== '' && isset($switcherMap[$rememberedApp])) {
            return [
                'app'           => $switcherMap[$rememberedApp],
                'resolved_by'   => 'session',
                'requested_app' => $requestedApp,
            ];
        }

        return [
            'app'           => $switcherApps[0] ?? null,
            'resolved_by'   => 'fallback',
            'requested_app' => $requestedApp,
        ];
    }

    /**
     * 构建侧边栏最终菜单
     *
     * @param array $workspace
     * @param array $currentAppMenus
     * @param array $publicMenus
     * @return array
     */
    private function buildSidebarMenus(array $workspace, array $currentAppMenus, array $publicMenus): array
    {
        $menus = [$workspace];
        $menus = array_merge($menus, $currentAppMenus);

        if ($publicMenus !== []) {
            $menus[] = [
                'id'       => 'public-entry-group',
                'name'     => '公共入口',
                'code'     => 'public.entry.group',
                'route'    => '',
                'url'      => '#',
                'icon'     => 'ti ti-layout-grid',
                'active'   => $this->hasActiveChild($publicMenus),
                'children' => $publicMenus,
            ];
        }

        return $menus;
    }

    /**
     * 解析菜单分支归属应用
     *
     * @param array $menu
     * @return string
     */
    private function resolveBranchOwner(array $menu): string
    {
        $code  = trim((string)($menu['code'] ?? ''));
        $route = trim((string)($menu['route'] ?? ''));

        $byCode = $this->appService->resolveAppByMenuCode($code);
        if ($byCode) {
            return $this->normalizeAppName((string)($byCode['name'] ?? ''));
        }

        $byPermission = $this->appService->resolveAppByPermission($code, $route);
        if ($byPermission) {
            return $this->normalizeAppName((string)($byPermission['name'] ?? ''));
        }

        $owners = [];
        foreach ((array)($menu['children'] ?? []) as $child) {
            if (!is_array($child)) {
                continue;
            }

            $childOwner = $this->resolveBranchOwner($child);
            if ($childOwner !== '') {
                $owners[$childOwner] = true;
            }
        }

        return count($owners) === 1 ? (string)array_key_first($owners) : '';
    }

    /**
     * 判断是否为应用根菜单
     *
     * @param array $menu
     * @param string $owner
     * @return bool
     */
    private function isAppRootMenu(array $menu, string $owner): bool
    {
        $definition = $this->appService->getAppDefinition($owner);
        $code       = trim((string)($menu['code'] ?? ''));
        $appCode    = trim((string)($definition['code'] ?? ''));

        return $code !== '' && ($code === $owner || ($appCode !== '' && $code === $appCode));
    }

    /**
     * 解析应用落地页
     *
     * @param int $userId
     * @param string $appName
     * @param array $menus
     * @param array|null $rootMenu
     * @return array|null
     */
    private function resolveLauncher(int $userId, string $appName, array $menus, ?array $rootMenu): ?array
    {
        $definition    = $this->appService->getAppDefinition($appName);
        $launcherRoute = $this->normalizeRoute((string)($definition['launcher_route'] ?? ''));

        if ($launcherRoute !== '' && $this->permissionService->canAccessRoute($userId, $launcherRoute)) {
            return [
                'route'       => $launcherRoute,
                'url'         => (string)dp_url($launcherRoute),
                'resolved_by' => 'launcher_route',
            ];
        }

        $firstMenu = $this->findFirstNavigableMenu($menus);
        if ($firstMenu !== null) {
            return [
                'route'       => (string)($firstMenu['route'] ?? ''),
                'url'         => (string)($firstMenu['url'] ?? ''),
                'resolved_by' => 'menu_leaf',
            ];
        }

        if (is_array($rootMenu) && trim((string)($rootMenu['url'] ?? '')) !== '#') {
            return [
                'route'       => (string)($rootMenu['route'] ?? ''),
                'url'         => (string)($rootMenu['url'] ?? ''),
                'resolved_by' => 'menu_root',
            ];
        }

        return null;
    }

    /**
     * 查找第一个可导航菜单
     *
     * @param array $menus
     * @return array|null
     */
    private function findFirstNavigableMenu(array $menus): ?array
    {
        foreach ($menus as $menu) {
            if (!is_array($menu)) {
                continue;
            }

            $url = trim((string)($menu['url'] ?? ''));
            if ($url !== '' && $url !== '#') {
                return $menu;
            }

            $found = $this->findFirstNavigableMenu((array)($menu['children'] ?? []));
            if ($found !== null) {
                return $found;
            }
        }

        return null;
    }

    /**
     * 探测应用静态图片
     *
     * @param string $appName
     * @return string
     */
    private function detectAppImage(string $appName): string
    {
        $publicPath = rtrim(app()->getRootPath(), DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR . 'public'
            . DIRECTORY_SEPARATOR . 'apps'
            . DIRECTORY_SEPARATOR . $appName
            . DIRECTORY_SEPARATOR;

        foreach (['svg', 'png', 'jpg', 'jpeg'] as $extension) {
            $file = $publicPath . 'app.' . $extension;
            if (is_file($file)) {
                return '/apps/' . $appName . '/app.' . $extension;
            }
        }

        return '';
    }

    /**
     * 是否命中工作台路由
     *
     * @param string $route
     * @return bool
     */
    private function isWorkspaceRoute(string $route): bool
    {
        return $this->normalizeRoute($route) === self::WORKSPACE_ROUTE;
    }

    /**
     * 判断菜单路由是否处于激活状态
     *
     * @param string $menuRoute
     * @param string $currentRoute
     * @param string $currentShort
     * @return bool
     */
    private function isMenuRouteActive(string $menuRoute, string $currentRoute, string $currentShort): bool
    {
        if ($menuRoute === '') {
            return false;
        }

        return $menuRoute === $currentRoute
            || $menuRoute === $currentShort
            || str_ends_with($menuRoute, '/' . $currentShort);
    }

    /**
     * 判断菜单集合是否包含激活项
     *
     * @param array $menus
     * @return bool
     */
    private function hasActiveChild(array $menus): bool
    {
        foreach ($menus as $menu) {
            if (!empty($menu['active'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * 规范化应用名
     *
     * @param string $app
     * @return string
     */
    private function normalizeAppName(string $app): string
    {
        return strtolower(trim($app));
    }

    /**
     * 规范化路由
     *
     * @param string $route
     * @return string
     */
    private function normalizeRoute(string $route): string
    {
        $route = trim(str_replace('\\', '/', $route), '/');
        if ($route === '') {
            return '';
        }

        return strtolower((string)preg_replace('/\.html?$/i', '', $route));
    }

    /**
     * 规范化菜单 URL 路径
     *
     * @param string $url
     * @return string
     */
    private function normalizeMenuUrlPath(string $url): string
    {
        $path = (string)(parse_url($url, PHP_URL_PATH) ?? '');
        $path = '/' . ltrim($path, '/');
        $path = preg_replace('/\.html?$/i', '', $path);

        return rtrim((string)$path, '/') ?: '/';
    }

    /**
     * 构建 iframe 模式下可直接打开的 URL
     *
     * @param string $url
     * @return string
     */
    private function buildIframeUrlFromMenuUrl(string $url): string
    {
        $path  = $this->normalizeMenuUrlPath($url);
        $query = (string)(parse_url($url, PHP_URL_QUERY) ?? '');

        return $query !== '' ? $path . '?' . $query : $path;
    }

    /**
     * 根据路由解析应用
     *
     * @param string $route
     * @return array|null
     */
    private function resolveAppFromRoute(string $route): ?array
    {
        $normalizedRoute = $this->normalizeRoute($route);
        if ($normalizedRoute === '') {
            return null;
        }

        $appName = strtolower((string)strtok($normalizedRoute, '/'));
        if ($appName === '') {
            return null;
        }

        return $this->appService->resolveApp($appName);
    }
}
