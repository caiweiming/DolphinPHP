<?php

namespace app\common\middleware;

use app\common\annotation\OpLog;
use app\common\helper\Logger;
use Closure;
use think\facade\Log;
use think\Request;
use think\Response;
use ReflectionMethod;
use ReflectionException;
use Throwable;

/**
 * 日志中间件
 * 自动处理带有OpLog注解的方法
 */
class LogMiddleware
{
    /**
     * 配置缓存
     */
    private static array|null $config = null;

    /**
     * 注解缓存
     */
    private static array $annotationCache = [];

    /**
     * 处理请求
     *
     * @param Request $request 请求对象
     * @param Closure $next 下一个中间件
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 加载配置
        $this->loadConfig();

        // 检查中间件是否启用
        if (!(self::$config['middleware']['enable'] ?? true)) {
            return $next($request);
        }

        // 检查是否应该排除此请求
        if ($this->shouldExclude($request)) {
            return $next($request);
        }

        // 解析控制器和方法
        $controller = $this->getController($request);
        $action     = $this->getAction($request);

        if (!$controller || !$action) {
            return $next($request);
        }

        // 获取注解信息
        $opLog = $this->extractOpLogAnnotation($controller, $action);

        // 处理请求
        $response = $next($request);

        // 如果有注解且自动处理开启，记录日志
        if ($opLog && $opLog->auto && (self::$config['middleware']['auto_annotation'] ?? true)) {
            $this->handleAutoLog($request, $response, $opLog, $controller, $action);
        }

        return $response;
    }

    /**
     * 处理自动日志记录
     *
     * @param Request $request 请求对象
     * @param Response $response 响应对象
     * @param OpLog $opLog 注解对象
     * @param string $controller 控制器名
     * @param string $action 方法名
     * @return void
     */
    private function handleAutoLog(Request $request, Response $response, OpLog $opLog, string $controller, string $action): void
    {
        try {
            // 检查是否应该记录此日志
            if (!$opLog->shouldLog()) {
                return;
            }

            // 创建Logger实例
            $logger = Logger::make($opLog->getTitle($controller, $action))
                ->level($opLog->level);

            // 设置异步处理
            if ($opLog->async || (self::$config['middleware']['force_async'] ?? false)) {
                $logger->async();
            }

            // 收集上下文数据
            $context = $this->collectContextData($request, $response, $opLog);

            // 记录日志
            $logger->context($context)->{$opLog->level}();

        } catch (Throwable $e) {
            // 静默处理异常
            if (self::$config['middleware']['exception_silence'] ?? true) {
                Log::error('自动日志记录失败', [
                    'error'      => $e->getMessage(),
                    'controller' => $controller,
                    'action'     => $action,
                ]);
            }
        }
    }

    /**
     * 收集上下文数据
     *
     * @param Request $request 请求对象
     * @param Response $response 响应对象
     * @param OpLog $opLog 注解对象
     * @return array
     */
    private function collectContextData(Request $request, Response $response, OpLog $opLog): array
    {
        $context = [];

        // 添加注解中的额外上下文
        if (!empty($opLog->context)) {
            $context = array_merge($context, $opLog->context);
        }

        // 收集请求参数
        if ($opLog->params) {
            $params = $request->param();
            if (!empty($opLog->fields)) {
                $params = $opLog->getFieldData($params);
            }
            if (!empty($opLog->exclude)) {
                $params = $opLog->filterExcludedFields($params);
            }
            $context['params'] = $params;
        }

        // 收集响应结果
        if ($opLog->result) {
            $responseData = $this->parseResponseData($response);
            if ($responseData !== null) {
                $context['result'] = $responseData;
            }
        }

        // 收集指定字段
        if (!empty($opLog->fields) && !$opLog->params) {
            $fieldData = $opLog->getFieldData($request->param());
            if (!empty($fieldData)) {
                $context['fields'] = $fieldData;
            }
        }

        return $context;
    }

    /**
     * 解析响应数据
     *
     * @param Response $response 响应对象
     * @return mixed
     */
    private function parseResponseData(Response $response): mixed
    {
        $content = $response->getContent();

        // 尝试解析JSON响应
        if ($response->getHeader('Content-Type') === 'application/json' ||
            ($this->isJson($content))) {
            return json_decode($content, true);
        }

        return [
            'type'    => 'string',
            'length'  => strlen($content),
            'preview' => mb_substr($content, 0, 100) . (strlen($content) > 100 ? '...' : '')
        ];
    }

