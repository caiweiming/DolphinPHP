<?php
declare(strict_types=1);

namespace app\showcase\service\components\chart;

/**
 * pie 图表详情页
 */
final class PieChartPage extends AbstractChartPage
{
    /**
     * @param array<string, mixed> $component
     * @return array<string, mixed>
     */
    public function build(array $component): array
    {
        $sections = [
            $this->makeSection(
                'basic_pie',
                '基础饼图',
                '最直接的占比展示写法，适合来源构成、状态分布和类型比例。',
                $this->renderChart(function ($chart): void {
                    $chart
                        ->title('流量来源占比')
                        ->type('pie')
                        ->series([
                            ['name' => '自然搜索', 'value' => 335],
                            ['name' => '广告投放', 'value' => 310],
                            ['name' => '私域运营', 'value' => 234],
                            ['name' => '老客直访', 'value' => 135],
                        ]);
                }),
                [
                    ['name' => 'type', 'value' => 'pie'],
                    ['name' => 'series', 'value' => "[['name' => '自然搜索', 'value' => 335], ...]"],
                ],
                [
                    '饼图最重要的是 series 数据结构：name + value，categories 在这里不是必须项。',
                    '如果只是做最普通的占比图，直接记住传一组 name/value 即可。',
                ],
                <<<'CODE'
[
    'type' => 'pie',
    'series' => [
        ['name' => '自然搜索', 'value' => 335],
        ['name' => '广告投放', 'value' => 310],
        ['name' => '私域运营', 'value' => 234],
    ],
]
CODE,
                <<<'CODE'
use app\common\render\Chart;

Chart::make('traffic_source_pie')
    ->title('流量来源占比')
    ->type('pie')
    ->series([
        ['name' => '自然搜索', 'value' => 335],
        ['name' => '广告投放', 'value' => 310],
        ['name' => '私域运营', 'value' => 234],
        ['name' => '老客直访', 'value' => 135],
    ]);
CODE,
                [
                    ['path' => 'app/common/render/chart/type/Pie.php', 'label' => 'pie 类型构建器', 'description' => 'pie 图表默认 option 生成逻辑。'],
                ]
            ),
            $this->makeSection(
                'donut_pie',
                '环形饼图',
                '通过给系列补充 radius 数组，把普通饼图改造成信息密度更高的环形图。',
                $this->renderChart(function ($chart): void {
                    $chart
                        ->title('订单状态占比')
                        ->type('pie')
                        ->series([
                            [
                                'name' => '订单状态',
                                'radius' => ['42%', '68%'],
                                'data' => [
                                    ['name' => '待支付', 'value' => 120],
                                    ['name' => '待发货', 'value' => 90],
                                    ['name' => '已完成', 'value' => 360],
                                    ['name' => '已关闭', 'value' => 40],
                                ],
                            ],
                        ]);
                }),
                [
                    ['name' => 'series[0].radius', 'value' => "['42%', '68%']"],
                    ['name' => 'series[0].data', 'value' => '环形图数据列表'],
                ],
                [
                    '环形图不需要单独的 type，仍然是 pie，只是 series 写法更完整。',
                    '这类示例可以让开发者知道：很多细节都可以直接走原生 series 参数。',
                ],
                <<<'CODE'
[
    'type' => 'pie',
    'series' => [[
        'radius' => ['42%', '68%'],
        'data' => [
            ['name' => '待支付', 'value' => 120],
            ['name' => '待发货', 'value' => 90],
        ],
    ]],
]
CODE,
                <<<'CODE'
use app\common\render\Chart;

Chart::make('order_status_pie')
    ->title('订单状态占比')
    ->type('pie')
    ->series([
        [
            'name' => '订单状态',
            'radius' => ['42%', '68%'],
            'data' => [
                ['name' => '待支付', 'value' => 120],
                ['name' => '待发货', 'value' => 90],
                ['name' => '已完成', 'value' => 360],
                ['name' => '已关闭', 'value' => 40],
            ],
        ],
    ]);
CODE,
                [
                    ['path' => 'app/common/render/Chart.php', 'label' => 'Chart 构建器源码', 'description' => 'series() 可直接承接原生 pie 半径等字段。'],
                ]
            ),
            $this->makeSection(
                'multi_pie_format',
                '多组数据写法',
                '展示 pie 的两种常见 series 输入格式，方便开发者快速判断自己应该怎么传数据。',
                $this->renderChart(function ($chart): void {
                    $chart
                        ->title('会员等级构成')
                        ->type('pie')
                        ->series([
                            '普通会员' => 420,
                            '银卡会员' => 260,
                            '金卡会员' => 160,
                            '黑金会员' => 60,
                        ]);
                }),
                [
                    ['name' => 'series', 'value' => "['普通会员' => 420, '银卡会员' => 260, ...]"],
                    ['name' => 'series[*]', 'value' => '也支持标准 name/value 数组'],
                ],
                [
                    'pie 在服务端支持关联数组写法，也支持标准数组写法，showcase 里必须把这两种都讲清楚。',
                    '开发者如果已有聚合结果 `名称 => 数值`，可以直接传，不必先手动组装每项的 name 和 value。',
                ],
                <<<'CODE'
[
    'type' => 'pie',
    'series' => [
        '普通会员' => 420,
        '银卡会员' => 260,
        '金卡会员' => 160,
        '黑金会员' => 60,
    ],
]
CODE,
                <<<'CODE'
use app\common\render\Chart;

Chart::make('member_level_pie')
    ->title('会员等级构成')
    ->type('pie')
    ->series([
        '普通会员' => 420,
        '银卡会员' => 260,
        '金卡会员' => 160,
        '黑金会员' => 60,
    ]);
CODE,
                [
                    ['path' => 'app/common/abstract/ChartType.php', 'label' => 'ChartType 抽象基类', 'description' => 'normalizePieSeries() 负责兼容多种 pie series 输入格式。'],
                ]
            ),
        ];

        return $this->makePage(
            $component,
            [
                'summary' => '饼图适合展示整体中各部分占比，重点在于让开发者看懂不同数据结构的写法，以及普通饼图和环形图如何互转。',
                'scenarios' => [
                    '来源构成、状态分布、等级比例等占比型场景',
                    '运营看板中需要快速感知“哪一类最多”时的总览图',
                    '已有聚合结果需要直接映射成占比图的管理后台页面',
                ],
                'capabilities' => [
                    '基础饼图',
                    '环形饼图',
                    '多种 series 写法',
                    '构成占比展示',
                ],
                'quick_start' => [
                    'array_code' => <<<'CODE'
[
    'type' => 'pie',
    'series' => [
        ['name' => '自然搜索', 'value' => 335],
        ['name' => '广告投放', 'value' => 310],
    ],
]
CODE,
                    'chart_code' => <<<'CODE'
use app\common\render\Chart;

Chart::make('source_pie')
    ->type('pie')
    ->series([
        ['name' => '自然搜索', 'value' => 335],
        ['name' => '广告投放', 'value' => 310],
    ]);
CODE,
                ],
            ],
            [
                [
                    'title' => '基础参数',
                    'items' => [
                        ['name' => 'type', 'summary' => '固定写 `pie`。'],
                        ['name' => 'series', 'summary' => '支持标准 name/value 数组、带 data 的完整 series，或关联数组快捷写法。'],
                    ],
                ],
                [
                    'title' => '高频扩展',
                    'items' => [
                        ['name' => 'series[*].radius', 'summary' => '控制普通饼图或环形图半径。'],
                        ['name' => 'option.legend', 'summary' => '覆盖图例位置、显隐和排版。'],
                    ],
            ],
            ],
            $sections,
            $this->relatedComponents((string) ($component['key'] ?? ''))
        );
    }
}
