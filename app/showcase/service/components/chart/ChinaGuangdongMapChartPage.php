<?php
declare(strict_types=1);

namespace app\showcase\service\components\chart;

/**
 * 广东地图详情页
 */
final class ChinaGuangdongMapChartPage extends AbstractChartPage
{
    /**
     * @param array<string, mixed> $component
     * @return array<string, mixed>
     */
    public function build(array $component): array
    {
        $sections = [
            $this->makeSection(
                'city_heatmap',
                '地市热力分布',
                '用地市级 regions 展示省内业务分布，是最常见的省级地图总览场景。',
                $this->renderChart(function ($chart): void {
                    $chart
                        ->title('广东地市销售热力')
                        ->height(420)
                        ->map('china.guangdong', [
                            'regions' => [
                                ['code' => '440100', 'value' => 96],
                                ['code' => '440300', 'value' => 98],
                                ['code' => '440600', 'value' => 82],
                                ['code' => '441900', 'value' => 88],
                                ['code' => '440400', 'value' => 74],
                            ],
                            'visualMap' => [
                                'min' => 0,
                                'max' => 100,
                                'left' => 'left',
                                'bottom' => 8,
                                'text' => ['高', '低'],
                            ],
                            'view' => [
                                'roam' => false,
                                'zoom' => 1,
                            ],
                        ]);
                }),
                [
                    ['name' => 'map', 'value' => 'china.guangdong'],
                    ['name' => 'regions', 'value' => '使用地市 code 或 name'],
                    ['name' => 'visualMap', 'value' => '省内城市色阶'],
                ],
                [
                    '广东地图适合展示省内城市维度的业务分布，是地图专题中最接近真实业务报表的一类示例。',
                    'Provider 已经内置地市编码、别名和坐标，开发者只要专注在业务指标本身即可。',
                ],
                <<<'CODE'
[
    'map' => 'china.guangdong',
    'regions' => [
        ['code' => '440100', 'value' => 96],
        ['code' => '440300', 'value' => 98],
        ['code' => '440600', 'value' => 82],
    ],
]
CODE,
                <<<'CODE'
use app\common\render\Chart;

Chart::make('guangdong_heatmap')
    ->title('广东地市销售热力')
    ->height(420)
    ->map('china.guangdong', [
        'regions' => [
            ['code' => '440100', 'value' => 96],
            ['code' => '440300', 'value' => 98],
            ['code' => '440600', 'value' => 82],
            ['code' => '441900', 'value' => 88],
            ['code' => '440400', 'value' => 74],
        ],
        'visualMap' => [
            'min' => 0,
            'max' => 100,
        ],
    ]);
CODE,
                [
                    ['path' => 'extend/chart_map/china/guangdong/Provider.php', 'label' => '广东地图 Provider', 'description' => '负责地市 code、别名和坐标归一化。'],
                    ['path' => 'public/extend/chart_map/china/guangdong/guangdong.geo.json', 'label' => '广东地图 GeoJSON', 'description' => '广东地市边界数据。'],
                ]
            ),
            $this->makeSection(
                'city_scatter',
                '重点城市散点',
                '通过 effectScatter 突出重点城市，适合展示重点门店、重点仓库或核心销售城市。',
                $this->renderChart(function ($chart): void {
                    $chart
                        ->title('重点城市散点')
                        ->height(420)
                        ->map('china.guangdong', [
                            'regions' => [
                                ['code' => '440100', 'value' => 96],
                                ['code' => '440300', 'value' => 98],
                                ['code' => '440600', 'value' => 82],
                            ],
                            'overlays' => [
                                [
                                    'type' => 'effectScatter',
                                    'symbolSize' => 16,
                                    'rippleEffect' => ['brushType' => 'stroke'],
                                    'data' => [
                                        ['code' => '440100', 'value' => 96],
                                        ['code' => '440300', 'value' => 98],
                                        ['code' => '441900', 'value' => 88],
                                    ],
                                ],
                            ],
                            'view' => ['roam' => false],
                        ]);
                }),
                [
                    ['name' => 'overlays[0].type', 'value' => 'effectScatter'],
                    ['name' => 'overlays[0].data', 'value' => '重点城市点位列表'],
                ],
                [
                    '省级地图上叠加散点，是最常见的“总览底图 + 重点点位”模式。',
                    'showcase 里把这类写法讲清楚后，开发者就能很快迁移到门店分布、仓库分布等真实场景。',
                ],
                <<<'CODE'
[
    'map' => 'china.guangdong',
    'overlays' => [[
        'type' => 'effectScatter',
        'data' => [
            ['code' => '440100', 'value' => 96],
            ['code' => '440300', 'value' => 98],
            ['code' => '441900', 'value' => 88],
        ],
    ]],
]
CODE,
                <<<'CODE'
use app\common\render\Chart;

Chart::make('guangdong_focus_points')
    ->height(420)
    ->map('china.guangdong', [
        'regions' => [
            ['code' => '440100', 'value' => 96],
            ['code' => '440300', 'value' => 98],
            ['code' => '440600', 'value' => 82],
        ],
        'overlays' => [[
            'type' => 'effectScatter',
            'symbolSize' => 16,
            'data' => [
                ['code' => '440100', 'value' => 96],
                ['code' => '440300', 'value' => 98],
                ['code' => '441900', 'value' => 88],
            ],
        ]],
    ]);
CODE,
                [
                    ['path' => 'extend/chart_map/china/guangdong/Provider.php', 'label' => '广东地图 Provider', 'description' => '覆盖物点位坐标由 Provider 自动补齐。'],
                    ['path' => 'public/extend/chart_map/china/guangdong/guangdong.js', 'label' => '广东地图前端 hook', 'description' => 'tooltip 与强调态修饰。'],
                ]
            ),
            $this->makeSection(
                'business_lines',
                '业务流向飞线',
                '通过 fromCode / toCode 展示业务从广州辐射到各城市的流向。',
                $this->renderChart(function ($chart): void {
                    $chart
                        ->title('业务流向飞线')
                        ->height(420)
                        ->map('china.guangdong', [
                            'regions' => [
                                ['code' => '440100', 'value' => 96],
                                ['code' => '440300', 'value' => 98],
                                ['code' => '440600', 'value' => 82],
                                ['code' => '441900', 'value' => 88],
                                ['code' => '440400', 'value' => 74],
                            ],
                            'overlays' => [
                                [
                                    'type' => 'lines',
                                    'effect' => ['show' => true, 'symbol' => 'arrow', 'trailLength' => 0.15],
                                    'lineStyle' => ['width' => 2, 'curveness' => 0.18],
                                    'data' => [
                                        ['fromCode' => '440100', 'toCode' => '440300', 'value' => 68],
                                        ['fromCode' => '440100', 'toCode' => '441900', 'value' => 42],
                                        ['fromCode' => '440100', 'toCode' => '440400', 'value' => 35],
                                    ],
                                ],
                            ],
                            'view' => ['roam' => false],
                        ]);
                }),
                [
                    ['name' => 'overlays[0].type', 'value' => 'lines'],
                    ['name' => 'fromCode / toCode', 'value' => '城市间流向定义'],
                ],
                [
                    '业务流向飞线最能体现地图专项和普通图表的差异：坐标、GeoJSON、覆盖物都交给 Provider 协议来处理。',
                    '如果需要在异步筛选后继续叠加更多流向，后续可以直接结合 mapData() 做增量合并。',
                ],
                <<<'CODE'
[
    'map' => 'china.guangdong',
    'overlays' => [[
        'type' => 'lines',
        'data' => [
            ['fromCode' => '440100', 'toCode' => '440300', 'value' => 68],
            ['fromCode' => '440100', 'toCode' => '441900', 'value' => 42],
        ],
    ]],
]
CODE,
                <<<'CODE'
use app\common\render\Chart;

Chart::make('guangdong_business_lines')
    ->height(420)
    ->map('china.guangdong', [
        'regions' => [
            ['code' => '440100', 'value' => 96],
            ['code' => '440300', 'value' => 98],
            ['code' => '440600', 'value' => 82],
            ['code' => '441900', 'value' => 88],
            ['code' => '440400', 'value' => 74],
        ],
        'overlays' => [[
            'type' => 'lines',
            'effect' => ['show' => true, 'symbol' => 'arrow'],
            'lineStyle' => ['width' => 2, 'curveness' => 0.18],
            'data' => [
                ['fromCode' => '440100', 'toCode' => '440300', 'value' => 68],
                ['fromCode' => '440100', 'toCode' => '441900', 'value' => 42],
                ['fromCode' => '440100', 'toCode' => '440400', 'value' => 35],
            ],
        ]],
    ]);
CODE,
                [
                    ['path' => 'extend/chart_map/china/guangdong/Provider.php', 'label' => '广东地图 Provider', 'description' => '负责飞线起终点 coords 组装。'],
                    ['path' => 'public/extend/chart_map/china/guangdong/guangdong.js', 'label' => '广东地图前端 hook', 'description' => '地图 tooltip 与强调色修饰。'],
                ]
            ),
        ];

        return $this->makePage(
            $component,
            [
                'summary' => '广东地图适合展示省内城市维度的业务分布、重点城市和业务流向，是更贴近真实业务报表的地图示例。',
                'scenarios' => [
                    '省内城市销售分布、订单分布、活跃门店分布',
                    '重点城市标记、重点客户或重点仓库展示',
                    '业务辐射流向、调拨流向或配送流向展示',
                ],
                'capabilities' => [
                    '地市热力分布',
                    '重点城市散点',
                    '业务流向飞线',
                    'mapData 增量合并',
                ],
                'quick_start' => [
                    'array_code' => <<<'CODE'
[
    'map' => 'china.guangdong',
    'regions' => [
        ['code' => '440100', 'value' => 96],
        ['code' => '440300', 'value' => 98],
    ],
]
CODE,
                    'chart_code' => <<<'CODE'
use app\common\render\Chart;

Chart::make('guangdong_map')
    ->map('china.guangdong', [
        'regions' => [
            ['code' => '440100', 'value' => 96],
            ['code' => '440300', 'value' => 98],
        ],
    ]);
CODE,
                ],
            ],
            [
                [
                    'title' => '基础参数',
                    'items' => [
                        ['name' => 'map', 'summary' => '地图扩展 key，这里写 `china.guangdong`。'],
                        ['name' => 'regions', 'summary' => '地市业务数据，支持 code 或地市名称。'],
                        ['name' => 'visualMap', 'summary' => '城市热力分布色阶。'],
                    ],
                ],
                [
                    'title' => '覆盖物与视角',
                    'items' => [
                        ['name' => 'overlays', 'summary' => '支持 effectScatter、lines 等城市覆盖物。'],
                        ['name' => 'view', 'summary' => '控制缩放、中心点和是否允许拖拽。'],
                        ['name' => 'tooltip', 'summary' => '可覆盖地图 tooltip 文案与格式。'],
                    ],
                ],
            ],
            $sections,
            $this->relatedComponents((string) ($component['key'] ?? ''))
        );
    }
}
