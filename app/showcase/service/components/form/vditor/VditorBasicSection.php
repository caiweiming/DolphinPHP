<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\vditor;

use app\common\render\Form;
use app\common\render\form\items\vditor\Vditor;

/**
 * vditor 基础编辑器能力块
 */
final class VditorBasicSection
{
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'basic'),
            'title' => (string) ($section['title'] ?? '基础编辑器'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'name', 'value' => 'content / changelog'],
                ['name' => 'driver + dir', 'value' => 'local + docs / releases'],
            ],
            'array_code' => <<<'CODE'
[
    ['vditor', 'content', '文档内容'],
    [
        'type' => 'vditor',
        'name' => 'changelog',
        'label' => '更新日志',
        'tips' => '支持 Markdown 语法',
        'dir' => 'releases',
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::vditor('content', '文档内容')->dir('docs');

Field::vditor('changelog', '更新日志')
    ->tips('支持 Markdown 语法')
    ->dir('releases');
CODE,
            'notes' => [
                '基础 vditor 适合技术文档、知识库、博客正文等 Markdown 写作场景。',
            ],
            'source_refs' => [[
                'path' => 'app/showcase/service/components/form/vditor/VditorBasicSection.php',
                'label' => '基础编辑器能力块',
                'description' => '组装 vditor 的基础编辑器示例与代码片段。',
            ]],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_vditor_section_basic_', false), '基础编辑器')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(Vditor::make('content', '文档内容')->id('content_basic')->dir('docs'))
            ->item(Vditor::make('changelog', '更新日志', '支持 Markdown 语法')->id('changelog_basic')->dir('releases'))
            ->fetch();
    }
}
