<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\cropper;

use app\common\render\Form;
use app\common\render\form\items\cropper\Cropper;

/**
 * cropper 基础裁剪能力块
 */
final class CropperBasicSection
{
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'basic'),
            'title' => (string) ($section['title'] ?? '基础裁剪'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'name', 'value' => 'avatar / poster'],
                ['name' => 'driver + dir', 'value' => 'avatars / posters'],
            ],
            'array_code' => <<<'CODE'
[
    ['cropper', 'avatar', '头像', '建议上传 200x200 图片'],
    [
        'type' => 'cropper',
        'name' => 'poster',
        'label' => '分享海报',
        'tips' => '建议上传竖版海报图',
        'dir' => 'posters',
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::cropper('avatar', '头像', '建议上传 200x200 图片')
    ->dir('avatars');

Field::cropper('poster', '分享海报', '建议上传竖版海报图')
    ->dir('posters');
CODE,
            'notes' => [
                '基础 cropper 适合头像、海报等有明确裁剪动作的单图录入场景。',
            ],
            'source_refs' => [[
                'path' => 'app/showcase/service/components/form/cropper/CropperBasicSection.php',
                'label' => '基础裁剪能力块',
                'description' => '组装 cropper 的基础裁剪示例与代码片段。',
            ]],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_cropper_section_basic_', false), '基础裁剪')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(Cropper::make('avatar', '头像', '建议上传 200x200 图片')->dir('avatars'))
            ->item(Cropper::make('poster', '分享海报', '建议上传竖版海报图')->dir('posters'))
            ->fetch();
    }
}
