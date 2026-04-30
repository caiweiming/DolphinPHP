<?php
declare(strict_types=1);

namespace Plugins\Demo\Hello\Controller;

use think\Response;

/**
 * 示例插件控制器
 */
class IndexController
{
    /**
     * 示例页面
     * @return Response
     */
    public function index(): Response
    {
        return dp_plugin_view('demo/hello', 'index.html', [
            'greeting' => (string)config('plugin_packages.demo.hello.example.greeting', ''),
        ], [
            'css' => ['__PLUGIN_DEMO_HELLO__/hello.css'],
            'js'  => ['__PLUGIN_DEMO_HELLO__/hello.js'],
        ]);
    }

    /**
     * 健康检查接口
     * @return Response
     */
    public function ping(): Response
    {
        return json([
            'plugin'  => 'demo/hello',
            'message' => lang('plugin_hello_message'),
        ]);
    }
}
