<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\image_select;

use app\common\render\Form;
use app\common\render\form\items\image_select\ImageSelect;

/**
 * image_select 多选模式能力块
 */
final class ImageSelectMultipleSection
{
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'multiple'),
            'title' => (string) ($section['title'] ?? '多选模式'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'multiple', 'value' => '切换为 checkbox 形式的多选图片集'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'image_select',
        'name' => 'poster_styles',
        'label' => '海报风格',
        'multiple' => true,
        'options' => [
            'poster_a' => '/static/img/none.png?a=1',
            'poster_b' => '/static/img/none.png?a=2',
            'poster_c' => '/static/img/none.png?a=3',
        ],
        'value' => ['poster_a', 'poster_c'],
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::imageSelect('poster_styles', '海报风格')
    ->multiple()
    ->options([
        'poster_a' => '/static/img/none.png?a=1',
        'poster_b' => '/static/img/none.png?a=2',
        'poster_c' => '/static/img/none.png?a=3',
    ])
    ->value(['poster_a', 'poster_c']);
CODE,
            'notes' => [
                '多选模式会把 input 切换为 checkbox，适合主题组合、模板组合等场景。',
                '多选时提交的也是 key 数组，所以 value 需要和 options 的 key 保持一致。',
            ],
            'source_refs' => [[
                'path' => 'app/showcase/service/components/form/image_select/ImageSelectMultipleSection.php',
                'label' => '多选模式能力块',
                'description' => '展示 image_select 如何切换为图片多选模式。',
            ]],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_image_select_section_multiple_', false), '多选模式')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                ImageSelect::make('poster_styles', '海报风格')
                    ->multiple()
                    ->options([
                        'poster_a' => '/static/img/none.png?a=1',
                        'poster_b' => '/static/img/none.png?a=2',
                        'poster_c' => '/static/img/none.png?a=3',
                    ])
                    ->value(['poster_a', 'poster_c'])
            )
            ->fetch();
    }
}
