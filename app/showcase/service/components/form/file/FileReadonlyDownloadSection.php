<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\file;

use app\common\render\Form;
use app\common\render\form\items\file\File;

/**
 * file 只读与下载能力块
 */
final class FileReadonlyDownloadSection
{
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'readonly_download'),
            'title' => (string) ($section['title'] ?? '只读与下载'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'readonly', 'value' => '切换为只读附件模式，关闭上传/删除交互'],
                ['name' => 'download', 'value' => '控制是否保留下载入口'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'file',
        'name' => 'readonly_doc',
        'label' => '只读附件',
        'readonly' => true,
        'download' => true,
        'value' => 0,
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::file('readonly_doc', '只读附件')
    ->value(0)
    ->readonly()
    ->download();
CODE,
            'notes' => [
                '详情页里常见“不可修改但可下载”的附件字段，file 组件天然适合这种场景。',
            ],
            'source_refs' => [[
                'path' => 'app/showcase/service/components/form/file/FileReadonlyDownloadSection.php',
                'label' => '只读与下载能力块',
                'description' => '展示 file 在只读场景和下载策略配置下的组合方式。',
            ]],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_file_section_readonly_', false), '只读与下载')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                File::make('readonly_doc', '只读附件')
                    ->value(0)
                    ->readonly()
                    ->download()
            )
            ->fetch();
    }
}
