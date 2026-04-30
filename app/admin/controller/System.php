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

namespace app\admin\controller;

use app\common\attribute\Permission;
use app\common\service\AdminCacheService;
use app\common\service\AppService;
use app\common\service\ConfigService;
use Exception;
use think\exception\HttpResponseException;
use think\response\Json;
use Throwable;

/**
 * 系统设置控制器
 */
#[Permission('系统设置', icon: 'ti ti-settings', sort: 10)]
class System extends Auth
{
    /**
     * 配置服务
     * @var ConfigService
     */
    protected ConfigService $configService;

    /**
     * 应用服务
     * @var AppService
     */
    protected AppService $appService;

    /**
     * 初始化
     * @return void
     * @throws Throwable
     */
    protected function initialize(): void
    {
        parent::initialize();
        $this->configService = app(ConfigService::class);
        $this->appService    = app(AppService::class);
        $this->appService->syncInstalledApps();
    }

    /**
     * 系统设置页
     * @return string|Json
     * @throws Exception|Throwable
     */
    public function index(): string|Json
    {
        $this->requireSuperAdmin('系统设置仅超级管理员可访问');

        $currentApp = $this->resolveCurrentApp();

        if (!$currentApp) {
            $this->buildEmptyState();
            $this->page->row($this->form);
            return $this->fetch();
        }

        $dynamicGroups  = $this->configService->getEnabledConfigsByGroup((string)$currentApp['name']);
        $dynamicMap     = $this->configService->getConfigMap(true, (string)$currentApp['name']);
        $declaredGroups = $this->appService->getDeclaredSettingsByGroup((string)$currentApp['name'], array_keys($dynamicMap));
        $groups         = $this->mergeGroups($dynamicGroups, $declaredGroups);

        if ($this->request->isPost()) {
            $submitted       = $this->request->post('config/a', []);
            $dynamicRecords  = $this->flattenGroups($dynamicGroups);
            $declaredRecords = $this->flattenGroups($declaredGroups);

            try {
                $changes = array_merge(
                    $this->configService->saveValues($submitted, $dynamicRecords),
                    $this->appService->saveDeclaredSettings((string)$currentApp['name'], $submitted, $declaredRecords)
                );

                if ($changes === []) {
                    $this->success('没有检测到变更');
                }

                dp_log_user_action('保存系统设置', [
                    'app'           => (string)$currentApp['name'],
                    'app_title'     => (string)$currentApp['title'],
                    'changed_count' => count($changes),
                    'changes'       => $changes,
                ]);
            } catch (Exception $e) {
                dp_log_exception($e, '保存系统设置');
                $this->error($e->getMessage());
            }

            $this->success('保存成功');
        }

        $this->buildForm($groups);
        $this->page->tabs($this->buildAppTabs($currentApp, $this->form), [
            'id'       => 'admin-system-app-tabs',
            'active'   => (string)$currentApp['name'],
            'remember' => true,
        ]);

        return $this->fetch();
    }

    /**
     * 安全清理缓存
     * @return void
     * @throws Throwable
     */
    #[Permission('清空缓存', icon: 'ti ti-broom')]
    public function clearCache(): void
    {
        $this->checkPermission('admin.system.clear_cache');

        try {
            $result = app(AdminCacheService::class)->clearSafeCache();

            try {
                dp_log_user_action('清空缓存', [
                    'scope'         => (string)($result['scope'] ?? 'safe'),
                    'removed_count' => (int)($result['removed_count'] ?? 0),
                ]);
            } catch (Throwable) {
            }

            $this->success('缓存清理成功');
        } catch (HttpResponseException $e) {
            throw $e;
        } catch (Throwable $e) {
            try {
                dp_log_exception($e, '清空缓存');
                dp_log_user_action('清空缓存失败', [
                    'scope' => 'safe',
                    'error' => $e->getMessage(),
                ], 'error');
            } catch (Throwable) {
            }
            $this->error($e->getMessage() !== '' ? $e->getMessage() : '缓存清理失败');
        }
    }

