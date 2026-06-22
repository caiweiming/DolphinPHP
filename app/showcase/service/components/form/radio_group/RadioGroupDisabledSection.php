<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\radio_group;

use app\common\render\Form;
use app\common\render\form\items\radio_group\RadioGroup;

/**
 * radio_group 禁用状态能力块
 */
final class RadioGroupDisabledSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'disabled'),
            'title' => (string) ($section['title'] ?? '禁用状态'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'disabled', 'value' => '可传数组禁用指定选项，适合下线套餐或灰度中方案'],
                ['name' => 'value', 'value' => '通常仍保留当前选中值，便于开发者识别当前配置'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'radio_group',
        'name' => 'locked_plan',
        'label' => '可选套餐',
        'tips' => '企业版正在灰度中，当前账号不可升级',
        'options' => [
            'starter' => '基础版',
            'growth' => '增长版',
            'enterprise' => '企业版（灰度中）',
        ],
        'value' => 'growth',
        'disabled' => ['enterprise'],
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::radioGroup('locked_plan', '可选套餐', '企业版正在灰度中，当前账号不可升级')
    ->options([
        'starter' => '基础版',
        'growth' => '增长版',
        'enterprise' => '企业版（灰度中）',
    ])
    ->value('growth')
    ->disabled(['enterprise']);
CODE,
            'notes' => [
                '对 radio_group 来说，禁用单个卡片通常比禁用整组更有业务价值，也更容易说明原因。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/radio_group/RadioGroupDisabledSection.php',
                    'label' => '禁用状态能力块',
                    'description' => '展示 radio_group 在不可选套餐或灰度方案场景下的禁用态效果。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_radio_group_section_disabled_', false), '禁用状态')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                RadioGroup::make('locked_plan', '可选套餐', '企业版正在灰度中，当前账号不可升级')
                    ->options([
                        'starter' => '基础版',
                        'growth' => '增长版',
                        'enterprise' => '企业版（灰度中）',
                    ])
                    ->value('growth')
                    ->disabled(['enterprise'])
            )
            ->fetch();
    }
}
