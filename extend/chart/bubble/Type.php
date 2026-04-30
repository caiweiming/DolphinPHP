<?php
declare(strict_types=1);

namespace chart\bubble;

use app\common\abstract\ChartType;

/**
 * 气泡图扩展类型
 *
 * 作为 extend/chart 的最小示例，演示自定义类型、静态资源和前端 hook。
 */
class Type extends ChartType
{
    /**
     * @inheritDoc
     */
    public function build(array $context): array
    {
        return $this->buildCartesianOption(
            $context,
            'scatter',
            [
                'type'  => 'value',
                'scale' => true,
            ],
            [
                'type'  => 'value',
                'scale' => true,
            ],
            'item'
        );
    }

    /**
     * @inheritDoc
     */
    public function assets(array $context = []): array
    {
        return [
            'css' => [
                dp_static_extend_chart_path() . 'bubble/bubble.css',
            ],
            'js'  => [
                dp_static_extend_chart_path() . 'bubble/bubble.js',
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    public function payload(array $context = []): array
    {
        return [
            'sizeDimension' => 2,
            'minSymbolSize' => 12,
            'maxSymbolSize' => 36,
        ];
    }
}
