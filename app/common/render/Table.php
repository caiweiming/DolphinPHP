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

namespace app\common\render;

use Closure;
use Exception;
use app\common\abstract\TableItem as TableItemAbstract;
use app\common\plugin\PluginRegistry;
use app\common\interface\TableRender as TableRenderInterface;
use think\app\Url;
use think\Container;
use think\exception\HttpResponseException;
use think\facade\Config;
use think\Paginator;
use think\Response;
use think\response\Json;

/**
 * 表格渲染器
 * @package app\common\render
 */
class Table extends Common implements TableRenderInterface
{
    /**
     * 表格实例
     * @var array
     */
    protected array $instances = [];

    /**
     * 模板变量
     * @var array
     */
    protected array $vars = [
        // 表格id
        'dp_table_id'             => 'dp_table',
        // 表格样式名
        'dp_table_class'          => [],
        // 表格列集合
        'dp_table_columns'        => [],
        // 表格字段
        'dp_table_fields'         => [],
        // 表格数据
        'dp_table_data'           => [],
        // 表格数据总数
        'dp_table_total'          => 0,
        // 数据主键名
        'dp_table_pk'             => 'id',
        // 多选列开关
        'dp_table_checkbox'       => true,
        // 多选列固定方式
        'dp_table_checkbox_fixed' => '',
        // 弹窗配置
        'dp_table_dialog'         => [],
        // 树形表格
        'dp_table_tree'           => false,
        // 表格配置选项
        'dp_table_options'        => [],
        // 搜索区配置
        'dp_table_search'         => [],
        // 顶部提示
        'dp_table_alert_top'      => [],
        // 底部提示
        'dp_table_alert_bottom'   => [],
        // 额外html代码
        'dp_table_extra_html'     => [
            'top'        => [],
            'bottom'     => [],
            'search_top' => []
        ],
    ];

    // 默认列类型
    private const NORMAL_TYPES = ['normal', 'checkbox', 'radio', 'numbers', 'space'];

    // 编辑列类型
    private const EDIT_TYPES = ['text.edit', 'textarea.edit'];

    /**
     * 内置表格列类型
     * @var array
     */
    protected array $types = [];

    /**
     * 当前表格列
     * @var array
     */
    protected array $currColumn = [];

    /**
     * @var mixed 表格原始数据
     */
    private mixed $data = null;

    /**
     * 表格列实例
     * @var array
     */
    private array $columnInstances = [];

    /**
     * 是否已编译
     * @var bool
     */
    protected bool $compiled = false;

    /**
     * 列是否已编译
     * @var bool
     */
    protected bool $columnsCompiled = false;

    /**
     * 数据方法名
     * @var string
     */
    protected string $dataMethod = 'data';

    /**
     * CRUD Token
     * @var string
     */
    protected string $crudToken = '';

    /**
     * CRUD 表名（用于生成 token）
     * @var string
     */
    protected string $crudTableName = '';

    /**
     * CRUD 表名类型（name|table）
     * @var string
     */
    protected string $crudTableType = 'name';

    /**
     * quickEdit 验证配置
     * @var array{enabled:bool,validator:string,fields:array}
     */
    protected array $quickEditValidate = [
        'enabled'   => false,
        'validator' => '',
        'fields'    => [],
    ];

    /**
     * 初始化
     */
    protected function initialize(): void
    {
        parent::initialize();
        $this->types         = Config::get('table.types', []);
        $this->crudTableName = $this->getDefaultCrudTableName();
        $this->crudTableType = 'name';
        $this->refreshCrudToken();

        // 表格默认配置
        $this->vars['dp_table_options'] = Config::get('table.options', []);
        // 数据请求url
        $this->vars['dp_table_options']['url'] = $this->request->baseUrl();
        // 分页参数
        $this->vars['dp_table_options']['page'] = $this->getDefaultPage();
        // 其他请求参数
        $this->vars['dp_table_options']['where'] = $this->request->except(['page', 'limit']);
        // 搜索参数命名空间
        $this->vars['dp_table_options']['searchParam'] = Config::get('table.search.param', '_s');
        // 搜索配置
        $this->vars['dp_table_search'] = Config::get('table.search', []);
        // 弹窗配置
        $this->vars['dp_table_dialog'] = Config::get('table.dialog', []);
    }

    /**
     * 创建表格实例 已经存在则直接获取
     * @param string $id 表格id
     * @return $this
     * @throws Exception
     */
    public function init(string $id = ''): static
    {
        if ($id == '') {
            throw new Exception(lang('dp#undefined table id'));
        }

        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        $object                      = Container::getInstance()->make(static::class, [], true);
        $object->vars['dp_table_id'] = $id;
        $this->instances[$id]        = $object;
        return $object;
    }

    /**
     * 静态方式创建表格实例
     * @param string $id 表格id
     * @return static
     * @throws Exception
     */
    public static function make(string $id = ''): static
    {
        $object = Container::getInstance()->make(static::class);
        return $object->init($id);
    }

    /**
     * 模板变量赋值
     * @param string|array $name 模板变量
     * @param mixed|null $value 变量值
     * @return $this
     */
    public function assign(string|array $name, mixed $value = null): static
    {
        $this->view->assign($name, $value);
        return $this;
    }

    /**
     * 渲染内容输出
     * @param string $content 内容
     * @param array $vars 模板变量
     * @return string
     */
    public function display(string $content, array $vars = []): string
    {
        $vars = array_merge($this->vars, $vars);
        return $this->view->display($content, $vars);
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
        // 统一通过 assets() 注册默认资源，避免多入口重复注入
        $this->assets();

        // 编译表格
        $this->compile();

        return $this->finalizeFetch($template, $vars);
    }

    /**
     * 渲染页面或返回表格数据
     * @return Json|$this
     * @throws Exception
     */
    public function render(): Json|static
    {
        $this->renderPage();
        return $this->respondData();
    }

