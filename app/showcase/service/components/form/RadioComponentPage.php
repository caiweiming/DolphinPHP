<?php
declare(strict_types=1);

namespace app\showcase\service\components\form;

/**
 * Showcase radio 组件页组装器
 */
final class RadioComponentPage
{
    /**
     * 组装 radio 组件页
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
                'summary' => 'Radio 组件用于承载互斥选项选择，适合性别、状态、类型、开票方式等只能选择一个答案的后台表单场景。',
                'scenarios' => [
                    '性别、状态、类型、权限等级等只有一个正确选项的单选场景',
                    '选项数量不多，希望直接把候选项平铺给开发者和最终用户查看',
                    '需要结合 when 联动下游字段、并在编辑态明确回显当前选中值的后台表单',
                ],
                'capabilities' => [
                    '基础单选',
                    '默认值与回填',
                    '选项配置',
                    'inline 水平排列',
                    '禁用状态',
                    '必填校验',
                    'when 条件联动',
                    '业务表单片段',
                ],
                'quick_start' => [
                    'array_code' => <<<'CODE'
[
    [
        'type' => 'radio',
        'name' => 'gender',
        'label' => '性别',
        'options' => [
            1 => '男',
            2 => '女',
        ],
        'value' => 1,
    ],
]
CODE,
                    'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::radio('gender', '性别', '请选择性别')
    ->options([
        1 => '男',
        2 => '女',
    ])
    ->value(1)
    ->required();
CODE,
                ],
            ],
            'param_groups' => $this->paramGroups(),
            'sections' => $sections,
            'related_components' => [
                ['key' => 'basic.switch', 'title' => 'switch 开关', 'status' => 'available'],
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
                    ['name' => 'name', 'summary' => '字段名，决定提交键名与单选组 name。'],
                    ['name' => 'label', 'summary' => '字段标题，用于说明这组选项表示什么含义。'],
                    ['name' => 'tips', 'summary' => '字段下方提示文案，适合说明单选规则或业务影响。'],
                    ['name' => 'options', 'summary' => '单选项数组，键为提交值，值为展示文案。'],
                    ['name' => 'value', 'summary' => '默认选中值或编辑态回显值。'],
                ],
            ],
            [
                'title' => '展示与状态',
                'items' => [
                    ['name' => 'inline', 'summary' => '开启后采用水平排列，适合 2 到 3 个短选项。'],
                    ['name' => 'disabled', 'summary' => '禁用全部选项或指定不可选项。'],
                    ['name' => 'required', 'summary' => '标记为必选字段，常与提示文案一起使用。'],
                ],
            ],
            [
                'title' => '联动与选型建议',
                'items' => [
                    ['name' => 'when', 'summary' => 'radio 常作为上游驱动字段控制下游字段显示、隐藏和必填。'],
                    ['name' => '选项数量建议', 'summary' => '2 到 3 个选项优先 radio；更多候选项更适合 select。'],
                    ['name' => 'radio / radio_group', 'summary' => '标准单选更克制，radio_group 更强调视觉展示。'],
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
