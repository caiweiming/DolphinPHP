<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\vditor;

use app\common\render\Form;
use app\common\render\form\items\vditor\Vditor;

/**
 * vditor 上传驱动与目录能力块
 */
final class VditorDriverDirSection
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
                ['name' => 'dir', 'value' => 'docs / wiki / articles 等业务目录'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'vditor',
        'name' => 'wiki_content',
        'label' => '知识库内容',
        'driver' => 'local',
        'dir' => 'wiki',
    ],
    [
        'type' => 'vditor',
        'name' => 'release_note',
        'label' => '发版说明',
        'driver' => 'aliyun',
        'dir' => 'releases',
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::vditor('wiki_content', '知识库内容')
    ->driver('local')
    ->dir('wiki');

Field::vditor('release_note', '发版说明')
    ->driver('aliyun')
    ->dir('releases');
CODE,
            'notes' => [
                '当 Markdown 图片需要进入特定资源目录时，显式指定 driver 和 dir 更便于后期治理。',
            ],
            'source_refs' => [[
                'path' => 'app/showcase/service/components/form/vditor/VditorDriverDirSection.php',
                'label' => '上传驱动与目录能力块',
                'description' => '展示 vditor 如何切换上传驱动和业务目录。',
            ]],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_vditor_section_driver_', false), '上传驱动与目录')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(Vditor::make('wiki_content', '知识库内容')->driver('local')->dir('wiki'))
            ->item(Vditor::make('release_note', '发版说明')->driver('local')->dir('releases'))
            ->fetch();
    }
}
