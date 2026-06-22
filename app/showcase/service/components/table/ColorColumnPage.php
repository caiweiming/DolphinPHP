<?php
declare(strict_types=1);

namespace app\showcase\service\components\table;

/**
 * color 列详情页
 */
final class ColorColumnPage extends AbstractTableColumnPage
{
    /**
     * @param array<string, mixed> $component
     * @return array<string, mixed>
     */
    public function build(array $component): array
    {
        $sourceRefs = [
            ['path' => 'app/common/render/table/color/Item.php', 'label' => 'color 列实现', 'description' => '处理颜色列模板与修复单元格行高。'],
        ];

        $sections = [
            $this->makeSection(
                'basic_color',
                '基础色值展示',
                '适合直接展示品牌色、主题色、标签色值。',
                $this->liveMain([
                    ['title', '主题名', '', [], ['minWidth' => 180]],
                    ['color_tag', '主色', 'color', [], ['width' => 140]],
                ], [
                    '_where_in' => ['id', [1, 2, 3]],
                    '_order' => ['id', 'asc'],
                    '_limit' => 3,
                ]),
                [
                    ['name' => 'type', 'value' => 'color'],
                    ['name' => '字段值', 'value' => '#0EA5E9 / rgb(...) 等'],
                ],
                [
                    '当字段本身就是颜色值时，color 列可以直接展示色块和文本。',
                ],
                <<<'CODE'
['theme_color', '主色', 'color', [], ['width' => 120]]
CODE,
                <<<'CODE'
$this->table
    ->columns([
        ['name', '主题名'],
        ['theme_color', '主色', 'color', [], ['width' => 120]],
    ]);
CODE,
                $sourceRefs
            ),
            $this->makeSection(
                'mapped_color',
                '透传 colorpicker 配置',
                'color 列的 `options` 会直接传给 `layui.colorpicker`，适合控制是否显示 alpha、预定义色板等。',
                $this->liveMain([
                    ['title', '主题名', '', [], ['minWidth' => 180]],
                    ['color_tag', '颜色', 'color', [
                        'alpha' => true,
                        'predefine' => true,
                        'format' => 'hex',
                    ], ['width' => 140]],
                ], [
                    '_where_in' => ['id', [1, 2]],
                    '_order' => ['id', 'asc'],
                    '_limit' => 2,
                ]),
                [
                    ['name' => 'options', 'value' => "['alpha' => true, 'predefine' => true, 'format' => 'hex']"],
                    ['name' => '适用', 'value' => '控制取色器行为，而不是做业务映射'],
                ],
                [
                    '当前内置 color 列不会把业务值映射成颜色，它要求字段本身就是色值。',
                    '如果需要“等级 -> 颜色”映射，应先在查询层产出真实色值字段，再交给 color 列展示。',
                ],
                <<<'CODE'
['color_tag', '颜色', 'color', ['alpha' => true, 'predefine' => true, 'format' => 'hex'], ['width' => 140]]
CODE,
                <<<'CODE'
$this->table
    ->columns([
        ['title', '主题名'],
        ['color_tag', '颜色', 'color', ['alpha' => true, 'predefine' => true, 'format' => 'hex'], ['width' => 140]],
    ]);
CODE,
                $sourceRefs
            ),
            $this->makeSection(
                'fix_cell',
                '行高修复说明',
                'color 列会自动追加 `dp-table-fix-cell` 类，避免色块类单元格行高不正确。',
                $this->infoPreview('实现特征', [
                    '编辑态 color 列会自动追加 dp-table-fix-cell，保证取色器单元格的高度稳定。',
                    '这是 color 列和普通文本列在内核处理层的一个关键差异。',
                ]),
                [
                    ['name' => '附加 class', 'value' => 'dp-table-fix-cell'],
                    ['name' => '目的', 'value' => '修复行高'],
                ],
                [
                    '这也是 color 列和普通文本列在内部实现上的一个重要区别。',
                ],
                <<<'CODE'
['color_value', '颜色', 'color']
CODE,
                <<<'CODE'
$this->table
    ->columns([
        ['color_value', '颜色', 'color'],
    ]);
CODE,
                $sourceRefs
            ),
            $this->makeSection(
                'business_snippet',
                '业务列片段',
                '适合主题管理、渠道色卡、标签颜色管理等场景。',
                $this->liveMain([
                    ['title', '标签', '', [], ['minWidth' => 180]],
                    ['color_tag', '颜色', 'color', [], ['width' => 120]],
                    ['status', '状态', 'status', ['禁用', '启用:green', '归档:red'], ['width' => 100]],
                ], [
                    '_where_in' => ['id', [1, 2, 3]],
                    '_order' => ['id', 'asc'],
                    '_limit' => 3,
                ]),
                [
                    ['name' => '组合列', 'value' => 'color + status'],
                    ['name' => '建议宽度', 'value' => '110 ~ 130'],
                ],
                [
                    '颜色列通常不需要过宽，核心是让色值和色块同时可见。',
                ],
                <<<'CODE'
[
    ['tag_name', '标签'],
    ['tag_color', '颜色', 'color', [], ['width' => 120]],
    ['status', '状态', 'status', ['禁用', '启用:green'], ['width' => 100]],
]
CODE,
                <<<'CODE'
$this->table
    ->columns([
        ['tag_name', '标签'],
        ['tag_color', '颜色', 'color', [], ['width' => 120]],
        ['status', '状态', 'status', ['禁用', '启用:green'], ['width' => 100]],
    ]);
CODE,
                $sourceRefs
            ),
        ];

        return $this->makePage(
            $component,
            [
                'summary' => 'color 列用于直接展示颜色值，或将状态映射为颜色块，适合主题色、标签色和视觉状态色管理场景。',
                'scenarios' => [
                    '主题配置、渠道色卡、标签颜色等直接展示色值的后台列表',
                    '需要开发者知道 color 列和普通文本列在内部样式上的差异',
                ],
                'capabilities' => ['色值展示', 'colorpicker 配置透传', '行高修复说明', '业务组合列'],
                'quick_start' => [
                    'array_code' => <<<'CODE'
[
    ['theme_color', '主色', 'color', [], ['width' => 120]],
]
CODE,
                    'table_code' => <<<'CODE'
$this->table
    ->columns([
        ['theme_color', '主色', 'color', [], ['width' => 120]],
    ]);
CODE,
                ],
            ],
            [
                [
                    'title' => '基础参数',
                    'items' => [
                        ['name' => 'type', 'summary' => '固定写 `color`。'],
                        ['name' => 'field', 'summary' => '字段值通常为十六进制色值或业务映射值。'],
                        ['name' => 'options', 'summary' => '会透传给 `layui.colorpicker`，用于控制取色器行为。'],
                    ],
                ],
                [
                    'title' => '实现特征',
                    'items' => [
                        ['name' => 'dp-table-fix-cell', 'summary' => '自动修复颜色单元格的行高问题。'],
                    ],
                ],
            ],
            $sections,
            [
                ['key' => 'state.status', 'title' => 'status 状态标签', 'status' => 'available'],
                ['key' => 'state.yes_no', 'title' => 'yes_no 是/否列', 'status' => 'available'],
                ['key' => 'basic.image', 'title' => 'image 图片缩略图', 'status' => 'available'],
            ]
        );
    }
}
