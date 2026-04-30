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
declare (strict_types=1);

namespace app\common\middleware;

use Closure;
use app\common\Request;
use think\exception\ValidateException;
use think\facade\Config;
use think\App;
use Throwable;

/**
 * CSRF Token验证中间件
 */
class FormTokenCheck
{
    /**
     * @var App
     */
    protected App $app;

    /**
     * @var array
     */
    protected array $config;

    /**
     * 构造函数
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->config = Config::get('csrf', []);
    }

    /**
     * 处理请求
     * @param Request $request
     * @param Closure $next
     * @param string|null $token
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $token = null): mixed
    {
        // 获取当前控制器
        if (!$controller = $request->controller()) {
            return $next($request);
        }

        // 检查是否需要进行CSRF验证
        if (!$this->shouldCheckToken($request, $controller)) {
            return $next($request);
        }

        // 验证表单令牌
        if (!$request->checkToken($token ?: ($this->config['token_name'] ?? '__token__'))) {
            throw new ValidateException('invalid token');
        }

        return $next($request);
    }

    /**
     * 检查是否需要进行Token验证
     * @param Request $request
     * @param string $controller
     * @return bool
     */
    protected function shouldCheckToken(Request $request, string $controller): bool
    {
        $controllerClass = dp_resolve_controller_class($controller, app('http')->getName());

        try {
            $instance = $this->app->make($controllerClass);
        } catch (Throwable) {
            return false;
        }

        // 如果控制器没有csrf属性，则使用全局配置
        if (!property_exists($instance, 'csrf')) {
            return (bool)($this->config['enable'] ?? false);
        }

        // 如果csrf为false，则跳过验证
        if (empty($instance->csrf)) {
            return false;
        }

        // 处理数组形式的csrf配置
        if (is_array($instance->csrf)) {
            $action = $request->action(true);
            
            // 检查only规则
            if (isset($instance->csrf['only'])) {
                return in_array($action, dp_parse_actions($instance->csrf['only']));
            }
            
            // 检查except规则
            if (isset($instance->csrf['except'])) {
                return !in_array($action, dp_parse_actions($instance->csrf['except']));
            }
        }

        return true;
    }
}
