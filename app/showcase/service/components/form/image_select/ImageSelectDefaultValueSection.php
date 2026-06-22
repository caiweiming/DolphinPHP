<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\image_select;

use app\common\render\Form;
use app\common\render\form\items\image_select\ImageSelect;

/**
 * image_select 默认值与回填能力块
 */
final class ImageSelectDefaultValueSection
{
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'default_value'),
            'title' => (string) ($section['title'] ?? '默认值与回填'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'value', 'value' => '单选为字符串，多选为数组'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'image_select',
        'name' => 'theme_cover',
        'label' => '主题封面',
        'options' => [
            'light' => '/static/img/none.png?theme=light',
            'dark' => '/static/img/none.png?theme=dark',
            'paper' => '/static/img/none.png?theme=paper',
        ],
        'value' => 'dark',
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::imageSelect('theme_cover', '主题封面')
    ->options([
        'light' => '/static/img/none.png?theme=light',
        'dark' => '/static/img/none.png?theme=dark',
        'paper' => '/static/img/none.png?theme=paper',
    ])
    ->value('dark');
CODE,
            'notes' => [
                '编辑态回显时最重要的是 value 和 options 的值保持一致，否则选中态无法正确展示。',
                '如果你把资源 URL 作为数组 key，就提交 URL；如果 key 是业务编码，就提交业务编码。',
            ],
            'source_refs' => [[
                'path' => 'app/showcase/service/components/form/image_select/ImageSelectDefaultValueSection.php',
                'label' => '默认值与回填能力块',
                'description' => '展示 image_select 在编辑态图片回显场景下的写法。',
            ]],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_image_select_section_value_', false), '默认值与回填')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                ImageSelect::make('theme_cover', '主题封面')
                    ->options([
                        'light' => '/static/img/none.png?theme=light',
                        'dark' => '/static/img/none.png?theme=dark',
                        'paper' => '/static/img/none.png?theme=paper',
                    ])
                    ->value('dark')
            )
            ->fetch();
    }
}
