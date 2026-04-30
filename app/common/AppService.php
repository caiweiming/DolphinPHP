<?php
declare (strict_types = 1);

namespace app\common;

use app\common\middleware\AppAccessGuard;
use think\Service;
use app\common\provider\RenderServiceProvider;
use app\common\provider\BusinessServiceProvider;

/**
 * 应用服务类
 */
class AppService extends Service
{
    /**
     * 服务注册
     */
    public function register(): void
    {
        // 注册渲染服务提供者
        $this->app->register(RenderServiceProvider::class);

        // 注册业务服务提供者
        $this->app->register(BusinessServiceProvider::class);

        // 注册其他自定义服务
        $this->registerCustomServices();
    }

    /**
     * 服务启动
     */
    public function boot(): void
    {
        $this->app->middleware->add(AppAccessGuard::class, 'app');

        // 服务启动后的初始化操作
        $this->bootCustomServices();
    }
    
    /**
     * 注册自定义服务
     */
    private function registerCustomServices(): void
    {
        // 可以在这里注册更多自定义服务
        // 例如：缓存服务、邮件服务、短信服务等
    }
    
    /**
     * 启动自定义服务
     */
    private function bootCustomServices(): void
    {
        // 可以在这里进行服务启动后的配置
        // 例如：设置默认配置、注册事件监听器等
    }
}
