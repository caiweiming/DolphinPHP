<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\number;

use app\common\render\Form;
use app\common\render\form\items\number\Number;

/**
 * number 前后缀能力块
 */
final class NumberPrefixSuffixSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'prefix_suffix'),
            'title' => (string) ($section['title'] ?? '前缀与后缀'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'prefix', 'value' => '货币、语义前缀等输入上下文'],
                ['name' => 'suffix', 'value' => '单位、比例等尾部提示'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'number',
        'name' => 'price',
        'label' => '价格',
        'tips' => '用于商品最终售价',
        'prefix' => '¥',
    ],
    [
        'type' => 'number',
        'name' => 'weight',
        'label' => '重量',
        'tips' => '用于物流计费',
        'suffix' => 'kg',
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::number('price', '价格', '用于商品最终售价')
    ->prefix('¥')
    ->min(0);

Field::number('weight', '重量', '用于物流计费')
    ->suffix('kg')
    ->min(0);
CODE,
            'notes' => [
                '前后缀不会参与实际提交值，只是帮助用户理解当前数字的业务上下文。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/number/NumberPrefixSuffixSection.php',
                    'label' => '前后缀能力块',
                    'description' => '展示货币符号、单位后缀等数值上下文配置。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_number_section_prefix_suffix_', false), '前缀与后缀')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                Number::make('price', '价格', '用于商品最终售价')
                    ->id('price_prefix_suffix')
                    ->prefix('¥')
                    ->min(0)
                    ->placeholder('请输入价格')
            )
            ->item(
                Number::make('weight', '重量', '用于物流计费')
                    ->suffix('kg')
                    ->min(0)
                    ->placeholder('请输入重量')
            )
            ->fetch();
    }
}
