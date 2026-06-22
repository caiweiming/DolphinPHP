<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\image_select;

use app\common\render\Form;
use app\common\render\form\items\image_select\ImageSelect;

/**
 * image_select 基础选择能力块
 */
final class ImageSelectBasicSection
{
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'basic'),
            'title' => (string) ($section['title'] ?? '基础选择'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'options', 'value' => '支持字符串图片 URL 列表快速配置'],
                ['name' => 'value', 'value' => '支持默认选中的图片值回显'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'image_select',
        'name' => 'avatar',
        'label' => '头像',
        'options' => [
            'base' => '/static/img/none.png',
            'default' => '/static/img/none.png?style=2',
            'rounded' => '/static/img/none.png?style=3',
        ],
        'value' => 'default',
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::imageSelect('avatar', '头像')
    ->options([
        'base' => '/static/img/none.png',
        'default' => '/static/img/none.png?style=2',
        'rounded' => '/static/img/none.png?style=3',
    ])
    ->value('default');
CODE,
            'notes' => [
                '基础 image_select 最适合“预置头像库”这类无需上传、直接挑选图片的场景。',
                '如果 options 使用关联数组，最终提交值是 options 的 key，而不是图片 URL 本身。',
            ],
            'source_refs' => [[
                'path' => 'app/showcase/service/components/form/image_select/ImageSelectBasicSection.php',
                'label' => '基础选择能力块',
                'description' => '组装 image_select 的基础图片选择示例与代码片段。',
            ]],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_image_select_section_basic_', false), '基础选择')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                ImageSelect::make('avatar', '头像')
                    ->options([
                        'base' => '/static/img/none.png',
                        'default' => '/static/img/none.png?style=2',
                        'rounded' => '/static/img/none.png?style=3',
                    ])
                    ->value('default')
            )
            ->fetch();
    }
}