    /**
     * 渲染页面结构（列编译）
     * @return static
     * @throws Exception
     */
    public function renderPage(): static
    {
        $this->compileSearch();
        $this->compileColumns();
        return $this;
    }

    /**
     * 响应表格数据请求
     * @return Json|static
     * @throws Exception
     */
    public function respondData(): Json|static
    {
        if (!$this->request->isAjax()) {
            return $this;
        }

        $tableId = $this->request->param(Config::get('table.var'), 'dp_table');
        if ($tableId !== $this->vars['dp_table_id']) {
            return $this;
        }

        $this->handleData();
        $this->compileData();

        $response = Response::create([
            'code'  => 0,
            'msg'   => '',
            'count' => $this->vars['dp_table_total'],
            'data'  => $this->vars['dp_table_data'],
        ], 'json');
        throw new HttpResponseException($response);
    }

    /**
     * 设置表格id
     * @param string $id
     * @return $this
     */
    public function id(string $id = ''): static
    {
        if ($id != '') {
            $this->vars['dp_table_id'] = $id;
        }
        return $this;
    }

    /**
     * 设置静态资源，css或js文件
     * @return array
     */
    public function assets(): array
    {
        // 添加默认加载的静态资源到AssetManager
        $this->assetManager->addJs(dp_static_render_path() . 'table/table.js', 50, [], 'table-js');
        $this->assetManager->addCss(dp_static_render_path() . 'table/table.css', 50, [], 'table-css');

        // 获取AssetManager管理的资源
        return $this->assetManager->getAssets();
    }

    /**
     * 添加html代码
     * @param string $content
     * @param string $pos
     * @return $this
     */
    public function html(string $content, string $pos = 'bottom'): static
    {
        if (empty($content)) {
            return $this;
        }

        $this->vars['dp_table_extra_html'][$pos][] = $content;
        return $this;
    }

    /**
     * 设置表格提示信息
     * @param string|array $content 提示内容
     * @param string $title 提示标题
     * @param string $type 提示类型：success,info,danger,warning
     * @param string $pos 显示位置：top,bottom
     * @return $this
     */
    public function alert(string|array $content = '', string $title = '', string $type = 'info', string $pos = 'top'): static
    {
        if (empty($content)) {
            return $this;
        }

        if (is_array($content)) {
            $content = implode('<br>', $content);
        }

        $extra = [];
        if (str_contains($type, ':')) {
            [$type, $extra] = explode(':', $type, 2);
            $extra = explode(',', $extra);
        }

        $pos                                    = $pos === 'bottom' ? 'bottom' : 'top';
        $this->vars['dp_table_alert_' . $pos][] = [
            'content' => $content,
            'title'   => $title,
            'type'    => $type,
            'icon'    => in_array('icon', $extra, true),
            'close'   => in_array('close', $extra, true),
        ];

        return $this;
    }

    /**
     * 搜索区 DSL 配置
     * 新签名：search($fields, $buttons, $options)
     * 兼容旧签名：search(['fields' => [], 'buttons' => [], 'layout' => []])
     *
     * @param mixed $fields
     * @param array $buttons
     * @param array $options
     * @return static
     */
    public function search(mixed $fields = [], array $buttons = [], array $options = []): static
    {
        $search = $this->vars['dp_table_search'] ?? [];
        if (!is_array($search)) {
            $search = [];
        }

        // 仅在使用点兜底，避免配置缺键时报错
        $search['fields']            = is_array($search['fields'] ?? null) ? $search['fields'] : [];
        $search['class']             = is_string($search['class'] ?? null) && $search['class'] !== ''
            ? $search['class']
            : 'layui-form layui-row layui-col-space16 dp-table-search-form';
        $search['field_col_class']   = is_string($search['field_col_class'] ?? null) && $search['field_col_class'] !== ''
            ? $search['field_col_class']
            : 'layui-col-md4';
        $search['button_col_class']  = is_string($search['button_col_class'] ?? null) && $search['button_col_class'] !== ''
            ? $search['button_col_class']
            : 'layui-col-xs12';
        $search['buttons']           = is_array($search['buttons'] ?? null) ? $search['buttons'] : [];
        $search['buttons']['submit'] = is_array($search['buttons']['submit'] ?? null) ? $search['buttons']['submit'] : [];
        $search['buttons']['reset']  = is_array($search['buttons']['reset'] ?? null) ? $search['buttons']['reset'] : [];

        // 兼容旧行为：search(false) 显式关闭搜索区
        if (is_bool($fields)) {
            if ($fields === false) {
                $search['fields'] = [];
            }
            $this->vars['dp_table_search'] = $search;
            return $this;
        }

        // 兼容字符串简写：search('username,nickname,status')
        if (is_string($fields) || is_numeric($fields)) {
            $fields = dp_normalize_search_fields($fields);
        }

        // 兼容签名：search(['fields' => ..., 'buttons' => ..., 'layout' => ...])
        if (is_array($fields) && $this->isSearchConfigArray($fields)) {
            if (array_key_exists('enabled', $fields) && !$fields['enabled']) {
                $search['fields']              = [];
                $this->vars['dp_table_search'] = $search;
                return $this;
            }

            $buttons = is_array($fields['buttons'] ?? null) ? $fields['buttons'] : $buttons;

            if (isset($fields['layout']) && is_array($fields['layout'])) {
                $options['layout'] = $fields['layout'];
            }
            if (isset($fields['class']) && is_string($fields['class']) && $fields['class'] !== '') {
                $options['class'] = $fields['class'];
            }

            $fields = $fields['fields'] ?? [];
        }

        if (isset($options['class']) && is_string($options['class']) && $options['class'] !== '') {
            $search['class'] = $options['class'];
        }

        if (isset($options['layout']) && is_array($options['layout'])) {
            if (!empty($options['layout']['field_col_class'])) {
                $search['field_col_class'] = (string)$options['layout']['field_col_class'];
            }
            if (!empty($options['layout']['button_col_class'])) {
                $search['button_col_class'] = (string)$options['layout']['button_col_class'];
            }
        }

        // 允许单字段对象写法：search(['name' => 'keyword'])
        if (is_array($fields) && !array_is_list($fields) && (isset($fields['name']) || isset($fields['type']))) {
            $fields = [$fields];
        }

        if (is_array($fields)) {
            $normalizedFields = [];
            foreach ($fields as $key => $field) {
                // 支持映射写法：['username' => '用户名']
                if (is_string($key) && (is_string($field) || is_numeric($field))) {
                    $name = trim($key);
                    if ($name === '') {
                        continue;
                    }
                    $normalizedFields[] = [
                        'name'        => $name,
                        'placeholder' => trim((string)$field),
                        'col_class'   => $search['field_col_class'],
                        '_auto'       => true,
                    ];
                    continue;
                }

                if (is_string($field) || is_numeric($field)) {
                    $name = trim((string)$field);
                    if ($name === '') {
                        continue;
                    }
                    $normalizedFields[] = [
                        'name'      => $name,
                        'col_class' => $search['field_col_class'],
                        '_auto'     => true,
                    ];
                    continue;
                }

                if (!is_array($field)) {
                    continue;
                }

                $normalizedField = $this->normalizeSearchField($field, $search['field_col_class']);
                if ($normalizedField !== null) {
                    $normalizedFields[] = $normalizedField;
                }
            }
            $search['fields'] = $normalizedFields;
        }

        $this->applySearchButtons($search, $buttons);
        if (isset($options['buttons']) && is_array($options['buttons'])) {
            $this->applySearchButtons($search, $options['buttons']);
        }

        $this->vars['dp_table_search'] = $search;
        return $this;
    }

