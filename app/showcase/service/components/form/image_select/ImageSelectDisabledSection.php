<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\image_select;

use app\common\render\Form;

/**
 * image_select 禁用状态能力块
 */
final class ImageSelectDisabledSection
{
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'disabled'),
            'title' => (string) ($section['title'] ?? '禁用状态'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'disabled(true)', 'value' => '禁用全部图片项'],
                ['name' => 'disabled([...])', 'value' => '仅禁用指定图片项'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'image_select',
        'name' => 'theme_choice',
        'label' => '主题方案',
        'options' => [
            'plan_a' => '/static/img/none.png?plan=1',
            'plan_b' => '/static/img/none.png?plan=2',
            'plan_c' => '/static/img/none.png?plan=3',
        ],
        'disabled' => ['plan_b'],
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::imageSelect('theme_choice', '主题方案')
    ->options([
        'plan_a' => '/static/img/none.png?plan=1',
        'plan_b' => '/static/img/none.png?plan=2',
        'plan_c' => '/static/img/none.png?plan=3',
    ])
    ->disabled(['plan_b']);
CODE,
            'notes' => [
                '禁用指定图片项很适合“某些模板已下线、某些头像仅会员可用”这类资源状态场景。',
                'disabled(true) 会禁用全部 key；传数组时应传 options 的 key 列表，而不是展示图片地址。',
            ],
            'source_refs' => [[
                'path' => 'app/showcase/service/components/form/image_select/ImageSelectDisabledSection.php',
                'label' => '禁用状态能力块',
                'description' => '展示 image_select 如何禁用全部或部分图片选项。',
            ]],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_image_select_section_disabled_', false), '禁用状态')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item([
                'type' => 'image_select',
                'name' => 'theme_choice',
                'label' => '主题方案',
                'disabled' => ['plan_b'],
                'options' => [
                    'plan_a' => '/static/img/none.png?plan=1',
                    'plan_b' => '/static/img/none.png?plan=2',
                    'plan_c' => '/static/img/none.png?plan=3',
                ],
            ])
            ->fetch();
    }
}
