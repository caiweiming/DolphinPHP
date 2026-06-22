<?php
declare(strict_types=1);

namespace app\showcase\service\components\form;

/**
 * Showcase image_select 组件页组装器
 */
final class ImageSelectComponentPage
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
                'summary' => 'ImageSelect 组件用于从预设图片集中做单选或多选，适合头像库、模板皮肤、图标方案等不需要上传、只需要挑选资源的后台表单场景。',
                'scenarios' => [
                    '头像库、皮肤模板、图标方案等从预设图片集中直接选择的场景',
                    '希望把图片选择做成可视化单选或多选，而不是纯文本下拉的后台表单',
                    '需要控制部分图片禁用、布局密度和默认选中项的资源选择场景',
                ],
                'capabilities' => [
                    '基础选择',
                    '默认值与回填',
                    '多选模式',
                    '禁用状态',
                    '样式与布局',
                    '选项结构',
                    '业务表单片段',
                ],
                'quick_start' => [
                    'array_code' => <<<'CODE'
[
    [
        'type' => 'image_select',
        'name' => 'avatar',
        'label' => '头像',
        'options' => [
            'avatar_1' => '/static/avatars/1.png',
            'avatar_2' => '/static/avatars/2.png',
            'avatar_3' => '/static/avatars/3.png',
        ],
        'value' => 'avatar_2',
    ],
]
CODE,
                    'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::imageSelect('avatar', '头像')
    ->options([
        'avatar_1' => '/static/avatars/1.png',
        'avatar_2' => '/static/avatars/2.png',
        'avatar_3' => '/static/avatars/3.png',
    ])
    ->value('avatar_2');
CODE,
                ],
            ],
            'param_groups' => $this->paramGroups(),
            'sections' => $sections,
            'related_components' => [
                ['key' => 'media.image', 'title' => 'image 图片上传', 'status' => 'available'],
                ['key' => 'choice.radio_group', 'title' => 'radio_group 单选标签组', 'status' => 'available'],
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
                    ['name' => 'name / label / tips', 'summary' => '字段名、标题与提示文案，定义图片选择字段的业务语义。'],
                    ['name' => 'options', 'summary' => '支持字符串 URL 列表或包含 `src/class/alt` 的对象结构。'],
                    ['name' => 'value', 'summary' => '默认值或编辑态回显值，单选为选项 key，多选为 key 数组。'],
                    ['name' => 'multiple', 'summary' => '开启图片多选模式。'],
                ],
            ],
            [
                'title' => '交互与状态',
                'items' => [
                    ['name' => 'disabled', 'summary' => '可禁用全部图片或指定图片项。'],
                    ['name' => 'class / label_class', 'summary' => '控制图片卡片布局与列宽。'],
                    ['name' => 'required', 'summary' => '标记为必选图片字段。'],
                    ['name' => 'tips', 'summary' => '补充用途、推荐选择范围等提示。'],
                ],
            ],
            [
                'title' => '选型建议',
                'items' => [
                    ['name' => 'image_select / image', 'summary' => '资源已预置时用 image_select；需要上传新图片时用 image。'],
                    ['name' => 'image_select / radio_group', 'summary' => '选项本身以图片视觉为主时用 image_select；文字主导时用 radio_group。'],
                    ['name' => '资源治理', 'summary' => '适合主题皮肤、头像库这类受控资源选择场景。'],
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
