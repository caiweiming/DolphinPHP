<?php
declare(strict_types=1);

namespace app\showcase\service\components\chart\chart_builder;

/**
 * Showcase 图表构建器方法板块组装器
 */
final class ChartBuilderMethodSectionBuilder
{
    /**
     * @return array<string, mixed>
     */
    public function build(string $methodKey): array
    {
        return match ($methodKey) {
            'title' => $this->title(),
            'subtitle' => $this->subtitle(),
            'height' => $this->height(),
            'minHeight' => $this->minHeight(),
            'renderer' => $this->renderer(),
            'theme' => $this->theme(),
            'type' => $this->type(),
            'categories' => $this->categories(),
            'series' => $this->series(),
            'dataset' => $this->dataset(),
            'datasetMerge' => $this->datasetMerge(),
            'loading' => $this->loading(),
            'empty' => $this->empty(),
            'option' => $this->option(),
            'map' => $this->map(),
            'mapData' => $this->mapData(),
            default => [],
        };
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function wrap(array $payload): array
    {
        return array_merge([
            'key' => '',
            'group_key' => '',
            'title' => '',
            'signature' => '',
            'summary' => '',
            'parameter_details' => [],
            'array_code' => '',
            'chart_code' => '',
            'usage_variants' => [],
            'behavior_notes' => [],
            'tips' => [],
            'example_mode' => 'source',
            'preview_html' => '',
            'source_refs' => [
                [
                    'path' => 'app/common/render/Chart.php',
                    'label' => 'Chart 构建器源码',
                    'description' => '方法签名与行为均来自该类。',
                ],
            ],
        ], $payload);
    }

    /**
     * @param string $key
     * @param string $groupKey
     * @param string $title
     * @param string $signature
     * @param string $summary
     * @param array<int, array{name:string, summary:string}> $parameters
     * @param string $arrayCode
     * @param string $chartCode
     * @param array<int, string> $notes
     * @param array<int, string> $tips
     * @param array<int, array<string, string>> $variants
     * @param string $exampleMode
     * @param string $previewHtml
     * @return array<string, mixed>
     */
    private function section(
        string $key,
        string $groupKey,
        string $title,
        string $signature,
        string $summary,
        array $parameters,
        string $arrayCode,
        string $chartCode,
        array $notes,
        array $tips,
        array $variants = [],
        string $exampleMode = 'source',
        string $previewHtml = ''
    ): array {
        return $this->wrap([
            'key' => $key,
            'group_key' => $groupKey,
            'title' => $title,
            'signature' => $signature,
            'summary' => $summary,
            'parameter_details' => $parameters,
            'array_code' => $arrayCode,
            'chart_code' => $chartCode,
            'behavior_notes' => $notes,
            'tips' => $tips,
            'usage_variants' => $variants,
            'example_mode' => $exampleMode,
            'preview_html' => $previewHtml,
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function variant(string $title, string $summary, string $arrayCode, string $chartCode): array
    {
        return [
            'title' => $title,
            'summary' => $summary,
            'array_code' => $arrayCode,
            'chart_code' => $chartCode,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function title(): array
    {
        return $this->section(
            'title',
            'basic',
            'title() 标题与副标题',
            "title(string \$value = '', string \$subtitle = ''): static",
            '一次性设置图表主标题和副标题，适合看板首屏和统计卡片的标题区。',
            [
                ['name' => '$value', 'summary' => '主标题文本。'],
                ['name' => '$subtitle', 'summary' => '副标题文本，可选。'],
            ],
            <<<'CODE'
[
    'title' => ['销售趋势', '最近 30 天'],
]
CODE,
            <<<'CODE'
use app\common\render\Chart;

Chart::make('sales_trend')
    ->title('销售趋势', '最近 30 天');
CODE,
            [
                'title() 适合主副标题一起配置，能减少标题区零散调用。',
                '如果页面本身已有大标题，图表副标题通常更适合放时间范围或统计口径。',
            ],
            [
                '需要只改副标题时，再使用 subtitle()，不要重复覆盖完整标题。',
            ],
            [
                $this->variant(
                    '仅主标题',
                    '适合卡片空间较紧凑的简单场景。',
                    "[\n    'title' => ['访问趋势'],\n]",
                    "use app\\common\\render\\Chart;\n\nChart::make('visit_trend')\n    ->title('访问趋势');"
                ),
                $this->variant(
                    '主副标题',
                    '适合需要同时说明主题和统计口径。',
                    "[\n    'title' => ['销售趋势', '最近 30 天'],\n]",
                    "use app\\common\\render\\Chart;\n\nChart::make('sales_trend')\n    ->title('销售趋势', '最近 30 天');"
                ),
            ],
            'preview',
            '<div class="card"><div class="card-header"><div><h3 class="card-title">销售趋势</h3><div class="card-subtitle text-secondary">最近 30 天</div></div></div></div>'
        );
    }

    private function subtitle(): array
    {
        return $this->section(
            'subtitle',
            'basic',
            'subtitle() 单独副标题',
            "subtitle(string \$value = ''): static",
            '在主标题已确定的前提下，单独补充统计口径、时间范围或解释说明。',
            [
                ['name' => '$value', 'summary' => '副标题文本。'],
            ],
            <<<'CODE'
[
    'subtitle' => '最近 7 天实时更新',
]
CODE,
            <<<'CODE'
use app\common\render\Chart;

Chart::make('visit_trend')
    ->title('访问趋势')
    ->subtitle('最近 7 天实时更新');
CODE,
            [
                'subtitle() 适合在已有主标题的情况下追加说明，不会影响标题主体。',
            ],
            [
                '副标题适合放统计维度或更新时间，不建议塞过长的业务说明。',
            ],
            [
                $this->variant(
                    '标题后单独补副标题',
                    '最常见链式写法。',
                    "[\n    'subtitle' => '最近 7 天实时更新',\n]",
                    "use app\\common\\render\\Chart;\n\nChart::make('visit_trend')\n    ->title('访问趋势')\n    ->subtitle('最近 7 天实时更新');"
                ),
            ]
        );
    }

    private function height(): array
    {
        return $this->section(
            'height',
            'basic',
            'height() 高度',
            "height(string|int \$height): static",
            '控制图表容器高度，适合按页面布局密度调整图表纵向空间。',
            [
                ['name' => '$height', 'summary' => '支持整数像素值或带单位字符串，如 360、"420px"、"50vh"。'],
            ],
            <<<'CODE'
[
    'height' => 360,
]
CODE,
            <<<'CODE'
use app\common\render\Chart;

Chart::make('sales_trend')
    ->height(360);
CODE,
            [
                'height() 会直接影响图表可读性，折线图和柱状图一般不要过矮。',
            ],
            [
                '如果图表放在网格卡片里，建议明确指定高度，避免不同卡片高度抖动。',
            ],
            [
                $this->variant(
                    '整数像素值',
                    '后台最常见的固定高度写法。',
                    "[\n    'height' => 360,\n]",
                    "use app\\common\\render\\Chart;\n\nChart::make('sales_trend')\n    ->height(360);"
                ),
                $this->variant(
                    '带单位字符串',
                    '适合响应式或视口相关布局。',
                    "[\n    'height' => '50vh',\n]",
                    "use app\\common\\render\\Chart;\n\nChart::make('sales_trend')\n    ->height('50vh');"
                ),
            ]
        );
    }

    private function minHeight(): array
    {
        return $this->section(
            'minHeight',
            'basic',
            'minHeight() 最小高度',
            "minHeight(string|int \$height): static",
            '为图表设置最小高度，避免在复杂布局或窄屏下被压扁。',
            [
                ['name' => '$height', 'summary' => '支持整数像素值或带单位字符串。'],
            ],
            <<<'CODE'
[
    'min_height' => 240,
]
CODE,
            <<<'CODE'
use app\common\render\Chart;

Chart::make('sales_trend')
    ->minHeight(240);
CODE,
            [
                'minHeight() 更像兜底能力，常用于多图表自适应布局时保持最低可读性。',
            ],
            [
                '如果已经固定了较大的 height，minHeight 一般保持默认即可。',
            ]
        );
    }

    private function renderer(): array
    {
        return $this->section(
            'renderer',
            'basic',
            'renderer() 渲染器',
            "renderer(string \$renderer = 'canvas'): static",
            '切换 ECharts 的渲染器类型，当前支持 canvas 与 svg。',
            [
                ['name' => '$renderer', 'summary' => '可选值为 canvas 或 svg。'],
            ],
            <<<'CODE'
[
    'renderer' => 'svg',
]
CODE,
            <<<'CODE'
use app\common\render\Chart;

Chart::make('trend_svg')
    ->renderer('svg');
CODE,
            [
                '多数场景下 canvas 就足够，svg 更适合需要导出清晰矢量效果或点位较少的场景。',
            ],
            [
                'showcase 里重点是让开发者知道有这个入口，不必在每个图表都主动改 renderer。',
            ],
            [
                $this->variant(
                    'Canvas 默认渲染',
                    '性能优先的常规后台场景。',
                    "[\n    'renderer' => 'canvas',\n]",
                    "use app\\common\\render\\Chart;\n\nChart::make('trend_canvas')\n    ->renderer('canvas');"
                ),
                $this->variant(
                    'SVG 渲染',
                    '适合点少、要求清晰度更高的图表。',
                    "[\n    'renderer' => 'svg',\n]",
                    "use app\\common\\render\\Chart;\n\nChart::make('trend_svg')\n    ->renderer('svg');"
                ),
            ]
        );
    }

    private function theme(): array
    {
        return $this->section(
            'theme',
            'basic',
            'theme() 主题',
            "theme(string \$theme = ''): static",
            '设置图表主题名，用于接入项目级的 ECharts 主题风格。',
            [
                ['name' => '$theme', 'summary' => '主题名称，应与前端已注册的 ECharts 主题一致。'],
            ],
            <<<'CODE'
[
    'theme' => 'vintage',
]
CODE,
            <<<'CODE'
use app\common\render\Chart;

Chart::make('sales_trend')
    ->theme('vintage');
CODE,
            [
                'theme() 只是声明图表使用哪个主题，前提仍然是浏览器端已注册该主题。',
            ],
            [
                'showcase 不会内置第三方主题资源，示例主要用于告诉开发者怎么写。',
            ]
        );
    }

    private function type(): array
    {
        return $this->section(
            'type',
            'data',
            'type() 图表类型',
            "type(string \$type = 'line'): static",
            '切换图表类型，是所有图表配置的起点。',
            [
                ['name' => '$type', 'summary' => '内置支持 line、bar、pie、scatter，也可传已注册的扩展类型。'],
            ],
            <<<'CODE'
[
    'type' => 'line',
]
CODE,
            <<<'CODE'
use app\common\render\Chart;

Chart::make('visit_trend')
    ->type('line');
CODE,
            [
                'type() 决定后续 categories、series 将以哪种图表语义被编译成 option。',
                '内置四种基础图表已经覆盖大多数后台场景，不够时再走扩展类型。',
            ],
            [
                '如果开发者只想知道支持哪些类型，先看图表专题首页四个基础组件即可。',
            ],
            [
                $this->variant(
                    '折线图',
                    '趋势类最常见写法。',
                    "[\n    'type' => 'line',\n]",
                    "use app\\common\\render\\Chart;\n\nChart::make('visit_trend')\n    ->type('line');"
                ),
                $this->variant(
                    '柱状图',
                    '类目对比最常见写法。',
                    "[\n    'type' => 'bar',\n]",
                    "use app\\common\\render\\Chart;\n\nChart::make('sales_bar')\n    ->type('bar');"
                ),
                $this->variant(
                    '饼图',
                    '构成占比最常见写法。',
                    "[\n    'type' => 'pie',\n]",
                    "use app\\common\\render\\Chart;\n\nChart::make('source_pie')\n    ->type('pie');"
                ),
            ]
        );
    }

    private function categories(): array
    {
        return $this->section(
            'categories',
            'data',
            'categories() 类目轴',
            "categories(array \$categories = []): static",
            '为 line/bar 等类目型图表设置 X 轴类目数据。',
            [
                ['name' => '$categories', 'summary' => '类目数组，如日期、月份、渠道名称、地区名称等。'],
            ],
            <<<'CODE'
[
    'categories' => ['周一', '周二', '周三'],
]
CODE,
            <<<'CODE'
use app\common\render\Chart;

Chart::make('visit_trend')
    ->categories(['周一', '周二', '周三']);
CODE,
            [
                'categories() 主要服务于 line/bar，pie/scatter 通常不依赖这个方法。',
            ],
            [
                '类目数量过多时应考虑旋转标签、分页或改用横向条形图，而不是一味堆满 X 轴。',
            ],
            [
                $this->variant(
                    '日期类目',
                    '最常见趋势图写法。',
                    "[\n    'categories' => ['周一', '周二', '周三'],\n]",
                    "use app\\common\\render\\Chart;\n\nChart::make('visit_trend')\n    ->categories(['周一', '周二', '周三']);"
                ),
                $this->variant(
                    '业务类目',
                    '适合柱状图或排行图。',
                    "[\n    'categories' => ['华东', '华南', '华北'],\n]",
                    "use app\\common\\render\\Chart;\n\nChart::make('region_bar')\n    ->categories(['华东', '华南', '华北']);"
                ),
            ]
        );
    }

    private function series(): array
    {
        return $this->section(
            'series',
            'data',
            'series() 系列数据',
            "series(array \$series = []): static",
            '注入图表的系列数据，是所有类型真正的内容主体。',
            [
                ['name' => '$series', 'summary' => '不同类型的格式不同：line/bar 常用 name + data；pie 常用 name + value；scatter 常用二维点位数组。'],
            ],
            <<<'CODE'
[
    'series' => [
        ['name' => '访问量', 'data' => [120, 132, 101]],
    ],
]
CODE,
            <<<'CODE'
use app\common\render\Chart;

Chart::make('visit_trend')
    ->series([
        ['name' => '访问量', 'data' => [120, 132, 101]],
    ]);
CODE,
            [
                'series() 是图表最核心的数据入口，showcase 应当优先把各种系列结构讲清楚。',
            ],
            [
                '如果 DSL 不够表达你的需求，优先在 series 每一项上直接补 ECharts 原生字段。',
            ],
            [
                $this->variant(
                    'line / bar 系列',
                    '最常见的一维数值列表。',
                    "[\n    'series' => [\n        ['name' => '访问量', 'data' => [120, 132, 101]],\n    ],\n]",
                    "use app\\common\\render\\Chart;\n\nChart::make('visit_trend')\n    ->series([\n        ['name' => '访问量', 'data' => [120, 132, 101]],\n    ]);"
                ),
                $this->variant(
                    'pie 系列',
                    '占比图常见 name/value 列表。',
                    "[\n    'series' => [\n        ['name' => '自然搜索', 'value' => 335],\n        ['name' => '广告投放', 'value' => 310],\n    ],\n]",
                    "use app\\common\\render\\Chart;\n\nChart::make('source_pie')\n    ->series([\n        ['name' => '自然搜索', 'value' => 335],\n        ['name' => '广告投放', 'value' => 310],\n    ]);"
                ),
                $this->variant(
                    'scatter 系列',
                    '二维分布图常见点位写法。',
                    "[\n    'series' => [\n        ['name' => '样本', 'data' => [[12, 32], [16, 40], [18, 52]]],\n    ],\n]",
                    "use app\\common\\render\\Chart;\n\nChart::make('sample_scatter')\n    ->series([\n        ['name' => '样本', 'data' => [[12, 32], [16, 40], [18, 52]]],\n    ]);"
                ),
            ]
        );
    }

    private function dataset(): array
    {
        return $this->section(
            'dataset',
            'dataset',
            'dataset() 异步数据源',
            "dataset(string \$url = '', string \$method = 'GET', array \$params = []): static",
            '配置图表异步数据接口，适合图表数据较大、需要筛选后再加载或由接口实时返回的场景。',
            [
                ['name' => '$url', 'summary' => '数据接口地址。'],
                ['name' => '$method', 'summary' => '请求方式，默认 GET。'],
                ['name' => '$params', 'summary' => '附带请求参数。'],
            ],
            <<<'CODE'
[
    'dataset' => [
        'url' => url('showcase/admin.demoApi/chart', ['dataset' => 'line']),
        'method' => 'GET',
        'params' => ['delay' => 120],
    ],
]
CODE,
            <<<'CODE'
use app\common\render\Chart;

Chart::make('trend_ajax')
    ->type('line')
    ->dataset((string) url('showcase/admin.demoApi/chart', ['dataset' => 'line']), 'GET', ['delay' => 120]);
CODE,
            [
                'dataset() 适合把图表定义和图表数据解耦，尤其适合筛选联动和实时接口场景。',
                '图表前端会把接口返回的 data 归一化成 option，不一定要手工返回整套 option。',
            ],
            [
                'showcase 中的 DemoApi 已提供 line/bar/pie/scatter/empty 五个数据集，可直接参考返回结构。',
            ],
            [
                $this->variant(
                    '无附加参数',
                    '适合固定接口地址。',
                    "[\n    'dataset' => [\n        'url' => url('showcase/admin.demoApi/chart', ['dataset' => 'line']),\n        'method' => 'GET',\n    ],\n]",
                    "use app\\common\\render\\Chart;\n\nChart::make('trend_ajax')\n    ->type('line')\n    ->dataset((string) url('showcase/admin.demoApi/chart', ['dataset' => 'line']));"
                ),
                $this->variant(
                    '附加查询参数',
                    '适合告诉接口当前周期或业务维度。',
                    "[\n    'dataset' => [\n        'url' => url('showcase/admin.demoApi/chart', ['dataset' => 'bar']),\n        'method' => 'GET',\n        'params' => ['delay' => 200],\n    ],\n]",
                    "use app\\common\\render\\Chart;\n\nChart::make('bar_ajax')\n    ->type('bar')\n    ->dataset((string) url('showcase/admin.demoApi/chart', ['dataset' => 'bar']), 'GET', ['delay' => 200]);"
                ),
            ]
        );
    }

    private function datasetMerge(): array
    {
        return $this->section(
            'datasetMerge',
            'dataset',
            'datasetMerge() 数据合并策略',
            "datasetMerge(bool \$merge = true): static",
            '控制异步接口返回值是与当前 option 合并，还是完全覆盖。',
            [
                ['name' => '$merge', 'summary' => 'true 表示合并，false 表示以接口结果为准完全替换。'],
            ],
            <<<'CODE'
[
    'dataset_merge' => false,
]
CODE,
            <<<'CODE'
use app\common\render\Chart;

Chart::make('trend_ajax')
    ->type('line')
    ->dataset((string) url('showcase/admin.demoApi/chart', ['dataset' => 'line']))
    ->datasetMerge(false);
CODE,
            [
                '合并模式适合本地先给默认 option，再由接口补充 categories / series。',
                '完全替换更适合接口直接返回完整 option 的场景。',
            ],
            [
                '如果开发者发现接口返回后某些默认配置被保留或被覆盖不符合预期，先检查 datasetMerge() 的值。',
            ],
            [
                $this->variant(
                    '合并模式',
                    '保留本地基础 option，再让接口补数据。',
                    "[\n    'dataset_merge' => true,\n]",
                    "use app\\common\\render\\Chart;\n\nChart::make('trend_ajax')\n    ->dataset((string) url('showcase/admin.demoApi/chart', ['dataset' => 'line']))\n    ->datasetMerge(true);"
                ),
                $this->variant(
                    '替换模式',
                    '接口直接决定最终图表表现。',
                    "[\n    'dataset_merge' => false,\n]",
                    "use app\\common\\render\\Chart;\n\nChart::make('trend_ajax')\n    ->dataset((string) url('showcase/admin.demoApi/chart', ['dataset' => 'line']))\n    ->datasetMerge(false);"
                ),
            ]
        );
    }

    private function loading(): array
    {
        return $this->section(
            'loading',
            'dataset',
            'loading() 加载态',
            "loading(bool|array \$loading = true): static",
            '控制图表加载中的占位反馈，适合异步数据、慢接口或初次进入看板时的体验优化。',
            [
                ['name' => '$loading', 'summary' => '布尔值控制显隐，也支持数组配置文本等扩展项。'],
            ],
            <<<'CODE'
[
    'loading' => ['show' => true, 'text' => '正在拉取趋势数据...'],
]
CODE,
            <<<'CODE'
use app\common\render\Chart;

Chart::make('trend_ajax')
    ->dataset((string) url('showcase/admin.demoApi/chart', ['dataset' => 'line']))
    ->loading(['show' => true, 'text' => '正在拉取趋势数据...']);
CODE,
            [
                'dataset() 设置了 URL 之后会默认打开 loading，loading() 主要用于进一步定制文案或显隐策略。',
                '异步图表如果没有 loading，开发者很容易误判为页面卡死或接口无响应。',
            ],
            [
                'loading 适合强调“正在加载”，不适合承载错误信息；错误或空数据应交给 empty 或业务提示处理。',
            ],
            [
                $this->variant(
                    '布尔开关',
                    '只控制是否展示。',
                    "[\n    'loading' => true,\n]",
                    "use app\\common\\render\\Chart;\n\nChart::make('trend_ajax')\n    ->loading(true);"
                ),
                $this->variant(
                    '自定义文本',
                    '更适合 showcase 展示实际体验差异。',
                    "[\n    'loading' => ['show' => true, 'text' => '正在拉取趋势数据...'],\n]",
                    "use app\\common\\render\\Chart;\n\nChart::make('trend_ajax')\n    ->loading(['show' => true, 'text' => '正在拉取趋势数据...']);"
                ),
            ]
        );
    }

    private function empty(): array
    {
        return $this->section(
            'empty',
            'dataset',
            'empty() 空态',
            "empty(string|array \$empty = ''): static",
            '控制无数据时的标题与说明，让图表在接口无数据时仍有明确反馈。',
            [
                ['name' => '$empty', 'summary' => '可传字符串快速设置说明，也可传数组同时设置 title / description。'],
            ],
            <<<'CODE'
[
    'empty' => [
        'title' => '暂无趋势数据',
        'description' => '当前周期没有可展示指标',
    ],
]
CODE,
            <<<'CODE'
use app\common\render\Chart;

Chart::make('trend_empty')
    ->dataset((string) url('showcase/admin.demoApi/chart', ['dataset' => 'empty']))
    ->empty([
        'title' => '暂无趋势数据',
        'description' => '当前周期没有可展示指标',
    ]);
CODE,
            [
                'empty() 是图表无数据时的正式反馈，不要让开发者在无数据场景只看到一块空白。',
                '如果只传字符串，会被当作 description 处理；想改标题应使用数组写法。',
            ],
            [
                'showcase 已提供 empty 数据集，安装后可以直接验证空态效果。',
            ],
            [
                $this->variant(
                    '字符串快速写法',
                    '只改说明文案。',
                    "[\n    'empty' => '当前周期没有可展示指标',\n]",
                    "use app\\common\\render\\Chart;\n\nChart::make('trend_empty')\n    ->empty('当前周期没有可展示指标');"
                ),
                $this->variant(
                    '数组完整写法',
                    '同时定义标题与说明。',
                    "[\n    'empty' => [\n        'title' => '暂无趋势数据',\n        'description' => '当前周期没有可展示指标',\n    ],\n]",
                    "use app\\common\\render\\Chart;\n\nChart::make('trend_empty')\n    ->empty([\n        'title' => '暂无趋势数据',\n        'description' => '当前周期没有可展示指标',\n    ]);"
                ),
            ]
        );
    }

    private function option(): array
    {
        return $this->section(
            'option',
            'dataset',
            'option() 原生配置覆盖',
            "option(array \$option = [], bool \$replace = false): static",
            '覆盖或合并 ECharts 原生 option，是 DSL 之外最重要的兜底能力。',
            [
                ['name' => '$option', 'summary' => '原生 ECharts option 配置。'],
                ['name' => '$replace', 'summary' => 'false 为递归合并，true 为完全替换。'],
            ],
            <<<'CODE'
[
    'option' => [
        'legend' => ['top' => 'bottom'],
        'xAxis' => ['axisLabel' => ['rotate' => 30]],
    ],
]
CODE,
            <<<'CODE'
use app\common\render\Chart;

Chart::make('trend_option')
    ->type('line')
    ->option([
        'legend' => ['top' => 'bottom'],
        'xAxis' => ['axisLabel' => ['rotate' => 30]],
    ]);
CODE,
            [
                'option() 是图表专题里最关键的逃生口：DSL 没覆盖到的细节，基本都应该从这里进去。',
                '默认是递归合并，适合在保留默认构建结果的基础上补细节。',
            ],
            [
                '只有在接口或本地已经准备好完整 option 时，才建议使用 $replace = true。',
            ],
            [
                $this->variant(
                    '合并默认 option',
                    '最常见、最安全的写法。',
                    "[\n    'option' => [\n        'legend' => ['top' => 'bottom'],\n    ],\n]",
                    "use app\\common\\render\\Chart;\n\nChart::make('trend_option')\n    ->option([\n        'legend' => ['top' => 'bottom'],\n    ]);"
                ),
                $this->variant(
                    '完全替换 option',
                    '适合你明确希望自行控制整套图表配置。',
                    "[\n    'option' => [\n        'tooltip' => ['trigger' => 'item'],\n        'series' => [],\n    ],\n    'replace' => true,\n]",
                    "use app\\common\\render\\Chart;\n\nChart::make('trend_option_replace')\n    ->option([\n        'tooltip' => ['trigger' => 'item'],\n        'series' => [],\n    ], true);"
                ),
            ]
        );
    }

    private function map(): array
    {
        return $this->section(
            'map',
            'dataset',
            'map() 地图专项扩展',
            "map(string \$mapKey = '', array \$data = []): static",
            '启用地图专项扩展协议，并传入地图 key 与初始地图数据。',
            [
                ['name' => '$mapKey', 'summary' => '地图扩展 key，如 demo_region、china.guangdong。'],
                ['name' => '$data', 'summary' => '初始地图数据，常见包括 regions、visualMap、overlays、view、tooltip。'],
            ],
            <<<'CODE'
[
    'map' => 'demo_region',
    'regions' => [
        ['code' => '1001', 'value' => 92],
        ['code' => '1002', 'value' => 76],
    ],
]
CODE,
            <<<'CODE'
use app\common\render\Chart;

Chart::make('demo_region_map')
    ->height(360)
    ->map('demo_region', [
        'regions' => [
            ['code' => '1001', 'value' => 92],
            ['code' => '1002', 'value' => 76],
        ],
        'visualMap' => [
            'min' => 0,
            'max' => 100,
        ],
    ]);
CODE,
            [
                'map() 不是普通图表的 type 切换，而是进入一套独立的地图专项协议。',
                '地图 key 最终会解析到 extend/chart_map/<map-key>/Provider.php，由 Provider 负责 GeoJSON、区域归一化和坐标补全。',
            ],
            [
                '如果只是做最小地图验证，建议先从 demo_region 开始，再迁移到 china.guangdong 这类真实行政区示例。',
            ],
            [
                $this->variant(
                    '最小区域地图',
                    '适合先验证 GeoJSON、regions 和 visualMap。',
                    "[\n    'map' => 'demo_region',\n    'regions' => [\n        ['code' => '1001', 'value' => 92],\n        ['code' => '1002', 'value' => 76],\n    ],\n]",
                    "use app\\common\\render\\Chart;\n\nChart::make('demo_region_map')\n    ->map('demo_region', [\n        'regions' => [\n            ['code' => '1001', 'value' => 92],\n            ['code' => '1002', 'value' => 76],\n        ],\n    ]);"
                ),
                $this->variant(
                    '省级行政区地图',
                    '适合真实业务地市分布场景。',
                    "[\n    'map' => 'china.guangdong',\n    'regions' => [\n        ['code' => '440100', 'value' => 96],\n        ['code' => '440300', 'value' => 98],\n    ],\n]",
                    "use app\\common\\render\\Chart;\n\nChart::make('guangdong_map')\n    ->map('china.guangdong', [\n        'regions' => [\n            ['code' => '440100', 'value' => 96],\n            ['code' => '440300', 'value' => 98],\n        ],\n    ]);"
                ),
                $this->variant(
                    '异步地图数据',
                    '本地先定义地图底图，再由接口返回业务数据。',
                    "[\n    'map' => 'demo_region',\n    'dataset' => [\n        'url' => url('showcase/admin.demoApi/chartMap', ['dataset' => 'demo_region']),\n    ],\n]",
                    "use app\\common\\render\\Chart;\n\nChart::make('demo_region_ajax')\n    ->map('demo_region')\n    ->dataset((string) url('showcase/admin.demoApi/chartMap', ['dataset' => 'demo_region']));"
                ),
            ]
        );
    }

    private function mapData(): array
    {
        return $this->section(
            'mapData',
            'dataset',
            'mapData() 地图数据增量合并',
            "mapData(array \$data = [], bool \$merge = true): static",
            '在已启用地图专项协议后，继续补充或覆盖地图数据。',
            [
                ['name' => '$data', 'summary' => '需要补充到地图里的 regions、overlays、view、tooltip 等数据。'],
                ['name' => '$merge', 'summary' => 'true 表示递归合并，false 表示直接替换现有 mapData。'],
            ],
            <<<'CODE'
[
    'map_data' => [
        'overlays' => [[
            'type' => 'effectScatter',
            'data' => [
                ['code' => '1001', 'value' => 92],
            ],
        ]],
    ],
]
CODE,
            <<<'CODE'
use app\common\render\Chart;

Chart::make('demo_region_overlay')
    ->map('demo_region', [
        'regions' => [
            ['code' => '1001', 'value' => 92],
            ['code' => '1002', 'value' => 76],
        ],
    ])
    ->mapData([
        'overlays' => [[
            'type' => 'effectScatter',
            'data' => [
                ['code' => '1001', 'value' => 92],
            ],
        ]],
    ]);
CODE,
            [
                'mapData() 更适合在已有地图底图配置基础上，分阶段补充业务区域数据或覆盖物。',
                '如果地图结构已经很大，把静态底图和动态业务数据拆开写，源码会明显更清晰。',
            ],
            [
                '默认 merge=true，适合“保留现有地图定义，再补充业务数据”的场景。',
            ],
            [
                $this->variant(
                    '递归合并 overlays',
                    '最常见的增量补点位或飞线场景。',
                    "[\n    'map_data' => [\n        'overlays' => [[\n            'type' => 'effectScatter',\n            'data' => [\n                ['code' => '1001', 'value' => 92],\n            ],\n        ]],\n    ],\n]",
                    "use app\\common\\render\\Chart;\n\nChart::make('demo_region_overlay')\n    ->map('demo_region', [\n        'regions' => [\n            ['code' => '1001', 'value' => 92],\n            ['code' => '1002', 'value' => 76],\n        ],\n    ])\n    ->mapData([\n        'overlays' => [[\n            'type' => 'effectScatter',\n            'data' => [\n                ['code' => '1001', 'value' => 92],\n            ],\n        ]],\n    ]);"
                ),
                $this->variant(
                    '替换 mapData',
                    '适合切换不同地图业务快照。',
                    "[\n    'map_data' => [\n        'regions' => [\n            ['code' => '440100', 'value' => 88],\n        ],\n    ],\n    'merge' => false,\n]",
                    "use app\\common\\render\\Chart;\n\nChart::make('guangdong_replace')\n    ->map('china.guangdong', [\n        'regions' => [\n            ['code' => '440100', 'value' => 96],\n            ['code' => '440300', 'value' => 98],\n        ],\n    ])\n    ->mapData([\n        'regions' => [\n            ['code' => '440100', 'value' => 88],\n        ],\n    ], false);"
                ),
            ]
        );
    }
}
