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

namespace app\common\render\table\select;

use app\common\abstract\TableItem;
use app\common\render\Table as TableRender;
use Exception;

/**
 * 下拉菜单
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
        $table->pushVar('dp_table_class', 'dp-table-fix-cell');
        $table->addExtraJs($this->createTemplet($column, [
            'options' => $column['options'] ?? [],
        ]));
        return $column;
    }
}