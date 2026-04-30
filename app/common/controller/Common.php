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

namespace app\common\controller;

use Exception;
use think\App;
use think\exception\ValidateException;
use app\common\Request;
use app\common\trait\Jump;
use app\common\trait\ValidatesRequest;
use app\common\helper\Logger;
use app\common\render\Chart;
use app\common\render\Form;
use app\common\render\Page;
use app\common\render\Table;
use app\common\interface\ChartRender as ChartRenderInterface;
use app\common\interface\PageRender as PageRenderInterface;
use app\common\interface\FormRender as FormRenderInterface;
use app\common\interface\TableRender as TableRenderInterface;
use Throwable;

/**
 * 公共控制器基础类
 */
abstract class Common
{
    use Jump;
    use ValidatesRequest;

    /**
     * 应用实例
     * @var App
     */
    protected App $app;

    /**
     * Request实例
     * @var \think\Request|Request
     */
    protected \think\Request|Request $request;

    /**
     * 是否批量验证
     * @var bool
     */
    protected bool $batchValidate = false;

    /**
     * 控制器中间件
     * @var array
     */
    protected array $middleware = [];

    /**
     * csrf验证配置
     * @var mixed
     */
    public mixed $csrf = null;

    /**
     * 页面实例
     * @var Page
     */
    protected PageRenderInterface $page;

    /**
     * 图表实例
     * @var Chart
     */
    protected ChartRenderInterface $chart;

    /**
     * 表单实例
     * @var Form
     */
    protected FormRenderInterface $form;

    /**
     * 表格实例
     * @var Table
     */
    protected TableRenderInterface $table;

    /**
     * 构造方法
     * @access public
     * @param App $app 应用对象
     */
    public function __construct(
        App $app,
        PageRenderInterface $page,
        ChartRenderInterface $chart,
        FormRenderInterface $form,
        TableRenderInterface $table
    )
    {
        $this->app     = $app;
        $this->page    = $page;
        $this->chart   = $chart;
        $this->form    = $form;
        $this->table   = $table;
        $this->request = $this->app->request;
        $this->csrf    = $this->csrf ?? $this->app->config->get('csrf.enable');

        // 控制器初始化
        $this->initialize();
    }

    /**
     * 初始化
     */
    protected function initialize()
    {
    }

    /**
     * 处理验证异常（保留后台原有日志行为）
     * @param ValidateException $exception
     * @param string|array $validate
     * @param array $data
     * @return void
     */
    protected function handleValidateException(ValidateException $exception, string|array $validate, array $data): void
    {
        dp_log('数据验证失败')
            ->type(Logger::TYPE_SYSTEM)
            ->context([
                'validator'       => $validate,
                'error_message'   => $exception->getError(),
                'failed_data'     => $this->filterSensitiveData($data),
                'controller'      => $this->request->controller(),
                'action'          => $this->request->action(),
                'request_url'     => $this->request->url(),
                'validation_type' => is_array($validate) ? 'rule_array' : 'validator_class'
            ])
            ->error();
        $this->error($exception->getError());
    }

