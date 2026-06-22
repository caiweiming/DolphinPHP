<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\file;

use app\common\render\Form;
use app\common\render\form\items\file\File;

/**
 * file 上传驱动与目录能力块
 */
final class FileDriverDirSection
{
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'driver_dir'),
            'title' => (string) ($section['title'] ?? '上传驱动与目录'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'driver', 'value' => '支持 local / aliyun / qiniu 等上传驱动'],
                ['name' => 'dir', 'value' => '控制上传目录，适合按业务归档附件资源'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'file',
        'name' => 'resource_pack',
        'label' => '资源包',
        'driver' => 'qiniu',
        'dir' => 'packages',
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::file('resource_pack', '资源包')
    ->driver('qiniu')
    ->dir('packages');
CODE,
            'notes' => [
                '附件字段也支持显式驱动切换，适合文档和压缩包走独立对象存储策略的项目。',
            ],
            'source_refs' => [[
                'path' => 'app/showcase/service/components/form/file/FileDriverDirSection.php',
                'label' => '上传驱动与目录能力块',
                'description' => '展示 file 如何切换上传驱动并指定目录。',
            ]],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_file_section_driver_', false), '上传驱动与目录')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(File::make('resource_pack', '资源包')->driver('qiniu')->dir('packages'))
            ->fetch();
    }
}
