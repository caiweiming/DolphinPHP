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

namespace app\common\render\form\items\table;

use app\common\abstract\FormItem;

/**
 * 表格展示渲染
 */
class Item extends FormItem
{
    /**
     * 默认参数配置
     * @var array
     */
    protected array $default = [
        'headers'       => [],
        'rows'          => [],
        'table_class'   => 'table table-bordered table-striped table-vcenter',
        'wrapper_class' => 'table-responsive',
        'empty_text'    => '暂无数据',
        'raw'           => false,
    ];

    /**
     * 渲染
     * @param array $params
     * @return array
     */
    public function handle(array $params = []): array
    {
        $params = array_merge($this->default, $params);

        $this->normalizeLegacyValue($params);

        $params['headers'] = $this->normalizeRows($params['headers'], true, (bool)$params['raw']);
        $params['rows']    = $this->normalizeRows($params['rows'], false, (bool)$params['raw']);

        $params['column_count'] = $this->detectColumnCount($params['headers'], $params['rows']);
        return $params;
    }

    /**
     * 兼容简化语法中将配置作为 value 传入的情况
     * @param array $params
     * @return void
     */
    private function normalizeLegacyValue(array &$params): void
    {
        $configKeys = ['headers', 'rows', 'table_class', 'wrapper_class', 'empty_text', 'raw'];
        $sources = [];

        if (isset($params['options']) && is_array($params['options'])) {
            $sources[] = $params['options'];
        }

        if (isset($params['value']) && is_array($params['value'])) {
            $sources[] = $params['value'];
        }

        foreach ($sources as $source) {
            $isConfigValue = array_intersect($configKeys, array_keys($source)) !== [];
            if (!$isConfigValue) {
                continue;
            }

            foreach ($configKeys as $key) {
                if (array_key_exists($key, $source) && empty($params[$key])) {
                    $params[$key] = $source[$key];
                }
            }
        }

        if (isset($params['value']) && is_array($params['value'])) {
            $value = $params['value'];
            $isConfigValue = array_intersect($configKeys, array_keys($value)) !== [];
            if (!$isConfigValue) {
                $params['rows'] = $value;
            }
        }
    }

    /**
     * 规范化表格行
     * @param array $rows
     * @param bool $header
     * @param bool $defaultRaw
     * @return array
     */
    private function normalizeRows(array $rows, bool $header, bool $defaultRaw): array
    {
        $normalizedRows = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $normalizedRow = [];
            foreach ($row as $cell) {
                $normalizedRow[] = $this->normalizeCell($cell, $header, $defaultRaw);
            }

            if ($normalizedRow !== []) {
                $normalizedRows[] = $normalizedRow;
            }
        }
        return $normalizedRows;
    }

    /**
     * 规范化单元格
     * @param mixed $cell
     * @param bool $header
     * @param bool $defaultRaw
     * @return array
     */
    private function normalizeCell(mixed $cell, bool $header, bool $defaultRaw): array
    {
        if (!is_array($cell) || array_is_list($cell)) {
            $cell = $this->stringifyCellContent($cell);

            return [
                'content' => $cell,
                'rowspan' => 1,
                'colspan' => 1,
                'class'   => '',
                'style'   => '',
                'attrs'   => '',
                'raw'     => $defaultRaw,
                'header'  => $header,
                'skip'    => false,
            ];
        }

        $content = $cell['content'] ?? ($cell['title'] ?? ($cell['value'] ?? ''));
        $content = $this->stringifyCellContent($content);

        return [
            'content' => $content,
            'rowspan' => max(1, (int)($cell['rowspan'] ?? 1)),
            'colspan' => max(1, (int)($cell['colspan'] ?? 1)),
            'class'   => (string)($cell['class'] ?? ''),
            'style'   => (string)($cell['style'] ?? ''),
            'attrs'   => (string)($cell['attrs'] ?? ''),
            'raw'     => isset($cell['raw']) ? (bool)$cell['raw'] : $defaultRaw,
            'header'  => $header,
            'skip'    => (bool)($cell['skip'] ?? false),
        ];
    }

    /**
     * 将复杂内容转为字符串，避免模板渲染时报数组转字符串错误
     * @param mixed $content
     * @return mixed
     */
    private function stringifyCellContent(mixed $content): mixed
    {
        if (is_array($content)) {
            return json_encode($content, JSON_UNESCAPED_UNICODE);
        }

        if (is_object($content)) {
            return method_exists($content, '__toString')
                ? (string)$content
                : json_encode($content, JSON_UNESCAPED_UNICODE);
        }

        return $content;
    }

    /**
     * 计算列总数
     * @param array $headers
     * @param array $rows
     * @return int
     */
    private function detectColumnCount(array $headers, array $rows): int
    {
        $max = 0;
        foreach (array_merge($headers, $rows) as $row) {
            $count = 0;
            foreach ($row as $cell) {
                if (($cell['skip'] ?? false) === true) {
                    continue;
                }
                $count += (int)($cell['colspan'] ?? 1);
            }
            $max = max($max, $count);
        }

        return max(1, $max);
    }
}
