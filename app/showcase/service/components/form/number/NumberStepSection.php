<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\number;

use app\common\render\Form;
use app\common\render\form\items\number\Number;

/**
 * number 步进值能力块
 */
final class NumberStepSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'step'),
            'title' => (string) ($section['title'] ?? '步进值 step'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'step', 'value' => '控制每次增减的步长，可用于整数或小数场景'],
                ['name' => 'min_length', 'value' => '常与 step 一起约束可用范围'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'number',
        'name' => 'batch_size',
        'label' => '批量数量',
        'tips' => '只能输入 5 的倍数',
        'step' => 5,
        'min_length' => 0,
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::number('batch_size', '批量数量', '只能输入 5 的倍数')
    ->step(5)
    ->min(0)
    ->placeholder('例如 10、15、20');
CODE,
            'notes' => [
                'step 很适合页容量、库存批次、权重档位这类离散数值场景。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/number/NumberStepSection.php',
                    'label' => '步进值能力块',
                    'description' => '展示 step 对整数步长场景的约束效果。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_number_section_step_', false), '步进值 step')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                Number::make('batch_size', '批量数量', '只能输入 5 的倍数')
                    ->step(5)
                    ->min(0)
                    ->placeholder('例如 10、15、20')
            )
            ->fetch();
    }
}
