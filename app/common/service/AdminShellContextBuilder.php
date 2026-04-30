<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\interface\PermissionService;
use app\common\model\User;
use app\common\plugin\PluginRegistry;
use think\Model;

/**
 * 后台壳层上下文构建服务
 *
 * 统一负责当前用户摘要、后台顶部工具项、右上角用户菜单项与壳层级资源收集。
 */
class AdminShellContextBuilder
{
    /**
     * @param PermissionService $permissionService
     */
    public function __construct(
        private readonly PermissionService $permissionService
    )
    {
    }

    /**
     * 构建后台壳层上下文
     *
     * @param mixed $adminUser
     * @return array<string, mixed>
     */
    public function buildContext(mixed $adminUser = []): array
    {
        $adminUser = $this->normalizeAdminUser($adminUser);
        if ($adminUser === []) {
            $sessionUser = $this->normalizeAdminUser(dp_is_login());
            if ($sessionUser === []) {
                return $this->emptyContext();
            }

            $adminUser = $sessionUser;
        }

        $userId = (int)($adminUser['id'] ?? 0);
        if ($userId <= 0) {
            return $this->emptyContext();
        }

        $currentUser = $this->buildCurrentUserSummary($adminUser);
        $topbar = $this->buildSlotItemsWithAssets(
            $this->getRegistry()->getAdminSlotItems('topbar.tools'),
            $userId,
            'topbar.tools',
            $currentUser
        );
        $userMenu = $this->buildSlotItemsWithAssets(
            $this->getRegistry()->getAdminSlotItems('user.menu'),
            $userId,
            'user.menu',
            $currentUser
        );

        return [
            'current_user'    => $currentUser,
            'topbar_slots'    => $topbar['items'],
            'user_menu_slots' => $userMenu['items'],
            'assets'          => $this->mergeAssetDeclarations($topbar['assets'], $userMenu['assets']),
        ];
    }

    /**
     * 构建后台当前登录用户摘要
     *
     * @param mixed $adminUser
     * @return array<string, string>
     */
    public function buildCurrentUserSummary(mixed $adminUser): array
    {
        $adminUser = $this->normalizeAdminUser($adminUser);
        $userId = (int)($adminUser['id'] ?? 0);
        if ($userId > 0) {
            $userModel = User::find($userId);
            if ($userModel) {
                $freshUser = $userModel->toArray();
                foreach (['username', 'nickname', 'avatar'] as $field) {
                    if (array_key_exists($field, $freshUser)) {
                        $adminUser[$field] = $freshUser[$field];
                    }
                }
            }
        }

        $username    = trim((string)($adminUser['username'] ?? ''));
        $nickname    = trim((string)($adminUser['nickname'] ?? ''));
        $displayName = $nickname !== '' ? $nickname : ($username !== '' ? $username : '管理员');
        $avatar      = $adminUser['avatar'] ?? null;
        $avatarUrl   = '/static/img/avatar.jpg';

        if (is_numeric($avatar) && (int)$avatar > 0) {
            $resolvedAvatarUrl = trim(dp_get_file_path((int)$avatar));
            if ($resolvedAvatarUrl !== '') {
                $avatarUrl = $resolvedAvatarUrl;
            }
        } elseif (is_string($avatar) && trim($avatar) !== '') {
            $avatarUrl = trim($avatar);
        }

        $profileRoute      = 'admin/profile/index';
        $profileUrl        = '';
        $profileMenuItem   = [];
        $profileController = root_path() . 'app/admin/controller/Profile.php';
        if (is_file($profileController)) {
            $profileUrl      = (string)dp_url($profileRoute);
            $profileMenuItem = [
                'title'      => '账号管理',
                'route'      => $profileRoute,
                'url'        => $profileUrl,
                'iframe_url' => '/admin/profile/index',
                'icon'       => 'ti ti-user-circle',
                'app_name'   => 'admin',
            ];
        }

        return [
            'display_name'      => $displayName,
            'secondary_text'    => $username !== '' ? $username : '当前登录用户',
            'avatar_url'        => $avatarUrl,
            'profile_url'       => $profileUrl,
            'profile_menu_item' => $profileMenuItem,
            'logout_url'        => '/admin/logout/index.html',
        ];
    }

