<?php
declare(strict_types=1);

namespace app\showcase\service\components\table;

/**
 * switch 列详情页
 */
final class SwitchColumnPage extends AbstractTableColumnPage
{
    /**
     * @param array<string, mixed> $component
     * @return array<string, mixed>
     */
    public function build(array $component): array
    {
        $sourceRefs = [
            ['path' => 'app/common/render/table/switch/Item.php', 'label' => 'switch 列实现', 'description' => '处理开关 options 与模板注入。'],
        ];

        $sections = [
            $this->makeSection(
                'basic_toggle',
                '基础开关列',
                '适合启用/禁用这类二元状态的列表展示。',
                $this->liveMain([
                    ['owner_name', '用户', '', [], ['minWidth' => 120]],
                    ['is_enabled', '状态', 'switch', ['关闭', '开启'], ['width' => 110]],
                ], [
                    '_where_in' => ['id', [1, 3]],
                    '_order' => ['id', 'asc'],
                    '_limit' => 2,
                ]),
                [
                    ['name' => 'type', 'value' => 'switch'],
                    ['name' => 'options', 'value' => "['关闭', '开启']"],
                ],
                [
                    'switch 列的核心是把二元值渲染成可切换开关。',
                    '是否真正发起状态更新，取决于页面是否接入对应事件处理。'
                ],
                <<<'CODE'
['status', '状态', 'switch', ['关闭', '开启'], ['width' => 110]]
CODE,
                <<<'CODE'
$this->table
    ->columns([
        ['username', '用户'],
        ['status', '状态', 'switch', ['关闭', '开启'], ['width' => 110]],
    ]);
CODE,
                $sourceRefs
            ),
            $this->makeSection(
                'custom_text',
                '自定义开关文案',
                '用 options 控制打开/关闭时的文案语义。',
                $this->liveMain([
                    ['title', '专题', '', [], ['minWidth' => 180]],
                    ['is_enabled', '公开展示', 'switch', ['私有', '公开'], ['width' => 120]],
                ], [
                    '_where_in' => ['id', [1, 3]],
                    '_order' => ['id', 'asc'],
                    '_limit' => 2,
                ]),
                [
                    ['name' => 'options', 'value' => "['私有', '公开']"],
                    ['name' => '适用', 'value' => '公开/私有、允许/禁止'],
                ],
                [
                    '开关列虽然是布尔值，但展示文案应尽量贴近业务语义。',
                ],
                <<<'CODE'
['is_public', '公开展示', 'switch', ['私有', '公开']]
CODE,
                <<<'CODE'
$this->table
    ->columns([
        ['title', '专题'],
        ['is_public', '公开展示', 'switch', ['私有', '公开']],
    ]);
CODE,
                $sourceRefs
            ),
            $this->makeSection(
                'event_binding',
                '事件绑定提示',
                '需要落地“点击即更新”时，前端还要接入 DolphinTableOn 或表单事件监听。',
                $this->infoPreview('事件接入', [
                    'switch 列负责渲染和默认 AJAX 提交流程，但不等于你的业务状态一定更新成功。',
                    '如果页面还有额外联动逻辑，应结合 DolphinTableOn 或表单事件继续监听切换结果。',
                ]),
                [
                    ['name' => '联动方式', 'value' => 'DolphinTableOn + form.on'],
                    ['name' => '依赖', 'value' => '表格 done 事件'],
                ],
                [
                    'switch 列本身负责渲染，不负责业务提交。',
                    '如果点击后要更新数据库，应参考表格事件系统文档绑定前端监听。',
                ],
                <<<'CODE'
['can_refund', '允许退款', 'switch', ['禁止', '允许'], ['width' => 120]]
CODE,
                <<<'CODE'
$this->table
    ->columns([
        ['order_no', '订单号'],
        ['can_refund', '允许退款', 'switch', ['禁止', '允许'], ['width' => 120]],
    ]);

// 结合 docs/表格/表格事件系统使用指南 绑定切换事件
CODE,
                $sourceRefs
            ),
            $this->makeSection(
                'business_snippet',
                '业务列片段',
                '常和状态列、操作列一起出现在管理列表中。',
                $this->liveMain([
                    ['owner_name', '角色', '', [], ['minWidth' => 140]],
                    ['is_system', '系统角色', 'switch', ['否', '是'], ['width' => 110]],
                    ['status', '状态', 'status', ['关闭', '开启:green', '冻结:red'], ['width' => 100]],
                ], [
                    '_where_in' => ['id', [1, 2, 3]],
                    '_order' => ['id', 'asc'],
                    '_limit' => 3,
                ]),
                [
                    ['name' => '组合列', 'value' => 'switch + status + actions'],
                    ['name' => '注意', 'value' => '避免页面塞入过多可操作列'],
                ],
                [
                    '如果列表里已经有大量行级操作，再加 switch 要注意交互密度。',
                ],
                <<<'CODE'
[
    ['name', '角色'],
    ['is_system', '系统角色', 'switch', ['否', '是'], ['width' => 110]],
    ['status', '状态', 'status', ['关闭', '开启:green'], ['width' => 100]],
]
CODE,
                <<<'CODE'
$this->table
    ->columns([
        ['name', '角色'],
        ['is_system', '系统角色', 'switch', ['否', '是'], ['width' => 110]],
        ['status', '状态', 'status', ['关闭', '开启:green'], ['width' => 100]],
    ]);
CODE,
                $sourceRefs
            ),
        ];

        return $this->makePage(
            $component,
            [
                'summary' => 'switch 列用于在表格中展示并切换二元状态值，适合启用/禁用、公开/私有、允许/禁止等列表场景。',
                'scenarios' => [
                    '用户、订单、内容等需要在列表中快速切换布尔状态的页面',
                    '希望开发者同时知道开关显示写法和前端事件接入边界',
                ],
                'capabilities' => ['开关展示', '业务文案', '事件接入提示', '组合列示例'],
                'quick_start' => [
                    'array_code' => <<<'CODE'
[
    ['status', '状态', 'switch', ['关闭', '开启'], ['width' => 110]],
]
CODE,
                    'table_code' => <<<'CODE'
$this->table
    ->columns([
        ['status', '状态', 'switch', ['关闭', '开启'], ['width' => 110]],
    ]);
CODE,
                ],
            ],
            [
                [
                    'title' => '基础参数',
                    'items' => [
                        ['name' => 'type', 'summary' => '固定写 `switch`。'],
                        ['name' => 'options', 'summary' => '定义开/关时的文案。'],
                        ['name' => 'cols.width', 'summary' => '建议 100 到 120。'],
                    ],
                ],
                [
                    'title' => '行为边界',
                    'items' => [
                        ['name' => '渲染边界', 'summary' => '内置列负责渲染，不直接等于业务更新成功。'],
                        ['name' => '事件接入', 'summary' => '需要结合 DolphinTableOn 和表单事件监听。'],
                    ],
                ],
            ],
            $sections,
            [
                ['key' => 'state.status', 'title' => 'status 状态标签', 'status' => 'available'],
                ['key' => 'interactive.actions', 'title' => 'actions 操作列', 'status' => 'available'],
                ['key' => 'state.yes_no', 'title' => 'yes_no 是/否列', 'status' => 'available'],
            ]
        );
    }
}
