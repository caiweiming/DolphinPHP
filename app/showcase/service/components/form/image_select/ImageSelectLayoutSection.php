<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\image_select;

use app\common\render\Form;

/**
 * image_select 样式与布局能力块
 */
final class ImageSelectLayoutSection
{
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'layout'),
            'title' => (string) ($section['title'] ?? '样式与布局'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'class', 'value' => '控制每个图片卡片的列宽布局'],
                ['name' => 'label_class', 'value' => '控制图片卡片的自定义视觉类名'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'image_select',
        'name' => 'cover_style',
        'label' => '封面风格',
        'class' => 'col-6 col-md-4',
        'options' => [
            'layout_a' => '/static/img/none.png?layout=1',
            'layout_b' => '/static/img/none.png?layout=2',
            'layout_c' => '/static/img/none.png?layout=3',
        ],
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::imageSelect('cover_style', '封面风格')
    ->class('col-6 col-md-4')
    ->options([
        'layout_a' => '/static/img/none.png?layout=1',
        'layout_b' => '/static/img/none.png?layout=2',
        'layout_c' => '/static/img/none.png?layout=3',
    ]);
CODE,
            'notes' => [
                '布局控制对图片选择器很重要，否则一旦图片项多起来，页面可读性会迅速下降。',
            ],
            'source_refs' => [[
                'path' => 'app/showcase/service/components/form/image_select/ImageSelectLayoutSection.php',
                'label' => '样式与布局能力块',
                'description' => '展示 image_select 如何通过 class 调整图片卡片布局。',
            ]],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_image_select_section_layout_', false), '样式与布局')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item([
                'type' => 'image_select',
                'name' => 'cover_style',
                'label' => '封面风格',
                'class' => 'col-6 col-md-4',
                'options' => [
                    'layout_a' => '/static/img/none.png?layout=1',
                    'layout_b' => '/static/img/none.png?layout=2',
                    'layout_c' => '/static/img/none.png?layout=3',
                ],
            ])
            ->fetch();
    }
}
