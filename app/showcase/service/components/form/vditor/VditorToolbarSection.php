<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\vditor;

use app\common\render\Form;
use app\common\render\form\items\vditor\Vditor;

/**
 * vditor 工具栏配置能力块
 */
final class VditorToolbarSection
{
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'toolbar'),
            'title' => (string) ($section['title'] ?? '工具栏配置'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'options.toolbar', 'value' => '控制可见按钮与操作顺序'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'vditor',
        'name' => 'content',
        'label' => '正文',
        'options' => [
            'toolbar' => [
                'headings', 'bold', 'italic', 'strike', 'link',
                'list', 'ordered-list', 'quote', 'code', 'upload',
            ],
        ],
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::vditor('content', '正文')
    ->options([
        'toolbar' => [
            'headings', 'bold', 'italic', 'strike', 'link',
            'list', 'ordered-list', 'quote', 'code', 'upload',
        ],
    ]);
CODE,
            'notes' => [
                '工具栏裁剪在企业项目里很常见，能明显降低无关功能对编辑人员的干扰。',
            ],
            'source_refs' => [[
                'path' => 'app/showcase/service/components/form/vditor/VditorToolbarSection.php',
                'label' => '工具栏配置能力块',
                'description' => '展示 vditor 如何通过 options.toolbar 精简或重排工具栏。',
            ]],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_vditor_section_toolbar_', false), '工具栏配置')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                Vditor::make('content', '正文')
                    ->id('content_toolbar')
                    ->options([
                        'toolbar' => [
                            'headings', 'bold', 'italic', 'strike', 'link',
                            'list', 'ordered-list', 'quote', 'code', 'upload',
                        ],
                    ])
            )
            ->fetch();
    }
}
