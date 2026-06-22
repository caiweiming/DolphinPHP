<?php
declare(strict_types=1);

namespace app\showcase\service\components\form;

/**
 * Showcase date 组件页组装器
 */
final class DateComponentPage
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
                'summary' => 'Date 组件用于承载单日期输入，适合生日、生效日、截止日、发布日等只需要日期而不需要时间的后台表单场景。',
                'scenarios' => [
                    '生日、生效日、失效日、发布日期等单日期输入场景',
                    '希望复用统一日期选择器交互，但明确关闭时间选择的后台编辑表单',
                    '需要通过原生 options 控制日期格式、多日期、自动关闭等行为的场景',
                ],
                'capabilities' => [
                    '基础用法',
                    '占位符与图标',
                    '默认值与回填',
                    '样式变体',
                    '只读与禁用',
                    '原生 options 配置',
                    '值格式与多日期提示',
                    '业务表单片段',
                ],
                'quick_start' => [
                    'array_code' => <<<'CODE'
[
    [
        'type' => 'date',
        'name' => 'publish_date',
        'label' => '发布日期',
        'tips' => '请选择发布日期',
        'placeholder' => '请选择发布日期',
        'value' => '2026-06-01',
        'options' => [
            'dateFormat' => 'yyyy-MM-dd',
            'autoClose' => true,
        ],
    ],
]
CODE,
                    'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::date('publish_date', '发布日期', '请选择发布日期')
    ->value('2026-06-01')
    ->placeholder('请选择发布日期')
    ->options([
        'dateFormat' => 'yyyy-MM-dd',
        'autoClose' => true,
    ]);
CODE,
                ],
            ],
            'param_groups' => $this->paramGroups(),
            'sections' => $sections,
            'related_components' => [
                ['key' => 'datetime.time', 'title' => 'time 时间选择器', 'status' => 'available'],
                ['key' => 'datetime.datetime', 'title' => 'datetime 日期时间选择器', 'status' => 'available'],
                ['key' => 'datetime.date_range', 'title' => 'date_range 日期范围选择器', 'status' => 'available'],
            ],
            'doc_links' => (array) ($component['doc_links'] ?? []),
            'sidebar_source_refs' => array_slice($this->mergeSourceRefs((array) ($component['source_refs'] ?? []), $sections), 0, 3),
            'source_refs' => $this->mergeSourceRefs((array) ($component['source_refs'] ?? []), $sections),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function paramGroups(): array
    {
        return [
            [
                'title' => '基础参数',
                'items' => [
                    ['name' => 'name / label / tips', 'summary' => '字段名、标题与提示文案，定义日期字段的业务语义。'],
                    ['name' => 'value', 'summary' => '默认值或编辑态回显值；数组值会转为多日期回显。'],
                    ['name' => 'placeholder', 'summary' => '未选择日期时的引导文案。'],
                    ['name' => 'required', 'summary' => '标记为必填日期输入。'],
                ],
            ],
            [
                'title' => '展示与状态',
                'items' => [
                    ['name' => 'icon', 'summary' => '控制日期图标位于左侧或右侧。'],
                    ['name' => 'rounded / flush', 'summary' => '切换圆角或扁平输入样式。'],
                    ['name' => 'readonly / disabled', 'summary' => '控制是否允许手动编辑或交互。'],
                    ['name' => 'size / class', 'summary' => '控制尺寸和自定义样式类。'],
                ],
            ],
            [
                'title' => '原生配置',
                'items' => [
                    ['name' => 'options.dateFormat', 'summary' => '控制最终输入框值的日期格式。'],
                    ['name' => 'options.autoClose', 'summary' => '选择后自动关闭日期面板。'],
                    ['name' => 'options.multipleDates', 'summary' => '开启多日期选择。'],
                    ['name' => 'options.inline', 'summary' => '以内嵌形式展示日期面板。'],
                ],
            ],
            [
                'title' => '选型建议',
                'items' => [
                    ['name' => 'date / datetime', 'summary' => '只需要日期时用 date；需要同时录入时间时用 datetime。'],
                    ['name' => 'date / date_range', 'summary' => '单日场景用 date；起止周期场景优先 date_range。'],
                    ['name' => '数组值回显', 'summary' => 'date 支持数组值，但更适合演示原生能力，不建议代替专门的 range 组件。'],
                ],
            ],
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
