<?php
declare(strict_types=1);

namespace think\middleware\throttle;

use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * 令牌桶算法
 * Class TokenBucket
 * @package think\middleware\throttle
 */
class TokenBucket extends ThrottleAbstract
{
    /**
     * @throws InvalidArgumentException
     */
    public function allowRequest(string $key, float $micro_now, int $max_requests, int $duration, CacheInterface $cache): bool
    {
        if ($max_requests <= 0 || $duration <= 0) return false;

        $assist_key = $key . 'store_num';              // 辅助缓存
        $rate = (float) $max_requests / $duration;     // 平均一秒生成 n 个 token

        $last_time = $cache->get($key, 0);
        $store_num = $cache->get($assist_key, 0);

        if ($last_time === 0 || $store_num === 0) {      // 首次访问
            $cache->set($key, $micro_now, $duration);
            $cache->set($assist_key, $max_requests - 1, $duration);
            return true;
        }

        $create_num = floor(($micro_now - $last_time) * $rate);              // 推算生成的 token 数
        $token_left = (int) min($max_requests, $store_num + $create_num);  //当前剩余 tokens 数量

        if ($token_left < 1) {
            $tmp = (int) ceil($duration / $max_requests);
            $this->wait_seconds = $tmp - intval(($micro_now - $last_time)) % $tmp;
            return false;
        }
        $this->cur_requests = $max_requests - $token_left;
        $cache->set($key, $micro_now, $duration);
        $cache->set($assist_key, $token_left - 1, $duration);
        return true;
    }
}
