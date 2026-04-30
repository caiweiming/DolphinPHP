<?php
declare(strict_types=1);

namespace app\common\plugin;

use app\common\service\PublishedAssetManager;
use app\common\service\PermissionSyncService;
use app\common\service\UploadDriverManager;
use RuntimeException;
use think\App;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Cache;
use think\facade\Db;
use Throwable;

/**
 * 插件管理器
 */
readonly class PluginManager
{
    private PublishedAssetManager $publishedAssetManager;

    /**
     * @param App $app
     * @param PluginRepository $repository
     * @param PluginAutoloader $autoloader
     * @param PublishedAssetManager|null $publishedAssetManager
     */
    public function __construct(
        private App                    $app,
        private PluginRepository       $repository,
        private PluginAutoloader       $autoloader,
        ?PublishedAssetManager         $publishedAssetManager = null
    )
    {
        $this->publishedAssetManager = $publishedAssetManager ?? new PublishedAssetManager();
    }

    /**
     * 获取全部插件
     * @return array<string, PluginDescriptor>
     */
    public function all(): array
    {
        return $this->repository->all();
    }

    /**
     * 获取展示行
     * @return array<int, array<string, mixed>>
     */
    public function getDisplayRows(): array
    {
        $rows = [];
        foreach ($this->all() as $descriptor) {
            $row                   = $descriptor->toArray();
            $row['installed_text'] = $descriptor->isInstalled() ? '是' : '否';
            $row['enabled_text']   = $descriptor->isEnabled() ? '是' : '否';
            $row['path_short']     = str_replace(root_path(), '', $descriptor->getPath());
            $row['title_display']  = $this->buildDisplayTitle($descriptor);
            $row['error_display']  = $this->buildDisplayError($descriptor);
            $rows[]                = $row;
        }

        $filteredRows = $this->applyHookFilter($rows, [
            'manager' => static::class,
        ]);
        if (is_array($filteredRows)) {
            $rows = $filteredRows;
        }

        return $rows;
    }

    /**
     * 插件存储是否可用
     * @return bool
     */
    public function storageReady(): bool
    {
        return $this->repository->storageReady();
    }

    /**
     * 安装插件
     * @param string $name
     * @return PluginDescriptor
     * @throws Throwable
     */
    public function install(string $name): PluginDescriptor
    {
        $descriptor = $this->requireDescriptor($name);
        if (!$descriptor->isValid()) {
            throw new RuntimeException($descriptor->getError() ?: '插件元数据无效');
        }

        $this->ensureStorageReady();
        if ($descriptor->isInstalled()) {
            throw new RuntimeException('插件已安装');
        }

        return $this->runLifecycle('install', $descriptor, function (FrameworkPluginInterface $plugin, PluginContext $context) use ($descriptor): void {
            $plugin->install($context);
            $this->persistPlugin($descriptor, [
                'title'              => $descriptor->getTitle(),
                'version'            => $descriptor->getVersion(),
                'plugin_api_version' => $descriptor->getPluginApiVersion(),
                'status'             => config('plugin.status.installed', 'installed'),
                'installed'          => 1,
                'enabled'            => 0,
                'path'               => $descriptor->getPath(),
                'provider'           => $descriptor->getProviderClass(),
                'main_class'         => $descriptor->getMainClass(),
                'dependencies_json'  => json_encode($descriptor->getDependencies(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'last_error'         => '',
                'install_time'       => time(),
                'disable_time'       => 0,
            ]);
        });
    }

    /**
     * 启用插件
     * @param string $name
     * @return PluginDescriptor
     * @throws Throwable
     */
    public function enable(string $name): PluginDescriptor
    {
        $descriptor = $this->requireDescriptor($name);
        $this->ensureStorageReady();

        if (!$descriptor->isInstalled()) {
            throw new RuntimeException('插件尚未安装');
        }

        if ($descriptor->isEnabled()) {
            throw new RuntimeException('插件已启用');
        }

        return $this->runLifecycle('enable', $descriptor, function (FrameworkPluginInterface $plugin, PluginContext $context): void {
            $this->assertDependenciesSatisfied($context->getDescriptor());
            $plugin->enable($context);
            $this->publishPublicAssets($context);
            $this->syncPluginPermissions($context);
            UploadDriverManager::clearCache();
            $this->persistPlugin($context->getDescriptor(), [
                'status'       => config('plugin.status.enabled', 'enabled'),
                'installed'    => 1,
                'enabled'      => 1,
                'enable_time'  => time(),
                'disable_time' => 0,
                'last_error'   => '',
            ]);
        });
    }

    /**
     * 禁用插件
     * @param string $name
     * @return PluginDescriptor
     * @throws Throwable
     */
    public function disable(string $name): PluginDescriptor
    {
        $descriptor = $this->requireDescriptor($name);
        $this->ensureStorageReady();

        if (!$descriptor->isEnabled()) {
            throw new RuntimeException('插件未启用');
        }

        return $this->runLifecycle('disable', $descriptor, function (FrameworkPluginInterface $plugin, PluginContext $context): void {
            $plugin->disable($context);
            $this->removePublishedPublicAssets($context);
            $this->clearPluginPermissions($context);
            UploadDriverManager::clearCache();
            $this->persistPlugin($context->getDescriptor(), [
                'status'       => config('plugin.status.disabled', 'disabled'),
                'installed'    => 1,
                'enabled'      => 0,
                'disable_time' => time(),
                'last_error'   => '',
            ]);
        });
    }

    /**
     * 升级插件
     * @param string $name
     * @return PluginDescriptor
     * @throws Throwable
     */
    public function upgrade(string $name): PluginDescriptor
    {
        $descriptor = $this->requireDescriptor($name);
        if (!$descriptor->isValid()) {
            throw new RuntimeException($descriptor->getError() ?: '插件元数据无效');
        }

        $this->ensureStorageReady();
        if (!$descriptor->isInstalled()) {
            throw new RuntimeException('插件尚未安装');
        }

        $record = $this->getStoredPluginRecord($descriptor->getName());
        if ($record === []) {
            throw new RuntimeException('插件安装记录不存在');
        }

        $fromVersion = trim((string)($record['version'] ?? ''));
        $toVersion   = trim($descriptor->getVersion());
        if ($fromVersion === '' || $toVersion === '') {
            throw new RuntimeException('插件版本信息不完整，无法升级');
        }

        if (version_compare($toVersion, $fromVersion, '=')) {
            throw new RuntimeException('插件版本未变化，无需升级');
        }

        if (version_compare($toVersion, $fromVersion, '<')) {
            throw new RuntimeException('目标版本低于当前已安装版本，当前仅支持升级到更高版本');
        }

        $wasEnabled          = (int)($record['enabled'] ?? 0) === 1;
        $previousStatus      = trim((string)($record['status'] ?? ''));
        $previousEnableTime  = (int)($record['enable_time'] ?? 0);
        $previousDisableTime = (int)($record['disable_time'] ?? 0);

        return $this->runLifecycle('upgrade', $descriptor, function (FrameworkPluginInterface $plugin, PluginContext $context) use (
            $descriptor,
            $fromVersion,
            $toVersion,
            $wasEnabled,
            $previousStatus,
            $previousEnableTime,
            $previousDisableTime
        ): void {
            if ($wasEnabled) {
                $this->assertDependenciesSatisfied($context->getDescriptor());
            }

            $plugin->upgrade($fromVersion, $toVersion, $context);

            if ($wasEnabled) {
                $this->publishPublicAssets($context);
                $this->syncPluginPermissions($context);
                UploadDriverManager::clearCache();
            }

            $this->persistPlugin($descriptor, [
                'version'            => $toVersion,
                'plugin_api_version' => $descriptor->getPluginApiVersion(),
                'status'             => $wasEnabled
                    ? config('plugin.status.enabled', 'enabled')
                    : ($previousStatus !== '' ? $previousStatus : config('plugin.status.installed', 'installed')),
                'installed'          => 1,
                'enabled'            => $wasEnabled ? 1 : 0,
                'enable_time'        => $wasEnabled ? $previousEnableTime : 0,
                'disable_time'       => $wasEnabled ? 0 : $previousDisableTime,
                'last_error'         => '',
            ]);
        });
    }

    /**
     * 发布插件静态资源
     * @param string $name
     * @return PluginDescriptor
     * @throws Throwable
     */
    public function publishAssets(string $name): PluginDescriptor
    {
        $descriptor = $this->requireDescriptor($name);

        if (!$descriptor->isValid()) {
            throw new RuntimeException($descriptor->getError() ?: '插件元数据无效');
        }

        try {
            $context = new PluginContext($this->app, $descriptor);
            $this->publishPublicAssets($context);
            $this->writeLog($descriptor->getName(), 'publish', 'success', 'Publish 成功');
        } catch (Throwable $e) {
            $this->writeLog($descriptor->getName(), 'publish', 'failed', $e->getMessage());
            throw $e;
        }

        return $descriptor;
    }

    /**
     * 清理插件操作日志
     * @param string $name
     * @return int
     * @throws DbException
     */
    public function clearLogs(string $name): int
    {
        $name = trim($name);
        if ($name === '') {
            throw new RuntimeException('缺少插件标识');
        }

        $this->ensureStorageReady();

        return Db::name($this->pluginLogTable())
            ->where('plugin_name', $name)
            ->delete();
    }

    /**
     * 卸载插件
     * @param string $name
     * @return PluginDescriptor
     * @throws Throwable
     */
    public function uninstall(string $name): PluginDescriptor
    {
        $descriptor = $this->requireDescriptor($name);
        $this->ensureStorageReady();

        if ($descriptor->isEnabled()) {
            throw new RuntimeException('请先禁用插件后再卸载');
        }

        if (!$descriptor->isInstalled()) {
            throw new RuntimeException('插件尚未安装');
        }

        return $this->runLifecycle('uninstall', $descriptor, function (FrameworkPluginInterface $plugin, PluginContext $context): void {
            $plugin->uninstall($context);
            $this->removePublishedPublicAssets($context);
            $this->clearPluginPermissions($context);
            UploadDriverManager::clearCache();
            Db::name($this->pluginTable())
                ->where('name', $context->getName())
                ->delete();
        });
    }

    /**
     * 运行生命周期
     * @param string $operation
     * @param PluginDescriptor $descriptor
     * @param callable $callback
     * @return PluginDescriptor
     * @throws Throwable
     */
    private function runLifecycle(string $operation, PluginDescriptor $descriptor, callable $callback): PluginDescriptor
    {
        $this->autoloader->register();
        $this->autoloader->addMappings($descriptor->getPsr4(), $descriptor->getPath());

        try {
            $plugin  = $this->makePlugin($descriptor);
            $context = new PluginContext($this->app, $descriptor);
            $this->dispatchHookAction('plugin.lifecycle.before', [
                'operation' => $operation,
                'name'      => $descriptor->getName(),
                'plugin'    => $descriptor->toArray(),
            ]);
            $callback($plugin, $context);

            $this->writeLog($descriptor->getName(), $operation, 'success', ucfirst($operation) . ' 成功');
            $this->flushRuntimeCache();
            $this->repository->clear();
            $refreshedDescriptor = $this->requireDescriptor($descriptor->getName());
            $this->dispatchHookAction('plugin.lifecycle.after', [
                'operation' => $operation,
                'name'      => $refreshedDescriptor->getName(),
                'plugin'    => $refreshedDescriptor->toArray(),
            ]);

            return $refreshedDescriptor;
        } catch (Throwable $e) {
            $this->markBroken($descriptor, $e->getMessage());
            $this->writeLog($descriptor->getName(), $operation, 'failed', $e->getMessage());
            $this->dispatchHookAction('plugin.lifecycle.failed', [
                'operation' => $operation,
                'name'      => $descriptor->getName(),
                'plugin'    => $descriptor->toArray(),
                'error'     => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * 实例化插件主类
     * @param PluginDescriptor $descriptor
     * @return FrameworkPluginInterface
     */
    private function makePlugin(PluginDescriptor $descriptor): FrameworkPluginInterface
    {
        $class = $descriptor->getMainClass();
        if ($class === '' || !class_exists($class)) {
            throw new RuntimeException('插件主类不存在: ' . $class);
        }

        $plugin = $this->app->make($class);
        if (!$plugin instanceof FrameworkPluginInterface) {
            throw new RuntimeException('插件主类必须实现 FrameworkPluginInterface');
        }

        return $plugin;
    }

    /**
     * 持久化插件状态
     * @param PluginDescriptor $descriptor
     * @param array $data
     * @throws DbException
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     */
    private function persistPlugin(PluginDescriptor $descriptor, array $data): void
    {
        $payload = array_merge([
            'name'               => $descriptor->getName(),
            'title'              => $descriptor->getTitle(),
            'version'            => $descriptor->getVersion(),
            'plugin_api_version' => $descriptor->getPluginApiVersion(),
            'path'               => $descriptor->getPath(),
            'provider'           => $descriptor->getProviderClass(),
            'main_class'         => $descriptor->getMainClass(),
            'update_time'        => time(),
        ], $data);

        $exists = Db::name($this->pluginTable())
            ->where('name', $descriptor->getName())
            ->find();

        if ($exists) {
            Db::name($this->pluginTable())
                ->where('name', $descriptor->getName())
                ->update($payload);
            return;
        }

        $payload['create_time'] = time();
        Db::name($this->pluginTable())->insert($payload);
    }

    /**
     * 记录日志
     * @param string $name
     * @param string $operation
     * @param string $status
     * @param string $message
     * @return void
     */
    private function writeLog(string $name, string $operation, string $status, string $message): void
    {
        if (!$this->storageReady()) {
            return;
        }

        try {
            Db::name($this->pluginLogTable())->insert([
                'plugin_name'  => $name,
                'operation'    => $operation,
                'status'       => $status,
                'message'      => $message,
                'context_json' => '{}',
                'operator_id'  => 0,
                'create_time'  => time(),
            ]);
        } catch (Throwable) {
            // 插件日志写入失败时不影响主流程。
        }
    }

    /**
     * 标记插件异常
     * @param PluginDescriptor $descriptor
     * @param string $error
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    private function markBroken(PluginDescriptor $descriptor, string $error): void
    {
        if (!$this->storageReady() || !$descriptor->isInstalled()) {
            return;
        }

        $this->persistPlugin($descriptor, [
            'status'       => config('plugin.status.broken', 'broken'),
            'enabled'      => 0,
            'last_error'   => $error,
            'disable_time' => time(),
        ]);
    }

    /**
     * 校验依赖
     * @param PluginDescriptor $descriptor
     * @return void
     */
    private function assertDependenciesSatisfied(PluginDescriptor $descriptor): void
    {
        foreach ($descriptor->getDependencies() as $dependency) {
            $dependencyName = '';
            if (is_string($dependency)) {
                $dependencyName = $dependency;
            } elseif (is_array($dependency)) {
                $dependencyName = (string)($dependency['name'] ?? '');
            }

            if ($dependencyName === '') {
                continue;
            }

            $dependencyDescriptor = $this->repository->find($dependencyName);
            if (!$dependencyDescriptor || !$dependencyDescriptor->isEnabled()) {
                throw new RuntimeException('依赖插件未启用: ' . $dependencyName);
            }
        }
    }

    /**
     * 清理运行时缓存
     * @return void
     */
    private function flushRuntimeCache(): void
    {
        try {
            Cache::delete((string)config('plugin.cache.enabled_key', 'plugin_runtime:enabled'));
        } catch (Throwable) {
            // 运行环境可能没有 runtime/cache 写权限，此处静默忽略。
        }
    }

    /**
     * 同步插件权限
     * @param PluginContext $context
     * @return void
     */
    private function syncPluginPermissions(PluginContext $context): void
    {
        $result = $this->app->make(PermissionSyncService::class)->syncPlugin(
            $context->getName(),
            $context->getPath()
        );

        if (!empty($result['error'])) {
            throw new RuntimeException('插件权限同步失败: ' . $result['error']);
        }
    }

    /**
     * 清理插件权限
     * @param PluginContext $context
     * @return void
     */
    private function clearPluginPermissions(PluginContext $context): void
    {
        $result = $this->app->make(PermissionSyncService::class)->clearPluginPermissions($context->getName());

        if (!empty($result['error'])) {
            throw new RuntimeException('插件权限清理失败: ' . $result['error']);
        }
    }

    /**
     * 需要存储已就绪
     * @return void
     */
    private function ensureStorageReady(): void
    {
        if (!$this->storageReady()) {
            throw new RuntimeException('插件数据表不存在，请先执行 sql/ 插件建表脚本');
        }
    }

    /**
     * 获取插件表名
     * @return string
     */
    private function pluginTable(): string
    {
        return (string)config('plugin.storage.plugin_table', 'admin_plugin');
    }

    /**
     * 获取插件日志表名
     * @return string
     */
    private function pluginLogTable(): string
    {
        return (string)config('plugin.storage.log_table', 'admin_plugin_log');
    }

    /**
     * 发布插件静态资源
     * @param PluginContext $context
     * @return void
     */
    private function publishPublicAssets(PluginContext $context): void
    {
        $source = $context->getPublicPath();
        if (!is_dir($source)) {
            return;
        }

        $target = $context->getPublishedPublicPath();
        $this->publishedAssetManager->publishDirectory($source, $target, [
            'symlink'                => (bool)config('plugin.asset.symlink', true),
            'directory_mode'         => 0755,
            'require_writable'       => true,
            'invalid_path_message'   => '插件静态资源目录路径无效',
            'create_failure_message' => '插件静态资源目录创建失败，请检查权限: %s',
            'not_writable_message'   => '插件静态资源目录无写权限: %s',
            'copy_failure_message'   => static fn(string $sourcePath): string => '插件静态资源复制失败: ' . $sourcePath,
        ]);
    }

    /**
     * 移除插件已发布静态资源
     * @param PluginContext $context
     * @return void
     */
    private function removePublishedPublicAssets(PluginContext $context): void
    {
        $this->publishedAssetManager->removePath($context->getPublishedPublicPath());
    }

    /**
     * 获取已存在的插件描述
     * @param string $name
     * @return PluginDescriptor
     */
    private function requireDescriptor(string $name): PluginDescriptor
    {
        $descriptor = $this->repository->find($name);
        if (!$descriptor) {
            throw new RuntimeException('插件不存在: ' . $name);
        }

        return $descriptor;
    }

    /**
     * 构建展示标题
     * @param PluginDescriptor $descriptor
     * @return string
     */
    private function buildDisplayTitle(PluginDescriptor $descriptor): string
    {
        $title = $descriptor->getTitle();

        return match ($descriptor->getStatus()) {
            'invalid' => '[无效] ' . $title,
            'broken' => '[异常] ' . $title,
            default => $title,
        };
    }

    /**
     * 构建展示错误信息
     * @param PluginDescriptor $descriptor
     * @return string
     */
    private function buildDisplayError(PluginDescriptor $descriptor): string
    {
        $error = trim($descriptor->getError());
        if ($error !== '') {
            return $error;
        }

        return match ($descriptor->getStatus()) {
            'broken' => '插件运行异常，请查看插件日志或重新启用后重试',
            'invalid' => '插件元数据或目录结构校验未通过',
            default => '-',
        };
    }

    /**
     * 获取已安装插件记录
     * @param string $name
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    private function getStoredPluginRecord(string $name): array
    {
        if (!$this->storageReady()) {
            return [];
        }

        $record = Db::name($this->pluginTable())
            ->where('name', $name)
            ->find();

        return is_array($record) ? $record : [];
    }

    /**
     * 触发插件 Action Hook
     * @param string $hook
     * @param array<string, mixed> $context
     * @return void
     */
    private function dispatchHookAction(string $hook, array $context = []): void
    {
        try {
            $this->app->make(HookManager::class)->doAction($hook, $context);
        } catch (Throwable) {
            // Hook 管理器异常时不影响主流程。
        }
    }

    /**
     * 应用插件 Filter Hook
     * @param mixed $value
     * @param array<string, mixed> $context
     * @return mixed
     */
    private function applyHookFilter(mixed $value, array $context = []): mixed
    {
        try {
            return $this->app->make(HookManager::class)->applyFilters('plugin.manager.display_rows', $value, $context);
        } catch (Throwable) {
            return $value;
        }
    }
}
