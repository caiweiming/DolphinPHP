<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\file;

use app\common\render\Form;
use app\common\render\form\items\file\File;
use app\common\render\form\items\text\Text;

/**
 * file 业务表单片段能力块
 */
final class FileProfileFormSection
{
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'profile_form'),
            'title' => (string) ($section['title'] ?? '业务表单片段'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'project_name', 'value' => '项目名称字段'],
                ['name' => 'project_contract', 'value' => '合同附件字段'],
                ['name' => 'project_attachments', 'value' => '补充资料字段'],
            ],
            'array_code' => <<<'CODE'
[
    ['text', 'project_name', '项目名称', '请输入项目名称'],
    [
        'type' => 'file',
        'name' => 'project_contract',
        'label' => '合同附件',
        'tips' => '上传一份已盖章合同',
        'dir' => 'contracts',
    ],
    [
        'type' => 'file',
        'name' => 'project_attachments',
        'label' => '补充资料',
        'tips' => '最多上传 3 个补充资料',
        'dir' => 'contracts/attachments',
        'options' => [
            'multiple' => 3,
        ],
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::text('project_name', '项目名称', '请输入项目名称');

Field::file('project_contract', '合同附件', '上传一份已盖章合同')
    ->dir('contracts');

Field::file('project_attachments', '补充资料', '最多上传 3 个补充资料')
    ->dir('contracts/attachments')
    ->multiple(3);
CODE,
            'notes' => [
                '真实业务里很常见“主文件 + 补充资料”的组合，这种示例对开发者的参考价值最高。',
            ],
            'source_refs' => [[
                'path' => 'app/showcase/service/components/form/file/FileProfileFormSection.php',
                'label' => '业务表单片段能力块',
                'description' => '展示 file 在真实附件类表单中的组合方式。',
            ]],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_file_section_profile_', false), '业务表单片段')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(Text::make('project_name', '项目名称', '请输入项目名称'))
            ->item(File::make('project_contract', '合同附件', '上传一份已盖章合同')->dir('contracts'))
            ->item(File::make('project_attachments', '补充资料', '最多上传 3 个补充资料')->dir('contracts/attachments')->multiple(3))
            ->fetch();
    }
}
