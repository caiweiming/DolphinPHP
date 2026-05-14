<?php
declare(strict_types=1);

namespace think\middleware\throttle;

use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * 计数器滑动窗口算法
 * Class CounterSlider
 * @package think\middleware\throttle
 */
class CounterSlider extends ThrottleAbstract
{
    /**
     * @throws InvalidArgumentException
     */
    public function allowRequest(string $key, float $micro_now, int $max_requests, int $duration, CacheInterface $cache): bool
    {
        $history = $cache->get($key, []);
        $now = (int) $micro_now;
        // 移除过期的请求的记录
        $history = array_values(array_filter($history, function ($val) use ($now, $duration) {
            return $val >= $now - $duration;
        }));

        $this->cur_requests = count($history);
        if ($this->cur_requests < $max_requests) {
            // 允许访问
            $history[] = $now;
            $cache->set($key, $history, $duration);
            return true;
        }

        if ($history) {
            $wait_seconds = $duration - ($now - $history[0]) + 1;
            $this->wait_seconds = max($wait_seconds, 0);
        }

        return false;
    }

}