<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\textarea;

use app\common\render\Form;
use app\common\render\form\items\textarea\Textarea;

/**
 * textarea 默认值与行数能力块
 */
final class TextareaValueRowsSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'value_rows'),
            'title' => (string) ($section['title'] ?? '默认值与行数'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'value', 'value' => '用于详情页回显或编辑态默认内容'],
                ['name' => 'rows', 'value' => '控制 textarea 初始可见行数'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'textarea',
        'name' => 'content',
        'label' => '内容草稿',
        'value' => "第一段：当前版本重点优化了示例页结构。\n第二段：源码展示改成按能力块分布。",
        'rows' => 10,
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::textarea('content', '内容草稿')
    ->value("第一段：当前版本重点优化了示例页结构。\n第二段：源码展示改成按能力块分布。")
    ->rows(10);
CODE,
            'notes' => [
                'rows 控制的是初始高度，不会限制实际输入内容的总行数。',
                '编辑态表单里，value 回显比 placeholder 更重要，避免开发者误把占位文本当默认内容。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/textarea/TextareaValueRowsSection.php',
                    'label' => '默认值与行数能力块',
                    'description' => '展示 value 回显与 rows 行数控制的组合。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_textarea_section_value_rows_', false), '默认值与行数')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                Textarea::make('content', '内容草稿')
                    ->value("第一段：当前版本重点优化了示例页结构。\n第二段：源码展示改成按能力块分布。")
                    ->rows(10)
            )
            ->fetch();
    }
}
