<?php
declare(strict_types=1);

namespace think\middleware\throttle;

use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * 漏桶算法
 * Class LeakyBucket
 * @package think\middleware\throttle
 */
class LeakyBucket extends ThrottleAbstract
{

    /**
     * @throws InvalidArgumentException
     */
    public function allowRequest(string $key, float $micro_now, int $max_requests, int $duration, CacheInterface $cache): bool
    {
        if ($max_requests <= 0) return false;

        $last_time = (float) $cache->get($key, 0);      // 最近一次请求
        $rate = (float) $duration / $max_requests;       // 平均 n 秒一个请求
        if ($micro_now - $last_time < $rate) {
            $this->cur_requests = 1;
            $this->wait_seconds = (int) ceil($rate - ($micro_now - $last_time));
            return false;
        }

        $cache->set($key, $micro_now, $duration);
        return true;
    }
}