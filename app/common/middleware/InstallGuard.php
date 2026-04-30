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

use app\common\Request;
use app\install\service\InstallStateService;
use Closure;

/**
 * 安装入口守卫
 *
 * 在未安装时统一引导到 install 应用，并在已安装后阻止再次进入安装流程。
 */
class InstallGuard
{
    public function __construct(private InstallStateService $installStateService)
    {
    }

    /**
     * 处理请求
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $pathInfo    = $this->normalizePathInfo((string)$request->pathinfo());
        $appName     = $this->resolveAppName($pathInfo);
        $isInstalled = $this->installStateService->isInstalled();

        if (!$isInstalled && in_array($appName, ['index', 'admin'], true)) {
            return redirect('/install');
        }

        if ($isInstalled && $appName === 'install') {
            return redirect('/admin');
        }

        return $next($request);
    }

    private function normalizePathInfo(string $pathInfo): string
    {
        $pathInfo = trim($pathInfo, '/');

        if (preg_match('/^index\.php(?:\/(.*))?$/i', $pathInfo, $matches) === 1) {
            return trim((string)($matches[1] ?? ''), '/');
        }

        return $pathInfo;
    }

    private function resolveAppName(string $pathInfo): string
    {
        $httpAppName = strtolower(trim((string)app('http')->getName()));
        if ($httpAppName !== '') {
            return $httpAppName;
        }

        $segments = array_values(array_filter(explode('/', $pathInfo), static fn(string $segment): bool => $segment !== ''));

        return strtolower($segments[0] ?? (string)config('app.default_app', 'index'));
    }

}
