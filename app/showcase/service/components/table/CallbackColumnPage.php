<?php
declare(strict_types=1);

namespace app\showcase\service\components\table;

/**
 * callback 列详情页
 */
final class CallbackColumnPage extends AbstractTableColumnPage
{
    /**
     * @param array<string, mixed> $component
     * @return array<string, mixed>
     */
    public function build(array $component): array
    {
        $sourceRefs = [
            ['path' => 'app/common/render/table/callback/Item.php', 'label' => 'callback 列实现', 'description' => '处理闭包与字符串回调，并校验白名单。'],
        ];

        $sections = [
            $this->makeSection(
                'closure_callback',
                '闭包回调',
                '最直接的写法是在列定义中传入 Closure，对当前值进行格式化。',
                $this->liveMedia([
                    ['file_ext', '后缀', '', [], ['width' => 100]],
                    ['file_ext', '展示值', 'callback', ['__showcase_callback__' => 'uppercase_file_ext'], ['width' => 120]],
                ], [
                    '_where_in' => ['id', [1, 2, 3]],
                    '_order' => ['id', 'asc'],
                    '_limit' => 3,
                ]),
                [
                    ['name' => 'callback', 'value' => 'Closure'],
                    ['name' => '入参', 'value' => '$value, $data, $originalData'],
                ],
                [
                    '闭包适合当前页面内的轻量格式化逻辑，可读性最直接。',
                ],
                <<<'CODE'
['ext', '后缀', 'callback', static fn($value): string => strtoupper((string) $value)]
CODE,
                <<<'CODE'
$this->table
    ->columns([
        ['ext', '后缀', 'callback', static fn($value): string => strtoupper((string) $value)],
    ]);
CODE,
                $sourceRefs
            ),
            $this->makeSection(
                'string_callback',
                '字符串回调',
                '也可以传字符串函数名，但必须命中 callback 白名单。',
                $this->liveMedia([
                    ['asset_name', '素材名', '', [], ['minWidth' => 180]],
                    ['gallery', '图集字段', 'callback', ['__showcase_callback__' => 'gallery_to_title_rows'], ['hide' => true]],
                    ['gallery', '图片集合', 'callback', 'dp_join_array_column:title', ['minWidth' => 220]],
                ], [
                    '_where_in' => ['id', [1, 3]],
                    '_order' => ['id', 'asc'],
                    '_limit' => 2,
                ]),
                [
                    ['name' => 'callback', 'value' => 'dp_join_array_column:title'],
                    ['name' => '前提', 'value' => '命中 allowed_functions 或 allowed_patterns'],
                ],
                [
                    '字符串回调更适合复用公共函数，但要注意安全白名单。',
                ],
                <<<'CODE'
[
    ['gallery', '图集字段', 'callback', static function ($value): array {
        $items = array_filter(array_map('trim', explode(',', (string) $value)));
        return array_map(static fn(string $item): array => ['title' => basename($item)], $items);
    }, ['hide' => true]],
    ['gallery', '图片集合', 'callback', 'dp_join_array_column:title'],
]
CODE,
                <<<'CODE'
$this->table
    ->columns([
        ['gallery', '图集字段', 'callback', static function ($value): array {
            $items = array_filter(array_map('trim', explode(',', (string) $value)));
            return array_map(static fn(string $item): array => ['title' => basename($item)], $items);
        }, ['hide' => true]],
        ['gallery', '图片集合', 'callback', 'dp_join_array_column:title'],
    ]);
CODE,
                $sourceRefs
            ),
            $this->makeSection(
                'security_boundary',
                '白名单与安全边界',
                '未命中白名单或不可调用的函数会被阻止，并回退原值。',
                $this->infoPreview('安全边界', [
                    '字符串回调必须命中 callback_allowed_functions 或 callback_allowed_patterns。',
                    '未命中白名单或函数不可调用时，会写 warning 日志并回退原值，不会任意执行函数。',
                ]),
                [
                    ['name' => '配置入口', 'value' => 'table.security.callback_allowed_*'],
                    ['name' => '失败回退', 'value' => '返回原始值'],
                ],
                [
                    'callback 列不是任意函数执行入口，showcase 必须把这个边界说明清楚。',
                ],
                <<<'CODE'
['roles', '角色集合', 'callback', 'dp_join_array_column:title']
CODE,
                <<<'CODE'
$this->table
    ->columns([
        ['roles', '角色集合', 'callback', 'dp_join_array_column:title'],
    ]);

// 注意：自定义函数需要先加入 table.security.callback_allowed_functions
CODE,
                $sourceRefs
            ),
            $this->makeSection(
                'business_snippet',
                '业务列片段',
                'callback 常用于“查询值不适合直接显示”的场景。',
                $this->liveMain([
                    ['title', '专题', '', [], ['minWidth' => 180]],
                    ['score', '评分展示', 'callback', ['__showcase_callback__' => 'score_label'], ['minWidth' => 160]],
                ], [
                    '_where_in' => ['id', [1, 2, 3]],
                    '_order' => ['id', 'asc'],
                    '_limit' => 3,
                ]),
                [
                    ['name' => '适用', 'value' => '价格格式化、数组拼接、复杂文案'],
                    ['name' => '不适用', 'value' => '大段业务逻辑'],
                ],
                [
                    '如果回调逻辑已经变得很长，应考虑改成查询层预处理或自定义列类型。',
                ],
                <<<'CODE'
['price', '价格展示', 'callback', static fn($value): string => '￥ ' . number_format((float) $value, 2) . ' / 月']
CODE,
                <<<'CODE'
$this->table
    ->columns([
        ['name', '商品'],
        ['price', '价格展示', 'callback', static fn($value): string => '￥ ' . number_format((float) $value, 2) . ' / 月'],
    ]);
CODE,
                $sourceRefs
            ),
        ];

        return $this->makePage(
            $component,
            [
                'summary' => 'callback 列用于通过回调函数或自定义逻辑生成复杂展示结果，适合格式化、数组拼接和轻量业务文案场景。',
                'scenarios' => [
                    '数组字段拼接、金额格式化、扩展名格式化等不适合直接输出原值的场景',
                    '开发者需要同时了解 callback 的能力和安全白名单边界',
                ],
                'capabilities' => ['闭包回调', '字符串回调', '白名单边界', '业务格式化片段'],
                'quick_start' => [
                    'array_code' => <<<'CODE'
[
    ['ext', '后缀', 'callback', static fn($value): string => strtoupper((string) $value)],
]
CODE,
                    'table_code' => <<<'CODE'
$this->table
    ->columns([
        ['ext', '后缀', 'callback', static fn($value): string => strtoupper((string) $value)],
    ]);
CODE,
                ],
            ],
            [
                [
                    'title' => '基础参数',
                    'items' => [
                        ['name' => 'type', 'summary' => '固定写 `callback`。'],
                        ['name' => 'options / callback', 'summary' => '可传 Closure 或字符串函数名。'],
                    ],
                ],
                [
                    'title' => '安全边界',
                    'items' => [
                        ['name' => 'allowed_functions', 'summary' => '显式允许的函数白名单。'],
                        ['name' => 'allowed_patterns', 'summary' => '允许的通配模式，如 `dp_*`。'],
                    ],
                ],
            ],
            $sections,
            [
                ['key' => 'interactive.url', 'title' => 'url 链接列', 'status' => 'available'],
                ['key' => 'interactive.actions', 'title' => 'actions 操作列', 'status' => 'available'],
                ['key' => 'basic.datetime', 'title' => 'datetime 时间列', 'status' => 'available'],
            ]
        );
    }
}
