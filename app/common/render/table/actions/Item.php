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

namespace app\common\render\table\actions;

use app\common\abstract\TableItem;
use app\common\render\Table as TableRender;
use Exception;
use Throwable;
use think\facade\Config;

/**
 * 右侧操作列
 */
class Item extends TableItem
{
    /**
     * 缺省值标记
     */
    private const VALUE_NOT_FOUND = '__DP_ACTIONS_VALUE_NOT_FOUND__';

    /**
     * 允许的状态值
     */
    private const WHEN_STATES = ['hidden', 'disabled'];

    /**
     * 允许的逻辑值
     */
    private const WHEN_LOGICS = ['and', 'or'];

    /**
     * 内置右侧按钮
     */
    protected array $buttons = [];

    /**
     * 处理列配置
     * @param array $column
     * @param TableRender $table
     * @return array
     * @throws Exception
     */
    public function handle(array $column, TableRender $table): array
    {
        $this->ensureBuiltinButtonsLoaded();

        $table->addExtraJs($this->createTemplet($column));

        // 删除field字段，确保字段筛选、导出、打印的时候，忽略操作栏
        unset($column['field']);
        return $column;
    }

    /**
     * 按行处理按钮状态
     * @param mixed $data
     * @param array $column
     * @param array|object $originalData
     * @return mixed
     * @throws Exception
     */
    public function handleValue(mixed $data, array $column = [], array|object $originalData = []): mixed
    {
        if (!is_array($data) || empty($column['field'])) {
            return $data;
        }

        $buttons                = $this->normalizeColumnButtons($column);
        $data[$column['field']] = $this->evaluateButtons($buttons, $data, $originalData);
        return $data;
    }

    /**
     * 归一化当前列的按钮配置
     * @param array $column
     * @return array
     * @throws Exception
     */
    private function normalizeColumnButtons(array $column): array
    {
        $this->ensureBuiltinButtonsLoaded();

        $buttons = $column['options'] ?? [];
        if (is_string($buttons)) {
            $buttons = explode(',', $buttons);
        }

        if (!is_array($buttons)) {
            return [];
        }

        return $this->normalizeButtons($buttons);
    }

    /**
     * 读取内置按钮定义
     * @return void
     */
    private function ensureBuiltinButtonsLoaded(): void
    {
        if ($this->buttons === []) {
            $this->buttons = Config::get('table.actions', []);
        }
    }

    /**
     * 归一化按钮配置
     * @param array $buttons
     * @return array
     * @throws Exception
     */
    private function normalizeButtons(array $buttons): array
    {
        $result = [];

        foreach ($buttons as $type => $button) {
            $button = $this->normalizeButtonDefinition($type, $button);
            if ($button === []) {
                continue;
            }

            $button = $this->normalizeButtonPayload($button);
            if ($button === []) {
                continue;
            }

            $result[] = $button;
        }

        return dp_filter_buttons_by_auth($result);
    }

    /**
     * 归一化单个按钮定义
     * @param int|string $type
     * @param mixed $button
     * @return array
     * @throws Exception
     */
    private function normalizeButtonDefinition(int|string $type, mixed $button): array
    {
        if (is_numeric($type) && is_string($button)) {
            return $this->parseStringButton($button);
        }

        if (!is_numeric($type) && is_array($button)) {
            return array_merge($this->matchButton((string)$type), $button);
        }

        return is_array($button) ? $button : [];
    }

    /**
     * 解析字符串格式的按钮配置
     * @param string $button
     * @return array
     * @throws Exception
     */
    private function parseStringButton(string $button): array
    {
        $button = trim($button);
        if ($button === '') {
            return [];
        }

        if (str_contains($button, ':')) {
            [$type, $dropdownStr] = explode(':', $button, 2);
            $button = $this->matchButton($type);
            if ($button !== []) {
                $button['dropdown'] = $dropdownStr;
                $button['event']    = 'more';
            }
            return $button;
        }

        return $this->matchButton($button);
    }

