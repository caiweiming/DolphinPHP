<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\image_select;

use app\common\render\Form;
use app\common\render\form\items\text\Text;

/**
 * image_select 业务表单片段能力块
 */
final class ImageSelectProfileFormSection
{
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'profile_form'),
            'title' => (string) ($section['title'] ?? '业务表单片段'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'theme_name', 'value' => '主题名称字段'],
                ['name' => 'theme_cover', 'value' => '主题封面样式选择字段'],
                ['name' => 'theme_badges', 'value' => '主题徽章多选字段'],
            ],
            'array_code' => <<<'CODE'
[
    ['text', 'theme_name', '主题名称', '请输入主题名称'],
    [
        'type' => 'image_select',
        'name' => 'theme_cover',
        'label' => '封面样式',
        'options' => [
            'cover_a' => '/static/img/none.png?cover=1',
            'cover_b' => '/static/img/none.png?cover=2',
            'cover_c' => '/static/img/none.png?cover=3',
        ],
    ],
    [
        'type' => 'image_select',
        'name' => 'theme_badges',
        'label' => '徽章组合',
        'multiple' => true,
        'options' => [
            'badge_a' => '/static/img/none.png?badge=1',
            'badge_b' => '/static/img/none.png?badge=2',
            'badge_c' => '/static/img/none.png?badge=3',
        ],
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::text('theme_name', '主题名称', '请输入主题名称');

Field::imageSelect('theme_cover', '封面样式')
    ->options([
        'cover_a' => '/static/img/none.png?cover=1',
        'cover_b' => '/static/img/none.png?cover=2',
        'cover_c' => '/static/img/none.png?cover=3',
    ]);

Field::imageSelect('theme_badges', '徽章组合')
    ->multiple()
    ->options([
        'badge_a' => '/static/img/none.png?badge=1',
        'badge_b' => '/static/img/none.png?badge=2',
        'badge_c' => '/static/img/none.png?badge=3',
    ]);
CODE,
            'notes' => [
                '真实业务里 image_select 很适合做主题市场、皮肤方案、头像库等受控资源选择场景。',
            ],
            'source_refs' => [[
                'path' => 'app/showcase/service/components/form/image_select/ImageSelectProfileFormSection.php',
                'label' => '业务表单片段能力块',
                'description' => '展示 image_select 在真实主题/模板类表单中的组合方式。',
            ]],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_image_select_section_profile_', false), '业务表单片段')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(Text::make('theme_name', '主题名称', '请输入主题名称'))
            ->item([
                'type' => 'image_select',
                'name' => 'theme_cover',
                'label' => '封面样式',
                'options' => [
                    'cover_a' => '/static/img/none.png?cover=1',
                    'cover_b' => '/static/img/none.png?cover=2',
                    'cover_c' => '/static/img/none.png?cover=3',
                ],
            ])
            ->item([
                'type' => 'image_select',
                'name' => 'theme_badges',
                'label' => '徽章组合',
                'multiple' => true,
                'options' => [
                    'badge_a' => '/static/img/none.png?badge=1',
                    'badge_b' => '/static/img/none.png?badge=2',
                    'badge_c' => '/static/img/none.png?badge=3',
                ],
            ])
            ->fetch();
    }
}
