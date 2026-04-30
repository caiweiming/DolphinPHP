<?php
declare(strict_types=1);

namespace app\common\application;

use app\common\model\App as AppModel;
use app\common\service\AppService;
use app\common\service\PermissionSyncService;
use app\common\service\PublishedAssetManager;
use Closure;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use RuntimeException;
use think\App;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Db;
use Throwable;

/**
 * 应用生命周期管理器
 */
class AppLifecycleManager
{
    protected PublishedAssetManager $publishedAssetManager;

    /**
     * @param App $app
     * @param AppService $appService
     * @param PermissionSyncService $permissionSyncService
     * @param AppMetadataValidator $metadataValidator
     * @param PublishedAssetManager|null $publishedAssetManager
     */
    public function __construct(
        protected App                   $app,
        protected AppService            $appService,
        protected PermissionSyncService $permissionSyncService,
        protected AppMetadataValidator  $metadataValidator,
        ?PublishedAssetManager          $publishedAssetManager = null,
    )
    {
        $this->publishedAssetManager = $publishedAssetManager ?? new PublishedAssetManager();
    }

    /**
     * 安装应用
     * @param string $name
     * @param int $operatorId
     * @return array<string, mixed>
     * @throws Throwable
     */
    public function install(string $name, int $operatorId = 0): array
    {
        $record = $this->requireRecord($name);
        if ((int)($record['is_system'] ?? 0) === 1 && $this->isInstalledState($record)) {
            throw new RuntimeException('内置应用已处于安装状态，无需重复安装');
        }

        if ($this->isInstalledState($record) && trim((string)($record['last_error'] ?? '')) === '') {
            throw new RuntimeException('应用已安装');
        }

        $context = $this->buildContext($record);
        $this->assertDependenciesInstalled($context);

        return $this->runLifecycle('install', $record, $context, $operatorId, function (AppLifecycleContext $context): array {
            $steps   = [];
            $steps[] = $this->runSqlScript($context->getPath() . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'install.sql', 'install_sql');
            $steps[] = $this->invokeHook('install', $context);
            $steps[] = $this->syncPermissions($context->getName());
            $steps[] = $this->syncMenu($context->getName());
            $steps[] = $this->publishAssets($context);

            $now = time();
            $this->persistRecord($context->getName(), [
                'title'             => trim((string)($context->getDefinition()['title'] ?? $context->getName())),
                'description'       => trim((string)($context->getDefinition()['description'] ?? '')),
                'version'           => $context->getCurrentVersion(),
                'installed_version' => $context->getCurrentVersion(),
                'author'            => trim((string)($context->getDefinition()['author'] ?? '')),
                'provider'          => trim((string)($context->getDefinition()['provider'] ?? '')),
                'status'            => 0,
                'lifecycle_status'  => (string)config('app_package.lifecycle.installed', 'installed'),
                'last_operation'    => 'install',
                'last_error'        => '',
                'install_time'      => $now,
                'disable_time'      => $now,
                'update_time'       => $now,
            ]);

            return ['steps' => $steps];
        });
    }

    /**
     * 启用应用
     * @param string $name
     * @param int $operatorId
     * @return array<string, mixed>
     * @throws Throwable
     */
    public function enable(string $name, int $operatorId = 0): array
    {
        $record = $this->requireRecord($name);
        if ((int)($record['status'] ?? 0) === 1) {
            throw new RuntimeException('应用已启用');
        }

        if (!$this->isInstalledState($record)) {
            throw new RuntimeException('应用尚未安装，不能启用');
        }

        $context = $this->buildContext($record);
        $this->assertDependenciesEnabled($context);

        return $this->runLifecycle('enable', $record, $context, $operatorId, function (AppLifecycleContext $context): array {
            $steps   = [];
            $steps[] = $this->invokeHook('enable', $context);
            $steps[] = $this->syncPermissions($context->getName());
            $steps[] = $this->syncMenu($context->getName());
            $steps[] = $this->publishAssets($context);

            $this->persistRecord($context->getName(), [
                'status'           => 1,
                'lifecycle_status' => (string)config('app_package.lifecycle.installed', 'installed'),
                'last_operation'   => 'enable',
                'last_error'       => '',
                'enable_time'      => time(),
                'update_time'      => time(),
            ]);

            return ['steps' => $steps];
        });
    }