    /**
     * 获取搜索条件（支持 = / like / between / between time / in / 比较运算）
     * 返回结构可直接传给 where()：
     * Db::name('xxx')->where($this->getSearchWhere())->select();
     *
     * 操作符优先级：
     * 1. $allowFields 传入的字段规则
     * 2. table->search() 字段中的 op 配置
     * 3. 默认 '='
     *
     * @param array $allowFields 字段白名单或字段规则
     *  - ['status', 'username']
     *  - ['username' => 'like', 'created_at' => 'between']
     * @param string $namespace 参数命名空间，留空时使用配置 table.search.param
     * @return array
     */
    protected function getSearchWhere(array $allowFields = [], string $namespace = ''): array
    {
        $raw = $this->getRawSearchInput($namespace);
        if ($raw === []) {
            return [];
        }

        $searchRules = $this->getSearchRulesFromTable();
        $likeFields  = $this->getSearchLikeFieldsFromTable();
        $customRules = $this->normalizeAllowFieldRules($allowFields);

        $useWhitelist = !empty($customRules) || !empty($searchRules);
        $allowedMap   = !empty($customRules) ? $customRules : $searchRules;

        $where = [];
        foreach ($this->filterSearchInputEntries($raw, $useWhitelist, $allowedMap) as $entry) {
            $field = $entry['field'];
            $value = $entry['value'];
            $op    = $allowedMap[$field] ?? '=';
            $op    = $this->normalizeSearchOp($op);

            // between/between time 支持字符串区间，不在这里提前过滤空
            if (!in_array($op, ['between', 'between time'], true) && ($value === '' || $value === null)) {
                continue;
            }

            switch ($op) {
                case 'like':
                    if (!is_scalar($value)) {
                        continue 2;
                    }
                    $likeField = $field;
                    if (!empty($likeFields[$field])) {
                        $likeField = implode('|', $likeFields[$field]);
                    }
                    $where[] = [$likeField, 'like', '%' . $value . '%'];
                    break;
                case 'between':
                case 'between time':
                    $range = $this->parseBetweenValue($value);
                    if ($range === null && $op === 'between time') {
                        $range = $this->parseSingleTimeValueAsRange($value);
                    }
                    if ($range === null) {
                        continue 2;
                    }
                    if ($op === 'between time') {
                        $range = $this->normalizeBetweenTimeRange($range);
                    }
                    $where[] = [$field, $op, $range];
                    break;
                case 'in':
                    $list = $this->parseInValue($value);
                    if ($list === []) {
                        continue 2;
                    }
                    $where[] = [$field, 'in', $list];
                    break;
                case '> time':
                case '< time':
                case '>= time':
                case '<= time':
                default:
                    if (is_array($value)) {
                        continue 2;
                    }
                    $where[] = [$field, $op, $value];
                    break;
            }
        }

        return $where;
    }

    /**
     * 获取搜索区原始值（不组装 where）
     *
     * @param array $fields 指定字段白名单，留空时优先按 table->search() 字段限制
     *  - ['status', 'username']
     *  - ['status' => true, 'username' => 1]
     * @param string $namespace 参数命名空间，留空时使用配置 table.search.param
     * @return array
     */
    protected function getSearchData(array $fields = [], string $namespace = ''): array
    {
        $raw = $this->getRawSearchInput($namespace);
        if ($raw === []) {
            return [];
        }

        $searchRules  = $this->getSearchRulesFromTable();
        $customFields = $this->normalizeSearchDataFields($fields);

        $useWhitelist = !empty($customFields) || !empty($searchRules);
        $allowedMap   = !empty($customFields)
            ? $customFields
            : array_fill_keys(array_keys($searchRules), true);

        $result = [];
        foreach ($this->filterSearchInputEntries($raw, $useWhitelist, $allowedMap) as $entry) {
            $field = $entry['field'];
            $value = $entry['value'];
            if ($value === '' || $value === null) {
                continue;
            }

            $result[$field] = $value;
        }

        return $result;
    }

    /**
     * 获取单个搜索字段值
     *
     * @param string $field 字段名
     * @param mixed $default 默认值
     * @param string $namespace 参数命名空间，留空时使用配置 table.search.param
     * @return mixed
     */
    protected function getSearchValue(string $field, mixed $default = null, string $namespace = ''): mixed
    {
        $field = trim($field);
        if ($field === '') {
            return $default;
        }

        $data = $this->getSearchData([$field], $namespace);
        return $data[$field] ?? $default;
    }

    // ====== 搜索配置读取 ======

    /**
     * 读取 table->search() 的字段配置
     * @return array<int, array>
     */
    private function getTableSearchFields(): array
    {
        if (!isset($this->table) || !method_exists($this->table, 'getVar')) {
            return [];
        }

        try {
            $search = $this->table->getVar('dp_table_search');
        } catch (Throwable) {
            return [];
        }

        if (!is_array($search) || !is_array($search['fields'] ?? null)) {
            return [];
        }

        $result = [];
        foreach ($search['fields'] as $field) {
            if (!is_array($field) || empty($field['name'])) {
                continue;
            }

            $name = trim((string)$field['name']);
            if ($name === '') {
                continue;
            }

            $field['name'] = $name;
            $result[]      = $field;
        }

        return $result;
    }

    /**
     * 获取 table->search() 配置中的字段规则
     * @return array<string, string>
     */
    private function getSearchRulesFromTable(): array
    {
        $rules = [];
        foreach ($this->getTableSearchFields() as $field) {
            $name         = (string)$field['name'];
            $rules[$name] = $this->normalizeSearchOp($field['op'] ?? '=');
        }

        return $rules;
    }

