<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\file;

use app\common\render\Form;
use app\common\render\form\items\file\File;

/**
 * file 多文件与数量限制能力块
 */
final class FileMultipleLimitSection
{
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'multiple_limit'),
            'title' => (string) ($section['title'] ?? '多文件与数量限制'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'multiple(true)', 'value' => '开启多文件模式'],
                ['name' => 'multiple(5)', 'value' => '开启多文件并限制最多 5 个附件'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'file',
        'name' => 'attachments',
        'label' => '资料附件',
        'tips' => '最多上传 5 个附件',
        'options' => [
            'multiple' => 5,
        ],
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::file('attachments', '资料附件', '最多上传 5 个附件')
    ->multiple(5);
CODE,
            'notes' => [
                '多文件模式提交时字段名会自动补成数组形式，后端需要做好数组落库转换。',
            ],
            'source_refs' => [[
                'path' => 'app/showcase/service/components/form/file/FileMultipleLimitSection.php',
                'label' => '多文件与数量限制能力块',
                'description' => '展示 file 如何开启多文件上传并限制最大数量。',
            ]],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_file_section_multiple_', false), '多文件与数量限制')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(File::make('attachments', '资料附件', '最多上传 5 个附件')->multiple(5))
            ->fetch();
    }
}
