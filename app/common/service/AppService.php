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

use app\common\application\AppMetadataValidator;
use app\common\model\App as AppModel;
use Exception;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Cache;
use Throwable;

/**
 * 应用注册与声明型设置服务
 */
class AppService
{
    /**
     * 应用列表缓存键
     */
    private const CACHE_KEY_APPS = 'admin_app:registry';

    /**
     * 应用设置映射缓存前缀
     */
    private const CACHE_KEY_SETTING_MAP_PREFIX = 'admin_app:setting_map:';

    /**
     * 缓存时间
     */
    private const CACHE_TTL = 600;

    /**
     * 应用模型
     * @var AppModel
     */
    protected AppModel $model;

    /**
     * 配置服务
     * @var ConfigService
     */
    protected ConfigService $configService;

    /**
     * 当前请求是否已完成同步
     * @var bool
     */
    protected bool $synced = false;

    /**
     * @param AppModel|null $model
     * @param ConfigService|null $configService
     */
    public function __construct(?AppModel $model = null, ?ConfigService $configService = null)
    {
        $this->model         = $model ?? new AppModel();
        $this->configService = $configService ?? app(ConfigService::class);
    }

    /**
     * 同步已安装应用到注册表
     * @return void
     */
    public function syncInstalledApps(): void
    {
        if ($this->synced) {
            return;
        }

        $discovered = $this->discoverApps();
        if ($discovered === []) {
            $this->synced = true;
            return;
        }

        try {
            $changed = false;

            foreach ($discovered as $appName => $meta) {
                /** @var AppModel|null $existing */
                $existing = $this->model->where('name', $appName)->find();

                if ($existing) {
                    $payload = $this->buildRefreshPayload($existing->toArray(), $meta);
                    if ($payload === []) {
                        continue;
                    }

                    $existing->save($payload);
                    $changed = true;
                    continue;
                }

                $this->model->create($this->buildInsertPayload($appName, $meta));
                $changed = true;
            }

            if ($changed) {
                $this->clearCache();
            }
        } catch (Throwable) {
        }

        $this->synced = true;
    }

    /**
     * 登记已导入的应用包
     * @param string $appName
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws Exception
     */
    public function registerImportedPackage(string $appName): void
    {
        $appName = trim($appName);
        if ($appName === '') {
            throw new Exception('应用包缺少有效的应用标识');
        }

        $definition = $this->loadAppMeta($appName);
        $manifest   = $this->loadAppManifest($appName);
        if ($definition === [] || $manifest === []) {
            throw new Exception('应用包元数据不完整，无法登记导入状态');
        }

        $payload = [
            'name'                          => $appName,
            'title'                         => trim((string)($definition['title'] ?? $appName)),
            'description'                   => trim((string)($definition['description'] ?? '')),
            'version'                       => trim((string)($definition['version'] ?? '')),
            'installed_version'             => '',
            'author'                        => trim((string)($definition['author'] ?? '')),
            'provider'                      => trim((string)($definition['provider'] ?? $definition['settings_provider'] ?? '')),
            'sort'                          => max(0, (int)($definition['sort'] ?? 0)),
            'status'                        => 0,
            'show_in_config'                => 0,
            'lifecycle_status'              => (string)config('app_package.lifecycle.imported', 'imported'),
            'distribution_protocol_version' => trim((string)($manifest['app_api_version'] ?? config('app_package.api_version', '1.0'))),
            'distribution_meta'             => $this->encodeJsonSafely($manifest),
            'last_operation'                => 'import',
            'last_error'                    => '',
            'disable_time'                  => time(),
        ];

        $icon = trim((string)($definition['icon'] ?? ''));
        if ($icon !== '') {
            $payload['icon'] = $icon;
        }

        /** @var AppModel|null $existing */
        $existing = $this->model->where('name', $appName)->find();
        if ($existing) {
            $existing->save($payload);
        } else {
            $payload['settings'] = '';
            $this->model->create($payload);
        }

        $this->clearCache($appName);
    }

    /**
     * 获取应用列表
     * @param bool $enabledOnly
     * @return array
     */
    public function getApps(bool $enabledOnly = false): array
    {
        $this->syncInstalledApps();
        $discovered = $this->discoverApps();
        $allowed    = array_fill_keys(array_keys($discovered), true);

        try {
            $apps = Cache::remember(self::CACHE_KEY_APPS, function () {
                return $this->model
                    ->order('sort', 'asc')
                    ->order('id', 'asc')
                    ->select()
                    ->toArray();
            }, self::CACHE_TTL);
        } catch (Throwable) {
            $apps = array_values($discovered);
        }

        $apps = array_values(array_filter($apps, function (array $app) use ($enabledOnly): bool {
            if ($enabledOnly && (int)($app['status'] ?? 1) !== 1) {
                return false;
            }

            return true;
        }));

        if ($allowed !== []) {
            $apps = array_values(array_filter($apps, static function (array $app) use ($allowed): bool {
                $name = (string)($app['name'] ?? '');
                return $name !== '' && isset($allowed[$name]);
            }));
        }

        foreach ($apps as &$app) {
            $app = $this->decorateAppRecord($app);
        }
        unset($app);

        return $apps;
    }

