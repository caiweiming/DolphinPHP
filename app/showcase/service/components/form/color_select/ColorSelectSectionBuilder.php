<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\color_select;

use app\common\render\Form;
use app\common\render\form\items\color_select\ColorSelect;
use app\common\render\form\items\text\Text;

/**
 * color_select 能力块构建器
 */
final class ColorSelectSectionBuilder
{
    public function build(array $component, array $section): array
    {
        $key = (string) ($section['key'] ?? 'basic');

        return match ($key) {
            'basic' => $this->basic($section),
            'circle' => $this->circle($section),
            'custom_values' => $this->customValues($section),
            'multiple' => $this->multiple($section),
            'disabled' => $this->disabled($section),
            'notice' => $this->notice($section),
            'profile_form' => $this->profile($section),
            default => $this->basic($section),
        };
    }

    private function basic(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => 'options', 'value' => '支持直接传主题色名或颜色值']],
            <<<'CODE'
[
    [
        'type' => 'color_select',
        'name' => 'color',
        'label' => '颜色',
        'options' => ['blue', 'green', 'orange'],
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::colorSelect('color', '颜色')
    ->options(['blue', 'green', 'orange']);
CODE,
            ['color_select 适合把颜色选择限制在一个明确的预设范围内。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_color_select_basic_', false), '基础预设颜色')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        ColorSelect::make('color', '颜色')
                            ->options(['blue', 'green', 'orange'])
                    )
                    ->fetch();
            }
        );
    }

    private function circle(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => 'circle(true)', 'value' => '将颜色项切换成圆形样式']],
            <<<'CODE'
[
    [
        'type' => 'color_select',
        'name' => 'badge_color',
        'label' => '标签色',
        'circle' => true,
        'options' => ['blue', 'green', 'orange', 'red'],
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::colorSelect('badge_color', '标签色')
    ->circle()
    ->options(['blue', 'green', 'orange', 'red']);
CODE,
            ['圆形样式更适合标签色、状态点这类视觉上本来就是圆点的场景。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_color_select_circle_', false), '圆形样式 circle')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        ColorSelect::make('badge_color', '标签色')
                            ->circle()
                            ->options(['blue', 'green', 'orange', 'red'])
                    )
                    ->fetch();
            }
        );
    }

    private function customValues(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => '自定义颜色值', 'value' => '支持主题色、自定义 style 和复杂对象']],
            <<<'CODE'
[
    [
        'type' => 'color_select',
        'name' => 'custom_palette',
        'label' => '自定义色板',
        'options' => [
            'brand' => '#1633ac',
            'warn' => 'rgb(177 30 31)',
            'light' => [
                'style' => 'background-color: #eeeeee',
                'label_class' => 'form-colorinput-light',
            ],
        ],
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::colorSelect('custom_palette', '自定义色板')
    ->options([
        'brand' => '#1633ac',
        'warn' => 'rgb(177 30 31)',
        'light' => [
            'style' => 'background-color: #eeeeee',
            'label_class' => 'form-colorinput-light',
        ],
    ]);
CODE,
            ['复杂对象配置适合做亮色边框、浅灰底等特殊可读性处理。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_color_select_custom_', false), '自定义颜色值')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        ColorSelect::make('custom_palette', '自定义色板')
                            ->options([
                                'brand' => '#1633ac',
                                'warn' => 'rgb(177 30 31)',
                                'light' => [
                                    'style' => 'background-color: #eeeeee',
                                    'label_class' => 'form-colorinput-light',
                                ],
                            ])
                    )
                    ->fetch();
            }
        );
    }

    private function multiple(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => 'multiple(true)', 'value' => '允许同时勾选多个颜色']],
            <<<'CODE'
