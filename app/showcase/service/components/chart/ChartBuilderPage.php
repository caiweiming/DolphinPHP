<?php
declare(strict_types=1);

namespace app\showcase\service\components\chart;

use app\showcase\service\registry\ShowcaseChartBuilderMethodCatalog;

/**
 * Showcase 图表构建器页面组装器
 */
final class ChartBuilderPage
{
    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $groups = ShowcaseChartBuilderMethodCatalog::groups();
        $sections = [];
        $sectionBuilder = app(chart_builder\ChartBuilderMethodSectionBuilder::class);

        foreach ($groups as $group) {
            foreach ((array) ($group['methods'] ?? []) as $methodKey) {
                $section = $sectionBuilder->build((string) $methodKey);

                if ($section !== []) {
                    $sections[] = $section;
                }
            }
        }

        return [
            'page' => [
                'key' => 'chart.builder',
                'title' => '图表构建器 / Chart Builder',
                'summary' => '系统展示 Chart.php 的图表级能力，帮助开发者快速抄用类型、数据源和状态配置写法。',
            ],
            'overview' => [
                'summary' => '本页聚焦 app/common/render/Chart.php 的公开业务 API，说明如何组织类型、类目、系列、Ajax 数据源以及 loading / empty 等图表级能力。',
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
            'groups' => $groups,
            'sections' => $sections,
            'source_refs' => [
                [
                    'path' => 'app/common/render/Chart.php',
                    'label' => '图表构建器源码',
                    'description' => '当前页面讲解的所有方法都来自该类。',
                ],
                [
                    'path' => 'app/showcase/controller/admin/Chart.php',
                    'label' => 'Showcase 控制器',
                    'description' => '承接首页入口与 builder 页面路由。',
                ],
            ],
        ];
    }
}
