<?php
declare(strict_types=1);

namespace app\showcase\service\components\form;

/**
 * Showcase radio_group 组件页组装器
 */
final class RadioGroupComponentPage
{
    /**
     * 组装 radio_group 组件页
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
                'summary' => 'RadioGroup 组件用于承载卡片式的互斥选项选择，适合套餐、方案、等级、风格模板等需要更强视觉表达的单选场景。',
                'scenarios' => [
                    '套餐、方案、价格档位等需要更大展示面积的单选场景',
                    '希望在选项中同时展示标题、副标题、说明和业务差异点的配置页',
                    '既要保留单选互斥语义，又希望比标准 radio 更有视觉层次的后台表单',
                ],
                'capabilities' => [
                    '基础卡片单选',
                    '默认值与回填',
                    '富文案选项',
                    '组件对象选项',
                    '禁用状态',
                    '必填校验',
                    '套餐型展示',
                    'when 条件联动',
                    '业务表单片段',
                ],
                'quick_start' => [
                    'array_code' => <<<'CODE'
[
    [
        'type' => 'radio_group',
        'name' => 'plan',
        'label' => '套餐',
        'tips' => '请选择套餐',
        'options' => [
            'starter' => '基础版',
            'pro' => '专业版',
            'enterprise' => '企业版',
        ],
        'value' => 'pro',
        'required' => true,
    ],
]
CODE,
                    'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::radioGroup('plan', '套餐', '请选择套餐')
    ->options([
        'starter' => '基础版',
        'pro' => '专业版',
        'enterprise' => '企业版',
    ])
    ->value('pro')
    ->required();
CODE,
                ],
            ],
            'param_groups' => $this->paramGroups(),
            'sections' => $sections,
            'related_components' => [
                ['key' => 'choice.radio', 'title' => 'radio 单选框', 'status' => 'available'],
                ['key' => 'choice.select', 'title' => 'select 下拉选择', 'status' => 'available'],
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
                    ['name' => 'name', 'summary' => '字段名，决定提交键名与整组 radio 的 name。'],
                    ['name' => 'label', 'summary' => '字段标题，用于说明这组卡片选项代表什么业务含义。'],
                    ['name' => 'tips', 'summary' => '字段下方提示文案，适合说明选型规则或价格影响。'],
                    ['name' => 'options', 'summary' => '单选项数组，键为提交值，值可为纯文本、HTML 富文案或组件对象渲染结果。'],
                    ['name' => 'value', 'summary' => '默认选中值或编辑态回显值。'],
                ],
            ],
            [
                'title' => '展示与状态',
                'items' => [
                    ['name' => 'required', 'summary' => '标记为必选字段，适合套餐、方案等必须明确选择的场景。'],
                    ['name' => 'disabled', 'summary' => '可禁用指定选项，用于灰掉不可用套餐或已下线方案。'],
                    ['name' => 'class / props', 'summary' => '可补充额外类名和属性，配合卡片布局做轻量增强。'],
                ],
            ],
            [
                'title' => '联动与选型建议',
                'items' => [
                    ['name' => 'when', 'summary' => 'radio_group 常作为方案入口字段，驱动下游附加配置显隐。'],
                    ['name' => 'radio / radio_group', 'summary' => 'radio 更简洁克制，radio_group 更适合需要展示差异点的卡片式单选。'],
                    ['name' => '适用选项数量', 'summary' => '通常适合 2 到 4 个需要重点比较的候选项，过多时更建议 select。'],
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
