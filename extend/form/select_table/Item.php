<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 自定义表单扩展项：选择表格处理器
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace form\select_table;

use app\common\abstract\FormItem;

/**
 * 选择表格表单项
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
        $customOptions            = $this->normalizeCustomOptions($params['options'] ?? []);
        $params['columns']        = $this->normalizeColumns($params['columns'] ?? ($customOptions['columns'] ?? []));
        $params['value']          = $this->normalizeRows($params['value'] ?? []);
        $params['button_text']    = (string)($params['button_text'] ?? ($customOptions['button_text'] ?? '选择数据'));
        $params['empty_text']     = (string)($params['empty_text'] ?? ($customOptions['empty_text'] ?? '暂无已选数据，请点击“选择数据”'));
        $params['unique_key']     = (string)($params['unique_key'] ?? ($customOptions['unique_key'] ?? 'id'));
        $params['selection_mode'] = (string)($params['selection_mode'] ?? ($customOptions['selection_mode'] ?? 'multiple'));
        $params['table_class']    = trim((string)($params['table_class'] ?? ($customOptions['table_class'] ?? '')));
        $params['select_limit']   = (int)($params['select_limit'] ?? ($customOptions['select_limit'] ?? 0));
        $params['extra_fields']  = $this->normalizeExtraFields($params['extra_fields'] ?? ($customOptions['extra_fields'] ?? []));
        $popup                    = $this->normalizePopup($customOptions['popup'] ?? []);

        if (!in_array($params['selection_mode'], ['single', 'multiple'], true)) {
            $params['selection_mode'] = 'multiple';
        }

        if ($params['select_limit'] < 0) {
            $params['select_limit'] = 0;
        }

        $params['columns_json'] = json_encode($params['columns'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $params['rows_json']    = json_encode($params['value'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $params['config_json']  = json_encode([
            'popup'          => $popup,
            'unique_key'     => $params['unique_key'],
            'selection_mode' => $params['selection_mode'],
            'select_limit'   => $params['select_limit'],
            'extra_fields'  => $params['extra_fields'],
            'empty_text'     => $params['empty_text'],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $params;
    }

    /**
     * 获取资源
     * @return array
     */
    public function getAssets(): array
    {
        return [
            'js'  => [
                '__EXTEND_FORM__/select_table/select_table.js'
            ],
            'css' => [
                '__EXTEND_FORM__/select_table/select_table.css'
            ],
        ];
    }

    /**
     * 标准化组件自定义配置
     * @param mixed $options
     * @return array
     */
    private function normalizeCustomOptions(mixed $options): array
    {
        if (!is_array($options)) {
            return [];
        }

        return [
            'columns'        => $options['columns'] ?? [],
            'button_text'    => $options['button_text'] ?? null,
            'empty_text'     => $options['empty_text'] ?? null,
            'popup'          => $options['popup'] ?? [],
            'unique_key'     => $options['unique_key'] ?? null,
            'selection_mode' => $options['selection_mode'] ?? null,
            'table_class'    => $options['table_class'] ?? null,
            'select_limit'   => $options['select_limit'] ?? null,
            'extra_fields'  => $options['extra_fields'] ?? [],
        ];
    }

    /**
     * 标准化附加提交字段
     * @param mixed $fields
     * @return array
     */
    private function normalizeExtraFields(mixed $fields): array
    {
        if (!is_array($fields)) {
            return [];
        }

        $result = [];
        foreach ($fields as $field) {
            if (!is_string($field)) {
                continue;
            }

            $field = trim($field);
            if ($field === '') {
                continue;
            }

            $result[$field] = $field;
        }

        return array_values($result);
    }

    /**
     * 标准化弹窗配置
     * @param mixed $popup
     * @return array
     */
    private function normalizePopup(mixed $popup): array
    {
        if (!is_array($popup)) {
            $popup = [];
        }

        return [
            'url'      => (string)($popup['url'] ?? ''),
            'title'    => (string)($popup['title'] ?? '选择数据'),
            'width'    => (string)($popup['width'] ?? '1000px'),
            'height'   => (string)($popup['height'] ?? '650px'),
            'table_id' => (string)($popup['table_id'] ?? ''),
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
            if (is_array($column)) {
                $key = (string)($column['key'] ?? (is_string($index) ? $index : 'col_' . $index));
                if ($key === '') {
                    $key = 'col_' . $index;
                }

                $result[] = [
                    'key'   => $key,
                    'title' => (string)($column['title'] ?? $key),
                ];
                continue;
            }

            // 支持简写：['id' => 'ID', 'nickname' => '昵称']
            if (is_string($index) && (is_string($column) || is_numeric($column))) {
                $result[] = [
                    'key'   => $index,
                    'title' => (string)$column,
                ];
            }
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
