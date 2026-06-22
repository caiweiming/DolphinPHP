<?php
declare(strict_types=1);

namespace app\showcase\service\components\page;

use app\common\render\Chart;
use app\common\render\Form;
use app\common\render\Page;
use app\common\render\Table;
use app\common\render\form\Field;
use app\showcase\service\registry\ShowcaseRegistryService;

/**
 * 页面组件详情页公共能力
 */
abstract class AbstractPageComponent
{
    protected function renderPagePreview(callable $callback): string
    {
        $page = Page::make('showcase_page_preview_' . substr(md5((string) microtime(true)), 0, 8));
        $page->clear();
        $page->title('');
        $page->preTitle('');
        $callback($page);

        return $this->renderPreviewFromVars($page->getVars());
    }

    protected function renderPreviewFromVars(array $vars): string
    {
        $headerHtml = '';
        $preTitle = trim((string) ($vars['dp_page_pre_title'] ?? ''));
        $title = trim((string) ($vars['dp_page_title'] ?? ''));
        $actions = (array) (($vars['dp_page_action']['top-right'] ?? []));

        if ($preTitle !== '' || $title !== '' || $actions !== []) {
            $headerHtml .= '<div class="showcase-page-preview-shell__header">';
            $headerHtml .= '<div class="showcase-page-preview-shell__headline">';

            if ($preTitle !== '') {
                $headerHtml .= '<div class="showcase-page-preview-shell__pretitle">' . htmlspecialchars($preTitle, ENT_QUOTES, 'UTF-8') . '</div>';
            }
            if ($title !== '') {
                $headerHtml .= '<h4 class="showcase-page-preview-shell__title">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h4>';
            }

            $headerHtml .= '</div>';

            if ($actions !== []) {
                $headerHtml .= '<div class="showcase-page-preview-shell__actions">';
                foreach ($actions as $action) {
                    $class = trim((string) ($action['class'] ?? 'btn btn-sm btn-primary'));
                    $buttonTitle = htmlspecialchars((string) ($action['title'] ?? ''), ENT_QUOTES, 'UTF-8');
                    $headerHtml .= '<a href="javascript:;" class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '">';
                    $headerHtml .= (string) ($action['icon'] ?? '');
                    $headerHtml .= $buttonTitle;
                    $headerHtml .= '</a>';
                }
                $headerHtml .= '</div>';
            }

            $headerHtml .= '</div>';
        }

        $rowsHtml = '';
        foreach ((array) ($vars['dp_page_rows'] ?? []) as $row) {
            $rowClass = trim((string) ($row['class'] ?? ''));
            $rowsHtml .= '<div class="row row-cards mb-3 ' . htmlspecialchars($rowClass, ENT_QUOTES, 'UTF-8') . '">';

            foreach ((array) ($row['cols'] ?? []) as $col) {
                if ($col === '-') {
                    $rowsHtml .= (string) ($vars['dp_newline'] ?? '<div class="w-100 p-0 m-0"></div>');
                    continue;
                }

                if (!is_array($col)) {
                    continue;
                }

                $colClass = trim((string) ($col['class'] ?? 'col'));
                $rowsHtml .= '<div class="' . htmlspecialchars($colClass, ENT_QUOTES, 'UTF-8') . '">';
                $rowsHtml .= (string) ($col['content'] ?? '');
                $rowsHtml .= '</div>';
            }

            $rowsHtml .= '</div>';
        }

        return '<div class="showcase-page-preview-shell">' . $headerHtml . $rowsHtml . '</div>';
    }

    protected function demoForm(string $id, string $title, array $items, array $data = []): Form
    {
        return Form::make($id, $title)
            ->header(false)
            ->action((string) url('showcase/admin.demoApi/submit'))
            ->data(array_merge($data, ['example_key' => $id]))
            ->items(array_merge([
                Field::hidden('example_key', '示例标识')->value($id),
            ], $items));
    }

    protected function demoTable(string $id, array $data, array $columns): Table
    {
        return Table::make($id)
            ->data($data)
            ->columns($columns)
            ->page(['layout' => 'prev, pager, next']);
    }

    protected function demoChart(string $id, array $categories, array $series): Chart
    {
        return Chart::make($id)
            ->type('line')
            ->height('220px')
            ->categories($categories)
            ->series($series);
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
     * @param string $pageCode
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
        string $pageCode,
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
            'page_code' => $pageCode,
            'source_refs' => $sourceRefs,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function relatedComponents(string $currentKey): array
    {
        $components = array_values(app(ShowcaseRegistryService::class)->components('page'));

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

