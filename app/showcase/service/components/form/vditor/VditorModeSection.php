<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\vditor;

use app\common\render\Form;
use app\common\render\form\items\vditor\Vditor;

/**
 * vditor 编辑模式能力块
 */
final class VditorModeSection
{
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'mode'),
            'title' => (string) ($section['title'] ?? '编辑模式'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'options.mode', 'value' => '支持 wysiwyg / ir / sv 三种模式切换'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'vditor',
        'name' => 'doc_content',
        'label' => '文档内容',
        'options' => [
            'mode' => 'sv',
        ],
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::vditor('doc_content', '文档内容')
    ->options([
        'mode' => 'sv',
    ]);
CODE,
            'notes' => [
                'vditor 的核心差异之一就是编辑模式切换，示例里直接展示最常见的 `sv` 分屏预览写法。',
            ],
            'source_refs' => [[
                'path' => 'app/showcase/service/components/form/vditor/VditorModeSection.php',
                'label' => '编辑模式能力块',
                'description' => '展示 vditor 如何通过 options.mode 切换编辑模式。',
            ]],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_vditor_section_mode_', false), '编辑模式')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                Vditor::make('doc_content', '文档内容')
                    ->id('doc_content_mode')
                    ->options([
                        'mode' => 'sv',
                    ])
            )
            ->fetch();
    }
}