    /**
     * 归一化按钮属性
     * @param array $button
     * @return array
     * @throws Exception
     */
    private function normalizeButtonPayload(array $button): array
    {
        if (isset($button['dropdown'])) {
            $button['dropdown'] = $this->normalizeDropdown($button['dropdown']);
            $button['event']    = 'more';
        }

        if (isset($button['confirm'])) {
            if ($button['confirm'] === false) {
                unset($button['confirm']);
            } else {
                $button['confirm'] = is_string($button['confirm'])
                    ? [$button['confirm'], '']
                    : (is_array($button['confirm']) ? array_values($button['confirm']) : []);
            }
        }

        if (isset($button['ajax'])) {
            if ($button['ajax'] === false) {
                unset($button['ajax']);
            } else {
                $button['ajax'] = is_string($button['ajax'])
                    ? ['type' => $button['ajax']]
                    : (is_array($button['ajax']) ? $button['ajax'] : []);
            }
        }

        if (isset($button['pop']) && $button['pop'] === false) {
            unset($button['pop']);
        }

        if (isset($button['when'])) {
            $button['when'] = $this->normalizeWhenRules($button['when']);
            if ($button['when'] === []) {
                unset($button['when']);
            }
        }

        if (!empty($button['child'])) {
            $button['child'] = $this->normalizeButtons(
                is_array($button['child']) ? $button['child'] : []
            );
        }

        return $button;
    }

    /**
     * 归一化下拉菜单
     * @param array|string $dropdown
     * @return array
     * @throws Exception
     */
    private function normalizeDropdown(array|string $dropdown): array
    {
        if (is_string($dropdown)) {
            $dropdown = explode('|', $dropdown);
        }

        if (!is_array($dropdown)) {
            throw new Exception(lang('dp#table actions config error'));
        }

        return $this->normalizeButtons($dropdown);
    }

    /**
     * 归一化 when 规则列表
     * @param mixed $when
     * @return array
     */
    private function normalizeWhenRules(mixed $when): array
    {
        if (!is_array($when) || $when === []) {
            return [];
        }

        $rules  = $this->isListArray($when) ? $when : [$when];
        $result = [];

        foreach ($rules as $rule) {
            if (!is_array($rule)) {
                continue;
            }

            $normalized = $this->normalizeWhenRule($rule);
            if ($normalized !== []) {
                $result[] = $normalized;
            }
        }

        return $result;
    }

    /**
     * 归一化单条 when 规则
     * @param array $rule
     * @return array
     */
    private function normalizeWhenRule(array $rule): array
    {
        $normalized = [];

        $state = strtolower(trim((string)($rule['state'] ?? '')));
        if (in_array($state, self::WHEN_STATES, true)) {
            $normalized['state'] = $state;
        }

        if (isset($rule['remark']) && !is_array($rule['remark'])) {
            $normalized['remark'] = (string)$rule['remark'];
        }

        if (isset($rule['callback']) && is_callable($rule['callback'])) {
            $normalized['callback'] = $rule['callback'];
        }

        $logic = strtolower(trim((string)($rule['logic'] ?? 'and')));
        if (!in_array($logic, self::WHEN_LOGICS, true)) {
            $logic = 'and';
        }

        if (isset($rule['rules']) && is_array($rule['rules'])) {
            $matchers = $this->normalizeMatcherRules($rule['rules']);
            if ($matchers !== []) {
                $normalized['logic'] = $logic;
                $normalized['rules'] = $matchers;
            }
        } elseif (isset($rule['field']) || isset($rule['op'])) {
            $matcher = $this->normalizeMatcherRule($rule);
            if ($matcher !== []) {
                $normalized = [...$normalized, ...$matcher];
            }
        }

        if (!isset($normalized['callback']) &&
            !isset($normalized['rules']) &&
            !isset($normalized['field']) &&
            !isset($normalized['op'])) {
            return [];
        }

        if (!isset($normalized['state']) && !isset($normalized['callback'])) {
            return [];
        }

        return $normalized;
    }

    /**
     * 归一化匹配规则列表
     * @param array $rules
     * @return array
     */
    private function normalizeMatcherRules(array $rules): array
    {
        $result = [];

        foreach ($rules as $rule) {
            if (!is_array($rule)) {
                continue;
            }

            $normalized = $this->normalizeMatcherRule($rule);
            if ($normalized !== []) {
                $result[] = $normalized;
            }
        }

        return $result;
    }

    /**
     * 归一化单条匹配规则
     * @param array $rule
     * @return array
     */
    private function normalizeMatcherRule(array $rule): array
    {
        $logic = strtolower(trim((string)($rule['logic'] ?? 'and')));
        if (!in_array($logic, self::WHEN_LOGICS, true)) {
            $logic = 'and';
        }

        if (isset($rule['rules']) && is_array($rule['rules'])) {
            $children = $this->normalizeMatcherRules($rule['rules']);
            if ($children === []) {
                return [];
            }

            return [
                'logic' => $logic,
                'rules' => $children,
            ];
        }

        $field = trim((string)($rule['field'] ?? ''));
        $op    = $this->normalizeOperator($rule['op'] ?? '=');
        if ($field === '' || $op === '') {
            return [];
        }

        return [
            'field' => $field,
            'op'    => $op,
            'value' => $rule['value'] ?? null,
        ];
    }

