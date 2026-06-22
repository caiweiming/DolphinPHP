<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\ueditor;

use app\common\render\Form;
use app\common\render\form\items\ueditor\Ueditor;

/**
 * ueditor 基础编辑器能力块
 */
final class UeditorBasicSection
{
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'basic'),
            'title' => (string) ($section['title'] ?? '基础编辑器'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'name', 'value' => 'content / introduction'],
                ['name' => 'driver + dir', 'value' => 'local + articles / help-center'],
            ],
            'array_code' => <<<'CODE'
[
    ['ueditor', 'content', '文章内容'],
    [
        'type' => 'ueditor',
        'name' => 'introduction',
        'label' => '图文介绍',
        'tips' => '支持图文混排',
        'dir' => 'help-center',
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::ueditor('content', '文章内容')->dir('articles');

Field::ueditor('introduction', '图文介绍')
    ->tips('支持图文混排')
    ->dir('help-center');
CODE,
            'notes' => [
                '基础 ueditor 适合最常见的 HTML 富文本正文录入场景。',
            ],
            'source_refs' => [[
                'path' => 'app/showcase/service/components/form/ueditor/UeditorBasicSection.php',
                'label' => '基础编辑器能力块',
                'description' => '组装 ueditor 的基础编辑器示例与代码片段。',
            ]],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_ueditor_section_basic_', false), '基础编辑器')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(Ueditor::make('content', '文章内容')->id('content_basic')->dir('articles'))
            ->item(Ueditor::make('introduction', '图文介绍', '支持图文混排')->id('introduction_basic')->dir('help-center'))
            ->fetch();
    }
}
