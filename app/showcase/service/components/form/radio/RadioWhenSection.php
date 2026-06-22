<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\radio;

use app\common\render\Form;

/**
 * radio when 条件联动能力块
 */
final class RadioWhenSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'when'),
            'title' => (string) ($section['title'] ?? 'when 条件联动'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'when.field', 'value' => '通过 radio 字段 invoice_type 驱动下游字段显隐'],
                ['name' => 'when.then/else', 'value' => '命中时 show + require，未命中时 hide + clear'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'radio',
        'name' => 'invoice_type',
        'label' => '发票类型',
        'inline' => true,
        'options' => [
            'normal' => '普票',
            'vat' => '专票',
        ],
        'value' => 'normal',
    ],
    [
        'type' => 'text',
        'name' => 'tax_no',
        'label' => '税号',
        'tips' => '仅专票时需要填写',
        'when' => [
            'field' => 'invoice_type',
            'op' => 'eq',
            'value' => 'vat',
            'then' => ['show', 'require'],
            'else' => ['hide', 'unrequire', 'clear'],
        ],
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::radio('invoice_type', '发票类型')
    ->inline()
    ->options([
        'normal' => '普票',
        'vat' => '专票',
    ])
    ->value('normal');

Field::text('tax_no', '税号', '仅专票时需要填写')
    ->when('invoice_type', 'eq', 'vat', ['show', 'require'], ['hide', 'unrequire', 'clear']);
CODE,
            'notes' => [
                'radio 很适合作为表单里的“分流字段”，根据选中值决定后续展示哪些额外输入项。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/radio/RadioWhenSection.php',
                    'label' => 'when 条件联动能力块',
                    'description' => '展示 radio 驱动下游字段显隐与必填的常见写法。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_radio_section_when_', false), 'when 条件联动')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->items([
                [
                    'type' => 'radio',
                    'name' => 'invoice_type',
                    'id' => 'invoice_type_when',
                    'label' => '发票类型',
                    'inline' => true,
                    'options' => [
                        'normal' => '普票',
                        'vat' => '专票',
                    ],
                    'value' => 'normal',
                ],
                [
                    'type' => 'text',
                    'name' => 'tax_no',
                    'label' => '税号',
                    'tips' => '仅专票时需要填写',
                    'when' => [
                        'field' => 'invoice_type',
                        'op' => 'eq',
                        'value' => 'vat',
                        'then' => ['show', 'require'],
                        'else' => ['hide', 'unrequire', 'clear'],
                    ],
                ],
            ])
            ->fetch();
    }
}
