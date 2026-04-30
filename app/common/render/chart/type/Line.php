<?php
declare(strict_types=1);

namespace app\common\render\chart\type;

use app\common\abstract\ChartType;

/**
 * 折线图类型构建器
 */
class Line extends ChartType
{
    /**
     * @inheritDoc
     */
    public function build(array $context): array
    {
        return $this->buildCartesianOption(
            $context,
            'line',
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
