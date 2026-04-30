<?php
declare(strict_types=1);

namespace app\common\plugin;

use InvalidArgumentException;

/**
 * 插件能力注册中心
 */
class PluginRegistry
{
    /**
     * 当前开放的后台工作台区域白名单
     */
    public const ALLOWED_ADMIN_WORKSPACE_REGIONS = [
        'top',
        'main_left',
    ];

    /**
     * 当前开放的后台工作台卡片类型白名单
     *
     * @var array<int, string>
     */
    public const ALLOWED_ADMIN_WORKSPACE_CARD_TYPES = [
        'metric',
        'links',
        'list',
        'view',
    ];

    /**
     * 当前开放的后台槽位白名单
     */
    public const ALLOWED_ADMIN_SLOTS = [
        'topbar.tools',
        'user.menu',
    ];

    /**
     * 当前开放的后台槽位类型白名单
     *
     * @var array<string, array<int, string>>
     */
    public const ALLOWED_ADMIN_SLOT_TYPES = [
        'topbar.tools' => ['link', 'dropdown', 'fragment'],
        'user.menu'    => ['link'],
    ];

    /**
     * @var array<string, mixed>
     */
    private array $items = [
        'form_items'         => [],
        'table_items'        => [],
        'chart_types'        => [],
        'chart_maps'         => [],
        'upload_drivers'     => [],
        'commands'           => [],
        'middlewares'        => [],
        'ai_adapters'        => [],
        'component_handlers' => [],
        'admin_slots'        => [],
        'admin_workspace_cards' => [],
    ];

    /**
     * 注册表单项
     * @param string $type
     * @param string $class
     * @return void
     */
    public function registerFormItem(string $type, string $class): void
    {
        $this->register('form_items', $type, $class);
    }

    /**
     * 获取表单项类
     * @param string $type
     * @return string
     */
    public function getFormItemClass(string $type): string
    {
        return $this->get('form_items', $type);
    }

    /**
     * 注册表格列
     * @param string $type
     * @param string $class
     * @return void
     */
    public function registerTableItem(string $type, string $class): void
    {
        $this->register('table_items', $type, $class);
    }

    /**
     * 获取表格列类
     * @param string $type
     * @return string
     */
    public function getTableItemClass(string $type): string
    {
        return $this->get('table_items', $type);
    }

    /**
     * 注册图表类型
     * @param string $type
     * @param string $class
     * @return void
     */
    public function registerChartType(string $type, string $class): void
    {
        $this->register('chart_types', $type, $class);
    }

    /**
     * 获取图表类型类
     * @param string $type
     * @return string
     */
    public function getChartTypeClass(string $type): string
    {
        return $this->get('chart_types', $type);
    }

    /**
     * 注册图表地图 Provider
     * @param string $mapKey
     * @param string $class
     * @return void
     */
    public function registerChartMap(string $mapKey, string $class): void
    {
        $this->register('chart_maps', $mapKey, $class);
    }

    /**
     * 获取图表地图 Provider
     * @param string $mapKey
     * @return string
     */
    public function getChartMapClass(string $mapKey): string
    {
        return $this->get('chart_maps', $mapKey);
    }

    /**
     * 注册上传驱动
     * @param string $name
     * @param string $class
     * @return void
     */
    public function registerUploadDriver(string $name, string $class): void
    {
        $name = dp_normalize_extension_name($name);
        $this->assertRegisterable('upload_drivers', $name, $class);
        $this->items['upload_drivers'][$name] = $class;
    }

    /**
     * 获取上传驱动类
     * @param string $name
     * @return string
     */
    public function getUploadDriverClass(string $name): string
    {
        $name = dp_normalize_extension_name($name);
        return $this->items['upload_drivers'][$name] ?? '';
    }

    /**
     * 注册插件命令
     * @param string $name
     * @param string $class
     * @return void
     */
    public function registerCommand(string $name, string $class): void
    {
        $name = strtolower(trim($name));
        $this->assertRegisterable('commands', $name, $class);
        $this->items['commands'][$name] = $class;
    }

    /**
     * 获取全部命令类
     * @return array<int, string>
     */
    public function getCommandClasses(): array
    {
        return array_values($this->items['commands']);
    }

