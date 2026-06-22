<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\ueditor;

use app\common\render\Form;
use app\common\render\form\items\ueditor\Ueditor;

/**
 * ueditor 编辑器高度能力块
 */
final class UeditorHeightSection
{
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'height'),
            'title' => (string) ($section['title'] ?? '编辑器高度'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'options.initialFrameHeight', 'value' => '常用于文章正文、帮助文档等不同篇幅场景'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'ueditor',
        'name' => 'help_content',
        'label' => '帮助内容',
        'options' => [
            'initialFrameHeight' => 480,
        ],
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::ueditor('help_content', '帮助内容')
    ->options([
        'initialFrameHeight' => 480,
    ]);
CODE,
            'notes' => [
                'UEditor 没有单独的高度链式方法，高度调整统一通过 options 透传。',
            ],
            'source_refs' => [[
                'path' => 'app/showcase/service/components/form/ueditor/UeditorHeightSection.php',
                'label' => '编辑器高度能力块',
                'description' => '展示 ueditor 如何通过 options 控制编辑器可视高度。',
            ]],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_ueditor_section_height_', false), '编辑器高度')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                Ueditor::make('help_content', '帮助内容')
                    ->options([
                        'initialFrameHeight' => 480,
                    ])
            )
            ->fetch();
    }
}
