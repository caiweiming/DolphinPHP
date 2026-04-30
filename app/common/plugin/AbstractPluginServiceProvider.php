<?php
declare(strict_types=1);

namespace app\common\plugin;

use ReflectionClass;
use think\facade\Config;
use think\Service;

/**
 * 插件服务提供者基类
 */
abstract class AbstractPluginServiceProvider extends Service
{
    /**
     * 注册插件基础资源
     * @return void
     */
    final public function register(): void
    {
        $this->mergePluginConfigs();
        $this->registerPluginViewPath();
        $this->registerPluginAssetNamespace();
        $this->registerPlugin();
    }

    /**
     * 启动插件基础资源
     * @return void
     */
    final public function boot(): void
    {
        $this->loadPluginRoutes();
        $this->loadPluginLang();
        $this->bootPlugin();
    }

    /**
     * 插件自定义注册逻辑
     * @return void
     */
    protected function registerPlugin(): void
    {
    }

    /**
     * 插件自定义启动逻辑
     * @return void
     */
    protected function bootPlugin(): void
    {
    }

    /**
     * 获取插件名
     * @return string
     */
    protected function getPluginName(): string
    {
        $descriptor = $this->resolveDescriptor();
        return $descriptor?->getName() ?: $this->resolvePluginNameFromFile();
    }

    /**
     * 获取插件根目录
     * @param string $path
     * @return string
     */
    protected function pluginPath(string $path = ''): string
    {
        $root = $this->resolvePluginRoot();
        if ($path === '') {
            return $root;
        }

        return $root . DIRECTORY_SEPARATOR . ltrim($path, '/\\');
    }

    /**
     * 获取插件视图目录
     * @param string $path
     * @return string
     */
    protected function pluginViewPath(string $path = ''): string
    {
        return $this->pluginPath('view' . ($path === '' ? '' : DIRECTORY_SEPARATOR . ltrim($path, '/\\')));
    }

    /**
     * 获取插件语言目录
     * @param string $path
     * @return string
     */
    protected function pluginLangPath(string $path = ''): string
    {
        return $this->pluginPath('lang' . ($path === '' ? '' : DIRECTORY_SEPARATOR . ltrim($path, '/\\')));
    }

    /**
     * 获取插件 public 目录
     * @param string $path
     * @return string
     */
    protected function pluginPublicPath(string $path = ''): string
    {
        return $this->pluginPath('public' . ($path === '' ? '' : DIRECTORY_SEPARATOR . ltrim($path, '/\\')));
    }

    /**
     * 获取插件静态资源 URL
     * @param string $path
     * @return string
     */
    protected function pluginAssetUrl(string $path = ''): string
    {
        $name = $this->getPluginName();
        [$vendor, $plugin] = $this->splitPluginName($name);

        $base = rtrim($this->app->request->rootUrl(), '/');
        $url  = $base . '/'
            . trim((string)config('plugin.asset.url_prefix', 'plugins'), '/')
            . '/'
            . $vendor
            . '/'
            . $plugin;

        if ($path === '') {
            return $url;
        }

        return $url . '/' . ltrim($path, '/');
    }

    /**
     * 注册表单项
     * @param string $type
     * @param string $class
     * @return void
     */
    protected function registerFormItem(string $type, string $class): void
    {
        $this->app->make(PluginRegistry::class)->registerFormItem($type, $class);
    }

    /**
     * 注册表格列
     * @param string $type
     * @param string $class
     * @return void
     */
    protected function registerTableItem(string $type, string $class): void
    {
        $this->app->make(PluginRegistry::class)->registerTableItem($type, $class);
    }

    /**
     * 注册图表类型
     * @param string $type
     * @param string $class
     * @return void
     */
    protected function registerChartType(string $type, string $class): void
    {
        $this->app->make(PluginRegistry::class)->registerChartType($type, $class);
    }

    /**
     * 注册图表地图 Provider
     * @param string $mapKey
     * @param string $class
     * @return void
     */
    protected function registerChartMap(string $mapKey, string $class): void
    {
        $this->app->make(PluginRegistry::class)->registerChartMap($mapKey, $class);
    }

    /**
     * 注册上传驱动
     * @param string $name
     * @param string $class
     * @return void
     */
    protected function registerUploadDriver(string $name, string $class): void
    {
        $this->app->make(PluginRegistry::class)->registerUploadDriver($name, $class);
    }

