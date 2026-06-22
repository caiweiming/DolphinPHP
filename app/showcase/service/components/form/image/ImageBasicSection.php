<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\image;

use app\common\render\Form;
use app\common\render\form\items\image\Image;

/**
 * image 基础上传能力块
 */
final class ImageBasicSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'basic'),
            'title' => (string) ($section['title'] ?? '基础上传'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'name', 'value' => 'cover / banner'],
                ['name' => 'dir', 'value' => 'covers / banners'],
            ],
            'array_code' => <<<'CODE'
[
    ['image', 'cover', '封面图', '上传一张封面图'],
    [
        'type' => 'image',
        'name' => 'banner',
        'label' => '横幅图',
        'tips' => '建议尺寸 1200x400',
        'dir' => 'banners',
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::image('cover', '封面图', '上传一张封面图')->dir('covers');
Field::image('banner', '横幅图', '建议尺寸 1200x400')->dir('banners');
CODE,
            'notes' => [
                '基础 image 已内置预览、浏览和上传能力，适合作为头像、封面图等单图入口。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/image/ImageBasicSection.php',
                    'label' => '基础上传能力块',
                    'description' => '组装 image 的基础单图上传示例与代码片段。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_image_section_basic_', false), '基础上传')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(Image::make('cover', '封面图', '上传一张封面图')->dir('covers'))
            ->item(Image::make('banner', '横幅图', '建议尺寸 1200x400')->dir('banners'))
            ->fetch();
    }
}