    /**
     * 获取允许进入 Config 的内置应用
     * @param bool $enabledOnly
     * @return array
     */
    public function getConfigApps(bool $enabledOnly = false): array
    {
        return array_values(array_filter($this->getApps($enabledOnly), static function (array $app): bool {
            return (int)($app['is_system'] ?? 0) === 1
                && (int)($app['show_in_config'] ?? 0) === 1;
        }));
    }

    /**
     * 获取应用选项
     * @param bool $enabledOnly
     * @param bool $onlyWithSettings
     * @return array
     */
    public function getAppOptions(bool $enabledOnly = false, bool $onlyWithSettings = false): array
    {
        $options = [];
        foreach ($this->getApps($enabledOnly) as $app) {
            if ($onlyWithSettings && empty($app['has_settings'])) {
                continue;
            }

            $name           = (string)($app['name'] ?? '');
            $options[$name] = (string)($app['title'] ?? $name);
        }

        return $options;
    }

    /**
     * 解析当前应用
     * @param string $app
     * @param bool $onlyWithSettings
     * @return array|null
     */
    public function resolveApp(string $app = '', bool $onlyWithSettings = false): ?array
    {
        $app  = trim($app);
        $apps = $this->getApps();

        foreach ($apps as $item) {
            if ($onlyWithSettings && empty($item['has_settings'])) {
                continue;
            }

            if ($app !== '' && (string)$item['name'] === $app) {
                return $item;
            }
        }

        if ($app !== '') {
            return null;
        }

        foreach ($apps as $item) {
            if ($onlyWithSettings && empty($item['has_settings'])) {
                continue;
            }

            return $item;
        }

        return null;
    }

    /**
     * 解析当前 Config 白名单应用
     * @param string $app
     * @return array|null
     */
    public function resolveConfigApp(string $app = ''): ?array
    {
        $app  = trim($app);
        $apps = $this->getConfigApps(true);

        foreach ($apps as $item) {
            if ($app !== '' && (string)$item['name'] === $app) {
                return $item;
            }
        }

        if ($app !== '') {
            return null;
        }

        return $apps[0] ?? null;
    }

    /**
     * 判断应用是否启用
     * @param string $app
     * @return bool
     */
    public function isAppEnabled(string $app): bool
    {
        $app = trim($app);
        if ($app === '') {
            return true;
        }

        $record = $this->resolveApp($app);
        if (!$record) {
            return true;
        }

        return (int)($record['status'] ?? 1) === 1;
    }

    /**
     * 判断应用是否处于已安装生命周期
     * @param string $app
     * @return bool
     */
    public function isAppInstalled(string $app): bool
    {
        $record = $this->resolveApp($app);
        if (!$record) {
            return true;
        }

        return $this->isInstalledRecord($record);
    }

    /**
     * 判断应用是否允许对外访问
     * @param string $app
     * @return bool
     */
    public function isAppAccessible(string $app): bool
    {
        $app = trim($app);
        if ($app === '') {
            return true;
        }

        $record = $this->resolveApp($app);
        if (!$record) {
            return true;
        }

        return $this->isAccessibleRecord($record);
    }

    /**
     * 判断路由是否属于可访问应用
     * @param string $route
     * @return bool
     */
    public function isRouteAccessible(string $route): bool
    {
        $record = $this->resolveAppByRoute($route);
        if (!$record) {
            return true;
        }

        return $this->isAccessibleRecord($record);
    }

    /**
     * 判断权限是否属于可访问应用
     * @param string $code
     * @param string|null $route
     * @return bool
     */
    public function isPermissionAccessible(string $code = '', ?string $route = null): bool
    {
        $record = $this->resolveAppByPermission($code, $route ?? '');
        if (!$record) {
            return true;
        }

        return (int)($record['status'] ?? 1) === 1;
    }

