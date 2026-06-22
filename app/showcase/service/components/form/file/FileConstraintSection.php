<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\file;

use app\common\render\Form;

/**
 * file 上传约束能力块
 */
final class FileConstraintSection
{
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'constraint'),
            'title' => (string) ($section['title'] ?? '上传约束'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'options.allowedTypes', 'value' => '限制允许上传的文件格式'],
                ['name' => 'options.maxFileSize', 'value' => '限制单个文件大小，支持带单位字符串'],
                ['name' => 'options.extraData', 'value' => '给上传接口追加业务参数'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'file',
        'name' => 'strict_file',
        'label' => '受限附件',
        'tips' => '仅允许 PDF 和 ZIP，单个不超过 10MB',
        'options' => [
            'allowedTypes' => ['application/pdf', '.zip'],
            'maxFileSize' => '10MB',
            'extraData' => ['scene' => 'contract'],
        ],
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::file('strict_file', '受限附件', '仅允许 PDF 和 ZIP，单个不超过 10MB')
    ->options([
        'allowedTypes' => ['application/pdf', '.zip'],
        'maxFileSize' => '10MB',
        'extraData' => ['scene' => 'contract'],
    ]);
CODE,
            'notes' => [
                '附件约束示例比口头说明更有价值，开发者可以直接照抄到合同、资料提交等表单里。',
            ],
            'source_refs' => [[
                'path' => 'app/showcase/service/components/form/file/FileConstraintSection.php',
                'label' => '上传约束能力块',
                'description' => '展示 file 组件如何限制附件类型、大小和上传附加参数。',
            ]],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_file_section_constraint_', false), '上传约束')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item([
                'type' => 'file',
                'name' => 'strict_file',
                'label' => '受限附件',
                'tips' => '仅允许 PDF 和 ZIP，单个不超过 10MB',
                'options' => [
                    'allowedTypes' => ['application/pdf', '.zip'],
                    'maxFileSize' => '10MB',
                    'extraData' => ['scene' => 'contract'],
                ],
            ])
            ->fetch();
    }
}
