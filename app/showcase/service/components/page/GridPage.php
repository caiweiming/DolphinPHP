<?php
declare(strict_types=1);

namespace app\showcase\service\components\page;

/**
 * grid 页面详情页
 */
final class GridPage extends AbstractPageComponent
{
    /**
     * @param array<string, mixed> $component
     * @return array<string, mixed>
     */
    public function build(array $component): array
    {
        $gridCards = [
            '<div class="card"><div class="card-body">订单趋势</div></div>',
            '<div class="card"><div class="card-body">渠道来源</div></div>',
            '<div class="card"><div class="card-body">库存预警</div></div>',
            '<div class="card"><div class="card-body">退款处理</div></div>',
        ];

        $sections = [
            $this->makeSection(
                'fixed-cols',
                '固定列数',
                '传入整数列数时，grid() 会自动把内容拆成等宽网格，适合数量稳定的统计卡片区域。',
                $this->renderPagePreview(function ($page) use ($gridCards): void {
                    $page->title('固定 2 列网格');
                    $page->grid($gridCards, 2);
                }),
                [
                    ['name' => 'cols', 'value' => '2'],
                ],
                [
                    '内部会生成 row-cols-2，按每行两列平均铺开。',
                ],
                <<<'CODE'
[
    'grid' => [
        'items' => ['订单趋势', '渠道来源', '库存预警', '退款处理'],
        'cols' => 2,
    ],
]
CODE,
                <<<'CODE'
use app\common\render\Page;

Page::make('grid_fixed')
    ->grid([
        '<div class="card"><div class="card-body">订单趋势</div></div>',
        '<div class="card"><div class="card-body">渠道来源</div></div>',
        '<div class="card"><div class="card-body">库存预警</div></div>',
        '<div class="card"><div class="card-body">退款处理</div></div>',
    ], 2);
CODE,
                [
                    ['path' => 'app/common/render/Page.php', 'label' => 'grid() 固定列实现', 'description' => 'row-cols-N 的组装逻辑。'],
                ]
            ),
            $this->makeSection(
                'responsive-cols',
                '响应式断点列数',
                '传入断点数组时，grid() 会在不同屏宽下自动切换列数，是后台概览页最常用的写法。',
                $this->renderPagePreview(function ($page) use ($gridCards): void {
                    $page->title('响应式网格');
                    $page->grid($gridCards, ['md' => 2, 'xl' => 4], ['class' => 'g-3']);
                }),
                [
                    ['name' => 'cols', 'value' => "['md' => 2, 'xl' => 4]"],
                    ['name' => 'attr.class', 'value' => "'g-3'"],
                ],
                [
                    '这是 Dashboard 和多图表页面最实用的方式，手机端会自然折成单列。',
                ],
                <<<'CODE'
[
    'grid' => [
        'items' => ['订单趋势', '渠道来源', '库存预警', '退款处理'],
        'cols' => ['md' => 2, 'xl' => 4],
        'attr' => ['class' => 'g-3'],
    ],
]
CODE,
                <<<'CODE'
use app\common\render\Page;

Page::make('grid_responsive')
    ->grid([
        '<div class="card"><div class="card-body">订单趋势</div></div>',
        '<div class="card"><div class="card-body">渠道来源</div></div>',
        '<div class="card"><div class="card-body">库存预警</div></div>',
        '<div class="card"><div class="card-body">退款处理</div></div>',
    ], ['md' => 2, 'xl' => 4], ['class' => 'g-3']);
CODE,
                [
                    ['path' => 'app/common/render/Page.php', 'label' => 'grid() 响应式实现', 'description' => 'row-cols-md-N / row-cols-xl-N 的组装逻辑。'],
                ]
            ),
            $this->makeSection(
                'grid-with-renderers',
                'grid() 承载真实渲染器块',
                'grid() 不只是放静态卡片，也可以直接承载真正的 Chart 或 HTML 摘要块，用于快速搭建总览页矩阵。',
                $this->renderPagePreview(function ($page): void {
                    $chartA = $this->demoChart('showcase_page_grid_chart_a', ['周一', '周二', '周三'], [
                        ['name' => '访问量', 'data' => [120, 156, 132]],
                    ]);
                    $chartB = $this->demoChart('showcase_page_grid_chart_b', ['周一', '周二', '周三'], [
                        ['name' => '下单量', 'data' => [28, 35, 31]],
                    ]);

                    $page->title('图表矩阵');
                    $page->grid([
                        $chartA,
                        $chartB,
                        '<div class="card"><div class="card-body">库存预警 12 条</div></div>',
                        '<div class="card"><div class="card-body">待复核退款 4 笔</div></div>',
                    ], ['md' => 2, 'xl' => 4], ['class' => 'g-3']);
                }),
                [
                    ['name' => 'items', 'value' => 'Chart + HTML 混合'],
                    ['name' => 'cols', 'value' => "['md' => 2, 'xl' => 4]"],
                ],
                [
                    '只要内容最终可渲染成字符串，grid() 就可以把它们按统一矩阵摆放。',
                ],
                <<<'CODE'
[
    'items' => ['$chartA', '$chartB', 'summaryCard', 'warningCard'],
    'cols' => ['md' => 2, 'xl' => 4],
]
CODE,
                <<<'CODE'
Page::make('overview')
    ->grid([
        $chartA,
        $chartB,
        '<div class="card"><div class="card-body">库存预警 12 条</div></div>',
        '<div class="card"><div class="card-body">待复核退款 4 笔</div></div>',
    ], ['md' => 2, 'xl' => 4], ['class' => 'g-3']);
CODE,
                [
                    ['path' => 'docs/图表/chart.md', 'label' => '图表与 Page 组合说明', 'description' => 'Chart 通常通过 page->row()/grid() 承载。'],
                ]
            ),
        ];

        return $this->makePage(
            $component,
            [
                'summary' => 'grid() 是 Page 上最适合做概览卡片、图表矩阵、指标宫格的能力。它解决的是“等宽铺开”问题，不用手写每一列宽度。',
                'scenarios' => [
                    '首页 Dashboard 指标卡片',
                    '多个图表的等宽矩阵布局',
                    '若干说明卡片、告警卡片的统一排版',
                ],
                'capabilities' => ['固定列数', '响应式断点', 'row attrs'],
                'quick_start' => [
                    'array_code' => "[\n    'items' => ['卡片 A', '卡片 B', '卡片 C', '卡片 D'],\n    'cols' => ['md' => 2, 'xl' => 4],\n]",
                    'page_code' => "use app\\common\\render\\Page;\n\nPage::make('grid_demo')\n    ->grid([\n        '<div class=\"card\"><div class=\"card-body\">卡片 A</div></div>',\n        '<div class=\"card\"><div class=\"card-body\">卡片 B</div></div>',\n        '<div class=\"card\"><div class=\"card-body\">卡片 C</div></div>',\n        '<div class=\"card\"><div class=\"card-body\">卡片 D</div></div>',\n    ], ['md' => 2, 'xl' => 4]);",
                ],
            ],
            [
                [
                    'title' => 'grid 参数',
                    'items' => [
                        ['name' => 'items', 'summary' => '要均匀铺开的内容数组。'],
                        ['name' => 'cols', 'summary' => '固定整数列数，或断点列数组。'],
                        ['name' => 'attr', 'summary' => '额外 row 属性。'],
                    ],
                ],
            ],
            $sections,
            $this->relatedComponents((string) ($component['key'] ?? ''))
        );
    }
}
