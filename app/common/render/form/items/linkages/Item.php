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

namespace app\common\render\form\items\linkages;

use app\common\abstract\FormItem;

/**
 * 快速联动组件
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
        $table = trim((string)($params['table'] ?? ''));
        if ($table === '') {
            $params['error_message'] = '未配置 table 参数';
        }

        $levels           = $this->normalizeLevels($params['levels'] ?? 3);
        $params['levels'] = $levels;

        $submitAll            = (bool)($params['submit_all'] ?? true);
        $params['submit_all'] = $submitAll;
        $multiple             = (bool)($params['multiple'] ?? false);
        $params['multiple']   = $multiple;

        $values = $this->resolveValues($params['value'] ?? null, $levels, $submitAll, $multiple);

        $token  = $this->buildToken($params, $table);
        $apiUrl = (string)($params['url'] ?? ($params['api_url'] ?? url('admin/api/getLinkages')));

        $params['linkages_options'] = dp_parse_options([
            'url'       => $apiUrl,
            'token'     => $token,
            'levels'    => $levels,
            'values'    => $values,
            'submitAll' => $submitAll,
            'multiple'  => $multiple,
            'emptyText' => $params['empty_text'] ?? '请选择',
        ]);

        return $params;
    }

    /**
     * 获取资源
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
            'init' => ['linkages']
        ];
    }

    /**
     * 规范化级别
     * @param mixed $levels
     * @return array
     */
    private function normalizeLevels(mixed $levels): array
    {
        if (is_int($levels) || (is_string($levels) && is_numeric($levels))) {
            $count  = max(1, (int)$levels);
            $result = [];
            for ($i = 1; $i <= $count; $i++) {
                $result[] = [
                    'key'         => 'level' . $i,
                    'label'       => '第' . $i . '级',
                    'placeholder' => '请选择第' . $i . '级',
                ];
            }
            return $result;
        }

        if (!is_array($levels) || empty($levels)) {
            return $this->normalizeLevels(3);
        }

        // 兼容关联数组写法：['id' => 'ID', 'username' => '用户名']
        if (!array_is_list($levels)) {
            $allScalar = true;
            foreach ($levels as $value) {
                if (is_array($value)) {
                    $allScalar = false;
                    break;
                }
            }

            if ($allScalar) {
                $assocResult = [];
                foreach ($levels as $field => $label) {
                    if (!is_string($field) || $field === '') {
                        continue;
                    }
                    $labelText     = is_string($label) && $label !== '' ? $label : $field;
                    $assocResult[] = [
                        'key'         => $field,
                        'label'       => $labelText,
                        'placeholder' => '请选择' . $labelText,
                    ];
                }
                if (!empty($assocResult)) {
                    return $assocResult;
                }
            }
        }

        $result = [];
        foreach ($levels as $level) {
            if (is_string($level)) {
                $label = trim($level);
                if ($label === '') {
                    continue;
                }
                $idx      = count($result) + 1;
                $result[] = [
                    'key'         => 'level' . $idx,
                    'label'       => $label,
                    'placeholder' => '请选择' . $label,
                ];
                continue;
            }

            if (!is_array($level)) {
                continue;
            }

            $idx         = count($result) + 1;
            $key         = (string)($level['key'] ?? ('level' . $idx));
            $label       = (string)($level['label'] ?? ('第' . $idx . '级'));
            $placeholder = (string)($level['placeholder'] ?? ('请选择' . $label));

            $result[] = [
                'key'         => $key,
                'label'       => $label,
                'placeholder' => $placeholder,
            ];
        }

        if (empty($result)) {
            return $this->normalizeLevels(3);
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
                $key  = $last['key'] ?? 'level1';
                return [$key => $value];
            }
            if (array_is_list($value)) {
                $mapped = [];
                foreach ($value as $idx => $item) {
                    if (!isset($levels[$idx])) {
                        continue;
                    }
                    $mapped[$levels[$idx]['key']] = $item;
                }
                return $mapped;
            }
            return $value;
        }

        if ($value === null || $value === '') {
            return [];
        }

        $last = end($levels);
        $key  = $last['key'] ?? 'level1';
        return [$key => $value];
    }

    /**
     * 生成 token 并写入 session
     * @param array $params
     * @param string $table
     * @return string
     */
    private function buildToken(array $params, string $table): string
    {
        $token = 'linkages_' . substr(md5(uniqid((string)mt_rand(), true)), 0, 16);

        session($token, [
            'table'      => $table,
            'connection' => (string)($params['connection'] ?? ''),
            'prefix'     => (bool)($params['prefix'] ?? false),
            'fields'     => $this->normalizeFields($params['fields'] ?? []),
            'root_pid'   => $params['root_pid'] ?? 0,
            'filters'    => is_array($params['filters'] ?? null) ? $params['filters'] : [],
        ]);

        return $token;
    }

    /**
     * 字段映射
     * @param mixed $fields
     * @return array
     */
    private function normalizeFields(mixed $fields): array
    {
        $defaults = [
            'id'   => 'id',
            'name' => 'name',
            'pid'  => 'pid',
        ];

        if (!is_array($fields)) {
            return $defaults;
        }

        foreach ($defaults as $key => $default) {
            if (!isset($fields[$key]) || !is_string($fields[$key]) || $fields[$key] === '') {
                $fields[$key] = $default;
            }
        }

        return $fields;
    }
}
