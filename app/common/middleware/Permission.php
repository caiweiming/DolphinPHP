<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2026 广东卓锐软件有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://www.dolphinphp.com
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\middleware;

use app\admin\controller\Auth;
use app\common\annotation\Permission as PermissionAnnotation;
use app\common\interface\PermissionService;
use app\common\model\Permission as PermissionModel;
use app\common\Request;
use app\common\service\AppService;
use app\common\service\UserContext;
use app\common\trait\Jump;
use Closure;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use think\exception\HttpResponseException;
use think\facade\Log;
use Throwable;

/**
 * 权限验证中间件
 * 基于 PHP 8 Attribute 注解的权限验证
 *
 * @package app\common\middleware
 */
class Permission
{
    use Jump;

    /**
     * 未显式声明权限时的动作权限别名
     */
    private const ACTION_PERMISSION_ALIASES = [
        'quickedit' => 'edit',
    ];

    /**
     * 白名单路由（无需权限验证）
     */
    private const WHITELIST = [
        'admin/index/index',       // 首页
        'admin/login/index',       // 登录
        'admin/login/logout',      // 退出
        'admin/login/captcha',     // 验证码
        'admin/profile/index',     // 个人资料
        'admin/profile/edit',      // 修改个人资料
        'admin/profile/password',  // 修改个人密码
    ];

    /**
     * 注解缓存（避免重复反射）
     * @var array
     */
    private static array $annotationCache = [];

    /**
     * 权限服务
     * @var PermissionService
     */
    private PermissionService $permissionService;

    /**
     * 权限模型
     * @var PermissionModel
     */
    private PermissionModel $permissionModel;

    /**
     * 应用服务
     * @var AppService
     */
    private AppService $appService;

