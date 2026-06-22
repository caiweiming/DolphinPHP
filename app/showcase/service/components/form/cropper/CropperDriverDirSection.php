<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\cropper;

use app\common\render\Form;
use app\common\render\form\items\cropper\Cropper;

/**
 * cropper 上传驱动与目录能力块
 */
final class CropperDriverDirSection
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
                ['name' => 'dir', 'value' => '控制裁剪结果上传目录，适合按业务归档'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'cropper',
        'name' => 'poster_cover',
        'label' => '活动海报',
        'driver' => 'qiniu',
        'dir' => 'activity/posters',
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::cropper('poster_cover', '活动海报')
    ->driver('qiniu')
    ->dir('activity/posters');
CODE,
            'notes' => [
                '对海报、头像这类高频资源做分目录存储，后续治理和迁移都会更轻松。',
            ],
            'source_refs' => [[
                'path' => 'app/showcase/service/components/form/cropper/CropperDriverDirSection.php',
                'label' => '上传驱动与目录能力块',
                'description' => '展示 cropper 如何切换上传驱动并指定裁剪结果目录。',
            ]],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_cropper_section_driver_', false), '上传驱动与目录')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(Cropper::make('poster_cover', '活动海报')->driver('local')->dir('activity/posters'))
            ->fetch();
    }
}