    /**
     * 添加表格列
     * @param string | array $field 字段名
     * @param string $title 列标题
     * @param mixed $type 列类型
     * @param mixed $options 列选项
     * @param array $cols 列属性
     * @return $this
     * @throws Exception
     */
    public function column(
        string|array $field = '',
        string       $title = '',
        mixed        $type = 'normal',
        mixed        $options = [],
        array        $cols = []
    ): static
    {
        if (empty($field)) {
            return $this;
        }

        $this->currColumn = [
            'field'    => $field,
            'title'    => $title,
            'type'     => $type,
            'options'  => $options,
            'cols'     => $cols,
            'callback' => null,
        ];

        if (is_array($field)) {
            $this->currColumn = array_merge($this->currColumn, $field);
        } elseif ($field instanceof Closure) {
            $this->currColumn = array_merge($this->currColumn, $field($this));
        }

        // 处理列类型
        $this->handleColumnType();

        // 表格id
        $this->currColumn['table_id'] = $this->vars['dp_table_id'];
        // CRUD token
        $this->currColumn['crud_token'] = $this->crudToken;

        // 添加到字段列表
        if (!empty($this->currColumn['field'] ?? '')) {
            $this->vars['dp_table_fields'][] = $this->currColumn;
        }

        $this->vars['dp_table_columns'][0][] = $this->currColumn;
        return $this;
    }

    /**
     * 批量添加表格列
     * @param array $columns 列定义数组
     * @return $this
     * @throws Exception
     */
    public function columns(array $columns = []): static
    {
        foreach ($columns as $column) {
            if (!is_array($column)) {
                continue;
            }

            // 确保至少有字段名和标题
            if (count($column) < 2) {
                continue;
            }

            // 解析参数
            $field   = $column[0] ?? '';
            $title   = $column[1] ?? '';
            $type    = $column[2] ?? 'normal';
            $options = $column[3] ?? [];
            $cols    = $column[4] ?? [];

            // 调用单个column方法
            $this->column($field, $title, $type, $options, $cols);
        }

        return $this;
    }

    /**
     * 设置数据请求url
     * @param string|Url $url
     * @return $this
     */
    public function url(string|Url $url = ''): static
    {
        if ($url != '') {
            $this->vars['dp_table_options']['url'] = $url instanceof Url ? $url->build() : $url;
        }
        return $this;
    }

    /**
     * 启用树形表格模式
     * @param bool $enable 是否启用或自定义树形配置
     * @return static
     */
    public function tree(bool $enable = false): static
    {
        $this->vars['dp_table_tree'] = $enable;
        return $this;
    }

    /**
     * 设置表格数据
     * @param null $data
     * @param string $method
     * @return $this
     */
    public function data($data = null, string $method = ''): static
    {
        if ($data !== null) {
            if (is_string($data) && str_starts_with($data, 'http')) {
                $this->vars['dp_table_options']['url'] = $data;
            } elseif ($data instanceof Url) {
                $this->vars['dp_table_options']['url'] = $data->build();
            } else {
                $this->data = $data;
            }

            if ($method != '') {
                $this->dataMethod = $method;
            }
        }

        return $this;
    }

    /**
     * 设置 CRUD 操作的表名
     * 自动生成 Token 并传递给前端，用于快速编辑等 CRUD 操作
     *
     * @param string $tableName 表名（不含前缀）或完整表名
     * @param string $type 类型：name(不含前缀) 或 table(完整表名)，默认 name
     * @return $this
     */
    public function tableName(string $tableName, string $type = 'name'): static
    {
        if ($tableName !== '') {
            $this->crudTableName = $tableName;
            $this->crudTableType = $type === 'table' ? 'table' : 'name';
            $this->refreshCrudToken();
        }
        return $this;
    }

    /**
     * 设置 quickEdit 验证器
     * 用法：
     * 1. validate() 启用默认约定（控制器同名验证器）
     * 2. validate(false) 关闭 quickEdit 验证
     * 3. validate('User')
     * 4. validate('User', 'mobile,status') 仅校验指定字段
     *
     * @param bool|string $validate
     * @param string|array $fields
     * @return $this
     * @throws Exception
     */
    public function validate(bool|string $validate = true, string|array $fields = []): static
    {
        if ($validate === false) {
            $this->quickEditValidate = [
                'enabled'   => false,
                'validator' => '',
                'fields'    => [],
            ];
            $this->refreshCrudToken();
            return $this;
        }

        $validator = '';
        if (is_string($validate)) {
            $validator = trim($validate);
            if ($validator !== '' && str_contains($validator, '.')) {
                throw new Exception('Table::validate() 不支持验证场景，请仅传入验证器名，例如 User');
            }
        }

        $this->quickEditValidate = [
            'enabled'   => true,
            'validator' => $validator,
            'fields'    => $this->parseQuickEditValidateFields($fields),
        ];

        $this->refreshCrudToken();
        return $this;
    }