    /**
     * 注册插件命令
     * @param string $name
     * @param string $class
     * @return void
     */
    protected function registerCommand(string $name, string $class): void
    {
        $this->app->make(PluginRegistry::class)->registerCommand($name, $class);
    }

    /**
     * 注册插件中间件别名
     * @param string $alias
     * @param string $class
     * @return void
     */
    protected function registerMiddleware(string $alias, string $class): void
    {
        $this->app->make(PluginRegistry::class)->registerMiddleware($alias, $class);

        $aliases                                      = (array)Config::get('middleware.alias', []);
        $aliases[dp_normalize_extension_name($alias)] = $class;
        Config::set(['alias' => $aliases], 'middleware');
    }

    /**
     * 注册 AI 适配器
     * @param string $name
     * @param string $class
     * @return void
     */
    protected function registerAiAdapter(string $name, string $class): void
    {
        $this->app->make(PluginRegistry::class)->registerAiAdapter($name, $class);
    }

    /**
     * 注册组件处理类
     * @param string $type
     * @param string $component
     * @param string $class
     * @return void
     */
    protected function registerComponentHandler(string $type, string $component, string $class): void
    {
        $this->app->make(PluginRegistry::class)->registerComponentHandler($type, $component, $class);
    }

    /**
     * 注册后台槽位入口项
     * @param string $slot
     * @param array<string, mixed> $item
     * @return void
     */
    protected function registerAdminSlot(string $slot, array $item): void
    {
        if (!isset($item['__plugin'])) {
            $item['__plugin'] = $this->getPluginName();
        }

        $this->app->make(PluginRegistry::class)->registerAdminSlot($slot, $item);
    }

    /**
     * 注册后台工作台卡片
     *
     * @param array<string, mixed> $card
     * @return void
     */
    protected function registerAdminWorkspaceCard(array $card): void
    {
        if (!isset($card['__plugin'])) {
            $card['__plugin'] = $this->getPluginName();
        }

        $this->app->make(PluginRegistry::class)->registerAdminWorkspaceCard($card);
    }

    /**
     * 注册 Action Hook
     * @param string $hook
     * @param callable $listener
     * @param int $priority
     * @return void
     */
    protected function registerAction(string $hook, callable $listener, int $priority = 50): void
    {
        $this->app->make(HookManager::class)->registerAction($hook, $listener, $priority);
    }

    /**
     * 注册 Filter Hook
     * @param string $hook
     * @param callable $listener
     * @param int $priority
     * @return void
     */
    protected function registerFilter(string $hook, callable $listener, int $priority = 50): void
    {
        $this->app->make(HookManager::class)->registerFilter($hook, $listener, $priority);
    }

    /**
     * 合并插件配置
     * @return void
     */
    protected function mergePluginConfigs(): void
    {
        $configDir = $this->pluginPath('config');
        if (!is_dir($configDir)) {
            return;
        }

        foreach (glob($configDir . DIRECTORY_SEPARATOR . '*.php') ?: [] as $file) {
            $configName = basename($file, '.php');
            $config     = include $file;
            if (!is_array($config)) {
                continue;
            }

            $this->setPluginNestedConfig($configName, $config);
        }
    }

    /**
     * 加载插件路由
     * @return void
     */
    protected function loadPluginRoutes(): void
    {
        $routeFile = $this->pluginPath('routes.php');
        if (is_file($routeFile)) {
            $this->loadRoutesFrom($routeFile);
        }
    }

    /**
     * 加载插件语言包
     * @return void
     */
    protected function loadPluginLang(): void
    {
        $langDir = $this->pluginPath('lang');
        if (!is_dir($langDir)) {
            return;
        }

        $defaultLang = $this->app->lang->defaultLangSet();
        $currentLang = $this->app->lang->getLangSet();
        $files       = array_values(array_unique(array_filter([
            $langDir . DIRECTORY_SEPARATOR . $defaultLang . '.php',
            $langDir . DIRECTORY_SEPARATOR . $currentLang . '.php',
        ], static fn(string $file): bool => is_file($file))));

        if ($files !== []) {
            $this->app->lang->load($files);
        }
    }

