<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\AdminUserWorkspace;
use InvalidArgumentException;

/**
 * 后台工作台快捷入口服务
 */
final class AdminWorkspaceQuickLinkService
{
    private const TYPE = 'quick_links';
    private const DEFAULT_LIMIT = 12;

    /**
     * 获取用户快捷入口状态
     *
     * @param int $userId
     * @param array<int, array<string, mixed>> $menus
     * @return array<string, mixed>
     */
    public function getState(int $userId, array $menus): array
    {
        $catalog = $this->buildCatalog($menus);
        $keys = $this->loadKeys($userId);
        $links = [];

        foreach ($keys as $key) {
            if (isset($catalog[$key])) {
                $links[] = $catalog[$key];
            }
        }

        $selectedKeys = array_column($links, 'key');
        $candidates = array_values(array_filter(
            $catalog,
            static fn(array $item): bool => !in_array($item['key'], $selectedKeys, true)
        ));

        return [
            'links'      => array_values($links),
            'candidates' => $candidates,
            'count'      => count($links),
            'limit'      => $this->resolveLimit(),
            'is_empty'   => $links === [],
        ];
    }

    /**
     * 新增快捷入口
     *
     * @param int $userId
     * @param string $key
     * @param array<int, array<string, mixed>> $menus
     * @return array<string, mixed>
     */
    public function add(int $userId, string $key, array $menus): array
    {
        $keys = $this->loadKeys($userId);
        $keys[] = trim($key);

        return $this->persist($userId, $keys, $menus);
    }

    /**
     * 删除快捷入口
     *
     * @param int $userId
     * @param string $key
     * @param array<int, array<string, mixed>> $menus
     * @return array<string, mixed>
     */
    public function remove(int $userId, string $key, array $menus): array
    {
        $target = trim($key);
        $keys = array_values(array_filter(
            $this->loadKeys($userId),
            static fn(string $item): bool => $item !== $target
        ));

        return $this->persist($userId, $keys, $menus);
    }

    /**
     * 清空快捷入口
     *
     * @param int $userId
     * @param array<int, array<string, mixed>> $menus
     * @return array<string, mixed>
     */
    public function clear(int $userId, array $menus): array
    {
        AdminUserWorkspace::where('user_id', $userId)
            ->where('type', self::TYPE)
            ->delete();

        return $this->getState($userId, $menus);
    }

    /**
     * 保存排序
     *
     * @param int $userId
     * @param array<int, mixed> $keys
     * @param array<int, array<string, mixed>> $menus
     * @return array<string, mixed>
     */
    public function saveOrder(int $userId, array $keys, array $menus): array
    {
        return $this->persist($userId, $keys, $menus);
    }

    /**
     * 获取工作台卡片状态
     *
     * @param int $userId
     * @param array<string, mixed> $navigationContext
     * @return array<string, mixed>
     */
    public function getWorkspaceCardState(int $userId, array $navigationContext): array
    {
        return $this->getState($userId, (array)($navigationContext['searchableMenus'] ?? []));
    }

    /**
     * 持久化快捷入口
     *
     * @param int $userId
     * @param array<int, mixed> $keys
     * @param array<int, array<string, mixed>> $menus
     * @return array<string, mixed>
     */
    private function persist(int $userId, array $keys, array $menus): array
    {
        $catalog = $this->buildCatalog($menus);
        $normalized = [];
        $limit = $this->resolveLimit();

        foreach ($keys as $key) {
            $key = trim((string)$key);
            if ($key === '' || isset($normalized[$key]) || !isset($catalog[$key])) {
                continue;
            }

            $normalized[$key] = $key;
        }

        if (count($normalized) > $limit) {
            throw new InvalidArgumentException(sprintf('快捷入口最多只能保存 %d 个', $limit));
        }

        $payload = json_encode(array_values($normalized), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $record = AdminUserWorkspace::where('user_id', $userId)
            ->where('type', self::TYPE)
            ->find();

        if ($record === null) {
            AdminUserWorkspace::create([
                'user_id'    => $userId,
                'type'       => self::TYPE,
                'items_json' => $payload,
            ]);
        } else {
            $record->save([
                'items_json' => $payload,
            ]);
        }

        return $this->getState($userId, $menus);
    }

    /**
     * 读取已保存的快捷入口键
     *
     * @param int $userId
     * @return array<int, string>
     */
    private function loadKeys(int $userId): array
    {
        $record = AdminUserWorkspace::where('user_id', $userId)
            ->where('type', self::TYPE)
            ->find();

        if ($record === null) {
            return [];
        }

        $decoded = json_decode((string)$record->getAttr('items_json'), true);
        if (!is_array($decoded)) {
            return [];
        }

        return array_values(array_filter(array_map(static fn($item): string => trim((string)$item), $decoded)));
    }

    /**
     * 解析快捷入口数量上限
     */
    private function resolveLimit(): int
    {
        $configuredLimit = (int)config('system.workspace.quick_links.max_links', self::DEFAULT_LIMIT);

        return $configuredLimit > 0 ? $configuredLimit : self::DEFAULT_LIMIT;
    }

    /**
     * 构建可访问菜单目录
     *
     * @param array<int, array<string, mixed>> $menus
     * @return array<string, array<string, string>>
     */
    private function buildCatalog(array $menus): array
    {
        $catalog = [];

        foreach ($menus as $menu) {
            if (!is_array($menu) || !empty($menu['is_workspace'])) {
                continue;
            }

            $route = trim((string)($menu['route'] ?? ''));
            $url = trim((string)($menu['url'] ?? ''));
            if ($route === '' || $url === '' || $url === '#') {
                continue;
            }

            $catalog[$route] = [
                'key'          => $route,
                'title'        => trim((string)($menu['title'] ?? $menu['name'] ?? '未命名菜单')),
                'url'          => $url,
                'iframe_url'   => trim((string)($menu['iframe_url'] ?? $url)),
                'icon'         => trim((string)($menu['icon'] ?? 'ti ti-point')),
                'app_name'     => trim((string)($menu['app_name'] ?? '')),
                'is_workspace' => !empty($menu['is_workspace']),
            ];
        }

        return $catalog;
    }
}
