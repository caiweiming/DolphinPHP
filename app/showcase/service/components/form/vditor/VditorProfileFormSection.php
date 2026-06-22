<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\vditor;

use app\common\render\Form;
use app\common\render\form\items\text\Text;
use app\common\render\form\items\vditor\Vditor;

/**
 * vditor 业务表单片段能力块
 */
final class VditorProfileFormSection
{
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'profile_form'),
            'title' => (string) ($section['title'] ?? '业务表单片段'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'doc_title', 'value' => '文档标题字段'],
                ['name' => 'doc_slug', 'value' => '文档标识字段'],
                ['name' => 'doc_content', 'value' => 'Markdown 正文字段'],
            ],
            'array_code' => <<<'CODE'
[
    ['text', 'doc_title', '文档标题', '请输入标题'],
    ['text', 'doc_slug', '文档标识', '请输入唯一标识'],
    [
        'type' => 'vditor',
        'name' => 'doc_content',
        'label' => '文档正文',
        'driver' => 'local',
        'dir' => 'docs',
        'options' => [
            'mode' => 'wysiwyg',
            'height' => 460,
        ],
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::text('doc_title', '文档标题', '请输入标题');
Field::text('doc_slug', '文档标识', '请输入唯一标识');

Field::vditor('doc_content', '文档正文')
    ->driver('local')
    ->dir('docs')
    ->options([
        'mode' => 'wysiwyg',
        'height' => 460,
    ]);
CODE,
            'notes' => [
                '这是知识库、开发文档后台最常见的组合方式，开发者可以直接参考这一套写法。',
            ],
            'source_refs' => [[
                'path' => 'app/showcase/service/components/form/vditor/VditorProfileFormSection.php',
                'label' => '业务表单片段能力块',
                'description' => '展示 vditor 在真实知识库/博客类表单中的组合方式。',
            ]],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_vditor_section_profile_', false), '业务表单片段')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(Text::make('doc_title', '文档标题', '请输入标题'))
            ->item(Text::make('doc_slug', '文档标识', '请输入唯一标识'))
            ->item(
                Vditor::make('doc_content', '文档正文')
                    ->id('doc_content_profile_form')
                    ->driver('local')
                    ->dir('docs')
                    ->options([
                        'mode' => 'wysiwyg',
                        'height' => 460,
                    ])
            )
            ->fetch();
    }
}