    /**
     * 设置分页参数
     * @param array $options
     * @return $this
     */
    public function page(array $options = []): static
    {
        foreach (['elem', 'jump'] as $item) {
            unset($options[$item]);
        }
        $this->vars['dp_table_options']['page'] = array_merge($this->vars['dp_table_options']['page'], $options);
        return $this;
    }

    /**
     * 右侧按钮快捷操作
     * @param mixed $options
     * @param string $title
     * @return $this
     * @throws Exception
     */
    public function actions(mixed $options, string $title = '操作'): static
    {
        return $this->column('right_button', $title, 'actions', $options);
    }

    /**
     * 多选列开关
     * @param bool | string $status
     * @return $this
     */
    public function checkbox(bool|string $status = true): static
    {
        $this->vars['dp_table_checkbox']       = $status ?? true;
        $this->vars['dp_table_checkbox_fixed'] = is_bool($status) ? '' : $status;
        return $this;
    }

    /**
     * 设置主键
     * @param string $pk
     * @return $this
     */
    public function primaryKey(string $pk = ''): static
    {
        if ($pk != '') {
            $this->vars['dp_table_pk'] = $pk;
        }
        return $this;
    }

    /**
     * 设置表格属性
     * 详细属性参考layui：https://layui.dev/docs/2/table/#options-intro
     * @param array $options
     * @return $this
     */
    public function options(array $options = []): static
    {
        $this->vars['dp_table_options'] = array_merge($this->vars['dp_table_options'], $options);
        return $this;
    }

    /**
     * 设置表格提交方式
     * @param string $method
     * @return $this
     */
    public function method(string $method = 'get'): static
    {
        $this->vars['dp_table_options']['method'] = $method;
        return $this;
    }

    /**
     * 设置表格工具栏
     * @param string|bool|array $options 工具栏左侧
     * @param array $defaultToolbar 工具栏右侧
     * @param bool $replace 是否覆盖默认按钮
     * @return $this
     */
    public function toolbar(string|bool|array $options = false, array $defaultToolbar = ['filter', 'exports', 'print'], bool $replace = false): static
    {
        // 设置默认工具栏
        $this->vars['dp_table_options']['defaultToolbar'] = $defaultToolbar;

        // 根据options类型进行处理
        if ($options === 'default') {
            $buttons = $this->filterToolbarByAuth(Config::get('table.toolbar.left'));
            $buttons = $this->processButtonAttributes($buttons);
            $html    = implode(' ', $buttons);
        } elseif (is_string($options) && !str_starts_with($options, '#')) {
            $html = $options;
        } elseif (is_array($options)) {
            $defaultButtons = array_column(Config::get('table.toolbar.left'), null, 'name');

            if (!$replace) {
                $options[] = 'reload';
            }

            $buttons = array_map(function ($option) use ($defaultButtons) {
                if (is_string($option)) {
                    return $defaultButtons[$option] ?? $option;
                }

                if (is_array($option) && isset($option['name']) && isset($defaultButtons[$option['name']])) {
                    return array_merge($defaultButtons[$option['name']], $option);
                }

                return $option;
            }, $options);

            // 权限过滤
            $buttons = $this->filterToolbarByAuth($buttons);
            $html    = implode(' ', $this->processButtonAttributes($buttons));
        } else {
            $this->vars['dp_table_options']['toolbar'] = $options;
            return $this;
        }

        // 设置工具栏HTML
        $this->vars['dp_table_options']['toolbar'] = '<div>' . $html . '</div>';

        return $this;
    }

    /**
     * 编译表格
     * @throws Exception
     */
    private function compile(): void
    {
        if (!$this->compiled) {
            // 获取布局文件
            $this->getLayoutTemplate('table');
            $this->vars['dp_table_options']['where'][Config::get('table.var')] = $this->vars['dp_table_id'];

            // 如果设置了 CRUD Token，添加到表格配置中
            if ($this->crudToken !== '') {
                $this->vars['dp_table_options']['crudToken'] = $this->crudToken;
            }

            $this->compileSearch();

            $this->vars['dp_table_options'] = dp_parse_options($this->vars['dp_table_options']);
            $this->vars['dp_table_class']   = implode(' ', array_unique($this->vars['dp_table_class']));
            $this->compiled                 = true;
        }
    }

    /**
     * 编译表格列
     * @throws Exception
     */
    private function compileColumns(): void
    {
        if ($this->columnsCompiled) {
            return;
        }

        // 处理表格多选列
        $columnCheckbox = [
            'field' => 'dp_key',
            'type'  => 'checkbox',
            'fixed' => $this->vars['dp_table_checkbox_fixed']
        ];

        if ($this->vars['dp_table_checkbox']) {
            array_unshift($this->vars['dp_table_columns'][0], $columnCheckbox);
        }

        foreach ($this->vars['dp_table_columns'][0] as $key => $column) {
            $this->vars['dp_table_columns'][0][$key] = $this->handleOptions($this->handleColumn($column));
        }

        $this->vars['dp_table_options']['cols'] = $this->vars['dp_table_columns'];
        $this->columnsCompiled                  = true;
    }

    /**
     * 编译表格行数据（递归处理，支持树形结构）
     */
    private function compileData(): void
    {
        $this->processDataRecursive($this->vars['dp_table_data'], $this->data);
    }

