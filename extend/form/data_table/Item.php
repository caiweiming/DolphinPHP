<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 自定义表单扩展项：数据表格处理器
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace form\data_table;

use app\common\abstract\FormItem;

/**
 * 数据表格表单项
 */
class Item extends FormItem
{
    /**
     * 处理参数
     * @param array $params
     * @return array
     */
    public function handle(array $params = []): array
    {
        $customOptions = $this->normalizeCustomOptions($params['options'] ?? []);

        // 兼容两种参数来源：
        // 1) 直接放在根级：columns/add_text/empty_text/table_class
        // 2) 放在 item() 的 options 参数内（推荐）
        $params['columns']        = $this->normalizeColumns($params['columns'] ?? ($customOptions['columns'] ?? []));
        $params['value']          = $this->normalizeRows($params['value'] ?? []);
        $params['add_text']       = (string)($params['add_text'] ?? ($customOptions['add_text'] ?? '添加新行'));
        $params['empty_text']     = (string)($params['empty_text'] ?? ($customOptions['empty_text'] ?? '暂无数据，请点击“添加新行”'));
        $params['table_class']    = trim((string)($params['table_class'] ?? ($customOptions['table_class'] ?? '')));
        $params['confirm_delete'] = $this->normalizeBool($params['confirm_delete'] ?? ($customOptions['confirm_delete'] ?? false));
        $params['sortable']       = $this->normalizeBool($params['sortable'] ?? ($customOptions['sortable'] ?? false));
        $params['columns_json']   = json_encode($params['columns'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $params['rows_json']      = json_encode($params['value'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $params['config_json']    = json_encode([
            'confirm_delete'       => $params['confirm_delete'],
            'confirm_title'        => (string)($params['confirm_title'] ?? ($customOptions['confirm_title'] ?? '确认删除')),
            'confirm_text'         => (string)($params['confirm_text'] ?? ($customOptions['confirm_text'] ?? '确定要删除这一行吗？')),
            'confirm_confirm_text' => (string)($params['confirm_confirm_text'] ?? ($customOptions['confirm_confirm_text'] ?? '确认')),
            'confirm_cancel_text'  => (string)($params['confirm_cancel_text'] ?? ($customOptions['confirm_cancel_text'] ?? '取消')),
            'confirm_status'       => (string)($params['confirm_status'] ?? ($customOptions['confirm_status'] ?? 'warning')),
            'sortable'             => $params['sortable'],
            'sort_notify'          => $this->normalizeBool($params['sort_notify'] ?? ($customOptions['sort_notify'] ?? true)),
            'sort_notify_message'  => (string)($params['sort_notify_message'] ?? ($customOptions['sort_notify_message'] ?? '排序已更新')),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $params;
    }

    /**
     * 标准化组件自定义配置（来自 item() 的 options 参数）
     * @param mixed $options
     * @return array
     */
    private function normalizeCustomOptions(mixed $options): array
    {
        if (!is_array($options)) {
            return [];
        }

        return [
            'columns'              => $options['columns'] ?? [],
            'add_text'             => $options['add_text'] ?? null,
            'empty_text'           => $options['empty_text'] ?? null,
            'table_class'          => $options['table_class'] ?? null,
            'confirm_delete'       => $options['confirm_delete'] ?? null,
            'confirm_title'        => $options['confirm_title'] ?? null,
            'confirm_text'         => $options['confirm_text'] ?? null,
            'confirm_confirm_text' => $options['confirm_confirm_text'] ?? null,
            'confirm_cancel_text'  => $options['confirm_cancel_text'] ?? null,
            'confirm_status'       => $options['confirm_status'] ?? null,
            'sortable'             => $options['sortable'] ?? null,
            'sort_notify'          => $options['sort_notify'] ?? null,
            'sort_notify_message'  => $options['sort_notify_message'] ?? null,
        ];
    }

    /**
     * 标准化布尔值
     * @param mixed $value
     * @return bool
     */
    private function normalizeBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int)$value === 1;
        }

        if (is_string($value)) {
            $value = strtolower(trim($value));
            return in_array($value, ['1', 'true', 'yes', 'on'], true);
        }

        return false;
    }

    /**
     * 获取资源
     * @return array
     */
    public function getAssets(): array
    {
        return [
            'js'  => [
                '__LIBS__/sortable/Sortable.js',
                '__EXTEND_FORM__/data_table/data_table.js'
            ],
            'css' => [
                '__EXTEND_FORM__/data_table/data_table.css'
            ],
        ];
    }

    /**
     * 标准化列定义
     * @param mixed $columns
     * @return array
     */
    private function normalizeColumns(mixed $columns): array
    {
        if (!is_array($columns)) {
            return [];
        }

        $result = [];
        foreach ($columns as $index => $column) {
            if (!is_array($column)) {
                continue;
            }

            // 兼容两种写法：
            // 1) 列表写法：[['key' => 'title', ...], ...]
            // 2) 关联写法：['title' => [...], ...]
            $key = (string)($column['key'] ?? (is_string($index) ? $index : 'col_' . $index));
            if ($key === '') {
                $key = 'col_' . $index;
            }

            $type = (string)($column['type'] ?? 'text');
            if (!in_array($type, ['text', 'textarea', 'select', 'number', 'switch'], true)) {
                $type = 'text';
            }

            $options = [];
            if ('select' === $type && is_array($column['options'] ?? null)) {
                foreach ($column['options'] as $optionValue => $optionLabel) {
                    $options[] = [
                        'value' => (string)$optionValue,
                        'label' => (string)$optionLabel,
                    ];
                }
            }

            $result[] = [
                'key'                => $key,
                'title'              => (string)($column['title'] ?? $key),
                'type'               => $type,
                'placeholder'        => (string)($column['placeholder'] ?? ''),
                'default'            => (string)($column['default'] ?? ''),
                'allow_empty'        => $this->normalizeBool($column['allow_empty'] ?? true),
                'empty_option_text'  => (string)($column['empty_option_text'] ?? '请选择'),
                'switch_true_value'  => (string)($column['switch_true_value'] ?? '1'),
                'switch_false_value' => (string)($column['switch_false_value'] ?? '0'),
                'switch_on_text'     => (string)($column['switch_on_text'] ?? '开启'),
                'switch_off_text'    => (string)($column['switch_off_text'] ?? '关闭'),
                'required'           => $this->normalizeBool($column['required'] ?? false),
                'pattern'            => (string)($column['pattern'] ?? ''),
                'required_message'   => (string)($column['required_message'] ?? ''),
                'pattern_message'    => (string)($column['pattern_message'] ?? ''),
                'options'            => $options,
            ];
        }

        return $result;
    }

    /**
     * 标准化行数据
     * @param mixed $rows
     * @return array
     */
    private function normalizeRows(mixed $rows): array
    {
        if (!is_array($rows)) {
            return [];
        }

        $result = [];
        foreach ($rows as $row) {
            if (is_array($row)) {
                $result[] = $row;
            }
        }

        return $result;
    }
}