    /**
     * 注册中间件别名
     * @param string $alias
     * @param string $class
     * @return void
     */
    public function registerMiddleware(string $alias, string $class): void
    {
        $alias = dp_normalize_extension_name($alias);
        $this->assertRegisterable('middlewares', $alias, $class);
        $this->items['middlewares'][$alias] = $class;
    }

    /**
     * 获取中间件别名映射
     * @return array<string, string>
     */
    public function getMiddlewares(): array
    {
        return $this->items['middlewares'];
    }

    /**
     * 注册 AI 适配器
     * @param string $name
     * @param string $class
     * @return void
     */
    public function registerAiAdapter(string $name, string $class): void
    {
        $name = dp_normalize_extension_name($name);
        $this->assertRegisterable('ai_adapters', $name, $class);
        $this->items['ai_adapters'][$name] = $class;
    }

    /**
     * 获取 AI 适配器类
     * @param string $name
     * @return string
     */
    public function getAiAdapterClass(string $name): string
    {
        $name = dp_normalize_extension_name($name);
        return $this->items['ai_adapters'][$name] ?? '';
    }

    /**
     * 注册组件处理类
     * @param string $type
     * @param string $component
     * @param string $class
     * @return void
     */
    public function registerComponentHandler(string $type, string $component, string $class): void
    {
        $type      = dp_to_snake_case($type);
        $component = dp_normalize_extension_path($component);
        $key       = $this->buildComponentKey($type, $component);

        $this->assertRegisterable('component_handlers', $key, $class);
        $this->items['component_handlers'][$key] = $class;
    }

    /**
     * 获取组件处理类
     * @param string $type
     * @param string $component
     * @return string
     */
    public function getComponentHandlerClass(string $type, string $component): string
    {
        $key = $this->buildComponentKey(dp_to_snake_case($type), dp_normalize_extension_path($component));
        return $this->items['component_handlers'][$key] ?? '';
    }

    /**
     * 注册后台槽位入口项
     * @param string $slot
     * @param array<string, mixed> $item
     * @return void
     */
    public function registerAdminSlot(string $slot, array $item): void
    {
        $slot = $this->normalizeAdminSlot($slot);
        $item = $this->normalizeAdminSlotItem($slot, $item);
        $id   = $item['id'];

        if (!isset($this->items['admin_slots'][$slot])) {
            $this->items['admin_slots'][$slot] = [];
        }

        $existing = $this->items['admin_slots'][$slot][$id] ?? null;
        if (is_array($existing) && $existing !== $item) {
            throw new InvalidArgumentException('后台槽位入口冲突: ' . $slot . '#' . $id);
        }

        $this->items['admin_slots'][$slot][$id] = $item;
    }

    /**
     * 获取指定后台槽位入口项
     * @param string $slot
     * @return array<int, array<string, mixed>>
     */
    public function getAdminSlotItems(string $slot): array
    {
        $slot = $this->normalizeAdminSlot($slot);
        return array_values($this->items['admin_slots'][$slot] ?? []);
    }

    /**
     * 获取全部后台槽位入口项
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function getAdminSlots(): array
    {
        $result = [];
        foreach ($this->items['admin_slots'] as $slot => $items) {
            $result[$slot] = array_values(is_array($items) ? $items : []);
        }

        return $result;
    }

    /**
     * 注册后台工作台卡片
     *
     * @param array<string, mixed> $card
     * @return void
     */
    public function registerAdminWorkspaceCard(array $card): void
    {
        $card = $this->normalizeAdminWorkspaceCard($card);
        $id   = $card['id'];

        $existing = $this->items['admin_workspace_cards'][$id] ?? null;
        if (is_array($existing) && $existing !== $card) {
            throw new InvalidArgumentException('后台工作台卡片冲突: ' . $id);
        }

        $this->items['admin_workspace_cards'][$id] = $card;
    }

