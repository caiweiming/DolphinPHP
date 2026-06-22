<?php
declare(strict_types=1);

namespace app\showcase\service\components\chart;

/**
 * bar 图表详情页
 */
final class BarChartPage extends AbstractChartPage
{
    /**
     * @param array<string, mixed> $component
     * @return array<string, mixed>
     */
    public function build(array $component): array
    {
        $sections = [
            $this->makeSection(
                'basic_bar',
                '基础柱状图',
                '最常见的类目对比写法，适合展示销售额、订单量、访问量等离散数据。',
                $this->renderChart(function ($chart): void {
                    $chart
                        ->title('近 7 日下单量')
                        ->type('bar')
                        ->categories(['周一', '周二', '周三', '周四', '周五', '周六', '周日'])
                        ->series([
                            ['name' => '订单量', 'data' => [220, 182, 191, 234, 290, 330, 310]],
                        ]);
                }),
                [
                    ['name' => 'type', 'value' => 'bar'],
                    ['name' => 'categories', 'value' => "['周一', '周二', '周三', '周四', '周五', '周六', '周日']"],
                    ['name' => 'series', 'value' => "[['name' => '订单量', 'data' => [220, 182, 191, 234, 290, 330, 310]]]"],
                ],
                [
                    '柱状图适合先把类目和值的对应关系讲清楚，再去做颜色、圆角、标签等细节定制。',
                    '如果开发者只是做最普通的统计柱状图，直接记住 categories + series 这组写法即可。',
                ],
                <<<'CODE'
[
    'type' => 'bar',
    'categories' => ['周一', '周二', '周三', '周四', '周五', '周六', '周日'],
    'series' => [
        ['name' => '订单量', 'data' => [220, 182, 191, 234, 290, 330, 310]],
    ],
]
CODE,
                <<<'CODE'
use app\common\render\Chart;

Chart::make('order_count_bar')
    ->title('近 7 日下单量')
    ->type('bar')
    ->categories(['周一', '周二', '周三', '周四', '周五', '周六', '周日'])
    ->series([
        ['name' => '订单量', 'data' => [220, 182, 191, 234, 290, 330, 310]],
    ]);
CODE,
                [
                    ['path' => 'app/common/render/Chart.php', 'label' => 'Chart 构建器源码', 'description' => 'bar 图表仍使用统一的 categories / series 入口。'],
                    ['path' => 'app/common/render/chart/type/Bar.php', 'label' => 'bar 类型构建器', 'description' => 'bar 图表默认 option 生成逻辑。'],
                ]
            ),
            $this->makeSection(
                'stacked_bar',
                '堆叠柱状图',
                '适合展示同一类目下多个来源或多个阶段的累积对比。',
                $this->renderChart(function ($chart): void {
                    $chart
                        ->title('渠道来源构成')
                        ->type('bar')
                        ->categories(['新客', '复购', '会员', '分销'])
                        ->series([
                            ['name' => '自然流量', 'stack' => 'total', 'data' => [120, 132, 101, 134]],
                            ['name' => '广告投放', 'stack' => 'total', 'data' => [220, 182, 191, 234]],
                            ['name' => '私域转化', 'stack' => 'total', 'data' => [150, 232, 201, 154]],
                        ]);
                }),
                [
                    ['name' => 'series[*].stack', 'value' => 'total'],
                    ['name' => 'series', 'value' => '多个系列共用相同 stack 值'],
                ],
                [
                    '堆叠的关键不是特殊 API，而是给多个系列配置同一个 stack 值。',
                    '如果同时还要看单系列对比关系，建议补充 tooltip 或标签，避免开发者误解数据。',
                ],
                <<<'CODE'
[
    'type' => 'bar',
    'series' => [
        ['name' => '自然流量', 'stack' => 'total', 'data' => [120, 132, 101, 134]],
        ['name' => '广告投放', 'stack' => 'total', 'data' => [220, 182, 191, 234]],
    ],
]
CODE,
                <<<'CODE'
use app\common\render\Chart;

Chart::make('channel_stack_bar')
    ->title('渠道来源构成')
    ->type('bar')
    ->categories(['新客', '复购', '会员', '分销'])
    ->series([
        ['name' => '自然流量', 'stack' => 'total', 'data' => [120, 132, 101, 134]],
        ['name' => '广告投放', 'stack' => 'total', 'data' => [220, 182, 191, 234]],
        ['name' => '私域转化', 'stack' => 'total', 'data' => [150, 232, 201, 154]],
    ]);
CODE,
                [
                    ['path' => 'app/common/render/chart/type/Bar.php', 'label' => 'bar 类型构建器', 'description' => '堆叠能力由系列原生配置直接承接。'],
                ]
            ),
            $this->makeSection(
                'grouped_bar',
                '并列多系列柱状图',
                '适合在同一类目下并排比较多个指标，而不是把它们累加到一起。',
                $this->renderChart(function ($chart): void {
                    $chart
                        ->title('渠道订单与支付对比')
                        ->type('bar')
                        ->categories(['官网', '小程序', 'App', '分销'])
                        ->series([
                            ['name' => '订单量', 'data' => [320, 286, 248, 198]],
                            ['name' => '支付单量', 'data' => [280, 252, 214, 176]],
                        ]);
                }),
                [
                    ['name' => 'series[0]', 'value' => '订单量'],
                    ['name' => 'series[1]', 'value' => '支付单量'],
                ],
                [
                    '如果不配置相同 stack，多个系列默认就是并排展示，这也是后台里更常见的比较型柱状图。',
                    '这类示例能让开发者区分“并列对比”和“堆叠构成”到底该怎么写。',
                ],
                <<<'CODE'
[
    'type' => 'bar',
    'categories' => ['官网', '小程序', 'App', '分销'],
    'series' => [
        ['name' => '订单量', 'data' => [320, 286, 248, 198]],
        ['name' => '支付单量', 'data' => [280, 252, 214, 176]],
    ],
]
CODE,
                <<<'CODE'
use app\common\render\Chart;

Chart::make('grouped_channel_bar')
    ->title('渠道订单与支付对比')
    ->type('bar')
    ->categories(['官网', '小程序', 'App', '分销'])
    ->series([
        ['name' => '订单量', 'data' => [320, 286, 248, 198]],
        ['name' => '支付单量', 'data' => [280, 252, 214, 176]],
    ]);
CODE,
                [
                    ['path' => 'app/common/render/chart/type/Bar.php', 'label' => 'bar 类型构建器', 'description' => '并列多系列柱状图仍走同一类目轴逻辑。'],
                ]
            ),
            $this->makeSection(
                'horizontal_bar',
                '横向条形图',
                '当类目名称较长或想突出排行顺序时，可以通过 option 覆盖坐标轴方向。',
                $this->renderChart(function ($chart): void {
                    $chart
                        ->title('商品销量排行 Top 5')
                        ->type('bar')
                        ->categories(['电动牙刷礼盒', '便携冲牙器', '儿童刷头套装', '旅行收纳包', '声波刷头'])
                        ->series([
                            ['name' => '销量', 'data' => [520, 468, 420, 390, 360]],
                        ])
                        ->option([
                            'xAxis' => ['type' => 'value'],
                            'yAxis' => [
                                'type' => 'category',
                                'data' => ['电动牙刷礼盒', '便携冲牙器', '儿童刷头套装', '旅行收纳包', '声波刷头'],
                            ],
                        ]);
                }),
                [
                    ['name' => 'option.xAxis', 'value' => "['type' => 'value']"],
                    ['name' => 'option.yAxis', 'value' => "['type' => 'category', 'data' => [...]]"],
                ],
                [
                    '横向条形图本质上是通过 option 覆盖默认轴配置，不需要额外的 barHorizontal 类型。',
                    '这类示例适合告诉开发者：Chart 自带 DSL 不够时，直接补 option 即可。',
                ],
                <<<'CODE'
[
    'type' => 'bar',
    'option' => [
        'xAxis' => ['type' => 'value'],
        'yAxis' => ['type' => 'category', 'data' => ['A', 'B', 'C']],
    ],
]
CODE,
                <<<'CODE'
use app\common\render\Chart;

Chart::make('sales_rank_bar')
    ->title('商品销量排行 Top 5')
    ->type('bar')
    ->categories(['电动牙刷礼盒', '便携冲牙器', '儿童刷头套装', '旅行收纳包', '声波刷头'])
    ->series([
        ['name' => '销量', 'data' => [520, 468, 420, 390, 360]],
    ])
    ->option([
        'xAxis' => ['type' => 'value'],
        'yAxis' => [
            'type' => 'category',
            'data' => ['电动牙刷礼盒', '便携冲牙器', '儿童刷头套装', '旅行收纳包', '声波刷头'],
        ],
    ]);
CODE,
                [
                    ['path' => 'app/common/render/Chart.php', 'label' => 'Chart 构建器源码', 'description' => 'option() 用于覆盖默认 bar 轴配置。'],
                ]
            ),
            $this->makeSection(
                'label_style_bar',
                '标签与颜色透传',
                '通过系列原生字段直接补标签、颜色和圆角，适合开发者快速做出更接近业务成品的柱状图。',
                $this->renderChart(function ($chart): void {
                    $chart
                        ->title('活动成交额')
                        ->type('bar')
                        ->categories(['618 预热', '618 爆发', '返场期'])
                        ->series([
                            [
                                'name' => '成交额',
                                'data' => [820, 1320, 960],
                                'label' => ['show' => true, 'position' => 'top'],
                                'itemStyle' => [
                                    'color' => '#2f6fed',
                                    'borderRadius' => [6, 6, 0, 0],
                                ],
                            ],
                        ]);
                }),
                [
                    ['name' => 'series[0].label', 'value' => "['show' => true, 'position' => 'top']"],
                    ['name' => 'series[0].itemStyle', 'value' => "['color' => '#2f6fed', 'borderRadius' => [6, 6, 0, 0]]"],
                ],
                [
                    '很多业务图表并不需要新 DSL，直接在系列上透传 label 和 itemStyle 就已经够用了。',
                    '这个示例的重点不是视觉炫技，而是让开发者知道“标签与颜色透传”就这样写。',
                ],
                <<<'CODE'
[
    'type' => 'bar',
    'series' => [[
        'name' => '成交额',
        'data' => [820, 1320, 960],
        'label' => ['show' => true, 'position' => 'top'],
        'itemStyle' => [
            'color' => '#2f6fed',
            'borderRadius' => [6, 6, 0, 0],
        ],
    ]],
]
CODE,
                <<<'CODE'
use app\common\render\Chart;

Chart::make('styled_revenue_bar')
    ->title('活动成交额')
    ->type('bar')
    ->categories(['618 预热', '618 爆发', '返场期'])
    ->series([
        [
            'name' => '成交额',
            'data' => [820, 1320, 960],
            'label' => ['show' => true, 'position' => 'top'],
            'itemStyle' => [
                'color' => '#2f6fed',
                'borderRadius' => [6, 6, 0, 0],
            ],
        ],
    ]);
CODE,
                [
                    ['path' => 'app/common/render/Chart.php', 'label' => 'Chart 构建器源码', 'description' => 'series() 可直接透传 label、itemStyle 等 bar 原生字段。'],
                ]
            ),
        ];

        return $this->makePage(
            $component,
            [
                'summary' => '柱状图适合展示不同类目之间的数值对比，是后台统计、业务报表和榜单排行里最常见的对比型图表。',
                'scenarios' => [
                    '不同日期、不同渠道、不同部门之间的指标对比',
                    'Top N 排行、阶段结果、来源结构等类目数据展示',
                    '需要同时支持并列对比、堆叠对比和横向排行的报表页',
                ],
                'capabilities' => [
                    '基础类目对比',
                    '并列多系列柱状图',
                    '堆叠柱状图',
                    '横向排行',
                    '标签与颜色透传',
                    'option 原生覆盖',
                ],
                'quick_start' => [
                    'array_code' => <<<'CODE'
[
    'type' => 'bar',
    'categories' => ['周一', '周二', '周三'],
    'series' => [
        ['name' => '订单量', 'data' => [220, 182, 191]],
    ],
]
CODE,
                    'chart_code' => <<<'CODE'
use app\common\render\Chart;

Chart::make('order_bar')
    ->type('bar')
    ->categories(['周一', '周二', '周三'])
    ->series([
        ['name' => '订单量', 'data' => [220, 182, 191]],
    ]);
CODE,
                ],
            ],
            [
                [
                    'title' => '基础参数',
                    'items' => [
                        ['name' => 'type', 'summary' => '固定写 `bar`。'],
                        ['name' => 'categories', 'summary' => '类目轴数据。'],
                        ['name' => 'series', 'summary' => '系列数组，可继续叠加 stack、label 等原生字段。'],
                    ],
                ],
                [
                    'title' => '高频扩展',
                    'items' => [
                        ['name' => 'option', 'summary' => '适合覆盖横向条形、间距、标签等 ECharts 原生配置。'],
                    ],
            ],
            ],
            $sections,
            $this->relatedComponents((string) ($component['key'] ?? ''))
        );
    }
}
