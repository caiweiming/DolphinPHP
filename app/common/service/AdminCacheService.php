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

namespace app\common\service;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use think\facade\Cache;

/**
 * 后台安全缓存清理服务
 *
 * 仅清理缓存驱动、模板编译缓存与显式服务缓存，不触碰日志、上传文件及其他业务文件。
 */
class AdminCacheService
{
    /**
     * @param AppService|null $appService
     * @param ConfigService|null $configService
     * @param Permission|null $permissionService
     * @param IconLibraryService|null $iconLibraryService
     * @param string $runtimePath
     */
    public function __construct(
        private ?AppService $appService = null,
        private ?ConfigService $configService = null,
        private ?Permission $permissionService = null,
        private ?IconLibraryService $iconLibraryService = null,
        private string $runtimePath = ''
    ) {
        $this->appService         = $this->appService ?? app(AppService::class);
        $this->configService      = $this->configService ?? app(ConfigService::class);
        $this->permissionService  = $this->permissionService ?? app(Permission::class);
        $this->iconLibraryService = $this->iconLibraryService ?? app(IconLibraryService::class);
        $this->runtimePath        = $this->runtimePath !== '' ? $this->runtimePath : app()->getRuntimePath();
    }

    /**
     * 执行安全清理
     * @return array<string, int|bool|string>
     */
    public function clearSafeCache(): array
    {
        Cache::clear();

        $tempPath = $this->normalizeRuntimePath($this->runtimePath) . 'temp';
        $removed  = $this->clearDirectoryContents($tempPath);

        $this->appService->clearCache();
        $this->configService->clearCache();
        $this->permissionService->clearAllCache();
        $this->iconLibraryService->clearCache();
        UploadDriverManager::clearCache();

        return [
            'success'      => true,
            'scope'        => 'safe',
            'temp_path'    => $tempPath,
            'removed_count'=> $removed,
        ];
    }

    /**
     * 清理目录下的全部内容，保留目录本身
     * @param string $path
     * @return int
     */
    private function clearDirectoryContents(string $path): int
    {
        if (!is_dir($path)) {
            return 0;
        }

        $removed = 0;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            $pathname = $item->getPathname();
            if ($item->isDir()) {
                if (!@rmdir($pathname) && is_dir($pathname)) {
                    throw new RuntimeException('清理模板缓存目录失败: ' . $pathname);
                }
            } else {
                if (!@unlink($pathname) && is_file($pathname)) {
                    throw new RuntimeException('清理模板缓存文件失败: ' . $pathname);
                }
            }

            $removed++;
        }

        return $removed;
    }

    /**
     * 规范化 runtime 路径
     * @param string $path
     * @return string
     */
    private function normalizeRuntimePath(string $path): string
    {
        return rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }
}
