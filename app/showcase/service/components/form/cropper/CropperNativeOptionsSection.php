<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\cropper;

use app\common\render\Form;

/**
 * cropper 原生 options 配置能力块
 */
final class CropperNativeOptionsSection
{
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'native_options'),
            'title' => (string) ($section['title'] ?? '原生 options 配置'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'options.selection.aspectRatio', 'value' => '控制裁剪框比例，如 1:1、16:9、3:4'],
                ['name' => 'options.selection.initialAspectRatio', 'value' => '控制首次打开时的初始裁剪框比例'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'cropper',
        'name' => 'square_avatar',
        'label' => '方形头像',
        'options' => [
            'selection' => [
                'aspectRatio' => 1,
                'initialAspectRatio' => 1,
            ],
        ],
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::cropper('square_avatar', '方形头像')
    ->options([
        'selection' => [
            'aspectRatio' => 1,
            'initialAspectRatio' => 1,
        ],
    ]);
CODE,
            'notes' => [
                '当前项目使用的是 Cropper.js v2，固定比例应配置到 selection 节点；顶层 aspectRatio / viewMode 不再支持。',
                '当未显式配置 selection.x / y / width / height 时，组件会按图片实际可见区域自动生成并居中默认选区，避免裁剪框超出图片。',
            ],
            'source_refs' => [[
                'path' => 'app/showcase/service/components/form/cropper/CropperNativeOptionsSection.php',
                'label' => '原生 options 配置能力块',
                'description' => '展示 cropper 组件如何透传裁剪器原生比例与视图模式配置。',
            ]],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_cropper_section_options_', false), '原生 options 配置')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item([
                'type' => 'cropper',
                'name' => 'square_avatar',
                'label' => '方形头像',
                'options' => [
                    'selection' => [
                        'aspectRatio' => 1,
                        'initialAspectRatio' => 1,
                    ],
                ],
            ])
            ->fetch();
    }
}
