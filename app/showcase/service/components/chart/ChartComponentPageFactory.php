<?php
declare(strict_types=1);

namespace app\showcase\service\components\chart;

use app\showcase\service\registry\ShowcaseRegistryService;
use InvalidArgumentException;

/**
 * Showcase 图表组件页工厂
 */
final class ChartComponentPageFactory
{
    /**
     * @param string $key
     * @return array<string, mixed>
     */
    public function make(string $key): array
    {
        $registry = app(ShowcaseRegistryService::class);
        $component = $registry->findComponent('chart', $key);

        if (($component['status'] ?? 'planned') !== 'available') {
            return [
                'component' => $component,
                'overview' => [
                    'summary' => '当前组件已注册到示例目录；如详情数据缺失，请优先参考右侧源码与文档入口。',
                    'capabilities' => (array) ($component['tags'] ?? []),
                ],
                'param_groups' => [],
                'sections' => [],
                'related_components' => [],
                'doc_links' => (array) ($component['doc_links'] ?? []),
                'source_refs' => (array) ($component['source_refs'] ?? []),
            ];
        }

        return $this->pageBuilderFor($key)->build($component);
    }

    private function pageBuilderFor(string $key): object
    {
        return match ($key) {
            'basic.line' => app(LineChartPage::class),
            'basic.bar' => app(BarChartPage::class),
            'basic.pie' => app(PieChartPage::class),
            'basic.scatter' => app(ScatterChartPage::class),
            'map.demo_region' => app(DemoRegionMapChartPage::class),
            'map.china_guangdong' => app(ChinaGuangdongMapChartPage::class),
            default => throw new InvalidArgumentException(sprintf('Unknown showcase chart component page: %s', $key)),
        };
    }
}