    /**
     * 注册插件视图目录
     * @return void
     */
    protected function registerPluginViewPath(): void
    {
        $viewDir = $this->pluginPath('view');
        if (!is_dir($viewDir)) {
            return;
        }

        $this->setPluginPathConfig('view_paths', $viewDir);
    }

    /**
     * 注册插件静态资源命名空间
     * @return void
     */
    protected function registerPluginAssetNamespace(): void
    {
        $publicDir = $this->pluginPath('public');
        if (!is_dir($publicDir)) {
            return;
        }

        $key                 = '__PLUGIN_' . strtoupper(str_replace(['/', '-'], '_', $this->getPluginName())) . '__';
        $replaceString       = (array)Config::get('view.tpl_replace_string', []);
        $replaceString[$key] = $this->pluginAssetUrl();
        Config::set(['tpl_replace_string' => $replaceString], 'view');

        $this->setPluginPathConfig('asset_urls', $this->pluginAssetUrl());
        $this->setPluginPathConfig('public_paths', $publicDir);
    }

    /**
     * 写入插件路径配置
     * @param string $key
     * @param string $value
     * @return void
     */
    private function setPluginPathConfig(string $key, string $value): void
    {
        $all                         = (array)config('plugin.' . $key, []);
        $all[$this->getPluginName()] = $value;
        Config::set([$key => $all], 'plugin');
    }

    /**
     * 写入插件嵌套配置
     * @param string $configName
     * @param array<string, mixed> $config
     * @return void
     */
    private function setPluginNestedConfig(string $configName, array $config): void
    {
        [$vendor, $plugin] = $this->splitPluginName($this->getPluginName());
        $all                                = (array)Config::get('plugin_packages', []);
        $existing                           = (array)($all[$vendor][$plugin][$configName] ?? []);
        $all[$vendor][$plugin][$configName] = array_replace_recursive($existing, $config);
        Config::set($all, 'plugin_packages');
    }

    /**
     * 解析插件描述对象
     * @return PluginDescriptor|null
     */
    private function resolveDescriptor(): ?PluginDescriptor
    {
        $repository = $this->app->bound(PluginRepository::class)
            ? $this->app->make(PluginRepository::class)
            : null;

        if (!$repository instanceof PluginRepository) {
            return null;
        }

        foreach ($repository->all() as $descriptor) {
            if ($descriptor->getProviderClass() === static::class) {
                return $descriptor;
            }
        }

        return null;
    }

    /**
     * 从文件路径推断插件名
     * @return string
     */
    private function resolvePluginNameFromFile(): string
    {
        $root = str_replace('\\', '/', rtrim((string)config('plugin.root', root_path() . 'plugins'), DIRECTORY_SEPARATOR)) . '/';
        $file = str_replace('\\', '/', (string)(new ReflectionClass($this))->getFileName());

        if (!str_starts_with($file, $root)) {
            return '';
        }

        $relative = trim(substr($file, strlen($root)), '/');
        $segments = explode('/', $relative);
        if (count($segments) < 2) {
            return '';
        }

        return $segments[0] . '/' . $segments[1];
    }

    /**
     * 解析插件根目录
     * @return string
     */
    private function resolvePluginRoot(): string
    {
        $descriptor = $this->resolveDescriptor();
        if ($descriptor) {
            return rtrim($descriptor->getPath(), DIRECTORY_SEPARATOR);
        }

        $root     = str_replace('\\', '/', rtrim((string)config('plugin.root', root_path() . 'plugins'), DIRECTORY_SEPARATOR)) . '/';
        $file     = str_replace('\\', '/', (string)(new ReflectionClass($this))->getFileName());
        $relative = trim(substr($file, strlen($root)), '/');
        $segments = explode('/', $relative);

        if (count($segments) < 2) {
            return rtrim(dirname($file), DIRECTORY_SEPARATOR);
        }

        return rtrim((string)config('plugin.root', root_path() . 'plugins'), DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . $segments[0]
            . DIRECTORY_SEPARATOR
            . $segments[1];
    }

    /**
     * 拆分插件名
     * @param string $name
     * @return array{0:string,1:string}
     */
    private function splitPluginName(string $name): array
    {
        $parts = explode('/', $name, 2);
        return [
            $parts[0] ?? '',
            $parts[1] ?? '',
        ];
    }
}
