<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\extensions;

use app\common\render\Form;
use app\common\render\form\Field;
use form\fieldset\Fieldset;

/**
 * fieldset 能力块构建器
 */
final class FieldsetSectionBuilder
{
    public function build(array $component, array $section): array
    {
        $key = (string) ($section['key'] ?? 'basic');

        return match ($key) {
            'basic' => $this->basic($section),
            'nested_items' => $this->nestedItems($section),
            'when' => $this->whenSection($section),
            'default_value' => $this->defaultValue($section),
            'help_tips' => $this->helpTips($section),
            'profile_form' => $this->profileForm($section),
            default => $this->basic($section),
        };
    }

    private function basic(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => 'options', 'value' => '内部子项继续使用标准表单项配置']],
            <<<'CODE'
[
    [
        'type' => 'fieldset',
        'name' => 'contact_block',
        'label' => '联系人信息',
        'tips' => '用于集中展示联系人相关字段',
        'options' => [
            ['type' => 'text', 'name' => 'contact_name', 'label' => '联系人'],
            ['type' => 'text', 'name' => 'contact_mobile', 'label' => '联系电话'],
        ],
    ],
]
CODE,
            <<<'CODE'
use form\fieldset\Fieldset;

$this->form->item(
    Fieldset::make('contact_block', '联系人信息')
        ->tips('用于集中展示联系人相关字段')
        ->options([
            ['type' => 'text', 'name' => 'contact_name', 'label' => '联系人'],
            ['type' => 'text', 'name' => 'contact_mobile', 'label' => '联系电话'],
        ])
);
CODE,
            ['fieldset 的核心不是新协议，而是把一组普通字段包装成结构块。'],
            function (): string {
                Form::clearInstances();

                return Form::make(uniqid('showcase_fieldset_basic_', false), '基础字段块')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Fieldset::make('contact_block', '联系人信息')
                            ->tips('用于集中展示联系人相关字段')
                            ->options([
                                ['type' => 'text', 'name' => 'contact_name', 'label' => '联系人'],
                                ['type' => 'text', 'name' => 'contact_mobile', 'label' => '联系电话'],
                            ])
                    )
                    ->fetch();
            }
        );
    }

    private function nestedItems(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => '复杂子项', 'value' => '内部可以继续组合 textarea / switch / select 等真实字段']],
            <<<'CODE'
[
    [
        'type' => 'fieldset',
        'name' => 'seo_block',
        'label' => 'SEO 配置',
        'options' => [
            ['type' => 'text', 'name' => 'seo_title', 'label' => 'SEO 标题'],
            ['type' => 'textarea', 'name' => 'seo_description', 'label' => 'SEO 描述'],
            ['type' => 'switch', 'name' => 'seo_follow', 'label' => '允许抓取'],
        ],
    ],
]
CODE,
            <<<'CODE'
use form\fieldset\Fieldset;

$this->form->item(
    Fieldset::make('seo_block', 'SEO 配置')
        ->options([
            ['type' => 'text', 'name' => 'seo_title', 'label' => 'SEO 标题'],
            ['type' => 'textarea', 'name' => 'seo_description', 'label' => 'SEO 描述'],
            ['type' => 'switch', 'name' => 'seo_follow', 'label' => '允许抓取'],
        ])
);
CODE,
            ['如果内部子项本来就需要上传、联动或编辑器，fieldset 也不会阻断这些能力。'],
            function (): string {
                Form::clearInstances();

                return Form::make(uniqid('showcase_fieldset_nested_', false), '复杂子项组合')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Fieldset::make('seo_block', 'SEO 配置')
                            ->options([
                                ['type' => 'text', 'name' => 'seo_title', 'label' => 'SEO 标题'],
                                ['type' => 'textarea', 'name' => 'seo_description', 'label' => 'SEO 描述'],
                                ['type' => 'switch', 'name' => 'seo_follow', 'label' => '允许抓取'],
                            ])
                    )
                    ->fetch();
            }
        );
    }

    private function whenSection(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => 'when', 'value' => '联动作用于整个字段块']],
            <<<'CODE'