    /**
     * 获取全部后台工作台卡片
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAdminWorkspaceCards(): array
    {
        return array_values($this->items['admin_workspace_cards'] ?? []);
    }

    /**
     * 获取全部已注册能力
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * 通用注册
     * @param string $bucket
     * @param string $name
     * @param string $class
     * @return void
     */
    private function register(string $bucket, string $name, string $class): void
    {
        $name = dp_normalize_extension_path($name);
        $this->assertRegisterable($bucket, $name, $class);
        $this->items[$bucket][$name] = $class;
    }

    /**
     * 通用获取
     * @param string $bucket
     * @param string $name
     * @return string
     */
    private function get(string $bucket, string $name): string
    {
        $name = dp_normalize_extension_path($name);
        return $this->items[$bucket][$name] ?? '';
    }

    /**
     * 构建组件键
     * @param string $type
     * @param string $component
     * @return string
     */
    private function buildComponentKey(string $type, string $component): string
    {
        return $type . ':' . $component;
    }

    /**
     * 规范化后台槽位名
     * @param string $slot
     * @return string
     */
    private function normalizeAdminSlot(string $slot): string
    {
        $slot = strtolower(trim($slot));
        if ($slot === '') {
            throw new InvalidArgumentException('后台槽位名不能为空');
        }

        if (!in_array($slot, self::ALLOWED_ADMIN_SLOTS, true)) {
            throw new InvalidArgumentException('未开放的后台槽位: ' . $slot);
        }

        return $slot;
    }