    /**
     * 计算当前行按钮集合
     * @param array $buttons
     * @param array $data
     * @param array|object $originalData
     * @return array
     */
    private function evaluateButtons(array $buttons, array $data, array|object $originalData): array
    {
        $result = [];

        foreach ($buttons as $button) {
            $evaluated = $this->evaluateButton($button, $data, $originalData);
            if ($evaluated !== null) {
                $result[] = $evaluated;
            }
        }

        return $this->trimSeparators($result);
    }

    /**
     * 计算单个按钮最终状态
     * @param array $button
     * @param array $data
     * @param array|object $originalData
     * @return array|null
     */
    private function evaluateButton(array $button, array $data, array|object $originalData): ?array
    {
        if (($button['type'] ?? '') === '-') {
            return ['type' => '-'];
        }

        $state = $this->evaluateWhenRules($button['when'] ?? [], $data, $originalData);
        if (($state['state'] ?? '') === 'hidden') {
            return null;
        }

        $resolved = $button;
        unset($resolved['when'], $resolved['auth'], $resolved['auth_logic']);

        if (isset($resolved['url'])) {
            $resolved['url'] = $this->replacePlaceholders((string)$resolved['url'], $data, $originalData);
        }

        if (isset($resolved['dropdown'])) {
            $resolved['dropdown'] = $this->evaluateButtons($resolved['dropdown'], $data, $originalData);
            if ($resolved['dropdown'] === []) {
                return null;
            }
            $resolved['event'] = 'more';
        }

        if (isset($resolved['child'])) {
            $resolved['child'] = $this->evaluateButtons($resolved['child'], $data, $originalData);
            if ($resolved['child'] === []) {
                return null;
            }
        }

        if (isset($resolved['pop'])) {
            $resolved = $this->preparePopupConfig($resolved);
        }

        if (($state['state'] ?? '') === 'disabled') {
            $resolved['disabled'] = true;
            if (($state['remark'] ?? '') !== '') {
                $resolved['remark'] = $state['remark'];
            }
        } else {
            unset($resolved['disabled'], $resolved['remark']);
        }

        if (($resolved['type'] ?? '') === 'group' && empty($resolved['child'])) {
            return null;
        }

        return $resolved;
    }

    /**
     * 处理弹窗配置
     * @param array $button
     * @return array
     */
    private function preparePopupConfig(array $button): array
    {
        if (!isset($button['pop'])) {
            return $button;
        }

        if ($button['pop'] === false) {
            unset($button['pop']);
            return $button;
        }

        $button['url'] = $this->appendQueryParam((string)($button['url'] ?? ''));

        $pop = [
            'title'   => $button['title'] ?? '操作',
            'content' => $button['url'],
        ];

        if (is_array($button['pop'])) {
            $button['pop'] = array_merge($pop, $button['pop']);
        } elseif ($button['pop'] === true) {
            $button['pop'] = $pop;
        } else {
            unset($button['pop']);
        }

        return $button;
    }

    /**
     * 计算 when 规则命中结果
     * @param array $rules
     * @param array $data
     * @param array|object $originalData
     * @return array
     */
    private function evaluateWhenRules(array $rules, array $data, array|object $originalData): array
    {
        $result = [];

        foreach ($rules as $rule) {
            $match = $this->evaluateWhenRule($rule, $data, $originalData);
            if ($match === []) {
                continue;
            }

            if (($match['state'] ?? '') === 'hidden') {
                return $match;
            }

            if (($match['state'] ?? '') === 'disabled' && $result === []) {
                $result = $match;
            }
        }

        return $result;
    }

    /**
     * 计算单条 when 规则
     * @param array $rule
     * @param array $data
     * @param array|object $originalData
     * @return array
     */
    private function evaluateWhenRule(array $rule, array $data, array|object $originalData): array
    {
        if (isset($rule['callback']) && is_callable($rule['callback'])) {
            try {
                $result = call_user_func($rule['callback'], $data, $originalData);
            } catch (Throwable) {
                return [];
            }

            if ($result === false || $result === null) {
                return [];
            }

            if ($result === true) {
                return $this->buildWhenState(
                    $rule['state'] ?? '',
                    $rule['remark'] ?? ''
                );
            }

            if (is_array($result)) {
                return $this->buildWhenState(
                    $result['state'] ?? ($rule['state'] ?? ''),
                    $result['remark'] ?? ($rule['remark'] ?? '')
                );
            }

            return [];
        }

        $matched = false;
        if (isset($rule['rules']) && is_array($rule['rules'])) {
            $matched = $this->matchRuleGroup($rule['rules'], $rule['logic'] ?? 'and', $data, $originalData);
        } elseif (isset($rule['field'], $rule['op'])) {
            $matched = $this->matchRule($rule, $data, $originalData);
        }

        if (!$matched) {
            return [];
        }

        return $this->buildWhenState(
            $rule['state'] ?? '',
            $rule['remark'] ?? ''
        );
    }

