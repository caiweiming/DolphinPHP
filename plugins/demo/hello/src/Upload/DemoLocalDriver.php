<?php
declare(strict_types=1);

namespace Plugins\Demo\Hello\Upload;

use app\common\interface\UploadDriver;

/**
 * 示例上传驱动
 */
readonly class DemoLocalDriver implements UploadDriver
{
    /**
     * @param array $config
     */
    public function __construct(private array $config = [])
    {
    }

    /**
     * 处理上传项
     * @param array $item
     * @return array
     */
    public function handle(array $item): array
    {
        $item['driver'] = 'demo_local';
        $item['props']  = trim(($item['props'] ?? '') . ' data-plugin-driver="demo_local"');
        return $item;
    }

    /**
     * 获取前端配置
     * @return array
     */
    public function config(): array
    {
        return $this->config + [
                'demo' => true,
            ];
    }

    /**
     * 获取 JS 资源
     * @return array
     */
    public function js(): array
    {
        return ['__PLUGIN_DEMO_HELLO__/upload/demo_local/demo-driver.js'];
    }

    /**
     * 获取 CSS 资源
     * @return array
     */
    public function css(): array
    {
        return [];
    }

    /**
     * 驱动元信息
     * @return array
     */
    public function meta(): array
    {
        return [
            'name'  => 'demo_local',
            'title' => '示例本地驱动',
        ];
    }

    /**
     * 健康检查
     * @return array
     */
    public function healthCheck(): array
    {
        return [
            'healthy'  => true,
            'errors'   => [],
            'warnings' => [],
        ];
    }
}
