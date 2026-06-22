<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\number;

use app\common\render\Form;
use app\common\render\form\items\number\Number;

/**
 * number 业务综合示例能力块
 */
final class NumberProfileFormSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'profile_form'),
            'title' => (string) ($section['title'] ?? '业务综合示例'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'prefix + min + required', 'value' => '适合价格这类必须填写的金额字段'],
                ['name' => 'value + min', 'value' => '适合库存、排序等带默认数的业务字段'],
                ['name' => 'suffix + step', 'value' => '适合重量、时长、比例等带单位的数值字段'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'number',
        'name' => 'product_price',
        'label' => '商品价格',
        'placeholder' => '请输入价格',
        'prefix' => '¥',
        'min_length' => 0,
        'required' => true,
        'tips' => '单位：元',
    ],
    [
        'type' => 'number',
        'name' => 'product_stock',
        'label' => '库存',
        'value' => 0,
        'min_length' => 0,
        'required' => true,
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::number('product_price', '商品价格', '单位：元')
    ->placeholder('请输入价格')
    ->prefix('¥')
    ->min(0)
    ->required();

Field::number('product_stock', '库存')
    ->value(0)
    ->min(0)
    ->required();
CODE,
            'notes' => [
                '综合示例更接近真实后台录入表单，开发者可以直接替换字段名、单位和范围。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/number/NumberProfileFormSection.php',
                    'label' => '业务综合示例能力块',
                    'description' => '把常见 number 用法组合成真实业务表单片段。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_number_section_profile_', false), '业务综合示例')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                Number::make('product_price', '商品价格', '单位：元')
                    ->placeholder('请输入价格')
                    ->prefix('¥')
                    ->min(0)
                    ->required()
            )
            ->item(
                Number::make('product_stock', '库存')
                    ->value(0)
                    ->min(0)
                    ->required()
            )
            ->item(
                Number::make('product_weight', '重量', '用于物流计费')
                    ->suffix('kg')
                    ->min(0)
                    ->step(1)
                    ->placeholder('请输入重量')
            )
            ->fetch();
    }
}
