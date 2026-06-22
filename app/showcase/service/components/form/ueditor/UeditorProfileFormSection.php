<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\ueditor;

use app\common\render\Form;
use app\common\render\form\items\text\Text;
use app\common\render\form\items\ueditor\Ueditor;

/**
 * ueditor 业务表单片段能力块
 */
final class UeditorProfileFormSection
{
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'profile_form'),
            'title' => (string) ($section['title'] ?? '业务表单片段'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'title', 'value' => '文章标题字段'],
                ['name' => 'summary', 'value' => '文章摘要字段'],
                ['name' => 'content', 'value' => '文章正文富文本字段'],
            ],
            'array_code' => <<<'CODE'
[
    ['text', 'title', '文章标题', '请输入文章标题'],
    ['text', 'summary', '文章摘要', '请输入摘要'],
    [
        'type' => 'ueditor',
        'name' => 'content',
        'label' => '文章正文',
        'driver' => 'local',
        'dir' => 'articles',
        'options' => [
            'initialFrameHeight' => 420,
        ],
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::text('title', '文章标题', '请输入文章标题');
Field::text('summary', '文章摘要', '请输入摘要');

Field::ueditor('content', '文章正文')
    ->driver('local')
    ->dir('articles')
    ->options([
        'initialFrameHeight' => 420,
    ]);
CODE,
            'notes' => [
                '这是最贴近真实 CMS 后台的组合方式，开发者基本可以直接照抄到文章管理模块里。',
            ],
            'source_refs' => [[
                'path' => 'app/showcase/service/components/form/ueditor/UeditorProfileFormSection.php',
                'label' => '业务表单片段能力块',
                'description' => '展示 ueditor 在真实文章/帮助中心类表单中的组合方式。',
            ]],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_ueditor_section_profile_', false), '业务表单片段')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(Text::make('title', '文章标题', '请输入文章标题'))
            ->item(Text::make('summary', '文章摘要', '请输入摘要'))
            ->item(
                Ueditor::make('content', '文章正文')
                    ->id('content_profile_form')
                    ->driver('local')
                    ->dir('articles')
                    ->options([
                        'initialFrameHeight' => 420,
                    ])
            )
            ->fetch();
    }
}
