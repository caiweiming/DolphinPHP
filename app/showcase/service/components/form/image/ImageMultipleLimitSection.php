<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\image;

use app\common\render\Form;
use app\common\render\form\items\image\Image;

/**
 * image 多图与数量限制能力块
 */
final class ImageMultipleLimitSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'multiple_limit'),
            'title' => (string) ($section['title'] ?? '多图与数量限制'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'multiple(true)', 'value' => '开启不限数量的多图模式'],
                ['name' => 'multiple(6)', 'value' => '开启多图并限制最多 6 张'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'image',
        'name' => 'gallery',
        'label' => '商品图集',
        'tips' => '最多上传 6 张',
        'options' => [
            'multiple' => 6,
        ],
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::image('gallery', '商品图集', '最多上传 6 张')
    ->multiple(6);
CODE,
            'notes' => [
                '多图模式提交时字段名会自动补成数组形式，后端需要做好数组落库转换。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/image/ImageMultipleLimitSection.php',
                    'label' => '多图与数量限制能力块',
                    'description' => '展示 image 如何开启多图上传并限制最大数量。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_image_section_multiple_', false), '多图与数量限制')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(Image::make('gallery', '商品图集', '最多上传 6 张')->multiple(6))
            ->fetch();
    }
}