[
    [
        'type' => 'color_select',
        'name' => 'theme_pool',
        'label' => '可用主题色',
        'multiple' => true,
        'options' => ['blue', 'green', 'orange', 'red'],
        'value' => ['blue', 'red'],
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::colorSelect('theme_pool', '可用主题色')
    ->multiple()
    ->options(['blue', 'green', 'orange', 'red'])
    ->value(['blue', 'red']);
CODE,
            ['多选场景下，后端应按数组处理而不是单值字符串。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_color_select_multiple_', false), 'multiple 多选')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        ColorSelect::make('theme_pool', '可用主题色')
                            ->multiple()
                            ->options(['blue', 'green', 'orange', 'red'])
                            ->value(['blue', 'red'])
                    )
                    ->fetch();
            }
        );
    }

    private function disabled(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => 'disabled', 'value' => '支持禁用指定颜色项']],
            <<<'CODE'
[
    [
        'type' => 'color_select',
        'name' => 'status_palette',
        'label' => '状态色',
        'disabled' => ['red'],
        'options' => ['blue', 'green', 'orange', 'red'],
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::colorSelect('status_palette', '状态色')
    ->options(['blue', 'green', 'orange', 'red'])
    ->disabled(['red']);
CODE,
            ['禁用指定颜色适合某些业务阶段不可选择特定状态色的场景。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_color_select_disabled_', false), '禁用指定颜色')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        ColorSelect::make('status_palette', '状态色')
                            ->options(['blue', 'green', 'orange', 'red'])
                            ->disabled(['red'])
                    )
                    ->fetch();
            }
        );
    }

    private function notice(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => '类型命名', 'value' => '数组配置推荐 color_select，门面方法仍是 colorSelect']],
            <<<'CODE'
[
    [
        'type' => 'color_select',
        'name' => 'theme_color',
        'label' => '主题色',
        'options' => ['blue', 'green', 'orange'],
        'value' => 'green',
        'tips' => '数组配置推荐使用 color_select；Field 门面方法保持为 colorSelect()',
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::colorSelect('theme_color', '主题色')
    ->options(['blue', 'green', 'orange'])
    ->value('green')
    ->tips('数组配置推荐使用 color_select；Field 门面方法保持为 colorSelect()');
CODE,
            ['这块主要是避免开发者在真实 color_select 示例旁边继续沿用旧别名。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_color_select_notice_', false), '别名与类型说明')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        ColorSelect::make('theme_color', '主题色')
                            ->options(['blue', 'green', 'orange'])
                            ->value('green')
                            ->tips('数组配置推荐使用 color_select；Field 门面方法保持为 colorSelect()')
                    )
                    ->fetch();
            }
        );
    }

    private function profile(array $section): array
    {
        return $this->wrap(
            $section,
            [
                ['name' => 'tag_name / tag_color', 'value' => '标签管理中最常见的配色场景'],
            ],
            <<<'CODE'
[
    ['type' => 'text', 'name' => 'tag_name', 'label' => '标签名称', 'tips' => '请输入标签名称'],
    [
        'type' => 'color_select',
        'name' => 'tag_color',
        'label' => '标签颜色',
        'circle' => true,
        'options' => ['blue', 'green', 'orange', 'red'],
        'value' => 'blue',
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::text('tag_name', '标签名称', '请输入标签名称');

Field::colorSelect('tag_color', '标签颜色')
    ->circle()
    ->options(['blue', 'green', 'orange', 'red'])
    ->value('blue');
CODE,
            ['标签、分类、状态管理页里，这就是最典型的 color_select 组合。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_color_select_profile_', false), '业务表单片段')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(Text::make('tag_name', '标签名称', '请输入标签名称'))
                    ->item(
                        ColorSelect::make('tag_color', '标签颜色')
                            ->circle()
                            ->options(['blue', 'green', 'orange', 'red'])
                            ->value('blue')
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
            'source_refs' => [[
                'path' => 'app/showcase/service/components/form/color_select/ColorSelectSectionBuilder.php',
                'label' => 'color_select 能力块',
                'description' => '按 section key 组装 color_select 的完整示例能力块。',
            ]],
        ];
    }
}