    /**
     * 判断字符串是否为JSON
     *
     * @param string $string 字符串
     * @return bool
     */
    private function isJson(string $string): bool
    {
        if (empty($string)) {
            return false;
        }

        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * 提取OpLog注解
     *
     * @param string $controller 控制器类名
     * @param string $action 方法名
     * @return OpLog|null
     */
    private function extractOpLogAnnotation(string $controller, string $action): ?OpLog
    {
        $cacheKey = $controller . '::' . $action;

        // 检查缓存
        if (isset(self::$annotationCache[$cacheKey])) {
            return self::$annotationCache[$cacheKey];
        }

        try {
            $reflection = new ReflectionMethod($controller, $action);
            $attributes = $reflection->getAttributes(OpLog::class);

            if (!empty($attributes)) {
                $opLog                            = $attributes[0]->newInstance();
                self::$annotationCache[$cacheKey] = $opLog;
                return $opLog;
            }

        } catch (ReflectionException) {
            // 方法不存在或无法反射
        } catch (Throwable) {
            // 其他异常
        }

        // 缓存空结果
        self::$annotationCache[$cacheKey] = null;
        return null;
    }

    /**
     * 获取控制器类名
     *
     * @param Request $request 请求对象
     * @return string|null
     */
    private function getController(Request $request): ?string
    {
        try {
            $route = $request->rule();
            if ($route && method_exists($route, 'getController')) {
                return $route->getController();
            }

            // 备用方法：从请求信息中获取
            $controller = $request->controller();
            if ($controller) {
                return dp_resolve_controller_class($controller, app('http')->getName() ?: 'admin');
            }

        } catch (Throwable) {
            // 静默处理
        }

        return null;
    }

    /**
     * 获取方法名
     *
     * @param Request $request 请求对象
     * @return string|null
     */
    private function getAction(Request $request): ?string
    {
        try {
            $route = $request->rule();
            if ($route && method_exists($route, 'getAction')) {
                return $route->getAction();
            }

            // 备用方法
            return $request->action();

        } catch (Throwable) {
            // 静默处理
        }

        return null;
    }

    /**
     * 检查是否应该排除此请求
     *
     * @param Request $request 请求对象
     * @return bool
     */
    private function shouldExclude(Request $request): bool
    {
        $filterConfig = self::$config['filter'] ?? [];

        // 检查排除的URL
        $excludeUrls = $filterConfig['exclude_urls'] ?? [];
        $currentUrl  = $request->pathinfo();

        foreach ($excludeUrls as $excludeUrl) {
            if (str_contains($currentUrl, $excludeUrl)) {
                return true;
            }
        }

        // 检查排除的HTTP方法
        $excludeMethods = $filterConfig['exclude_methods'] ?? [];
        if (in_array($request->method(), $excludeMethods)) {
            return true;
        }

        return false;
    }

    /**
     * 加载配置
     *
     * @return void
     */
    private function loadConfig(): void
    {
        if (self::$config === null) {
            // 加载基础配置
            self::$config = config('logging', []);

            // 确保配置是数组
            if (!is_array(self::$config)) {
                self::$config = [];
            }

            // 设置默认值
            self::$config = array_merge([
                'enable'       => true,
                'middleware'   => ['enable' => true, 'auto_annotation' => true, 'exception_silence' => true],
                'filter'       => ['exclude_urls' => [], 'exclude_methods' => []],
                'environments' => []
            ], self::$config);

            // 合并环境特定配置
            try {
                $env = app()->env ?? 'production';
                if (isset(self::$config['environments'][$env]) && is_array(self::$config['environments'])) {
                    $envConfig = self::$config['environments'][$env];
                    if (!empty($envConfig) && is_array($envConfig)) {
                        self::$config = $this->mergeConfig(self::$config, $envConfig);
                    }
                }
            } catch (Throwable) {
                // 配置合并失败时使用默认配置
            }
        }
    }

    /**
     * 安全的配置合并方法
     *
     * @param array $base 基础配置
     * @param array $override 覆盖配置
     * @return array
     */
    private function mergeConfig(array $base, array $override): array
    {
        foreach ($override as $key => $value) {
            if (is_array($value) && isset($base[$key]) && is_array($base[$key])) {
                $base[$key] = $this->mergeConfig($base[$key], $value);
            } else {
                $base[$key] = $value;
            }
        }
        return $base;
    }
}
