<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\radio;

use app\common\render\Form;
use app\common\render\form\items\radio\Radio;

/**
 * radio 必填校验能力块
 */
final class RadioRequiredSection
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
                ['name' => 'tips', 'value' => '建议用 tips 告诉开发者不选时的业务后果'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'radio',
        'name' => 'invoice_type',
        'label' => '发票类型',
        'tips' => '请先确认需要普票还是专票，提交后会影响财务流程',
        'options' => [
            'normal' => '普票',
            'vat' => '专票',
        ],
        'required' => true,
        'value' => 'normal',
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::radio('invoice_type', '发票类型', '请先确认需要普票还是专票，提交后会影响财务流程')
    ->options([
        'normal' => '普票',
        'vat' => '专票',
    ])
    ->required()
    ->value('normal');
CODE,
            'notes' => [
                'radio 常用来承接“必须明确二选一或三选一”的业务场景，required 很常见。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/radio/RadioRequiredSection.php',
                    'label' => '必填校验能力块',
                    'description' => '展示 required 与提示文案在 radio 场景中的常见搭配。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_radio_section_required_', false), '必填校验')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                Radio::make('invoice_type', '发票类型', '请先确认需要普票还是专票，提交后会影响财务流程')
                    ->id('invoice_type_required')
                    ->options([
                        'normal' => '普票',
                        'vat' => '专票',
                    ])
                    ->required()
                    ->value('normal')
            )
            ->fetch();
    }
}