    /**
     * 计算规则组
     * @param array $rules
     * @param string $logic
     * @param array $data
     * @param array|object $originalData
     * @return bool
     */
    private function matchRuleGroup(array $rules, string $logic, array $data, array|object $originalData): bool
    {
        $results = [];

        foreach ($rules as $rule) {
            if (!is_array($rule)) {
                continue;
            }

            if (isset($rule['rules']) && is_array($rule['rules'])) {
                $results[] = $this->matchRuleGroup($rule['rules'], $rule['logic'] ?? 'and', $data, $originalData);
                continue;
            }

            if (!isset($rule['field'], $rule['op'])) {
                continue;
            }

            $results[] = $this->matchRule($rule, $data, $originalData);
        }

        if ($results === []) {
            return false;
        }

        return strtolower($logic) === 'or'
            ? in_array(true, $results, true)
            : !in_array(false, $results, true);
    }

    /**
     * 计算单条匹配规则
     * @param array $rule
     * @param array $data
     * @param array|object $originalData
     * @return bool
     */
    private function matchRule(array $rule, array $data, array|object $originalData): bool
    {
        $actual = $this->readFieldValue($data, $originalData, (string)$rule['field']);
        $expect = $rule['value'] ?? null;

        return match ($rule['op']) {
            '=', '==' => $actual == $expect,
            '!=', '<>' => $actual != $expect,
            '>', 'gt' => $actual > $expect,
            '>=', 'egt', 'gte' => $actual >= $expect,
            '<', 'lt' => $actual < $expect,
            '<=', 'elt', 'lte' => $actual <= $expect,
            'in' => in_array($actual, $this->normalizeCompareArray($expect), true),
            'not in', 'nin' => !in_array($actual, $this->normalizeCompareArray($expect), true),
            'contains' => $this->containsValue($actual, $expect),
            'not contains' => !$this->containsValue($actual, $expect),
            'empty' => $this->isEmptyValue($actual),
            'not empty' => !$this->isEmptyValue($actual),
            'null' => $actual === null,
            'not null' => $actual !== null,
            default => false,
        };
    }

    /**
     * 构造命中后的状态结果
     * @param mixed $state
     * @param mixed $remark
     * @return array
     */
    private function buildWhenState(mixed $state, mixed $remark = ''): array
    {
        $state = strtolower(trim((string)$state));
        if (!in_array($state, self::WHEN_STATES, true)) {
            return [];
        }

        $result = ['state' => $state];
        if (!is_array($remark) && $remark !== null && (string)$remark !== '') {
            $result['remark'] = (string)$remark;
        }

        return $result;
    }

    /**
     * 标准化比较操作符
     * @param mixed $op
     * @return string
     */
    private function normalizeOperator(mixed $op): string
    {
        $op = strtolower(trim((string)$op));
        return match ($op) {
            '=', '==', 'eq' => '=',
            '!=', '<>', 'ne' => '!=',
            '>', 'gt' => '>',
            '>=', 'egt', 'gte' => '>=',
            '<', 'lt' => '<',
            '<=', 'elt', 'lte' => '<=',
            'in' => 'in',
            'not in', 'nin' => 'not in',
            'contains' => 'contains',
            'not contains' => 'not contains',
            'empty' => 'empty',
            'not empty' => 'not empty',
            'null' => 'null',
            'not null' => 'not null',
            default => '',
        };
    }

    /**
     * 读取当前行字段值
     * @param array $data
     * @param array|object $originalData
     * @param string $field
     * @return mixed
     */
    private function readFieldValue(array $data, array|object $originalData, string $field): mixed
    {
        if ($field === '') {
            return null;
        }

        $value = $this->extractValue($data, $field);
        if ($value !== self::VALUE_NOT_FOUND) {
            return $value;
        }

        $value = $this->extractValue($originalData, $field);
        return $value === self::VALUE_NOT_FOUND ? null : $value;
    }

