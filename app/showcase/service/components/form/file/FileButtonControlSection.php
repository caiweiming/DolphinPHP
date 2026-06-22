<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\file;

use app\common\render\Form;

/**
 * file 按钮控制能力块
 */
final class FileButtonControlSection
{
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'button_control'),
            'title' => (string) ($section['title'] ?? '按钮控制'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'upload / browser', 'value' => '控制上传与浏览入口的显示'],
                ['name' => 'download / clear / delete', 'value' => '控制下载、清空和删除能力'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'file',
        'name' => 'manual_attachment',
        'label' => '手动浏览附件',
        'upload' => false,
        'browser' => true,
        'download' => false,
        'clear' => true,
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::file('manual_attachment', '手动浏览附件')
    ->upload(false)
    ->browser()
    ->download(false)
    ->clear();
CODE,
            'notes' => [
                '按钮显隐很适合素材库挑选、审批态预览或仅允许追加附件的差异化交互。',
            ],
            'source_refs' => [[
                'path' => 'app/showcase/service/components/form/file/FileButtonControlSection.php',
                'label' => '按钮控制能力块',
                'description' => '展示 file 如何按业务需要裁剪上传组件按钮。',
            ]],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_file_section_buttons_', false), '按钮控制')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item([
                'type' => 'file',
                'name' => 'manual_attachment',
                'label' => '手动浏览附件',
                'upload' => false,
                'browser' => true,
                'download' => false,
                'clear' => true,
            ])
            ->fetch();
    }
}
