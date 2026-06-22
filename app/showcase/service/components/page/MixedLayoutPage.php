<?php
declare(strict_types=1);

namespace app\showcase\service\components\page;

use app\common\render\form\Field;

/**
 * 混合布局详情页
 */
final class MixedLayoutPage extends AbstractPageComponent
{
    /**
     * @param array<string, mixed> $component
     * @return array<string, mixed>
     */
    public function build(array $component): array
    {
        $miniForm = $this->demoForm('showcase_page_mixed_form', '筛选条件', [
            Field::text('keyword', '关键词', '请输入订单号或用户名'),
            Field::select('status', '状态')->options([1 => '已支付', 0 => '待支付']),
        ], [
            'keyword' => 'DP20260618',
            'status' => 1,
        ]);

        $miniTable = $this->demoTable('showcase_page_mixed_table', [
            ['id' => 1, 'name' => '订单 1001', 'status' => 1],
            ['id' => 2, 'name' => '订单 1002', 'status' => 0],
        ], [
            ['id', 'ID'],
            ['name', '订单标题'],
            ['status', '状态', 'status', ['待支付:warning', '已支付:success']],
        ]);

        $miniChart = $this->demoChart('showcase_page_mixed_chart', ['周一', '周二', '周三'], [
            ['name' => '订单量', 'data' => [32, 41, 53]],
        ]);

        $sections = [
            $this->makeSection(
                'form-table-chart',
                '同页承载多种渲染器',
                'Page 的核心价值不是自己提供复杂控件，而是作为容器把 Form、Table、Chart 等内容稳定组织在同一页。',
                $this->renderPagePreview(function ($page) use ($miniForm, $miniTable, $miniChart): void {
                    $page->title('运营工作台');
                    $page->row([
                        [$miniForm, 'md-4'],
                        [$miniChart, 'md-8'],
                    ]);
                    $page->row([
                        [$miniTable, 'md-12'],
                    ]);
                }),
                [
                    ['name' => 'row 1', 'value' => 'Form + Chart'],
                    ['name' => 'row 2', 'value' => 'Table'],
                ],
                [
                    'Form / Table / Chart 都是独立渲染器，Page 负责页面布局和承载顺序。',
                ],
                <<<'CODE'
[
    'rows' => [
        ['form', 'chart'],
        ['table'],
    ],
]
CODE,
                <<<'CODE'
use app\common\render\Page;

Page::make('workspace')
    ->title('运营工作台')
    ->row([
        [$form, 'md-4'],
        [$chart, 'md-8'],
    ])
    ->row([
        [$table, 'md-12'],
    ]);
CODE,
                [
                    ['path' => 'app/admin/controller/Profile.php', 'label' => 'Profile 组合页参考', 'description' => 'Page 承载多个 Form 的真实案例。'],
                    ['path' => 'app/admin/controller/Config.php', 'label' => 'Config 组合页参考', 'description' => 'Page + Table 的真实案例。'],
                ]
            ),
        ];

        return $this->makePage(
            $component,
            [
                'summary' => '如果说 Form、Table、Chart 分别解决“单个内容块怎么渲染”，那么 Page 解决的就是“这些内容块怎么在一个后台页面上协同出现”。',
                'scenarios' => [
                    '一个页面同时展示筛选表单、结果表格和趋势图',
                    '系统配置页、工作台页、报表总览页这类综合页面',
                    '需要先看摘要卡片，再看筛选和列表的后台场景',
                ],
                'capabilities' => ['承载 Form', '承载 Table', '承载 Chart', '跨行组合'],
                'quick_start' => [
                    'array_code' => "[\n    ['form', 'md-4'],\n    ['chart', 'md-8'],\n    ['table', 'md-12'],\n]",
                    'page_code' => "use app\\common\\render\\Page;\n\nPage::make('workspace')\n    ->row([\n        [\$form, 'md-4'],\n        [\$chart, 'md-8'],\n    ])\n    ->row([\n        [\$table, 'md-12'],\n    ]);",
                ],
            ],
            [
                [
                    'title' => '组合要点',
                    'items' => [
                        ['name' => 'Page', 'summary' => '只负责页面骨架和布局。'],
                        ['name' => 'Form/Table/Chart', 'summary' => '负责各自的真实渲染和交互。'],
                    ],
                ],
            ],
            $sections,
            $this->relatedComponents((string) ($component['key'] ?? ''))
        );
    }
}