    /**
     * 用户上下文服务
     * @var UserContext
     */
    private UserContext $userContext;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->app               = app();
        $this->request           = $this->app->request;
        $this->permissionService = app(PermissionService::class);
        $this->permissionModel   = new PermissionModel();
        $this->appService        = app(AppService::class);
        $this->userContext       = app(UserContext::class);
    }

    /**
     * 处理请求
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        try {
            // 记录开始时间（性能监控）
            $startTime = microtime(true);

            // 解析路由信息
            [$controller, $action] = $this->parseRoute($request);

            // 构建完整类名
            $class = $this->resolveControllerClass($controller);

            // 检查是否需要权限验证
            if (!$this->needPermissionCheck($class, $request)) {
                return $next($request);
            }

            // 检查是否在白名单中
            $route = strtolower(app('http')->getName() . '/' . $this->getRoutePath($request));
            if ($this->isWhitelist($route)) {
                return $next($request);
            }

            $currentApp = strtolower(app('http')->getName());
            if (!$this->appService->isAppEnabled($currentApp)) {
                dp_log_security('访问已停用应用', [
                    'user_id'    => $this->userContext->getUserId(),
                    'app'        => $currentApp,
                    'route'      => $route,
                    'ip'         => $request->ip(),
                    'user_agent' => $request->header('user-agent'),
                ]);

                if ($request->isAjax()) {
                    $this->withCode(403)->error('当前应用已停用');
                }

                $this->withCode(403)->error('当前应用已停用', 'admin/index/index');
            }

            $requestMethod = strtoupper($request->method());

            // 先检查方法级权限注解
            $methodPermission = $this->parseMethodPermissionAnnotation($class, $action);
            if ($methodPermission !== null) {
                $this->checkPermission($methodPermission, $request);

                if ($methodPermission->dataScope) {
                    $request->dataScopeConfig = $methodPermission->getDataScopeConfig();
                }

                $request->permissionCheck = true;
                return $next($request);
            }

            // 方法级未声明时，优先按当前路由精确匹配权限记录
            $routePermission = $this->permissionModel->matchRoute($route, $requestMethod, false);
            if ($routePermission !== null) {
                $this->checkRoutePermission($routePermission, $route, $requestMethod, $request);
                $request->permissionCheck = true;
                return $next($request);
            }

            // 路由未配置权限记录时，按动作推导最小权限，避免类级菜单权限放行所有子动作
            $classPermission    = $this->parseClassPermissionAnnotation($class);
            $fallbackPermission = $this->buildFallbackPermission($classPermission, $route, $action);
            if ($fallbackPermission !== null) {
                $this->checkPermission($fallbackPermission, $request);

                if ($fallbackPermission->dataScope) {
                    $request->dataScopeConfig = $fallbackPermission->getDataScopeConfig();
                }
            }

            // 记录性能指标
            $duration = (microtime(true) - $startTime) * 1000;
            if ($duration > 5) {
                Log::warning("权限检查耗时过长: {$duration}ms", [
                    'route'      => $route,
                    'permission' => $methodPermission?->getFirstCode() ?? $fallbackPermission?->getFirstCode() ?? '',
                    'user_id'    => $this->userContext->getUserId(),
                ]);
            }

            // 标记已通过权限验证
            $request->permissionCheck = true;

            return $next($request);
        } catch (HttpResponseException $e) {
            throw $e;
        } catch (InvalidArgumentException $e) {
            // 路由解析错误
            $this->withCode(400)->error($e->getMessage());
        } catch (Throwable $e) {
            // 记录错误日志
            Log::error("权限中间件异常: " . $e->getMessage(), [
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            // 为了安全，发生异常时拒绝访问
            $this->withCode(500)->error('系统异常，请联系管理员');
        }
    }

    /**
     * 解析路由信息
     *
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
        if ($suffix) {
            $action = preg_replace('/\.(' . $suffix . ')$/i', '', $action);
        }

        return [$controller, $action];
    }

    /**
     * 获取路由路径
     *
     * @param Request $request
     * @return string
     */
    private function getRoutePath(Request $request): string
    {
        [$controller, $action] = $this->parseRoute($request);
        return strtolower($controller . '/' . $action);
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
     * 检查是否需要权限验证
     *
     * @param string $class
     * @return bool
     */
    private function needPermissionCheck(string $class): bool
    {
        // 如果控制器不存在，跳过验证
        if (!class_exists($class)) {
            return false;
        }

        // 如果控制器不继承 Auth，跳过权限验证
        // 原因：只对需要登录的控制器进行权限验证
        if (!is_subclass_of($class, Auth::class)) {
            return false;
        }

        // 如果用户未登录，跳过权限验证
        // 原因：登录验证由 AnnotationAuth 中间件处理
        if (!$this->userContext->isLoggedIn()) {
            return false;
        }

        return true;
    }

    /**
     * 检查是否在白名单中
     *
     * @param string $route
     * @return bool
     */
    private function isWhitelist(string $route): bool
    {
        foreach (self::WHITELIST as $pattern) {
            // 支持通配符匹配
            if (fnmatch(strtolower($pattern), $route)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 解析权限注解
     *
     * @param string $class
     * @param string $method
     * @return PermissionAnnotation|null
     */
    private function parseMethodPermissionAnnotation(string $class, string $method): ?PermissionAnnotation
    {
        $cacheKey = $class . '@' . $method . ':method';
        if (isset(self::$annotationCache[$cacheKey])) {
            return self::$annotationCache[$cacheKey];
        }

        try {
            // 反射解析方法注解
            $reflection = new ReflectionMethod($class, $method);
            $attributes = $reflection->getAttributes(PermissionAnnotation::class);

            if (!empty($attributes)) {
                $permission                       = $attributes[0]->newInstance();
                self::$annotationCache[$cacheKey] = $permission;
                return $permission;
            }

            self::$annotationCache[$cacheKey] = null;
            return null;
        } catch (ReflectionException $e) {
            Log::error("解析权限注解失败: " . $e->getMessage(), [
                'class'  => $class,
                'method' => $method,
            ]);

            self::$annotationCache[$cacheKey] = null;
            return null;
        }
    }

    /**
     * 解析类级权限注解
     *
     * @param string $class
     * @return PermissionAnnotation|null
     */
    private function parseClassPermissionAnnotation(string $class): ?PermissionAnnotation
    {
        $cacheKey = $class . '@class';
        if (isset(self::$annotationCache[$cacheKey])) {
            return self::$annotationCache[$cacheKey];
        }

        try {
            $classReflection = new ReflectionClass($class);
            $classAttributes = $classReflection->getAttributes(PermissionAnnotation::class);

            if (!empty($classAttributes)) {
                $permission                       = $classAttributes[0]->newInstance();
                self::$annotationCache[$cacheKey] = $permission;
                return $permission;
            }

            self::$annotationCache[$cacheKey] = null;
            return null;
        } catch (ReflectionException $e) {
            Log::error("解析类级权限注解失败: " . $e->getMessage(), [
                'class' => $class,
            ]);

            self::$annotationCache[$cacheKey] = null;
            return null;
        }
    }

    /**
     * 构建回退权限
     *
     * 规则：
     * 1. index/default action 仍使用类级菜单权限
     * 2. 其他 action 按控制器权限代码推导最小方法权限
     *
     * @param PermissionAnnotation|null $classPermission
     * @param string $route
     * @param string $action
     * @return PermissionAnnotation|null
     */
    private function buildFallbackPermission(?PermissionAnnotation $classPermission, string $route, string $action): ?PermissionAnnotation
    {
        $normalizedAction = strtolower($action);
        $defaultAction    = strtolower((string)config('route.default_action', 'index'));

        if ($normalizedAction === '' || $normalizedAction === $defaultAction) {
            return $classPermission;
        }

        $baseCode = $this->resolveFallbackBaseCode($classPermission, $route);
        if ($baseCode === '') {
            return null;
        }

        $resolvedAction = $this->resolveFallbackActionCode($action);
        $derivedCode    = $baseCode . '.' . $resolvedAction;

        return new PermissionAnnotation($derivedCode, $resolvedAction);
    }

    /**
     * 解析回退权限基础代码
     *
     * @param PermissionAnnotation|null $classPermission
     * @param string $route
     * @return string
     */
    private function resolveFallbackBaseCode(?PermissionAnnotation $classPermission, string $route): string
    {
        $firstCode = trim($classPermission?->getFirstCode() ?? '');
        if ($firstCode !== '') {
            return $firstCode;
        }

        $routeParts = explode('/', strtolower($route));
        if (count($routeParts) < 2) {
            return '';
        }

        return $routeParts[0] . '.' . $routeParts[1];
    }

    /**
     * 解析回退动作权限代码
     *
     * @param string $action
     * @return string
     */
    private function resolveFallbackActionCode(string $action): string
    {
        $normalized = strtolower($action);
        if (isset(self::ACTION_PERMISSION_ALIASES[$normalized])) {
            return self::ACTION_PERMISSION_ALIASES[$normalized];
        }

        $snake = preg_replace('/(?<!^)[A-Z]/', '_$0', $action);
        $snake = strtolower((string)$snake);

        return trim($snake) !== '' ? $snake : $normalized;
    }

    /**
     * 执行权限验证
     *
     * @param PermissionAnnotation $permission
     * @param Request $request
     * @return void
     */
    private function checkPermission(PermissionAnnotation $permission, Request $request): void
    {
        try {
            $userId = $this->userContext->getUserId();

            // 调用权限服务验证
            $hasPermission = $this->permissionService->hasPermission(
                $userId,
                $permission->getCodeArray(),
                $permission->logic
            );

            if (!$hasPermission) {
                // 记录安全日志
                dp_log_security('权限验证失败', [
                    'user_id'    => $userId,
                    'permission' => $permission->getCodeArray(),
                    'logic'      => $permission->logic,
                    'route'      => $this->getRoutePath($request),
                    'ip'         => $request->ip(),
                    'user_agent' => $request->header('user-agent'),
                ]);

                // 返回403错误
                $this->denyAccess($permission, $request);
            }
        } catch (HttpResponseException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error("权限验证异常: " . $e->getMessage(), [
                'user_id'    => $this->userContext->getUserId(),
                'permission' => $permission->getCodeArray(),
                'file'       => $e->getFile(),
                'line'       => $e->getLine(),
            ]);

            // 为了安全，发生异常时拒绝访问
            $this->withCode(403)->error('权限验证失败，请联系管理员');
        }
    }

    /**
     * 执行路由权限验证
     *
     * @param PermissionModel|array $permission
     * @param string $route
     * @param string $method
     * @param Request $request
     * @return void
     */
    private function checkRoutePermission(PermissionModel|array $permission, string $route, string $method, Request $request): void
    {
        try {
            $userId         = $this->userContext->getUserId();
            $permissionData = $permission instanceof PermissionModel ? $permission->toArray() : (array)$permission;

            $hasPermission = $this->permissionService->canAccessRoute($userId, $route, $method);
            if ($hasPermission) {
                return;
            }

            dp_log_security('路由权限验证失败', [
                'user_id'    => $userId,
                'permission' => (string)($permissionData['code'] ?? ''),
                'route'      => $route,
                'method'     => $method,
                'ip'         => $request->ip(),
                'user_agent' => $request->header('user-agent'),
            ]);

            $annotation = new PermissionAnnotation(
                (string)($permissionData['code'] ?? $route),
                (string)($permissionData['name'] ?? '')
            );
            $this->denyAccess($annotation, $request);
        } catch (HttpResponseException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error("路由权限验证异常: " . $e->getMessage(), [
                'user_id' => $this->userContext->getUserId(),
                'route'   => $route,
                'method'  => $method,
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            $this->withCode(403)->error('权限验证失败，请联系管理员');
        }
    }

    /**
     * 拒绝访问（返回403错误）
     *
     * @param PermissionAnnotation $permission
     * @param Request $request
     * @return void
     */
    private function denyAccess(PermissionAnnotation $permission, Request $request): void
    {
        $errorMessage = $permission->getErrorMessage();

        // 判断是否是AJAX请求
        if ($request->isAjax()) {
            // AJAX请求返回JSON
            $this->withCode(403)->error($errorMessage, '', [
                'permission' => $permission->getCodeArray(),
                'url'        => dp_url('admin/index/index'),
            ]);
        } else {
            // HTML请求返回错误页面
            $this->withCode(403)->error($errorMessage, 'admin/index/index');
        }
    }

    /**
     * 清除注解缓存（用于开发环境）
     *
     * @return void
     */
    public static function clearCache(): void
    {
        self::$annotationCache = [];
    }
}
