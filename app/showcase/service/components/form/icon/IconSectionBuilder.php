<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\icon;

use app\common\render\Form;
use app\common\render\form\items\icon\Icon;
use app\common\render\form\items\text\Text;
use think\facade\Config;

/**
 * icon 能力块构建器
 */
final class IconSectionBuilder
{
    public function build(array $component, array $section): array
    {
        $key = (string) ($section['key'] ?? 'basic');

        return match ($key) {
            'basic' => $this->basic($section),
            'default_value' => $this->defaultValue($section),
            'placeholder' => $this->placeholder($section),
            'libs_files' => $this->libsFiles($section),
            'notice' => $this->notice($section),
            'profile_form' => $this->profile($section),
            default => $this->basic($section),
        };
    }

    private function basic(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => '默认图标库', 'value' => '默认加载内置 Tabler、Simple Line、Font Awesome 等图标库']],
            <<<'CODE'
[
    [
        'type' => 'icon',
        'name' => 'icon',
        'label' => '图标',
        'tips' => '请选择图标',
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::icon('icon', '图标')
    ->tips('请选择图标');
CODE,
            ['基础示例主要让开发者看到默认弹窗选择器和图标返回值形态。'],
            function (): string {
                Form::clearInstances();
                Config::set([
                    'auto_load_css' => false,
                    'libs' => [],
                ], 'icon');
                return Form::make(uniqid('showcase_icon_basic_', false), '基础图标选择')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(Icon::make('icon', '图标')->id('icon_basic')->tips('请选择图标')->attr('builtin_only', true))
                    ->fetch();
            }
        );
    }

    private function defaultValue(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => 'value', 'value' => '用图标 class 直接回显当前选中图标']],
            <<<'CODE'
[
    [
        'type' => 'icon',
        'name' => 'menu_icon',
        'label' => '菜单图标',
        'value' => 'ti ti-layout-dashboard',
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::icon('menu_icon', '菜单图标')
    ->value('ti ti-layout-dashboard');
CODE,
            ['icon 的默认值和最终存储值本质上都是图标 class 字符串。'],
            function (): string {
                Form::clearInstances();
                Config::set([
                    'auto_load_css' => false,
                    'libs' => [],
                ], 'icon');
                return Form::make(uniqid('showcase_icon_value_', false), '默认值与回显')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Icon::make('menu_icon', '菜单图标')
                            ->value('ti ti-layout-dashboard')
                            ->attr('builtin_only', true)
                    )
                    ->fetch();
            }
        );
    }

    private function placeholder(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => 'options.placeholder / default_icon', 'value' => '配置输入框提示和未选择时的预览图标']],
            <<<'CODE'
[
    [
        'type' => 'icon',
        'name' => 'action_icon',
        'label' => '动作图标',
        'options' => [
            'placeholder' => '请输入关键字搜索图标',
            'default_icon' => 'fa fa-icons',
        ],
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::icon('action_icon', '动作图标')
    ->options([
        'placeholder' => '请输入关键字搜索图标',
        'default_icon' => 'fa fa-icons',
    ]);
CODE,
            ['这两个 options 是 icon 组件最常用、最直观的交互增强参数。'],
            function (): string {
                Form::clearInstances();
                Config::set([
                    'auto_load_css' => false,
                    'libs' => [],
                ], 'icon');
                return Form::make(uniqid('showcase_icon_placeholder_', false), '占位符与默认预览图标')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Icon::make('action_icon', '动作图标')
                            ->options([
                                'placeholder' => '请输入关键字搜索图标',
                                'default_icon' => 'fa fa-icons',
                            ])
                            ->attr('builtin_only', true)
                    )
                    ->fetch();
            }
        );
    }

    private function libsFiles(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => 'libsFile / libsFiles', 'value' => '可加载项目内额外图标库定义文件']],
            <<<'CODE'
[
    [
        'type' => 'icon',
        'name' => 'custom_icon',
        'label' => '扩展图标',
        'libs_files' => ['app/common/render/form/items/icon/libs/tabler.php'],
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::icon('custom_icon', '扩展图标')
    ->libsFiles(['app/common/render/form/items/icon/libs/tabler.php']);
CODE,
            ['示例里复用了项目内现成图标库文件，重点是演示方法本身，而不是新增图标源。'],
            function (): string {
                Form::clearInstances();
                Config::set([
                    'auto_load_css' => false,
                    'libs' => [],
                ], 'icon');
                return Form::make(uniqid('showcase_icon_libs_', false), '扩展图标库文件')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Icon::make('custom_icon', '扩展图标')
                            ->libsFiles(['app/common/render/form/items/icon/libs/tabler.php'])
                            ->attr('builtin_only', true)
                    )
                    ->fetch();
            }
        );
    }

    private function notice(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => '配置提示', 'value' => '扩展图标库需要保证 CSS 已正确加载']],
            <<<'CODE'
[
    [
        'type' => 'icon',
        'name' => 'notice_icon',
        'label' => '图标选择',
        'value' => 'ti ti-bell',
        'tips' => 'icon 最终返回图标 class；如果追加自定义图标库，必须同时保证对应 CSS 已被加载',
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::icon('notice_icon', '图标选择')
    ->value('ti ti-bell')
    ->tips('icon 最终返回图标 class；如果追加自定义图标库，必须同时保证对应 CSS 已被加载');
CODE,
            ['这里重点说明两个边界：保存的是图标 class，扩展图标库时还要确保对应 CSS 已加载。'],
            function (): string {
                Form::clearInstances();
                Config::set([
                    'auto_load_css' => false,
                    'libs' => [],
                ], 'icon');
                return Form::make(uniqid('showcase_icon_notice_', false), '配置与使用提示')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Icon::make('notice_icon', '图标选择')
                            ->value('ti ti-bell')
                            ->tips('icon 最终返回图标 class；如果追加自定义图标库，必须同时保证对应 CSS 已被加载')
                            ->attr('builtin_only', true)
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
                ['name' => 'title / icon / route', 'value' => '后台菜单或快捷入口配置的典型场景'],
            ],
            <<<'CODE'
[
    ['type' => 'text', 'name' => 'title', 'label' => '菜单名称', 'tips' => '请输入菜单名称'],
    ['type' => 'icon', 'name' => 'icon', 'label' => '菜单图标', 'value' => 'ti ti-home'],
    ['type' => 'text', 'name' => 'route', 'label' => '路由地址', 'tips' => '请输入菜单路由'],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::text('title', '菜单名称', '请输入菜单名称');
Field::icon('icon', '菜单图标')->value('ti ti-home');
Field::text('route', '路由地址', '请输入菜单路由');
CODE,
            ['这是开发者最容易照抄复用的 icon 业务场景。'],
            function (): string {
                Form::clearInstances();
                Config::set([
                    'auto_load_css' => false,
                    'libs' => [],
                ], 'icon');
                return Form::make(uniqid('showcase_icon_profile_', false), '业务表单片段')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(Text::make('title', '菜单名称', '请输入菜单名称'))
                    ->item(Icon::make('icon', '菜单图标')->id('icon_profile_form')->value('ti ti-home')->attr('builtin_only', true))
                    ->item(Text::make('route', '路由地址', '请输入菜单路由'))
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
                'path' => 'app/showcase/service/components/form/icon/IconSectionBuilder.php',
                'label' => 'icon 能力块',
                'description' => '按 section key 组装 icon 的完整示例能力块。',
            ]],
        ];
    }
}
