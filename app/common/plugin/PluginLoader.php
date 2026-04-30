<?php
declare(strict_types=1);

namespace app\common\plugin;

use RuntimeException;
use think\App;
use think\facade\Cache;
use think\facade\Db;
use Throwable;

/**
 * 插件运行时加载器
 */
class PluginLoader
{
    /**
     * @var bool
     */
    private bool $loaded = false;

    /**
     * @param App $app
     * @param PluginRepository $repository
     * @param PluginAutoloader $autoloader
     */
    public function __construct(
        private readonly App              $app,
        private readonly PluginRepository $repository,
        private readonly PluginAutoloader $autoloader
    )
    {
    }

    /**
     * 装载已启用插件
     * @return void
     */
    public function loadEnabled(): void
    {
        if ($this->loaded || !config('plugin.auto_load', true)) {
            return;
        }

        $this->autoloader->register();
        $enabled     = $this->repository->enabled();
        $loadedNames = [];
        $pending     = $enabled;
        $failed      = [];

        while ($pending !== []) {
            $progress = false;

            foreach ($pending as $name => $descriptor) {
                $dependencyState = $this->resolveLoadableDependencies($descriptor, $loadedNames, array_keys($pending), array_keys($failed));
                if ($dependencyState['status'] === 'wait') {
                    continue;
                }

                unset($pending[$name]);
                $progress = true;

                if ($dependencyState['status'] === 'fail') {
                    $failed[$name] = $dependencyState['message'];
                    $this->recordLoadFailure($descriptor, $dependencyState['message']);
                    continue;
                }

                try {
                    $this->autoloader->addMappings($descriptor->getPsr4(), $descriptor->getPath());

                    $provider = $descriptor->getProviderClass();
                    if ($provider === '' || !class_exists($provider)) {
                        throw new RuntimeException('插件服务提供者不存在: ' . $provider);
                    }

                    $this->app->register($provider);
                    $service = $this->app->getService($provider);
                    if ($service) {
                        $this->app->bootService($service);
                    }
                    $this->dispatchHookAction('plugin.runtime.loaded', [
                        'name'     => $descriptor->getName(),
                        'plugin'   => $descriptor->toArray(),
                        'provider' => $provider,
                    ]);
                    $loadedNames[] = $descriptor->getName();
                } catch (Throwable $e) {
                    $failed[$name] = $e->getMessage();
                    $this->recordLoadFailure($descriptor, $e->getMessage());
                    continue;
                }
            }

            if ($progress) {
                continue;
            }

            foreach ($pending as $name => $descriptor) {
                $message       = '检测到插件依赖循环或无可推进的装载顺序';
                $failed[$name] = $message;
                $this->recordLoadFailure($descriptor, $message);
            }
            break;
        }

        foreach ($enabled as $descriptor) {
            if (!in_array($descriptor->getName(), $loadedNames, true) && !isset($failed[$descriptor->getName()])) {
                $this->recordLoadFailure($descriptor, '插件未被装载，但未返回明确错误');
            }
        }

        try {
            Cache::set(
                (string)config('plugin.cache.enabled_key', 'plugin_runtime:enabled'),
                $loadedNames,
                (int)config('plugin.cache.ttl', 300)
            );
        } catch (Throwable) {
            // 运行环境可能没有 runtime/cache 写权限，此处静默降级为无缓存模式。
        }

        $this->loaded = true;
    }

    /**
     * 解析依赖装载状态
     * @param PluginDescriptor $descriptor
     * @param array<int, string> $loadedNames
     * @param array<int, string> $pendingNames
     * @param array<int, string> $failedNames
     * @return array{status:string,message:string}
     */
    private function resolveLoadableDependencies(PluginDescriptor $descriptor, array $loadedNames, array $pendingNames, array $failedNames): array
    {
        $dependencies = $this->extractDependencyNames($descriptor);
        foreach ($dependencies as $dependencyName) {
            if (in_array($dependencyName, $failedNames, true)) {
                return [
                    'status'  => 'fail',
                    'message' => '依赖插件装载失败: ' . $dependencyName,
                ];
            }

            if (in_array($dependencyName, $loadedNames, true)) {
                continue;
            }

            if (in_array($dependencyName, $pendingNames, true)) {
                return [
                    'status'  => 'wait',
                    'message' => '',
                ];
            }

            return [
                'status'  => 'fail',
                'message' => '依赖插件未启用或不存在: ' . $dependencyName,
            ];
        }

        return [
            'status'  => 'ready',
            'message' => '',
        ];
    }

    /**
     * 提取依赖插件名
     * @param PluginDescriptor $descriptor
     * @return array<int, string>
     */
    private function extractDependencyNames(PluginDescriptor $descriptor): array
    {
        $names = [];
        foreach ($descriptor->getDependencies() as $dependency) {
            if (is_string($dependency) && $dependency !== '') {
                $names[] = $dependency;
                continue;
            }

            if (is_array($dependency) && !empty($dependency['name']) && is_string($dependency['name'])) {
                $names[] = $dependency['name'];
            }
        }

        return array_values(array_unique($names));
    }

    /**
     * 记录装载失败
     * @param PluginDescriptor $descriptor
     * @param string $message
     * @return void
     */
    private function recordLoadFailure(PluginDescriptor $descriptor, string $message): void
    {
        $this->dispatchHookAction('plugin.runtime.load_failed', [
            'name'   => $descriptor->getName(),
            'plugin' => $descriptor->toArray(),
            'error'  => $message,
        ]);

        try {
            trace('插件装载失败 [' . $descriptor->getName() . ']: ' . $message, 'error');
        } catch (Throwable) {
        }

        if (!$this->repository->storageReady()) {
            return;
        }

        try {
            $exists = Db::name((string)config('plugin.storage.plugin_table', 'admin_plugin'))
                ->where('name', $descriptor->getName())
                ->find();
            if ($exists) {
                Db::name((string)config('plugin.storage.plugin_table', 'admin_plugin'))
                    ->where('name', $descriptor->getName())
                    ->update([
                        'status'       => config('plugin.status.broken', 'broken'),
                        'enabled'      => 0,
                        'last_error'   => $message,
                        'disable_time' => time(),
                        'update_time'  => time(),
                    ]);
            }

            Db::name((string)config('plugin.storage.log_table', 'admin_plugin_log'))->insert([
                'plugin_name'  => $descriptor->getName(),
                'operation'    => 'runtime_load',
                'status'       => 'failed',
                'message'      => $message,
                'context_json' => '{}',
                'operator_id'  => 0,
                'create_time'  => time(),
            ]);
        } catch (Throwable) {
            // 装载失败记录失败时不再抛出，避免影响主系统启动。
        }
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
            // Hook 管理器异常时不影响插件装载流程。
        }
    }
}