    /**
     * 解析后台槽位入口
     *
     * @param array<int, array<string, mixed>> $items
     * @param int $userId
     * @param string $slot
     * @return array<int, array<string, mixed>>
     */
    public function resolveSlotItems(array $items, int $userId, string $slot = '', array $currentUser = []): array
    {
        return $this->buildSlotItemsWithAssets($items, $userId, $slot, $currentUser)['items'];
    }

    /**
     * 构建可见槽位项及其资源
     *
     * @param array<int, array<string, mixed>> $items
     * @param int $userId
     * @param string $slot
     * @param array<string, string> $currentUser
     * @return array<string, mixed>
     */
    private function buildSlotItemsWithAssets(array $items, int $userId, string $slot, array $currentUser = []): array
    {
        $resolved = [];
        $assets   = $this->emptyAssets();

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $permission = trim((string)($item['permission'] ?? ''));
            if ($permission !== '' && !$this->permissionService->hasPermission($userId, $permission)) {
                continue;
            }

            $type = strtolower(trim((string)($item['type'] ?? 'link')));
            if (!$this->isSlotTypeAllowed($slot, $type)) {
                continue;
            }

            $resolvedItem = match ($type) {
                'dropdown' => $this->resolveDropdownItem($item, $currentUser),
                'fragment' => $this->resolveFragmentItem($item, $currentUser),
                default    => $this->resolveLinkItem($item),
            };

            if ($resolvedItem === []) {
                continue;
            }

            $resolved[] = $resolvedItem;
            $assets     = $this->mergeAssetDeclarations($assets, (array)($item['assets'] ?? []));
        }

        usort($resolved, static function (array $a, array $b): int {
            $sortComparison = ((int)$a['sort']) <=> ((int)$b['sort']);
            if ($sortComparison !== 0) {
                return $sortComparison;
            }

            return strcmp((string)$a['id'], (string)$b['id']);
        });

