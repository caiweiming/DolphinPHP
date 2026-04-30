<?php
declare(strict_types=1);

namespace app\common\plugin;

/**
 * 插件描述对象
 */
readonly class PluginDescriptor
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        private array $data
    )
    {
    }

    /**
     * 创建实例
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    /**
     * 获取插件名称
     * @return string
     */
    public function getName(): string
    {
        return (string)($this->data['name'] ?? '');
    }

    /**
     * 获取插件 Vendor
     * @return string
     */
    public function getVendor(): string
    {
        return $this->getNameParts()[0] ?? '';
    }

    /**
     * 获取插件短名称
     * @return string
     */
    public function getShortName(): string
    {
        return $this->getNameParts()[1] ?? '';
    }

    /**
     * 获取标题
     * @return string
     */
    public function getTitle(): string
    {
        return (string)($this->data['title'] ?? $this->getName());
    }

    /**
     * 获取描述
     * @return string
     */
    public function getDescription(): string
    {
        return (string)($this->data['description'] ?? '');
    }

    /**
     * 获取版本
     * @return string
     */
    public function getVersion(): string
    {
        return (string)($this->data['version'] ?? '');
    }

    /**
     * 获取插件协议版本
     * @return string
     */
    public function getPluginApiVersion(): string
    {
        return (string)($this->data['plugin_api_version'] ?? '');
    }

    /**
     * 获取作者
     * @return string
     */
    public function getAuthor(): string
    {
        return (string)($this->data['author'] ?? '');
    }

    /**
     * 获取插件根路径
     * @return string
     */
    public function getPath(): string
    {
        return (string)($this->data['path'] ?? '');
    }

    /**
     * 获取服务提供者类
     * @return string
     */
    public function getProviderClass(): string
    {
        return (string)($this->data['provider'] ?? '');
    }

    /**
     * 获取主类
     * @return string
     */
    public function getMainClass(): string
    {
        return (string)($this->data['main'] ?? '');
    }

    /**
     * 获取自动加载配置
     * @return array<string, mixed>
     */
    public function getAutoload(): array
    {
        return (array)($this->data['autoload'] ?? []);
    }

    /**
     * 获取 PSR-4 映射
     * @return array<string, string>
     */
    public function getPsr4(): array
    {
        return (array)($this->getAutoload()['psr-4'] ?? []);
    }

    /**
     * 获取依赖
     * @return array<int, mixed>
     */
    public function getDependencies(): array
    {
        return (array)($this->data['dependencies'] ?? []);
    }

    /**
     * 获取 require 配置
     * @return array<string, mixed>
     */
    public function getRequire(): array
    {
        return (array)($this->data['require'] ?? []);
    }

    /**
     * 获取状态
     * @return string
     */
    public function getStatus(): string
    {
        return (string)($this->data['status'] ?? config('plugin.status.discovered', 'discovered'));
    }

    /**
     * 是否已安装
     * @return bool
     */
    public function isInstalled(): bool
    {
        return (bool)($this->data['installed'] ?? false);
    }

    /**
     * 是否已启用
     * @return bool
     */
    public function isEnabled(): bool
    {
        return (bool)($this->data['enabled'] ?? false);
    }

    /**
     * 是否有效
     * @return bool
     */
    public function isValid(): bool
    {
        return !($this->data['invalid'] ?? false);
    }

    /**
     * 获取错误信息
     * @return string
     */
    public function getError(): string
    {
        return (string)($this->data['error'] ?? '');
    }

    /**
     * 获取状态文本
     * @return string
     */
    public function getStatusText(): string
    {
        return match ($this->getStatus()) {
            'enabled' => '已启用',
            'installed' => '已安装',
            'disabled' => '已禁用',
            'invalid' => '无效',
            'broken' => '异常',
            default => '未安装',
        };
    }

    /**
     * 生成新描述对象
     * @param array<string, mixed> $overrides
     * @return self
     */
    public function with(array $overrides): self
    {
        return new self(array_replace($this->data, $overrides));
    }

    /**
     * 转数组
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->data + [
                'name'        => $this->getName(),
                'vendor'      => $this->getVendor(),
                'short_name'  => $this->getShortName(),
                'title'       => $this->getTitle(),
                'description' => $this->getDescription(),
                'version'     => $this->getVersion(),
                'author'      => $this->getAuthor(),
                'status_text' => $this->getStatusText(),
            ];
    }

    /**
     * 获取插件名称分段
     * @return array<int, string>
     */
    private function getNameParts(): array
    {
        $name = $this->getName();
        if ($name === '' || !str_contains($name, '/')) {
            return [];
        }

        return explode('/', $name, 2);
    }
}
