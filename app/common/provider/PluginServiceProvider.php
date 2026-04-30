<?php
declare(strict_types=1);

namespace app\common\provider;

use app\common\command\PluginDisable;
use app\common\command\PluginEnable;
use app\common\command\PluginInstall;
use app\common\command\PluginClearLogs;
use app\common\command\PluginList;
use app\common\command\PluginPublish;
use app\common\command\PluginUninstall;
use app\common\command\PluginUpgrade;
use app\common\command\MakePlugin;
use app\common\plugin\HookManager;
use app\common\plugin\PluginMetadataValidator;
use app\common\plugin\PluginAutoloader;
use app\common\plugin\PluginLoader;
use app\common\plugin\PluginManager;
use app\common\plugin\PluginPackageBuilder;
use app\common\plugin\PluginPackageImporter;
use app\common\plugin\PluginRepository;
use app\common\plugin\PluginRegistry;
use app\install\service\InstallStateService;
use think\Service;

/**
 * 插件服务提供者
 */
class PluginServiceProvider extends Service
{
    /**
     * 注册服务
     * @return void
     */
    public function register(): void
    {
        $this->app->bind(PluginAutoloader::class, PluginAutoloader::class);
        $this->app->bind(PluginMetadataValidator::class, PluginMetadataValidator::class);
        $this->app->bind(PluginRepository::class, PluginRepository::class);
        $this->app->bind(PluginRegistry::class, PluginRegistry::class);
        $this->app->bind(HookManager::class, HookManager::class);
        $this->app->bind(PluginLoader::class, PluginLoader::class);
        $this->app->bind(PluginManager::class, PluginManager::class);
        $this->app->bind(PluginPackageBuilder::class, PluginPackageBuilder::class);
        $this->app->bind(PluginPackageImporter::class, PluginPackageImporter::class);
        $this->app->bind('plugin.manager', PluginManager::class);
        $this->app->bind('plugin.registry', PluginRegistry::class);
        $this->app->bind('plugin.hooks', HookManager::class);
    }

    /**
     * 启动服务
     * @return void
     */
    public function boot(): void
    {
        if (app(InstallStateService::class)->isInstalled()) {
            $loader = app(PluginLoader::class);
            $loader->loadEnabled();
        }

        if ($this->app->runningInConsole()) {
            $pluginCommands = $this->app->make(PluginRegistry::class)->getCommandClasses();
            $this->commands([
                MakePlugin::class,
                PluginList::class,
                PluginInstall::class,
                PluginEnable::class,
                PluginDisable::class,
                PluginClearLogs::class,
                PluginPublish::class,
                PluginUpgrade::class,
                PluginUninstall::class,
                ...$pluginCommands,
            ]);
        }
    }
}
