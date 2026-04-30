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

namespace app\common\interface;

/**
 * 上传驱动接口
 * @package app\common\interface
 */
interface UploadDriver
{
    /**
     * 构造函数
     * @param array $config 驱动配置
     */
    public function __construct(array $config = []);

    /**
     * 处理上传项配置
     * @param array $item 表单项数据
     * @return array
     */
    public function handle(array $item): array;

    /**
     * 获取前端配置
     * @return array
     */
    public function config(): array;

    /**
     * 获取JS资源文件
     * @return array
     */
    public function js(): array;

    /**
     * 获取CSS资源文件
     * @return array
     */
    public function css(): array;

    /**
     * 驱动元信息
     * @return array
     */
    public function meta(): array;

    /**
     * 健康检查
     * @return array 返回包含健康状态信息的数组，格式：['healthy' => bool, 'errors' => array, 'warnings' => array]
     */
    public function healthCheck(): array;
}