    /**
     * 递归处理数据节点
     * @param array &$data 数据引用
     * @param mixed $originalData 原始数据
     * @return void
     */
    private function processDataRecursive(array &$data, mixed $originalData): void
    {
        foreach ($data as $key => &$item) {
            // 另外拷贝一份主键值
            $item['dp_pk'] = $item[$this->vars['dp_table_pk']] ?? '';

            // 处理字段值
            foreach ($this->vars['dp_table_fields'] as $dp_table_field) {
                $item = $this->handleValue($item, $dp_table_field, $originalData[$key] ?? $item);
            }

            // 如果有子节点，递归处理
            if (!empty($item['children']) && is_array($item['children'])) {
                $childrenOriginal = $originalData[$key]['children'] ?? $item['children'];
                $this->processDataRecursive($item['children'], $childrenOriginal);
            }
        }
    }

    /**
     * 处理表格列
     * @param array $column
     * @return array
     * @throws Exception
     */
    private function handleColumn(array $column): array
    {
        $type           = dp_normalize_extension_path((string)($column['type'] ?? ''));
        $column['type'] = $type;

        // 处理默认类型
        if ($type === '' || in_array($type, self::NORMAL_TYPES, true)) {
            return $column;
        }

        // 处理可编辑类型
        if (in_array($type, self::EDIT_TYPES, true)) {
            return [
                ...$column,
                'edit' => strstr($type, '.', true) ?: $type,
                'type' => 'normal'
            ];
        }

        $class = $this->resolveColumnTypeClass($type);
        if ($class === '') {
            return $column;
        }

        if (!isset($this->columnInstances[$class])) {
            try {
                $this->columnInstances[$class] = invoke($class);
            } catch (Exception) {
                throw new Exception(lang('dp#undefined class', ['class' => $class]));
            }
        }

        $column = $this->columnInstances[$class]->handle($column, $this);

        // 非layui内置类型，统一设置为normal
        if (!in_array($column['type'], self::NORMAL_TYPES)) {
            $column['type'] = 'normal';
        }

        return $column;
    }

    /**
     * 解析列类型处理类（内置优先，扩展约定兜底）
     * @param string $type
     * @return string
     * @throws Exception
     */
    private function resolveColumnTypeClass(string $type): string
    {
        if ($type === '') {
            return '';
        }

        $type = dp_normalize_extension_path($type);

        // 内置类型
        if (isset($this->types[$type]) && is_string($this->types[$type]) && $this->types[$type] !== '') {
            return $this->types[$type];
        }

        $pluginClass = app(PluginRegistry::class)->getTableItemClass($type);
        if ($pluginClass !== '') {
            $this->types[$type] = $pluginClass;
            return $pluginClass;
        }

        // 约定优先：未注册类型按 extend/table/<type>/Item.php 自动解析
        $customClass = $this->resolveExtendColumnTypeClass($type);
        if ($customClass === '') {
            return '';
        }

        $this->types[$type] = $customClass;
        return $customClass;
    }

    /**
     * 解析扩展列类型类名
     * @param string $type
     * @return string
     * @throws Exception
     */
    private function resolveExtendColumnTypeClass(string $type): string
    {
        $type = dp_normalize_extension_path($type);

        // 允许 foo、foo.bar、foo/bar、foo\bar
        if (!preg_match('/^[A-Za-z][A-Za-z0-9_]*(?:[.\/\\][A-Za-z][A-Za-z0-9_]*)*$/', $type)) {
            return '';
        }

        $namespacePath = str_replace(['.', '/', '\\'], '\\', $type);
        $relativePath  = str_replace(['.', '/', '\\'], DIRECTORY_SEPARATOR, $type);
        $class         = 'table\\' . $namespacePath . '\\Item';
        $classFile     = dp_extend_table_path() . $relativePath . DIRECTORY_SEPARATOR . 'Item.php';

        if (!is_file($classFile)) {
            return '';
        }

        if (!class_exists($class)) {
            return '';
        }

        if (!is_subclass_of($class, TableItemAbstract::class)) {
            throw new Exception("Class $class must extend " . TableItemAbstract::class);
        }

        return $class;
    }

    /**
     * 处理表格列选项
     * @param array $column
     * @return array
     */
    private function handleOptions(array $column): array
    {
        return [
            'field'    => $column['field'] ?? '',
            'title'    => $column['title'] ?? '',
            'fixed'    => $column['fixed'] ?? '',
            'edit'     => $column['edit'] ?? false,
            'type'     => $column['type'],
            'minWidth' => 100,
            ...$column['cols'] ?? []
        ];
    }

    /**
     * 处理表格值
     * @param array $data
     * @param array $column
     * @param array|object $originalData
     * @return array
     */
    private function handleValue(array $data, array $column, array|object $originalData): array
    {
        $columnInstance = $this->columnInstances[$this->types[$column['type']] ?? null] ?? null;
        if ($columnInstance) {
            $data = $columnInstance->handleValue($data, $column, $originalData);
        }
        return $data;
    }

    /**
     * 处理数据
     * @throws Exception
     */
    private function handleData(): void
    {
        $this->data = match (true) {
            $this->data instanceof Closure => call_user_func($this->data),
            $this->data instanceof Paginator => $this->data,
            is_string($this->data) || is_object($this->data) => $this->dataCall($this->data),
            default => $this->dataCall('data')
        };

        if ($this->data instanceof Paginator) {
            $data                         = $this->data->toArray();
            $this->vars['dp_table_data']  = $data['data'];
            $this->vars['dp_table_total'] = $data['total'];
        } else {
            $this->vars['dp_table_data']  = $this->data;
            $this->vars['dp_table_total'] = count($this->data);
        }

        // 给每一行数据添加原始数据（递归处理，支持树形结构）
        $this->addDataToTree($this->vars['dp_table_data'], $this->data);
    }

