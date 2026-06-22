<?php
declare(strict_types=1);

namespace app\showcase\service\components\table;

/**
 * yes_no 列详情页
 */
final class YesNoColumnPage extends AbstractTableColumnPage
{
    /**
     * @param array<string, mixed> $component
     * @return array<string, mixed>
     */
    public function build(array $component): array
    {
        $sourceRefs = [
            ['path' => 'app/common/render/table/yes_no/Item.php', 'label' => 'yes_no 列实现', 'description' => '负责渲染是/否模板。'],
        ];

        $sections = [
            $this->makeSection(
                'basic_yes_no',
                '基础是/否展示',
                '最常见场景是把 0/1 或 false/true 映射成是/否。',
                $this->liveMain([
                    ['owner_name', '用户名', '', [], ['minWidth' => 120]],
                    ['is_system', '是否管理员', 'yes_no', [], ['width' => 110]],
                ], [
                    '_where_in' => ['id', [1, 2]],
                    '_order' => ['id', 'asc'],
                    '_limit' => 2,
                ]),
                [
                    ['name' => 'type', 'value' => 'yes_no'],
                    ['name' => '默认语义', 'value' => '0 => 否，1 => 是'],
                ],
                [
                    'yes_no 适合轻量布尔状态，不需要额外颜色和交互时优先用它。',
                ],
                <<<'CODE'
['is_admin', '是否管理员', 'yes_no', [], ['width' => 110]]
CODE,
                <<<'CODE'
$this->table
    ->columns([
        ['username', '用户名'],
        ['is_admin', '是否管理员', 'yes_no', [], ['width' => 110]],
    ]);
CODE,
                $sourceRefs
            ),
            $this->makeSection(
                'business_copy',
                '业务文案映射',
                '如果业务语义不是“是/否”，建议直接在表头或邻近列写清楚。',
                $this->liveMain([
                    ['title', '专题', '', [], ['minWidth' => 180]],
                    ['is_system', '首页推荐', 'yes_no', [], ['width' => 110]],
                ], [
                    '_where_in' => ['id', [1, 2]],
                    '_order' => ['id', 'asc'],
                    '_limit' => 2,
                ]),
                [
                    ['name' => '推荐场景', 'value' => '是否推荐、是否展示、是否锁定'],
                    ['name' => '替代方案', 'value' => '需要颜色时改用 status'],
                ],
                [
                    'yes_no 自身不承载复杂视觉语义，需要颜色提示时应切换到 status。',
                ],
                <<<'CODE'
['is_recommend', '首页推荐', 'yes_no', [], ['width' => 110]]
CODE,
                <<<'CODE'
$this->table
    ->columns([
        ['topic_name', '专题'],
        ['is_recommend', '首页推荐', 'yes_no', [], ['width' => 110]],
    ]);
CODE,
                $sourceRefs
            ),
            $this->makeSection(
                'with_status',
                '与 status 的选型区别',
                '是/否列强调轻量布尔值，status 更适合多状态和彩色标签。',
                $this->liveMain([
                    ['title', '内容项', '', [], ['minWidth' => 180]],
                    ['is_enabled', '是否展示', 'yes_no', [], ['width' => 110]],
                    ['status', '审核状态', 'status', ['待审核', '已通过:green', '已驳回:red'], ['width' => 120]],
                ], [
                    '_where_in' => ['id', [1, 2, 3]],
                    '_order' => ['id', 'asc'],
                    '_limit' => 3,
                ]),
                [
                    ['name' => '轻量布尔值', 'value' => 'yes_no'],
                    ['name' => '多状态值', 'value' => 'status'],
                ],
                [
                    '开发者最容易混淆 yes_no 和 status，这里应明确使用边界。',
                ],
                <<<'CODE'
['is_show', '是否展示', 'yes_no']
CODE,
                <<<'CODE'
$this->table
    ->columns([
        ['is_show', '是否展示', 'yes_no'],
    ]);
CODE,
                $sourceRefs
            ),
            $this->makeSection(
                'business_snippet',
                '业务列片段',
                '常和名称列、状态列一起出现，用于快速扫读布尔判断。',
                $this->liveMain([
                    ['category', '栏目', '', [], ['minWidth' => 140]],
                    ['is_system', '导航显示', 'yes_no', [], ['width' => 100]],
                    ['status', '状态', 'status', ['禁用', '启用:green', '归档:red'], ['width' => 100]],
                ], [
                    '_where_in' => ['id', [1, 2, 3]],
                    '_order' => ['id', 'asc'],
                    '_limit' => 3,
                ]),
                [
                    ['name' => '组合列', 'value' => 'yes_no + status'],
                    ['name' => '建议宽度', 'value' => '100 ~ 110'],
                ],
                [
                    '如果列表里同屏出现多个布尔字段，优先压缩宽度，避免稀疏布局。',
                ],
                <<<'CODE'
[
    ['name', '栏目'],
    ['is_nav', '导航显示', 'yes_no', [], ['width' => 100]],
    ['status', '状态', 'status', ['禁用', '启用:green'], ['width' => 100]],
]
CODE,
                <<<'CODE'
$this->table
    ->columns([
        ['name', '栏目'],
        ['is_nav', '导航显示', 'yes_no', [], ['width' => 100]],
        ['status', '状态', 'status', ['禁用', '启用:green'], ['width' => 100]],
    ]);
CODE,
                $sourceRefs
            ),
        ];

        return $this->makePage(
            $component,
            [
                'summary' => 'yes_no 列用于将布尔值或枚举值映射为是/否展示，适合轻量状态、权限开关和只读判断字段。',
                'scenarios' => [
                    '是否管理员、是否推荐、是否审核通过等轻量布尔状态列表',
                    '不需要颜色标签，只想让开发者快速扫读是/否结果的场景',
                ],
                'capabilities' => ['基础是/否显示', '业务文案场景', '与 status 选型对比', '业务组合列'],
                'quick_start' => [
                    'array_code' => <<<'CODE'
[
    ['is_admin', '是否管理员', 'yes_no', [], ['width' => 110]],
]
CODE,
                    'table_code' => <<<'CODE'
$this->table
    ->columns([
        ['is_admin', '是否管理员', 'yes_no', [], ['width' => 110]],
    ]);
CODE,
                ],
            ],
            [
                [
                    'title' => '基础参数',
                    'items' => [
                        ['name' => 'type', 'summary' => '固定写 `yes_no`。'],
                        ['name' => 'field / title', 'summary' => '字段名与表头标题。'],
                        ['name' => 'cols.width', 'summary' => '一般 100 到 110 即可。'],
                    ],
                ],
                [
                    'title' => '选型建议',
                    'items' => [
                        ['name' => 'yes_no', 'summary' => '适合简单布尔判断。'],
                        ['name' => 'status', 'summary' => '适合多状态或需要颜色区分的场景。'],
                    ],
                ],
            ],
            $sections,
            // makePage() 会统一生成 sidebar_source_refs 和 source_refs 供详情页展示源码入口
            [
                ['key' => 'state.status', 'title' => 'status 状态标签', 'status' => 'available'],
                ['key' => 'state.switch', 'title' => 'switch 开关列', 'status' => 'available'],
                ['key' => 'state.color', 'title' => 'color 颜色列', 'status' => 'available'],
            ]
        );
    }
}
