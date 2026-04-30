<?php
declare(strict_types=1);

namespace Plugins\Demo\Hello\Form;

use app\common\abstract\FormItem;

/**
 * 示例表单项
 */
class DemoNoticeItem extends FormItem
{
    /**
     * 处理表单项
     * @param array $params
     * @return array
     */
    public function handle(array $params = []): array
    {
        $params['label']    = $params['label'] ?? '插件提示';
        $params['content']  = $params['content'] ?? '这是由插件注册的表单项。';
        $params['template'] = $params['template'] ?? $this->getTemplate();
        return $params;
    }

    /**
     * 获取表单项资源
     * @return array
     */
    public function getAssets(): array
    {
        return [
            'css'  => ['__PLUGIN_DEMO_HELLO__/hello.css'],
            'js'   => ['__PLUGIN_DEMO_HELLO__/hello.js'],
            'init' => [],
        ];
    }

    /**
     * 获取模板路径
     * @return string
     */
    public function getTemplate(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'demo_notice.html';
    }
}
