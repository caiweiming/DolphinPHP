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

namespace app\common\render\table\icon;

use app\common\abstract\TableItem;
use app\common\render\Table as TableRender;
use Exception;

/**
 * 图标
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
        $table->extraJs(
            $this->createTemplet($column, [
                'style' => $this->getStyle($column)
            ])
        );
        return $column;
    }

    /**
     * 获取样式
     * @param array $column
     * @return string
     */
    private function getStyle(array $column): string
    {
        $style = $column['style'] ?? $column['options'] ?? '';

        if (!is_array($style)) {
            return $style;
        }

        return implode(';', array_map(
            fn($key, $value) => "$key:$value",
            array_keys($style),
            $style
        ));
    }
}
