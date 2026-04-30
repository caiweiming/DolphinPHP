<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2026 广东卓锐软件有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://www.dolphinphp.com
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\install\service;

use RuntimeException;

/**
 * 安装状态服务
 *
 * 负责安装锁文件的定位、读写与安装态判断。
 */
class InstallStateService
{
    public function __construct(
        private ?string $lockFile = null,
        private ?string $legacyLockFile = null,
    ) {
        $this->lockFile ??= root_path() . 'config/install.lock';
        $this->legacyLockFile ??= root_path() . 'install.lock';
    }

    /**
     * 获取锁文件路径
     * @return string
     */
    public function lockFile(): string
    {
        return $this->lockFile;
    }

    /**
     * 判断是否已安装
     * @return bool
     */
    public function isInstalled(): bool
    {
        $resolvedLockFile = $this->resolveReadableLockFile();

        return $resolvedLockFile !== null && filesize($resolvedLockFile) > 0;
    }

    /**
     * 读取安装锁内容
     * @return array<string, mixed>
     */
    public function readLock(): array
    {
        $resolvedLockFile = $this->resolveReadableLockFile();
        if ($resolvedLockFile === null || filesize($resolvedLockFile) <= 0) {
            return [];
        }

        $content = @file_get_contents($resolvedLockFile);
        if ($content === false || $content === '') {
            return [];
        }

        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * 写入安装锁文件
     * @param array $payload
     * @return void
     */
    public function writeLock(array $payload): void
    {
        $encoded = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($encoded === false) {
            throw new RuntimeException('安装锁文件编码失败');
        }

        if (@file_put_contents($this->lockFile, $encoded) === false) {
            throw new RuntimeException('安装锁文件写入失败');
        }
    }

    /**
     * 清理安装锁文件
     * @return void
     */
    public function clearLock(): void
    {
        if (is_file($this->lockFile)) {
            @unlink($this->lockFile);
        }

        if ($this->legacyLockFile !== null && is_file($this->legacyLockFile)) {
            @unlink($this->legacyLockFile);
        }
    }

    private function resolveReadableLockFile(): ?string
    {
        if (is_file($this->lockFile)) {
            return $this->lockFile;
        }

        if ($this->legacyLockFile !== null && is_file($this->legacyLockFile)) {
            return $this->legacyLockFile;
        }

        return null;
    }
}
