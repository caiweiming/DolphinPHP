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

namespace app\common\render\table\image;

use app\common\abstract\TableItem;
use app\common\render\Table as TableRender;
use Exception;

/**
 * 图片
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
        $table->addJsUrl('__THEME_LIBS__/fslightbox/index.js');
        $table->addExtraJs($this->createTemplet($column));
        return $column;
    }

    /**
     * 处理值
     * @param mixed $data
     * @param array $column
     * @return mixed
     */
    public function handleValue(mixed $data, array $column = []): mixed
    {
        $values = array_filter(explode(',', (string)$data[$column['field']]));
        $data[$column['field']] = [];
        foreach ($values as $value) {
            $data[$column['field']][] = is_numeric($value) ? dp_get_file_path($value) : $value;
        }
        return $data;
    }
}