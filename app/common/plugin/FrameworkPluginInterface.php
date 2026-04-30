<?php
declare(strict_types=1);

namespace app\common\plugin;

/**
 * 框架插件生命周期接口
 */
interface FrameworkPluginInterface
{
    /**
     * 安装插件
     * @param PluginContext $context
     * @return void
     */
    public function install(PluginContext $context): void;

    /**
     * 启用插件
     * @param PluginContext $context
     * @return void
     */
    public function enable(PluginContext $context): void;

    /**
     * 禁用插件
     * @param PluginContext $context
     * @return void
     */
    public function disable(PluginContext $context): void;

    /**
     * 卸载插件
     * @param PluginContext $context
     * @return void
     */
    public function uninstall(PluginContext $context): void;

    /**
     * 升级插件
     * @param string $fromVersion
     * @param string $toVersion
     * @param PluginContext $context
     * @return void
     */
    public function upgrade(string $fromVersion, string $toVersion, PluginContext $context): void;
}
