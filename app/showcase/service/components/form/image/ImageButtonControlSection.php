<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\image;

use app\common\render\Form;

/**
 * image 按钮控制能力块
 */
final class ImageButtonControlSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'button_control'),
            'title' => (string) ($section['title'] ?? '按钮控制'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'upload / browser', 'value' => '控制上传与浏览入口的显示'],
                ['name' => 'clear / delete', 'value' => '控制清空和删除能力，适合精简交互'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'image',
        'name' => 'manual_cover',
        'label' => '手动浏览封面',
        'upload' => false,
        'browser' => true,
        'clear' => true,
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::image('manual_cover', '手动浏览封面')
    ->upload(false)
    ->browser()
    ->clear();
CODE,
            'notes' => [
                '按钮显隐非常适合做“仅允许从素材库选择”或“仅允许上传、不允许清空”的差异化交互。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/image/ImageButtonControlSection.php',
                    'label' => '按钮控制能力块',
                    'description' => '展示 image 如何按业务需要裁剪上传组件按钮。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_image_section_buttons_', false), '按钮控制')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item([
                'type' => 'image',
                'name' => 'manual_cover',
                'label' => '手动浏览封面',
                'upload' => false,
                'browser' => true,
                'clear' => true,
            ])
            ->fetch();
    }
}