    /**
     * 获取 table->search() 配置中的 like 多字段映射
     * @return array<string, array>
     */
    private function getSearchLikeFieldsFromTable(): array
    {
        $result = [];
        foreach ($this->getTableSearchFields() as $field) {
            $name = (string)$field['name'];
            if ($this->normalizeSearchOp($field['op'] ?? '=') !== 'like') {
                continue;
            }

            $targets = dp_normalize_search_fields($field['fields'] ?? []);
            if ($targets !== []) {
                $result[$name] = $targets;
            }
        }

        return $result;
    }

    // ====== 搜索输入读取与过滤 ======

    /**
     * 读取搜索区原始输入
     * @param string $namespace 参数命名空间，留空时读取配置 table.search.param
     * @return array
     */
    private function getRawSearchInput(string $namespace = ''): array
    {
        $namespace = $namespace !== '' ? $namespace : (string)config('table.search.param', '_s');
        $raw       = $this->request->param($namespace, []);
        return is_array($raw) ? $raw : [];
    }

    /**
     * 过滤并规范化搜索输入条目
     * @param array $raw
     * @param bool $useWhitelist
     * @param array $allowedMap
     * @return array<int, array{field:string,value:mixed}>
     */
    private function filterSearchInputEntries(array $raw, bool $useWhitelist, array $allowedMap): array
    {
        $result = [];
        foreach ($raw as $field => $value) {
            if (!is_string($field) && !is_numeric($field)) {
                continue;
            }

            $field = trim((string)$field);
            if ($field === '') {
                continue;
            }

            // 未配置白名单时，至少限制字段名字符集
            if (!$useWhitelist && !preg_match('/^[A-Za-z_][A-Za-z0-9_.]*$/', $field)) {
                continue;
            }

            if ($useWhitelist && !array_key_exists($field, $allowedMap)) {
                continue;
            }

            if (is_string($value)) {
                $value = trim($value);
            }

            // 仅接受标量或数组类型
            if (!is_scalar($value) && !is_array($value)) {
                continue;
            }

            $result[] = ['field' => $field, 'value' => $value];
        }

        return $result;
    }

    /**
     * 规范化字段白名单/规则
     * @param array $allowFields
     * @return array<string, string>
     */
    private function normalizeAllowFieldRules(array $allowFields): array
    {
        $rules = [];
        foreach ($allowFields as $key => $value) {
            if (is_int($key) && is_string($value)) {
                $name = trim($value);
                if ($name !== '') {
                    $rules[$name] = '=';
                }
                continue;
            }

            if (!is_string($key)) {
                continue;
            }

            $name = trim($key);
            if ($name === '') {
                continue;
            }

            $rules[$name] = $this->normalizeSearchOp($value);
        }

        return $rules;
    }

    /**
     * 规范化搜索原值字段白名单
     * @param array $fields
     * @return array<string, bool>
     */
    private function normalizeSearchDataFields(array $fields): array
    {
        $result = [];
        foreach ($fields as $key => $value) {
            if (is_int($key) && (is_string($value) || is_numeric($value))) {
                $name = trim((string)$value);
                if ($name !== '') {
                    $result[$name] = true;
                }
                continue;
            }

            if (is_string($key) && trim($key) !== '') {
                $result[trim($key)] = true;
            }
        }

        return $result;
    }

    /**
     * 规范化搜索操作符
     * @param mixed $op
     * @return string
     */
    private function normalizeSearchOp(mixed $op): string
    {
        $op = strtolower(trim((string)$op));
        $op = preg_replace('/\s+/', ' ', $op);
        return in_array($op, [
            '=',
            '>',
            '>=',
            '<',
            '<=',
            '<>',
            'like',
            'between',
            'between time',
            '> time',
            '< time',
            '>= time',
            '<= time',
            'in',
        ], true) ? $op : '=';
    }

    /**
     * 解析 between 条件值
     * 支持：
     * - ['2026-01-01', '2026-01-31']
     * - '2026-01-01 - 2026-01-31'
     *
     * @param mixed $value
     * @return array|null
     */
    private function parseBetweenValue(mixed $value): ?array
    {
        if (is_array($value)) {
            $items = array_values($value);
            if (count($items) >= 2) {
                $start = is_string($items[0]) ? trim($items[0]) : $items[0];
                $end   = is_string($items[1]) ? trim($items[1]) : $items[1];
                if ($start !== '' && $end !== '' && $start !== null && $end !== null) {
                    return [$start, $end];
                }
            }
            return null;
        }

        if (!is_scalar($value)) {
            return null;
        }

        $text = trim((string)$value);
        if ($text === '') {
            return null;
        }

        if (str_contains($text, ' - ')) {
            $parts = explode(' - ', $text, 2);
        } elseif (str_contains($text, ',')) {
            $parts = explode(',', $text, 2);
        } else {
            return null;
        }

        $start = trim(($parts[0] ?? ''));
        $end   = trim(($parts[1] ?? ''));
        if ($start === '' || $end === '') {
            return null;
        }

        return [$start, $end];
    }

