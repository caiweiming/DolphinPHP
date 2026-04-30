<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 自定义表格扩展项：徽章列处理器
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace table\badge;

use app\common\abstract\TableItem;
use app\common\render\Table as TableRender;
use Exception;

/**
 * 徽章列
 */
class Item extends TableItem
{
    /**
     * 处理列配置并注入模板
     * @param array $column
     * @param TableRender $table
     * @return array
     * @throws Exception
     */
    public function handle(array $column, TableRender $table): array
    {
        $labels  = [];
        $colors  = [];
        $options = $column['options'] ?? [];
        $options = empty($options) ? ['禁用:', '启用:green'] : $options;

        foreach ($options as $key => $option) {
            [$label, $color] = str_contains($option, ':')
                ? explode(':', $option)
                : [$option, $key == 1 ? 'green' : ''];

            $labels[$key] = $label;
            $colors[$key] = $color ? "-{$color}" : '';
        }

        $table->addExtraJs($this->createTemplet($column, [
            'label' => $labels,
            'color' => $colors,
        ]));
        return $column;
    }
}