    /**
     * 根据权限信息解析应用
     * @param string $code
     * @param string $route
     * @return array|null
     */
    public function resolveAppByPermission(string $code = '', string $route = ''): ?array
    {
        $record = $this->resolveAppByRoute($route);
        if ($record) {
            return $record;
        }

        $code = trim($code);
        if ($code === '') {
            return null;
        }

        $prefix = strtolower((string)strtok($code, '.'));
        if ($prefix !== '') {
            $record = $this->resolveApp($prefix);
            if ($record) {
                return $record;
            }
        }

        foreach ($this->getApps() as $app) {
            $appName = trim((string)($app['name'] ?? ''));
            if ($appName === '') {
                continue;
            }

            $metaCode = trim((string)($this->loadAppMeta($appName)['code'] ?? ''));
            if ($metaCode !== '' && ($code === $metaCode || str_starts_with($code, $metaCode . '.'))) {
                return $app;
            }
        }

        return null;
    }

    /**
     * 根据应用顶级菜单代码解析应用
     * @param string $menuCode
     * @return array|null
     */
    public function resolveAppByMenuCode(string $menuCode): ?array
    {
        $menuCode = trim($menuCode);
        if ($menuCode === '') {
            return null;
        }

        foreach ($this->getApps() as $app) {
            $appName = trim((string)($app['name'] ?? ''));
            if ($appName === '') {
                continue;
            }

            $metaCode = trim((string)($this->loadAppMeta($appName)['code'] ?? ''));
            if ($menuCode === $appName || ($metaCode !== '' && $menuCode === $metaCode)) {
                return $app;
            }
        }

        return null;
    }

    /**
     * 判断应用是否允许进入 Config
     * @param string $app
     * @return bool
     */
    public function canUseDynamicConfig(string $app): bool
    {
        $record = $this->resolveApp($app);
        if (!$record) {
            return false;
        }

        return (int)($record['status'] ?? 0) === 1
            && (int)($record['is_system'] ?? 0) === 1
            && (int)($record['show_in_config'] ?? 0) === 1;
    }

    /**
     * 获取声明型设置分组
     * @param string $app
     * @param array $excludeKeys
     * @return array
     */
    public function getDeclaredSettingsByGroup(string $app, array $excludeKeys = []): array
    {
        $app = trim($app);
        if ($app === '') {
            return [];
        }

        $excludeMap  = array_fill_keys(array_filter(array_map('strval', $excludeKeys)), true);
        $definitions = $this->loadSettingsDefinition($app);
        if ($definitions === []) {
            return [];
        }

        $storedValues = $this->getStoredSettings($app);
        $groups       = [];

        foreach ($definitions as $definition) {
            $groupKey   = (string)$definition['group_key'];
            $groupTitle = (string)$definition['group'];

            foreach ($definition['items'] as $item) {
                $key = (string)$item['key'];
                if ($key === '' || isset($excludeMap[$key])) {
                    continue;
                }

                $record = [
                    'source'        => 'declared',
                    'app'           => $app,
                    'group'         => $groupTitle,
                    'group_key'     => $groupKey,
                    'group_sort'    => (int)$definition['group_sort'],
                    'title'         => (string)$item['title'],
                    'key'           => $key,
                    'type'          => (string)$item['type'],
                    'value'         => array_key_exists($key, $storedValues)
                        ? $this->normalizeStoredValue($storedValues[$key])
                        : '',
                    'default_value' => $this->normalizeStoredValue($item['default']),
                    'options'       => $this->encodeJsonSafely((array)$item['options']),
                    'rules'         => $this->normalizeRules($item['rules']),
                    'remark'        => (string)$item['remark'],
                    'sort'          => (int)$item['sort'],
                    'status'        => !empty($item['status']) ? 1 : 0,
                ];

                if ($record['status'] !== 1) {
                    continue;
                }

                $groups[$groupKey]['title']   ??= $groupTitle;
                $groups[$groupKey]['sort']    ??= (int)$definition['group_sort'];
                $groups[$groupKey]['items'][] = $record;
            }
        }

        if ($groups === []) {
            return [];
        }

        uasort($groups, static function (array $left, array $right): int {
            $leftSort  = (int)($left['sort'] ?? 0);
            $rightSort = (int)($right['sort'] ?? 0);

            if ($leftSort === $rightSort) {
                return strcmp((string)($left['title'] ?? ''), (string)($right['title'] ?? ''));
            }

            return $leftSort <=> $rightSort;
        });

        $result = [];
        foreach ($groups as $group) {
            $items = $group['items'] ?? [];
            usort($items, static function (array $left, array $right): int {
                $leftSort  = (int)($left['sort'] ?? 0);
                $rightSort = (int)($right['sort'] ?? 0);

                if ($leftSort === $rightSort) {
                    return strcmp((string)($left['title'] ?? ''), (string)($right['title'] ?? ''));
                }

                return $leftSort <=> $rightSort;
            });

            $result[(string)$group['title']] = $items;
        }

        return $result;
    }

