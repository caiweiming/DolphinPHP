<?php
declare(strict_types=1);

namespace tests;


use think\Request;

/**
 * 常驻内存型的单元测试，当 TP 运行在常驻内存型的时候。
 * 一个 App 实例，处理多次请求
 * Class ResidentMemoryTest
 * @package tests
 */
class ResidentMemoryTest extends Base
{
    public function test_resident_memory()
    {
        $app = new GCApp(static::$ROOT_PATH);
        $app->setRuntimePath(static::$RUNTIME_PATH);
        $app->middleware->import(include $this->middleware_file, $this->middleware_type);
        $app->config->set($this->get_default_throttle_config(), 'throttle');

        // 处理多个请求
        $allowCount1 = 0;
        $allowCount2 = 0;

        for ($i = 0; $i < 200; $i++) {
            // 受访问频率限制
            $request = new Request();
            $request->setMethod('GET');
            $request->setUrl('/');
            $response = $app->http->run($request);
            if ($response->getCode() == 200) {
                $allowCount1++;
            }

            // 不受访问频率限制
            $request = new Request();
            $request->setMethod('POST');
            $request->setUrl('/');

            $response = $app->http->run($request);
            if ($response->getCode() == 200) {
                $allowCount2++;
            }
        }

        $app->refClear();
        $this->assertEquals(100, $allowCount1);
        $this->assertEquals(200, $allowCount2);
    }
}
