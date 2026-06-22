<?php
declare(strict_types=1);

namespace app\showcase\service\components\form;

/**
 * Showcase color_select 组件页组装器
 */
final class ColorSelectComponentPage
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
                'summary' => 'ColorSelect 组件用于从预设颜色集合中快速选择一个或多个颜色值，适合标签色、状态色和分类色等标准化选色场景。',
                'scenarios' => [
                    '标签色、状态色、分类色等需要从固定颜色集合中选择的场景',
                    '希望限制用户只能从预设品牌色或主题色中选择的配置场景',
                    '需要比较单选、多选、圆形样式和禁用态差异的颜色选择场景',
                ],
                'capabilities' => [
                    '基础预设颜色',
                    '圆形样式 circle',
                    '自定义颜色值',
                    'multiple 多选',
                    '禁用指定颜色',
                    '别名与类型说明',
                    '业务表单片段',
                ],
                'quick_start' => [
                    'array_code' => <<<'CODE'
[
    [
        'type' => 'color_select',
        'name' => 'color',
        'label' => '颜色',
        'options' => [
            '#206bc4',
            '#2fb344',
            '#f76707',
        ],
    ],
]
CODE,
                    'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::colorSelect('color', '颜色')
    ->options([
        '#206bc4',
        '#2fb344',
        '#f76707',
    ]);
CODE,
                ],
            ],
            'param_groups' => $this->paramGroups(),
            'sections' => $sections,
            'related_components' => [
                ['key' => 'choice.color', 'title' => 'color 取色器', 'status' => 'available'],
                ['key' => 'choice.select_group', 'title' => 'select_group 多选标签', 'status' => 'available'],
                ['key' => 'choice.checkbox_group', 'title' => 'checkbox_group 多选标签组', 'status' => 'available'],
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
                    ['name' => 'name / label / tips', 'summary' => '字段名、标题和提示文案。'],
                    ['name' => 'options', 'summary' => '支持主题色名、自定义颜色值或完整样式数组。'],
                    ['name' => 'value', 'summary' => '当前选中颜色值，单选为字符串，多选通常为数组。'],
                ],
            ],
            [
                'title' => '核心能力',
                'items' => [
                    ['name' => 'circle(true)', 'summary' => '切换为圆形颜色项样式。'],
                    ['name' => 'multiple(true)', 'summary' => '将颜色选择切换为多选模式。'],
                    ['name' => 'disabled', 'summary' => '支持禁用全部或指定颜色项。'],
                ],
            ],
            [
                'title' => '选型建议',
                'items' => [
                    ['name' => '预设色治理', 'summary' => '品牌规范明确时优先用 color_select。'],
                    ['name' => 'color_select / color', 'summary' => '标准化色板用 color_select，自由取色用 color。'],
                    ['name' => '值结构', 'summary' => '多选模式下提交值通常是数组，后端需统一处理。'],
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
