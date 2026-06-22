<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\image;

use app\common\render\Form;
use app\common\render\form\items\image\Image;

/**
 * image 上传驱动与目录能力块
 */
final class ImageDriverDirSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'driver_dir'),
            'title' => (string) ($section['title'] ?? '上传驱动与目录'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'driver', 'value' => '支持 local / aliyun / qiniu 等上传驱动'],
                ['name' => 'dir', 'value' => '控制上传目录，适合按业务归档图片资源'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'image',
        'name' => 'product_cover',
        'label' => '商品封面',
        'driver' => 'qiniu',
        'dir' => 'products',
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::image('product_cover', '商品封面')
    ->driver('qiniu')
    ->dir('products');
CODE,
            'notes' => [
                '如果项目启用了多种上传驱动，示例代码直接体现显式驱动切换，开发者更容易照抄。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/image/ImageDriverDirSection.php',
                    'label' => '上传驱动与目录能力块',
                    'description' => '展示 image 如何切换上传驱动并指定目录。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_image_section_driver_', false), '上传驱动与目录')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                Image::make('product_cover', '商品封面')
                    ->driver('qiniu')
                    ->dir('products')
            )
            ->fetch();
    }
}