    /**
     * 将单个时间值转为 between time 区间
     * 例如：
     * - 2026-01-03 => ['2026-01-03', '2026-01-03']（后续会补全天边界）
     * - 2026-01-03 10:00:00 => 同起止时间
     *
     * @param mixed $value
     * @return array|null
     */
    private function parseSingleTimeValueAsRange(mixed $value): ?array
    {
        if (!is_scalar($value)) {
            return null;
        }

        $text = trim((string)$value);
        if ($text === '') {
            return null;
        }

        return [$text, $text];
    }

    /**
     * 规范化 between time 的时间边界
     * - 仅日期值（YYYY-MM-DD / YYYY/MM/DD）会自动补全：
     *   start => 00:00:00
     *   end   => 23:59:59
     *
     * @param array $range
     * @return array
     */
    private function normalizeBetweenTimeRange(array $range): array
    {
        $start = $range[0] ?? null;
        $end   = $range[1] ?? null;

        if (is_scalar($start)) {
            $startText = trim((string)$start);
            if (preg_match('/^\d{4}[-\/]\d{1,2}[-\/]\d{1,2}$/', $startText)) {
                $start = str_replace('/', '-', $startText) . ' 00:00:00';
            }
        }

        if (is_scalar($end)) {
            $endText = trim((string)$end);
            if (preg_match('/^\d{4}[-\/]\d{1,2}[-\/]\d{1,2}$/', $endText)) {
                $end = str_replace('/', '-', $endText) . ' 23:59:59';
            }
        }

        return [$start, $end];
    }

    /**
     * 解析 in 条件值
     * @param mixed $value
     * @return array
     */
    private function parseInValue(mixed $value): array
    {
        if (is_array($value)) {
            $items = $value;
        } elseif (is_scalar($value)) {
            $text = trim((string)$value);
            if ($text === '') {
                return [];
            }
            $text  = str_replace('，', ',', $text);
            $items = explode(',', $text);
        } else {
            return [];
        }

        $result = [];
        foreach ($items as $item) {
            if (is_string($item)) {
                $item = trim($item);
            }
            if ($item === '' || $item === null || is_array($item) || is_object($item)) {
                continue;
            }
            $result[] = $item;
        }

        return array_values(array_unique($result, SORT_REGULAR));
    }

    /**
     * 过滤敏感数据用于日志记录
     *
     * @param array $data 原始数据
     * @return array 过滤后的数据
     */
    private function filterSensitiveData(array $data): array
    {
        $sensitiveFields = ['password', 'passwd', 'pwd', 'token', 'secret', 'key', 'captcha'];
        $filtered        = [];

        foreach ($data as $field => $value) {
            if (in_array(strtolower($field), $sensitiveFields)) {
                $filtered[$field] = '***';
            } else {
                // 截断过长的值
                if (is_string($value) && strlen($value) > 200) {
                    $filtered[$field] = substr($value, 0, 200) . '...';
                } elseif (is_array($value)) {
                    $filtered[$field] = '[array]';
                } elseif (is_object($value)) {
                    $filtered[$field] = '[object]';
                } else {
                    $filtered[$field] = $value;
                }
            }
        }

        return $filtered;
    }

    /**
     * 解析和获取模板内容 用于输出
     * @param string $template 模板文件名或者内容
     * @param array $vars 模板变量
     * @return string
     * @throws Exception
     */
    public function fetch(string $template = '', array $vars = []): string
    {
        return $this->page->fetch($template, $vars);
    }

    /**
     * 渲染内容输出
     * @param string $content 内容
     * @param array $vars 模板变量
     * @return string
     */
    public function display(string $content, array $vars = []): string
    {
        return $this->page->display($content, $vars);
    }

    /**
     * 模板变量赋值
     * @param string|array $name 模板变量
     * @param mixed|null $value 变量值
     * @return $this
     */
    public function assign(string|array $name, mixed $value = null): static
    {
        $this->page->assign($name, $value);
        return $this;
    }
}
