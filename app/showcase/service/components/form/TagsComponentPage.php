<?php
declare(strict_types=1);

namespace app\showcase\service\components\form;

/**
 * Showcase tags 组件页组装器
 */
final class TagsComponentPage
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
                'summary' => 'Tags 组件用于承载可新增、可删除的标签输入场景，适合文章标签、关键词、技能标签等需要自由录入多个值的后台表单。',
                'scenarios' => [
                    '文章标签、关键词、技能标签等需要自由输入多个标签的场景',
                    '需要白名单约束和回车快速录入并存的标签输入场景',
                    '需要默认值、只读态和样式调整的多值输入场景',
                ],
                'capabilities' => [
                    '基础标签输入',
                    '提示与占位符',
                    '只读与默认值',
                    '白名单选项',
                    '样式与尺寸',
                    '值处理提示',
                    '业务表单片段',
                ],
                'quick_start' => [
                    'array_code' => <<<'CODE'
[
    [
        'type' => 'tags',
        'name' => 'tags',
        'label' => '标签',
        'tips' => '输入后按回车添加标签',
        'value' => ['PHP', 'ThinkPHP'],
    ],
]
CODE,
                    'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::tags('tags', '标签')
    ->tips('输入后按回车添加标签')
    ->value(['PHP', 'ThinkPHP']);
CODE,
                ],
            ],
            'param_groups' => $this->paramGroups(),
            'sections' => $sections,
            'related_components' => [
                ['key' => 'choice.select_group', 'title' => 'select_group 多选标签', 'status' => 'available'],
                ['key' => 'choice.select2', 'title' => 'select2 增强下拉选择', 'status' => 'available'],
                ['key' => 'basic.text', 'title' => 'text 单行文本框', 'status' => 'available'],
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
                    ['name' => 'name / label / tips', 'summary' => '字段名、标题与提示文案，定义标签输入字段的业务语义。'],
                    ['name' => 'value', 'summary' => '默认标签值或编辑态回显值。'],
                    ['name' => 'placeholder', 'summary' => '指导用户如何录入标签。'],
                    ['name' => 'required', 'summary' => '标记为必填字段。'],
                ],
            ],
            [
                'title' => 'Tagify 配置',
                'items' => [
                    ['name' => 'options.whitelist', 'summary' => '限制可选标签白名单。'],
                    ['name' => 'options.enforceWhitelist', 'summary' => '仅允许录入白名单中的标签。'],
                    ['name' => 'options.maxTags', 'summary' => '限制最多可录入的标签数量。'],
                    ['name' => 'options.duplicates', 'summary' => '控制是否允许重复标签。'],
                ],
            ],
            [
                'title' => '样式与状态',
                'items' => [
                    ['name' => 'readonly', 'summary' => '切换为只读模式。'],
                    ['name' => 'rounded / flush / size / class', 'summary' => '控制输入框外观与尺寸。'],
                    ['name' => 'props', 'summary' => '补充属性，用于脚本挂载或业务标记。'],
                ],
            ],
            [
                'title' => '选型建议',
                'items' => [
                    ['name' => 'tags / select_group', 'summary' => '自由录入用 tags，固定候选项用 select_group。'],
                    ['name' => '值结构', 'summary' => 'tags 常以数组提交，落库前需统一格式。'],
                    ['name' => '白名单', 'summary' => '内容治理要求高时建议开启白名单约束。'],
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