[
    ['type' => 'switch', 'name' => 'enable_seo', 'label' => '启用 SEO'],
    [
        'type' => 'fieldset',
        'name' => 'seo_block',
        'label' => 'SEO 配置',
        'when' => ['enable_seo', 'eq', 1, ['show'], ['hide']],
        'options' => [
            ['type' => 'text', 'name' => 'seo_title', 'label' => 'SEO 标题'],
        ],
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;
use form\fieldset\Fieldset;

Field::switch('enable_seo', '启用 SEO');

$this->form->item(
    Fieldset::make('seo_block', 'SEO 配置')
        ->when('enable_seo', 'eq', '1', ['show'], ['hide'])
        ->options([
            ['type' => 'text', 'name' => 'seo_title', 'label' => 'SEO 标题'],
        ])
);
CODE,
            ['这里的重点是让开发者明确：when 控制的是外层字段块整体，而不是某个内部子项。'],
            function (): string {
                Form::clearInstances();

                return Form::make(uniqid('showcase_fieldset_when_', false), '外层联动控制')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(Field::switch('enable_seo', '启用 SEO')->value(1))
                    ->item(
                        Fieldset::make('seo_block', 'SEO 配置')
                            ->when('enable_seo', 'eq', '1', ['show'], ['hide'])
                            ->options([
                                ['type' => 'text', 'name' => 'seo_title', 'label' => 'SEO 标题'],
                            ])
                    )
                    ->fetch();
            }
        );
    }

    private function defaultValue(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => 'data()', 'value' => '内部字段按各自 name 回显，不依赖 fieldset 外层 value']],
            <<<'CODE'
$this->form->data([
    'contact_name' => '张三',
    'contact_mobile' => '13800138000',
]);

[
    [
        'type' => 'fieldset',
        'name' => 'contact_block',
        'label' => '联系人信息',
        'options' => [
            ['type' => 'text', 'name' => 'contact_name', 'label' => '联系人'],
            ['type' => 'text', 'name' => 'contact_mobile', 'label' => '联系电话'],
        ],
    ],
]
CODE,
            <<<'CODE'
use form\fieldset\Fieldset;

$this->form->data([
    'contact_name' => '张三',
    'contact_mobile' => '13800138000',
]);

$this->form->item(
    Fieldset::make('contact_block', '联系人信息')
        ->options([
            ['type' => 'text', 'name' => 'contact_name', 'label' => '联系人'],
            ['type' => 'text', 'name' => 'contact_mobile', 'label' => '联系电话'],
        ])
);
CODE,
            ['fieldset 自己不会把 value 拆给子项，回显仍看每个内部字段自己的 name。'],
            function (): string {
                Form::clearInstances();

                return Form::make(uniqid('showcase_fieldset_value_', false), '默认值与回显方式')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->data([
                        'contact_name' => '张三',
                        'contact_mobile' => '13800138000',
                    ])
                    ->item(
                        Fieldset::make('contact_block', '联系人信息')
                            ->options([
                                ['type' => 'text', 'name' => 'contact_name', 'label' => '联系人'],
                                ['type' => 'text', 'name' => 'contact_mobile', 'label' => '联系电话'],
                            ])
                    )
                    ->fetch();
            }
        );
    }

    private function helpTips(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => 'help / tips', 'value' => '标题帮助提示与底部说明文案']],
            <<<'CODE'
[
    [
        'type' => 'fieldset',
        'name' => 'advanced_block',
        'label' => '高级设置',
        'help' => '这是一组高级参数，请谨慎修改',
        'tips' => '用于维护少量高级参数',
        'options' => [
            ['type' => 'number', 'name' => 'sort', 'label' => '排序'],
            ['type' => 'switch', 'name' => 'is_lock', 'label' => '锁定'],
        ],
    ],
]
CODE,
            <<<'CODE'
use form\fieldset\Fieldset;

