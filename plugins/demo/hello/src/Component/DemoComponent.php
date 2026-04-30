<?php
declare(strict_types=1);

namespace Plugins\Demo\Hello\Component;

/**
 * 示例组件处理类
 */
class DemoComponent
{
    /**
     * 返回组件选项
     * @return array<string, mixed>
     */
    public function options(): array
    {
        return [
            'plugin'  => 'demo/hello',
            'options' => (array)config('plugin_packages.demo.hello.example.component_options', []),
        ];
    }
}
