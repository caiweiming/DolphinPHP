<?php
declare(strict_types=1);

namespace app\common\application;

use RuntimeException;

/**
 * 应用包源管理器
 */
class AppPackageSourceManager
{
    /**
     * @param AppPackageArchiveInspector $archiveInspector
     */
    public function __construct(
        protected AppPackageArchiveInspector $archiveInspector,
    )
    {
    }

    /**
     * 获取可用源列表
     * @return array<int, array<string, mixed>>
     */
    public function listSources(): array
    {
        $sources = [];
        foreach ((array)config('app_package.sources', []) as $name => $config) {
            if (!is_array($config) || empty($config['enabled'])) {
                continue;
            }

            $sources[] = [
                'name'   => (string)$name,
                'title'  => trim((string)($config['title'] ?? $name)),
                'driver' => trim((string)($config['driver'] ?? 'directory')),
            ];
        }

        usort($sources, static fn(array $left, array $right): int => strcmp((string)$left['title'], (string)$right['title']));
        return $sources;
    }

    /**
     * 列出应用包
     * @param string $source
     * @param string $keyword
     * @return array<int, array<string, mixed>>
     */
    public function listPackages(string $source, string $keyword = ''): array
    {
        return $this->makeSource($source)->listPackages($keyword);
    }

    /**
     * 获取应用包
     * @param string $source
     * @param string $packageId
     * @return array<string, mixed>
     */
    public function fetchPackage(string $source, string $packageId): array
    {
        return $this->makeSource($source)->fetchPackage($packageId);
    }

    /**
     * 获取源配置
     * @param string $source
     * @return array<string, mixed>
     */
    public function getSourceConfig(string $source): array
    {
        $source = trim($source);
        $config = (array)config('app_package.sources.' . $source, []);
        if ($source === '' || $config === [] || empty($config['enabled'])) {
            throw new RuntimeException('应用包源不存在或未启用');
        }

        return $config;
    }

    /**
     * 创建源实例
     * @param string $source
     * @return AppPackageSourceInterface
     */
    public function makeSource(string $source): AppPackageSourceInterface
    {
        $config = $this->getSourceConfig($source);
        $driver = trim((string)($config['driver'] ?? 'directory'));

        return match ($driver) {
            'directory' => new DirectoryAppPackageSource($source, $config, $this->archiveInspector),
            'remote_catalog' => new RemoteCatalogAppPackageSource($source, $config),
            default => throw new RuntimeException('不支持的应用包源驱动：' . $driver),
        };
    }
}
