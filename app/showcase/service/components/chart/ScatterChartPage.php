<?php
declare(strict_types=1);

namespace app\showcase\service\components\chart;

/**
 * scatter 图表详情页
 */
final class ScatterChartPage extends AbstractChartPage
{
    /**
     * @param array<string, mixed> $component
     * @return array<string, mixed>
     */
    public function build(array $component): array
    {
        $sections = [
            $this->makeSection(
                'basic_scatter',
                '基础散点图',
                '最常见的二维分布图写法，适合查看两个连续变量之间的相关性。',
                $this->renderChart(function ($chart): void {
                    $chart
                        ->title('广告花费与转化关系')
                        ->type('scatter')
                        ->series([
                            [
                                'name' => '计划 A',
                                'data' => [[12, 32], [16, 40], [18, 52], [22, 65], [28, 78]],
                            ],
                        ]);
                }),
                [
                    ['name' => 'type', 'value' => 'scatter'],
                    ['name' => 'series[0].data', 'value' => '[[x, y], [x, y], ...]'],
                ],
                [
                    'scatter 的重点在于 data 是二维数组，不是普通的一维数值列表。',
                    '如果开发者要表达两个连续变量的关系，优先考虑 scatter，而不是硬塞到 line/bar。',
                ],
                <<<'CODE'
[
    'type' => 'scatter',
    'series' => [[
        'name' => '计划 A',
        'data' => [[12, 32], [16, 40], [18, 52], [22, 65], [28, 78]],
    ]],
]
CODE,
                <<<'CODE'
use app\common\render\Chart;

Chart::make('ads_scatter')
    ->title('广告花费与转化关系')
    ->type('scatter')
    ->series([
        [
            'name' => '计划 A',
            'data' => [[12, 32], [16, 40], [18, 52], [22, 65], [28, 78]],
        ],
    ]);
CODE,
                [
                    ['path' => 'app/common/render/chart/type/Scatter.php', 'label' => 'scatter 类型构建器', 'description' => 'scatter 图表默认双数值轴配置。'],
                ]
            ),
            $this->makeSection(
                'bubble_scatter',
                '气泡尺寸映射',
                '通过 symbolSize 回调把第三维数据映射成气泡大小，适合做样本量、客单价等强弱对比。',
                $this->renderChart(function ($chart): void {
                    $chart
                        ->title('投放计划质量分层')
                        ->type('scatter')
                        ->series([
                            [
                                'name' => '投放计划',
                                'data' => [
                                    ['value' => [12, 28], 'symbolSize' => 14],
                                    ['value' => [18, 42], 'symbolSize' => 18],
                                    ['value' => [26, 60], 'symbolSize' => 26],
                                    ['value' => [30, 74], 'symbolSize' => 34],
                                ],
                            ],
                        ]);
                }),
                [
                    ['name' => 'series[0].data', 'value' => "[['value' => [x, y], 'symbolSize' => 14], ...]"],
                    ['name' => 'series[0].symbolSize', 'value' => '也支持在每个数据点上单独配置尺寸'],
                ],
                [
                    'scatter 不仅能做点位分布，也能通过第三维数据映射成气泡图。',
                    '这里重点是告诉开发者：除了 series 级别配置，也可以在每个数据点上直接透传原生字段。',
                ],
                <<<'CODE'
[
    'type' => 'scatter',
    'series' => [[
        'data' => [
            ['value' => [12, 28], 'symbolSize' => 14],
            ['value' => [18, 42], 'symbolSize' => 18],
            ['value' => [26, 60], 'symbolSize' => 26],
        ],
    ]],
]
CODE,
                <<<'CODE'
use app\common\render\Chart;

Chart::make('plan_quality_scatter')
    ->title('投放计划质量分层')
    ->type('scatter')
    ->series([
        [
            'name' => '投放计划',
            'data' => [
                ['value' => [12, 28], 'symbolSize' => 14],
                ['value' => [18, 42], 'symbolSize' => 18],
                ['value' => [26, 60], 'symbolSize' => 26],
                ['value' => [30, 74], 'symbolSize' => 34],
            ],
        ],
    ]);
CODE,
                [
                    ['path' => 'app/common/render/Chart.php', 'label' => 'Chart 构建器源码', 'description' => 'series() 支持透传 symbolSize 等原生字段。'],
                ]
            ),
            $this->makeSection(
                'series_symbol_size',
                '系列级气泡尺寸',
                '当一组数据点都希望维持同样的气泡大小时，可以直接在系列级别配置 symbolSize。',
                $this->renderChart(function ($chart): void {
                    $chart
                        ->title('统一尺寸样本点')
                        ->type('scatter')
                        ->series([
                            [
                                'name' => '统一样本',
                                'symbolSize' => 22,
                                'data' => [[12, 26], [18, 36], [22, 44], [27, 58]],
                            ],
                        ]);
                }),
                [
                    ['name' => 'series[0].symbolSize', 'value' => '22'],
                    ['name' => 'series[0].data', 'value' => '普通二维点位数组'],
                ],
                [
                    '如果点位尺寸本身没有第三维含义，就直接在系列级别给一个固定 symbolSize，更简单也更稳定。',
                    '这个示例是为了区分“点级 symbolSize”与“系列级气泡尺寸”两种常见写法。',
                ],
                <<<'CODE'
[
    'type' => 'scatter',
    'series' => [[
        'name' => '统一样本',
        'symbolSize' => 22,
        'data' => [[12, 26], [18, 36], [22, 44], [27, 58]],
    ]],
]
CODE,
                <<<'CODE'
use app\common\render\Chart;

Chart::make('fixed_symbol_scatter')
    ->title('统一尺寸样本点')
    ->type('scatter')
    ->series([
        [
            'name' => '统一样本',
            'symbolSize' => 22,
            'data' => [[12, 26], [18, 36], [22, 44], [27, 58]],
        ],
    ]);
CODE,
                [
                    ['path' => 'app/common/render/Chart.php', 'label' => 'Chart 构建器源码', 'description' => 'symbolSize 既可写在系列级，也可写在单个点位上。'],
                ]
            ),
            $this->makeSection(
                'multi_series_scatter',
                '多系列散点',
                '适合在同一张图里对比不同样本群体的分布差异。',
                $this->renderChart(function ($chart): void {
                    $chart
                        ->title('新客与老客分布对比')
                        ->type('scatter')
                        ->series([
                            ['name' => '新客', 'data' => [[12, 20], [18, 32], [24, 48], [28, 60]]],
                            ['name' => '老客', 'data' => [[10, 30], [16, 42], [20, 56], [25, 72]]],
                        ]);
                }),
                [
                    ['name' => 'series[0]', 'value' => '新客样本'],
                    ['name' => 'series[1]', 'value' => '老客样本'],
                ],
                [
                    '多系列 scatter 的理解方式和多系列 line/bar 一致，只是 data 结构变成二维点位。',
                    '如果要让系列更容易区分，可以补 color 或 legend 定制，但不必强耦合到 DSL 里。',
                ],
                <<<'CODE'
[
    'type' => 'scatter',
    'series' => [
        ['name' => '新客', 'data' => [[12, 20], [18, 32], [24, 48], [28, 60]]],
        ['name' => '老客', 'data' => [[10, 30], [16, 42], [20, 56], [25, 72]]],
    ],
]
CODE,
                <<<'CODE'
use app\common\render\Chart;

Chart::make('user_segment_scatter')
    ->title('新客与老客分布对比')
    ->type('scatter')
    ->series([
        ['name' => '新客', 'data' => [[12, 20], [18, 32], [24, 48], [28, 60]]],
        ['name' => '老客', 'data' => [[10, 30], [16, 42], [20, 56], [25, 72]]],
    ]);
CODE,
                [
                    ['path' => 'app/common/render/chart/type/Scatter.php', 'label' => 'scatter 类型构建器', 'description' => '多系列 scatter 仍使用同一个类型构建器。'],
                ]
            ),
            $this->makeSection(
                'style_tooltip_scatter',
                '样式与 tooltip 透传',
                '通过 itemStyle 和 option.tooltip 补充颜色、透明度和提示文案，让示例更接近真实业务图表。',
                $this->renderChart(function ($chart): void {
                    $chart
                        ->title('高价值样本分布')
                        ->type('scatter')
                        ->series([
                            [
                                'name' => '高价值样本',
                                'itemStyle' => [
                                    'color' => '#f08c2b',
                                    'opacity' => 0.82,
                                ],
                                'data' => [
                                    ['name' => '样本 A', 'value' => [16, 42]],
                                    ['name' => '样本 B', 'value' => [20, 54]],
                                    ['name' => '样本 C', 'value' => [28, 70]],
                                ],
                            ],
                        ])
                        ->option([
                            'tooltip' => [
                                'trigger' => 'item',
                                'formatter' => '{b}<br/>X / Y: {c}',
                            ],
                        ]);
                }),
                [
                    ['name' => 'series[0].itemStyle', 'value' => "['color' => '#f08c2b', 'opacity' => 0.82]"],
                    ['name' => 'option.tooltip', 'value' => "['trigger' => 'item', 'formatter' => '{b}<br/>X / Y: {c}']"],
                ],
                [
                    'scatter 常见的真实需求并不复杂，很多时候只是补一点样式和 tooltip，就已经足够上线。',
                    '这个示例的重点是让开发者知道“样式与 tooltip 透传”可以直接写在系列和 option 上。',
                ],
                <<<'CODE'
[
    'type' => 'scatter',
    'series' => [[
        'name' => '高价值样本',
        'itemStyle' => ['color' => '#f08c2b', 'opacity' => 0.82],
        'data' => [
            ['name' => '样本 A', 'value' => [16, 42]],
            ['name' => '样本 B', 'value' => [20, 54]],
            ['name' => '样本 C', 'value' => [28, 70]],
        ],
    ]],
    'option' => [
        'tooltip' => ['trigger' => 'item', 'formatter' => '{b}<br/>X / Y: {c}'],
    ],
]
CODE,
                <<<'CODE'
use app\common\render\Chart;

Chart::make('styled_scatter')
    ->title('高价值样本分布')
    ->type('scatter')
    ->series([
        [
            'name' => '高价值样本',
            'itemStyle' => ['color' => '#f08c2b', 'opacity' => 0.82],
            'data' => [
                ['name' => '样本 A', 'value' => [16, 42]],
                ['name' => '样本 B', 'value' => [20, 54]],
                ['name' => '样本 C', 'value' => [28, 70]],
            ],
        ],
    ])
    ->option([
        'tooltip' => ['trigger' => 'item', 'formatter' => '{b}<br/>X / Y: {c}'],
    ]);
CODE,
                [
                    ['path' => 'app/common/render/Chart.php', 'label' => 'Chart 构建器源码', 'description' => 'option() 与 series() 可共同承接散点图样式和提示配置。'],
                ]
            ),
        ];

        return $this->makePage(
            $component,
            [
                'summary' => '散点图适合展示两个连续变量之间的分布关系，也能扩展成带气泡尺寸的三维信息表达。',
                'scenarios' => [
                    '广告花费与转化率、价格与销量、时长与得分等相关性分析',
                    '用户分层、样本聚类、质量评估等分布可视化',
                    '需要同时表达点位和样本量大小的后台分析页面',
                ],
                'capabilities' => [
                    '基础二维散点',
                    '气泡尺寸映射',
                    '系列级气泡尺寸',
                    '多系列分布对比',
                    '样式与 tooltip 透传',
                    '原生 series 字段透传',
                ],
                'quick_start' => [
                    'array_code' => <<<'CODE'
[
    'type' => 'scatter',
    'series' => [[
        'name' => '样本',
        'data' => [[12, 32], [16, 40], [18, 52]],
    ]],
]
CODE,
                    'chart_code' => <<<'CODE'
use app\common\render\Chart;

Chart::make('sample_scatter')
    ->type('scatter')
    ->series([
        [
            'name' => '样本',
            'data' => [[12, 32], [16, 40], [18, 52]],
        ],
    ]);
CODE,
                ],
            ],
            [
                [
                    'title' => '基础参数',
                    'items' => [
                        ['name' => 'type', 'summary' => '固定写 `scatter`。'],
                        ['name' => 'series', 'summary' => '二维点位数组，常用格式为 [[x, y], [x, y]]。'],
                    ],
                ],
                [
                    'title' => '高频扩展',
                    'items' => [
                        ['name' => 'series[*].symbolSize', 'summary' => '控制点大小或气泡大小。'],
                        ['name' => 'series[*].itemStyle', 'summary' => '定制颜色、透明度等表现。'],
                    ],
            ],
            ],
            $sections,
            $this->relatedComponents((string) ($component['key'] ?? ''))
        );
    }
}
