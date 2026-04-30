<?php
declare(strict_types=1);

namespace app\common\application;

use think\App;

/**
 * 应用生命周期上下文
 */
readonly class AppLifecycleContext
{
    /**
     * @param App $app
     * @param string $name
     * @param string $path
     * @param array<string, mixed> $record
     * @param array<string, mixed> $manifest
     * @param array<string, mixed> $definition
     * @param array<string, mixed> $options
     */
    public function __construct(
        private App    $app,
        private string $name,
        private string $path,
        private array  $record,
        private array  $manifest,
        private array  $definition,
        private array  $options = [],
    )
    {
    }

    /**
     * 获取容器
     * @return App
     */
    public function getApp(): App
    {
        return $this->app;
    }

    /**
     * 获取应用标识
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * 获取应用目录
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * 获取注册表记录
     * @return array<string, mixed>
     */
    public function getRecord(): array
    {
        return $this->record;
    }

    /**
     * 获取静态元数据
     * @return array<string, mixed>
     */
    public function getManifest(): array
    {
        return $this->manifest;
    }

    /**
     * 获取合成定义
     * @return array<string, mixed>
     */
    public function getDefinition(): array
    {
        return $this->definition;
    }

    /**
     * 获取选项
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getOption(string $key, mixed $default = null): mixed
    {
        return $this->options[$key] ?? $default;
    }

    /**
     * 当前磁盘版本
     * @return string
     */
    public function getCurrentVersion(): string
    {
        return trim((string)($this->manifest['version'] ?? $this->definition['version'] ?? ''));
    }

    /**
     * 最近已安装版本
     * @return string
     */
    public function getInstalledVersion(): string
    {
        return trim((string)($this->record['installed_version'] ?? ''));
    }
}
