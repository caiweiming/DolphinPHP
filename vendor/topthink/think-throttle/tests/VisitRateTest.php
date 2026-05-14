<?php
declare(strict_types=1);
/**
 * 访问频率的单元测试
 */

namespace tests;

use think\middleware\Throttle;
use think\middleware\throttle\CounterSlider;
use think\Request;

class VisitRateTest extends Base
{
    function is_visit_allow(string $uri): bool
    {
        $request = new Request();
        $request->setUrl($uri);
        $response = $this->get_response($request);
        return $response->getCode() == 200;
    }

    /**
     * 根据请求的 url 设置不同的访问频率
     */
    function test_custom_visit_rate() {
        $config = $this->get_default_throttle_config();
        $config['key'] = function(Throttle $throttle, Request $request) {
            $throttle->setDriverClass(CounterSlider::class);
            $path = $request->baseUrl();
            if ($path === '/path1') {
                $throttle->setRate('10/m');
            } else if ($path === '/path2') {
                $throttle->setRate('20/m');
            } else if ($path === '/path3') {
                $throttle->setRate('30/m');
            }
            return $path;
        };
        $this->set_throttle_config($config);

        $allowCount0 = 0;
        $allowCount1 = 0;
        $allowCount2 = 0;
        $allowCount3 = 0;
        for ($i = 0; $i < 200; $i++) {
            if ($this->visit_with_http_code($this->create_request('/'))) {
                $allowCount0++;
            }
            if ($this->visit_with_http_code($this->create_request('/path1'), 404)) {
                $allowCount1++;
            }
            if ($this->visit_with_http_code($this->create_request('/path2'), 404)) {
                $allowCount2++;
            }
            if ($this->visit_with_http_code($this->create_request('/path3'), 404)) {
                $allowCount3++;
            }
        }
        $this->assertEquals(100, $allowCount0);
        $this->assertEquals(10, $allowCount1);
        $this->assertEquals(20, $allowCount2);
        $this->assertEquals(30, $allowCount3);
    }

    /**
     * 访问 2 个周期，成功次数 2 * count
     */
    function test_visit_rate_more_period() {
        $config = $this->get_default_throttle_config();
        $config['visit_rate'] = '10/s';
        $this->set_throttle_config($config);

        $allowCount = 0;
        $micro_start = microtime(true);
        // 由于缓存过期时间只精确到秒，因此周期差需要延后约 1 秒，这里取 0.9 秒
        while (microtime(true) - $micro_start < 2 + 0.9) {
            if ($this->is_visit_allow('/')) {
                $allowCount++;
            }
            usleep(10);     // 请求均匀分布
        }
        $this->assertEquals(20, $allowCount);
    }
}
