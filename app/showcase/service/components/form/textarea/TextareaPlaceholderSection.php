<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\textarea;

use app\common\render\Form;
use app\common\render\form\items\textarea\Textarea;

/**
 * textarea 占位符与提示能力块
 */
final class TextareaPlaceholderSection
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
                ['name' => 'placeholder', 'value' => '引导用户如何组织多行内容'],
                ['name' => 'tips', 'value' => '在字段下方补充字数、格式或注意事项'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'textarea',
        'name' => 'feedback',
        'label' => '反馈内容',
        'tips' => '建议按“问题现象、复现步骤、期望结果”三个部分填写',
        'placeholder' => "请先写问题现象，再写复现步骤\n最后补充你期望的处理结果",
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::textarea('feedback', '反馈内容', '建议按“问题现象、复现步骤、期望结果”三个部分填写')
    ->placeholder("请先写问题现象，再写复现步骤\n最后补充你期望的处理结果");
CODE,
            'notes' => [
                '多行输入的 placeholder 更适合直接给出内容结构，帮助用户降低组织成本。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/textarea/TextareaPlaceholderSection.php',
                    'label' => '占位提示能力块',
                    'description' => '展示 placeholder 与 tips 在多行输入中的组合方式。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_textarea_section_placeholder_', false), '占位符与提示')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                Textarea::make('feedback', '反馈内容', '建议按“问题现象、复现步骤、期望结果”三个部分填写')
                    ->placeholder("请先写问题现象，再写复现步骤\n最后补充你期望的处理结果")
                    ->rows(6)
            )
            ->fetch();
    }
}
