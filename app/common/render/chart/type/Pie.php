<?php
declare(strict_types=1);

namespace app\common\render\chart\type;

use app\common\abstract\ChartType;

/**
 * 饼图类型构建器
 */
class Pie extends ChartType
{
    /**
     * @inheritDoc
     */
    public function build(array $context): array
    {
        return [
            'tooltip' => ['trigger' => 'item'],
            'legend'  => array_replace_recursive(
                ['top' => 'bottom'],
                $this->normalizeLegend($context['option']['legend'] ?? ['show' => true])
            ),
            'series'  => $this->normalizePieSeries((array)($context['series'] ?? [])),
        ];
    }
}
