<?php
declare(strict_types=1);

namespace app\showcase\service\components\form;

/**
 * Showcase checkbox 组件页组装器
 */
final class CheckboxComponentPage
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
                'summary' => 'Checkbox 组件用于承载标准多选场景，适合角色分配、权限勾选、兴趣爱好等需要选择多个离散选项的后台表单。',
                'scenarios' => [
                    '角色分配、权限勾选、兴趣爱好等需要标准多选框的场景',
                    '需要把多个候选项平铺展示并允许同时选择多个结果的后台表单',
                    '需要备注说明、禁用状态和默认勾选值的标准多选场景',
                ],
                'capabilities' => [
                    '基础多选',
                    '默认值与回填',
                    'inline 横向排列',
                    '禁用状态',
                    '备注说明',
                    '选项字符串解析',
                    '值处理提示',
                    '业务表单片段',
                ],
                'quick_start' => [
                    'array_code' => <<<'CODE'
[
    [
        'type' => 'checkbox',
        'name' => 'role_ids',
        'label' => '角色',
        'options' => [
            1 => '管理员',
            2 => '编辑',
            3 => '作者',
        ],
        'value' => [1, 3],
    ],
]
CODE,
                    'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::checkbox('role_ids', '角色')
    ->options([
        1 => '管理员',
        2 => '编辑',
        3 => '作者',
    ])
    ->value([1, 3]);
CODE,
                ],
            ],
            'param_groups' => $this->paramGroups(),
            'sections' => $sections,
            'related_components' => [
                ['key' => 'choice.checkbox_group', 'title' => 'checkbox_group 多选标签组', 'status' => 'available'],
                ['key' => 'choice.select_group', 'title' => 'select_group 多选标签', 'status' => 'available'],
                ['key' => 'choice.select', 'title' => 'select 下拉选择', 'status' => 'available'],
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
                    ['name' => 'name / label / tips', 'summary' => '字段名、标题与提示文案，定义多选字段的业务语义。'],
                    ['name' => 'options', 'summary' => '使用键值对定义候选项。'],
                    ['name' => 'value', 'summary' => '默认勾选项或编辑态回显值，类型为数组。'],
                    ['name' => 'required', 'summary' => '标记为必选字段。'],
                ],
            ],
            [
                'title' => '交互与展示',
                'items' => [
                    ['name' => 'inline', 'summary' => '开启水平排列，适合选项较少的场景。'],
                    ['name' => 'disabled', 'summary' => '可禁用全部或指定选项。'],
                    ['name' => 'remark', 'summary' => '为指定选项补充说明文案。'],
                    ['name' => '`选项#说明`', 'summary' => '可直接在 options 文案中使用 `#` 自动拆分备注说明。'],
                ],
            ],
            [
                'title' => '值处理',
                'items' => [
                    ['name' => '数组提交', 'summary' => 'checkbox 提交结果为数组，后端需按数组或 JSON/逗号串处理。'],
                    ['name' => '未选中', 'summary' => '未选中时通常需要后端兜底为空数组。'],
                    ['name' => '扩展配置入口', 'summary' => '适合作为“勾选多个能力后展示扩展配置”的入口字段。'],
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
