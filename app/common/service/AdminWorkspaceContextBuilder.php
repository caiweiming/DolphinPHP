<?php
declare(strict_types=1);

namespace app\common\service;

use app\admin\model\File;
use app\common\interface\PermissionService;
use app\common\model\App;
use app\common\model\Permission;
use app\common\model\User;
use app\common\plugin\PluginDescriptor;
use app\common\plugin\PluginRegistry;
use app\common\plugin\PluginRepository;
use think\facade\Db;
use Throwable;

/**
 * 后台工作台上下文构建服务
 *
 * 统一聚合顶部摘要、业务区、系统区与插件卡片。
 */
final class AdminWorkspaceContextBuilder
{
    public function __construct(
        private readonly PermissionService $permissionService,
        private readonly PluginRegistry $pluginRegistry,
        private readonly PluginRepository $pluginRepository,
        private readonly AdminWorkspaceQuickLinkService $quickLinkService
    ) {
    }

    /**
     * 构建后台工作台上下文
     *
     * @param int $userId
     * @param array<string, mixed> $navigationContext
     * @return array<string, mixed>
     */
    public function buildContext(int $userId, array $navigationContext = []): array
    {
        $healthStatus = $this->buildHealthStatus();
        $summaryCards = [
            $this->metricCard('workspace.users', '用户总数', 'ti ti-users', $this->safeCount(static fn(): int => (int)User::count()), 3, 10),
            $this->metricCard('workspace.files', '附件总数', 'ti ti-files', $this->safeCount(static fn(): int => (int)File::count()), 3, 20),
            $this->metricCard('workspace.apps', '应用总数', 'ti ti-apps', $this->safeCount(static fn(): int => (int)App::count()), 3, 30),
            $this->metricCard('workspace.plugins', '插件数量', 'ti ti-plug-connected', $this->resolveInstalledPluginCount(), 3, 40),
        ];

        $cards = array_merge(
            $this->buildBuiltinCards($userId, $navigationContext),
            $this->resolvePluginCards($userId)
        );

        return [
            'summaryCards' => $summaryCards,
            'regions'      => $this->groupCardsByRegion($cards),
            'healthStatus' => $healthStatus,
            'meta'         => [
                'generated_at' => date('Y-m-d H:i:s'),
                'user_id'      => $userId,
            ],
        ];
    }

    /**
     * 解析插件工作台卡片
     *
     * @param int $userId
     * @return array<int, array<string, mixed>>
     */
    private function resolvePluginCards(int $userId): array
    {
        $resolved = [];

        foreach ($this->pluginRegistry->getAdminWorkspaceCards() as $card) {
            $permission = trim((string)($card['permission'] ?? ''));
            if ($permission !== '' && !$this->permissionService->hasPermission($userId, $permission)) {
                continue;
            }

            try {
                $payload = $this->resolveCardPayload($card);
                dp_register_page_assets((array)($card['assets'] ?? []));

                $resolved[] = array_merge($card, $payload, [
                    'status' => (string)($card['status'] ?? 'ready'),
                ]);
            } catch (Throwable) {
                $resolved[] = array_merge($card, [
                    'status'      => 'warning',
                    'description' => (string)($card['title'] ?? '卡片') . ' 暂时不可用',
                    'items'       => [],
                ]);
            }
        }

        return $resolved;
    }

    /**
     * 解析卡片数据
     *
     * @param array<string, mixed> $card
     * @return array<string, mixed>
     */
    private function resolveCardPayload(array $card): array
    {
        if (($card['type'] ?? '') === 'view') {
            $viewFile = (string)($card['__view_file'] ?? '');
            if ($viewFile === '' || !is_file($viewFile)) {
                throw new \RuntimeException('工作台视图卡片模板不存在');
            }

            return [
                'content_html' => (string)app()->view->fetch($viewFile, ['card' => $card]),
            ];
        }

        $provider = trim((string)($card['provider'] ?? ''));
        if ($provider === '') {
            return [];
        }

        $instance = app()->make($provider);
        if (!method_exists($instance, 'build')) {
            return [];
        }

        $payload = $instance->build($card);
        return is_array($payload) ? $payload : [];
    }

