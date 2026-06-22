<?php
declare(strict_types=1);

namespace app\showcase\service\components\chart;

use app\common\render\Chart;
use app\showcase\service\registry\ShowcaseRegistryService;

/**
 * 图表详情页公共能力
 */
abstract class AbstractChartPage
{
    /**
     * @param callable(Chart): void $callback
     */
    protected function renderChart(callable $callback): string
    {
        $chart = Chart::make('showcase_chart_' . substr(md5((string) microtime(true)), 0, 8));
        $callback($chart);

        return $chart->fetch();
    }

    /**
     * @param array<string, mixed> $component
     * @param array<string, mixed> $overview
     * @param list<array<string, mixed>> $paramGroups
     * @param list<array<string, mixed>> $sections
     * @param list<array<string, mixed>> $relatedComponents
     * @return array<string, mixed>
     */
    protected function makePage(
        array $component,
        array $overview,
        array $paramGroups,
        array $sections,
        array $relatedComponents
    ): array {
        return [
            'component' => $component,
            'overview' => $overview,
            'param_groups' => $paramGroups,
            'sections' => $sections,
            'related_components' => $relatedComponents,
            'doc_links' => (array) ($component['doc_links'] ?? []),
            'sidebar_source_refs' => array_slice($this->mergeSourceRefs((array) ($component['source_refs'] ?? []), $sections), 0, 3),
            'source_refs' => $this->mergeSourceRefs((array) ($component['source_refs'] ?? []), $sections),
        ];
    }

    /**
     * @param string $key
     * @param string $title
     * @param string $summary
     * @param string $previewHtml
     * @param list<array{name:string, value:string}> $params
     * @param list<string> $notes
     * @param string $arrayCode
     * @param string $chartCode
     * @param list<array<string, string>> $sourceRefs
     * @return array<string, mixed>
     */
    protected function makeSection(
        string $key,
        string $title,
        string $summary,
        string $previewHtml,
        array $params,
        array $notes,
        string $arrayCode,
        string $chartCode,
        array $sourceRefs
    ): array {
        return [
            'key' => $key,
            'title' => $title,
            'summary' => $summary,
            'preview_html' => $previewHtml,
            'params' => $params,
            'notes' => $notes,
            'array_code' => $arrayCode,
            'chart_code' => $chartCode,
            'source_refs' => $sourceRefs,
        ];
    }

    protected function demoUrl(string $dataset): string
    {
        return (string) url('showcase/admin.demoApi/chart', ['dataset' => $dataset]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function relatedComponents(string $currentKey): array
    {
        $components = array_values(app(ShowcaseRegistryService::class)->components('chart'));

        return array_values(array_filter($components, static function (array $component) use ($currentKey): bool {
            return ($component['key'] ?? '') !== $currentKey && ($component['status'] ?? 'planned') === 'available';
        }));
    }

    /**
     * @param array<int, array<string, mixed>> $componentSources
     * @param list<array<string, mixed>> $sections
     * @return array<int, array<string, mixed>>
     */
    private function mergeSourceRefs(array $componentSources, array $sections): array
    {
        $result = $componentSources;
        $seen = [];

        foreach ($componentSources as $source) {
            $path = (string) ($source['path'] ?? '');
            if ($path !== '') {
                $seen[$path] = true;
            }
        }

        foreach ($sections as $section) {
            foreach ((array) ($section['source_refs'] ?? []) as $source) {
                $path = (string) ($source['path'] ?? '');

                if ($path === '' || isset($seen[$path])) {
                    continue;
                }

                $seen[$path] = true;
                $result[] = $source;
            }
        }

        return $result;
    }
}
