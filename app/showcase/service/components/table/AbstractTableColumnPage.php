<?php
declare(strict_types=1);

namespace app\showcase\service\components\table;

/**
 * 表格列详情页公共能力
 */
abstract class AbstractTableColumnPage
{
    /**
     * @param string $title
     * @param list<string> $items
     */
    protected function infoPreview(string $title, array $items): string
    {
        $title = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $html = '<div class="showcase-builder-placeholder">';
        $html .= '<div class="showcase-builder-placeholder__title">' . $title . '</div>';
        $html .= '<div class="showcase-note-list">';

        foreach ($items as $item) {
            $html .= '<p class="showcase-panel__text">' . htmlspecialchars($item, ENT_QUOTES, 'UTF-8') . '</p>';
        }

        $html .= '</div></div>';

        return $html;
    }

    /**
     * @param array<int, array<int|string, mixed>> $columns
     * @param array<string, mixed> $options
     */
    protected function liveMain(array $columns, array $options = []): string
    {
        return (string) (app(TableLiveDemoBuilder::class)->buildMainTable($columns, $options)['html'] ?? '');
    }

    /**
     * @param array<int, array<int|string, mixed>> $columns
     * @param array<string, mixed> $options
     */
    protected function liveMedia(array $columns, array $options = []): string
    {
        return (string) (app(TableLiveDemoBuilder::class)->buildMediaTable($columns, $options)['html'] ?? '');
    }

    /**
     * @param array<int, array<int|string, mixed>> $columns
     * @param array<string, mixed> $options
     */
    protected function liveComplex(array $columns, array $options = []): string
    {
        return (string) (app(TableLiveDemoBuilder::class)->buildComplexTable($columns, $options)['html'] ?? '');
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
     * @param string $title
     * @param string $summary
     * @param string $previewHtml
     * @param list<array{name:string, value:string}> $params
     * @param list<string> $notes
     * @param string $arrayCode
     * @param string $tableCode
     * @param list<array<string, string>> $sourceRefs
     * @param string $key
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
        string $tableCode,
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
            'table_code' => $tableCode,
            'source_refs' => $sourceRefs,
        ];
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