    /**
     * 构建内置卡片
     *
     * @param int $userId
     * @param array<string, mixed> $navigationContext
     * @return array<int, array<string, mixed>>
     */
    private function buildBuiltinCards(int $userId, array $navigationContext): array
    {
        $quickLinkState = $this->quickLinkService->getWorkspaceCardState($userId, $navigationContext);

        $environmentItems = [
            ['title' => 'DolphinPHP版本：' . $this->resolveDolphinVersion()],
            ['title' => 'ThinkPHP版本：' . $this->resolveThinkPhpVersion()],
            ['title' => '操作系统：' . $this->resolveOperatingSystem()],
            ['title' => '中间件：' . $this->resolveServerSoftware()],
            ['title' => 'MYSQL版本：' . $this->resolveDatabaseVersion()],
            ['title' => 'PHP版本：' . PHP_VERSION],
            ['title' => '上传限制：' . $this->resolveUploadLimit()],
        ];

        return [
            [
                'id'          => 'workspace.quick_links',
                'title'       => '快捷入口',
                'region'      => 'main_left',
                'type'        => 'quick_links',
                'icon'        => 'ti ti-bolt',
                'sort'        => 10,
                'span'        => 6,
                'status'      => 'ready',
                'links'       => $quickLinkState['links'],
                'candidates'  => $quickLinkState['candidates'],
                'count'       => $quickLinkState['count'],
                'limit'       => $quickLinkState['limit'],
                'is_empty'    => $quickLinkState['is_empty'],
            ],
            [
                'id'          => 'workspace.environment',
                'title'       => '运行环境',
                'region'      => 'main_left',
                'type'        => 'list',
                'icon'        => 'ti ti-server',
                'sort'        => 40,
                'span'        => 6,
                'status'      => 'ready',
                'items'       => $environmentItems,
            ],
        ];
    }

    /**
     * 获取已安装插件数量
     *
     * @return int
     */
    private function resolveInstalledPluginCount(): int
    {
        return $this->safeCount(function (): int {
            return count(array_filter(
                $this->pluginRepository->all(),
                static fn(PluginDescriptor $descriptor): bool => $descriptor->isInstalled()
            ));
        });
    }

    /**
     * 按区域分组并排序卡片
     *
     * @param array<int, array<string, mixed>> $cards
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function groupCardsByRegion(array $cards): array
    {
        $regions = [
            'top'        => [],
            'main_left'  => [],
        ];

        foreach ($cards as $card) {
            $region = (string)($card['region'] ?? '');
            if (!array_key_exists($region, $regions)) {
                continue;
            }

            $regions[$region][] = $card;
        }

        foreach ($regions as &$items) {
            usort($items, static function (array $left, array $right): int {
                $sortComparison = ((int)($left['sort'] ?? 100)) <=> ((int)($right['sort'] ?? 100));
                if ($sortComparison !== 0) {
                    return $sortComparison;
                }

                return strcmp((string)($left['id'] ?? ''), (string)($right['id'] ?? ''));
            });
        }
        unset($items);

        return $regions;
    }

    /**
     * 构建摘要统计卡片
     *
     * @param string $id
     * @param string $title
     * @param string $icon
     * @param int $value
     * @param int $span
     * @param int $sort
     * @return array<string, mixed>
     */
    private function metricCard(string $id, string $title, string $icon, int $value, int $span, int $sort): array
    {
        return [
            'id'          => $id,
            'title'       => $title,
            'type'        => 'metric',
            'icon'        => $icon,
            'span'        => $span,
            'sort'        => $sort,
            'status'      => 'ready',
            'value'       => $value,
            'description' => '',
        ];
    }

