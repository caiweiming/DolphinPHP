<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\number;

use app\common\render\Form;
use app\common\render\form\items\number\Number;

/**
 * number 占位符与提示能力块
 */
final class NumberPlaceholderSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'placeholder_tips'),
            'title' => (string) ($section['title'] ?? '占位符与提示'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'placeholder', 'value' => '提示用户期望输入的数值格式或范围'],
                ['name' => 'tips', 'value' => '说明单位、业务规则或取值建议'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'number',
        'name' => 'price',
        'label' => '价格',
        'tips' => '请输入商品最终售价，单位：元',
        'placeholder' => '例如 199',
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::number('price', '价格', '请输入商品最终售价，单位：元')
    ->placeholder('例如 199');
CODE,
            'notes' => [
                '数字输入也需要清晰的 placeholder 和 tips，尤其是金额、比例、年龄这类业务含义容易歧义的字段。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/number/NumberPlaceholderSection.php',
                    'label' => '占位提示能力块',
                    'description' => '展示 placeholder 与 tips 在数值场景中的组合方式。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_number_section_placeholder_', false), '占位符与提示')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                Number::make('price', '价格', '请输入商品最终售价，单位：元')
                    ->id('price_placeholder_tips')
                    ->placeholder('例如 199')
            )
            ->fetch();
    }
}
