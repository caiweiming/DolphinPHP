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

namespace app\common\render\table\datetime;

use app\common\abstract\TableItem;
use app\common\render\Table as TableRender;
use util\PrettyTime;
use Exception;

/**
 * 日期时间
 */
class Item extends TableItem
{
    /**
     * 默认格式
     */
    private const DEFAULT_FORMAT = [
        'time'          => 'H:i:s',
        'time.edit'     => 'H:i:s',
        'date'          => 'Y-m-d',
        'date.edit'     => 'Y-m-d',
        'datetime'      => 'Y-m-d H:i:s',
        'datetime.edit' => 'Y-m-d H:i:s',
    ];

    /**
     * 默认类型
     */
    private const DEFAULT_TYPE = [
        'time'          => 'time',
        'time.edit'     => 'time',
        'date'          => 'date',
        'date.edit'     => 'date',
        'datetime'      => 'datetime',
        'datetime.edit' => 'datetime',
    ];

    /**
     * 可编辑的类型
     */
    private const EDITABLE_TYPE = [
        'time.edit',
        'date.edit',
        'datetime.edit',
    ];

    /**
     * handle
     * @param array $column
     * @param TableRender $table
     * @return array
     * @throws Exception
     */
    public function handle(array $column, TableRender $table): array
    {
        $column['_type'] = $column['type'];
        $column['type']  = 'datetime';

        // 编辑模式
        if (in_array($column['_type'], self::EDITABLE_TYPE)) {
            $column['format']            = $this->getFormat($column);
            $column['options']           = $this->getOptions($column);
            $column['options']['format'] = $this->handleFormat($column['format']);
            $column['options']['type']   = $column['options']['type'] ?? self::DEFAULT_TYPE[$column['_type'] ?? 'datetime'];

            $template = dp_table_path() . 'datetime/edit.html';

            // 编辑模式下，添加“dp-table-fix-cell”类，以修复行高不正确的问题
            $table->pushVar('dp_table_class', 'dp-table-fix-cell');
        }

        $table->extraJs(
            $this->createTemplet($column, [
                'options' => $column['options']
            ], $template ?? '')
        );
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
        $value = $data[$column['field']];
        // 友好时间格式
        if ($column['type'] == 'pretty_time') {
            $data[$column['field']] = !$value ? $this->getDefault($column) : PrettyTime::format($value);
            return $data;
        }

        // 处理默认值
        $column['default'] = $column['default'] ?? '';
        $data[$column['field']] = !$value ? (string)$column['default'] : date($this->getFormat($column), intval($value));
        return $data;
    }

    /**
     * 获取日期格式配置
     * @param array $column
     * @return array
     */
    private function getOptions(array $column): array
    {
        return match (true) {
            isset($column['options']) && is_array($column['options']) => $column['options'],
            isset($column['options']) && is_string($column['options']) => [
                'format' => $column['options']
            ],
            default => []
        };
    }

    /**
     * 获取格式
     * @param array $column
     * @return string
     */
    private function getFormat(array $column): string
    {
        if (!empty($column['format'])) {
            return $column['format'];
        }

        if (!empty($column['options']['format'])) {
            return $column['options']['format'];
        }

        if (!empty($column['options']) && is_string($column['options'])) {
            return $column['options'];
        }

        return self::DEFAULT_FORMAT[$column['_type'] ?? $column['type'] ?? 'Y-m-d H:i:s'];
    }

    /**
     * 获取默认值
     * @param array $column
     * @return string
     */
    private function getDefault(array $column): string
    {
        return match (true) {
            isset($column['default']) => $column['default'],
            isset($column['options']) && is_string($column['options']) => $column['options'],
            default => ''
        };
    }

    /**
     * 将PHP日期格式转为laydate日期格式
     * @param string $format
     * @return string
     */
    private function handleFormat(string $format = ''): string
    {
        $map = [
            // 年份
            'Y' => 'yyyy',    // 4 位数年份
            'y' => 'yy',      // 2 位数年份

            // 月份
            'm' => 'MM',      // 数字表示月份，有前导零（01-12）
            'n' => 'M',       // 数字表示月份，无前导零（1-12）
            'F' => 'MMMM',    // 月份，完整的文本格式
            'M' => 'MMM',     // 月份，3个字母

            // 日
            'd' => 'dd',      // 月份中的第几天，有前导零（01-31）
            'j' => 'd',       // 月份中的第几天，无前导零（1-31）
            'D' => 'ddd',     // 星期中的第几天，文本表示，3个字母
            'l' => 'dddd',    // 星期几，完整的文本格式

            // 小时
            'H' => 'HH',      // 24小时制，有前导零（00-23）
            'G' => 'H',       // 24小时制，无前导零（0-23）
            'h' => 'hh',      // 12小时制，有前导零（01-12）
            'g' => 'h',       // 12小时制，无前导零（1-12）

            // 分钟
            'i' => 'mm',      // 有前导零的分钟数（00-59）

            // 秒
            's' => 'ss',      // 有前导零的秒数（00-59）

            // 上午/下午
            'A' => 'A',       // 大写的上午下午标识（AM/PM）
            'a' => 'a',       // 小写的上午下午标识（am/pm）

            // 其他常用格式
            'w' => 'e',       // 星期中的第几天，数字表示
            'W' => 'W',       // 年份中的第几周
            'z' => 'DDD',     // 年份中的第几天
            'N' => 'E',       // ISO-8601 格式数字表示的星期中的第几天
        ];

        $result = '';
        $len    = strlen($format);

        for ($i = 0; $i < $len; $i++) {
            $char = $format[$i];

            // 处理日期格式字符
            $result .= $map[$char] ?? $char;
        }

        return $result;
    }
}