$this->form->item(
    Fieldset::make('advanced_block', '高级设置')
        ->attr('help', '这是一组高级参数，请谨慎修改')
        ->tips('用于维护少量高级参数')
        ->options([
            ['type' => 'number', 'name' => 'sort', 'label' => '排序'],
            ['type' => 'switch', 'name' => 'is_lock', 'label' => '锁定'],
        ])
);
CODE,
            ['这个板块主要回答两个问题：标题右侧的帮助说明怎么写，底部 tips 怎么和字段块一起工作。'],
            function (): string {
                Form::clearInstances();

                return Form::make(uniqid('showcase_fieldset_help_', false), 'help 与 tips')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Fieldset::make('advanced_block', '高级设置')
                            ->attr('help', '这是一组高级参数，请谨慎修改')
                            ->tips('用于维护少量高级参数')
                            ->options([
                                ['type' => 'number', 'name' => 'sort', 'label' => '排序'],
                                ['type' => 'switch', 'name' => 'is_lock', 'label' => '锁定'],
                            ])
                    )
                    ->fetch();
            }
        );
    }

    private function profileForm(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => '页面结构分区', 'value' => '复杂编辑页中的联系人块与 SEO 块']],
            <<<'CODE'
[
    [
        'type' => 'fieldset',
        'name' => 'contact_block',
        'label' => '联系人信息',
        'options' => [
            ['type' => 'text', 'name' => 'contact_name', 'label' => '联系人'],
            ['type' => 'text', 'name' => 'contact_mobile', 'label' => '联系电话'],
        ],
    ],
    [
        'type' => 'fieldset',
        'name' => 'seo_block',
        'label' => 'SEO 配置',
        'options' => [
            ['type' => 'text', 'name' => 'seo_title', 'label' => 'SEO 标题'],
        ],
    ],
]
CODE,
            <<<'CODE'
use form\fieldset\Fieldset;

$this->form->item(
    Fieldset::make('contact_block', '联系人信息')
        ->options([
            ['type' => 'text', 'name' => 'contact_name', 'label' => '联系人'],
            ['type' => 'text', 'name' => 'contact_mobile', 'label' => '联系电话'],
        ])
);

$this->form->item(
    Fieldset::make('seo_block', 'SEO 配置')
        ->options([
            ['type' => 'text', 'name' => 'seo_title', 'label' => 'SEO 标题'],
        ])
);
CODE,
            ['复杂编辑页里，fieldset 最有价值的用法不是“单独一个块”，而是组织整页结构。'],
            function (): string {
                Form::clearInstances();

                return Form::make(uniqid('showcase_fieldset_profile_', false), '业务表单片段')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Fieldset::make('contact_block', '联系人信息')
                            ->options([
                                ['type' => 'text', 'name' => 'contact_name', 'label' => '联系人'],
                                ['type' => 'text', 'name' => 'contact_mobile', 'label' => '联系电话'],
                            ])
                    )
                    ->item(
                        Fieldset::make('seo_block', 'SEO 配置')
                            ->options([
                                ['type' => 'text', 'name' => 'seo_title', 'label' => 'SEO 标题'],
                            ])
                    )
                    ->fetch();
            }
        );
    }

    private function wrap(array $section, array $params, string $arrayCode, string $fieldCode, array $notes, callable $previewBuilder): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'basic'),
            'title' => (string) ($section['title'] ?? ''),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $previewBuilder(),
            'params' => $params,
            'array_code' => $arrayCode,
            'field_code' => $fieldCode,
            'notes' => $notes,
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/extensions/FieldsetSectionBuilder.php',
                    'label' => 'fieldset 能力块',
                    'description' => '按 section key 组装 fieldset 的完整示例能力块。',
                ],
                [
                    'path' => 'extend/form/fieldset/Fieldset.php',
                    'label' => '扩展项门面类',
                    'description' => 'fieldset 的独立门面入口，供业务表单直接调用。',
                ],
                [
                    'path' => 'extend/form/fieldset/Item.php',
                    'label' => '扩展项渲染器',
                    'description' => 'fieldset 的真实渲染与子项递归处理逻辑。',
                ],
            ],
        ];
    }
}
