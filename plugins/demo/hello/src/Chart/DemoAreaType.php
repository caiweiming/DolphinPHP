<?php
declare(strict_types=1);

namespace Plugins\Demo\Hello\Chart;

use app\common\abstract\ChartType;

/**
 * 示例图表类型
 */
class DemoAreaType extends ChartType
{
    /**
     * 构建图表配置
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function build(array $context): array
    {
        $option = $this->buildCartesianOption(
            $context,
            'line',
            [
                'type' => 'category',
                'data' => (array)($context['categories'] ?? []),
            ],
            [
                'type' => 'value',
            ]
        );

        foreach ($option['series'] as &$series) {
            $series['smooth']    = true;
            $series['areaStyle'] = ['opacity' => 0.18];
        }

        return $option;
    }
}
