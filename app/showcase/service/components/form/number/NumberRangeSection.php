<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\number;

use app\common\render\Form;
use app\common\render\form\items\number\Number;

/**
 * number 范围限制能力块
 */
final class NumberRangeSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'range_limit'),
            'title' => (string) ($section['title'] ?? '最小值与最大值'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'min_length', 'value' => '最小允许值'],
                ['name' => 'max_length', 'value' => '最大允许值'],
                ['name' => 'min()/max()', 'value' => '链式别名，适合快速表达数值范围'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'number',
        'name' => 'age',
        'label' => '年龄',
        'tips' => '请输入 1 到 150 之间的数字',
        'min_length' => 1,
        'max_length' => 150,
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::number('age', '年龄', '请输入 1 到 150 之间的数字')
    ->min(1)
    ->max(150)
    ->placeholder('例如 32');
CODE,
            'notes' => [
                '当前组件命名沿用了 `min_length/max_length`，但在 number 场景里实际表达的是最小值和最大值约束。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/number/NumberRangeSection.php',
                    'label' => '范围限制能力块',
                    'description' => '展示 min/max 范围配置与链式别名写法。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_number_section_range_', false), '最小值与最大值')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                Number::make('age', '年龄', '请输入 1 到 150 之间的数字')
                    ->id('age_range_limit')
                    ->min(1)
                    ->max(150)
                    ->placeholder('例如 32')
            )
            ->fetch();
    }
}
