<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\ueditor;

use app\common\render\Form;
use app\common\render\form\items\ueditor\Ueditor;

/**
 * ueditor 原生 options 配置能力块
 */
final class UeditorNativeOptionsSection
{
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'native_options'),
            'title' => (string) ($section['title'] ?? '原生 options 配置'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'options.toolbars', 'value' => '自定义工具栏分组'],
                ['name' => 'options.wordCount / autoFloatEnabled', 'value' => '控制字数统计和浮动工具栏'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'ueditor',
        'name' => 'content',
        'label' => '正文',
        'options' => [
            'toolbars' => [[
                'bold', 'italic', 'underline', 'insertimage', 'link', 'source',
            ]],
            'wordCount' => true,
            'autoFloatEnabled' => false,
        ],
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::ueditor('content', '正文')
    ->options([
        'toolbars' => [[
            'bold', 'italic', 'underline', 'insertimage', 'link', 'source',
        ]],
        'wordCount' => true,
        'autoFloatEnabled' => false,
    ]);
CODE,
            'notes' => [
                'ueditor 的高级能力主要通过 options 透传，示例里尽量覆盖开发中最常改的配置项。',
            ],
            'source_refs' => [[
                'path' => 'app/showcase/service/components/form/ueditor/UeditorNativeOptionsSection.php',
                'label' => '原生 options 配置能力块',
                'description' => '展示 ueditor 如何透传工具栏和编辑体验相关原生配置。',
            ]],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_ueditor_section_options_', false), '原生 options 配置')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                Ueditor::make('content', '正文')
                    ->id('content_native_options')
                    ->options([
                        'toolbars' => [[
                            'bold', 'italic', 'underline', 'insertimage', 'link', 'source',
                        ]],
                        'wordCount' => true,
                        'autoFloatEnabled' => false,
                    ])
            )
            ->fetch();
    }
}
