<?php
declare(strict_types=1);

namespace app\common\application;

use FilesystemIterator;
use PhpZip\ZipFile;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use Throwable;

/**
 * 应用分发包构建器
 */
class AppPackageBuilder
{
    /**
     * @param AppMetadataValidator $metadataValidator
     */
    public function __construct(protected AppMetadataValidator $metadataValidator)
    {
    }

    /**
     * 构建应用分发包
     * @param string $appName
     * @return array{path:string,name:string}
     */
    public function build(string $appName): array
    {
        $appName = trim($appName);
        if ($appName === '') {
            throw new RuntimeException('应用标识不能为空');
        }

        $appRoot = $this->resolveAppRoot($appName);
        if (!is_dir($appRoot)) {
            throw new RuntimeException('应用目录不存在，无法打包');
        }

        $metadata = $this->readMetadata($appRoot);
        $error    = $this->metadataValidator->validate($metadata, $appRoot);
        if ($error !== '') {
            throw new RuntimeException($error);
        }

        $realRoot = realpath($appRoot);
        if ($realRoot === false) {
            throw new RuntimeException('应用目录不可访问，无法打包');
        }

        $realRoot = $this->normalizePath($realRoot);
        $this->cleanupExpiredPackages();

        $packageDir  = $this->preparePackageDirectory();
        $archiveName = $this->buildArchiveName($metadata);
        $archivePath = $packageDir . DIRECTORY_SEPARATOR . $archiveName;
        $archiveRoot = 'app/' . $appName . '/';
        $zip         = new ZipFile();
        $hasEntries  = false;

        try {
            $directoryIterator = new RecursiveDirectoryIterator($realRoot, FilesystemIterator::SKIP_DOTS);
            $filterIterator    = new RecursiveCallbackFilterIterator(
                $directoryIterator,
                function (SplFileInfo $item) use ($realRoot): bool {
                    if ($item->isLink()) {
                        return false;
                    }

                    $pathname = $this->normalizePath($item->getPathname());
                    if ($pathname === '') {
                        return false;
                    }

                    $relativePath = ltrim(substr($pathname, strlen($realRoot)), '/');
                    if ($relativePath === '') {
                        return true;
                    }

                    if ($this->shouldExclude($relativePath, $item->isDir())) {
                        return false;
                    }

                    $realPath = realpath($item->getPathname());
                    if ($realPath === false) {
                        return false;
                    }

                    return $this->isWithinRoot($this->normalizePath($realPath), $realRoot);
                }
            );
            $iterator          = new RecursiveIteratorIterator(
                $filterIterator,
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $item) {
                $pathname = $this->normalizePath($item->getPathname());
                if ($pathname === '') {
                    continue;
                }

                $relativePath = ltrim(substr($pathname, strlen($realRoot)), '/');
                if ($relativePath === '') {
                    continue;
                }

                $realPath = realpath($item->getPathname());
                if ($realPath === false) {
                    continue;
                }

                $realPath = $this->normalizePath($realPath);
                if (!$this->isWithinRoot($realPath, $realRoot)) {
                    continue;
                }

                $entryName = $archiveRoot . str_replace('\\', '/', $relativePath);
                if ($item->isDir()) {
                    $zip->addEmptyDir(rtrim($entryName, '/') . '/');
                    continue;
                }

                if (!$item->isFile()) {
                    continue;
                }

                $zip->addFile($realPath, $entryName);
                $hasEntries = true;
            }

            if (!$hasEntries) {
                throw new RuntimeException('应用目录中没有可打包的文件');
            }

            $zip->saveAsFile($archivePath);
        } catch (Throwable $e) {
            if (is_file($archivePath)) {
                @unlink($archivePath);
            }

            throw new RuntimeException('应用打包失败：' . $e->getMessage(), 0, $e);
        } finally {
            $zip->close();
        }

        return [
            'path' => $archivePath,
            'name' => $archiveName,
        ];
    }