    /**
     * 保存声明型设置值
     * @param string $app
     * @param array $submitted
     * @param array $records
     * @return array
     * @throws Exception
     */
    public function saveDeclaredSettings(string $app, array $submitted, array $records): array
    {
        $app = trim($app);
        if ($app === '' || $records === []) {
            return [];
        }

        /** @var AppModel|null $appRecord */
        $appRecord = $this->model->where('name', $app)->find();
        if (!$appRecord) {
            throw new Exception('应用不存在');
        }

        $storedSettings = $this->getStoredSettings($app);
        $settings       = $storedSettings;
        $changes        = [];

        foreach ($records as $record) {
            $key      = trim((string)($record['key'] ?? ''));
            $title    = trim((string)($record['title'] ?? $key));
            $existing = array_key_exists($key, $storedSettings)
                ? $this->normalizeStoredValue($storedSettings[$key])
                : '';
            $rawValue = array_key_exists($key, $submitted)
                ? $submitted[$key]
                : $this->configService->getMissingSubmittedValue($record);

            $this->configService->validateSubmittedValue($record, $rawValue, $title);
            $storedValue  = $this->configService->normalizeSubmittedValue($record, $rawValue);
            $defaultValue = (string)($record['default_value'] ?? '');

            if ($storedValue === $defaultValue || $storedValue === '') {
                unset($settings[$key]);
            } else {
                $settings[$key] = $storedValue;
            }

            $afterValue = $settings[$key] ?? '';
            if ($afterValue === $existing) {
                continue;
            }

            $changes[] = [
                'app'    => $app,
                'source' => 'declared',
                'group'  => (string)($record['group'] ?? ''),
                'title'  => $title,
                'key'    => $key,
                'type'   => (string)($record['type'] ?? ''),
                'before' => $this->configService->summarizeStoredValue($existing, (string)($record['type'] ?? ''), $record),
                'after'  => $this->configService->summarizeStoredValue($afterValue, (string)($record['type'] ?? ''), $record),
            ];
        }

        if ($changes === []) {
            return [];
        }

        $appRecord->save([
            'settings' => $settings === [] ? '' : $this->encodeJsonSafely($settings),
        ]);

        $this->clearCache($app);
        return $changes;
    }

    /**
     * 获取应用设置值
     * @param string $app
     * @param string $key
     * @param mixed $default
     * @return mixed
     * @throws Throwable
     */
    public function getSetting(string $app, string $key, mixed $default = null): mixed
    {
        $app = trim($app);
        $key = trim($key);

        if ($app === '' || $key === '') {
            return $default;
        }

        $map = Cache::remember(self::CACHE_KEY_SETTING_MAP_PREFIX . $app, function () use ($app) {
            $map = [];

            foreach ($this->getDeclaredSettingsByGroup($app) as $items) {
                foreach ($items as $record) {
                    $settingKey = trim((string)($record['key'] ?? ''));
                    if ($settingKey === '') {
                        continue;
                    }

                    $value = $record['value'] !== ''
                        ? $record['value']
                        : (string)($record['default_value'] ?? '');

                    $map[$settingKey] = $this->configService->convertStoredValue($record, $value);
                }
            }

            return $map;
        }, self::CACHE_TTL);

        return $map[$key] ?? $default;
    }

    /**
     * 判断应用是否存在声明型设置
     * @param string $app
     * @return bool
     */
    public function hasDeclaredSettings(string $app): bool
    {
        return $this->loadSettingsDefinition($app) !== [];
    }

    /**
     * 获取应用合成定义
     * @param string $app
     * @return array
     */
    public function getAppDefinition(string $app): array
    {
        return $this->loadAppMeta($app);
    }

    /**
     * 获取应用静态分发元数据
     * @param string $app
     * @return array
     */
    public function getAppManifest(string $app): array
    {
        return $this->loadAppManifest($app);
    }

    /**
     * 清理缓存
     * @param string $app
     * @return void
     */
    public function clearCache(string $app = ''): void
    {
        $this->synced = false;
        Cache::delete(self::CACHE_KEY_APPS);

        $app = trim($app);
        if ($app !== '') {
            Cache::delete(self::CACHE_KEY_SETTING_MAP_PREFIX . $app);
            return;
        }

        $names = array_keys($this->discoverApps());

        try {
            $storedNames = $this->model->column('name');
            foreach ($storedNames as $storedName) {
                $storedName = trim((string)$storedName);
                if ($storedName !== '') {
                    $names[] = $storedName;
                }
            }
        } catch (Throwable) {
        }

        foreach (array_unique($names) as $name) {
            if ($name !== '') {
                Cache::delete(self::CACHE_KEY_SETTING_MAP_PREFIX . $name);
            }
        }
    }

