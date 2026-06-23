<?php
declare(strict_types=1);

namespace app\showcase\service\components\table\table_builder;

/**
 * Showcase 表格构建器方法板块组装器
 */
final class TableBuilderMethodSectionBuilder
{
    /**
     * @return array<string, mixed>
     */
    public function build(string $methodKey): array
    {
        return match ($methodKey) {
            'id' => $this->id(),
            'alert' => $this->alert(),
            'extra_html' => $this->extraHtml(),
            'data' => $this->data(),
            'url' => $this->url(),
            'method' => $this->method(),
            'tableName' => $this->tableName(),
            'primaryKey' => $this->primaryKey(),
            'validate' => $this->validate(),
            'column' => $this->column(),
            'columns' => $this->columns(),
            'search' => $this->search(),
            'toolbar' => $this->toolbar(),
            'actions' => $this->actions(),
            'checkbox' => $this->checkbox(),
            'page' => $this->page(),
            'options' => $this->options(),
            'tree' => $this->tree(),
            'assign' => $this->assign(),
            default => [],
        };
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function wrap(array $payload): array
    {
        return array_merge([
            'key' => '',
            'group_key' => '',
            'title' => '',
            'signature' => '',
            'summary' => '',
            'parameter_details' => [],
            'variants' => [],
            'array_code' => '',
            'table_code' => '',
            'usage_variants' => [],
            'behavior_notes' => [],
            'tips' => [],
            'example_mode' => 'source',
            'preview_html' => '',
            'source_refs' => [
                [
                    'path' => 'app/common/render/Table.php',
                    'label' => 'Table 构建器源码',
                    'description' => '方法签名与行为均来自该类。',
                ],
            ],
        ], $payload);
    }

    /**
     * @param string $method
     * @param string $summary
     * @param array<int, array{name:string, summary:string}> $parameters
     * @param string $arrayCode
     * @param string $tableCode
     * @param array<int, string> $notes
     * @param array<int, string> $tips
     * @param string $groupKey
     * @param string $title
     * @param string $exampleMode
     * @param string $previewHtml
     * @param array<int, array<string, mixed>> $usageVariants
     * @return array<string, mixed>
     */
    private function section(
        string $method,
        string $summary,
        array $parameters,
        string $arrayCode,
        string $tableCode,
        array $notes,
        array $tips,
        string $groupKey,
        string $title,
        string $exampleMode = 'source',
        string $previewHtml = '',
        array $usageVariants = []
    ): array {
        return $this->wrap([
            'key' => $method,
            'group_key' => $groupKey,
            'title' => $title,
            'signature' => $this->signature($method),
            'summary' => $summary,
            'parameter_details' => $parameters,
            'array_code' => $arrayCode,
            'table_code' => $tableCode,
            'behavior_notes' => $notes,
            'tips' => $tips,
            'example_mode' => $exampleMode,
            'preview_html' => $previewHtml,
            'usage_variants' => $usageVariants,
        ]);
    }

    private function signature(string $method): string
    {
        return match ($method) {
            'id' => "id(string \$id = ''): static",
            'alert' => "alert(string|array \$content = '', string \$title = '', string \$type = 'info', string \$pos = 'top'): static",
            'extra_html' => "extraHtml(string \$content, string \$pos = 'bottom'): static",
            'data' => "data(\$data = null, string \$method = ''): static",
            'url' => "url(string|Url \$url = ''): static",
            'method' => "method(string \$method = 'get'): static",
            'tableName' => "tableName(string \$tableName, string \$type = 'name'): static",
            'primaryKey' => "primaryKey(string \$pk = ''): static",
            'validate' => "validate(bool|string \$validate = true, string|array \$fields = []): static",
            'column' => "column(string|array \$field = '', string \$title = '', mixed \$type = 'normal', mixed \$options = [], array \$cols = []): static",
            'columns' => "columns(array \$columns = []): static",
            'search' => "search(mixed \$fields = [], array \$buttons = [], array \$options = []): static",
            'toolbar' => "toolbar(string|bool|array \$options = false, array \$defaultToolbar = ['filter', 'exports', 'print'], bool \$replace = false): static",
            'actions' => "actions(mixed \$options, string \$title = '操作'): static",
            'checkbox' => "checkbox(bool|string \$status = true): static",
            'page' => "page(array \$options = []): static",
            'options' => "options(array \$options = []): static",
            'tree' => "tree(bool \$enable = false): static",
            'assign' => "assign(string|array \$name, mixed \$value = null): static",
            default => '',
        };
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function dualCodeVariant(string $title, string $summary, string $arrayCode, string $tableCode): array
    {
        return [
            'title' => $title,
            'summary' => $summary,
            'array_code' => $arrayCode,
            'table_code' => $tableCode,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function id(): array
    {
        return $this->section(
            'id',
            '显式指定表格根节点 id，便于脚本挂载、联动多个表格实例或稳定定位 Ajax 返回的目标表格。',
            [
                ['name' => '$id', 'summary' => '表格实例 id，必须在页面内保持唯一。'],
            ],
            <<<'CODE'
[
    'id' => 'order_table',
]
CODE,
            <<<'CODE'
use app\common\render\Table;

Table::make('order_table')
    ->id('order_table');
CODE,
            ['DOM id 是表格 Ajax 请求与前端实例绑定的关键标识，命名应稳定且唯一。'],
            ['不要把随机字符串当作长期表格 id，后续脚本和测试会更难维护。'],
            'identity_notice',
            'id() 表格 DOM 标识'
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function alert(): array
    {
        return $this->section(
            'alert',
            '在表格顶部或底部输出说明、操作提示、风险提醒，适合告诉开发者当前表格的数据约束与交互边界。',
            [
                ['name' => '$content', 'summary' => '提示内容，支持字符串或字符串数组。'],
                ['name' => '$title', 'summary' => '提示标题。'],
                ['name' => '$type', 'summary' => 'success、info、danger、warning 及其扩展修饰。'],
                ['name' => '$pos', 'summary' => 'top 或 bottom。'],
            ],
            <<<'CODE'
[
    'alert' => [
        'content' => ['排序列支持拖拽', '状态列支持 quickEdit'],
        'title' => '演示说明',
        'type' => 'info:icon,close',
        'pos' => 'top',
    ],
]
CODE,
            <<<'CODE'
use app\common\render\Table;

Table::make('demo_table')
    ->alert(['排序列支持拖拽', '状态列支持 quickEdit'], '演示说明', 'info:icon,close', 'top');
CODE,
            ['alert() 适合补充“这张表怎么操作”，不要拿它替代正式的业务校验反馈。'],
            ['提示内容较多时优先使用数组写法，源码更直观。'],
            'identity_notice',
            'alert() 表格提示条',
            'preview',
            '<div class="alert alert-info"><strong>演示说明</strong><br>排序列支持拖拽<br>状态列支持 quickEdit</div>',
            [
                $this->dualCodeVariant(
                    '字符串内容',
                    '适合一行提醒。',
                    "[\n    'alert' => '仅展示最近 30 天数据',\n]",
                    "use app\\common\\render\\Table;\n\nTable::make('demo_table')\n    ->alert('仅展示最近 30 天数据');"
                ),
                $this->dualCodeVariant(
                    '数组内容 + 图标关闭',
                    '适合多行说明和可关闭提示。',
                    "[\n    'alert' => [\n        'content' => ['仅展示最近 30 天数据', '导出前请先筛选'],\n        'title' => '注意',\n        'type' => 'warning:icon,close',\n    ],\n]",
                    "use app\\common\\render\\Table;\n\nTable::make('demo_table')\n    ->alert(['仅展示最近 30 天数据', '导出前请先筛选'], '注意', 'warning:icon,close');"
                ),
            ]
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function extraHtml(): array
    {
        return $this->section(
            'extra_html',
            '在表格顶部或底部插入自定义 HTML，适合放摘要指标、批量操作提示或辅助说明块。',
            [
                ['name' => '$content', 'summary' => '附加 HTML 内容。'],
                ['name' => '$pos', 'summary' => 'top 或 bottom。'],
            ],
            <<<'CODE'
[
    'extra_html' => '<div class="dp-summary">当前共 128 条数据</div>',
    'pos' => 'top',
]
CODE,
            <<<'CODE'
use app\common\render\Table;

Table::make('demo_table')
    ->extraHtml('<div class="dp-summary">当前共 128 条数据</div>', 'top');
CODE,
            ['extraHtml() 更适合插入结构性说明或指标摘要，不建议塞复杂交互逻辑。'],
            ['如果内容会复用，优先抽成模板片段或视图片段，而不是长期内联字符串。'],
            'identity_notice',
            'extraHtml() 附加 HTML',
            'source',
            '',
            [
                $this->dualCodeVariant(
                    '顶部说明块',
                    '放在表格头部，适合摘要或说明。',
                    "[\n    'extra_html' => '<div class=\"dp-summary\">当前共 128 条数据</div>',\n    'pos' => 'top',\n]",
                    "use app\\common\\render\\Table;\n\nTable::make('demo_table')\n    ->extraHtml('<div class=\"dp-summary\">当前共 128 条数据</div>', 'top');"
                ),
                $this->dualCodeVariant(
                    '底部扩展块',
                    '放在表格底部，适合注意事项。',
                    "[\n    'extra_html' => '<div class=\"dp-footer-note\">导出结果以当前筛选条件为准</div>',\n    'pos' => 'bottom',\n]",
                    "use app\\common\\render\\Table;\n\nTable::make('demo_table')\n    ->extraHtml('<div class=\"dp-footer-note\">导出结果以当前筛选条件为准</div>', 'bottom');"
                ),
            ]
        );
    }

    private function data(): array
    {
        return $this->section(
            'data',
            '配置表格真实数据来源。既可以直接给静态数组，也可以给 URL、Url 对象或闭包/模型查询结果。',
            [
                ['name' => '$data', 'summary' => '静态数组、URL、Url 对象或其他可被表格处理的数据源。'],
                ['name' => '$method', 'summary' => '请求方式，仅在 URL 数据源场景下有意义。'],
            ],
            <<<'CODE'
[
    'data' => [
        ['id' => 1, 'username' => 'admin', 'status' => 1],
        ['id' => 2, 'username' => 'editor', 'status' => 0],
    ],
]
CODE,
            <<<'CODE'
use app\common\render\Table;

Table::make('user_table')
    ->data([
        ['id' => 1, 'username' => 'admin', 'status' => 1],
        ['id' => 2, 'username' => 'editor', 'status' => 0],
    ]);
CODE,
            ['data() 决定表格是静态演示、真实接口拉取还是 CRUD 表读取的起点。'],
            ['如果是示例页，优先给少量真实结构数据，而不是纯静态占位字符串。'],
            'data_crud',
            'data() 数据源',
            'source',
            '',
            [
                $this->dualCodeVariant(
                    '静态数组数据',
                    '适合 showcase、只读预览或临时结果表。',
                    "[\n    'data' => [\n        ['id' => 1, 'username' => 'admin'],\n        ['id' => 2, 'username' => 'editor'],\n    ],\n]",
                    "use app\\common\\render\\Table;\n\nTable::make('user_table')\n    ->data([\n        ['id' => 1, 'username' => 'admin'],\n        ['id' => 2, 'username' => 'editor'],\n    ]);"
                ),
                $this->dualCodeVariant(
                    '字符串 URL',
                    '直接把数据请求交给接口地址。',
                    "[\n    'data' => '/admin/user/data',\n    'method' => 'post',\n]",
                    "use app\\common\\render\\Table;\n\nTable::make('user_table')\n    ->data('/admin/user/data', 'post');"
                ),
                $this->dualCodeVariant(
                    'Url 对象',
                    '适合需要借助框架 URL 生成器的场景。',
                    "[\n    'data' => url('admin/user/data', ['scene' => 'archive']),\n]",
                    "use app\\common\\render\\Table;\n\nTable::make('user_table')\n    ->data(url('admin/user/data', ['scene' => 'archive']));"
                ),
            ]
        );
    }

    private function url(): array
    {
        return $this->section(
            'url',
            '单独指定数据请求 URL。适合列和搜索已先定义好，稍后再补充接口地址的写法。',
            [
                ['name' => '$url', 'summary' => '字符串 URL 或 Url 对象。'],
            ],
            <<<'CODE'
[
    'url' => '/admin/user/data',
]
CODE,
            <<<'CODE'
use app\common\render\Table;

Table::make('user_table')
    ->url('/admin/user/data');
CODE,
            ['当数据源和请求参数逻辑分开维护时，url() 会比 data() 更清晰。'],
            ['不要同时在 data() 和 url() 上维护两套接口地址，避免后期阅读混乱。'],
            'data_crud',
            'url() 数据请求地址',
            'source',
            '',
            [
                $this->dualCodeVariant(
                    '字符串 URL',
                    '最常见写法。',
                    "[\n    'url' => '/admin/user/data',\n]",
                    "use app\\common\\render\\Table;\n\nTable::make('user_table')\n    ->url('/admin/user/data');"
                ),
                $this->dualCodeVariant(
                    'Url 对象',
                    '参数由框架统一拼装。',
                    "[\n    'url' => url('admin/user/data', ['scene' => 'recycle']),\n]",
                    "use app\\common\\render\\Table;\n\nTable::make('user_table')\n    ->url(url('admin/user/data', ['scene' => 'recycle']));"
                ),
            ]
        );
    }

    private function method(): array
    {
        return $this->section(
            'method',
            '指定表格数据请求方式，默认是 get，也可以改为 post 以承载更复杂的筛选数据。',
            [
                ['name' => '$method', 'summary' => '请求方式，如 get、post。'],
            ],
            <<<'CODE'
[
    'method' => 'post',
]
CODE,
            <<<'CODE'
use app\common\render\Table;

Table::make('user_table')
    ->method('post');
CODE,
            ['method() 只影响表格数据请求本身，不改变 quickEdit 的提交协议。'],
            ['列表筛选参数较多时，用 post 往往更稳妥。'],
            'data_crud',
            'method() 请求方式',
            'source',
            '',
            [
                $this->dualCodeVariant(
                    'GET 请求',
                    '适合轻量筛选、参数较少的列表。',
                    "[\n    'method' => 'get',\n]",
                    "use app\\common\\render\\Table;\n\nTable::make('user_table')\n    ->method('get');"
                ),
                $this->dualCodeVariant(
                    'POST 请求',
                    '适合筛选条件较多或参数结构较复杂的场景。',
                    "[\n    'method' => 'post',\n]",
                    "use app\\common\\render\\Table;\n\nTable::make('user_table')\n    ->method('post');"
                ),
            ]
        );
    }

    private function tableName(): array
    {
        return $this->section(
            'tableName',
            '为 quickEdit、开关列、排序等 CRUD 能力指定目标数据表，并自动生成 CRUD token。',
            [
                ['name' => '$tableName', 'summary' => '表名，可传不带前缀或完整表名。'],
                ['name' => '$type', 'summary' => 'name 表示不带前缀，table 表示完整表名。'],
            ],
            <<<'CODE'
[
    'table_name' => 'showcase_table',
    'type' => 'name',
]
CODE,
            <<<'CODE'
use app\common\render\Table;

Table::make('demo_table')
    ->tableName('showcase_table');
CODE,
            ['tableName() 是 CRUD 能力的关键前提，没有它时很多快捷编辑能力只会停留在展示层。', '它会生成 CRUD token，供 quickEdit、开关列、排序等交互定位目标表。'],
            ['只在真实允许修改的数据表上开启该能力，避免把纯展示表误接到生产数据。'],
            'data_crud',
            'tableName() CRUD 目标表',
            'behavior',
            '',
            [
                $this->dualCodeVariant(
                    '不带前缀表名',
                    '最常用写法，框架自行处理前缀。',
                    "[\n    'table_name' => 'showcase_table',\n    'type' => 'name',\n]",
                    "use app\\common\\render\\Table;\n\nTable::make('demo_table')\n    ->tableName('showcase_table');"
                ),
                $this->dualCodeVariant(
                    '完整表名',
                    '适合你已经拿到完整表名字符串的场景。',
                    "[\n    'table_name' => 'dp_showcase_table',\n    'type' => 'table',\n]",
                    "use app\\common\\render\\Table;\n\nTable::make('demo_table')\n    ->tableName('dp_showcase_table', 'table');"
                ),
            ]
        );
    }

    private function primaryKey(): array
    {
        return $this->section(
            'primaryKey',
            '显式指定当前表格所使用的主键字段，便于 quickEdit、行操作和树表模式稳定定位数据行。',
            [
                ['name' => '$pk', 'summary' => '主键字段名。'],
            ],
            <<<'CODE'
[
    'primary_key' => 'uuid',
]
CODE,
            <<<'CODE'
use app\common\render\Table;

Table::make('asset_table')
    ->primaryKey('uuid');
CODE,
            ['当业务表不使用默认 id 作为主键时，应该同步声明 primaryKey()。'],
            ['如果列操作依赖行主键，主键字段名一定要和真实数据结构保持一致。'],
            'data_crud',
            'primaryKey() 主键字段',
            'source',
            '',
            [
                $this->dualCodeVariant(
                    '默认主键字段',
                    '显式声明常见的 id 字段，方便团队统一认知。',
                    "[\n    'primary_key' => 'id',\n]",
                    "use app\\common\\render\\Table;\n\nTable::make('asset_table')\n    ->primaryKey('id');"
                ),
                $this->dualCodeVariant(
                    '业务主键字段',
                    '适合 uuid、code、snowflake_id 等自定义主键。',
                    "[\n    'primary_key' => 'asset_uuid',\n]",
                    "use app\\common\\render\\Table;\n\nTable::make('asset_table')\n    ->primaryKey('asset_uuid');"
                ),
            ]
        );
    }

    private function validate(): array
    {
        return $this->section(
            'validate',
            '为 quickEdit 提交增加验证器约束，控制哪些字段允许被校验、如何校验。',
            [
                ['name' => '$validate', 'summary' => 'true、false 或验证器名称。'],
                ['name' => '$fields', 'summary' => '需要校验的字段列表，支持字符串或数组。'],
            ],
            <<<'CODE'
[
    'validate' => 'ShowcaseTable',
    'fields' => ['status', 'sort'],
]
CODE,
            <<<'CODE'
use app\common\render\Table;

Table::make('demo_table')
    ->validate('ShowcaseTable', ['status', 'sort']);
CODE,
            ['validate() 只作用于 quickEdit 这类快捷修改链路，不影响普通列表加载。'],
            ['校验器名不要带场景后缀，当前方法不支持传入 `Validator.scene` 写法。'],
            'data_crud',
            'validate() quickEdit 校验',
            'source',
            '',
            [
                $this->dualCodeVariant(
                    '指定验证器与字段',
                    '推荐在可写表中显式声明。',
                    "[\n    'validate' => 'ShowcaseTable',\n    'fields' => 'status,sort',\n]",
                    "use app\\common\\render\\Table;\n\nTable::make('demo_table')\n    ->validate('ShowcaseTable', 'status,sort');"
                ),
                $this->dualCodeVariant(
                    '关闭校验',
                    '仅在你确认上游已兜底时使用。',
                    "[\n    'validate' => false,\n]",
                    "use app\\common\\render\\Table;\n\nTable::make('demo_table')\n    ->validate(false);"
                ),
            ]
        );
    }

    private function column(): array
    {
        return $this->section(
            'column',
            '逐列追加定义，适合需要在条件分支里动态拼接列或少量增量追加列的场景。',
            [
                ['name' => '$field', 'summary' => '字段名或整列数组定义。'],
                ['name' => '$title', 'summary' => '列表头标题。'],
                ['name' => '$type', 'summary' => '列类型，如 normal、status、preview、date.edit。'],
                ['name' => '$options', 'summary' => '列类型参数。'],
                ['name' => '$cols', 'summary' => '额外列属性。'],
            ],
            <<<'CODE'
[
    'field' => 'status',
    'title' => '状态',
    'type' => 'status',
    'options' => ['禁用', '启用:green'],
]
CODE,
            <<<'CODE'
use app\common\render\Table;

Table::make('demo_table')
    ->column('status', '状态', 'status', ['禁用', '启用:green']);
CODE,
            ['column() 适合动态拼装；如果整表列定义一次性很完整，通常 columns() 更直观。'],
            ['列类型和 options 需要对应，避免把只读映射配置误写到可编辑列上。'],
            'columns_search',
            'column() 单列定义',
            'preview',
            <<<'HTML'
<div class="showcase-table-builder-column-preview">
    <div class="showcase-table-builder-column-preview__header">
        <span>字段</span>
        <span>标题</span>
        <span>类型</span>
        <span>预览结果</span>
    </div>
    <div class="showcase-table-builder-column-preview__row">
        <span><code>status</code></span>
        <span>状态</span>
        <span><code>status</code></span>
        <span class="showcase-table-builder-column-preview__tag">启用</span>
    </div>
</div>
HTML,
            [
                $this->dualCodeVariant(
                    '基础状态列',
                    '按字段、标题、类型、选项四段式定义。',
                    "[\n    'field' => 'status',\n    'title' => '状态',\n    'type' => 'status',\n    'options' => ['禁用', '启用:green'],\n]",
                    "use app\\common\\render\\Table;\n\nTable::make('demo_table')\n    ->column('status', '状态', 'status', ['禁用', '启用:green']);"
                ),
                $this->dualCodeVariant(
                    '数组整列写法',
                    '适合把列配置整体先组装成数组再传入。',
                    "[\n    'column' => [\n        'field' => 'cover',\n        'title' => '封面',\n        'type' => 'image',\n        'cols' => ['width' => 88],\n    ],\n]",
                    "use app\\common\\render\\Table;\n\nTable::make('demo_table')\n    ->column([\n        'field' => 'cover',\n        'title' => '封面',\n        'type' => 'image',\n        'cols' => ['width' => 88],\n    ]);"
                ),
                $this->dualCodeVariant(
                    '附加列属性',
                    '适合补充 width、fixed、sort 等列属性。',
                    "[\n    'field' => 'sort',\n    'title' => '排序',\n    'type' => 'text.edit',\n    'cols' => ['width' => 100, 'sort' => true, 'fixed' => 'left'],\n]",
                    "use app\\common\\render\\Table;\n\nTable::make('demo_table')\n    ->column('sort', '排序', 'text.edit', [], ['width' => 100, 'sort' => true, 'fixed' => 'left']);"
                ),
            ]
        );
    }

    private function columns(): array
    {
        return $this->section(
            'columns',
            '批量定义整张表的列，是最常见、最适合写示例和业务列表的方式。',
            [
                ['name' => '$columns', 'summary' => '列定义二维数组。'],
            ],
            <<<'CODE'
[
    'columns' => [
        ['id', 'ID'],
        ['username', '用户名'],
        ['status', '状态', 'status', ['禁用', '启用:green']],
    ],
]
CODE,
            <<<'CODE'
use app\common\render\Table;

Table::make('demo_table')
    ->columns([
        ['id', 'ID'],
        ['username', '用户名'],
        ['status', '状态', 'status', ['禁用', '启用:green']],
    ]);
CODE,
            ['columns() 适合大多数列表页，列结构在一个数组里一眼就能看清。'],
            ['如果后面还要按条件追加列，可以先 columns() 再少量 column() 补充。'],
            'columns_search',
            'columns() 批量列定义',
            'preview',
            <<<'HTML'
<div class="showcase-table-builder-columns-preview">
    <div class="showcase-table-builder-columns-preview__table">
        <div class="showcase-table-builder-columns-preview__head">
            <span>ID</span>
            <span>用户名</span>
            <span>状态</span>
        </div>
        <div class="showcase-table-builder-columns-preview__body">
            <span>1</span>
            <span>admin</span>
            <span class="showcase-table-builder-columns-preview__status">启用</span>
        </div>
        <div class="showcase-table-builder-columns-preview__body">
            <span>2</span>
            <span>editor</span>
            <span class="showcase-table-builder-columns-preview__status showcase-table-builder-columns-preview__status--muted">禁用</span>
        </div>
    </div>
</div>
HTML,
            [
                $this->dualCodeVariant(
                    '基础列数组',
                    '最适合常规列表页一次性声明全部列。',
                    "[\n    'columns' => [\n        ['id', 'ID'],\n        ['username', '用户名'],\n        ['status', '状态', 'status', ['禁用', '启用:green']],\n    ],\n]",
                    "use app\\common\\render\\Table;\n\nTable::make('demo_table')\n    ->columns([\n        ['id', 'ID'],\n        ['username', '用户名'],\n        ['status', '状态', 'status', ['禁用', '启用:green']],\n    ]);"
                ),
                $this->dualCodeVariant(
                    '混合多种列类型',
                    '同一个 columns() 中组合图片列、时间列、状态列等不同能力。',
                    "[\n    'columns' => [\n        ['cover', '封面', 'image', [], ['width' => 88]],\n        ['publish_time', '发布时间', 'datetime'],\n        ['status', '状态', 'switch', [0, 1]],\n    ],\n]",
                    "use app\\common\\render\\Table;\n\nTable::make('demo_table')\n    ->columns([\n        ['cover', '封面', 'image', [], ['width' => 88]],\n        ['publish_time', '发布时间', 'datetime'],\n        ['status', '状态', 'switch', [0, 1]],\n    ]);"
                ),
            ]
        );
    }

    private function search(): array
    {
        return $this->section(
            'search',
            '配置搜索区字段、按钮和布局。它既支持当前 DSL，也兼容旧签名数组写法。',
            [
                ['name' => '$fields', 'summary' => '字段列表、字段映射、旧签名配置或 false。'],
                ['name' => '$buttons', 'summary' => '按钮配置。'],
                ['name' => '$options', 'summary' => '布局与按钮等附加配置。'],
            ],
            <<<'CODE'
[
    'search' => [
        ['name' => 'keyword', 'placeholder' => '输入关键词'],
        ['name' => 'status', 'type' => 'select', 'options' => ['全部', '禁用', '启用']],
    ],
]
CODE,
            <<<'CODE'
use app\common\render\Table;

Table::make('demo_table')
    ->search([
        ['name' => 'keyword', 'placeholder' => '输入关键词'],
        ['name' => 'status', 'type' => 'select', 'options' => ['全部', '禁用', '启用']],
    ]);
CODE,
            ['search() 不只是“有没有搜索框”，还决定字段布局、按钮区和默认交互。'],
            ['新项目建议优先使用新 DSL；旧签名兼容主要用于迁移和老代码对照。'],
            'columns_search',
            'search() 搜索区 DSL',
            'preview',
            <<<'HTML'
<div class="showcase-table-builder-search-preview">
    <div class="showcase-table-builder-search-preview__row">
        <div class="showcase-table-builder-search-preview__field">
            <span class="showcase-table-builder-search-preview__label">关键词</span>
            <span class="showcase-table-builder-search-preview__control">输入关键词</span>
        </div>
        <div class="showcase-table-builder-search-preview__field">
            <span class="showcase-table-builder-search-preview__label">状态</span>
            <span class="showcase-table-builder-search-preview__control showcase-table-builder-search-preview__control--select">全部 / 禁用 / 启用</span>
        </div>
        <div class="showcase-table-builder-search-preview__actions">
            <span class="showcase-table-builder-search-preview__button showcase-table-builder-search-preview__button--primary">筛选</span>
            <span class="showcase-table-builder-search-preview__button">重置</span>
        </div>
    </div>
</div>
HTML,
            [
                $this->dualCodeVariant(
                    '字段数组写法',
                    '最适合新项目和完整配置。',
                    "[\n    'search' => [\n        ['name' => 'keyword', 'placeholder' => '输入关键词'],\n        ['name' => 'status', 'type' => 'select', 'options' => ['全部', '禁用', '启用']],\n    ],\n]",
                    "use app\\common\\render\\Table;\n\nTable::make('demo_table')\n    ->search([\n        ['name' => 'keyword', 'placeholder' => '输入关键词'],\n        ['name' => 'status', 'type' => 'select', 'options' => ['全部', '禁用', '启用']],\n    ]);"
                ),
                $this->dualCodeVariant(
                    '字段映射简写',
                    '适合轻量关键字搜索。',
                    "[\n    'search' => [\n        'username' => '输入用户名',\n        'mobile' => '输入手机号',\n    ],\n]",
                    "use app\\common\\render\\Table;\n\nTable::make('demo_table')\n    ->search([\n        'username' => '输入用户名',\n        'mobile' => '输入手机号',\n    ]);"
                ),
                $this->dualCodeVariant(
                    '旧签名兼容写法',
                    '适合迁移老代码时逐步收口。',
                    "[\n    'search' => [\n        'fields' => ['keyword', 'status'],\n        'buttons' => ['submit' => ['text' => '筛选']],\n        'layout' => ['field_col_class' => 'layui-col-md3'],\n    ],\n]",
                    "use app\\common\\render\\Table;\n\nTable::make('demo_table')\n    ->search([\n        'fields' => ['keyword', 'status'],\n        'buttons' => ['submit' => ['text' => '筛选']],\n        'layout' => ['field_col_class' => 'layui-col-md3'],\n    ]);"
                ),
            ]
        );
    }

    private function toolbar(): array
    {
        return $this->section(
            'toolbar',
            '配置表格顶部工具栏，支持默认工具栏、自定义 HTML 和数组按钮写法。',
            [
                ['name' => '$options', 'summary' => 'false、default、字符串 HTML、数组按钮或模板选择器。'],
                ['name' => '$defaultToolbar', 'summary' => '右侧默认工具集合。'],
                ['name' => '$replace', 'summary' => '数组按钮时是否替换默认左侧按钮。'],
            ],
            <<<'CODE'
[
    'toolbar' => ['add', 'delete', 'reload'],
]
CODE,
            <<<'CODE'
use app\common\render\Table;

Table::make('demo_table')
    ->toolbar(['add', 'delete', 'reload']);
CODE,
            ['toolbar() 同时影响左侧业务按钮和右侧默认工具按钮。'],
            ['需要完全接管左侧按钮时，再考虑把 $replace 设为 true。'],
            'toolbar_interaction',
            'toolbar() 工具栏',
            'preview',
            <<<'HTML'
<div class="showcase-table-builder-toolbar-preview">
    <div class="showcase-table-builder-toolbar-preview__left">
        <span class="showcase-table-builder-toolbar-preview__button showcase-table-builder-toolbar-preview__button--primary">新增</span>
        <span class="showcase-table-builder-toolbar-preview__button">删除</span>
        <span class="showcase-table-builder-toolbar-preview__button">刷新</span>
    </div>
    <div class="showcase-table-builder-toolbar-preview__right">
        <span class="showcase-table-builder-toolbar-preview__tool">筛选</span>
        <span class="showcase-table-builder-toolbar-preview__tool">导出</span>
        <span class="showcase-table-builder-toolbar-preview__tool">打印</span>
    </div>
</div>
HTML,
            [
                $this->dualCodeVariant(
                    '默认工具栏',
                    '使用框架内置左侧按钮集合。',
                    "[\n    'toolbar' => 'default',\n]",
                    "use app\\common\\render\\Table;\n\nTable::make('demo_table')\n    ->toolbar('default');"
                ),
                $this->dualCodeVariant(
                    '自定义 HTML',
                    '适合一次性按钮片段或说明块。',
                    "[\n    'toolbar' => '<div><button class=\"layui-btn\">同步</button></div>',\n]",
                    "use app\\common\\render\\Table;\n\nTable::make('demo_table')\n    ->toolbar('<div><button class=\"layui-btn\">同步</button></div>');"
                ),
                $this->dualCodeVariant(
                    '数组按钮写法',
                    '最适合常规业务列表。',
                    "[\n    'toolbar' => ['add', 'delete', 'reload'],\n]",
                    "use app\\common\\render\\Table;\n\nTable::make('demo_table')\n    ->toolbar(['add', 'delete', 'reload']);"
                ),
            ]
        );
    }

    private function actions(): array
    {
        return $this->section(
            'actions',
            '快速追加右侧操作列，本质上是一个内置 actions 类型列的快捷封装。',
            [
                ['name' => '$options', 'summary' => '操作按钮配置。'],
                ['name' => '$title', 'summary' => '操作列标题。'],
            ],
            <<<'CODE'
[
    'actions' => [
        'edit' => ['title' => '编辑'],
        'delete' => ['title' => '删除', 'confirm' => '确认删除？'],
    ],
]
CODE,
            <<<'CODE'
use app\common\render\Table;

Table::make('demo_table')
    ->actions([
        'edit' => ['title' => '编辑'],
        'delete' => ['title' => '删除', 'confirm' => '确认删除？'],
    ]);
CODE,
            ['actions() 适合快速补齐“编辑 / 删除 / 详情”这类常规行操作。'],
            ['如果操作列还要混合复杂 callback 或自定义模板，可以回到普通 column() 写法。'],
            'toolbar_interaction',
            'actions() 右侧操作列',
            'preview',
            <<<'HTML'
<div class="showcase-table-builder-actions-preview">
    <div class="showcase-table-builder-actions-preview__title">操作列</div>
    <div class="showcase-table-builder-actions-preview__buttons">
        <span class="showcase-table-builder-actions-preview__button">编辑</span>
        <span class="showcase-table-builder-actions-preview__button showcase-table-builder-actions-preview__button--accent">详情</span>
        <span class="showcase-table-builder-actions-preview__button showcase-table-builder-actions-preview__button--danger">删除</span>
    </div>
</div>
HTML,
            [
                $this->dualCodeVariant(
                    '键值按钮映射',
                    '适合最常见的编辑、删除、详情等操作。',
                    "[\n    'actions' => [\n        'edit' => ['title' => '编辑'],\n        'delete' => ['title' => '删除', 'confirm' => '确认删除？'],\n    ],\n]",
                    "use app\\common\\render\\Table;\n\nTable::make('demo_table')\n    ->actions([\n        'edit' => ['title' => '编辑'],\n        'delete' => ['title' => '删除', 'confirm' => '确认删除？'],\n    ]);"
                ),
                $this->dualCodeVariant(
                    '自定义列标题',
                    '把默认“操作”改成更贴近业务的标题。',
                    "[\n    'actions' => ['preview' => ['title' => '预览']],\n    'title' => '管理',\n]",
                    "use app\\common\\render\\Table;\n\nTable::make('demo_table')\n    ->actions([\n        'preview' => ['title' => '预览'],\n    ], '管理');"
                ),
                $this->dualCodeVariant(
                    '数组按钮列表',
                    '适合需要保留顺序或传递更完整参数的场景。',
                    "[\n    'actions' => [\n        ['name' => 'edit', 'title' => '编辑'],\n        ['name' => 'detail', 'title' => '详情', 'class' => 'layui-btn-normal'],\n    ],\n]",
                    "use app\\common\\render\\Table;\n\nTable::make('demo_table')\n    ->actions([\n        ['name' => 'edit', 'title' => '编辑'],\n        ['name' => 'detail', 'title' => '详情', 'class' => 'layui-btn-normal'],\n    ]);"
                ),
            ]
        );
    }

    private function checkbox(): array
    {
        return $this->section(
            'checkbox',
            '控制是否显示多选列，也可以指定固定列方向。',
            [
                ['name' => '$status', 'summary' => 'true、false 或 fixed 方向字符串。'],
            ],
            <<<'CODE'
[
    'checkbox' => 'left',
]
CODE,
            <<<'CODE'
use app\common\render\Table;

Table::make('demo_table')
    ->checkbox('left');
CODE,
            ['checkbox() 是批量删除、批量审核等能力的前提。'],
            ['批量操作很重时建议把多选列固定到左侧，滚动时体验更稳定。'],
            'toolbar_interaction',
            'checkbox() 多选列',
            'preview',
            <<<'HTML'
<div class="showcase-table-builder-checkbox-preview">
    <div class="showcase-table-builder-checkbox-preview__row">
        <span class="showcase-table-builder-checkbox-preview__cell showcase-table-builder-checkbox-preview__cell--check">□</span>
        <span class="showcase-table-builder-checkbox-preview__cell">第 1 行：已勾选后可参与批量操作</span>
    </div>
    <div class="showcase-table-builder-checkbox-preview__row">
        <span class="showcase-table-builder-checkbox-preview__cell showcase-table-builder-checkbox-preview__cell--check">□</span>
        <span class="showcase-table-builder-checkbox-preview__cell">第 2 行：固定到左侧时，横向滚动仍可见</span>
    </div>
</div>
HTML,
            [
                $this->dualCodeVariant(
                    '普通多选',
                    '开启默认多选列。',
                    "[\n    'checkbox' => true,\n]",
                    "use app\\common\\render\\Table;\n\nTable::make('demo_table')\n    ->checkbox();"
                ),
                $this->dualCodeVariant(
                    '固定列多选',
                    '让多选列始终固定在左侧。',
                    "[\n    'checkbox' => 'left',\n]",
                    "use app\\common\\render\\Table;\n\nTable::make('demo_table')\n    ->checkbox('left');"
                ),
            ]
        );
    }

    private function page(): array
    {
        return $this->section(
            'page',
            '配置分页行为，例如每页数量、可选分页大小和分页布局。',
            [
                ['name' => '$options', 'summary' => '分页配置数组。'],
            ],
            <<<'CODE'
[
    'page' => [
        'limit' => 20,
        'limits' => [20, 50, 100],
    ],
]
CODE,
            <<<'CODE'
use app\common\render\Table;

Table::make('demo_table')
    ->page([
        'limit' => 20,
        'limits' => [20, 50, 100],
    ]);
CODE,
            ['page() 只合并安全的分页参数，内部会屏蔽不适合开放的配置项。'],
            ['当你发现分页行为不符合预期时，先检查 options() 是否又覆盖了 page 设置。'],
            'toolbar_interaction',
            'page() 分页配置',
            'preview',
            <<<'HTML'
<div class="showcase-table-builder-page-preview">
    <div class="showcase-table-builder-page-preview__meta">每页 20 条，共 128 条</div>
    <div class="showcase-table-builder-page-preview__pager">
        <span class="showcase-table-builder-page-preview__item">上一页</span>
        <span class="showcase-table-builder-page-preview__item showcase-table-builder-page-preview__item--active">1</span>
        <span class="showcase-table-builder-page-preview__item">2</span>
        <span class="showcase-table-builder-page-preview__item">3</span>
        <span class="showcase-table-builder-page-preview__item">下一页</span>
    </div>
</div>
HTML,
            [
                $this->dualCodeVariant(
                    '限制每页数量',
                    '最适合标准后台列表。',
                    "[\n    'page' => [\n        'limit' => 20,\n        'limits' => [20, 50, 100],\n    ],\n]",
                    "use app\\common\\render\\Table;\n\nTable::make('demo_table')\n    ->page([\n        'limit' => 20,\n        'limits' => [20, 50, 100],\n    ]);"
                ),
                $this->dualCodeVariant(
                    '精简分页布局',
                    '适合窄列表或卡片式布局中的内嵌表格。',
                    "[\n    'page' => [\n        'layout' => ['prev', 'page', 'next', 'count'],\n        'limit' => 10,\n    ],\n]",
                    "use app\\common\\render\\Table;\n\nTable::make('demo_table')\n    ->page([\n        'layout' => ['prev', 'page', 'next', 'count'],\n        'limit' => 10,\n    ]);"
                ),
            ]
        );
    }

    private function options(): array
    {
        return $this->section(
            'options',
            '透传底层表格 options，用于补充 page、limits、skin、size 等通用属性。',
            [
                ['name' => '$options', 'summary' => '表格原生 options 数组。'],
            ],
            <<<'CODE'
[
    'options' => [
        'limits' => [20, 50, 100],
        'size' => 'sm',
    ],
]
CODE,
            <<<'CODE'
use app\common\render\Table;

Table::make('demo_table')
    ->options([
        'limits' => [20, 50, 100],
        'size' => 'sm',
    ]);
CODE,
            ['options() 适合补齐通用底层参数，但不建议把整张表所有逻辑都塞进这里。'],
            ['优先使用更高层的方法，如 page()、toolbar()、search()，只有缺口才回退到 options()。'],
            'toolbar_interaction',
            'options() 原生配置',
            'preview',
            <<<'HTML'
<div class="showcase-table-builder-options-preview">
    <div class="showcase-table-builder-options-preview__item">
        <span class="showcase-table-builder-options-preview__key">size</span>
        <span class="showcase-table-builder-options-preview__value">sm</span>
    </div>
    <div class="showcase-table-builder-options-preview__item">
        <span class="showcase-table-builder-options-preview__key">limits</span>
        <span class="showcase-table-builder-options-preview__value">20 / 50 / 100</span>
    </div>
    <div class="showcase-table-builder-options-preview__item">
        <span class="showcase-table-builder-options-preview__key">skin</span>
        <span class="showcase-table-builder-options-preview__value">line</span>
    </div>
</div>
HTML,
            [
                $this->dualCodeVariant(
                    '分页与尺寸',
                    '适合集中配置 limits、size 等基础参数。',
                    "[\n    'options' => [\n        'limits' => [20, 50, 100],\n        'size' => 'sm',\n    ],\n]",
                    "use app\\common\\render\\Table;\n\nTable::make('demo_table')\n    ->options([\n        'limits' => [20, 50, 100],\n        'size' => 'sm',\n    ]);"
                ),
                $this->dualCodeVariant(
                    'where 默认参数',
                    '适合为数据接口补充固定场景参数。',
                    "[\n    'options' => [\n        'where' => ['scene' => 'archive', 'from' => 'showcase'],\n    ],\n]",
                    "use app\\common\\render\\Table;\n\nTable::make('demo_table')\n    ->options([\n        'where' => ['scene' => 'archive', 'from' => 'showcase'],\n    ]);"
                ),
                $this->dualCodeVariant(
                    '原生外观控制',
                    '适合统一调整 skin、高度和单元格尺寸。',
                    "[\n    'options' => [\n        'skin' => 'line',\n        'height' => 'full-260',\n        'cellMinWidth' => 120,\n    ],\n]",
                    "use app\\common\\render\\Table;\n\nTable::make('demo_table')\n    ->options([\n        'skin' => 'line',\n        'height' => 'full-260',\n        'cellMinWidth' => 120,\n    ]);"
                ),
            ]
        );
    }

    private function tree(): array
    {
        return $this->section(
            'tree',
            '开启树形表模式，适合栏目、部门、分类等层级结构数据。',
            [
                ['name' => '$enable', 'summary' => '是否开启树表模式。'],
            ],
            <<<'CODE'
[
    'tree' => true,
]
CODE,
            <<<'CODE'
use app\common\render\Table;

Table::make('category_table')
    ->tree(true);
CODE,
            ['tree() 只负责开启树表模式，数据本身仍需要提供正确的层级结构。'],
            ['如果树形数据来自接口，先确认返回结构里已经具备父子关系字段。'],
            'toolbar_interaction',
            'tree() 树形表模式',
            'source',
            '',
            [
                $this->dualCodeVariant(
                    '开启树表',
                    '最基本的层级列表入口。',
                    "[\n    'tree' => true,\n]",
                    "use app\\common\\render\\Table;\n\nTable::make('category_table')\n    ->tree(true);"
                ),
                $this->dualCodeVariant(
                    '关闭树表',
                    '适合在同一段构建逻辑里按条件切换。',
                    "[\n    'tree' => false,\n]",
                    "use app\\common\\render\\Table;\n\nTable::make('category_table')\n    ->tree(false);"
                ),
            ]
        );
    }

    private function assign(): array
    {
        return $this->section(
            'assign',
            '向表格布局模板透传模板变量，便于额外模板片段读取统计信息、上下文参数或调试标识。',
            [
                ['name' => '$name', 'summary' => '模板变量名，支持字符串或数组。'],
                ['name' => '$value', 'summary' => '模板变量值。'],
            ],
            <<<'CODE'
[
    'assign' => [
        'summaryTitle' => '库存看板',
        'scene' => 'showcase',
    ],
]
CODE,
            <<<'CODE'
use app\common\render\Table;

Table::make('stock_table')
    ->assign([
        'summaryTitle' => '库存看板',
        'scene' => 'showcase',
    ]);
CODE,
            ['assign() 主要服务于模板变量透传，不直接改变列行为或请求协议。'],
            ['不建议把业务核心逻辑藏在模板变量里；它更适合补充上下文，而不是替代配置 API。'],
            'advanced',
            'assign() 模板变量',
            'behavior',
            '',
            [
                $this->dualCodeVariant(
                    '单变量写法',
                    '适合少量上下文透传。',
                    "[\n    'assign' => ['summaryTitle' => '库存看板'],\n]",
                    "use app\\common\\render\\Table;\n\nTable::make('stock_table')\n    ->assign('summaryTitle', '库存看板');"
                ),
                $this->dualCodeVariant(
                    '数组批量写法',
                    '适合模板片段需要多个上下文字段。',
                    "[\n    'assign' => [\n        'summaryTitle' => '库存看板',\n        'scene' => 'showcase',\n    ],\n]",
                    "use app\\common\\render\\Table;\n\nTable::make('stock_table')\n    ->assign([\n        'summaryTitle' => '库存看板',\n        'scene' => 'showcase',\n    ]);"
                ),
            ]
        );
    }
}
