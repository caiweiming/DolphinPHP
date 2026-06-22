<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\number;

use app\common\render\Form;
use app\common\render\form\items\number\Number;

/**
 * number 状态能力块
 */
final class NumberStateSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'state'),
            'title' => (string) ($section['title'] ?? '只读与禁用'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'readonly', 'value' => '保留展示与复制语义，但不允许修改'],
                ['name' => 'disabled', 'value' => '直接禁用输入交互'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'number',
        'name' => 'readonly_id',
        'label' => '只读编号',
        'value' => 2026052901,
        'readonly' => true,
    ],
    [
        'type' => 'number',
        'name' => 'disabled_total',
        'label' => '禁用总额',
        'value' => 8888,
        'disabled' => true,
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::number('readonly_id', '只读编号')
    ->value(2026052901)
    ->readonly();

Field::number('disabled_total', '禁用总额')
    ->value(8888)
    ->disabled();
CODE,
            'notes' => [
                'readonly 适合系统生成但需要查看的编号，disabled 更适合当前节点不可编辑的合计类字段。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/number/NumberStateSection.php',
                    'label' => '状态能力块',
                    'description' => '展示 readonly 与 disabled 在数值字段中的差异。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_number_section_state_', false), '只读与禁用')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(Number::make('readonly_id', '只读编号')->value(2026052901)->readonly())
            ->item(Number::make('disabled_total', '禁用总额')->value(8888)->disabled())
            ->fetch();
    }
}
