<?php
declare(strict_types=1);

namespace app\showcase\service\components\table;

/**
 * select 列详情页
 */
final class SelectColumnPage extends AbstractTableColumnPage
{
    /**
     * @param array<string, mixed> $component
     * @return array<string, mixed>
     */
    public function build(array $component): array
    {
        $sourceRefs = [
            ['path' => 'app/common/render/table/select/Item.php', 'label' => 'select 列实现', 'description' => '注入 select 模板和 quickEdit 所需配置。'],
        ];

        $sections = [
            $this->makeSection(
                'basic_select',
                '基础下拉列',
                '适合把状态值或枚举值渲染成表格内可切换的下拉框。',
                $this->liveMain([
                    ['owner_name', '用户名', '', [], ['minWidth' => 120]],
                    ['status', '状态', 'select', ['0' => '禁用', '1' => '启用'], ['width' => 120]],
                ], [
                    '_where_in' => ['id', [1, 2]],
                    '_order' => ['id', 'asc'],
                    '_limit' => 2,
                ]),
                [
                    ['name' => 'type', 'value' => 'select'],
                    ['name' => 'options', 'value' => "['0' => '禁用', '1' => '启用']"],
                ],
                [
                    'select 列不是静态映射列，而是会渲染出真实的下拉框。',
                ],
                <<<'CODE'
['status', '状态', 'select', ['0' => '禁用', '1' => '启用'], ['width' => 120]]
CODE,
                <<<'CODE'
$this->table
    ->columns([
        ['username', '用户名'],
        ['status', '状态', 'select', ['0' => '禁用', '1' => '启用'], ['width' => 120]],
    ]);
CODE,
                $sourceRefs
            ),
            $this->makeSection(
                'quickedit_options',
                '映射值与回填',
                '下拉项值会与当前行字段值比对，并回填选中项。',
                $this->liveMain([
                    ['code', '订单号', '', [], ['minWidth' => 140]],
                    ['priority', '发货状态', 'select', ['5' => '待发货', '7' => '已发货', '9' => '已签收'], ['minWidth' => 130]],
                ], [
                    '_where_in' => ['id', [1, 2, 3]],
                    '_order' => ['id', 'asc'],
                    '_limit' => 3,
                ]),
                [
                    ['name' => '回填逻辑', 'value' => 'option value == 当前字段值'],
                    ['name' => '空选项', 'value' => '默认带“请选择”'],
                ],
                [
                    '模板会自动附带一个空选项，适合允许清空的场景。',
                ],
                <<<'CODE'
['shipping_status', '发货状态', 'select', ['pending' => '待发货', 'sent' => '已发货', 'signed' => '已签收']]
CODE,
                <<<'CODE'
$this->table
    ->columns([
        ['order_no', '订单号'],
        ['shipping_status', '发货状态', 'select', ['pending' => '待发货', 'sent' => '已发货', 'signed' => '已签收']],
    ]);
CODE,
                $sourceRefs
            ),
            $this->makeSection(
                'optgroup',
                'optgroup 分组写法',
                'options 支持二维数组，用于渲染下拉分组。',
                $this->liveMain([
                    ['owner_name', '负责人', '', [], ['minWidth' => 120]],
                    ['department_code', '所属部门', 'select', ['华东区域' => ['OPS-EAST' => '运营东区'], '销售区域' => ['SALES-B2B' => '企业销售'], '内容中心' => ['CONTENT-QA' => '内容质检']], ['minWidth' => 150]],
                ], [
                    '_where_in' => ['id', [1, 2, 3]],
                    '_order' => ['id', 'asc'],
                    '_limit' => 3,
                ]),
                [
                    ['name' => '二维 options', 'value' => "['广东' => ['sz' => '深圳组', 'gz' => '广州组']]"],
                    ['name' => '效果', 'value' => '渲染 optgroup'],
                ],
                [
                    '模板会识别数组值并自动生成 optgroup 结构。',
                ],
                <<<'CODE'
['department_code', '所属部门', 'select', ['广东' => ['sz' => '深圳组', 'gz' => '广州组'], '上海' => ['sh' => '上海组']]]
CODE,
                <<<'CODE'
$this->table
    ->columns([
        ['owner', '负责人'],
        ['department_code', '所属部门', 'select', ['广东' => ['sz' => '深圳组', 'gz' => '广州组'], '上海' => ['sh' => '上海组']]],
    ]);
CODE,
                $sourceRefs
            ),
            $this->makeSection(
                'fix_cell',
                '样式修复说明',
                'select 列会自动追加 `dp-table-fix-cell`，避免表格内下拉导致行高异常。',
                $this->infoPreview('样式处理', [
                    'select 列会自动追加 dp-table-fix-cell，避免下拉框把表格行高撑乱。',
                    '这是 select 列相对普通静态列的一个内部差异点。',
                ]),
                [
                    ['name' => '附加 class', 'value' => 'dp-table-fix-cell'],
                    ['name' => '目的', 'value' => '修复行高'],
                ],
                [
                    '这也是 select 列和普通静态列在内部处理上的明显差异。',
                ],
                <<<'CODE'
['status', '状态', 'select', ['0' => '禁用', '1' => '启用']]
CODE,
                <<<'CODE'
$this->table
    ->columns([
        ['status', '状态', 'select', ['0' => '禁用', '1' => '启用']],
    ]);
CODE,
                $sourceRefs
            ),
            $this->makeSection(
                'quickedit_chain',
                'quickEdit 提交链路',
                '当选择变化时，模板会直接向 `DolphinConfig.url.quickEdit` 发起 AJAX 更新。',
                $this->infoPreview('提交链路', [
                    '前端通过 lay-filter 监听当前下拉变更。',
                    '变化后会向 DolphinConfig.url.quickEdit 提交 id、field、value、_t。',
                    '若后端返回失败，组件会恢复原选中值并提示错误。',
                ]),
                [
                    ['name' => '请求地址', 'value' => 'DolphinConfig.url.quickEdit'],
                    ['name' => '提交字段', 'value' => 'id / field / value / _t'],
                ],
                [
                    'select 列并不是只负责渲染，它已经内置了 quickEdit 提交流程。',
                    '因此页面要确保 CRUD token、tableName 和 quickEdit 白名单配置齐全。',
                ],
                <<<'CODE'
['status', '状态', 'select', ['0' => '禁用', '1' => '启用']]
CODE,
                <<<'CODE'
$this->table
    ->tableName('admin_user')
    ->columns([
        ['username', '用户名'],
        ['status', '状态', 'select', ['0' => '禁用', '1' => '启用']],
    ]);

// 需要后端同时具备 quickEdit 处理能力
CODE,
                $sourceRefs
            ),
        ];

        return $this->makePage(
            $component,
            [
                'summary' => 'select 列用于在表格中渲染下拉选择框，并通过 quickEdit 请求即时更新当前行字段。',
                'scenarios' => [
                    '状态、部门、等级等枚举字段需要在列表中直接切换的后台页面',
                    '开发者需要明确知道 select 列已经内置 quickEdit 提交链路的场景',
                ],
                'capabilities' => ['基础下拉列', '回填与空选项', 'optgroup 分组写法', 'dp-table-fix-cell', 'quickEdit 提交链路'],
                'quick_start' => [
                    'array_code' => <<<'CODE'
[
    ['status', '状态', 'select', ['0' => '禁用', '1' => '启用'], ['width' => 120]],
]
CODE,
                    'table_code' => <<<'CODE'
$this->table
    ->tableName('admin_user')
    ->columns([
        ['status', '状态', 'select', ['0' => '禁用', '1' => '启用'], ['width' => 120]],
    ]);
CODE,
                ],
            ],
            [
                [
                    'title' => '基础参数',
                    'items' => [
                        ['name' => 'type', 'summary' => '固定写 `select`。'],
                        ['name' => 'options', 'summary' => '支持普通键值对，也支持二维数组 optgroup。'],
                    ],
                ],
                [
                    'title' => '关键边界',
                    'items' => [
                        ['name' => 'quickEdit', 'summary' => '选择变化后会直接发 AJAX 更新。'],
                        ['name' => 'dp-table-fix-cell', 'summary' => '自动修复下拉单元格行高。'],
                    ],
                ],
            ],
            $sections,
            // makePage() 会统一生成 sidebar_source_refs 和 source_refs 供详情页展示源码入口
            [
                ['key' => 'interactive.actions', 'title' => 'actions 操作列', 'status' => 'available'],
                ['key' => 'basic.datetime', 'title' => 'datetime 时间列', 'status' => 'available'],
                ['key' => 'state.status', 'title' => 'status 状态标签', 'status' => 'available'],
            ]
        );
    }
}
