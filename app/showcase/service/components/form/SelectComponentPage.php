<?php
declare(strict_types=1);

namespace app\showcase\service\components\form;

/**
 * Showcase select 组件页组装器
 */
final class SelectComponentPage
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
                'summary' => 'Select 组件用于承载预定义选项中的单选或多选，适合状态、分类、城市、角色、标签等需要节省空间的后台表单场景。',
                'scenarios' => [
                    '状态、分类、城市、角色等预定义选项较稳定的下拉选择场景',
                    '希望在同一字段内承载多选值，但不想占用过多纵向空间的后台表单',
                    '需要通过禁用选项、分组和占位符提升选择体验的常规管理场景',
                ],
                'capabilities' => [
                    '基础下拉选择',
                    '默认值与回填',
                    '占位符提示',
                    'multiple 多选',
                    '多选默认值',
                    '禁用状态',
                    '禁用指定选项',
                    '分组选项',
                    '扩展参数与 DOM 控制',
                    '业务表单片段',
                ],
                'quick_start' => [
                    'array_code' => <<<'CODE'
[
    [
        'type' => 'select',
        'name' => 'status',
        'label' => '状态',
        'tips' => '请选择状态',
        'options' => [
            0 => '禁用',
            1 => '启用',
        ],
        'value' => 1,
        'placeholder' => '请选择状态',
    ],
]
CODE,
                    'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::select('status', '状态', '请选择状态')
    ->options([
        0 => '禁用',
        1 => '启用',
    ])
    ->value(1)
    ->placeholder('请选择状态');
CODE,
                ],
            ],
            'param_groups' => $this->paramGroups(),
            'sections' => $sections,
            'related_components' => [
                ['key' => 'choice.radio', 'title' => 'radio 单选框', 'status' => 'available'],
                ['key' => 'choice.radio_group', 'title' => 'radio_group 单选标签组', 'status' => 'available'],
                ['key' => 'basic.switch', 'title' => 'switch 开关', 'status' => 'available'],
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
                    ['name' => 'name', 'summary' => '字段名，决定提交键名与 select 元素 name。'],
                    ['name' => 'label', 'summary' => '字段标题，用于说明这组选项表示什么业务含义。'],
                    ['name' => 'tips', 'summary' => '字段提示文案，适合说明选项差异或填写要求。'],
                    ['name' => 'options', 'summary' => '下拉选项数组，键为提交值，值为展示文本。'],
                    ['name' => 'value', 'summary' => '默认选中值或编辑态回显值；多选场景常用逗号分隔字符串。'],
                ],
            ],
            [
                'title' => '选择与分组',
                'items' => [
                    ['name' => 'placeholder', 'summary' => '自定义空选项提示文案。'],
                    ['name' => 'multiple', 'summary' => '启用多选模式，提交 name 会自动补成数组形式。'],
                    ['name' => 'group', 'summary' => '通过分组数据渲染 optgroup 结构。'],
                    ['name' => 'disabled', 'summary' => '可禁用整个下拉框，或指定部分不可选项。'],
                ],
            ],
            [
                'title' => '扩展能力',
                'items' => [
                    ['name' => 'help', 'summary' => '标签右侧帮助说明，适合补充选项来源或业务规则。'],
                    ['name' => 'id / class', 'summary' => '自定义 DOM id 与样式类，便于脚本挂载或局部定制。'],
                    ['name' => 'props', 'summary' => '透传原生属性，如 data-*、disabled 等。'],
                    ['name' => 'select / radio / radio_group', 'summary' => '选项少且要平铺时用 radio；需要卡片表达时用 radio_group；注重节省空间时用 select。'],
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
