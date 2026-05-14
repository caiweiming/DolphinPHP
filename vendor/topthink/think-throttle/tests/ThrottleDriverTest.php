<?php
declare(strict_types=1);

namespace tests;

use think\middleware\throttle\CounterFixed;
use think\middleware\throttle\CounterSlider;
use think\middleware\throttle\LeakyBucket;
use think\middleware\throttle\TokenBucket;

/**
 * 不同节流驱动单元测试
 */
class ThrottleDriverTest extends Base
{
    function driver_run(string $derive_name): int
    {
        $config = $this->get_default_throttle_config();
        $config['driver_name'] = $derive_name;
        $config['visit_rate'] = '60/m';
        $this->set_throttle_config($config);
        $allowCount = 0;
        for ($i = 0; $i < 200; $i++) {
            $request = $this->create_request('/');
            if ($this->visit_with_http_code($request)) {
                $allowCount++;
            }
        }
        return $allowCount;
    }

    function test_counter_fixed() {
        $this->assertEquals(60, $this->driver_run(CounterFixed::class));
    }

    function test_counter_slider() {
        $this->assertEquals(60, $this->driver_run(CounterSlider::class));
    }

    function test_leaky_bucket() {
        // 漏桶算法，速率 1/s
        $this->assertEquals(1, $this->driver_run(LeakyBucket::class));
    }
    function test_token_bucket() {
        $this->assertEquals(60, $this->driver_run(TokenBucket::class));
    }
}
