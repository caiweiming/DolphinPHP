<?php
declare(strict_types=1);

namespace app\common\service;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

/**
 * 已发布静态资源目录管理器
 *
 * 统一处理目录发布、软链接回退复制与目标移除，避免应用与插件各自维护一套
 * 近似的文件系统实现。
 */
final class PublishedAssetManager
{
    /**
     * 发布目录到目标路径
     *
     * @param string $source
     * @param string $target
     * @param array<string, mixed> $options
     * @return bool 是否使用了软链接
     */
    public function publishDirectory(string $source, string $target, array $options = []): bool
    {
        $options = $this->normalizeOptions($options);

        $this->ensureDirectory(dirname($target), $options);
        $this->removePath($target);

        if (($options['symlink'] ?? false) && function_exists('symlink') && @symlink($source, $target)) {
            return true;
        }

        $this->copyDirectory($source, $target, $options);

        return false;
    }

    /**
     * 移除已发布路径
     * @param string $path
     * @return void
     */
    public function removePath(string $path): void
    {
        if (!file_exists($path) && !is_link($path)) {
            return;
        }

        if (is_file($path) || is_link($path)) {
            @unlink($path);
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            $pathname = $item->getPathname();
            if ($item->isDir() && !$item->isLink()) {
                @rmdir($pathname);
                continue;
            }

            @unlink($pathname);
        }

        @rmdir($path);
    }

    /**
     * 复制目录
     *
     * @param string $source
     * @param string $target
     * @param array<string, mixed> $options
     * @return void
     */
    private function copyDirectory(string $source, string $target, array $options): void
    {
        $this->ensureDirectory($target, $options);

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $sourcePath = $item->getPathname();
            $relative   = substr($sourcePath, strlen($source) + 1);
            $targetPath = $target . DIRECTORY_SEPARATOR . $relative;

            if ($item->isDir() && !$item->isLink()) {
                $this->ensureDirectory($targetPath, $options);
                continue;
            }

            $this->ensureDirectory(dirname($targetPath), $options);
            if (!@copy($sourcePath, $targetPath)) {
                throw new RuntimeException($this->formatMessage($options['copy_failure_message'], $sourcePath));
            }
        }
    }

    /**
     * 确保目录存在
     *
     * @param string $path
     * @param array<string, mixed> $options
     * @return void
     */
    private function ensureDirectory(string $path, array $options): void
    {
        if ($path === '') {
            throw new RuntimeException((string)$options['invalid_path_message']);
        }

        if (is_dir($path)) {
            if (($options['require_writable'] ?? false) && !is_writable($path)) {
                throw new RuntimeException($this->formatMessage($options['not_writable_message'], $path));
            }

            return;
        }

        $parent = dirname($path);
        if ($parent !== $path && !is_dir($parent)) {
            $this->ensureDirectory($parent, $options);
        } elseif (($options['require_writable'] ?? false) && $parent !== '' && !is_writable($parent)) {
            throw new RuntimeException($this->formatMessage($options['not_writable_message'], $parent));
        }

        $mode = (int)($options['directory_mode'] ?? 0775);
        if (!@mkdir($path, $mode, true) && !is_dir($path)) {
            throw new RuntimeException($this->formatMessage($options['create_failure_message'], $path));
        }

        if (($options['require_writable'] ?? false) && !is_writable($path)) {
            throw new RuntimeException($this->formatMessage($options['not_writable_message'], $path));
        }
    }

    /**
     * 归一化选项
     *
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    private function normalizeOptions(array $options): array
    {
        return array_merge([
            'symlink'                => true,
            'directory_mode'         => 0775,
            'require_writable'       => false,
            'invalid_path_message'   => '目录路径无效',
            'create_failure_message' => '目录创建失败：%s',
            'not_writable_message'   => '目录无写权限: %s',
            'copy_failure_message'   => '目录复制失败',
        ], $options);
    }

    /**
     * 格式化异常消息
     * @param mixed $message
     * @param string $path
     * @return string
     */
    private function formatMessage(mixed $message, string $path): string
    {
        if (is_callable($message)) {
            return (string)$message($path);
        }

        $message = (string)$message;
        if (str_contains($message, '%s')) {
            return sprintf($message, $path);
        }

        return $message;
    }
}
