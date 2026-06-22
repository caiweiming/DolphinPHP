<?php
declare(strict_types=1);

namespace app\showcase\service\components\table;

/**
 * actions 列详情页
 */
final class ActionsColumnPage extends AbstractTableColumnPage
{
    /**
     * @param array<string, mixed> $component
     * @return array<string, mixed>
     */
    public function build(array $component): array
    {
        $sourceRefs = [
            ['path' => 'app/common/render/table/actions/Item.php', 'label' => 'actions 列实现', 'description' => '内置按钮和自定义按钮归一化逻辑。'],
        ];

        $sections = [
            $this->makeSection(
                'builtin_buttons',
                '内置按钮组合',
                '最常见的写法是直接使用 edit、delete 等内置按钮标识。',
                $this->liveMain([
                    ['owner_name', '用户名', '', [], ['minWidth' => 120]],
                    ['right_button', '操作', 'actions', ['edit', 'delete'], ['width' => 160]],
                ], [
                    '_where_in' => ['id', [1, 2]],
                    '_order' => ['id', 'asc'],
                    '_limit' => 2,
                ]),
                [
                    ['name' => 'type', 'value' => 'actions'],
                    ['name' => 'options', 'value' => "['edit', 'delete']"],
                ],
                [
                    '能直接复用内置按钮时，优先用字符串标识，配置最简。',
                ],
                <<<'CODE'
['right_button', '操作', 'actions', ['edit', 'delete'], ['width' => 160]]
CODE,
                <<<'CODE'
$this->table
    ->columns([
        ['username', '用户名'],
        ['right_button', '操作', 'actions', ['edit', 'delete'], ['width' => 160]],
    ]);
CODE,
                $sourceRefs
            ),
            $this->makeSection(
                'override_builtin',
                '覆盖内置按钮',
                '当按钮标题、链接或打开方式不同于默认值时，可以按键名覆盖。',
                $this->liveMain([
                    ['title', '专题', '', [], ['minWidth' => 180]],
                    ['right_button', '操作', 'actions', [
                        'edit' => [
                            'title' => '打开编辑页',
                            'target' => '_blank',
                            'auth' => '',
                        ],
                        'delete',
                    ], ['width' => 180]],
                ], [
                    '_where_in' => ['id', [1]],
                    '_limit' => 1,
                ]),
                [
                    ['name' => 'edit.title', 'value' => '打开编辑页'],
                    ['name' => 'edit.target', 'value' => '_blank'],
                ],
                [
                    '键名用内置按钮名，值用数组覆盖已有配置，是最稳妥的扩展方式。',
                ],
                <<<'CODE'
[
    'edit' => [
        'title' => '打开编辑页',
        'target' => '_blank',
    ],
    'delete',
]
CODE,
                <<<'CODE'
$this->table
    ->actions([
        'edit' => [
            'title' => '打开编辑页',
            'target' => '_blank',
        ],
        'delete',
    ]);
CODE,
                $sourceRefs
            ),
            $this->makeSection(
                'custom_button',
                '自定义按钮',
                '业务需要额外动作时，可以直接写完整按钮配置。',
                $this->liveMain([
                    ['title', '文件名', '', [], ['minWidth' => 160]],
                    ['right_button', '操作', 'actions', [[
                        'name' => 'preview',
                        'title' => '预览',
                        'event' => 'preview',
                        'url' => '/showcase/file/preview/__id__',
                        'icon' => 'ti ti-eye',
                        'auth' => '',
                    ], 'delete'], ['width' => 180]],
                ], [
                    '_where_in' => ['id', [2]],
                    '_limit' => 1,
                ]),
                [
                    ['name' => 'name', 'value' => 'preview'],
                    ['name' => 'event', 'value' => 'preview'],
                ],
                [
                    '如果不是复用内置按钮，建议显式写 name、title、event、url。',
                    'URL 中常用 `__id__` 之类占位符拼接当前行数据。',
                ],
                <<<'CODE'
[
    [
        'name' => 'preview',
        'title' => '预览',
        'event' => 'preview',
        'url' => (string) url('preview', ['id' => '__id__']),
        'icon' => 'ti ti-eye',
    ],
    'delete',
]
CODE,
                <<<'CODE'
$this->table
    ->actions([
        [
            'name' => 'preview',
            'title' => '预览',
            'event' => 'preview',
            'url' => (string) url('preview', ['id' => '__id__']),
            'icon' => 'ti ti-eye',
        ],
        'delete',
    ]);
CODE,
                $sourceRefs
            ),
            $this->makeSection(
                'dropdown',
                '下拉更多操作',
                '按钮过多时建议收敛到 dropdown，避免右侧操作列太拥挤。',
                $this->liveMain([
                    ['code', '订单号', '', [], ['minWidth' => 140]],
                    ['right_button', '操作', 'actions', [
                        'edit',
                        'more' => [
                            'title' => '更多',
                            'auth' => '',
                            'dropdown' => ['enable', '-', 'disable'],
                        ],
                    ], ['width' => 200]],
                ], [
                    '_where_in' => ['id', [3]],
                    '_limit' => 1,
                ]),
                [
                    ['name' => 'dropdown', 'value' => "['enable', '-', 'disable']"],
                    ['name' => '推荐场景', 'value' => '按钮超过 3 个'],
                ],
                [
                    '更多操作可以写字符串语法，也可以写数组语法。',
                    '复杂场景建议使用 dropdown 数组，可读性更高。',
                ],
                <<<'CODE'
[
    'edit',
    'more' => [
        'title' => '更多',
        'dropdown' => ['enable', '-', 'disable'],
    ],
]
CODE,
                <<<'CODE'
$this->table
    ->actions([
        'edit',
        'more' => [
            'title' => '更多',
            'dropdown' => ['enable', '-', 'disable'],
        ],
    ]);
CODE,
                $sourceRefs
            ),
        ];

        return $this->makePage(
            $component,
            [
                'summary' => 'actions 列用于承载行级按钮、权限控制和常用操作入口，适合大多数后台列表页。',
                'scenarios' => [
                    'CRUD 列表页需要编辑、删除、启停等标准行级动作',
                    '业务页面需要预览、导出、更多菜单等扩展入口',
                ],
                'capabilities' => ['内置按钮', '覆盖默认值', '自定义按钮', 'dropdown 更多操作'],
                'quick_start' => [
                    'array_code' => <<<'CODE'
[
    ['right_button', '操作', 'actions', ['edit', 'delete'], ['width' => 160]],
]
CODE,
                    'table_code' => <<<'CODE'
$this->table
    ->columns([
        ['right_button', '操作', 'actions', ['edit', 'delete'], ['width' => 160]],
    ]);
CODE,
                ],
            ],
            [
                [
                    'title' => '基础参数',
                    'items' => [
                        ['name' => 'type', 'summary' => '固定写 `actions`。'],
                        ['name' => 'options', 'summary' => '按钮配置数组，可混合内置标识与自定义按钮。'],
                        ['name' => 'cols.width', 'summary' => '按钮较多时建议放宽到 160 或更大。'],
                    ],
                ],
                [
                    'title' => '推荐写法',
                    'items' => [
                        ['name' => '内置按钮优先', 'summary' => '能复用 edit/delete 时，优先写字符串标识。'],
                        ['name' => '复杂下拉', 'summary' => '推荐使用 dropdown 数组，不再堆长字符串。'],
                    ],
                ],
            ],
            $sections,
            [
                ['key' => 'media.preview', 'title' => 'preview 预览列', 'status' => 'available'],
                ['key' => 'state.status', 'title' => 'status 状态标签', 'status' => 'available'],
                ['key' => 'state.switch', 'title' => 'switch 开关列', 'status' => 'available'],
            ]
        );
    }
}
