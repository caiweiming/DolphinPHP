<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\image_select;

use app\common\render\Form;

/**
 * image_select 选项结构能力块
 */
final class ImageSelectOptionSchemaSection
{
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'option_schema'),
            'title' => (string) ($section['title'] ?? '选项结构'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => '字符串选项', 'value' => '最简结构，直接传图片 URL'],
                ['name' => '对象选项', 'value' => '可补充 src / alt / class / label_class 等更多元信息'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'image_select',
        'name' => 'template_cover',
        'label' => '模板封面',
        'options' => [
            'light' => [
                'src' => '/static/img/none.png?tpl=light',
                'alt' => '浅色模板',
                'class' => 'col-6',
                'label_class' => 'border-primary',
            ],
            'dark' => [
                'src' => '/static/img/none.png?tpl=dark',
                'alt' => '深色模板',
                'class' => 'col-6',
            ],
        ],
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::imageSelect('template_cover', '模板封面')
    ->options([
        'light' => [
            'src' => '/static/img/none.png?tpl=light',
            'alt' => '浅色模板',
            'class' => 'col-6',
            'label_class' => 'border-primary',
        ],
        'dark' => [
            'src' => '/static/img/none.png?tpl=dark',
            'alt' => '深色模板',
            'class' => 'col-6',
        ],
    ]);
CODE,
            'notes' => [
                '对象结构比纯字符串更适合做“模板市场”“皮肤中心”这类带说明语义的图片选择器。',
            ],
            'source_refs' => [[
                'path' => 'app/showcase/service/components/form/image_select/ImageSelectOptionSchemaSection.php',
                'label' => '选项结构能力块',
                'description' => '展示 image_select 支持的字符串选项与对象选项两种配置结构。',
            ]],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_image_select_section_schema_', false), '选项结构')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item([
                'type' => 'image_select',
                'name' => 'template_cover',
                'label' => '模板封面',
                'options' => [
                    'light' => [
                        'src' => '/static/img/none.png?tpl=light',
                        'alt' => '浅色模板',
                        'class' => 'col-6',
                        'label_class' => 'border-primary',
                    ],
                    'dark' => [
                        'src' => '/static/img/none.png?tpl=dark',
                        'alt' => '深色模板',
                        'class' => 'col-6',
                    ],
                ],
            ])
            ->fetch();
    }
}
