<?php
declare(strict_types=1);

namespace app\showcase\service\components\form;

/**
 * Showcase table 组件页组装器
 */
final class TableComponentPage
{
    /**
     * @param array<string, mixed> $component
     * @param list<array<string, string>> $sectionCatalog
     * @return array<string, mixed>
     */
    public function build(array $component, array $sectionCatalog): array
    {
        $sections = [];

        foreach ($sectionCatalog as $sectionMeta) {
            $builderClass = (string) ($sectionMeta['builder'] ?? '');
            if ($builderClass === '') {
                continue;
            }

            $sections[] = app($builderClass)->build($component, $sectionMeta);
        }

        return [
            'component' => $component,
            'overview' => [
                'summary' => 'Table 组件用于在表单中展示结构化只读数据，适合统计结果、明细对照、导入预览和配置矩阵等需要表格化呈现的后台场景。',
                'scenarios' => [
                    '统计结果、明细对照、导入预览等需要只读表格展示的场景',
                    '希望把复杂配置、比对结果或多列摘要直接嵌入表单中的场景',
                    '需要同时演示多表头、合并单元格、HTML 单元格和空态占位的场景',
                ],
                'capabilities' => [
                    '基础表格',
                    'value 回显表体',
                    '多表头与合并单元格',
                    '单元格 raw 与 HTML',
                    '样式类与外层容器',
                    '空数据占位',
                    '导入预览场景',
                    '业务表单片段',
                ],
                'quick_start' => [
                    'array_code' => <<<'CODE'
[
    [
        'type' => 'table',
        'name' => 'report',
        'label' => '销售报表',
        'headers' => [
            ['日期', '订单数', '销售额'],
        ],
        'rows' => [
            ['2026-06-01', '152', '¥38,500'],
            ['2026-06-02', '168', '¥41,200'],
        ],
    ],
]
CODE,
                    'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::table('report', '销售报表')
    ->headers([
        ['日期', '订单数', '销售额'],
    ])
    ->rows([
        ['2026-06-01', '152', '¥38,500'],
        ['2026-06-02', '168', '¥41,200'],
    ]);
CODE,
                ],
            ],
            'param_groups' => $this->paramGroups(),
            'sections' => $sections,
            'related_components' => [
                ['key' => 'basic.static', 'title' => 'static 静态文本', 'status' => 'available'],
                ['key' => 'basic.html', 'title' => 'html HTML 内容', 'status' => 'available'],
                ['key' => 'rich.tabs', 'title' => 'tabs 标签分组', 'status' => 'available'],
            ],
            'doc_links' => (array) ($component['doc_links'] ?? []),
            'sidebar_source_refs' => array_slice($this->mergeSourceRefs((array) ($component['source_refs'] ?? []), $sections), 0, 3),
            'source_refs' => $this->mergeSourceRefs((array) ($component['source_refs'] ?? []), $sections),
        ];
    }

    private function paramGroups(): array
    {
        return [
            [
                'title' => '基础参数',
                'items' => [
                    ['name' => 'headers(array)', 'summary' => '设置表头，可支持多行表头。'],
                    ['name' => 'rows(array) / data(array)', 'summary' => '设置表体数据，`data()` 是 `rows()` 别名。'],
                    ['name' => 'value', 'summary' => '兼容旧写法，可直接把表体数组作为 value 传入。'],
                ],
            ],
            [
                'title' => '展示能力',
                'items' => [
                    ['name' => 'raw(bool)', 'summary' => '控制单元格内容是否按 HTML 原样输出。'],
                    ['name' => 'rowspan / colspan', 'summary' => '通过单元格对象实现合并单元格与复杂头部。'],
                    ['name' => 'emptyText(string)', 'summary' => '空数据时输出自定义占位文案。'],
                ],
            ],
            [
                'title' => '布局建议',
                'items' => [
                    ['name' => 'tableClass(string)', 'summary' => '控制表格类名，如边框、条纹、紧凑模式。'],
                    ['name' => 'wrapperClass(string)', 'summary' => '控制外层容器，如 `table-responsive`。'],
                    ['name' => '选型建议', 'summary' => '需要只读表格展示用 table；需要真正可编辑表格时应考虑独立页面或自定义组件。'],
                ],
            ],
        ];
    }

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
