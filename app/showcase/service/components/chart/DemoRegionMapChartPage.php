<?php
declare(strict_types=1);

namespace app\showcase\service\components\chart;

/**
 * demo_region 地图详情页
 */
final class DemoRegionMapChartPage extends AbstractChartPage
{
    /**
     * @param array<string, mixed> $component
     * @return array<string, mixed>
     */
    public function build(array $component): array
    {
        $sections = [
            $this->makeSection(
                'basic_map',
                '基础区域着色',
                '使用最小地图扩展包展示 regions + visualMap 的最基础写法。',
                $this->renderChart(function ($chart): void {
                    $chart
                        ->title('示例区域分布')
                        ->height(360)
                        ->map('demo_region', [
                            'regions' => [
                                ['code' => '1001', 'value' => 92],
                                ['code' => '1002', 'value' => 76],
                                ['code' => '1003', 'value' => 58],
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
                                'zoom' => 1.05,
                            ],
                        ]);
                }),
                [
                    ['name' => 'map', 'value' => 'demo_region'],
                    ['name' => 'regions', 'value' => "[['code' => '1001', 'value' => 92], ...]"],
                    ['name' => 'visualMap', 'value' => "['min' => 0, 'max' => 100]"],
                ],
                [
                    '示例区域地图是一个最小 GeoJSON 示意地图，不是中国行政区或真实业务地图。',
                    '它更适合先验证地图协议是否打通，而不是一开始就接真实行政区地图。',
                    'regions 支持 code 或 name，两者最终都会被 Provider 归一化为 GeoJSON 里的区域名。',
                ],
                <<<'CODE'
[
    'map' => 'demo_region',
    'regions' => [
        ['code' => '1001', 'value' => 92],
        ['code' => '1002', 'value' => 76],
        ['code' => '1003', 'value' => 58],
    ],
]
CODE,
                <<<'CODE'
use app\common\render\Chart;

Chart::make('demo_region_map')
    ->title('示例区域分布')
    ->height(360)
    ->map('demo_region', [
        'regions' => [
            ['code' => '1001', 'value' => 92],
            ['code' => '1002', 'value' => 76],
            ['code' => '1003', 'value' => 58],
        ],
        'visualMap' => [
            'min' => 0,
            'max' => 100,
            'left' => 'left',
            'bottom' => 8,
        ],
    ]);
CODE,
                [
                    ['path' => 'extend/chart_map/demo_region/Provider.php', 'label' => 'demo_region Provider', 'description' => '负责 code / alias / overlays 的归一化处理。'],
                    ['path' => 'public/extend/chart_map/demo_region/demo_region.geo.json', 'label' => 'demo_region GeoJSON', 'description' => '地图区域边界来源。'],
                ]
            ),
            $this->makeSection(
                'effect_scatter_overlay',
                'effectScatter 覆盖物',
                '在地图区域基础上叠加动态散点，突出关键区域或重点站点。',
                $this->renderChart(function ($chart): void {
                    $chart
                        ->title('重点区域覆盖物')
                        ->height(360)
                        ->map('demo_region', [
                            'regions' => [
                                ['code' => '1001', 'value' => 92],
                                ['code' => '1002', 'value' => 76],
                                ['code' => '1003', 'value' => 58],
                            ],
                            'overlays' => [
                                [
                                    'type' => 'effectScatter',
                                    'rippleEffect' => ['brushType' => 'stroke'],
                                    'symbolSize' => 18,
                                    'data' => [
                                        ['code' => '1001', 'value' => 92],
                                        ['code' => '1002', 'value' => 76],
                                    ],
                                ],
                            ],
                            'view' => ['roam' => false],
                        ]);
                }),
                [
                    ['name' => 'overlays[0].type', 'value' => 'effectScatter'],
                    ['name' => 'overlays[0].data', 'value' => '支持 code 或 name'],
                ],
                [
                    '地图覆盖物的坐标可以不手写，Provider 会按区域名称或编码自动补齐 regionCoords。',
                    'effectScatter 很适合在 showcae 里直观展示“地图不仅能填色，还能叠加点位”。',
                ],
                <<<'CODE'
[
    'map' => 'demo_region',
    'overlays' => [[
        'type' => 'effectScatter',
        'data' => [
            ['code' => '1001', 'value' => 92],
            ['code' => '1002', 'value' => 76],
        ],
    ]],
]
CODE,
                <<<'CODE'
use app\common\render\Chart;

Chart::make('demo_region_points')
    ->height(360)
    ->map('demo_region', [
        'regions' => [
            ['code' => '1001', 'value' => 92],
            ['code' => '1002', 'value' => 76],
            ['code' => '1003', 'value' => 58],
        ],
        'overlays' => [[
            'type' => 'effectScatter',
            'rippleEffect' => ['brushType' => 'stroke'],
            'symbolSize' => 18,
            'data' => [
                ['code' => '1001', 'value' => 92],
                ['code' => '1002', 'value' => 76],
            ],
        ]],
    ]);
CODE,
                [
                    ['path' => 'extend/chart_map/demo_region/Provider.php', 'label' => 'demo_region Provider', 'description' => 'normalizeOverlayData() 负责覆盖物点位补坐标。'],
                ]
            ),
            $this->makeSection(
                'lines_overlay',
                'lines 飞线覆盖物',
                '用 fromCode / toCode 表达区域间流向，适合订单流向、调拨流向或访问来源去向。',
                $this->renderChart(function ($chart): void {
                    $chart
                        ->title('区域流向')
                        ->height(360)
                        ->map('demo_region', [
                            'regions' => [
                                ['code' => '1001', 'value' => 92],
                                ['code' => '1002', 'value' => 76],
                                ['code' => '1003', 'value' => 58],
                            ],
                            'overlays' => [
                                [
                                    'type' => 'lines',
                                    'effect' => ['show' => true, 'symbol' => 'arrow', 'trailLength' => 0.15],
                                    'lineStyle' => ['width' => 2, 'curveness' => 0.2],
                                    'data' => [
                                        ['fromCode' => '1001', 'toCode' => '1002', 'value' => 38],
                                        ['fromCode' => '1002', 'toCode' => '1003', 'value' => 24],
                                    ],
                                ],
                            ],
                            'view' => ['roam' => false],
                        ]);
                }),
                [
                    ['name' => 'overlays[0].type', 'value' => 'lines'],
                    ['name' => 'fromCode / toCode', 'value' => '通过 Provider 自动转换为 coords'],
                ],
                [
                    '飞线数据不需要手写经纬度，地图专项 Provider 会把 from/to 的 code 或 name 转成 coords。',
                    '这类示例能让开发者直接看到 map() 和普通图表最大的差异：它是“地图协议”，不是“type(map)”语法糖。',
                ],
                <<<'CODE'
[
    'map' => 'demo_region',
    'overlays' => [[
        'type' => 'lines',
        'data' => [
            ['fromCode' => '1001', 'toCode' => '1002', 'value' => 38],
            ['fromCode' => '1002', 'toCode' => '1003', 'value' => 24],
        ],
    ]],
]
CODE,
                <<<'CODE'
use app\common\render\Chart;

Chart::make('demo_region_lines')
    ->height(360)
    ->map('demo_region', [
        'regions' => [
            ['code' => '1001', 'value' => 92],
            ['code' => '1002', 'value' => 76],
            ['code' => '1003', 'value' => 58],
        ],
        'overlays' => [[
            'type' => 'lines',
            'effect' => ['show' => true, 'symbol' => 'arrow'],
            'lineStyle' => ['width' => 2, 'curveness' => 0.2],
            'data' => [
                ['fromCode' => '1001', 'toCode' => '1002', 'value' => 38],
                ['fromCode' => '1002', 'toCode' => '1003', 'value' => 24],
            ],
        ]],
    ]);
CODE,
                [
                    ['path' => 'extend/chart_map/demo_region/Provider.php', 'label' => 'demo_region Provider', 'description' => 'normalizeOverlayData() 负责飞线 coords 组装。'],
                    ['path' => 'public/extend/chart_map/demo_region/demo_region.js', 'label' => 'demo_region 前端 hook', 'description' => '示例地图的边框强调态处理。'],
                ]
            ),
        ];

        return $this->makePage(
            $component,
            [
                'summary' => '示例区域地图是一个最小 GeoJSON 示意地图，帮助开发者先验证 map() 协议与 Provider 链路；它不是中国行政区或真实业务地图。',
                'scenarios' => [
                    '在接入真实行政区地图前，先验证地图协议是否打通',
                    '学习 regions、visualMap、effectScatter、lines 的最小写法',
                    '理解地图扩展包 Provider 如何处理别名、编码和坐标归一化',
                ],
                'capabilities' => [
                    '区域着色',
                    'effectScatter 覆盖物',
                    'lines 飞线覆盖物',
                    '最小地图协议',
                ],
                'quick_start' => [
                    'array_code' => <<<'CODE'
[
    'map' => 'demo_region',
    'regions' => [
        ['code' => '1001', 'value' => 92],
        ['code' => '1002', 'value' => 76],
    ],
]
CODE,
                    'chart_code' => <<<'CODE'
use app\common\render\Chart;

Chart::make('demo_region_map')
    ->map('demo_region', [
        'regions' => [
            ['code' => '1001', 'value' => 92],
            ['code' => '1002', 'value' => 76],
        ],
    ]);
CODE,
                ],
            ],
            [
                [
                    'title' => '基础参数',
                    'items' => [
                        ['name' => 'map', 'summary' => '地图扩展 key，这里固定写 `demo_region`。'],
                        ['name' => 'regions', 'summary' => '区域数据，可传 code 或 name。'],
                        ['name' => 'visualMap', 'summary' => '区域色阶配置。'],
                    ],
                ],
                [
                    'title' => '覆盖物参数',
                    'items' => [
                        ['name' => 'overlays', 'summary' => '支持 effectScatter、lines 等地图覆盖物。'],
                        ['name' => 'view', 'summary' => '控制 roam、zoom、center 等地图视角。'],
                    ],
                ],
            ],
            $sections,
            $this->relatedComponents((string) ($component['key'] ?? ''))
        );
    }
}
