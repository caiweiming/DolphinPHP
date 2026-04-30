<?php
declare(strict_types=1);

namespace app\common\render\chart\type;

use app\common\abstract\ChartType;

/**
 * 柱状图类型构建器
 */
class Bar extends ChartType
{
    /**
     * @inheritDoc
     */
    public function build(array $context): array
    {
        return $this->buildCartesianOption(
            $context,
            'bar',
            [
                'type' => 'category',
                'data' => array_values((array)($context['categories'] ?? [])),
            ],
            [
                'type' => 'value',
            ]
        );
    }
}
