<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2026 广东卓锐软件有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://www.dolphinphp.com
// +----------------------------------------------------------------------
// | 作者: 蔡伟明 <314013107@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace app\common\render\table\status;

use app\common\abstract\TableItem;
use app\common\render\Table as TableRender;
use Exception;

/**
 * 状态标签
 */
class Item extends TableItem
{
    /**
     * handle
     * @param array $column
     * @param TableRender $table
     * @return array
     * @throws Exception
     */
    public function handle(array $column, TableRender $table): array
    {
        $labels = [];
        $colors = [];
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