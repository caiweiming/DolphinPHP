<?php
declare(strict_types=1);

namespace app\common\plugin;

use think\App;

/**
 * 插件上下文对象
 */
readonly class PluginContext
{
    /**
     * @param App $app
     * @param PluginDescriptor $descriptor
     */
    public function __construct(
        private App              $app,
        private PluginDescriptor $descriptor
    )
    {
    }

    /**
     * 获取应用实例
     * @return App
     */
    public function getApp(): App
    {
        return $this->app;
    }

    /**
     * 获取插件描述对象
     * @return PluginDescriptor
     */
    public function getDescriptor(): PluginDescriptor
    {
        return $this->descriptor;
    }

    /**
     * 获取插件名
     * @return string
     */
    public function getName(): string
    {
        return $this->descriptor->getName();
    }

    /**
     * 获取插件 Vendor
     * @return string
     */
    public function getVendor(): string
    {
        return $this->descriptor->getVendor();
    }

    /**
     * 获取插件短名称
     * @return string
     */
    public function getShortName(): string
    {
        return $this->descriptor->getShortName();
    }

    /**
     * 获取插件根目录
     * @return string
     */
    public function getPath(): string
    {
        return $this->descriptor->getPath();
    }

    /**
     * 获取插件配置目录
     * @return string
     */
    public function getConfigPath(): string
    {
        return rtrim($this->getPath(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'config';
    }

    /**
     * 获取插件视图目录
     * @return string
     */
    public function getViewPath(): string
    {
        return rtrim($this->getPath(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'view';
    }

    /**
     * 获取插件语言包目录
     * @return string
     */
    public function getLangPath(): string
    {
        return rtrim($this->getPath(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'lang';
    }

    /**
     * 获取插件 public 目录
     * @return string
     */
    public function getPublicPath(): string
    {
        return rtrim($this->getPath(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'public';
    }

    /**
     * 获取插件发布后的 public 目录
     * @return string
     */
    public function getPublishedPublicPath(): string
    {
        return rtrim((string)config('plugin.asset.public_root', public_path() . 'plugins'), DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . $this->getVendor()
            . DIRECTORY_SEPARATOR
            . $this->getShortName();
    }

    /**
     * 获取插件静态资源 URL 前缀
     * @param string $path
     * @return string
     */
    public function getAssetUrl(string $path = ''): string
    {
        $base = rtrim($this->app->request->rootUrl(), '/');
        $url  = $base . '/'
            . trim((string)config('plugin.asset.url_prefix', 'plugins'), '/')
            . '/'
            . $this->getVendor()
            . '/'
            . $this->getShortName();

        if ($path === '') {
            return $url;
        }

        return $url . '/' . ltrim($path, '/');
    }
}