    /**
     * 从数组或对象中按路径提取值
     * @param mixed $source
     * @param string $path
     * @return mixed
     */
    private function extractValue(mixed $source, string $path): mixed
    {
        if (is_array($source) && array_key_exists($path, $source)) {
            return $source[$path];
        }

        if (is_object($source) && !str_contains($path, '.')) {
            if (isset($source->{$path}) || property_exists($source, $path)) {
                return $source->{$path};
            }

            if (method_exists($source, 'getAttr')) {
                try {
                    return $source->getAttr($path);
                } catch (Throwable) {
                    return self::VALUE_NOT_FOUND;
                }
            }
        }

        if (!str_contains($path, '.')) {
            return self::VALUE_NOT_FOUND;
        }

        $current = $source;
        foreach (explode('.', $path) as $segment) {
            if (is_array($current) && array_key_exists($segment, $current)) {
                $current = $current[$segment];
                continue;
            }

            if (is_object($current)) {
                if (isset($current->{$segment}) || property_exists($current, $segment)) {
                    $current = $current->{$segment};
                    continue;
                }

                if (method_exists($current, 'getAttr')) {
                    try {
                        $current = $current->getAttr($segment);
                        continue;
                    } catch (Throwable) {
                    }
                }
            }

            return self::VALUE_NOT_FOUND;
        }

        return $current;
    }

    /**
     * 替换 URL 中的字段占位符
     * @param string $content
     * @param array $data
     * @param array|object $originalData
     * @return string
     */
    private function replacePlaceholders(string $content, array $data, array|object $originalData): string
    {
        return preg_replace_callback('/__(.*?)__/', function (array $matches) use ($data, $originalData) {
            $value = $this->readFieldValue($data, $originalData, (string)($matches[1] ?? ''));
            if ($value === null || is_array($value) || is_object($value)) {
                return '';
            }

            return (string)$value;
        }, $content) ?? $content;
    }

    /**
     * 清理分隔线
     * @param array $buttons
     * @return array
     */
    private function trimSeparators(array $buttons): array
    {
        $result        = [];
        $prevSeparator = true;

        foreach ($buttons as $button) {
            if (($button['type'] ?? '') === '-') {
                if ($prevSeparator) {
                    continue;
                }

                $result[]      = ['type' => '-'];
                $prevSeparator = true;
                continue;
            }

            $result[]      = $button;
            $prevSeparator = false;
        }

        if ($result !== [] && ($result[array_key_last($result)]['type'] ?? '') === '-') {
            array_pop($result);
        }

        return array_values($result);
    }

    /**
     * 追加查询参数
     * @param string $url
     * @return string
     */
    private function appendQueryParam(string $url): string
    {
        if ($url === '' || str_contains($url, '_pop' . '=')) {
            return $url;
        }

        return $url . (str_contains($url, '?') ? '&' : '?') . '_pop=1';
    }

    /**
     * 判断是否为 list 数组
     * @param array $value
     * @return bool
     */
    private function isListArray(array $value): bool
    {
        return function_exists('array_is_list')
            ? array_is_list($value)
            : array_keys($value) === range(0, count($value) - 1);
    }

    /**
     * 规范化比较数组
     * @param mixed $value
     * @return array
     */
    private function normalizeCompareArray(mixed $value): array
    {
        if (is_array($value)) {
            return array_values($value);
        }

        if (is_string($value)) {
            return array_values(array_filter(array_map('trim', explode(',', $value)), static fn($item) => $item !== ''));
        }

        return [$value];
    }

    /**
     * 判断是否包含指定值
     * @param mixed $actual
     * @param mixed $expect
     * @return bool
     */
    private function containsValue(mixed $actual, mixed $expect): bool
    {
        if (is_array($actual)) {
            return in_array($expect, $actual, true);
        }

        return str_contains((string)$actual, (string)$expect);
    }

    /**
     * 判断是否为空值
     * @param mixed $value
     * @return bool
     */
    private function isEmptyValue(mixed $value): bool
    {
        return $value === null || $value === '' || $value === [];
    }

    /**
     * 匹配内置按钮
     * @param string $name
     * @return array
     * @throws Exception
     */
    private function matchButton(string $name): array
    {
        $this->ensureBuiltinButtonsLoaded();

        $button = $this->buttons[strtolower($name)] ?? [];
        if ($button !== [] && isset($button['event']) && $button['event'] !== 'more') {
            $button['url'] = (string)dp_url($button['event'], ['id' => '__id__']);

            // 自动生成权限标识：应用名.控制器名.按钮event
            // 格式：admin.user.edit、admin.user.delete 等
            if (!isset($button['auth'])) {
                $button['auth'] = dp_generate_auth_code($button['event']);
            }
        }

        return $button;
    }
}
