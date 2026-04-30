<?php
declare(strict_types=1);

namespace app\common\provider;

use think\Service;
use app\admin\service\User as UserService;
use app\common\interface\FileService as FileServiceInterface;
use app\common\interface\PermissionService as PermissionServiceInterface;
use app\common\service\Permission as PermissionService;

/**
 * 业务服务提供者
 */
class BusinessServiceProvider extends Service
{
    /**
     * 服务注册
     */
    public function register(): void
    {
        // 注册用户服务
        $this->app->bind('user.service', UserService::class);

        // 注册文件服务
        $this->app->bind('file.service', function ($app) {
            return $app->make(FileServiceInterface::class);
        });

        // 注册权限服务(单例模式)
        $this->app->bind(PermissionServiceInterface::class, PermissionService::class);
        $this->app->bind('permission.service', PermissionService::class);
    }

    /**
     * 服务启动
     */
    public function boot(): void
    {
        // 可以在这里进行一些服务启动后的初始化操作
    }
}
