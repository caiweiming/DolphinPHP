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

namespace app\common\provider;

use app\common\command\PermissionSync;
use app\common\service\PermissionSyncService;
use think\Service;

/**
 * 权限服务提供者
 *
 * 负责注册权限相关服务到容器
 *
 * @package app\common\provider
 */
class PermissionServiceProvider extends Service
{
    /**
     * 服务注册
     */
    public function register(): void
    {
        // 注册权限同步服务
        $this->app->bind('permission.sync', PermissionSyncService::class);
    }

    /**
     * 服务启动
     */
    public function boot(): void
    {
        // 注册命令（仅在命令行模式下）
        if ($this->app->runningInConsole()) {
            $this->commands([
                PermissionSync::class
            ]);
        }
    }
}
