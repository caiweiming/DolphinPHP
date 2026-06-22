<?php
declare(strict_types=1);

namespace app\cms\service;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * CMS 视图缓存清理服务
 *
 * 用于清理 CMS 应用自身的模板编译缓存，避免旧模板编译残留继续被复用。
 */
class CmsViewCacheService
{
    /**
     * 清理 CMS 运行时模板缓存目录
     * @return void
     */
    public function clearCompiledTemplates(): void
    {
        $path = runtime_path() . 'cms' . DIRECTORY_SEPARATOR . 'temp';
        if (!is_dir($path)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            $pathname = $item->getPathname();
            if ($item->isDir()) {
                @rmdir($pathname);
                continue;
            }

            @unlink($pathname);
        }
    }
}
