<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\number;

use app\common\render\Form;
use app\common\render\form\items\number\Number;

/**
 * number 校验状态能力块
 */
final class NumberValidationSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'validation'),
            'title' => (string) ($section['title'] ?? '校验与反馈状态'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'valid', 'value' => 'true 为成功态，false 为失败态'],
                ['name' => 'tips', 'value' => '配合反馈文案展示校验结果'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'number',
        'name' => 'valid_stock',
        'label' => '可用库存',
        'tips' => '库存值合法，可直接上架',
        'valid' => true,
        'value' => 256,
    ],
    [
        'type' => 'number',
        'name' => 'invalid_discount',
        'label' => '折扣比例',
        'tips' => '折扣值超出允许范围，请重新输入',
        'valid' => false,
        'value' => 135,
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::number('valid_stock', '可用库存', '库存值合法，可直接上架')
    ->valid(true)
    ->value(256);

Field::number('invalid_discount', '折扣比例', '折扣值超出允许范围，请重新输入')
    ->valid(false)
    ->value(135);
CODE,
            'notes' => [
                'valid() 只负责反馈展示，不替代后端真正的数值规则校验。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/number/NumberValidationSection.php',
                    'label' => '校验状态能力块',
                    'description' => '展示 valid(true/false) 在数值输入中的视觉反馈。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_number_section_validation_', false), '校验与反馈状态')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(Number::make('valid_stock', '可用库存', '库存值合法，可直接上架')->valid(true)->value(256))
            ->item(Number::make('invalid_discount', '折扣比例', '折扣值超出允许范围，请重新输入')->valid(false)->value(135))
            ->fetch();
    }
}