    /**
     * 处理列类型
     */
    private function handleColumnType(): void
    {
        $this->currColumn['type'] = $this->currColumn['type'] == '' ? 'normal' : $this->currColumn['type'];

        // 统一处理闭包情况
        $closureFields = ['type', 'options', 'callback'];
        foreach ($closureFields as $field) {
            if (isset($this->currColumn[$field]) && $this->currColumn[$field] instanceof Closure) {
                if ($field !== 'callback') {
                    $this->currColumn['callback'] = $this->currColumn[$field];
                }
                $this->currColumn['type'] = 'callback';
                break;
            }
        }

        // 处理数组类型
        if (is_array($this->currColumn['type'])) {
            $data_list                    = $this->currColumn['type'];
            $this->currColumn['type']     = 'callback';
            $this->currColumn['callback'] = function ($item) use ($data_list) {
                return $data_list[$item] ?? '';
            };
        }
    }

    /**
     * 编译搜索区配置
     * @return void
     */
    private function compileSearch(): void
    {
        $search = $this->vars['dp_table_search'] ?? [];
        if (!is_array($search)) {
            $search = [];
        }

        $search['fields'] = is_array($search['fields'] ?? null) ? $search['fields'] : [];
        if ($search['fields'] === []) {
            $this->vars['dp_table_search'] = $search;
            return;
        }

        foreach ($search['fields'] as $index => $field) {
            if (!is_array($field)) {
                unset($search['fields'][$index]);
                continue;
            }

            $field = $this->hydrateSearchFieldFromColumn($field, $search['field_col_class'] ?? 'layui-col-md4');
            if ($field === null) {
                unset($search['fields'][$index]);
                continue;
            }

            $name = $field['name'] ?? '';
            if ($name === '') {
                continue;
            }

            if (!array_key_exists('value', $field) || $field['value'] === null) {
                $field['value'] = $this->request->param($name, '');
            }

            if (($field['type'] ?? 'text') === 'select' && !isset($field['options'])) {
                $field['options'] = [];
            }

            unset($field['_auto']);
            $search['fields'][$index] = $field;
        }

        $search['fields']              = array_values($search['fields']);
        $this->vars['dp_table_search'] = $search;
    }

    /**
     * 根据表格列配置补全搜索字段（用于字符串简写）
     * @param array $field
     * @param string $defaultColClass
     * @return array|null
     */
    private function hydrateSearchFieldFromColumn(array $field, string $defaultColClass): ?array
    {
        $name = trim((string)($field['name'] ?? ''));
        if ($name === '') {
            return null;
        }

        if (empty($field['_auto'])) {
            return $field;
        }

        $column = $this->findTableColumnByField($name);
        $title  = $this->extractColumnTitle($column, $name);

        $type = 'text';
        if (isset($column['type']) && is_string($column['type'])) {
            $columnType = strtolower(trim($column['type']));
            if (in_array($columnType, ['date', 'datetime'], true)) {
                $type = 'date';
            }
        }

        if ($type === 'text' && is_array($column['options'] ?? null) && $this->isSimpleOptionsMap($column['options'])) {
            $type = 'select';
        }

        $config = [
            'name'      => $name,
            'type'      => $type,
            'label'     => '',
            'col_class' => (string)($field['col_class'] ?? $defaultColClass),
            '_auto'     => true,
        ];

        if ($type === 'select') {
            $config['options'] = $column['options'] ?? [];
        }

        if ($type === 'date') {
            $config['range'] = false;
        }

        $config['placeholder'] = $type === 'text' ? ('请输入' . $title) : ('请选择' . $title);

        // 字符串简写允许被手工字段配置覆盖
        $config = array_merge($config, $field);

        return $this->normalizeSearchField($config, $defaultColClass);
    }

    /**
     * 通过字段名查找已定义列
     * @param string $fieldName
     * @return array
     */
    private function findTableColumnByField(string $fieldName): array
    {
        $columns = $this->vars['dp_table_fields'] ?? [];
        if (!is_array($columns)) {
            return [];
        }

        // 1) 精确匹配完整字段名（优先）
        foreach ($columns as $column) {
            if (!is_array($column)) {
                continue;
            }

            $columnField = $column['field'] ?? '';
            if (!is_string($columnField) && !is_numeric($columnField)) {
                continue;
            }

            if (trim((string)$columnField) === $fieldName) {
                return $column;
            }
        }

        // 2) 回退匹配：使用点号后的末段（仅在唯一命中时生效，避免歧义）
        $tail = str_contains($fieldName, '.') ? trim((string)strrchr($fieldName, '.'), '.') : $fieldName;
        if ($tail === '') {
            return [];
        }

        $matched = [];
        foreach ($columns as $column) {
            if (!is_array($column)) {
                continue;
            }

            $columnField = $column['field'] ?? '';
            if (!is_string($columnField) && !is_numeric($columnField)) {
                continue;
            }

            $columnField = trim((string)$columnField);
            if ($columnField === '') {
                continue;
            }

            $columnTail = str_contains($columnField, '.') ? trim((string)strrchr($columnField, '.'), '.') : $columnField;
            if ($columnTail === $tail) {
                $matched[] = $column;
            }
        }

        if (count($matched) === 1) {
            return $matched[0];
        }

        return [];
    }

    /**
     * 提取列标题文本
     * @param array $column
     * @param string $fallback
     * @return string
     */
    private function extractColumnTitle(array $column, string $fallback): string
    {
        $title = (string)($column['title'] ?? '');
        $title = trim(strip_tags(html_entity_decode($title, ENT_QUOTES)));
        return $title !== '' ? $title : $fallback;
    }

    /**
     * 判断是否是简单选项映射（value => label）
     * @param array $options
     * @return bool
     */
    private function isSimpleOptionsMap(array $options): bool
    {
        if ($options === []) {
            return false;
        }

        foreach ($options as $label) {
            if (is_array($label) || $label instanceof Closure) {
                return false;
            }
        }

        return true;
    }

