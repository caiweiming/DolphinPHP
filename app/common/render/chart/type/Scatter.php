<?php
declare(strict_types=1);

namespace app\common\render\chart\type;

use app\common\abstract\ChartType;

/**
 * 散点图类型构建器
 */
class Scatter extends ChartType
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
}
