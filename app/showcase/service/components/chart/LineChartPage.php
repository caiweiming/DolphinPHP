<?php
declare(strict_types=1);

namespace app\showcase\service\components\chart;

/**
 * line 图表详情页
 */
final class LineChartPage extends AbstractChartPage
{
    // source_refs 会被控制器抽取为“本节源码”和右侧“源码参考”两类入口。

    /**
     * @param array<string, mixed> $component
     * @return array<string, mixed>
     */
    public function build(array $component): array
    {
        $sections = [
            $this->makeSection(
                'basic_line',
                '基础折线图',
                '单系列趋势线，用于最常见的时间走势和数值变化分析。',
                $this->renderChart(function ($chart): void {
                    $chart
                        ->title('近 7 日访问趋势')
                        ->type('line')
                        ->categories(['周一', '周二', '周三', '周四', '周五', '周六', '周日'])
                        ->series([
                            ['name' => '访问量', 'data' => [120, 132, 101, 134, 90, 230, 210]],
                        ]);
                }),
                [
                    ['name' => 'type', 'value' => 'line'],
                    ['name' => 'categories', 'value' => "['周一', '周二', '周三', '周四', '周五', '周六', '周日']"],
                    ['name' => 'series', 'value' => "[['name' => '访问量', 'data' => [120, 132, 101, 134, 90, 230, 210]]]"],
                ],
                [
                    '最适合先验证 categories 与 series 是否已经接通。',
                    '如果只是简单趋势线，先不要急着覆盖太多 option。',
                ],
                <<<'CODE'
[
    'type' => 'line',
    'categories' => ['周一', '周二', '周三', '周四', '周五', '周六', '周日'],
    'series' => [
        ['name' => '访问量', 'data' => [120, 132, 101, 134, 90, 230, 210]],
    ],
]
CODE,
                <<<'CODE'
use app\common\render\Chart;

Chart::make('visit_trend')
    ->title('近 7 日访问趋势')
    ->type('line')
    ->categories(['周一', '周二', '周三', '周四', '周五', '周六', '周日'])
    ->series([
        ['name' => '访问量', 'data' => [120, 132, 101, 134, 90, 230, 210]],
    ]);
CODE,
                [
                    ['path' => 'app/common/render/Chart.php', 'label' => 'Chart 构建器源码', 'description' => 'categories / series / title 等通用能力入口。'],
                    ['path' => 'app/common/render/chart/type/Line.php', 'label' => 'line 类型构建器', 'description' => 'line 图表默认 option 生成逻辑。'],
                ]
            ),
            $this->makeSection(
                'multi_series',
                '多系列折线',
                '适合对比多个指标在同一时间序列下的变化。',
                $this->renderChart(function ($chart): void {
                    $chart
                        ->title('渠道对比趋势', '近 7 日 UV')
                        ->type('line')
                        ->categories(['周一', '周二', '周三', '周四', '周五', '周六', '周日'])
                        ->series([
                            ['name' => '官网', 'data' => [150, 182, 191, 234, 290, 330, 310]],
                            ['name' => '小程序', 'data' => [120, 110, 145, 160, 190, 240, 280]],
                        ]);
                }),
                [
                    ['name' => 'series[0]', 'value' => '官网'],
                    ['name' => 'series[1]', 'value' => '小程序'],
                ],
                [
                    '多系列时建议保留 legend，方便开发者看清每条线对应哪个业务指标。',
                    '如果只是对比两个系列，先让颜色差异明显，不要过度定制。',
                ],
                <<<'CODE'
[
    'type' => 'line',
    'series' => [
        ['name' => '官网', 'data' => [150, 182, 191, 234, 290, 330, 310]],
        ['name' => '小程序', 'data' => [120, 110, 145, 160, 190, 240, 280]],
    ],
]
CODE,
                <<<'CODE'
use app\common\render\Chart;

Chart::make('channel_trend')
    ->title('渠道对比趋势', '近 7 日 UV')
    ->type('line')
    ->categories(['周一', '周二', '周三', '周四', '周五', '周六', '周日'])
    ->series([
        ['name' => '官网', 'data' => [150, 182, 191, 234, 290, 330, 310]],
        ['name' => '小程序', 'data' => [120, 110, 145, 160, 190, 240, 280]],
    ]);
CODE,
                [
                    ['path' => 'app/common/render/chart/type/Line.php', 'label' => 'line 类型构建器', 'description' => '多系列折线仍由相同类型构建器承接。'],
                ]
            ),
            $this->makeSection(
                'option_native_line',
                'option 原生覆盖',
                '通过 series 和 option 直接补充平滑曲线、面积填充和坐标轴细节，适合告诉开发者 DSL 不够时怎么继续往下写。',
                $this->renderChart(function ($chart): void {
                    $chart
                        ->title('平滑面积趋势')
                        ->type('line')
                        ->categories(['周一', '周二', '周三', '周四', '周五', '周六', '周日'])
                        ->series([
                            [
                                'name' => '支付金额',
                                'smooth' => true,
                                'lineStyle' => ['width' => 3],
                                'areaStyle' => ['opacity' => 0.18],
                                'data' => [98, 126, 158, 210, 268, 246, 288],
                            ],
                        ])
                        ->option([
                            'yAxis' => [
                                'type' => 'value',
                                'splitLine' => ['lineStyle' => ['type' => 'dashed']],
                            ],
                        ]);
                }),
                [
                    ['name' => 'series[0].smooth', 'value' => 'true'],
                    ['name' => 'series[0].areaStyle', 'value' => "['opacity' => 0.18]"],
                    ['name' => 'option.yAxis', 'value' => '覆盖默认坐标轴细节'],
                ],
                [
                    'series 支持直接透传 lineStyle、smooth、areaStyle 等原生字段。',
                    '如果开发者只是想补平滑、面积或坐标轴样式，不需要等待框架再额外封装一个 DSL。',
                ],
                <<<'CODE'
[
    'type' => 'line',
    'series' => [[
        'name' => '支付金额',
        'smooth' => true,
        'lineStyle' => ['width' => 3],
        'areaStyle' => ['opacity' => 0.18],
        'data' => [98, 126, 158, 210, 268, 246, 288],
    ]],
    'option' => [
        'yAxis' => [
            'type' => 'value',
            'splitLine' => ['lineStyle' => ['type' => 'dashed']],
        ],
    ],
]
CODE,
                <<<'CODE'
use app\common\render\Chart;

Chart::make('payment_trend_line')
    ->title('平滑面积趋势')
    ->type('line')
    ->categories(['周一', '周二', '周三', '周四', '周五', '周六', '周日'])
    ->series([
        [
            'name' => '支付金额',
            'smooth' => true,
            'lineStyle' => ['width' => 3],
            'areaStyle' => ['opacity' => 0.18],
            'data' => [98, 126, 158, 210, 268, 246, 288],
        ],
    ])
    ->option([
        'yAxis' => [
            'type' => 'value',
            'splitLine' => ['lineStyle' => ['type' => 'dashed']],
        ],
    ]);
CODE,
                [
                    ['path' => 'app/common/render/Chart.php', 'label' => 'Chart 构建器源码', 'description' => 'series() 和 option() 可继续承接 line 原生配置。'],
                ]
            ),
            $this->makeSection(
                'ajax_dataset',
                'Ajax 数据源',
                '通过 dataset 指向接口，由前端异步拉取图表数据。',
                $this->renderChart(function ($chart): void {
                    $chart
                        ->title('Ajax 趋势图')
                        ->type('line')
                        ->dataset($this->demoUrl('line'))
                        ->loading(['show' => true, 'text' => '正在拉取趋势数据...']);
                }),
                [
                    ['name' => 'dataset', 'value' => "url('showcase/admin.demoApi/chart', ['dataset' => 'line'])"],
                    ['name' => 'datasetMerge', 'value' => 'true'],
                ],
                [
                    '适合图表初始不想直接内联大数据时使用。',
                    '接口返回结构要和 Chart 当前协议保持一致。',
                ],
                <<<'CODE'
[
    'dataset' => [
        'url' => url('showcase/admin.demoApi/chart', ['dataset' => 'line']),
        'method' => 'GET',
    ],
]
CODE,
                <<<'CODE'
use app\common\render\Chart;

Chart::make('trend_ajax')
    ->title('Ajax 趋势图')
    ->type('line')
    ->dataset((string) url('showcase/admin.demoApi/chart', ['dataset' => 'line']));
CODE,
                [
                    ['path' => 'app/common/render/Chart.php', 'label' => 'Chart 构建器源码', 'description' => 'dataset / datasetMerge 等数据源能力入口。'],
                    ['path' => 'app/showcase/controller/admin/DemoApi.php', 'label' => 'Showcase Demo API', 'description' => '图表示例所需的异步数据接口。'],
                ]
            ),
            $this->makeSection(
                'dataset_replace',
                'datasetMerge(false)',
                '当接口直接返回完整图表结果时，可以关闭合并模式，让远程响应完全决定最终效果。',
                $this->renderChart(function ($chart): void {
                    $chart
                        ->title('接口完整接管图表')
                        ->type('line')
                        ->dataset($this->demoUrl('line'))
                        ->datasetMerge(false)
                        ->loading(['show' => true, 'text' => '正在加载完整图表配置...']);
                }),
                [
                    ['name' => 'dataset', 'value' => "url('showcase/admin.demoApi/chart', ['dataset' => 'line'])"],
                    ['name' => 'datasetMerge', 'value' => 'false'],
                ],
                [
                    'datasetMerge(false) 适合接口直接返回完整 categories / series 或整套 option 的场景。',
                    '如果你发现接口返回后还残留了本地默认配置，先检查是否忘了关闭合并模式。',
                ],
                <<<'CODE'
[
    'dataset' => [
        'url' => url('showcase/admin.demoApi/chart', ['dataset' => 'line']),
        'method' => 'GET',
    ],
    'dataset_merge' => false,
]
CODE,
                <<<'CODE'
use app\common\render\Chart;

Chart::make('trend_replace_ajax')
    ->title('接口完整接管图表')
    ->type('line')
    ->dataset((string) url('showcase/admin.demoApi/chart', ['dataset' => 'line']))
    ->datasetMerge(false)
    ->loading(['show' => true, 'text' => '正在加载完整图表配置...']);
CODE,
                [
                    ['path' => 'app/common/render/Chart.php', 'label' => 'Chart 构建器源码', 'description' => 'datasetMerge(false) 用于关闭默认合并策略。'],
                    ['path' => 'app/showcase/controller/admin/DemoApi.php', 'label' => 'Showcase Demo API', 'description' => '这里的返回结构可直接作为 datasetMerge(false) 参考。'],
                ]
            ),
            $this->makeSection(
                'loading_empty',
                'empty 与 loading 联动',
                '当接口较慢或暂无数据时，需要给开发者看到明确的占位反馈。',
                $this->renderChart(function ($chart): void {
                    $chart
                        ->title('暂无趋势数据')
                        ->type('line')
                        ->dataset($this->demoUrl('empty'))
                        ->loading(['show' => true, 'text' => '正在检查是否有趋势数据...'])
                        ->empty([
                            'title' => '暂无趋势数据',
                            'description' => '当前周期没有可展示指标',
                        ]);
                }),
                [
                    ['name' => 'loading', 'value' => "['show' => true, 'text' => '加载中...']"],
                    ['name' => 'empty', 'value' => "['title' => '暂无趋势数据']"],
                ],
                [
                    '这类能力更偏图表级通用配置，实际写法可直接抄到其他类型图表中。',
                    '建议和 dataset 场景一起阅读，理解异步状态配置。',
                ],
                <<<'CODE'
[
    'loading' => ['show' => true, 'text' => '加载中...'],
    'empty' => ['title' => '暂无趋势数据', 'description' => '当前周期没有可展示指标'],
]
CODE,
                <<<'CODE'
use app\common\render\Chart;

Chart::make('trend_state')
    ->type('line')
    ->loading(['show' => true, 'text' => '加载中...'])
    ->empty([
        'title' => '暂无趋势数据',
        'description' => '当前周期没有可展示指标',
    ]);
CODE,
                [
                    ['path' => 'app/common/render/Chart.php', 'label' => 'Chart 构建器源码', 'description' => 'loading / empty 等图表状态能力入口。'],
                ]
            ),
        ];

        return $this->makePage(
            $component,
            [
                'summary' => '折线图适合展示随时间或序列变化的趋势，是后台分析、运营报表和业务看板中最常见的基础图表。',
                'scenarios' => [
                    '访问量、订单量、GMV 等随时间变化的趋势观察',
                    '多渠道、多指标在同一时间轴上的对比',
                    '需要同时承接静态数据与 Ajax 数据源的看板页',
                ],
                'capabilities' => [
                    '趋势展示',
                    '多系列对比',
                    'option 原生覆盖',
                    'Ajax 数据源',
                    'datasetMerge(false)',
                    'loading / empty 状态',
                ],
                'quick_start' => [
                    'array_code' => <<<'CODE'
[
    'type' => 'line',
    'categories' => ['周一', '周二', '周三'],
    'series' => [
        ['name' => '访问量', 'data' => [120, 132, 101]],
    ],
]
CODE,
                    'chart_code' => <<<'CODE'
use app\common\render\Chart;

Chart::make('visit_trend')
    ->type('line')
    ->categories(['周一', '周二', '周三'])
    ->series([
        ['name' => '访问量', 'data' => [120, 132, 101]],
    ]);
CODE,
                ],
            ],
            [
                [
                    'title' => '基础参数',
                    'items' => [
                        ['name' => 'type', 'summary' => '固定写 `line`。'],
                        ['name' => 'categories', 'summary' => 'X 轴类目数组。'],
                        ['name' => 'series', 'summary' => '系列数组，每项包含 name、data 等字段。'],
                    ],
                ],
                [
                    'title' => '配套能力',
                    'items' => [
                        ['name' => 'dataset', 'summary' => '异步数据源地址。'],
                        ['name' => 'loading / empty', 'summary' => '控制加载态与空态。'],
                    ],
            ],
            ],
            $sections,
            $this->relatedComponents((string) ($component['key'] ?? ''))
        );
    }
}
