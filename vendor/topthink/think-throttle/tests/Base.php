<?php
declare(strict_types=1);
namespace tests;

use PHPUnit\Framework\TestCase;
use think\Request;
use think\Response;

abstract class Base extends TestCase {
    static string $ROOT_PATH = __DIR__ . "/../vendor/topthink/think";
    static string $RUNTIME_PATH = __DIR__ . "/../runtime/";

    protected array $throttle_config = [];
    protected string $middleware_file = __DIR__ . "/config/global-middleware.php";
    protected string $middleware_type = 'global';

    /**
     * thinkphp 一般运行在 php-fpm 模式下，每次处理请求都要重新加载配置文件
     * @param Request $request
     * @return Response
     */
    function get_response(Request $request): Response {
        // 创建 \think\App 对象，设置配置
        $app = new GCApp(static::$ROOT_PATH);
        $app->setRuntimePath(static::$RUNTIME_PATH);

        // 加载中间件
        $app->middleware->import(include $this->middleware_file, $this->middleware_type);
        // 设置 throttle 配置
        $app->config->set($this->throttle_config, 'throttle');

        $response = $app->http->run($request);
        $app->refClear();
        return $response;
    }

    /**
     * @param string $uri
     * @param string $method
     * @param string $host
     * @param array $data
     * @param array $headers
     * @return Request
     */
    function create_request(string $uri, string $method = 'GET', string $host = '127.0.0.1', array $data = [], array $headers = []): Request
    {
        $request = new Request();
        $request->setMethod($method);
        $request->setHost($host);
        $request->setDomain($host);
        $request->setUrl(sprintf('https://%s/%s', $host, $uri));
        $request->withPost($data);
        $request->withHeader($headers);

        // uri 中提取 path info
        $path = strpos($uri, '?') ? strstr($uri, '?', true) : $uri;
        $request->setBaseUrl($path);
        $path_info = empty($path) || '/' == $path ? '' : ltrim($path, '/');
        $request->setPathinfo($path_info);
        return $request;
    }

    function visit_with_http_code(Request $request, int $http_code = 200): bool
    {
        $response = $this->get_response($request);
        return $response->getCode() == $http_code;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // 每次测试完毕都需要清理 runtime cache 目录，避免影响其他单元测试
        $cache_dir = static::$RUNTIME_PATH . "cache";
        $dirs = glob($cache_dir . '/*', GLOB_ONLYDIR);
        foreach ($dirs as $dir) {
            $files = glob($dir . '/*.php');
            foreach ($files as $file) {
                unlink($file);
            }
        }
        // 删除 cache 下的空目录
        foreach ($dirs as $dir) {
            rmdir($dir);
        }
        unset($cache_dir);
        unset($dirs);
        gc_collect_cycles();    // 进行垃圾回收
    }

    /**
     * 获取默认的 throttle 基础配置信息
     * @return array
     */
    function get_default_throttle_config(): array {
        static $config = [];    // 默认配置从文件中读取，可以设置为静态变量
        if (!$config) {
            $config = include dirname(__DIR__) . "/src/config.php";
        }
        return $config;
    }

    /**
     * 设置中间件配置文件
     * @param string $file 文件的路径 eg: $this->app->getBasePath() . 'middleware.php'
     * @param string $type 类型：global 全局；route 路由；controller 控制器
     */
    function set_middleware(string $file, string $type = 'global'): void
    {
        $this->middleware_file = $file;
        $this->middleware_type = $type;
    }

    /**
     * 设置 throttle 配置
     * @param array $config
     */
    function set_throttle_config(array $config): void
    {
        $this->throttle_config = $config;
    }

}
