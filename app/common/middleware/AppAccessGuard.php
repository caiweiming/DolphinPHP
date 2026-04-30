<?php
declare(strict_types=1);

namespace app\common\middleware;

use app\common\Request;
use app\common\service\AppService;
use app\install\service\InstallStateService;
use Closure;
use think\exception\HttpException;

/**
 * 应用访问边界守卫
 */
class AppAccessGuard
{
    /**
     * 处理请求
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $appName = strtolower(trim((string)app('http')->getName()));
        if ($appName === '') {
            return $next($request);
        }

        if (!app(InstallStateService::class)->isInstalled()) {
            return $next($request);
        }

        if ($this->shouldBypassInstallAppGuard($appName)) {
            return $next($request);
        }

        if (!app(AppService::class)->isAppAccessible($appName)) {
            throw new HttpException(404, 'app not exists:' . $appName);
        }

        return $next($request);
    }

    /**
     * install 应用的安装后访问策略统一交给 InstallGuard 处理
     * @param string $appName
     * @return bool
     */
    private function shouldBypassInstallAppGuard(string $appName): bool
    {
        return $appName === 'install';
    }
}
