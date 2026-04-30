<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2026 广东卓锐软件有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://www.dolphinphp.com
// +----------------------------------------------------------------------
// | 作者: 蔡伟明 <314013107@qq.com>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\common\middleware;

use Closure;
use app\common\Request;
use app\common\trait\Jump;
use app\common\attribute\LoginCheck;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use Throwable;

/**
 * 登录认证中间件
 * 基于 PHP 8 Attribute 实现
 */
class AnnotationAuth
{
    use Jump;

    /**
     * 处理请求
     * @param Request $request
     * @param Closure $next
     * @return mixed
     * @throws Throwable
     */
    public function handle(Request $request, Closure $next): mixed
    {
        // 解析路由信息
        [$controller, $action] = $this->parseRoute($request);

        // 构建完整的控制器类名
        $class = $this->resolveControllerClass($controller);

        // 解析登录检查配置
        $loginCheckConfig = $this->resolveLoginCheck($class, $action);

        // 如果不需要登录，直接放行
        if (!$loginCheckConfig['required']) {
            $request->annotationAuth = true;
            return $next($request);
        }

        // 检查登录状态
        $userInfo = dp_is_login();

        if ($userInfo === false) {
            // 记录未登录访问日志
            dp_log_security('未登录访问受保护资源', [
                'controller' => $controller,
                'action'     => $action,
                'ip'         => $request->ip(),
                'url'        => $request->url(true),
                'user_agent' => $request->header('user-agent')
            ]);

            // 处理未登录情况
            $this->handleUnauthorized($request, $loginCheckConfig);
        }

        // 用户已登录，放行
        $request->annotationAuth = true;
        return $next($request);
    }

    /**
     * 解析路由信息
     * @param Request $request
     * @return array
     */
    private function parseRoute(Request $request): array
    {
        $pathInfo = array_values(array_filter(explode('/', trim($request->pathinfo(), '/')), static fn(string $item): bool => $item !== ''));

        if ($pathInfo === []) {
            return [config('route.default_controller'), config('route.default_action')];
        }

        $action = strip_tags(array_pop($pathInfo) ?: config('route.default_action'));
        $controller = implode('.', array_map(static fn(string $item): string => strip_tags($item), $pathInfo));
        if ($controller === '') {
            $controller = (string)config('route.default_controller');
        }

        // 移除URL后缀
        $suffix = ltrim(config('route.url_html_suffix'), '.');
        $action = preg_replace('/\.(' . $suffix . ')$/i', '', $action);

        return [$controller, $action];
    }

    /**
     * 解析控制器类名
     *
     * @param string $controller
     * @return string
     */
    private function resolveControllerClass(string $controller): string
    {
        return dp_resolve_controller_class($controller, app('http')->getName());
    }

    /**
     * 解析登录检查配置
     * 优先级：方法级别 > 类级别 > 默认值
     *
     * @param string $class 控制器类名
     * @param string $action 方法名
     * @return array{required: bool, message: string, redirect: string|null}
     * @throws Throwable
     */
    private function resolveLoginCheck(string $class, string $action): array
    {
        try {
            // 1. 检查方法级别 Attribute
            $methodReflection = new ReflectionMethod($class, $action);
            $methodAttributes = $methodReflection->getAttributes(LoginCheck::class);

            if (!empty($methodAttributes)) {
                $instance = $methodAttributes[0]->newInstance();
                return [
                    'required' => $instance->required,
                    'message'  => $instance->message,
                    'redirect' => $instance->redirect
                ];
            }

            // 2. 检查类级别 Attribute
            $classReflection = new ReflectionClass($class);
            $classAttributes = $classReflection->getAttributes(LoginCheck::class);

            if (!empty($classAttributes)) {
                $instance = $classAttributes[0]->newInstance();
                return [
                    'required' => $instance->required,
                    'message'  => $instance->message,
                    'redirect' => $instance->redirect
                ];
            }

            // 3. 默认配置：需要登录（安全优先）
            return [
                'required' => true,
                'message'  => '请先登录',
                'redirect' => null
            ];

        } catch (ReflectionException $e) {
            // 反射异常，记录日志并采用安全默认值
            dp_log('LoginCheck Attribute 解析失败')->context([
                'class'  => $class,
                'action' => $action,
                'error'  => $e->getMessage()
            ])->error();

            return [
                'required' => true,
                'message'  => '请先登录',
                'redirect' => null
            ];
        }
    }

    /**
     * 处理未授权访问
     *
     * @param Request $request
     * @param array $config
     * @return void
     */
    private function handleUnauthorized(Request $request, array $config): void
    {
        $loginUrl = $this->buildLoginUrlWithRedirect($request);

        // 如果指定了重定向URL
        if ($config['redirect'] !== null) {
            $this->redirect($config['redirect']);
        }

        // 判断是否为 AJAX 请求
        if ($request->isAjax()) {
            $this->error($config['message'], $loginUrl, [], 401);
        }

        // 普通请求重定向到登录页
        $this->redirect($loginUrl);
    }

    /**
     * 构建携带回跳地址的登录 URL
     * @param Request $request
     * @return string
     */
    private function buildLoginUrlWithRedirect(Request $request): string
    {
        $currentUrl = $request->url();
        if ($currentUrl === '' || str_contains($currentUrl, '/admin/login/index')) {
            return (string)dp_url(config('system.login_url'));
        }

        return (string)dp_url(config('system.login_url'), ['redirect' => $currentUrl]);
    }
}