        return [
            'items'  => $resolved,
            'assets' => $assets,
        ];
    }

    /**
     * 解析 link 类型壳层项
     *
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    private function resolveLinkItem(array $item): array
    {
        $route = trim((string)($item['route'] ?? ''));
        $url   = trim((string)($item['url'] ?? ''));
        $href  = $route !== '' ? (string)dp_url($route) : $url;
        if ($href === '') {
            return [];
        }

        return [
            'id'         => (string)($item['id'] ?? ''),
            'type'       => 'link',
            'title'      => (string)($item['title'] ?? ''),
            'icon'       => (string)($item['icon'] ?? 'ti ti-link'),
            'url'        => $href,
            'route'      => $route,
            'sort'       => (int)($item['sort'] ?? 100),
            'permission' => trim((string)($item['permission'] ?? '')),
        ];
    }

    /**
     * 解析 dropdown 类型壳层项
     *
     * @param array<string, mixed> $item
     * @param array<string, string> $currentUser
     * @return array<string, mixed>
     */
    private function resolveDropdownItem(array $item, array $currentUser = []): array
    {
        $viewFile = trim((string)($item['__view_file'] ?? ''));
        if ($viewFile === '' || !is_file($viewFile)) {
            return [];
        }

        return [
            'id'         => (string)($item['id'] ?? ''),
            'type'       => 'dropdown',
            'title'      => (string)($item['title'] ?? ''),
            'icon'       => (string)($item['icon'] ?? 'ti ti-link'),
            'badge'      => trim((string)($item['badge'] ?? '')),
            'content_html'=> $this->renderSlotView($viewFile, $item, $currentUser),
            'view_file'  => $viewFile,
            'sort'       => (int)($item['sort'] ?? 100),
            'permission' => trim((string)($item['permission'] ?? '')),
        ];
    }

    /**
     * 解析 fragment 类型壳层项
     *
     * @param array<string, mixed> $item
     * @param array<string, string> $currentUser
     * @return array<string, mixed>
     */
    private function resolveFragmentItem(array $item, array $currentUser = []): array
    {
        $viewFile = trim((string)($item['__view_file'] ?? ''));
        if ($viewFile === '' || !is_file($viewFile)) {
            return [];
        }

        return [
            'id'           => (string)($item['id'] ?? ''),
            'type'         => 'fragment',
            'title'        => (string)($item['title'] ?? ''),
            'icon'         => (string)($item['icon'] ?? ''),
            'content_html' => $this->renderSlotView($viewFile, $item, $currentUser),
            'view_file'    => $viewFile,
            'sort'         => (int)($item['sort'] ?? 100),
            'permission'   => trim((string)($item['permission'] ?? '')),
        ];
    }

    /**
     * 合并壳层资源声明
     *
     * @param array<string, mixed> $base
     * @param array<string, mixed> $append
     * @return array<string, array<int, string>>
     */
    private function mergeAssetDeclarations(array $base, array $append): array
    {
        $result = [
            'css'  => array_values(array_filter((array)($base['css'] ?? []), 'is_string')),
            'js'   => array_values(array_filter((array)($base['js'] ?? []), 'is_string')),
            'init' => array_values(array_filter((array)($base['init'] ?? []), 'is_string')),
        ];

        foreach (['css', 'js', 'init'] as $bucket) {
            foreach ((array)($append[$bucket] ?? []) as $item) {
                if (!is_string($item)) {
                    continue;
                }

                $item = trim($item);
                if ($item === '' || in_array($item, $result[$bucket], true)) {
                    continue;
                }

                $result[$bucket][] = $item;
            }
        }

        return $result;
    }

    /**
     * 判断 slot/type 是否允许
     *
     * @param string $slot
     * @param string $type
     * @return bool
     */
    private function isSlotTypeAllowed(string $slot, string $type): bool
    {
        if ($slot === '') {
            return in_array($type, ['link', 'dropdown'], true);
        }

        $allowedTypes = PluginRegistry::ALLOWED_ADMIN_SLOT_TYPES[$slot] ?? [];
        return in_array($type, $allowedTypes, true);
    }

    /**
     * 获取当前插件注册中心
     *
     * 不能在构造函数里长期持有 PluginRegistry。
     * 后台壳层上下文服务可能在插件运行时装载之前被提前实例化，
     * 如果持有了旧实例，就会看到“插件已装载，但壳层 slot 仍为空”的现象。
     */
    private function getRegistry(): PluginRegistry
    {
        return app(PluginRegistry::class);
    }

    /**
     * 空壳层上下文
     *
     * @return array<string, mixed>
     */
    private function emptyContext(): array
    {
        return [
            'current_user'    => [],
            'topbar_slots'    => [],
            'user_menu_slots' => [],
            'assets'          => $this->emptyAssets(),
        ];
    }

    /**
     * 空资源声明
     *
     * @return array<string, array<int, string>>
     */
    private function emptyAssets(): array
    {
        return [
            'css'  => [],
            'js'   => [],
            'init' => [],
        ];
    }

    /**
     * 渲染壳层模板片段
     *
     * @param string $viewFile
     * @param array<string, mixed> $item
     * @param array<string, string> $currentUser
     * @return string
     */
    private function renderSlotView(string $viewFile, array $item, array $currentUser = []): string
    {
        return (string)app()->view->fetch($viewFile, [
            'item'         => $item,
            'current_user' => $currentUser,
        ]);
    }

    /**
     * 规范化后台用户上下文
     *
     * @param mixed $adminUser
     * @return array<string, mixed>
     */
    private function normalizeAdminUser(mixed $adminUser): array
    {
        if (is_array($adminUser)) {
            return $adminUser;
        }

        if ($adminUser instanceof Model) {
            return $adminUser->toArray();
        }

        if (is_object($adminUser) && method_exists($adminUser, 'toArray')) {
            $result = $adminUser->toArray();
            return is_array($result) ? $result : [];
        }

        return [];
    }
}