    /**
     * 读取应用静态元数据
     * @param string $appRoot
     * @return array<string, mixed>
     */
    private function readMetadata(string $appRoot): array
    {
        $manifestPath = rtrim($appRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR
            . config('app_package.manifest', 'app.json');
        if (!is_file($manifestPath)) {
            throw new RuntimeException('应用缺少 app.json，无法打包');
        }

        $decoded = json_decode((string)file_get_contents($manifestPath), true);
        if (!is_array($decoded)) {
            throw new RuntimeException('应用 app.json 解析失败');
        }

        return $decoded;
    }

    /**
     * 解析应用根目录
     * @param string $appName
     * @return string
     */
    private function resolveAppRoot(string $appName): string
    {
        return rtrim((string)config('app_package.root', app()->getBasePath()), DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . $appName;
    }

    /**
     * 准备临时打包目录
     * @return string
     */
    private function preparePackageDirectory(): string
    {
        $directory = rtrim((string)config('app_package.package.temp_root', runtime_path() . 'apps/packages'), DIRECTORY_SEPARATOR);
        if ($directory === '') {
            throw new RuntimeException('应用打包目录配置无效');
        }

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('应用打包目录创建失败');
        }

        return $directory;
    }

    /**
     * 清理过期打包文件
     * @return void
     */
    private function cleanupExpiredPackages(): void
    {
        $directory = rtrim((string)config('app_package.package.temp_root', runtime_path() . 'apps/packages'), DIRECTORY_SEPARATOR);
        $ttl       = max(0, (int)config('app_package.package.keep_seconds', 3600));
        if ($directory === '' || !is_dir($directory) || $ttl <= 0) {
            return;
        }

        $expireAt = time() - $ttl;
        foreach (glob($directory . DIRECTORY_SEPARATOR . '*.zip') ?: [] as $file) {
            if (!is_file($file)) {
                continue;
            }

            $mtime = @filemtime($file);
            if ($mtime !== false && $mtime < $expireAt) {
                @unlink($file);
            }
        }
    }

    /**
     * 构建压缩包文件名
     * @param array<string, mixed> $metadata
     * @return string
     */
    private function buildArchiveName(array $metadata): string
    {
        $name      = preg_replace('/[^A-Za-z0-9._-]+/', '-', trim((string)($metadata['name'] ?? 'app-package'))) ?: 'app-package';
        $version   = preg_replace('/[^A-Za-z0-9._-]+/', '-', trim((string)($metadata['version'] ?? '0.0.0'))) ?: '0.0.0';
        $timestamp = date('YmdHis');

        return $name . '-' . $version . '-' . $timestamp . '.zip';
    }

    /**
     * 是否应排除条目
     * @param string $relativePath
     * @param bool $isDir
     * @return bool
     */
    private function shouldExclude(string $relativePath, bool $isDir): bool
    {
        $normalized = trim(str_replace('\\', '/', $relativePath), '/');
        if ($normalized === '') {
            return false;
        }

        $segments = explode('/', $normalized);
        $name     = end($segments) ?: $normalized;

        $excludeDirectories = array_map('strval', (array)config('app_package.package.exclude_directories', []));
        foreach ($segments as $segment) {
            if (in_array($segment, $excludeDirectories, true)) {
                return true;
            }
        }

        $excludeFiles = array_map('strval', (array)config('app_package.package.exclude_files', []));
        if (!$isDir && in_array($name, $excludeFiles, true)) {
            return true;
        }

        $patterns = array_map('strval', (array)config('app_package.package.exclude_patterns', []));
        foreach ($patterns as $pattern) {
            if ($pattern !== '' && fnmatch($pattern, $name)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 规范化路径
     * @param string $path
     * @return string
     */
    private function normalizePath(string $path): string
    {
        $normalized = str_replace('\\', '/', $path);
        if ($normalized === '/') {
            return '/';
        }

        return rtrim($normalized, '/');
    }

    /**
     * 判断路径是否位于根目录内
     * @param string $path
     * @param string $root
     * @return bool
     */
    private function isWithinRoot(string $path, string $root): bool
    {
        return $path === $root || str_starts_with($path, $root . '/');
    }
}
