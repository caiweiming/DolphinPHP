<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\ueditor;

use app\common\render\Form;
use app\common\render\form\items\ueditor\Ueditor;

/**
 * ueditor 上传驱动与目录能力块
 */
final class UeditorDriverDirSection
{
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'driver_dir'),
            'title' => (string) ($section['title'] ?? '上传驱动与目录'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'driver', 'value' => 'local / aliyun / qiniu 等上传驱动'],
                ['name' => 'dir', 'value' => 'articles / manuals / help-center 等业务目录'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'ueditor',
        'name' => 'article_content',
        'label' => '文章正文',
        'driver' => 'local',
        'dir' => 'articles',
    ],
    [
        'type' => 'ueditor',
        'name' => 'manual_content',
        'label' => '产品手册',
        'driver' => 'qiniu',
        'dir' => 'manuals',
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::ueditor('article_content', '文章正文')
    ->driver('local')
    ->dir('articles');

Field::ueditor('manual_content', '产品手册')
    ->driver('qiniu')
    ->dir('manuals');
CODE,
            'notes' => [
                '当正文图片、附件需要按业务模块隔离时，显式指定 driver 和 dir 会更稳妥。',
            ],
            'source_refs' => [[
                'path' => 'app/showcase/service/components/form/ueditor/UeditorDriverDirSection.php',
                'label' => '上传驱动与目录能力块',
                'description' => '展示 ueditor 如何切换上传驱动和业务目录。',
            ]],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_ueditor_section_driver_', false), '上传驱动与目录')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(Ueditor::make('article_content', '文章正文')->driver('local')->dir('articles'))
            ->item(Ueditor::make('manual_content', '产品手册')->driver('local')->dir('manuals'))
            ->fetch();
    }
}
