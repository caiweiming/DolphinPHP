<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\file;

use app\common\render\Form;
use app\common\render\form\items\file\File;

/**
 * file 默认值与回填能力块
 */
final class FileDefaultValueSection
{
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'default_value'),
            'title' => (string) ($section['title'] ?? '默认值与回填'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'value', 'value' => '支持单文件 id 或多文件 id 数组回显'],
                ['name' => 'download', 'value' => '回显文件通常会同时显示下载入口'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'file',
        'name' => 'document',
        'label' => '已上传附件',
        'value' => 0,
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::file('document', '已上传附件')
    ->value(0);
CODE,
            'notes' => [
                '示例里用 `0` 触发内置占位文件信息，避免对测试库中的真实附件记录产生依赖。',
            ],
            'source_refs' => [[
                'path' => 'app/showcase/service/components/form/file/FileDefaultValueSection.php',
                'label' => '默认值与回填能力块',
                'description' => '展示 file 在默认值回显场景下的结构和占位表现。',
            ]],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_file_section_value_', false), '默认值与回填')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(File::make('document', '已上传附件')->value(0))
            ->fetch();
    }
}
