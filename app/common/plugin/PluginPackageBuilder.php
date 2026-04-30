<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2026 广东卓锐软件有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://www.dolphinphp.com
// +----------------------------------------------------------------------
// | 作者: 蔡伟明 <314013107@qq.com>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\common\plugin;

use FilesystemIterator;
use PhpZip\ZipFile;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use Throwable;

/**
 * 插件分发包构建器
 */
class PluginPackageBuilder
{
    /**
     * 构建插件分发包
     * @param PluginDescriptor $descriptor
     * @return array{path:string,name:string}
     */
    public function build(PluginDescriptor $descriptor): array
    {
        if (!$descriptor->isValid()) {
            throw new RuntimeException($descriptor->getError() !== '' ? $descriptor->getError() : '插件元数据无效，无法打包');
        }

        $pluginPath = $this->normalizePath($descriptor->getPath());
        if ($pluginPath === '' || !is_dir($pluginPath)) {
            throw new RuntimeException('插件目录不存在，无法打包');
        }

        $pluginRoot = realpath($pluginPath);
        if ($pluginRoot === false) {
            throw new RuntimeException('插件目录不可访问，无法打包');
        }
        $pluginRoot = $this->normalizePath($pluginRoot);

        $this->cleanupExpiredPackages();

        $packageDir  = $this->preparePackageDirectory();
        $archiveName = $this->buildArchiveName($descriptor);
        $archivePath = $packageDir . DIRECTORY_SEPARATOR . $archiveName;
        $zip         = new ZipFile();
        $hasEntries  = false;
        $archiveRoot = 'plugins/' . trim($descriptor->getName(), '/') . '/';

        try {
            $directoryIterator = new RecursiveDirectoryIterator($pluginRoot, FilesystemIterator::SKIP_DOTS);
            $filterIterator    = new RecursiveCallbackFilterIterator(
                $directoryIterator,
                function (SplFileInfo $item) use ($pluginRoot): bool {
                    if ($item->isLink()) {
                        return false;
                    }

                    $pathname = $this->normalizePath($item->getPathname());
                    if ($pathname === '') {
                        return false;
                    }

                    $relativePath = ltrim(substr($pathname, strlen($pluginRoot)), '/');
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

                    return $this->isWithinRoot($this->normalizePath($realPath), $pluginRoot);
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

                $relativePath = ltrim(substr($pathname, strlen($this->normalizePath($pluginRoot))), '/');
                if ($relativePath === '') {
                    continue;
                }

                $realPath = realpath($item->getPathname());
                if ($realPath === false) {
                    continue;
                }

                $realPath = $this->normalizePath($realPath);
                if (!$this->isWithinRoot($realPath, $pluginRoot)) {
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
                throw new RuntimeException('插件目录中没有可打包的文件');
            }

            $zip->saveAsFile($archivePath);
        } catch (Throwable $e) {
            if (is_file($archivePath)) {
                @unlink($archivePath);
            }

            throw new RuntimeException('插件打包失败：' . $e->getMessage(), 0, $e);
        } finally {
            $zip->close();
        }

        return [
            'path' => $archivePath,
            'name' => $archiveName,
        ];
    }

    /**
     * 准备临时打包目录
     * @return string
     */
    private function preparePackageDirectory(): string
    {
        $directory = rtrim((string)config('plugin.package.temp_root', runtime_path() . 'plugins/packages'), DIRECTORY_SEPARATOR);
        if ($directory === '') {
            throw new RuntimeException('插件打包目录配置无效');
        }

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('插件打包目录创建失败');
        }

        return $directory;
    }

    /**
     * 清理过期打包文件
     * @return void
     */
    private function cleanupExpiredPackages(): void
    {
        $directory = rtrim((string)config('plugin.package.temp_root', runtime_path() . 'plugins/packages'), DIRECTORY_SEPARATOR);
        $ttl       = max(0, (int)config('plugin.package.keep_seconds', 3600));
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
     * @param PluginDescriptor $descriptor
     * @return string
     */
    private function buildArchiveName(PluginDescriptor $descriptor): string
    {
        $name      = str_replace('/', '-', trim($descriptor->getName(), '/'));
        $name      = preg_replace('/[^A-Za-z0-9._-]+/', '-', $name) ?: 'plugin-package';
        $version   = trim($descriptor->getVersion());
        $version   = $version !== '' ? preg_replace('/[^A-Za-z0-9._-]+/', '-', $version) : '0.0.0';
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

        $segments            = array_values(array_filter(explode('/', $normalized), static fn(string $segment): bool => $segment !== ''));
        $basename            = end($segments) ?: $normalized;
        $excludedDirectories = (array)config('plugin.package.exclude_directories', [
            '.git',
            '.svn',
            '.hg',
            '.idea',
            '.vscode',
            'runtime',
        ]);
        $excludedFiles       = (array)config('plugin.package.exclude_files', [
            '.DS_Store',
            'Thumbs.db',
        ]);
        $excludedPatterns    = (array)config('plugin.package.exclude_patterns', [
            '*.log',
            '*.tmp',
            '*.temp',
            '*~',
        ]);

        foreach ($segments as $segment) {
            if (in_array($segment, $excludedDirectories, true)) {
                return true;
            }
        }

        if (!$isDir && in_array($basename, $excludedFiles, true)) {
            return true;
        }

        foreach ($excludedPatterns as $pattern) {
            if (is_string($pattern) && $pattern !== '' && fnmatch($pattern, $basename, FNM_CASEFOLD)) {
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
        return rtrim(str_replace('\\', '/', $path), '/');
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
