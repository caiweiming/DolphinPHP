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

namespace app\common\render\form\items\linkage;

use app\common\abstract\FormItem;

/**
 * 多级联动组件
 */
class Item extends FormItem
{
    /**
     * 渲染
     * @param array $params
     * @return array
     */
    public function handle(array $params = []): array
    {
        $levels                  = $this->normalizeLevels($params['levels'] ?? [], $params);
        $params['levels']        = $levels;
        $params['level_options'] = $this->normalizeOptions($params['options'] ?? []);

        $submitAll            = (bool)($params['submit_all'] ?? true);
        $params['submit_all'] = $submitAll;
        $multiple             = (bool)($params['multiple'] ?? false);
        $params['multiple']   = $multiple;

        $levelValues            = $this->resolveValues($params['value'] ?? null, $levels, $submitAll, $multiple);
        $params['level_values'] = $levelValues;

        $url                   = (string)($params['url'] ?? ($params['request_url'] ?? ''));
        $params['request_url'] = $url;

        $params['linkage_options'] = dp_parse_options([
            'url'       => $url,
            'levels'    => $levels,
            'values'    => $levelValues,
            'submitAll' => $submitAll,
            'multiple'  => $multiple,
            'emptyText' => $params['empty_text'] ?? '请选择',
        ]);

        return $params;
    }

    /**
     * 获取表单项资源
     * @return array
     */
    public function getAssets(): array
    {
        return [
            'js' => [
                '__LIBS__/select2/js/select2.full.min.js',
                '__LIBS__/select2/js/i18n/zh-CN.js',
            ],
            'css' => [
                '__LIBS__/select2/css/select2.min.css',
                '__LIBS__/select2/themes/bootstrap5/select2-bootstrap-5-theme.min.css',
            ],
            'init' => ['linkage']
        ];
    }

    /**
     * 规范化级别配置
     * @param array $levels
     * @param array $params
     * @return array
     */
    private function normalizeLevels(array $levels, array $params): array
    {
        $result = [];
        foreach ($levels as $level) {
            if (is_string($level)) {
                $key = trim($level);
                if ($key === '') {
                    continue;
                }
                $result[] = [
                    'key'         => $key,
                    'label'       => $key,
                    'placeholder' => '',
                ];
                continue;
            }

            if (!is_array($level)) {
                continue;
            }

            $key = $level['key'] ?? ($level['name'] ?? '');
            if (!is_string($key) || $key === '') {
                continue;
            }

            $label         = $level['label'] ?? $key;
            $placeholder   = $level['placeholder'] ?? '';
            $url           = $level['url'] ?? '';
            $requestKeys   = $level['request_keys'] ?? [];
            $requestFields = $level['request_fields'] ?? [];
            $paramsExtra   = $level['params'] ?? [];

            $normalizedRequestKeys = [];
            if (is_array($requestKeys)) {
                foreach ($requestKeys as $requestKey) {
                    if (is_string($requestKey) && $requestKey !== '') {
                        $normalizedRequestKeys[] = $requestKey;
                    }
                }
            }

            $normalizedParamsExtra = [];
            if (is_array($paramsExtra)) {
                foreach ($paramsExtra as $paramKey => $paramValue) {
                    if (is_string($paramKey) && $paramKey !== '') {
                        $normalizedParamsExtra[$paramKey] = $paramValue;
                    }
                }
            }

            $normalizedRequestFields = [];
            if (is_array($requestFields)) {
                if (array_is_list($requestFields)) {
                    foreach ($requestFields as $fieldName) {
                        if (is_string($fieldName) && $fieldName !== '') {
                            $normalizedRequestFields[$fieldName] = $fieldName;
                        }
                    }
                } else {
                    foreach ($requestFields as $requestKey => $fieldName) {
                        if (is_string($requestKey) && $requestKey !== '' && is_string($fieldName) && $fieldName !== '') {
                            $normalizedRequestFields[$requestKey] = $fieldName;
                        }
                    }
                }
            }

            $result[] = [
                'key'            => $key,
                'label'          => is_string($label) ? $label : $key,
                'placeholder'    => is_string($placeholder) ? $placeholder : '',
                'url'            => trim((string)$url),
                'request_keys'   => $normalizedRequestKeys,
                'request_fields' => $normalizedRequestFields,
                'params'         => $normalizedParamsExtra,
            ];
        }

        if (empty($result)) {
            $fallbackKey   = (string)($params['name'] ?? 'level');
            $fallbackLabel = (string)($params['label'] ?? $fallbackKey);
            $result[]      = [
                'key'         => $fallbackKey,
                'label'       => $fallbackLabel,
                'placeholder' => '',
            ];
        }

        return $result;
    }

    /**
     * 规范化选项数据
     * @param mixed $options
     * @return array
     */
    private function normalizeOptions(mixed $options): array
    {
        if (!is_array($options)) {
            return [];
        }

        $result = [];
        if (array_is_list($options)) {
            foreach ($options as $item) {
                if (is_array($item)) {
                    if (array_key_exists('key', $item) && array_key_exists('value', $item)) {
                        $result[] = [
                            'key'   => (string)$item['key'],
                            'value' => $item['value'],
                        ];
                        continue;
                    }

                    if (array_is_list($item) && count($item) >= 2) {
                        $result[] = [
                            'key'   => (string)$item[0],
                            'value' => $item[1],
                        ];
                    }
                }
            }
            return $result;
        }

        foreach ($options as $key => $value) {
            $result[] = [
                'key'   => (string)$key,
                'value' => $value,
            ];
        }

        return $result;
    }

    /**
     * 解析默认值
     * @param mixed $value
     * @param array $levels
     * @param bool $submitAll
     * @param bool $multiple
     * @return array
     */
    private function resolveValues(mixed $value, array $levels, bool $submitAll = true, bool $multiple = false): array
    {
        if (is_array($value)) {
            if (!$submitAll && $multiple && array_is_list($value)) {
                $last = end($levels);
                $key  = $last['key'] ?? '';
                return $key !== '' ? [$key => $value] : [];
            }
            return $value;
        }

        if ($value === null || $value === '') {
            return [];
        }

        $last = end($levels);
        $key  = $last['key'] ?? '';
        if ($key === '') {
            return [];
        }

        return [$key => $value];
    }
}
