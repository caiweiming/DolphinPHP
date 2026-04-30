<?php
declare(strict_types=1);

namespace app\common\application;

use Random\RandomException;
use RuntimeException;
use Throwable;

/**
 * 远程目录应用包源
 */
class RemoteCatalogAppPackageSource implements AppPackageSourceInterface
{
    /**
     * @param string $name
     * @param array<string, mixed> $config
     */
    public function __construct(
        protected string $name,
        protected array  $config,
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @inheritDoc
     */
    public function getTitle(): string
    {
        return trim((string)($this->config['title'] ?? $this->name));
    }

    /**
     * @inheritDoc
     */
    public function listPackages(string $keyword = ''): array
    {
        $catalog  = $this->fetchCatalog();
        $keyword  = mb_strtolower(trim($keyword));
        $packages = [];

        foreach ($catalog as $item) {
            $row = [
                'id'                  => trim((string)($item['id'] ?? $item['name'] ?? '')),
                'source'              => $this->name,
                'source_title'        => $this->getTitle(),
                'filename'            => trim((string)($item['filename'] ?? '')),
                'name'                => trim((string)($item['name'] ?? '')),
                'title'               => trim((string)($item['title'] ?? $item['name'] ?? '')),
                'description'         => trim((string)($item['description'] ?? '')),
                'version'             => trim((string)($item['version'] ?? '')),
                'protocol_version'    => trim((string)($item['protocol_version'] ?? $item['app_api_version'] ?? '')),
                'sha256'              => trim((string)($item['sha256'] ?? '')),
                'signature'           => trim((string)($item['signature'] ?? '')),
                'signature_algorithm' => trim((string)($item['signature_algorithm'] ?? '')),
                'download_url'        => trim((string)($item['download_url'] ?? '')),
            ];

            if ($row['id'] === '' || $row['download_url'] === '') {
                continue;
            }

            if ($keyword !== '') {
                $matched = false;
                foreach (['name', 'title', 'description', 'version'] as $field) {
                    $value = mb_strtolower((string)$row[$field]);
                    if ($value !== '' && mb_stripos($value, $keyword) !== false) {
                        $matched = true;
                        break;
                    }
                }
                if (!$matched) {
                    continue;
                }
            }

            $packages[] = $row;
        }

        usort($packages, static fn(array $left, array $right): int => strcmp((string)$left['title'], (string)$right['title']));
        return $packages;
    }

    /**
     * @inheritDoc
     * @throws RandomException
     */
    public function fetchPackage(string $packageId): array
    {
        $packageId = trim($packageId);
        if ($packageId === '') {
            throw new RuntimeException('缺少应用包标识');
        }

        foreach ($this->listPackages() as $package) {
            if ((string)$package['id'] !== $packageId) {
                continue;
            }

            $tempRoot = rtrim((string)config('app_package.store.temp_root', runtime_path() . 'apps/store'), DIRECTORY_SEPARATOR)
                . DIRECTORY_SEPARATOR . 'fetched' . DIRECTORY_SEPARATOR . $this->name;
            $this->ensureDirectory($tempRoot);

            $filename = trim((string)($package['filename'] ?: $packageId . '.zip'));
            if (!str_ends_with(strtolower($filename), '.zip')) {
                $filename .= '.zip';
            }

            $targetPath = $tempRoot . DIRECTORY_SEPARATOR . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.zip';
            $contents   = $this->httpGet((string)$package['download_url']);
            if ($contents === '') {
                throw new RuntimeException('远程应用包下载失败');
            }

            if (file_put_contents($targetPath, $contents) === false) {
                throw new RuntimeException('远程应用包写入临时目录失败');
            }

            $package['path']     = $targetPath;
            $package['filename'] = $filename;
            return $package;
        }

        throw new RuntimeException('远程源中不存在指定应用包');
    }

    /**
     * 获取远程目录
     * @return array<int, array<string, mixed>>
     */
    private function fetchCatalog(): array
    {
        $catalogUrl = trim((string)($this->config['catalog_url'] ?? ''));
        if ($catalogUrl === '') {
            return [];
        }

        $contents = $this->httpGet($catalogUrl);
        if ($contents === '') {
            return [];
        }

        try {
            $decoded = json_decode($contents, true);
        } catch (Throwable) {
            return [];
        }

        if (is_array($decoded) && isset($decoded['packages']) && is_array($decoded['packages'])) {
            return array_values(array_filter($decoded['packages'], 'is_array'));
        }

        return is_array($decoded) ? array_values(array_filter($decoded, 'is_array')) : [];
    }

    /**
     * 发起 HTTP GET
     * @param string $url
     * @return string
     */
    private function httpGet(string $url): string
    {
        $headers = [];
        foreach ((array)($this->config['headers'] ?? []) as $key => $value) {
            if (is_string($key) && $key !== '') {
                $headers[] = $key . ': ' . $value;
                continue;
            }

            if (is_string($value) && $value !== '') {
                $headers[] = $value;
            }
        }

        $context = stream_context_create([
            'http' => [
                'method'        => 'GET',
                'timeout'       => max(1, (int)($this->config['timeout'] ?? 15)),
                'header'        => $headers !== [] ? implode("\r\n", $headers) : '',
                'ignore_errors' => true,
            ],
        ]);

        $contents = @file_get_contents($url, false, $context);
        return is_string($contents) ? $contents : '';
    }

    /**
     * 确保目录存在
     * @param string $path
     * @return void
     */
    private function ensureDirectory(string $path): void
    {
        if (!is_dir($path) && !mkdir($path, 0775, true) && !is_dir($path)) {
            throw new RuntimeException('目录创建失败：' . $path);
        }
    }
}