    /**
     * 扫描应用目录
     * @return array
     */
    private function discoverApps(): array
    {
        $basePath = rtrim(app()->getBasePath(), DIRECTORY_SEPARATOR);
        $dirs     = scandir($basePath) ?: [];
        $apps     = [];

        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..' || in_array($dir, ['common', 'lang'], true)) {
                continue;
            }

            $path = $basePath . DIRECTORY_SEPARATOR . $dir;
            if (!is_dir($path)) {
                continue;
            }

            $appConfigPath = $path . DIRECTORY_SEPARATOR . 'app.php';
            if (!is_file($appConfigPath)) {
                continue;
            }

            $meta       = $this->getAppDefinition($dir);
            $apps[$dir] = [
                'name'           => $dir,
                'title'          => (string)($meta['title'] ?? $meta['name'] ?? $dir),
                'description'    => (string)($meta['description'] ?? ''),
                'icon'           => (string)($meta['icon'] ?? ''),
                'version'        => (string)($meta['version'] ?? ''),
                'author'         => (string)($meta['author'] ?? ''),
                'provider'       => (string)($meta['provider'] ?? $meta['settings_provider'] ?? ''),
                'sort'           => max(0, (int)($meta['sort'] ?? 0)),
                'status'         => !array_key_exists('status', $meta) || !empty($meta['status']) ? 1 : 0,
                'show_in_config' => 0,
                'settings'       => '',
            ];
        }

        uasort($apps, static function (array $left, array $right): int {
            if ((int)$left['sort'] === (int)$right['sort']) {
                return strcmp((string)$left['name'], (string)$right['name']);
            }

            return (int)$left['sort'] <=> (int)$right['sort'];
        });

        return $apps;
    }

    /**
     * 加载应用元信息
     * @param string $app
     * @return array
     */
    private function loadAppMeta(string $app): array
    {
        $manifest = $this->loadAppManifest($app);
        $runtime  = $this->loadAppRuntimeConfig($app);

        if ($manifest === [] && $runtime === []) {
            return [];
        }

        return $this->mergeAppMeta($app, $manifest, $runtime);
    }

    /**
     * 加载应用静态元数据
     * @param string $app
     * @return array
     */
    private function loadAppManifest(string $app): array
    {
        $path = app()->getBasePath() . $app . DIRECTORY_SEPARATOR . config('app_package.manifest', 'app.json');
        if (!is_file($path)) {
            return [];
        }

        try {
            $meta = json_decode((string)file_get_contents($path), true);
            return is_array($meta) ? $meta : [];
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * 加载应用运行时配置
     * @param string $app
     * @return array
     */
    private function loadAppRuntimeConfig(string $app): array
    {
        $path = app()->getBasePath() . $app . DIRECTORY_SEPARATOR . 'app.php';
        if (!is_file($path)) {
            return [];
        }

        try {
            $meta = include $path;
            return is_array($meta) ? $meta : [];
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * 合成应用定义
     * @param string $app
     * @param array $manifest
     * @param array $runtime
     * @return array
     */
    private function mergeAppMeta(string $app, array $manifest, array $runtime): array
    {
        $title = trim((string)($manifest['title'] ?? $runtime['name'] ?? $manifest['name'] ?? $app));

        return array_merge($manifest, $runtime, [
            'title'       => $title !== '' ? $title : $app,
            'name'        => $title !== '' ? $title : $app,
            'description' => trim((string)($manifest['description'] ?? $runtime['description'] ?? '')),
            'version'     => trim((string)($manifest['version'] ?? $runtime['version'] ?? '')),
            'author'      => trim((string)($manifest['author'] ?? $runtime['author'] ?? '')),
        ]);
    }

    /**
     * 加载应用设置声明
     * @param string $app
     * @return array
     */
    private function loadSettingsDefinition(string $app): array
    {
        $path = app()->getBasePath() . $app . DIRECTORY_SEPARATOR . 'settings.php';
        if (!is_file($path)) {
            return [];
        }

        try {
            $definition = include $path;
        } catch (Throwable) {
            return [];
        }

        if (!is_array($definition)) {
            return [];
        }

        $groups = [];
        foreach ($definition as $index => $group) {
            if (!is_array($group)) {
                continue;
            }

            $groupKey   = trim((string)($group['key'] ?? 'group_' . $index));
            $groupTitle = trim((string)($group['title'] ?? $group['group'] ?? $groupKey));
            $items      = $group['items'] ?? [];

            if ($groupTitle === '' || !is_array($items)) {
                continue;
            }

            $normalizedItems = [];
            foreach ($items as $itemIndex => $item) {
                if (!is_array($item)) {
                    continue;
                }

                $key  = strtolower(trim((string)($item['key'] ?? '')));
                $type = trim((string)($item['type'] ?? 'text'));
                if ($key === '' || !$this->configService->isSupportedType($type)) {
                    continue;
                }

                $normalizedItems[] = [
                    'key'     => $key,
                    'title'   => trim((string)($item['title'] ?? $key)),
                    'type'    => $type,
                    'default' => $item['default'] ?? '',
                    'options' => is_array($item['options'] ?? null) ? $item['options'] : [],
                    'rules'   => $item['rules'] ?? '',
                    'remark'  => trim((string)($item['remark'] ?? '')),
                    'sort'    => max(0, (int)($item['sort'] ?? (($itemIndex + 1) * 10))),
                    'status'  => !array_key_exists('status', $item) || !empty($item['status']) ? 1 : 0,
                ];
            }

            if ($normalizedItems === []) {
                continue;
            }

            $groups[] = [
                'group_key'  => $groupKey,
                'group'      => $groupTitle,
                'group_sort' => max(0, (int)($group['sort'] ?? (($index + 1) * 10))),
                'items'      => $normalizedItems,
            ];
        }

        usort($groups, static function (array $left, array $right): int {
            if ((int)$left['group_sort'] === (int)$right['group_sort']) {
                return strcmp((string)$left['group'], (string)$right['group']);
            }

            return (int)$left['group_sort'] <=> (int)$right['group_sort'];
        });

        return $groups;
    }

    /**
     * 根据路由解析应用
     * @param string $route
     * @return array|null
     */
    private function resolveAppByRoute(string $route): ?array
    {
        $route = trim(str_replace('\\', '/', $route), '/');
        if ($route === '') {
            return null;
        }

        $app = strtolower((string)strtok($route, '/'));
        if ($app === '') {
            return null;
        }

        return $this->resolveApp($app);
    }

    /**
     * 判断应用记录是否允许访问
     * @param array<string, mixed> $record
     * @return bool
     */
    private function isAccessibleRecord(array $record): bool
    {
        return (int)($record['status'] ?? 1) === 1
            && $this->isInstalledRecord($record);
    }

    /**
     * 判断应用记录是否处于已安装生命周期
     * @param array<string, mixed> $record
     * @return bool
     */
    private function isInstalledRecord(array $record): bool
    {
        return (string)($record['lifecycle_status'] ?? config('app_package.lifecycle.installed', 'installed'))
            === (string)config('app_package.lifecycle.installed', 'installed');
    }

    /**
     * 获取应用存储的声明型配置值
     * @param string $app
     * @return array
     */
    private function getStoredSettings(string $app): array
    {
        try {
            $record = $this->model->where('name', $app)->find();
        } catch (Throwable) {
            return [];
        }

        if (!$record) {
            return [];
        }

        $settings = trim((string)$record->getAttr('settings'));
        if ($settings === '') {
            return [];
        }

        $decoded = json_decode($settings, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * 装饰应用记录
     * @param array $record
     * @return array
     */
    private function decorateAppRecord(array $record): array
    {
        $name                            = (string)($record['name'] ?? '');
        $record['has_declared_settings'] = $name !== '' && $this->hasDeclaredSettings($name);
        $record['has_dynamic_configs']   = $name !== ''
            ? $this->configService->hasConfigs($name)
            : false;
        $record['has_settings']          = !empty($record['has_declared_settings']) || !empty($record['has_dynamic_configs']);
        $record['show_in_config']        = (int)($record['show_in_config'] ?? 0);
        $record['can_show_in_config']    = (int)($record['is_system'] ?? 0) === 1;
        $record['author']                = trim((string)($record['author'] ?? ''));
        $record['lifecycle_status']      = trim((string)($record['lifecycle_status'] ?? config('app_package.lifecycle.installed', 'installed')));
        $record['lifecycle_status_text'] = $this->getLifecycleStatusText($record['lifecycle_status']);
        $record['installed_version']     = trim((string)($record['installed_version'] ?? ''));
        $record['last_operation']        = trim((string)($record['last_operation'] ?? ''));
        $record['last_error']            = trim((string)($record['last_error'] ?? ''));

        $distributionState                       = $name !== '' ? $this->inspectDistributionState($name) : [];
        $record['distribution_ready']            = !empty($distributionState['ready']) ? 1 : 0;
        $record['distribution_error']            = (string)($distributionState['error'] ?? '');
        $record['distribution_protocol_version'] = trim((string)($record['distribution_protocol_version'] ?? ($distributionState['protocol_version'] ?? '')));
        $record['version_display']               = $this->buildVersionDisplay($record);
        $record['upgrade_available']             = $this->hasUpgradeAvailable($record, $distributionState) ? 1 : 0;
        $record['upgrade_available_text']        = !empty($record['upgrade_available']) ? '可升级' : '无';
        $record['lifecycle_state_text']          = $this->buildLifecycleStateText($record);
        $record['lifecycle_state_key']           = $this->buildLifecycleStateKey($record);

        return $record;
    }

    /**
     * 构建新增载荷
     * @param string $appName
     * @param array $meta
     * @return array
     */
    private function buildInsertPayload(string $appName, array $meta): array
    {
        $distributionState = $this->inspectDistributionState($appName);
        $now               = time();

        return [
            'name'                          => $appName,
            'title'                         => (string)($meta['title'] ?? $appName),
            'description'                   => (string)($meta['description'] ?? ''),
            'icon'                          => (string)($meta['icon'] ?? ''),
            'version'                       => (string)($meta['version'] ?? ''),
            'installed_version'             => '',
            'author'                        => (string)($meta['author'] ?? ''),
            'provider'                      => (string)($meta['provider'] ?? ''),
            'settings'                      => '',
            'sort'                          => max(0, (int)($meta['sort'] ?? 0)),
            'status'                        => 0,
            'show_in_config'                => 0,
            'lifecycle_status'              => (string)config('app_package.lifecycle.imported', 'imported'),
            'distribution_protocol_version' => (string)($distributionState['protocol_version'] ?? ''),
            'distribution_meta'             => (string)($distributionState['manifest_json'] ?? ''),
            'last_operation'                => 'discover',
            'last_error'                    => '',
            'install_time'                  => 0,
            'enable_time'                   => 0,
            'disable_time'                  => $now,
        ];
    }

    /**
     * 构建刷新载荷
     * @param array $current
     * @param array $meta
     * @return array
     */
    private function buildRefreshPayload(array $current, array $meta): array
    {
        $payload = [];
        foreach (['title', 'description', 'version', 'author', 'provider'] as $field) {
            $newValue = $meta[$field] ?? '';
            $oldValue = $current[$field] ?? '';

            if ($newValue !== $oldValue) {
                $payload[$field] = $newValue;
            }
        }

        $currentIcon = trim((string)($current['icon'] ?? ''));
        $metaIcon    = trim((string)($meta['icon'] ?? ''));
        if ($currentIcon === '' && $metaIcon !== '') {
            $payload['icon'] = $metaIcon;
        }

        $distributionState = $this->inspectDistributionState((string)($current['name'] ?? ''));
        $protocolVersion   = (string)($distributionState['protocol_version'] ?? '');
        $manifestJson      = (string)($distributionState['manifest_json'] ?? '');
        $version           = (string)($meta['version'] ?? '');

        if ($protocolVersion !== (string)($current['distribution_protocol_version'] ?? '')) {
            $payload['distribution_protocol_version'] = $protocolVersion;
        }

        if ($manifestJson !== (string)($current['distribution_meta'] ?? '')) {
            $payload['distribution_meta'] = $manifestJson;
        }

        if ($version !== '' && $version !== (string)($current['version'] ?? '')) {
            $payload['version'] = $version;
        }

        if (
            trim((string)($current['installed_version'] ?? '')) === ''
            && trim((string)($current['lifecycle_status'] ?? '')) === (string)config('app_package.lifecycle.installed', 'installed')
            && $version !== ''
        ) {
            $payload['installed_version'] = $version;
        }

        return $payload;
    }

    /**
     * 检查应用分发状态
     * @param string $app
     * @return array{ready:bool,error:string,protocol_version:string,manifest_json:string}
     */
    private function inspectDistributionState(string $app): array
    {
        $app = trim($app);
        if ($app === '') {
            return [
                'ready'            => false,
                'error'            => '应用标识不能为空',
                'protocol_version' => '',
                'manifest_json'    => '',
            ];
        }

        $appPath       = app()->getBasePath() . $app . DIRECTORY_SEPARATOR;
        $manifestPath  = $appPath . config('app_package.manifest', 'app.json');
        $defaultResult = [
            'ready'            => false,
            'error'            => '缺少 app.json',
            'protocol_version' => '',
            'manifest_json'    => '',
        ];

        if (!is_file($manifestPath)) {
            return $defaultResult;
        }

        try {
            $decoded = json_decode((string)file_get_contents($manifestPath), true);
        } catch (Throwable) {
            return [
                'ready'            => false,
                'error'            => 'app.json 读取失败',
                'protocol_version' => '',
                'manifest_json'    => '',
            ];
        }

        if (!is_array($decoded)) {
            return [
                'ready'            => false,
                'error'            => 'app.json 解析失败',
                'protocol_version' => '',
                'manifest_json'    => '',
            ];
        }

        $validator = app(AppMetadataValidator::class);
        $error     = $validator->validate($decoded, rtrim($appPath, DIRECTORY_SEPARATOR));

        return [
            'ready'            => $error === '',
            'error'            => $error,
            'protocol_version' => trim((string)($decoded['app_api_version'] ?? '')),
            'manifest_json'    => $this->encodeJsonSafely($decoded),
        ];
    }

    /**
     * 获取生命周期状态文案
     * @param string $status
     * @return string
     */
    private function getLifecycleStatusText(string $status): string
    {
        return match ($status) {
            (string)config('app_package.lifecycle.imported', 'imported') => '已导入',
            (string)config('app_package.lifecycle.installed', 'installed') => '已安装',
            (string)config('app_package.lifecycle.broken', 'broken') => '异常',
            default => $status !== '' ? $status : '未知',
        };
    }

    /**
     * 是否有可升级版本
     * @param array<string, mixed> $record
     * @param array<string, mixed> $distributionState
     * @return bool
     */
    private function hasUpgradeAvailable(array $record, array $distributionState): bool
    {
        $currentVersion   = trim((string)($record['version'] ?? ''));
        $installedVersion = trim((string)($record['installed_version'] ?? ''));
        $lifecycleStatus  = trim((string)($record['lifecycle_status'] ?? ''));

        if (
            $currentVersion === ''
            || $installedVersion === ''
            || $lifecycleStatus !== (string)config('app_package.lifecycle.installed', 'installed')
            || empty($distributionState['ready'])
        ) {
            return false;
        }

        return version_compare($currentVersion, $installedVersion, '>');
    }

    /**
     * 构建版本展示文本
     * @param array<string, mixed> $record
     * @return string
     */
    private function buildVersionDisplay(array $record): string
    {
        $currentVersion   = trim((string)($record['version'] ?? ''));
        $installedVersion = trim((string)($record['installed_version'] ?? ''));
        if ($installedVersion === '' || $installedVersion === $currentVersion) {
            return $currentVersion;
        }

        return $currentVersion . ' / 已装 ' . $installedVersion;
    }

    /**
     * 构建生命周期展示文案
     * @param array<string, mixed> $record
     * @return string
     */
    private function buildLifecycleStateText(array $record): string
    {
        $status          = (int)($record['status'] ?? 0);
        $lifecycleStatus = trim((string)($record['lifecycle_status'] ?? ''));
        $lastError       = trim((string)($record['last_error'] ?? ''));

        if ($lifecycleStatus === (string)config('app_package.lifecycle.imported', 'imported')) {
            return '待安装';
        }

        if ($lifecycleStatus === (string)config('app_package.lifecycle.broken', 'broken') || $lastError !== '') {
            return '异常';
        }

        if (!empty($record['upgrade_available'])) {
            return '可升级';
        }

        if ($lifecycleStatus === (string)config('app_package.lifecycle.installed', 'installed')) {
            return $status === 1 ? '已启用' : '已禁用';
        }

        return $this->getLifecycleStatusText($lifecycleStatus);
    }

    /**
     * 构建生命周期展示键
     * @param array<string, mixed> $record
     * @return string
     */
    private function buildLifecycleStateKey(array $record): string
    {
        return match ($this->buildLifecycleStateText($record)) {
            '待安装' => 'imported',
            '已启用' => 'enabled',
            '已禁用' => 'disabled',
            '可升级' => 'upgrade_available',
            '异常' => 'broken',
            default => trim((string)($record['lifecycle_status'] ?? '')),
        };
    }

    /**
     * 规范化规则存储格式
     * @param mixed $rules
     * @return string
     */
    private function normalizeRules(mixed $rules): string
    {
        if (is_array($rules)) {
            return $this->encodeJsonSafely($rules);
        }

        return trim((string)$rules);
    }

    /**
     * 规范化存储值
     * @param mixed $value
     * @return string
     */
    private function normalizeStoredValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_array($value)) {
            return $this->encodeJsonSafely($value);
        }

        return (string)$value;
    }

    /**
     * 安全编码 JSON
     * @param array $value
     * @return string
     */
    private function encodeJsonSafely(array $value): string
    {
        try {
            $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return $encoded === false ? '' : $encoded;
        } catch (Throwable) {
            return '';
        }
    }
}
