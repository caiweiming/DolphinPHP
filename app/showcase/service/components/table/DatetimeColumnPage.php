<?php
declare(strict_types=1);

namespace app\showcase\service\components\table;

/**
 * datetime 列详情页
 */
final class DatetimeColumnPage extends AbstractTableColumnPage
{
    /**
     * @param array<string, mixed> $component
     * @return array<string, mixed>
     */
    public function build(array $component): array
    {
        $sourceRefs = [
            ['path' => 'app/common/render/table/datetime/Item.php', 'label' => 'datetime 列实现', 'description' => '处理日期时间格式化与 edit 模式。'],
        ];

        $sections = [
            $this->makeSection(
                'basic_datetime',
                '基础日期时间',
                '直接把时间戳格式化为标准日期时间字符串。',
                $this->liveMain([
                    ['title', '文章标题', '', [], ['minWidth' => 180]],
                    ['created_at', '创建时间', 'datetime', [], ['minWidth' => 160]],
                ], [
                    '_where_in' => ['id', [1, 2]],
                    '_order' => ['id', 'asc'],
                    '_limit' => 2,
                ]),
                [
                    ['name' => 'type', 'value' => 'datetime'],
                    ['name' => '默认格式', 'value' => 'Y-m-d H:i:s'],
                ],
                [
                    'datetime 列默认按时间戳格式化，不是直接输出原始值。',
                ],
                <<<'CODE'
['created_at', '创建时间', 'datetime', [], ['minWidth' => 160]]
CODE,
                <<<'CODE'
$this->table
    ->columns([
        ['title', '文章标题'],
        ['created_at', '创建时间', 'datetime', [], ['minWidth' => 160]],
    ]);
CODE,
                $sourceRefs
            ),
            $this->makeSection(
                'custom_format',
                '自定义格式',
                '可以通过 options 或 format 改成只显示日期、只显示时间等格式。',
                $this->liveMain([
                    ['title', '活动', '', [], ['minWidth' => 180]],
                    ['expire_date', '开始日期', 'date', 'Y-m-d', ['width' => 120]],
                ], [
                    '_where_in' => ['id', [1]],
                    '_limit' => 1,
                ]),
                [
                    ['name' => 'options', 'value' => 'Y-m-d'],
                    ['name' => '适用', 'value' => '只显示日期或时间'],
                ],
                [
                    '字符串 options 会被当成 format 使用，这是 datetime 列的一个简洁写法。',
                ],
                <<<'CODE'
['start_at', '开始日期', 'date', 'Y-m-d', ['width' => 120]]
CODE,
                <<<'CODE'
$this->table
    ->columns([
        ['activity_name', '活动'],
        ['start_at', '开始日期', 'date', 'Y-m-d', ['width' => 120]],
    ]);
CODE,
                $sourceRefs
            ),
            $this->makeSection(
                'pretty_time',
                'pretty_time 友好时间',
                '当你希望显示“3 分钟前”“昨天”这类友好时间时，可用 pretty_time。',
                $this->liveMain([
                    ['title', '消息标题', '', [], ['minWidth' => 180]],
                    ['publish_time', '发布时间', 'pretty_time', [], ['minWidth' => 140]],
                ], [
                    '_where_in' => ['id', [1, 2, 3]],
                    '_order' => ['id', 'asc'],
                    '_limit' => 3,
                ]),
                [
                    ['name' => 'type', 'value' => 'pretty_time'],
                    ['name' => '适用', 'value' => '消息、动态、日志'],
                ],
                [
                    'pretty_time 适合强调相对时间，但不适合财务、审计这类需要精确时间的场景。',
                ],
                <<<'CODE'
['published_at', '发布时间', 'pretty_time']
CODE,
                <<<'CODE'
$this->table
    ->columns([
        ['message_title', '消息标题'],
        ['published_at', '发布时间', 'pretty_time'],
    ]);
CODE,
                $sourceRefs
            ),
            $this->makeSection(
                'editable_datetime',
                '可编辑时间列',
                '支持 `date.edit`、`time.edit`、`datetime.edit` 三种 quickEdit 写法。',
                $this->liveMain([
                    ['category', '部门', '', [], ['minWidth' => 140]],
                    ['expire_date', '过期日期', 'date.edit', ['type' => 'date'], ['width' => 140]],
                ], [
                    '_where_in' => ['id', [1, 2]],
                    '_order' => ['id', 'asc'],
                    '_limit' => 2,
                ]),
                [
                    ['name' => '可编辑类型', 'value' => 'date.edit / time.edit / datetime.edit'],
                    ['name' => '前端依赖', 'value' => 'laydate'],
                ],
                [
                    '进入 edit 模式后，内部会改走 datetime/edit.html 模板，并注入 laydate 配置。',
                ],
                <<<'CODE'
['expire_date', '过期日期', 'date.edit']
CODE,
                <<<'CODE'
$this->table
    ->tableName('admin_department')
    ->columns([
        ['name', '部门名称'],
        ['expire_date', '过期日期', 'date.edit'],
    ]);
CODE,
                $sourceRefs
            ),
            $this->makeSection(
                'quickedit_boundary',
                'quickEdit 边界',
                '日期 quickEdit 当前默认会把前端日期字符串转成时间戳再落库，这一点必须讲清楚。',
                $this->infoPreview('落库边界', [
                    '时间戳字段可以直接使用 date.edit / datetime.edit。',
                    '如果库表存的是字符串日期，不要直接依赖默认 quickEdit 落库，应自定义处理或改走普通编辑页。',
                ]),
                [
                    ['name' => '后端行为', 'value' => 'type=datetime 时执行 strtotime'],
                    ['name' => '风险', 'value' => '字符串日期字段会被写成时间戳'],
                ],
                [
                    '这部分是 datetime 列最关键的使用边界，showcase 必须明确提示。',
                    '如果你的库表字段存的是字符串日期，应自定义 quickEdit 处理或改走普通编辑页。',
                ],
                <<<'CODE'
['publish_time', '发布时间', 'datetime.edit']
CODE,
                <<<'CODE'
$this->table
    ->tableName('admin_article')
    ->validate('Article', ['publish_time'])
    ->columns([
        ['title', '标题'],
        ['publish_time', '发布时间', 'datetime.edit'],
    ]);
CODE,
                $sourceRefs
            ),
        ];

        return $this->makePage(
            $component,
            [
                'summary' => 'datetime 列用于格式化时间戳字段，并承接 date/time/datetime 及其 quickEdit 相关能力。',
                'scenarios' => [
                    '文章、订单、日志等需要稳定格式化时间戳的后台列表',
                    '需要在列表中直接编辑日期时间字段的 quickEdit 场景',
                ],
                'capabilities' => ['基础日期时间', '自定义格式', 'pretty_time', 'edit 模式', 'quickEdit 边界'],
                'quick_start' => [
                    'array_code' => <<<'CODE'
[
    ['created_at', '创建时间', 'datetime', [], ['minWidth' => 160]],
]
CODE,
                    'table_code' => <<<'CODE'
$this->table
    ->tableName('admin_department')
    ->columns([
        ['created_at', '创建时间', 'datetime', [], ['minWidth' => 160]],
        ['expire_date', '过期日期', 'date.edit'],
    ]);
CODE,
                ],
            ],
            [
                [
                    'title' => '基础参数',
                    'items' => [
                        ['name' => 'type', 'summary' => '支持 `date`、`time`、`datetime`、`pretty_time` 以及对应 `.edit` 类型。'],
                        ['name' => 'format / options', 'summary' => '控制显示格式，也可透传 laydate 配置。'],
                    ],
                ],
                [
                    'title' => '重要边界',
                    'items' => [
                        ['name' => '时间戳假设', 'summary' => '普通 datetime 列默认把字段当时间戳处理。'],
                        ['name' => 'quickEdit 落库', 'summary' => 'edit 模式默认把日期字符串转成时间戳。'],
                    ],
                ],
            ],
            $sections,
            // makePage() 会统一生成 sidebar_source_refs 和 source_refs 供详情页展示源码入口
            [
                ['key' => 'interactive.select', 'title' => 'select 下拉映射列', 'status' => 'available'],
                ['key' => 'state.status', 'title' => 'status 状态标签', 'status' => 'available'],
                ['key' => 'interactive.actions', 'title' => 'actions 操作列', 'status' => 'available'],
            ]
        );
    }
}
