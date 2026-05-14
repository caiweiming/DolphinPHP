<?php
declare(strict_types=1);

namespace tests;


/**
 * 路由节流器单元测试
 * Class ResidentMemoryTest
 * @package tests
 */
class RouteThrottleTest extends Base
{
    public function test_route_middleware_type()
    {
        $this->middleware_type = 'route';   // 路由中间件
        $config = $this->get_default_throttle_config();
        $config['key'] = '__CONTROLLER__/__ACTION__/__IP__';
        $this->set_throttle_config($config);
        $allowCount1 = 0;
        $allowCount2 = 0;
        for ($i = 0; $i < 200; $i++) {
            $request = $this->create_request('/hello/name');
            if ($this->visit_with_http_code($request)) {
                $allowCount1++;
            }
            $request = $this->create_request('/');
            if ($this->visit_with_http_code($request)) {
                $allowCount2++;
            }
        }
        $this->assertEquals(100, $allowCount1);
        $this->assertEquals(100, $allowCount2);
    }

    public function test_global_middleware_type()
    {
        $this->middleware_type = 'global';   // 全局中间件
        $config = $this->get_default_throttle_config();
        $config['key'] = '__CONTROLLER__/__ACTION__/__IP__';
        $this->set_throttle_config($config);
        $allowCount1 = 0;
        $allowCount2 = 0;
        // 默认 100/m ，所以两个路由都是 50 次成功
        for ($i = 0; $i < 200; $i++) {
            $request = $this->create_request('/hello/name');
            if ($this->visit_with_http_code($request)) {
                $allowCount1++;
            }
            $request = $this->create_request('/');
            if ($this->visit_with_http_code($request)) {
                $allowCount2++;
            }
        }
        $this->assertEquals(50, $allowCount1);
        $this->assertEquals(50, $allowCount2);
    }
}
