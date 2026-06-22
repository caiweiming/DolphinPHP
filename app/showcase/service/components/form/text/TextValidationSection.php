<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\text;

use app\common\render\Form;
use app\common\render\form\items\text\Text;

/**
 * text 校验状态能力块
 */
final class TextValidationSection
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
        'type' => 'text',
        'name' => 'valid_slug',
        'label' => '可用标识',
        'tips' => '该标识可直接用于页面 URL',
        'valid' => true,
        'value' => 'showcase-text-demo',
    ],
    [
        'type' => 'text',
        'name' => 'invalid_slug',
        'label' => '冲突标识',
        'tips' => '该标识已被其他页面占用',
        'valid' => false,
        'value' => 'homepage',
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::text('valid_slug', '可用标识', '该标识可直接用于页面 URL')
    ->valid(true)
    ->value('showcase-text-demo');

Field::text('invalid_slug', '冲突标识', '该标识已被其他页面占用')
    ->valid(false)
    ->value('homepage');
CODE,
            'notes' => [
                'valid() 不改变业务校验规则本身，而是用于展示当前字段的校验反馈状态。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/text/TextValidationSection.php',
                    'label' => '校验状态能力块',
                    'description' => '展示 valid(true/false) 对应的视觉反馈。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_text_section_validation_', false), '校验与反馈状态')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                Text::make('valid_slug', '可用标识', '该标识可直接用于页面 URL')
                    ->valid(true)
                    ->value('showcase-text-demo')
            )
            ->item(
                Text::make('invalid_slug', '冲突标识', '该标识已被其他页面占用')
                    ->valid(false)
                    ->value('homepage')
            )
            ->fetch();
    }
}
