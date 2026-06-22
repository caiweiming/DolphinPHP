<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\file;

use app\common\render\Form;
use app\common\render\form\items\file\File;

/**
 * file 基础上传能力块
 */
final class FileBasicSection
{
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'basic'),
            'title' => (string) ($section['title'] ?? '基础上传'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'name', 'value' => 'attachment / contract_file'],
                ['name' => 'dir', 'value' => 'documents / contracts'],
            ],
            'array_code' => <<<'CODE'
[
    ['file', 'attachment', '附件', '支持 PDF、Word、Excel 格式'],
    [
        'type' => 'file',
        'name' => 'contract_file',
        'label' => '合同文件',
        'tips' => '上传一份签约合同',
        'dir' => 'contracts',
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::file('attachment', '附件', '支持 PDF、Word、Excel 格式')->dir('documents');
Field::file('contract_file', '合同文件', '上传一份签约合同')->dir('contracts');
CODE,
            'notes' => [
                '基础 file 适合最常见的单附件上传场景，如合同、说明书、资料包。',
            ],
            'source_refs' => [[
                'path' => 'app/showcase/service/components/form/file/FileBasicSection.php',
                'label' => '基础上传能力块',
                'description' => '组装 file 的基础单文件上传示例与代码片段。',
            ]],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_file_section_basic_', false), '基础上传')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(File::make('attachment', '附件', '支持 PDF、Word、Excel 格式')->dir('documents'))
            ->item(File::make('contract_file', '合同文件', '上传一份签约合同')->dir('contracts'))
            ->fetch();
    }
}
