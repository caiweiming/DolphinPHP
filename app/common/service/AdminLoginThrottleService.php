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

use think\facade\Cache;

/**
 * 后台登录失败限制服务
 */
final class AdminLoginThrottleService
{
    private const CACHE_PREFIX = 'admin_security:login_throttle:';

    public function isLocked(string $username): bool
    {
        return $this->getRemainingLockSeconds($username) > 0;
    }

    public function getRemainingLockSeconds(string $username): int
    {
        $state = $this->getState($username);
        $lockedUntil = (int)($state['locked_until'] ?? 0);
        if ($lockedUntil <= 0) {
            return 0;
        }

        $remaining = $lockedUntil - time();
        if ($remaining <= 0) {
            $this->clear($username);
            return 0;
        }

        return $remaining;
    }

    public function recordFailure(string $username, int $maxRetries, int $lockMinutes): void
    {
        $username = $this->normalizeUsername($username);
        if ($username === '' || $maxRetries <= 0) {
            return;
        }

        $state = $this->getState($username);
        $count = (int)($state['count'] ?? 0) + 1;

        $nextState = [
            'count'        => $count,
            'locked_until' => 0,
        ];

        if ($count >= $maxRetries && $lockMinutes > 0) {
            $nextState['locked_until'] = time() + ($lockMinutes * 60);
        }

        Cache::set($this->buildCacheKey($username), $nextState, $this->resolveTtlSeconds($lockMinutes));
    }

    public function clear(string $username): void
    {
        $username = $this->normalizeUsername($username);
        if ($username === '') {
            return;
        }

        Cache::delete($this->buildCacheKey($username));
    }

    private function getState(string $username): array
    {
        $username = $this->normalizeUsername($username);
        if ($username === '') {
            return [];
        }

        $state = Cache::get($this->buildCacheKey($username), []);
        return is_array($state) ? $state : [];
    }

    private function buildCacheKey(string $username): string
    {
        return self::CACHE_PREFIX . sha1($this->normalizeUsername($username));
    }

    private function normalizeUsername(string $username): string
    {
        return strtolower(trim($username));
    }

    private function resolveTtlSeconds(int $lockMinutes): int
    {
        $ttl = max($lockMinutes * 60, 3600);
        return $ttl;
    }
}
