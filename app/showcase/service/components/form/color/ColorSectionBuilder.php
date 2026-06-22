<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\color;

use app\common\render\Form;
use app\common\render\form\items\color\Color;
use app\common\render\form\items\text\Text;

/**
 * color 能力块构建器
 */
final class ColorSectionBuilder
{
    public function build(array $component, array $section): array
    {
        $key = (string) ($section['key'] ?? 'basic');

        return match ($key) {
            'basic' => $this->basic($section),
            'default_value' => $this->defaultValue($section),
            'alpha_format' => $this->alphaFormat($section),
            'theme_swatches' => $this->themeSwatches($section),
            'actions' => $this->actions($section),
            'group_class' => $this->groupClass($section),
            'profile_form' => $this->profile($section),
            default => $this->basic($section),
        };
    }

    private function basic(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => 'value', 'value' => '可选，默认留空等待用户自由取色']],
            <<<'CODE'
[
    [
        'type' => 'color',
        'name' => 'theme_color',
        'label' => '主题色',
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::color('theme_color', '主题色');
CODE,
            ['color 是自由取色器，适合没有固定色板约束的场景。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_color_basic_', false), '基础取色器')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(Color::make('theme_color', '主题色')->id('theme_color_basic'))
                    ->fetch();
            }
        );
    }

    private function defaultValue(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => 'value', 'value' => '用 HEX 或 RGBA 等格式提供默认颜色']],
            <<<'CODE'
[
    [
        'type' => 'color',
        'name' => 'brand_color',
        'label' => '品牌色',
        'value' => '#206bc4',
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::color('brand_color', '品牌色')
    ->value('#206bc4');
CODE,
            ['默认值和编辑态回填用法一致，关键是和后端保存格式保持统一。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_color_value_', false), '默认值与回填')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Color::make('brand_color', '品牌色')
                            ->value('#206bc4')
                    )
                    ->fetch();
            }
        );
    }

    private function alphaFormat(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => 'options.alpha / format', 'value' => '控制是否支持透明度与颜色输出格式']],
            <<<'CODE'
[
    [
        'type' => 'color',
        'name' => 'overlay_color',
        'label' => '遮罩色',
        'value' => 'rgba(32, 107, 196, 0.65)',
        'options' => [
            'alpha' => true,
            'format' => 'rgb',
        ],
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::color('overlay_color', '遮罩色')
    ->value('rgba(32, 107, 196, 0.65)')
    ->options([
        'alpha' => true,
        'format' => 'rgb',
    ]);
CODE,
            ['需要透明度时，后端字段长度和格式校验也要同步调整。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_color_alpha_', false), '颜色格式与透明度')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Color::make('overlay_color', '遮罩色')
                            ->value('rgba(32, 107, 196, 0.65)')
                            ->options([
                                'alpha' => true,
                                'format' => 'rgb',
                            ])
                    )
                    ->fetch();
            }
        );
    }

    private function themeSwatches(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => 'options.swatches / theme', 'value' => '提供预设色板并切换弹层样式']],
            <<<'CODE'
[
    [
        'type' => 'color',
        'name' => 'status_color',
        'label' => '状态色',
        'options' => [
            'theme' => 'large',
            'swatches' => ['#206bc4', '#2fb344', '#f76707', '#d63939'],
        ],
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::color('status_color', '状态色')
    ->options([
        'theme' => 'large',
        'swatches' => ['#206bc4', '#2fb344', '#f76707', '#d63939'],
    ]);
CODE,
            ['预设色板非常适合把设计系统中的品牌色直接给到开发者或运营使用。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_color_swatches_', false), '预设色与主题样式')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Color::make('status_color', '状态色')
                            ->options([
                                'theme' => 'large',
                                'swatches' => ['#206bc4', '#2fb344', '#f76707', '#d63939'],
                            ])
                    )
                    ->fetch();
            }
        );
    }

    private function actions(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => 'clearButton / closeButton / formatToggle', 'value' => '控制弹层交互按钮']],
            <<<'CODE'
[
    [
        'type' => 'color',
        'name' => 'advanced_color',
        'label' => '高级取色',
        'value' => '#206bc4',
        'options' => [
            'theme' => 'pill',
            'clearButton' => true,
            'closeButton' => true,
            'formatToggle' => true,
        ],
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::color('advanced_color', '高级取色')
    ->value('#206bc4')
    ->options([
        'theme' => 'pill',
        'clearButton' => true,
        'closeButton' => true,
        'formatToggle' => true,
    ]);
CODE,
            ['这类交互开关适合给高级配置页或内部运营工具使用。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_color_actions_', false), '显示按钮与交互开关')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Color::make('advanced_color', '高级取色')
                            ->value('#206bc4')
                            ->options([
                                'theme' => 'pill',
                                'clearButton' => true,
                                'closeButton' => true,
                                'formatToggle' => true,
                            ])
                    )
                    ->fetch();
            }
        );
    }

    private function groupClass(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => 'group_class', 'value' => '控制颜色输入外层容器布局样式']],
            <<<'CODE'
[
    [
        'type' => 'color',
        'name' => 'compact_color',
        'label' => '紧凑色值',
        'group_class' => 'w-auto',
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::color('compact_color', '紧凑色值')
    ->groupClass('w-auto');
CODE,
            ['group_class 适合微调颜色输入框的容器宽度和排版方式。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_color_group_', false), 'group_class 与布局')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Color::make('compact_color', '紧凑色值')
                            ->groupClass('w-auto')
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
                ['name' => 'theme_name / theme_color', 'value' => '真实主题配置场景'],
            ],
            <<<'CODE'
[
    ['type' => 'text', 'name' => 'theme_name', 'label' => '主题名称', 'tips' => '请输入主题名称'],
    [
        'type' => 'color',
        'name' => 'theme_color',
        'label' => '主题色',
        'value' => '#206bc4',
        'options' => [
            'swatches' => ['#206bc4', '#2fb344', '#f76707', '#d63939'],
        ],
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::text('theme_name', '主题名称', '请输入主题名称');

Field::color('theme_color', '主题色')
    ->value('#206bc4')
    ->options([
        'swatches' => ['#206bc4', '#2fb344', '#f76707', '#d63939'],
    ]);
CODE,
            ['这是最典型的品牌主题、栏目主题配置表单组合。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_color_profile_', false), '业务表单片段')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(Text::make('theme_name', '主题名称', '请输入主题名称'))
                    ->item(
                        Color::make('theme_color', '主题色')
                            ->id('theme_color_profile_form')
                            ->value('#206bc4')
                            ->options([
                                'swatches' => ['#206bc4', '#2fb344', '#f76707', '#d63939'],
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
            'source_refs' => [[
                'path' => 'app/showcase/service/components/form/color/ColorSectionBuilder.php',
                'label' => 'color 能力块',
                'description' => '按 section key 组装 color 的完整示例能力块。',
            ]],
        ];
    }
}
