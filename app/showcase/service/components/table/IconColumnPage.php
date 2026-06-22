<?php
declare(strict_types=1);

namespace app\showcase\service\components\table;

/**
 * icon 列详情页
 */
final class IconColumnPage extends AbstractTableColumnPage
{
    /**
     * @param array<string, mixed> $component
     * @return array<string, mixed>
     */
    public function build(array $component): array
    {
        $sourceRefs = [
            ['path' => 'app/common/render/table/icon/Item.php', 'label' => 'icon 列实现', 'description' => '处理 style 透传和模板注入。'],
        ];

        $sections = [
            $this->makeSection(
                'basic_icon',
                '基础图标列',
                '最直接的写法是让字段本身保存图标 class，例如 `ti ti-home`。',
                $this->liveMedia([
                    ['asset_name', '素材名', '', [], ['minWidth' => 180]],
                    ['icon_class', '图标', 'icon', [], ['width' => 110]],
                ], [
                    '_where_in' => ['id', [1, 2, 3]],
                    '_order' => ['id', 'asc'],
                    '_limit' => 3,
                ]),
                [
                    ['name' => 'type', 'value' => 'icon'],
                    ['name' => '字段值', 'value' => 'ti ti-home 这类 class'],
                ],
                [
                    'icon 列本身不负责选择图标，只负责把字段值渲染成 `<i class=\"...\">`。',
                ],
                <<<'CODE'
['icon_class', '图标', 'icon', [], ['width' => 110]]
CODE,
                <<<'CODE'
$this->table
    ->columns([
        ['channel_name', '渠道'],
        ['icon_class', '图标', 'icon', [], ['width' => 110]],
    ]);
CODE,
                $sourceRefs
            ),
            $this->makeSection(
                'style_array',
                'style 数组写法',
                '如果需要统一控制图标尺寸或颜色，可以传 style 数组。',
                $this->liveMedia([
                    ['file_name', '文件名', '', [], ['minWidth' => 180]],
                    ['icon_class', '图标', 'icon', ['color' => '#10b981', 'font-size' => '18px'], ['width' => 100]],
                ], [
                    '_where_in' => ['id', [1, 2]],
                    '_order' => ['id', 'asc'],
                    '_limit' => 2,
                ]),
                [
                    ['name' => 'style', 'value' => "['color' => '#10b981', 'font-size' => '18px']"],
                    ['name' => '行为', 'value' => '内部会拼成 style 字符串'],
                ],
                [
                    '数组 style 会被自动转换成内联样式字符串，适合统一尺寸和颜色。',
                ],
                <<<'CODE'
['status_icon', '图标', 'icon', ['color' => '#10b981', 'font-size' => '18px'], ['width' => 100]]
CODE,
                <<<'CODE'
$this->table
    ->columns([
        ['status_name', '状态'],
        ['status_icon', '图标', 'icon', ['color' => '#10b981', 'font-size' => '18px'], ['width' => 100]],
    ]);
CODE,
                $sourceRefs
            ),
            $this->makeSection(
                'style_string',
                'style 字符串写法',
                '如果已有样式字符串，也可以直接传入，不必改成数组。',
                $this->liveMedia([
                    ['asset_name', '平台', '', [], ['minWidth' => 180]],
                    ['icon_class', '图标', 'icon', 'color:#16a34a;font-size:20px', ['width' => 100]],
                ], [
                    '_where_in' => ['id', [2]],
                    '_limit' => 1,
                ]),
                [
                    ['name' => 'options', 'value' => 'color:#16a34a;font-size:20px'],
                    ['name' => '兼容', 'value' => 'style 或 options 都可承接'],
                ],
                [
                    '旧代码里如果已经是字符串样式，可以直接复用，不需要重构。',
                ],
                <<<'CODE'
['platform_icon', '图标', 'icon', 'color:#16a34a;font-size:20px']
CODE,
                <<<'CODE'
$this->table
    ->columns([
        ['platform_icon', '图标', 'icon', 'color:#16a34a;font-size:20px'],
    ]);
CODE,
                $sourceRefs
            ),
            $this->makeSection(
                'business_snippet',
                '业务列片段',
                '适合平台管理、导航配置、状态视觉提示等轻量场景。',
                $this->liveMedia([
                    ['asset_name', '模块', '', [], ['minWidth' => 160]],
                    ['icon_class', '图标', 'icon', ['font-size' => '18px'], ['width' => 96]],
                    ['status', '状态', 'status', ['禁用', '启用:green', '归档:red'], ['width' => 100]],
                ], [
                    '_where_in' => ['id', [1, 2, 3]],
                    '_order' => ['id', 'asc'],
                    '_limit' => 3,
                ]),
                [
                    ['name' => '组合列', 'value' => 'icon + status'],
                    ['name' => '建议宽度', 'value' => '90 ~ 110'],
                ],
                [
                    '图标列通常不需要太宽，重点是保证 class 值和视觉语义一致。',
                ],
                <<<'CODE'
[
    ['name', '模块'],
    ['icon_class', '图标', 'icon', ['font-size' => '18px'], ['width' => 96]],
    ['status', '状态', 'status', ['禁用', '启用:green'], ['width' => 100]],
]
CODE,
                <<<'CODE'
$this->table
    ->columns([
        ['name', '模块'],
        ['icon_class', '图标', 'icon', ['font-size' => '18px'], ['width' => 96]],
        ['status', '状态', 'status', ['禁用', '启用:green'], ['width' => 100]],
    ]);
CODE,
                $sourceRefs
            ),
        ];

        return $this->makePage(
            $component,
            [
                'summary' => 'icon 列用于渲染图标类字段，适合状态图标、平台图标和轻量视觉提示。',
                'scenarios' => [
                    '导航管理、平台管理、渠道列表等需要轻量图标提示的后台表格',
                    '开发者需要知道图标 class 如何配，以及 style 如何透传的场景',
                ],
                'capabilities' => ['基础图标列', 'style 数组写法', 'style 字符串写法', '业务组合列'],
                'quick_start' => [
                    'array_code' => <<<'CODE'
[
    ['icon_class', '图标', 'icon', [], ['width' => 110]],
]
CODE,
                    'table_code' => <<<'CODE'
$this->table
    ->columns([
        ['icon_class', '图标', 'icon', [], ['width' => 110]],
    ]);
CODE,
                ],
            ],
            [
                [
                    'title' => '基础参数',
                    'items' => [
                        ['name' => 'type', 'summary' => '固定写 `icon`。'],
                        ['name' => 'field', 'summary' => '字段值一般是图标 class。'],
                        ['name' => 'style / options', 'summary' => '可传字符串或数组，用于控制图标样式。'],
                    ],
                ],
            ],
            $sections,
            // makePage() 会统一生成 sidebar_source_refs 和 source_refs 供详情页展示源码入口
            [
                ['key' => 'state.status', 'title' => 'status 状态标签', 'status' => 'available'],
                ['key' => 'basic.image', 'title' => 'image 图片缩略图', 'status' => 'available'],
                ['key' => 'interactive.url', 'title' => 'url 链接列', 'status' => 'available'],
            ]
        );
    }
}
