<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\radio_group;

use app\common\render\Form;
use app\common\render\form\items\radio_group\RadioGroup;

/**
 * radio_group 基础卡片单选能力块
 */
final class RadioGroupBasicSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'basic'),
            'title' => (string) ($section['title'] ?? '基础卡片单选'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'name', 'value' => 'plan'],
                ['name' => 'options', 'value' => '使用键值对定义互斥候选项，每个选项渲染为整张卡片'],
                ['name' => 'value', 'value' => 'pro 表示默认选中“专业版”'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'radio_group',
        'name' => 'plan',
        'label' => '套餐',
        'tips' => '请选择适合当前团队规模的套餐',
        'options' => [
            'starter' => '基础版',
            'pro' => '专业版',
            'enterprise' => '企业版',
        ],
        'value' => 'pro',
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::radioGroup('plan', '套餐', '请选择适合当前团队规模的套餐')
    ->options([
        'starter' => '基础版',
        'pro' => '专业版',
        'enterprise' => '企业版',
    ])
    ->value('pro');
CODE,
            'notes' => [
                '基础卡片单选适合让开发者快速理解 radio_group 与标准 radio 在视觉层级上的区别。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/radio_group/RadioGroupBasicSection.php',
                    'label' => '基础卡片单选能力块',
                    'description' => '组装 radio_group 的最小可用示例与代码片段。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_radio_group_section_basic_', false), '基础卡片单选')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                RadioGroup::make('plan', '套餐', '请选择适合当前团队规模的套餐')
                    ->options([
                        'starter' => '基础版',
                        'pro' => '专业版',
                        'enterprise' => '企业版',
                    ])
                    ->value('pro')
            )
            ->fetch();
    }
}
