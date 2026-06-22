<?php
declare(strict_types=1);

namespace app\showcase\service\components\form;

/**
 * Showcase button_group 组件页组装器
 */
final class ButtonGroupComponentPage
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
                'summary' => 'ButtonGroup 组件用于在表单中承载互斥操作集合或工具按钮集合，适合状态切换、视图切换和一组紧凑动作入口。',
                'scenarios' => [
                    '状态切换、视图切换、工具入口等需要一组紧凑按钮的场景',
                    '列表筛选区或配置表单中的互斥模式切换场景',
                    '需要同时演示默认值、图标模式和垂直布局差异的场景',
                ],
                'capabilities' => [
                    '基础按钮组',
                    '默认值与回填',
                    '图标按钮组',
                    '垂直布局',
                    '自定义 group_class',
                    '值处理提示',
                    '业务表单片段',
                ],
                'quick_start' => [
                    'array_code' => <<<'CODE'
[
    [
        'type' => 'button_group',
        'name' => 'view_mode',
        'label' => '视图模式',
        'options' => [
            'table' => '表格',
            'card' => '卡片',
        ],
        'value' => 'table',
    ],
]
CODE,
                    'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::buttonGroup('view_mode', '视图模式')
    ->options([
        'table' => '表格',
        'card' => '卡片',
    ])
    ->value('table');
CODE,
                ],
            ],
            'param_groups' => $this->paramGroups(),
            'sections' => $sections,
            'related_components' => [
                ['key' => 'basic.button', 'title' => 'button 按钮', 'status' => 'available'],
                ['key' => 'basic.html', 'title' => 'html HTML 内容', 'status' => 'available'],
                ['key' => 'basic.static', 'title' => 'static 静态文本', 'status' => 'available'],
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
                    ['name' => 'name / label / tips', 'summary' => '字段名、标题和说明文案。'],
                    ['name' => 'options', 'summary' => '模板真实读取的是 options，而不是 buttons。'],
                    ['name' => 'value', 'summary' => '默认选中项，对应 options 的 key。'],
                ],
            ],
            [
                'title' => '布局与外观',
                'items' => [
                    ['name' => 'icon(true)', 'summary' => '将按钮组切换为图标按钮样式。'],
                    ['name' => 'vertical(true)', 'summary' => '容器类切换为 btn-group-vertical。'],
                    ['name' => 'groupClass()', 'summary' => '自定义整个按钮组的 class。'],
                ],
            ],
            [
                'title' => '值处理建议',
                'items' => [
                    ['name' => '互斥值', 'summary' => 'button_group 最终提交的是单个选中值，适合模式切换。'],
                    ['name' => '选项 HTML', 'summary' => 'options 的 value 支持原样输出，可渲染图标 HTML。'],
                    ['name' => '选型建议', 'summary' => '互斥切换用 button_group；独立动作按钮集合仍更适合 button。'],
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