    /**
     * 规范化后台槽位入口项
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    private function normalizeAdminSlotItem(string $slot, array $item): array
    {
        $id         = strtolower(trim((string)($item['id'] ?? '')));
        $title      = trim((string)($item['title'] ?? ''));
        $icon       = trim((string)($item['icon'] ?? ''));
        $type       = strtolower(trim((string)($item['type'] ?? 'link')));
        $route      = trim((string)($item['route'] ?? ''));
        $url        = trim((string)($item['url'] ?? ''));
        $view       = trim((string)($item['view'] ?? ''));
        $sort       = isset($item['sort']) ? (int)$item['sort'] : 100;
        $permission = trim((string)($item['permission'] ?? ''));
        $badge      = trim((string)($item['badge'] ?? ''));
        $plugin     = dp_normalize_extension_path((string)($item['__plugin'] ?? ''));
        $assets     = $this->normalizeAdminSlotAssets((array)($item['assets'] ?? []));

        if ($id === '') {
            throw new InvalidArgumentException('后台槽位入口 id 不能为空');
        }

        if ($type !== 'fragment' && $title === '') {
            throw new InvalidArgumentException('后台槽位入口 title 不能为空');
        }

        if ($type !== 'fragment' && $icon === '') {
            throw new InvalidArgumentException('后台槽位入口 icon 不能为空');
        }

        if ($type === '') {
            throw new InvalidArgumentException('后台槽位入口 type 不能为空');
        }

        if (!in_array($type, self::ALLOWED_ADMIN_SLOT_TYPES[$slot] ?? [], true)) {
            throw new InvalidArgumentException('后台槽位不支持的组件类型: ' . $slot . '#' . $type);
        }

        $viewFile = '';
        if (in_array($type, ['dropdown', 'fragment'], true)) {
            if ($view === '') {
                throw new InvalidArgumentException('后台槽位 ' . $type . ' 组件必须提供 view');
            }

            if (str_contains($view, '..')) {
                throw new InvalidArgumentException('后台槽位 ' . $type . ' 视图路径非法');
            }

            if ($plugin === '') {
                throw new InvalidArgumentException('后台槽位 ' . $type . ' 组件必须声明来源插件');
            }

            $viewFile = dp_plugin_view_path($plugin, $view);
            if ($viewFile === '' || !is_file($viewFile)) {
                throw new InvalidArgumentException('后台槽位 ' . $type . ' 视图片段不存在: ' . $plugin . '/' . $view);
            }
        } elseif ($route === '' && $url === '') {
            throw new InvalidArgumentException('后台槽位入口必须提供 route 或 url');
        }

        return [
            'id'         => $id,
            'type'       => $type,
            'title'      => $title,
            'icon'       => $icon,
            'route'      => $route,
            'url'        => $url,
            'view'       => $view,
            'sort'       => $sort,
            'permission' => $permission,
            'badge'      => $badge,
            'assets'     => $assets,
            '__plugin'   => $plugin,
            '__view_file'=> $viewFile,
        ];
    }

    /**
     * 规范化后台槽位资源声明
     *
     * @param array<string, mixed> $assets
     * @return array<string, array<int, string>>
     */
    private function normalizeAdminSlotAssets(array $assets): array
    {
        $result = [
            'css'  => [],
            'js'   => [],
            'init' => [],
        ];

        foreach (['css', 'js', 'init'] as $bucket) {
            foreach ((array)($assets[$bucket] ?? []) as $item) {
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
     * 规范化后台工作台卡片
     *
     * @param array<string, mixed> $card
     * @return array<string, mixed>
     */
    private function normalizeAdminWorkspaceCard(array $card): array
    {
        $id         = strtolower(trim((string)($card['id'] ?? '')));
        $title      = trim((string)($card['title'] ?? ''));
        $region     = strtolower(trim((string)($card['region'] ?? '')));
        $type       = strtolower(trim((string)($card['type'] ?? 'metric')));
        $icon       = trim((string)($card['icon'] ?? 'ti ti-layout-grid'));
        $sort       = isset($card['sort']) ? (int)$card['sort'] : 100;
        $permission = trim((string)($card['permission'] ?? ''));
        $provider   = trim((string)($card['provider'] ?? ''));
        $view       = trim((string)($card['view'] ?? ''));
        $plugin     = dp_normalize_extension_path((string)($card['__plugin'] ?? ''));
        $span       = max(1, min(12, (int)($card['span'] ?? 12)));
        $assets     = $this->normalizeAdminSlotAssets((array)($card['assets'] ?? []));

        if ($id === '') {
            throw new InvalidArgumentException('后台工作台卡片 id 不能为空');
        }

        if ($title === '') {
            throw new InvalidArgumentException('后台工作台卡片 title 不能为空');
        }

        if (!in_array($region, self::ALLOWED_ADMIN_WORKSPACE_REGIONS, true)) {
            throw new InvalidArgumentException('未开放的工作台区域: ' . $region);
        }

        if (!in_array($type, self::ALLOWED_ADMIN_WORKSPACE_CARD_TYPES, true)) {
            throw new InvalidArgumentException('工作台卡片不支持的类型: ' . $type);
        }

        if ($type !== 'view' && $provider === '') {
            throw new InvalidArgumentException('非 view 工作台卡片必须提供 provider');
        }

        if ($type === 'view' && $view === '') {
            throw new InvalidArgumentException('view 工作台卡片必须提供 view');
        }

        $viewFile = '';
        if ($view !== '') {
            if (str_contains($view, '..')) {
                throw new InvalidArgumentException('后台工作台卡片视图路径非法');
            }

            if ($plugin === '') {
                throw new InvalidArgumentException('工作台 view 卡片必须声明来源插件');
            }

            $viewFile = dp_plugin_view_path($plugin, $view);
            if ($viewFile === '' || !is_file($viewFile)) {
                throw new InvalidArgumentException('工作台 view 卡片模板不存在: ' . $plugin . '/' . $view);
            }
        }

        return [
            'id'          => $id,
            'title'       => $title,
            'region'      => $region,
            'type'        => $type,
            'icon'        => $icon,
            'sort'        => $sort,
            'permission'  => $permission,
            'provider'    => $provider,
            'view'        => $view,
            'span'        => $span,
            'assets'      => $assets,
            '__plugin'    => $plugin,
            '__view_file' => $viewFile,
        ];
    }

    /**
     * 校验注册项
     * @param string $bucket
     * @param string $name
     * @param string $class
     * @return void
     */
    private function assertRegisterable(string $bucket, string $name, string $class): void
    {
        if ($name === '') {
            throw new InvalidArgumentException('插件能力标识不能为空');
        }

        if ($class === '') {
            throw new InvalidArgumentException('插件能力类名不能为空');
        }

        if (isset($this->items[$bucket][$name]) && $this->items[$bucket][$name] !== $class) {
            throw new InvalidArgumentException('插件能力冲突: ' . $name);
        }
    }
}
