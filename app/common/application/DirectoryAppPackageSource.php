<?php
declare(strict_types=1);

namespace app\common\application;

use FilesystemIterator;
use Random\RandomException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Throwable;

/**
 * 本地目录应用包源
 */
class DirectoryAppPackageSource implements AppPackageSourceInterface
{
    /**
     * @param string $name
     * @param array<string, mixed> $config
     * @param AppPackageArchiveInspector $archiveInspector
     */
    public function __construct(
        protected string                     $name,
        protected array                      $config,
        protected AppPackageArchiveInspector $archiveInspector,
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
        $root = $this->getRoot();
        if (!is_dir($root)) {
            return [];
        }

        $keyword  = mb_strtolower(trim($keyword));
        $packages = [];

        foreach ($this->discoverArchives($root) as $archiveId) {
            $archivePath = $this->resolveArchivePath($archiveId);
            try {
                $inspection = $this->archiveInspector->inspect($archivePath);
            } catch (Throwable) {
                continue;
            }

            $sidecar = $this->readSidecarMetadata($archivePath);
            $row     = [
                'id'                  => $archiveId,
                'source'              => $this->name,
                'source_title'        => $this->getTitle(),
                'filename'            => basename($archivePath),
                'name'                => (string)($inspection['name'] ?? ''),
                'title'               => (string)($sidecar['title'] ?? $inspection['title'] ?? basename($archivePath)),
                'description'         => (string)($sidecar['description'] ?? $inspection['description'] ?? ''),
                'version'             => (string)($sidecar['version'] ?? $inspection['version'] ?? ''),
                'protocol_version'    => (string)($sidecar['protocol_version'] ?? $inspection['protocol_version'] ?? ''),
                'sha256'              => trim((string)($sidecar['sha256'] ?? '')),
                'signature'           => trim((string)($sidecar['signature'] ?? '')),
                'signature_algorithm' => trim((string)($sidecar['signature_algorithm'] ?? '')),
            ];

            if ($keyword !== '') {
                $haystacks = [
                    mb_strtolower((string)$row['name']),
                    mb_strtolower((string)$row['title']),
                    mb_strtolower((string)$row['description']),
                    mb_strtolower((string)$row['version']),
                ];
                $matched   = false;
                foreach ($haystacks as $value) {
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

        usort($packages, static function (array $left, array $right): int {
            return strcmp((string)($left['title'] ?? ''), (string)($right['title'] ?? ''));
        });

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

        $sourcePath = '';
        foreach ($this->listPackages() as $package) {
            if ((string)($package['id'] ?? '') !== $packageId) {
                continue;
            }

            $sourcePath = $this->resolveArchivePath($packageId);
            break;
        }

        if ($sourcePath === '' || !is_file($sourcePath)) {
            throw new RuntimeException('目录源中不存在指定应用包');
        }

        $sidecar    = $this->readSidecarMetadata($sourcePath);
        $inspection = $this->archiveInspector->inspect($sourcePath);
        $tempRoot   = rtrim((string)config('app_package.store.temp_root', runtime_path() . 'apps/store'), DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR . 'fetched' . DIRECTORY_SEPARATOR . $this->name;
        $this->ensureDirectory($tempRoot);

        $targetPath = $tempRoot . DIRECTORY_SEPARATOR . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.zip';
        if (!@copy($sourcePath, $targetPath)) {
            throw new RuntimeException('应用包复制到临时目录失败');
        }

        return [
            'id'                  => $packageId,
            'path'                => $targetPath,
            'filename'            => basename($sourcePath),
            'source'              => $this->name,
            'source_title'        => $this->getTitle(),
            'name'                => (string)($inspection['name'] ?? ''),
            'title'               => (string)($sidecar['title'] ?? $inspection['title'] ?? ''),
            'version'             => (string)($sidecar['version'] ?? $inspection['version'] ?? ''),
            'description'         => (string)($sidecar['description'] ?? $inspection['description'] ?? ''),
            'protocol_version'    => (string)($inspection['protocol_version'] ?? ''),
            'sha256'              => trim((string)($sidecar['sha256'] ?? '')),
            'signature'           => trim((string)($sidecar['signature'] ?? '')),
            'signature_algorithm' => trim((string)($sidecar['signature_algorithm'] ?? '')),
        ];
    }

    /**
     * 获取源目录
     * @return string
     */
    private function getRoot(): string
    {
        $root = trim((string)($this->config['root'] ?? ''));
        if ($root === '') {
            throw new RuntimeException('目录源根目录未配置');
        }

        return rtrim($root, DIRECTORY_SEPARATOR);
    }

    /**
     * 发现归档文件
     * @param string $root
     * @return array<int, string>
     */
    private function discoverArchives(string $root): array
    {
        $paths = [];
        if (!empty($this->config['recursive'])) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $item) {
                if ($item->isFile() && strtolower($item->getExtension()) === 'zip') {
                    $paths[] = $this->normalizeRelativePath(substr($item->getPathname(), strlen($root) + 1));
                }
            }

            sort($paths);
            return $paths;
        }

        foreach (glob($root . DIRECTORY_SEPARATOR . '*.zip') ?: [] as $path) {
            if (is_file($path)) {
                $paths[] = basename($path);
            }
        }

        sort($paths);
        return $paths;
    }

    /**
     * 读取 sidecar 元数据
     * @param string $archivePath
     * @return array<string, mixed>
     */
    private function readSidecarMetadata(string $archivePath): array
    {
        $path = $archivePath . '.meta.json';
        if (!is_file($path)) {
            return [];
        }

        try {
            $decoded = json_decode((string)file_get_contents($path), true);
            return is_array($decoded) ? $decoded : [];
        } catch (Throwable) {
            return [];
        }
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

    /**
     * 解析归档绝对路径
     * @param string $archiveId
     * @return string
     */
    private function resolveArchivePath(string $archiveId): string
    {
        $archiveId = $this->normalizeRelativePath($archiveId);
        if ($archiveId === '' || str_contains($archiveId, '../') || str_starts_with($archiveId, '/')) {
            throw new RuntimeException('应用包标识不合法');
        }

        if (!str_ends_with(strtolower($archiveId), '.zip')) {
            throw new RuntimeException('应用包标识不合法');
        }

        return $this->getRoot() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $archiveId);
    }

    /**
     * 标准化相对路径
     * @param string $path
     * @return string
     */
    private function normalizeRelativePath(string $path): string
    {
        return trim(str_replace('\\', '/', $path), '/');
    }
}
