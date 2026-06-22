<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\cropper;

use app\common\render\Form;
use app\common\render\form\items\cropper\Cropper;

/**
 * cropper 裁剪动作能力块
 */
final class CropperActionsSection
{
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'actions'),
            'title' => (string) ($section['title'] ?? '裁剪动作'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'actions', 'value' => '控制 zoom / rotate / scale 等裁剪操作按钮'],
                ['name' => '可选值', 'value' => '支持 zoom-in / zoom-out / rotate-left / rotate-right / scale-x / scale-y'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'cropper',
        'name' => 'banner_image',
        'label' => '横幅图',
        'actions' => ['zoom-in', 'zoom-out', 'rotate-left', 'rotate-right'],
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::cropper('banner_image', '横幅图')
    ->actions(['zoom-in', 'zoom-out', 'rotate-left', 'rotate-right', 'scale-x']);
CODE,
            'notes' => [
                '如果业务不允许翻转图片，只保留缩放和旋转动作会更克制，也更贴近设计规范。',
                '当运营经常需要镜像图片时，可以把 `scale-x` / `scale-y` 明确加进示例，而不是只停留在文档说明里。',
            ],
            'source_refs' => [[
                'path' => 'app/showcase/service/components/form/cropper/CropperActionsSection.php',
                'label' => '裁剪动作能力块',
                'description' => '展示 cropper 如何按业务需要裁剪缩放、旋转和翻转操作。',
            ]],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_cropper_section_actions_', false), '裁剪动作')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(Cropper::make('banner_image', '横幅图')->actions(['zoom-in', 'zoom-out', 'rotate-left', 'rotate-right', 'scale-x']))
            ->fetch();
    }
}