    /**
     * 禁用应用
     * @param string $name
     * @param int $operatorId
     * @return array<string, mixed>
     * @throws Throwable
     */
    public function disable(string $name, int $operatorId = 0): array
    {
        $record = $this->requireRecord($name);
        if ((int)($record['is_system'] ?? 0) === 1) {
            throw new RuntimeException('内置应用不允许禁用');
        }

        if ((int)($record['status'] ?? 0) !== 1) {
            throw new RuntimeException('应用未启用');
        }

        $context = $this->buildContext($record);

        return $this->runLifecycle('disable', $record, $context, $operatorId, function (AppLifecycleContext $context): array {
            $steps   = [];
            $steps[] = $this->invokeHook('disable', $context);

            $this->persistRecord($context->getName(), [
                'status'           => 0,
                'lifecycle_status' => (string)config('app_package.lifecycle.installed', 'installed'),
                'last_operation'   => 'disable',
                'last_error'       => '',
                'disable_time'     => time(),
                'update_time'      => time(),
            ]);

            return ['steps' => $steps];
        });
    }

    /**
     * 卸载应用
     * @param string $name
     * @param int $operatorId
     * @param bool $cleanupData
     * @return array<string, mixed>
     * @throws Throwable
     */
    public function uninstall(string $name, int $operatorId = 0, bool $cleanupData = false): array
    {
        $record = $this->requireRecord($name);
        if ((int)($record['is_system'] ?? 0) === 1) {
            throw new RuntimeException('内置应用不允许卸载');
        }

        if ((int)($record['status'] ?? 0) === 1) {
            throw new RuntimeException('请先禁用应用后再卸载');
        }

        if (!$this->isInstalledState($record)) {
            throw new RuntimeException('应用尚未安装');
        }

        $context = $this->buildContext($record, ['cleanup_data' => $cleanupData]);

        return $this->runLifecycle('uninstall', $record, $context, $operatorId, function (AppLifecycleContext $context): array {
            $steps   = [];
            $steps[] = $this->invokeHook('uninstall', $context);
            if ($context->getOption('cleanup_data', false)) {
                $steps[] = $this->runSqlScript($context->getPath() . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'uninstall.sql', 'uninstall_sql');
            }
            $steps[] = $this->clearPermissions($context->getName());
            $steps[] = $this->removePublishedAssets($context);

            $this->persistRecord($context->getName(), [
                'status'            => 0,
                'lifecycle_status'  => (string)config('app_package.lifecycle.imported', 'imported'),
                'installed_version' => '',
                'last_operation'    => 'uninstall',
                'last_error'        => '',
                'disable_time'      => time(),
                'update_time'       => time(),
            ]);

            return ['steps' => $steps];
        });
    }

    /**
     * 升级应用
     * @param string $name
     * @param int $operatorId
     * @return array<string, mixed>
     * @throws Throwable
     */
    public function upgrade(string $name, int $operatorId = 0): array
    {
        $record = $this->requireRecord($name);
        if (!$this->isInstalledState($record)) {
            throw new RuntimeException('应用尚未安装，不能升级');
        }

        $context     = $this->buildContext($record);
        $fromVersion = trim((string)($record['installed_version'] ?? ''));
        $toVersion   = $context->getCurrentVersion();

        if ($fromVersion === '' || $toVersion === '') {
            throw new RuntimeException('应用版本信息不完整，无法升级');
        }

        if (version_compare($toVersion, $fromVersion, '=')) {
            throw new RuntimeException('当前版本未变化，无需升级');
        }

        if (version_compare($toVersion, $fromVersion, '<')) {
            throw new RuntimeException('目标版本低于当前已安装版本，当前仅支持升级到更高版本');
        }

        if ((int)($record['status'] ?? 0) === 1) {
            $this->assertDependenciesEnabled($context);
        } else {
            $this->assertDependenciesInstalled($context);
        }

        return $this->runLifecycle('upgrade', $record, $context, $operatorId, function (AppLifecycleContext $context, array $record) use ($fromVersion, $toVersion): array {
            $steps   = [];
            $steps[] = $this->runUpgradeScript($context, $fromVersion, $toVersion);
            $steps[] = $this->invokeHook('upgrade', $context, $fromVersion, $toVersion);
            $steps[] = $this->syncPermissions($context->getName());
            $steps[] = $this->syncMenu($context->getName());
            $steps[] = $this->publishAssets($context);

            $this->persistRecord($context->getName(), [
                'version'           => $toVersion,
                'installed_version' => $toVersion,
                'lifecycle_status'  => (string)config('app_package.lifecycle.installed', 'installed'),
                'last_operation'    => 'upgrade',
                'last_error'        => '',
                'status'            => (int)($record['status'] ?? 0) === 1 ? 1 : 0,
                'update_time'       => time(),
            ]);

            return ['steps' => $steps, 'from_version' => $fromVersion, 'to_version' => $toVersion];
        });
    }

