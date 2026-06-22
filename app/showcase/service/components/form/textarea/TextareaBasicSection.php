<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\textarea;

use app\common\render\Form;
use app\common\render\form\items\textarea\Textarea;

/**
 * textarea 基础用法能力块
 */
final class TextareaBasicSection
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
                ['name' => 'name', 'value' => 'description / notes'],
                ['name' => 'label', 'value' => '描述 / 备注'],
            ],
            'array_code' => <<<'CODE'
[
    ['textarea', 'description', '描述', '请输入描述信息'],
    ['textarea', 'notes', '备注', '请输入补充说明'],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::textarea('description', '描述', '请输入描述信息');
Field::textarea('notes', '备注', '请输入补充说明');
CODE,
            'notes' => [
                '基础 textarea 适合最直接的多行纯文本输入场景。',
                '默认就会渲染真实 textarea，方便观察 rows、placeholder 和内容回显结构。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/textarea/TextareaBasicSection.php',
                    'label' => '基础用法能力块',
                    'description' => '组装 textarea 的基础示例与代码片段。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_textarea_section_basic_', false), '基础用法')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                Textarea::make('description', '描述', '请输入描述信息')
                    ->placeholder('请输入描述信息')
            )
            ->item(
                Textarea::make('notes', '备注', '请输入补充说明')
                    ->placeholder('例如 记录交付说明、内部备注等')
            )
            ->fetch();
    }
}
