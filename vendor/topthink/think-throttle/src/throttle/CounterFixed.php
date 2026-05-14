<?php
declare(strict_types=1);

namespace think\middleware\throttle;

use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * 计数器固定窗口算法
 * Class CounterFixed
 * @package think\middleware\throttle
 */
class CounterFixed extends ThrottleAbstract
{

    /**
     * @throws InvalidArgumentException
     */
    public function allowRequest(string $key, float $micro_now, int $max_requests, int $duration, CacheInterface $cache): bool
    {
        $cur_requests = (int) $cache->get($key, 0);
        $now = (int) $micro_now;
        $wait_reset_seconds = $duration - $now % $duration;     // 距离下次重置还有n秒时间
        $this->wait_seconds = $wait_reset_seconds % $duration  + 1;
        $this->cur_requests = $cur_requests;

        if ($cur_requests < $max_requests) {   // 允许访问
            $cache->set($key, $this->cur_requests + 1, $wait_reset_seconds);
            return true;
        }

        return false;
    }
}