    /**
     * 清理应用日志
     * @param string $name
     * @return int
     */
    public function clearLogs(string $name): int
    {
        $name = trim($name);
        if ($name === '') {
            throw new RuntimeException('缺少应用标识');
        }

        try {
            return Db::name((string)config('app_package.storage.log_table', 'admin_app_log'))
                ->where('app_name', $name)
                ->delete();
        } catch (Throwable $e) {
            throw new RuntimeException('清理应用日志失败：' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 运行生命周期
     * @param string $operation
     * @param array<string, mixed> $record
     * @param AppLifecycleContext $context
     * @param int $operatorId
     * @param callable $callback
     * @return array<string, mixed>
     * @throws Throwable
     */
    private function runLifecycle(string $operation, array $record, AppLifecycleContext $context, int $operatorId, callable $callback): array
    {
        $name    = trim((string)($record['name'] ?? ''));
        $payload = [
            'app'               => $name,
            'operation'         => $operation,
            'lifecycle_status'  => (string)($record['lifecycle_status'] ?? ''),
            'current_version'   => $context->getCurrentVersion(),
            'installed_version' => $context->getInstalledVersion(),
        ];

        try {
            $result = Db::transaction(function () use ($callback, $context, $record): array {
                return (array)$callback($context, $record);
            });

            $this->writeLog($name, $operation, 'success', ucfirst($operation) . ' 成功', $payload + $result, $operatorId);
            $this->appService->clearCache($name);

            return $result;
        } catch (Throwable $e) {
            $this->markFailed($name, $operation, $e->getMessage());
            $this->writeLog($name, $operation, 'failed', $e->getMessage(), $payload, $operatorId);
            $this->appService->clearCache($name);
            throw $e;
        }
    }

    /**
     * 构建生命周期上下文
     * @param array<string, mixed> $record
     * @param array<string, mixed> $options
     * @return AppLifecycleContext
     */
    private function buildContext(array $record, array $options = []): AppLifecycleContext
    {
        $name = trim((string)($record['name'] ?? ''));
        if ($name === '') {
            throw new RuntimeException('应用标识不能为空');
        }

        $path = app()->getBasePath() . $name;
        if (!is_dir($path)) {
            throw new RuntimeException('应用目录不存在');
        }

        $manifest = $this->appService->getAppManifest($name);
        $error    = $this->metadataValidator->validate($manifest, $path);
        if ($error !== '') {
            throw new RuntimeException($error);
        }

        $definition = $this->appService->getAppDefinition($name);

        return new AppLifecycleContext(
            $this->app,
            $name,
            $path,
            $record,
            $manifest,
            $definition,
            $options,
        );
    }

    /**
     * 获取应用记录
     * @param string $name
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    private function requireRecord(string $name): array
    {
        $name = trim($name);
        if ($name === '') {
            throw new RuntimeException('缺少应用标识');
        }

        $record = AppModel::where('name', $name)->find();
        if (!$record instanceof AppModel) {
            throw new RuntimeException('应用不存在');
        }

        return $record->toArray();
    }

    /**
     * 判断是否处于已安装态
     * @param array<string, mixed> $record
     * @return bool
     */
    private function isInstalledState(array $record): bool
    {
        return trim((string)($record['lifecycle_status'] ?? ''))
            === (string)config('app_package.lifecycle.installed', 'installed');
    }

    /**
     * 校验依赖已安装
     * @param AppLifecycleContext $context
     * @return void
     */
    private function assertDependenciesInstalled(AppLifecycleContext $context): void
    {
        foreach ((array)($context->getManifest()['dependencies'] ?? []) as $dependency) {
            $dependency = trim((string)$dependency);
            if ($dependency === '') {
                continue;
            }

            if (!$this->appService->isAppInstalled($dependency)) {
                throw new RuntimeException('依赖应用尚未安装：' . $dependency);
            }
        }
    }

    /**
     * 校验依赖已启用
     * @param AppLifecycleContext $context
     * @return void
     */
    private function assertDependenciesEnabled(AppLifecycleContext $context): void
    {
        foreach ((array)($context->getManifest()['dependencies'] ?? []) as $dependency) {
            $dependency = trim((string)$dependency);
            if ($dependency === '') {
                continue;
            }

            if (!$this->appService->isAppInstalled($dependency)) {
                throw new RuntimeException('依赖应用尚未安装：' . $dependency);
            }

            if (!$this->appService->isAppEnabled($dependency)) {
                throw new RuntimeException('依赖应用未启用：' . $dependency);
            }
        }
    }

    /**
     * 同步应用权限
     * @param string $name
     * @return array<string, mixed>
     */
    private function syncPermissions(string $name): array
    {
        $result = $this->permissionSyncService->sync($name, [
            'incremental' => true,
            'dryRun'      => false,
        ]);

        if (!empty($result['error'])) {
            throw new RuntimeException('应用权限同步失败：' . $result['error']);
        }

        return [
            'step'         => 'permission_sync',
            'added'        => (int)($result['added'] ?? 0),
            'menu_count'   => (int)($result['menuCount'] ?? 0),
            'button_count' => (int)($result['buttonCount'] ?? 0),
        ];
    }

    /**
     * 同步应用顶级菜单
     * @param string $name
     * @return string[]
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    private function syncMenu(string $name): array
    {
        $this->permissionSyncService->syncAppMenu($name);

        return [
            'step'    => 'menu_sync',
            'message' => '应用菜单同步完成',
        ];
    }

    /**
     * 清理应用权限
     * @param string $name
     * @return array<string, mixed>
     */
    private function clearPermissions(string $name): array
    {
        $result = $this->permissionSyncService->clearAppPermissions($name);
        if (!empty($result['error'])) {
            throw new RuntimeException('应用权限清理失败：' . $result['error']);
        }

        return [
            'step'       => 'permission_clear',
            'deleted'    => (int)($result['deleted'] ?? 0),
            'menu_count' => (int)($result['menuCount'] ?? 0),
        ];
    }

    /**
     * 发布静态资源
     * @param AppLifecycleContext $context
     * @return array<string, mixed>
     */
    private function publishAssets(AppLifecycleContext $context): array
    {
        if (!config('app_package.publish.enabled', true)) {
            return [
                'step'    => 'asset_publish',
                'message' => '资源发布已关闭，跳过',
            ];
        }

        $source = rtrim($context->getPath(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR
            . trim((string)config('app_package.publish.source_dir', 'public'), DIRECTORY_SEPARATOR);
        if (!is_dir($source)) {
            return [
                'step'    => 'asset_publish',
                'message' => '应用未提供 public 资源目录，跳过',
            ];
        }

        $targetRoot = rtrim((string)config('app_package.publish.target_root', public_path() . 'apps'), DIRECTORY_SEPARATOR);
        $target     = $targetRoot . DIRECTORY_SEPARATOR . $context->getName();
        $linked     = $this->publishedAssetManager->publishDirectory($source, $target, [
            'symlink'                => (bool)config('app_package.publish.symlink', true),
            'directory_mode'         => 0775,
            'invalid_path_message'   => '目录路径无效',
            'create_failure_message' => '目录创建失败：%s',
            'copy_failure_message'   => '应用资源发布失败',
        ]);

        if ($linked) {
            return [
                'step'    => 'asset_publish',
                'message' => '应用资源已通过软链接发布',
                'target'  => $target,
            ];
        }

        return [
            'step'    => 'asset_publish',
            'message' => '应用资源已复制发布',
            'target'  => $target,
        ];
    }

    /**
     * 移除静态资源
     * @param AppLifecycleContext $context
     * @return array<string, mixed>
     */
    private function removePublishedAssets(AppLifecycleContext $context): array
    {
        $targetRoot = rtrim((string)config('app_package.publish.target_root', public_path() . 'apps'), DIRECTORY_SEPARATOR);
        $target     = $targetRoot . DIRECTORY_SEPARATOR . $context->getName();
        $this->publishedAssetManager->removePath($target);

        return [
            'step'    => 'asset_remove',
            'message' => '应用资源已移除',
            'target'  => $target,
        ];
    }

    /**
     * 执行升级脚本
     * @param AppLifecycleContext $context
     * @param string $fromVersion
     * @param string $toVersion
     * @return array<string, mixed>
     */
    private function runUpgradeScript(AppLifecycleContext $context, string $fromVersion, string $toVersion): array
    {
        $basePath = $context->getPath() . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR;
        $specific = $basePath . 'upgrades' . DIRECTORY_SEPARATOR . $fromVersion . '_to_' . $toVersion . '.sql';
        if (is_file($specific)) {
            return $this->runSqlScript($specific, 'upgrade_sql');
        }

        return $this->runSqlScript($basePath . 'upgrade.sql', 'upgrade_sql');
    }

    /**
     * 执行 SQL 脚本
     * @param string $path
     * @param string $step
     * @return array<string, mixed>
     */
    private function runSqlScript(string $path, string $step): array
    {
        if (!is_file($path)) {
            return [
                'step'    => $step,
                'message' => '未提供 SQL 脚本，跳过',
            ];
        }

        $contents = trim((string)file_get_contents($path));
        if ($contents === '') {
            return [
                'step'    => $step,
                'message' => 'SQL 脚本为空，跳过',
            ];
        }

        $statements = $this->splitSqlStatements($contents);
        foreach ($statements as $statement) {
            if ($statement === '') {
                continue;
            }

            Db::execute($statement);
        }

        return [
            'step'       => $step,
            'message'    => 'SQL 脚本执行完成',
            'statements' => count($statements),
            'path'       => $path,
        ];
    }

    /**
     * 调用生命周期钩子
     * @param string $hook
     * @param AppLifecycleContext $context
     * @param string|null $fromVersion
     * @param string|null $toVersion
     * @return array<string, mixed>
     * @throws ReflectionException
     */
    private function invokeHook(string $hook, AppLifecycleContext $context, ?string $fromVersion = null, ?string $toVersion = null): array
    {
        $callable = $context->getDefinition()[$hook] ?? null;
        if ($callable === null || $callable === '') {
            return [
                'step'    => $hook . '_hook',
                'message' => '未定义生命周期钩子，跳过',
            ];
        }

        $resolved = $this->resolveCallable($callable);
        if (!is_callable($resolved)) {
            throw new RuntimeException('生命周期钩子不可调用：' . $hook);
        }

        $arguments = $hook === 'upgrade'
            ? [$fromVersion, $toVersion, $context]
            : [$context];
        $this->invokeCallable($resolved, array_values(array_filter($arguments, static fn(mixed $item): bool => $item !== null)));

        return [
            'step'    => $hook . '_hook',
            'message' => '生命周期钩子执行完成',
        ];
    }

    /**
     * 解析可调用对象
     * @param mixed $callable
     * @return callable|array|string|object|null
     */
    private function resolveCallable(mixed $callable): mixed
    {
        if (is_array($callable) || $callable instanceof Closure || is_object($callable)) {
            return $callable;
        }

        if (!is_string($callable)) {
            return null;
        }

        $callable = trim($callable);
        if ($callable === '') {
            return null;
        }

        if (str_contains($callable, '@')) {
            [$class, $method] = array_map('trim', explode('@', $callable, 2));
            return [$this->app->make($class), $method];
        }

        if (str_contains($callable, '::')) {
            [$class, $method] = array_map('trim', explode('::', $callable, 2));
            return [class_exists($class) ? $this->app->make($class) : $class, $method];
        }

        if (class_exists($callable)) {
            return $this->app->make($callable);
        }

        return $callable;
    }

    /**
     * 自适应调用
     * @param callable|array|string|object $callable
     * @param array<int, mixed> $arguments
     * @return void
     * @throws ReflectionException
     */
    private function invokeCallable(mixed $callable, array $arguments): void
    {
        if (is_array($callable)) {
            $reflection = new ReflectionMethod($callable[0], (string)$callable[1]);
            $reflection->invokeArgs($callable[0], array_slice($arguments, 0, $reflection->getNumberOfParameters()));
            return;
        }

        if (is_string($callable) && function_exists($callable)) {
            $reflection = new ReflectionFunction($callable);
            $reflection->invokeArgs(array_slice($arguments, 0, $reflection->getNumberOfParameters()));
            return;
        }

        if ($callable instanceof Closure) {
            $reflection = new ReflectionFunction($callable);
            $reflection->invokeArgs(array_slice($arguments, 0, $reflection->getNumberOfParameters()));
            return;
        }

        if (is_object($callable) && is_callable($callable)) {
            $reflection = new ReflectionMethod($callable, '__invoke');
            $reflection->invokeArgs($callable, array_slice($arguments, 0, $reflection->getNumberOfParameters()));
            return;
        }

        throw new RuntimeException('生命周期钩子不可调用');
    }

    /**
     * 标记失败
     * @param string $name
     * @param string $operation
     * @param string $message
     * @return void
     */
    private function markFailed(string $name, string $operation, string $message): void
    {
        try {
            $record  = $this->requireRecord($name);
            $payload = [
                'last_operation' => $operation,
                'last_error'     => $message,
                'update_time'    => time(),
            ];

            if (in_array($operation, ['install', 'enable', 'upgrade'], true)) {
                $payload['lifecycle_status'] = (string)config('app_package.lifecycle.broken', 'broken');
                $payload['status']           = 0;
                $payload['disable_time']     = time();
            } elseif ((int)($record['status'] ?? 0) !== 1) {
                $payload['disable_time'] = time();
            }

            $this->persistRecord($name, $payload);
        } catch (Throwable) {
            // 写失败状态不应覆盖原始异常。
        }
    }

    /**
     * 持久化应用记录
     * @param string $name
     * @param array<string, mixed> $payload
     * @return void
     */
    private function persistRecord(string $name, array $payload): void
    {
        AppModel::where('name', $name)->update($payload);
    }

    /**
     * 写应用日志
     * @param string $name
     * @param string $operation
     * @param string $status
     * @param string $message
     * @param array<string, mixed> $context
     * @param int $operatorId
     * @return void
     */
    private function writeLog(string $name, string $operation, string $status, string $message, array $context, int $operatorId): void
    {
        try {
            Db::name((string)config('app_package.storage.log_table', 'admin_app_log'))->insert([
                'app_name'     => $name,
                'operation'    => $operation,
                'status'       => $status,
                'message'      => $message,
                'context_json' => $this->encodeJson($context),
                'operator_id'  => max(0, $operatorId),
                'create_time'  => time(),
            ]);
        } catch (Throwable) {
            // 日志写入失败时不影响主流程。
        }
    }

    /**
     * 编码 JSON
     * @param array<string, mixed> $value
     * @return string
     */
    private function encodeJson(array $value): string
    {
        try {
            $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return $encoded === false ? '{}' : $encoded;
        } catch (Throwable) {
            return '{}';
        }
    }

    /**
     * 拆分 SQL 语句
     * @param string $sql
     * @return array<int, string>
     */
    private function splitSqlStatements(string $sql): array
    {
        $statements = [];
        $buffer     = '';
        $quote      = '';
        $length     = strlen($sql);

        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];
            $next = $i + 1 < $length ? $sql[$i + 1] : '';

            if ($quote === '' && $char === '-' && $next === '-') {
                while ($i < $length && !in_array($sql[$i], ["\n", "\r"], true)) {
                    $i++;
                }
                continue;
            }

            if ($quote === '' && $char === '#') {
                while ($i < $length && !in_array($sql[$i], ["\n", "\r"], true)) {
                    $i++;
                }
                continue;
            }

            if ($quote === '' && $char === '/' && $next === '*') {
                $i += 2;
                while ($i < $length - 1 && !($sql[$i] === '*' && $sql[$i + 1] === '/')) {
                    $i++;
                }
                $i++;
                continue;
            }

            if (($char === '\'' || $char === '"') && ($i === 0 || $sql[$i - 1] !== '\\')) {
                $quote = $quote === $char ? '' : ($quote === '' ? $char : $quote);
            }

            if ($char === ';' && $quote === '') {
                $statement = trim($buffer);
                if ($statement !== '') {
                    $statements[] = $statement;
                }
                $buffer = '';
                continue;
            }

            $buffer .= $char;
        }

        $buffer = trim($buffer);
        if ($buffer !== '') {
            $statements[] = $buffer;
        }

        return $statements;
    }

}
