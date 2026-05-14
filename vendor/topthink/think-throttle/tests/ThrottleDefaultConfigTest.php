<?php
declare(strict_types=1);

namespace tests;

/**
 * 默认配置的单元测试
 * Class ThrottleDefaultConfig
 * @package tests
 */
class ThrottleDefaultConfigTest extends Base
{
    function test_visit_rate()
    {
        $this->set_throttle_config($this->get_default_throttle_config());
        // 默认的访问频率为 '100/m'
        $allowCount = 0;
        for ($i = 0; $i < 200; $i++) {
            $request = $this->create_request('/');
            if ($this->visit_with_http_code($request)) {
                $allowCount++;
            }
        }
        $this->assertEquals(100, $allowCount);
    }

    function test_unlimited_request_method()
    {
        $this->set_throttle_config($this->get_default_throttle_config());
        // 默认只限制了 ['GET', 'HEAD'] ，对 POST 不做限制
        $allowCount = 0;
        for ($i = 0; $i < 200; $i++) {
            $request = $this->create_request('/','POST');
            if ($this->visit_with_http_code($request)) {
                $allowCount++;
            }
        }
        $this->assertEquals(200, $allowCount);
    }

}
