<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\textarea;

use app\common\render\Form;
use app\common\render\form\items\textarea\Textarea;

/**
 * textarea 业务综合示例能力块
 */
final class TextareaProfileFormSection
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
                ['name' => 'rows + max_length', 'value' => '适合摘要、备注这类中短多行文本'],
                ['name' => 'required + autosize', 'value' => '适合正文说明、反馈内容这类长度不稳定字段'],
                ['name' => 'flush', 'value' => '适合嵌入式备注输入场景'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'textarea',
        'name' => 'article_summary',
        'label' => '文章摘要',
        'placeholder' => '请输入摘要',
        'rows' => 5,
        'max_length' => 200,
        'tips' => '摘要会展示在列表页，建议控制在 200 字以内',
    ],
    [
        'type' => 'textarea',
        'name' => 'article_content',
        'label' => '正文说明',
        'placeholder' => '请输入正文说明',
        'rows' => 8,
        'autosize' => true,
        'required' => true,
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::textarea('article_summary', '文章摘要', '摘要会展示在列表页，建议控制在 200 字以内')
    ->placeholder('请输入摘要')
    ->rows(5)
    ->max(200);

Field::textarea('article_content', '正文说明')
    ->placeholder('请输入正文说明')
    ->rows(8)
    ->autosize()
    ->required();
CODE,
            'notes' => [
                '综合示例更接近真实后台编辑表单，开发者可以直接按字段名和提示语替换为自己的业务内容。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/textarea/TextareaProfileFormSection.php',
                    'label' => '业务综合示例能力块',
                    'description' => '把常见 textarea 用法组合成真实业务表单片段。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_textarea_section_profile_', false), '业务综合示例')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                Textarea::make('article_summary', '文章摘要', '摘要会展示在列表页，建议控制在 200 字以内')
                    ->placeholder('请输入摘要')
                    ->rows(5)
                    ->max(200)
            )
            ->item(
                Textarea::make('article_content', '正文说明')
                    ->placeholder('请输入正文说明')
                    ->rows(8)
                    ->autosize()
                    ->required()
            )
            ->item(
                Textarea::make('internal_remark', '内部备注', '嵌入式场景可使用扁平化样式减少视觉重量')
                    ->flush()
                    ->rows(4)
                    ->placeholder('例如 记录本次审核补充说明')
            )
            ->fetch();
    }
}