    /**
     * 构建动态表单
     * @param array $groups
     * @return void
     * @throws Exception
     */
    private function buildForm(array $groups): void
    {
        if ($groups === []) {
            $this->form
                ->footer(false)
                ->items([
                    [
                        'type'  => 'html',
                        'name'  => '__empty_notice__',
                        'label' => '暂无配置',
                        'value' => '<div class="alert alert-info mb-0">当前应用尚未接入任何可渲染设置，请先在“配置管理”中新增动态配置，或在应用目录下声明 `settings.php`。</div>',
                    ],
                ]);
            return;
        }

        if (count($groups) === 1) {
            $items = current($groups) ?: [];
            $this->form->items(array_map(
                fn(array $record): array => $this->configService->buildFormItem($record),
                $items
            ));
            return;
        }

        $tabs = [];
        foreach ($groups as $group => $items) {
            $tabs[] = [
                'title'   => $group,
                'content' => array_map(
                    fn(array $record): array => $this->configService->buildFormItem($record),
                    $items
                ),
            ];
        }

        $this->form->items([
            [
                'type'    => 'tabs',
                'name'    => 'system_groups',
                'label'   => '配置分组',
                'options' => $tabs,
            ],
        ])
        ->header(false);
    }

    /**
     * 构建无设置可用时的空态
     * @return void
     * @throws Exception
     */
    private function buildEmptyState(): void
    {
        $this->form
            ->footer(false)
            ->items([
                [
                    'type'  => 'html',
                    'name'  => '__empty_system_apps__',
                    'label' => '暂无设置',
                    'value' => '<div class="alert alert-info mb-0">当前没有任何应用接入设置中心，请先在应用目录中声明 `settings.php` 或添加动态配置定义。</div>',
                ],
            ]);
    }

    /**
     * 构建应用切换 Tabs
     * @param array $currentApp
     * @param mixed $currentContent
     * @return array
     */
    private function buildAppTabs(array $currentApp, mixed $currentContent = ''): array
    {
        $tabs = [];

        foreach ($this->appService->getApps(true) as $app) {
            if (empty($app['has_settings'])) {
                continue;
            }

            $name = (string)($app['name'] ?? '');
            if ($name === '') {
                continue;
            }

            $tabs[$name] = [
                'icon'  => $this->buildAppTabIcon($app),
                'title' => (string)($app['title'] ?? $name),
                'url'   => dp_url('index', ['app' => $name]),
            ];

            if ($name === (string)($currentApp['name'] ?? '')) {
                unset($tabs[$name]['url']);
                $currentContent->header(false);
                $tabs[$name]['content'] = $currentContent;
            }
        }

        return $tabs;
    }

    /**
     * 构建应用 Tab 图标
     * @param array $app
     * @return string
     */
    private function buildAppTabIcon(array $app): string
    {
        $icon = trim((string)($app['icon'] ?? ''));
        if ($icon === '') {
            return '';
        }

        return '<i class="dp-icon ' . htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') . ' me-1"></i>';
    }

    /**
     * 解析当前可用应用
     * @return array|null
     */
    private function resolveCurrentApp(): ?array
    {
        $requested = trim((string)$this->request->param('app', ''));

        foreach ($this->appService->getApps(true) as $app) {
            if (empty($app['has_settings'])) {
                continue;
            }

            if ($requested !== '' && (string)($app['name'] ?? '') === $requested) {
                return $app;
            }
        }

        foreach ($this->appService->getApps(true) as $app) {
            if (!empty($app['has_settings'])) {
                return $app;
            }
        }

        return null;
    }

    /**
     * 合并两类分组数据
     * @param array $dynamicGroups
     * @param array $declaredGroups
     * @return array
     */
    private function mergeGroups(array $dynamicGroups, array $declaredGroups): array
    {
        $merged = $dynamicGroups;

        foreach ($declaredGroups as $group => $items) {
            if (isset($merged[$group])) {
                $merged[$group] = array_merge($merged[$group], $items);
                continue;
            }

            $merged[$group] = $items;
        }

        return $merged;
    }

    /**
     * 打平分组记录
     * @param array $groups
     * @return array
     */
    private function flattenGroups(array $groups): array
    {
        $records = [];

        foreach ($groups as $items) {
            foreach ($items as $item) {
                $records[] = $item;
            }
        }

        return $records;
    }
}
