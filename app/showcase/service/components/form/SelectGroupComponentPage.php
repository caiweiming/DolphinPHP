<?php
declare(strict_types=1);

namespace app\showcase\service\components\form;

/**
 * Showcase select_group 组件页组装器
 */
final class SelectGroupComponentPage
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
                'summary' => 'SelectGroup 组件用于承载标签式多选场景，适合文章标签、能力标签、分类标签等需要轻量多选和更紧凑布局的后台表单。',
                'scenarios' => [
                    '文章标签、能力标签、分类标签等需要轻量标签式多选的场景',
                    '希望用紧凑胶囊标签承载多选的后台表单',
                    '需要更少空间展示选项，并支持默认值、禁用态与圆角样式的场景',
                ],
                'capabilities' => [
                    '基础标签多选',
                    '默认值与回填',
                    '禁用状态',
                    '圆角样式',
                    '选项规模建议',
                    '值处理提示',
                    '业务表单片段',
                ],
                'quick_start' => [
                    'array_code' => <<<'CODE'
[
    [
        'type' => 'select_group',
        'name' => 'tags',
        'label' => '标签',
        'options' => [
            'php' => 'PHP',
            'tp8' => 'ThinkPHP',
            'mysql' => 'MySQL',
        ],
        'value' => ['php', 'tp8'],
    ],
]
CODE,
                    'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::selectGroup('tags', '标签')
    ->options([
        'php' => 'PHP',
        'tp8' => 'ThinkPHP',
        'mysql' => 'MySQL',
    ])
    ->value(['php', 'tp8']);
CODE,
                ],
            ],
            'param_groups' => $this->paramGroups(),
            'sections' => $sections,
            'related_components' => [
                ['key' => 'choice.checkbox', 'title' => 'checkbox 多选框', 'status' => 'available'],
                ['key' => 'choice.checkbox_group', 'title' => 'checkbox_group 多选标签组', 'status' => 'available'],
                ['key' => 'choice.tags', 'title' => 'tags 标签输入', 'status' => 'available'],
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
                    ['name' => 'name / label / tips', 'summary' => '字段名、标题与提示文案，定义标签多选字段的业务语义。'],
                    ['name' => 'options', 'summary' => '使用键值对定义标签候选项。'],
                    ['name' => 'value', 'summary' => '默认选中项或编辑态回显值，类型为数组。'],
                    ['name' => 'required', 'summary' => '标记为必选字段。'],
                ],
            ],
            [
                'title' => '交互与展示',
                'items' => [
                    ['name' => 'rounded', 'summary' => '切换为胶囊式圆角标签。'],
                    ['name' => 'disabled', 'summary' => '可禁用全部或指定标签项。'],
                    ['name' => 'class / props', 'summary' => '补充类名和属性，用于布局或脚本挂载。'],
                ],
            ],
            [
                'title' => '值处理与选型',
                'items' => [
                    ['name' => '数组提交', 'summary' => 'select_group 提交结果为数组。'],
                    ['name' => '规模建议', 'summary' => '更适合短标签和少量选项，选项过多建议改用 select2。'],
                    ['name' => 'select_group / tags', 'summary' => '固定候选项用 select_group，自由输入用 tags。'],
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
