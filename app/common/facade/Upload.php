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

namespace app\common\facade;

use app\common\interface\UploadDriver;
use think\Facade;

/**
 * 上传服务门面
 * 
 * 提供统一的文件上传驱动管理接口，支持本地存储、阿里云OSS、七牛云等多种存储方式
 * 
 * 使用示例：
 * ```php
 * // 获取默认驱动实例
 * $driver = Upload::driver();
 * 
 * // 获取指定驱动实例，传入上下文参数
 * $qiniuDriver = Upload::driver('qiniu', ['bucket' => 'my-bucket']);
 * 
 * // 处理表单项配置
 * $processedItem = Upload::processItem($formItem, 'local');
 * 
 * // 获取所有可用驱动
 * $drivers = Upload::getAvailableDrivers();
 * 
 * // 重新发现驱动
 * Upload::discover();
 * 
 * // 清除驱动缓存
 * Upload::clearCache();
 * ```
 * 
 * @method static UploadDriver driver(?string $driver = null, array $context = []) 获取指定的上传驱动实例，支持传入上下文参数进行自定义配置
 * @method static array getAvailableDrivers() 获取所有已注册的可用驱动列表，包含驱动元信息和配置
 * @method static array processItem(array $item, ?string $driver = null) 处理表单项配置，自动加载驱动相关的CSS/JS资源
 * @method static void discover() 自动扫描配置路径并发现可用的上传驱动，支持缓存机制
 * @method static void clearCache() 清除驱动发现缓存和实例缓存，强制重新加载
 * 
 * @throws \app\common\exception\UploadDriverException 当驱动不存在、配置无效、类不存在或实例化失败时抛出
 * 
 * @package app\common\facade
 * @author 蔡伟明 <314013107@qq.com>
 * @since 2.0.0
 */
class Upload extends Facade
{
    /**
     * 获取门面对应的服务容器绑定标识
     * 
     * @return string 服务容器中的绑定标识符
     */
    protected static function getFacadeClass(): string
    {
        return 'upload';
    }
}
