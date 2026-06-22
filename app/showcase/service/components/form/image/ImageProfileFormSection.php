<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\image;

use app\common\render\Form;
use app\common\render\form\items\image\Image;
use app\common\render\form\items\text\Text;

/**
 * image 业务表单片段能力块
 */
final class ImageProfileFormSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'profile_form'),
            'title' => (string) ($section['title'] ?? '业务表单片段'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'profile_cover', 'value' => '封面图字段'],
                ['name' => 'profile_gallery', 'value' => '商品图集字段'],
            ],
            'array_code' => <<<'CODE'
[
    ['text', 'product_name', '商品名称', '请输入商品名称'],
    [
        'type' => 'image',
        'name' => 'profile_cover',
        'label' => '封面图',
        'tips' => '商品主图，建议尺寸 800x800',
        'dir' => 'products/covers',
    ],
    [
        'type' => 'image',
        'name' => 'profile_gallery',
        'label' => '商品图集',
        'tips' => '最多上传 6 张商品详情图',
        'dir' => 'products/gallery',
        'options' => [
            'multiple' => 6,
        ],
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::text('product_name', '商品名称', '请输入商品名称');

Field::image('profile_cover', '封面图', '商品主图，建议尺寸 800x800')
    ->dir('products/covers');

Field::image('profile_gallery', '商品图集', '最多上传 6 张商品详情图')
    ->dir('products/gallery')
    ->multiple(6);
CODE,
            'notes' => [
                '真实业务里最常见的是“单图封面 + 多图图集”的组合，这种示例最利于开发者直接照抄。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/image/ImageProfileFormSection.php',
                    'label' => '业务表单片段能力块',
                    'description' => '展示 image 在真实商品类表单中的组合方式。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_image_section_profile_', false), '业务表单片段')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(Text::make('product_name', '商品名称', '请输入商品名称'))
            ->item(
                Image::make('profile_cover', '封面图', '商品主图，建议尺寸 800x800')
                    ->dir('products/covers')
            )
            ->item(
                Image::make('profile_gallery', '商品图集', '最多上传 6 张商品详情图')
                    ->dir('products/gallery')
                    ->multiple(6)
            )
            ->fetch();
    }
}
