<?php
declare(strict_types=1);

namespace Plugins\Demo\Hello\Middleware;

use Closure;
use think\Request;
use think\Response;

/**
 * 示例插件中间件
 */
class TraceMiddleware
{
    /**
     * 处理请求
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);
        $response->header([
            'X-DP-Plugin-Middleware' => 'demo/hello',
        ]);

        return $response;
    }
}