    /**
     * 构建健康状态
     *
     * @return array<string, mixed>
     */
    private function buildHealthStatus(): array
    {
        $uploadsPath = rtrim((string)public_path(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'uploads';
        $runtimePath = (string)runtime_path();

        $checks = [
            [
                'label'  => '缓存驱动',
                'status' => config('cache.default', 'file') !== '' ? 'ok' : 'warning',
            ],
            [
                'label'  => '队列连接',
                'status' => config('queue.default', 'sync') !== '' ? 'ok' : 'warning',
            ],
            [
                'label'  => '上传目录',
                'status' => is_dir($uploadsPath) && is_writable($uploadsPath) ? 'ok' : 'warning',
            ],
            [
                'label'  => '运行目录',
                'status' => is_dir($runtimePath) && is_writable($runtimePath) ? 'ok' : 'warning',
            ],
            [
                'label'  => '权限模型',
                'status' => $this->safeCount(static fn(): int => (int)Permission::count()) >= 0 ? 'ok' : 'warning',
            ],
        ];

        $status = 'ready';
        foreach ($checks as $check) {
            if ((string)$check['status'] !== 'ok') {
                $status = 'warning';
                break;
            }
        }

        return [
            'status'  => $status,
            'summary' => $status === 'ready' ? '核心检查通过' : '存在需要关注的检查项',
            'checks'  => $checks,
        ];
    }

    /**
     * 安全统计数量
     *
     * @param callable():int $resolver
     * @return int
     */
    private function safeCount(callable $resolver): int
    {
        try {
            return (int)$resolver();
        } catch (Throwable) {
            return 0;
        }
    }

    /**
     * 安全读取记录列表
     *
     * @param callable():array<int, array<string, mixed>> $resolver
     * @return array<int, array<string, mixed>>
     */
    private function safeRecords(callable $resolver): array
    {
        try {
            $result = $resolver();
            return is_array($result) ? $result : [];
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * 安全解析数据库版本
     *
     * @return string
     */
    private function resolveDatabaseVersion(): string
    {
        try {
            $rows = Db::query('SELECT VERSION() AS version');
            $version = trim((string)($rows[0]['version'] ?? ''));

            return $version !== '' ? $version : '未知';
        } catch (Throwable) {
            return '未知';
        }
    }

    /**
     * 解析 DolphinPHP 版本
     */
    private function resolveDolphinVersion(): string
    {
        $version = trim((string)config('dolphin.version', ''));

        return $version !== '' ? $version : '未知';
    }

    /**
     * 解析 ThinkPHP 版本
     */
    private function resolveThinkPhpVersion(): string
    {
        $version = ltrim(trim((string)app()->version()), 'vV');

        return $version !== '' ? $version : '未知';
    }

    /**
     * 解析操作系统
     */
    private function resolveOperatingSystem(): string
    {
        $name = php_uname('s');
        $release = php_uname('r');
        $version = trim($name . ' ' . $release);

        return $version !== '' ? $version : '未知';
    }

    /**
     * 解析 Web 中间件
     */
    private function resolveServerSoftware(): string
    {
        $serverSoftware = trim((string)($_SERVER['SERVER_SOFTWARE'] ?? getenv('SERVER_SOFTWARE') ?: ''));

        return $serverSoftware !== '' ? $serverSoftware : '未知';
    }

    /**
     * 解析上传限制
     */
    private function resolveUploadLimit(): string
    {
        $uploadMaxFilesize = trim((string)ini_get('upload_max_filesize'));
        $postMaxSize = trim((string)ini_get('post_max_size'));

        if ($uploadMaxFilesize === '' && $postMaxSize === '') {
            return '未知';
        }

        if ($uploadMaxFilesize === '') {
            return $postMaxSize;
        }

        if ($postMaxSize === '' || $postMaxSize === $uploadMaxFilesize) {
            return $uploadMaxFilesize;
        }

        return $uploadMaxFilesize . '（POST：' . $postMaxSize . '）';
    }
}
