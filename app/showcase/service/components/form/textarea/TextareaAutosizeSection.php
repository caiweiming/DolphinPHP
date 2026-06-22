<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\textarea;

use app\common\render\Form;
use app\common\render\form\items\textarea\Textarea;

/**
 * textarea autosize 能力块
 */
final class TextareaAutosizeSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'autosize'),
            'title' => (string) ($section['title'] ?? '自动增高 autosize'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'autosize', 'value' => '内容增长时自动调整 textarea 高度'],
                ['name' => 'rows', 'value' => '可与 autosize 搭配设置初始高度'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'textarea',
        'name' => 'remark',
        'label' => '处理备注',
        'tips' => '内容越长，输入框会随之增高',
        'rows' => 4,
        'autosize' => true,
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::textarea('remark', '处理备注', '内容越长，输入框会随之增高')
    ->rows(4)
    ->autosize()
    ->placeholder('适合记录长度不确定的过程说明');
CODE,
            'notes' => [
                'autosize 更适合内容长度不稳定的备注、处理记录、反馈说明等字段。',
                'rows 仍可以作为初始高度基线，后续再由 autosize 根据内容扩展。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/textarea/TextareaAutosizeSection.php',
                    'label' => 'autosize 能力块',
                    'description' => '展示 textarea 自动增高的配置方式。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_textarea_section_autosize_', false), '自动增高 autosize')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                Textarea::make('remark', '处理备注', '内容越长，输入框会随之增高')
                    ->rows(4)
                    ->autosize()
                    ->placeholder('适合记录长度不确定的过程说明')
            )
            ->fetch();
    }
}
