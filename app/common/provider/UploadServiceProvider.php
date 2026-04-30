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

namespace app\common\provider;

use app\common\interface\UploadDriver;
use think\Service;
use app\common\service\UploadDriverManager;

/**
 * 上传服务提供者
 * 负责注册上传相关服务到容器
 */
class UploadServiceProvider extends Service
{
    /**
     * 服务注册
     */
    public function register(): void
    {
        // 注册上传驱动管理器
        $this->app->bind('upload.manager', UploadDriverManager::class);
        
        // 注册上传服务门面
        $this->app->bind('upload', function () {
            return new class {
                /**
                 * 获取驱动实例
                 * @param string|null $driver 驱动名称
                 * @param array $context 上下文
                 * @return UploadDriver
                 */
                public function driver(?string $driver = null, array $context = []): UploadDriver
                {
                    $driver = $driver ?: config('upload.default', 'local');
                    return UploadDriverManager::driver($driver, $context);
                }
                
                /**
                 * 获取所有可用驱动
                 * @return array
                 */
                public function getAvailableDrivers(): array
                {
                    return UploadDriverManager::getAvailableDrivers();
                }
                
                /**
                 * 处理表单项
                 * @param array $item 表单项数据
                 * @param string|null $driver 指定驱动
                 * @return array
                 */
                public function processItem(array $item, ?string $driver = null): array
                {
                    return UploadDriverManager::processItem($item, $driver);
                }
                
                /**
                 * 发现驱动
                 * @return void
                 */
                public function discover(): void
                {
                    UploadDriverManager::discover();
                }
                
                /**
                 * 清除缓存
                 * @return void
                 */
                public function clearCache(): void
                {
                    UploadDriverManager::clearCache();
                }
            };
        });
    }

    /**
     * 服务启动
     */
    public function boot(): void
    {
        // 自动发现驱动
        if (config('upload.discovery.auto_discover', true)) {
            UploadDriverManager::discover();
        }
        
        // 注册命令
        if ($this->app->runningInConsole()) {
            $this->commands([
                \app\common\command\UploadDriver::class
            ]);
        }
    }
}