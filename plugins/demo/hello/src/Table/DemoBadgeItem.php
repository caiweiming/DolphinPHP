<?php
declare(strict_types=1);

namespace Plugins\Demo\Hello\Table;

use app\common\abstract\TableItem;
use app\common\render\Table as TableRender;
use Exception;

/**
 * 示例表格列
 */
class DemoBadgeItem extends TableItem
{
    /**
     * 处理列定义
     * @param array $column
     * @param TableRender $table
     * @return array
     * @throws Exception
     */
    public function handle(array $column, TableRender $table): array
    {
        $table->addExtraJs($this->createTemplet($column, [
            'color' => $column['color'] ?? 'info',
        ], __DIR__ . DIRECTORY_SEPARATOR . 'demo_badge.html'));
        return $column;
    }
}
