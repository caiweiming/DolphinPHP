<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\radio_group;

use app\common\render\Form;

/**
 * radio_group when 条件联动能力块
 */
final class RadioGroupWhenSection
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
                ['name' => 'when.field', 'value' => '通过 delivery_mode 驱动下游字段显隐'],
                ['name' => 'when.then/else', 'value' => '命中时 show + require，未命中时 hide + clear'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'radio_group',
        'name' => 'delivery_mode',
        'label' => '交付方式',
        'options' => [
            'express' => '<div class="fw-semibold">快递配送</div><div class="text-secondary small">适合实体商品寄送</div>',
            'pickup' => '<div class="fw-semibold">到店自提</div><div class="text-secondary small">门店核销后自提</div>',
        ],
        'value' => 'express',
    ],
    [
        'type' => 'text',
        'name' => 'self_pickup_store',
        'label' => '自提门店',
        'tips' => '仅到店自提时需要选择门店',
        'when' => [
            'field' => 'delivery_mode',
            'op' => 'eq',
            'value' => 'pickup',
            'then' => ['show', 'require'],
            'else' => ['hide', 'unrequire', 'clear'],
        ],
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::radioGroup('delivery_mode', '交付方式')
    ->options([
        'express' => '<div class="fw-semibold">快递配送</div><div class="text-secondary small">适合实体商品寄送</div>',
        'pickup' => '<div class="fw-semibold">到店自提</div><div class="text-secondary small">门店核销后自提</div>',
    ])
    ->value('express');

Field::text('self_pickup_store', '自提门店', '仅到店自提时需要选择门店')
    ->when('delivery_mode', 'eq', 'pickup', ['show', 'require'], ['hide', 'unrequire', 'clear']);
CODE,
            'notes' => [
                'radio_group 很适合作为“方案入口字段”，先让用户选模式，再决定后面展示哪些配置项。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/radio_group/RadioGroupWhenSection.php',
                    'label' => 'when 条件联动能力块',
                    'description' => '展示 radio_group 驱动下游字段显隐与必填的常见写法。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_radio_group_section_when_', false), 'when 条件联动')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->items([
                [
                    'type' => 'radio_group',
                    'name' => 'delivery_mode',
                    'label' => '交付方式',
                    'options' => [
                        'express' => '<div class="fw-semibold">快递配送</div><div class="text-secondary small">适合实体商品寄送</div>',
                        'pickup' => '<div class="fw-semibold">到店自提</div><div class="text-secondary small">门店核销后自提</div>',
                    ],
                    'value' => 'express',
                ],
                [
                    'type' => 'text',
                    'name' => 'self_pickup_store',
                    'label' => '自提门店',
                    'tips' => '仅到店自提时需要选择门店',
                    'when' => [
                        'field' => 'delivery_mode',
                        'op' => 'eq',
                        'value' => 'pickup',
                        'then' => ['show', 'require'],
                        'else' => ['hide', 'unrequire', 'clear'],
                    ],
                ],
            ])
            ->fetch();
    }
}
