<?php
declare(strict_types=1);

namespace app\showcase\service\components\table;

/**
 * status 列详情页
 */
final class StatusColumnPage extends AbstractTableColumnPage
{
    /**
     * @param array<string, mixed> $component
     * @return array<string, mixed>
     */
    public function build(array $component): array
    {
        $sections = [
            $this->makeSection(
                'basic_mapping',
                '基础状态映射',
                '显式声明标签文案和颜色，适合审核状态、启用状态这类固定映射。',
                $this->liveMain([
                    ['id', 'ID', '', [], ['width' => 70]],
                    ['title', '标题', '', [], ['minWidth' => 180]],
                    ['status', '状态', 'status', ['禁用', '启用:green'], ['width' => 100]],
                ], [
                    '_where_in' => ['id', [1, 2]],
                    '_order' => ['id', 'asc'],
                    '_limit' => 2,
                ]),
                [
                    ['name' => 'type', 'value' => 'status'],
                    ['name' => 'options', 'value' => "['禁用', '启用:green']"],
                ],
                [
                    '最常见写法是用 options 传入标签与颜色映射。',
                    '当 value 为 0/1 时，通常会直接映射成禁用/启用。',
                ],
                <<<'CODE'
['status', '状态', 'status', ['禁用', '启用:green'], ['width' => 100]]
CODE,
                <<<'CODE'
$this->table
    ->columns([
        ['id', 'ID'],
        ['title', '标题'],
        ['status', '状态', 'status', ['禁用', '启用:green'], ['width' => 100]],
    ]);
CODE,
                [
                    ['path' => 'app/common/render/table/status/Item.php', 'label' => 'status 列实现', 'description' => '处理标签和颜色映射。'],
                ]
            ),
            $this->makeSection(
                'default_color',
                '默认颜色推断',
                '当 options 只传文案时，组件会对 1 默认补绿色，其余值保持默认色。',
                $this->liveMain([
                    ['title', '任务', '', [], ['minWidth' => 180]],
                    ['status', '流程状态', 'status', ['待审核', '已通过'], ['width' => 120]],
                ], [
                    '_where_in' => ['id', [1, 2]],
                    '_order' => ['id', 'asc'],
                    '_limit' => 2,
                ]),
                [
                    ['name' => 'options', 'value' => "['待审核', '已通过']"],
                    ['name' => '默认行为', 'value' => '索引 1 自动补 green'],
                ],
                [
                    '如果只写文案不写颜色，索引为 1 的选项会默认套绿色标签。',
                    '需要更明确的品牌色或风险色时，应显式写成 文案:颜色。',
                ],
                <<<'CODE'
['status', '流程状态', 'status', ['待审核', '已通过']]
CODE,
                <<<'CODE'
$this->table
    ->columns([
        ['title', '任务'],
        ['status', '流程状态', 'status', ['待审核', '已通过']],
    ]);
CODE,
                [
                    ['path' => 'app/common/render/table/status/Item.php', 'label' => 'status 列实现', 'description' => '默认颜色推断逻辑在这里处理。'],
                ]
            ),
            $this->makeSection(
                'multi_state',
                '多状态颜色映射',
                '一个列里承载多个业务状态时，建议显式给每个状态写清楚颜色。',
                $this->liveMain([
                    ['code', '订单号', '', [], ['minWidth' => 140]],
                    ['status', '状态', 'status', ['待支付', '已完成:green', '已关闭:red'], ['minWidth' => 120]],
                ], [
                    '_where_in' => ['id', [1, 2, 3]],
                    '_order' => ['id', 'asc'],
                    '_limit' => 3,
                ]),
                [
                    ['name' => 'options', 'value' => "['待支付', '已完成:green', '已关闭:red']"],
                    ['name' => '适用', 'value' => '订单、工单、审批流'],
                ],
                [
                    '多状态业务不要依赖默认颜色，避免开发者误解每个状态的视觉语义。',
                    '如果状态值来自枚举，建议保持 options 顺序和枚举值一致。',
                ],
                <<<'CODE'
['status', '状态', 'status', ['待支付', '已完成:green', '已关闭:red'], ['minWidth' => 120]]
CODE,
                <<<'CODE'
$this->table
    ->columns([
        ['order_no', '订单号'],
        ['status', '状态', 'status', ['待支付', '已完成:green', '已关闭:red'], ['minWidth' => 120]],
    ]);
CODE,
                [
                    ['path' => 'app/common/render/table/status/item.html', 'label' => 'status 列模板', 'description' => '模板中会读取 label 和 color 数据。'],
                ]
            ),
            $this->makeSection(
                'business_snippet',
                '业务列片段',
                '在真实业务表格里，status 列通常和时间列、操作列一起出现。',
                $this->liveMain([
                    ['id', 'ID', '', [], ['width' => 70]],
                    ['category', '分类', '', [], ['minWidth' => 140]],
                    ['status', '状态', 'status', ['禁用', '启用:green', '归档:red'], ['width' => 100]],
                    ['updated_at', '更新时间', 'datetime', [], ['minWidth' => 160]],
                ], [
                    '_where_in' => ['id', [1, 2, 3]],
                    '_order' => ['id', 'asc'],
                    '_limit' => 3,
                ]),
                [
                    ['name' => '组合列', 'value' => 'status + datetime + actions'],
                    ['name' => '建议宽度', 'value' => '100 ~ 120'],
                ],
                [
                    '状态列通常不需要太宽，控制在 100 左右即可。',
                    '如果还有快速编辑或操作列，建议把状态列放在中间区域，便于扫读。',
                ],
                <<<'CODE'
[
    ['id', 'ID', '', [], ['width' => 70]],
    ['name', '分类'],
    ['status', '状态', 'status', ['禁用', '启用:green'], ['width' => 100]],
    ['updated_at', '更新时间', 'datetime', [], ['minWidth' => 160]],
]
CODE,
                <<<'CODE'
$this->table
    ->columns([
        ['id', 'ID', '', [], ['width' => 70]],
        ['name', '分类'],
        ['status', '状态', 'status', ['禁用', '启用:green'], ['width' => 100]],
        ['updated_at', '更新时间', 'datetime', [], ['minWidth' => 160]],
    ]);
CODE,
                [
                    ['path' => 'app/common/render/table/status/Item.php', 'label' => 'status 列实现', 'description' => '状态列组合场景可直接复用当前内置能力。'],
                ]
            ),
        ];

        return $this->makePage(
            $component,
            [
                'summary' => 'status 列用于将状态值映射为带颜色的标签展示，适合审核状态、上架状态、流程状态等需要在列表中快速识别的场景。',
                'scenarios' => [
                    '审核状态、启用状态、流程状态等需要一眼区分的业务列表',
                    '多种状态共存，且希望通过颜色降低扫读成本的后台表格',
                    '需要开发者快速知道 options 怎样配置状态文案和颜色',
                ],
                'capabilities' => [
                    '状态文案映射',
                    '颜色标签',
                    '默认颜色推断',
                    '多状态业务写法',
                ],
                'quick_start' => [
                    'array_code' => <<<'CODE'
[
    ['status', '状态', 'status', ['禁用', '启用:green'], ['width' => 100]],
]
CODE,
                    'table_code' => <<<'CODE'
// 等价于使用 Table::columns() 配置当前列
$this->table
    ->columns([
        ['status', '状态', 'status', ['禁用', '启用:green'], ['width' => 100]],
    ]);
CODE,
                ],
            ],
            [
                [
                    'title' => '基础参数',
                    'items' => [
                        ['name' => 'field / title', 'summary' => '字段名和表头标题。'],
                        ['name' => 'type', 'summary' => '固定写 `status`。'],
                        ['name' => 'options', 'summary' => '状态标签列表，支持 `文案:颜色`。'],
                        ['name' => 'cols.width', 'summary' => '建议控制在 100 到 120。'],
                    ],
                ],
                [
                    'title' => '配置建议',
                    'items' => [
                        ['name' => '默认颜色', 'summary' => '只写文案时，索引为 1 的项默认补绿色。'],
                        ['name' => '多状态场景', 'summary' => '建议为每个状态显式写颜色，降低歧义。'],
                    ],
                ],
            ],
            $sections,
            // makePage() 会统一生成 sidebar_source_refs 和 source_refs 供详情页展示源码入口
            [
                ['key' => 'state.switch', 'title' => 'switch 开关列', 'status' => 'available'],
                ['key' => 'state.yes_no', 'title' => 'yes_no 是/否列', 'status' => 'available'],
                ['key' => 'interactive.actions', 'title' => 'actions 操作列', 'status' => 'available'],
            ]
        );
    }
}