    /**
     * 规范化搜索字段配置
     * @param array $field
     * @param string $defaultColClass
     * @return array|null
     */
    private function normalizeSearchField(array $field, string $defaultColClass): ?array
    {
        $name = trim((string)($field['name'] ?? ''));
        if ($name === '') {
            return null;
        }

        $type = (string)($field['type'] ?? 'text');
        if (!in_array($type, ['text', 'select', 'date'], true)) {
            $type = 'text';
        }

        $targetFields      = dp_normalize_search_fields($field['fields'] ?? []);
        $explicitOpInput   = array_key_exists('op', $field) ? trim((string)$field['op']) : '';
        $hasExplicitOp     = $explicitOpInput !== '';
        $resolvedOpDefault = '=';

        if (!$hasExplicitOp && $targetFields !== []) {
            $resolvedOpDefault = 'like';
        } elseif (!$hasExplicitOp && $type === 'date') {
            $resolvedOpDefault = 'between time';
        }

        $normalized = [
            'name'        => $name,
            'type'        => $type,
            'op'          => $this->normalizeSearchOperator($hasExplicitOp ? $explicitOpInput : $resolvedOpDefault),
            'label'       => (string)($field['label'] ?? ''),
            'placeholder' => (string)($field['placeholder'] ?? ''),
            'col_class'   => (string)($field['col_class'] ?? $defaultColClass),
            'value'       => $field['value'] ?? null,
            'attrs'       => is_array($field['attrs'] ?? null) ? $field['attrs'] : [],
            'fields'      => $targetFields,
        ];

        if ($type === 'select') {
            $options               = $field['options'] ?? [];
            $normalized['options'] = is_array($options) ? $options : [];
        }

        if ($type === 'date') {
            $normalized['range']  = (bool)($field['range'] ?? false);
            $normalized['format'] = (string)($field['format'] ?? 'yyyy-MM-dd');
        }

        return $normalized;
    }

