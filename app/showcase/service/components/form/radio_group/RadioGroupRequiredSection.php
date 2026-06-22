<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\radio_group;

use app\common\render\Form;
use app\common\render\form\items\radio_group\RadioGroup;

/**
 * radio_group 必填校验能力块
 */
final class RadioGroupRequiredSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'required'),
            'title' => (string) ($section['title'] ?? '必填校验'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'required', 'value' => 'true 后在标签上显示必填标识'],
                ['name' => 'tips', 'value' => '建议用 tips 明确告知开发者不选择时的业务影响'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'radio_group',
        'name' => 'onboarding_plan',
        'label' => '开通方案',
        'tips' => '请先确认要开通的版本，提交后会影响试用权益',
        'options' => [
            'trial' => '试用版',
            'team' => '团队版',
            'enterprise' => '企业版',
        ],
        'required' => true,
        'value' => 'team',
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::radioGroup('onboarding_plan', '开通方案', '请先确认要开通的版本，提交后会影响试用权益')
    ->options([
        'trial' => '试用版',
        'team' => '团队版',
        'enterprise' => '企业版',
    ])
    ->required()
    ->value('team');
CODE,
            'notes' => [
                '套餐、方案、模板等关键选型字段通常都应标记必填，避免提交后走默认逻辑。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/radio_group/RadioGroupRequiredSection.php',
                    'label' => '必填校验能力块',
                    'description' => '展示 required 与提示文案在 radio_group 场景中的常见搭配。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_radio_group_section_required_', false), '必填校验')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                RadioGroup::make('onboarding_plan', '开通方案', '请先确认要开通的版本，提交后会影响试用权益')
                    ->options([
                        'trial' => '试用版',
                        'team' => '团队版',
                        'enterprise' => '企业版',
                    ])
                    ->required()
                    ->value('team')
            )
            ->fetch();
    }
}
