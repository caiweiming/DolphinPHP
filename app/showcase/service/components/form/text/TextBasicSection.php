<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\text;

use app\common\render\Form;
use app\common\render\form\items\text\Text;

/**
 * text 基础用法能力块
 */
final class TextBasicSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'basic'),
            'title' => (string) ($section['title'] ?? '基础用法'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'name', 'value' => 'nickname / job_title'],
                ['name' => 'label', 'value' => '昵称 / 职位'],
            ],
            'array_code' => <<<'CODE'
[
    ['text', 'nickname', '昵称', '用于列表与欢迎语展示'],
    ['text', 'job_title', '职位', '例如产品经理、设计师'],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::text('nickname', '昵称', '用于列表与欢迎语展示');
Field::text('job_title', '职位', '例如产品经理、设计师');
CODE,
            'notes' => [
                '基础输入适合承载最常见的短文本信息。',
                '默认就会输出真实 input，便于直接观察最终结构。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/text/TextBasicSection.php',
                    'label' => '基础用法能力块',
                    'description' => '组装 text 的基础示例与代码片段。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_text_section_basic_', false), '基础用法')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(Text::make('nickname', '昵称', '用于列表与欢迎语展示')->placeholder('请输入昵称'))
            ->item(Text::make('job_title', '职位', '例如产品经理、设计师')->placeholder('请输入职位'))
            ->fetch();
    }
}
