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

namespace app\common\interface;

use app\common\render\Table as TableRender;

/**
 * 表格项接口
 * @package app\common\interface
 */
interface TableItem
{
    /**
     * 处理方法
     * @param array $column
     * @param TableRender $table
     * @return array
     */
    public function handle(array $column, TableRender $table): array;

    /**
     * 处理值
     * @param mixed $data
     * @param array $column
     * @return mixed
     */
    public function handleValue(mixed $data, array $column = []): mixed;
}
