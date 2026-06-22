<?php
declare(strict_types=1);

namespace app\showcase\service\components\table;

/**
 * url 列详情页
 */
final class UrlColumnPage extends AbstractTableColumnPage
{
    /**
     * @param array<string, mixed> $component
     * @return array<string, mixed>
     */
    public function build(array $component): array
    {
        $sourceRefs = [
            ['path' => 'app/common/render/table/url/Item.php', 'label' => 'url 列实现', 'description' => '处理 href 占位符替换、target 与弹窗配置。'],
        ];

        $sections = [
            $this->makeSection(
                'basic_link',
                '基础链接列',
                '将字段值渲染为普通可点击链接，是最常见的用法。',
                $this->liveMain([
                    ['title', '站点名', '', [], ['minWidth' => 160]],
                    ['code', '访问地址', 'url', ['href' => 'https://www.dolphinphp.com/docs/__code__'], ['minWidth' => 220]],
                ], [
                    '_where_in' => ['id', [1]],
                    '_limit' => 1,
                ]),
                [
                    ['name' => 'type', 'value' => 'url'],
                    ['name' => 'target', 'value' => '_blank'],
                ],
                [
                    '不传 target 时，默认也是 `_blank`。',
                ],
                <<<'CODE'
['website', '访问地址', 'url']
CODE,
                <<<'CODE'
$this->table
    ->columns([
        ['website', '访问地址', 'url'],
    ]);
CODE,
                $sourceRefs
            ),
            $this->makeSection(
                'custom_href',
                '自定义 href 模板',
                '通过 `__field__` 占位符将当前行字段拼接到目标链接里。',
                $this->liveMain([
                    ['title', '文章标题', 'url', ['href' => '/showcase/article/__id__', 'target' => '_blank'], ['minWidth' => 200]],
                ], [
                    '_where_in' => ['id', [2]],
                    '_limit' => 1,
                ]),
                [
                    ['name' => 'options.href', 'value' => '/article/__id__'],
                    ['name' => '占位符', 'value' => '__字段名__'],
                ],
                [
                    '内部会把 `__id__` 这类占位符替换成模板里的当前行字段值。',
                ],
                <<<'CODE'
['title', '查看详情', 'url', ['href' => '/article/__id__']]
CODE,
                <<<'CODE'
$this->table
    ->columns([
        ['title', '查看详情', 'url', ['href' => '/article/__id__']],
    ]);
CODE,
                $sourceRefs
            ),
            $this->makeSection(
                'dialog_open',
                '弹窗打开方式',
                'url 列也支持配置弹窗打开，而不一定是新窗口跳转。',
                $this->liveMain([
                    ['code', '订单号', '', [], ['minWidth' => 140]],
                    ['title', '详情', 'url', ['href' => '/showcase/order/detail/__id__', 'pop' => '订单详情'], ['minWidth' => 180]],
                ], [
                    '_where_in' => ['id', [1]],
                    '_limit' => 1,
                ]),
                [
                    ['name' => 'options.pop', 'value' => '订单详情'],
                    ['name' => '弹窗类型', 'value' => 'true / string / array'],
                ],
                [
                    'pop 为字符串时会作为弹窗标题；为 true 时使用默认 type=2。',
                ],
                <<<'CODE'
['order_no', '详情', 'url', ['href' => '/order/detail/__id__', 'pop' => '订单详情']]
CODE,
                <<<'CODE'
$this->table
    ->columns([
        ['order_no', '详情', 'url', [
            'href' => '/order/detail/__id__',
            'pop' => '订单详情',
        ]],
    ]);
CODE,
                $sourceRefs
            ),
            $this->makeSection(
                'business_snippet',
                '业务列片段',
                '常和名称列、状态列一起使用，充当详情入口或外链入口。',
                $this->liveMedia([
                    ['asset_name', '名称', '', [], ['minWidth' => 180]],
                    ['preview_url', '预览链接', 'url', ['target' => '_blank'], ['minWidth' => 180]],
                    ['status', '状态', 'status', ['禁用', '启用:green', '归档:red'], ['width' => 100]],
                ], [
                    '_where_in' => ['id', [1, 2, 3]],
                    '_order' => ['id', 'asc'],
                    '_limit' => 3,
                ]),
                [
                    ['name' => '组合列', 'value' => 'url + status'],
                    ['name' => '常见目标', 'value' => '详情页 / 预览页 / 外链'],
                ],
                [
                    '如果这个入口承担的是“操作”而不是“纯展示”，也可以考虑放到 actions 列中。',
                ],
                <<<'CODE'
[
    ['title', '名称'],
    ['preview_url', '预览链接', 'url', ['target' => '_blank']],
    ['status', '状态', 'status', ['禁用', '启用:green'], ['width' => 100]],
]
CODE,
                <<<'CODE'
$this->table
    ->columns([
        ['title', '名称'],
        ['preview_url', '预览链接', 'url', ['target' => '_blank']],
        ['status', '状态', 'status', ['禁用', '启用:green'], ['width' => 100]],
    ]);
CODE,
                $sourceRefs
            ),
        ];

        return $this->makePage(
            $component,
            [
                'summary' => 'url 列用于将字段值渲染为可跳转链接或外部地址，适合详情入口、预览页和外链场景。',
                'scenarios' => [
                    '列表里提供详情页、预览页或官网地址等跳转入口',
                    '需要通过 `__field__` 占位符拼接目标地址的表格页面',
                ],
                'capabilities' => ['基础链接', 'href 模板占位符', '弹窗打开', '业务组合列'],
                'quick_start' => [
                    'array_code' => <<<'CODE'
[
    ['website', '访问地址', 'url'],
]
CODE,
                    'table_code' => <<<'CODE'
$this->table
    ->columns([
        ['website', '访问地址', 'url'],
    ]);
CODE,
                ],
            ],
            [
                [
                    'title' => '基础参数',
                    'items' => [
                        ['name' => 'type', 'summary' => '固定写 `url`。'],
                        ['name' => 'options.href', 'summary' => '自定义跳转地址模板，支持 `__field__` 占位符。'],
                        ['name' => 'options.target', 'summary' => '打开方式，默认 `_blank`。'],
                        ['name' => 'options.pop', 'summary' => '配置弹窗打开。'],
                    ],
                ],
            ],
            $sections,
            [
                ['key' => 'interactive.actions', 'title' => 'actions 操作列', 'status' => 'available'],
                ['key' => 'media.preview', 'title' => 'preview 预览列', 'status' => 'available'],
                ['key' => 'state.status', 'title' => 'status 状态标签', 'status' => 'available'],
            ]
        );
    }
}