    /**
     * 规范化搜索操作符
     * @param string $op
     * @return string
     */
    private function normalizeSearchOperator(string $op): string
    {
        $op = strtolower(trim(preg_replace('/\s+/', ' ', $op)));
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
     * 判断是否是搜索配置对象（旧签名兼容）
     * @param array $data
     * @return bool
     */
    private function isSearchConfigArray(array $data): bool
    {
        foreach (['fields', 'buttons', 'layout', 'class', 'enabled'] as $key) {
            if (array_key_exists($key, $data)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 应用搜索按钮配置
     * @param array $search
     * @param array $buttons
     * @return void
     */
    private function applySearchButtons(array &$search, array $buttons): void
    {
        if (!isset($search['buttons']) || !is_array($search['buttons'])) {
            $search['buttons'] = [];
        }

        foreach (['submit', 'reset'] as $buttonType) {
            if (!isset($search['buttons'][$buttonType]) || !is_array($search['buttons'][$buttonType])) {
                $search['buttons'][$buttonType] = [];
            }

            if (!array_key_exists($buttonType, $buttons)) {
                continue;
            }

            $button = $buttons[$buttonType];
            if (is_string($button)) {
                $button = ['text' => $button];
            }

            if (!is_array($button)) {
                continue;
            }

            if (isset($button['show'])) {
                $search['buttons'][$buttonType]['show'] = (bool)$button['show'];
            }
            if (!empty($button['text'])) {
                $search['buttons'][$buttonType]['text'] = (string)$button['text'];
            }
            if (!empty($button['class'])) {
                $search['buttons'][$buttonType]['class'] = (string)$button['class'];
            }
        }
    }

    /**
     * 过滤工具栏按钮权限
     * @param array $buttons 按钮配置数组
     * @return array 过滤后的按钮配置
     */
    private function filterToolbarByAuth(array $buttons): array
    {
        $result = [];

        foreach ($buttons as $key => $button) {
            // 跳过字符串类型的按钮（直接HTML）
            if (is_string($button)) {
                $result[$key] = $button;
                continue;
            }

            // 自动生成权限标识（如果未设置）
            if (!isset($button['auth']) && isset($button['event'])) {
                $button['auth'] = dp_generate_auth_code($button['event']);
            }

            $result[$key] = $button;
        }

        // 使用全局函数进行权限过滤
        return dp_filter_buttons_by_auth($result);
    }

    /**
     * 处理按钮属性
     * @param array $buttons
     * @return array
     */
    private function processButtonAttributes(array $buttons): array
    {
        $result = [];
        foreach ($buttons as $button) {
            if (is_string($button)) {
                $result[] = $button;
                continue;
            }

            // 处理弹出层
            if (isset($button['pop']) && $button['pop'] !== false) {
                $pop = [
                    'title' => $button['title'] ?? '操作',
                ];

                $button['pop'] = is_array($button['pop'])
                    ? array_merge($pop, $button['pop'])
                    : $pop;

                $button['pop'] = dp_parse_options($button['pop']);
            }

            // 处理ajax请求
            if (isset($button['ajax'])) {
                if ($button['ajax'] === false) {
                    unset($button['ajax']);
                } else {
                    $ajax = is_string($button['ajax']) ? ['type' => $button['ajax']] : $button['ajax'];
                    if ($this->crudToken !== '') {
                        $ajax['data']['_t'] = $this->crudToken;
                    }
                    $button['ajax'] = dp_parse_options($ajax);
                }
            }

            // 处理确认提示
            if (isset($button['confirm'])) {
                if ($button['confirm'] === false) {
                    unset($button['confirm']);
                } else {
                    $button['confirm'] = dp_parse_options(
                        is_string($button['confirm']) ? [$button['confirm'], ''] : $button['confirm']
                    );
                }
            }

            // 处理按钮链接
            if (isset($button['url'])) {
                $button['url'] = (string)$button['url'];
                if (!str_starts_with($button['url'], '/') &&
                    !str_starts_with($button['url'], 'http:') &&
                    !str_starts_with($button['url'], 'https:')) {
                    $button['url'] = (string)dp_url($button['url']);
                }

                // 拒绝 javascript: 协议，避免注入执行
                if (str_starts_with(strtolower($button['url']), 'javascript:')) {
                    $button['url'] = '';
                }

                if ($this->crudToken !== '') {
                    $button['url'] = $this->appendCrudTokenToUrl($button['url'], $this->crudToken);
                }
            }

            // 限制 onClick 为合法函数名，避免注入表达式
            if (isset($button['onClick'])) {
                $onClick = (string)$button['onClick'];
                if (!preg_match('/^[A-Za-z_$][A-Za-z0-9_$]*$/', $onClick)) {
                    unset($button['onClick']);
                }
            }

            // 构建HTML
            $attributes = [
                'class'        => 'layui-inline ' . ($button['class'] ?? ''),
                'title'        => $button['title'] ?? '',
                'data-confirm' => $button['confirm'] ?? '',
                'data-field'   => $button['field'] ?? '',
                'data-param'   => $button['param'] ?? '',
                'data-target'  => $button['target'] ?? '',
                'data-pop'     => $button['pop'] ?? '',
                'data-click'   => $button['onClick'] ?? '',
                'data-ajax'    => $button['ajax'] ?? '',
                'data-url'     => $button['url'] ?? '',
                'lay-event'    => $button['name'] ?? ''
            ];

            $html = '<div';
            foreach ($attributes as $attr => $value) {
                $html .= ' ' . $attr . '="' . $value . '"';
            }
            $html .= '><i class="' . ($button['icon'] ?? '') . '"></i></div>';

            $result[] = $html;
        }

        return $result;
    }

    /**
     * 数据调用
     * @param string|object $method
     * @return mixed
     * @throws Exception
     */
    private function dataCall(string|object $method): mixed
    {
        $params = [];
        if (is_object($method)) {
            $class    = get_class($method);
            $instance = $method;
            $method   = $this->dataMethod;
        } elseif (is_string($method) && str_contains($method, '\\')) {
            $class    = $method;
            $method   = $this->dataMethod;
            $instance = app($class);
        } else {
            // 获取当前请求的模块、控制器和方法
            $module           = app('http')->getName();
            $controller       = $this->request->controller();

            // 构建完整的控制器类名
            $class    = dp_resolve_controller_class($controller, $module);
            $instance = app($class);
        }

        if (!method_exists($instance, $method)) {
            throw new Exception(lang('dp#method data does not exist', ['class' => $class, 'method' => $method]));
        }

        return Closure::bind(
            fn(...$args) => $this->$method(...$args),
            $instance,
            $class
        )(...$params);
    }

    /**
     * 获取默认分页设置
     * @return array
     */
    private function getDefaultPage(): array
    {
        $config          = Config::get('table.page', []);
        $config['curr']  = $this->request->get('page', 1);
        $config['limit'] = $this->request->get('limit', 20);
        return $config;
    }

    /**
     * 获取默认 CRUD 表名（与 CrudActions::getTableName 保持一致）
     * @return string
     */
    private function getDefaultCrudTableName(): string
    {
        $app        = app('http')->getName();
        $controller = $this->request->controller();
        return dp_resolve_crud_table_name((string)$controller, (string)$app);
    }

    /**
     * 解析 quickEdit 验证字段
     * @param string|array $fields
     * @return array
     */
    private function parseQuickEditValidateFields(string|array $fields): array
    {
        if (is_string($fields)) {
            $fields = array_map('trim', explode(',', $fields));
        }

        if (!is_array($fields)) {
            return [];
        }

        $result = [];
        foreach ($fields as $field) {
            $field = trim((string)$field);
            if ($field !== '') {
                $result[] = $field;
            }
        }

        return array_values(array_unique($result));
    }

    /**
     * 生成 CRUD token
     * @return void
     */
    private function refreshCrudToken(): void
    {
        if ($this->crudTableName === '') {
            $this->crudToken = '';
            return;
        }

        $meta = [];
        if (!empty($this->quickEditValidate['enabled'])) {
            $meta['quick_edit_validate'] = $this->quickEditValidate;
        }

        $this->crudToken = dp_crud_token_encode($this->crudTableName, $this->crudTableType, 3600, $meta);

        // 同步已定义列中的 crud_token，避免先定义列后设置 validate 时 token 不一致
        if (is_array($this->vars['dp_table_fields'] ?? null)) {
            foreach ($this->vars['dp_table_fields'] as &$column) {
                if (is_array($column)) {
                    $column['crud_token'] = $this->crudToken;
                }
            }
            unset($column);
        }

        if (is_array($this->vars['dp_table_columns'][0] ?? null)) {
            foreach ($this->vars['dp_table_columns'][0] as &$column) {
                if (is_array($column)) {
                    $column['crud_token'] = $this->crudToken;
                }
            }
            unset($column);
        }
    }

    /**
     * 向 URL 追加 CRUD token
     * @param string $url
     * @param string $token
     * @return string
     */
    private function appendCrudTokenToUrl(string $url, string $token): string
    {
        if ($url === '' || str_contains($url, '_t=')) {
            return $url;
        }

        if (preg_match('#^https?://#i', $url)) {
            $urlHost  = parse_url($url, PHP_URL_HOST);
            $currHost = $this->request->host();
            if ($urlHost && strcasecmp($urlHost, $currHost) !== 0) {
                return $url;
            }
        }

        return $url . (str_contains($url, '?') ? '&' : '?') . '_t=' . urlencode($token);
    }

    /**
     * 递归为树形数据添加 _data 字段
     * @param array &$tree 树形数据引用
     * @param mixed $originalData 原始数据（可能是数组、Paginator对象等）
     * @return void
     */
    private function addDataToTree(array &$tree, mixed $originalData): void
    {
        foreach ($tree as $key => &$item) {
            // 添加原始数据引用（排除 children，避免数据冗余）
            $itemData = $originalData[$key] ?? $item;
            if (is_array($itemData)) {
                unset($itemData['children']);
            }
            $item['_data'] = $itemData;

            // 如果有子节点，递归处理
            if (!empty($item['children']) && is_array($item['children'])) {
                $childrenOriginal = $originalData[$key]['children'] ?? $item['children'];
                $this->addDataToTree($item['children'], $childrenOriginal);
            }
        }
    }
}
