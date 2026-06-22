<?php
declare(strict_types=1);

namespace app\showcase\service\components\form;

/**
 * Showcase checkbox_group 组件页组装器
 */
final class CheckboxGroupComponentPage
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
                'summary' => 'CheckboxGroup 组件用于承载卡片式多选场景，适合功能模块、权限集合、套餐能力等需要更强视觉强调的后台表单。',
                'scenarios' => [
                    '功能模块、权限集合、套餐能力等需要卡片式多选的场景',
                    '需要比标准复选框更强对比感的多选场景',
                    '需要更大点击区域和更丰富业务文案的后台表单',
                ],
                'capabilities' => [
                    '基础卡片多选',
                    '默认值与回填',
                    '必填场景',
                    '禁用状态',
                    '布局与文案',
                    '值处理提示',
                    '业务表单片段',
                ],
                'quick_start' => [
                    'array_code' => <<<'CODE'
[
    [
        'type' => 'checkbox_group',
        'name' => 'modules',
        'label' => '功能模块',
        'options' => [
            'user' => '用户管理',
            'content' => '内容管理',
            'setting' => '系统设置',
        ],
        'value' => ['user', 'content'],
    ],
]
CODE,
                    'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::checkboxGroup('modules', '功能模块')
    ->options([
        'user' => '用户管理',
        'content' => '内容管理',
        'setting' => '系统设置',
    ])
    ->value(['user', 'content']);
CODE,
                ],
            ],
            'param_groups' => $this->paramGroups(),
            'sections' => $sections,
            'related_components' => [
                ['key' => 'choice.checkbox', 'title' => 'checkbox 多选框', 'status' => 'available'],
                ['key' => 'choice.radio_group', 'title' => 'radio_group 单选标签组', 'status' => 'available'],
                ['key' => 'choice.select_group', 'title' => 'select_group 多选标签', 'status' => 'available'],
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
                    ['name' => 'name / label / tips', 'summary' => '字段名、标题与提示文案，定义卡片多选字段的业务语义。'],
                    ['name' => 'options', 'summary' => '使用键值对定义卡片候选项。'],
                    ['name' => 'value', 'summary' => '默认选中项或编辑态回显值，类型为数组。'],
                    ['name' => 'required', 'summary' => '强调必须至少选择一个卡片项。'],
                ],
            ],
            [
                'title' => '交互与展示',
                'items' => [
                    ['name' => 'disabled', 'summary' => '可禁用全部或指定卡片选项。'],
                    ['name' => 'class / props', 'summary' => '补充类名和属性，用于布局或业务标记。'],
                    ['name' => 'HTML 文案', 'summary' => 'options 值支持 HTML，可展示标题、副标题和说明。'],
                ],
            ],
            [
                'title' => '值处理',
                'items' => [
                    ['name' => '数组提交', 'summary' => 'checkbox_group 提交结果为数组，后端通常存 JSON 或逗号串。'],
                    ['name' => '组件对比', 'summary' => '需要卡片展示时用 checkbox_group；只需轻量列表时用 checkbox。'],
                    ['name' => '候选数量', 'summary' => '适合 2 到 6 个需要重点比较的多选项。'],
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
