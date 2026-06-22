<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\button_group;

use app\common\render\Form;
use app\common\render\form\items\button_group\ButtonGroup;
use app\common\render\form\items\text\Text;

/**
 * button_group 能力块构建器
 */
final class ButtonGroupSectionBuilder
{
    public function build(array $component, array $section): array
    {
        $key = (string) ($section['key'] ?? 'basic');

        return match ($key) {
            'basic' => $this->basic($section),
            'default_value' => $this->defaultValue($section),
            'icon' => $this->icon($section),
            'vertical' => $this->vertical($section),
            'group_class' => $this->groupClass($section),
            'notice' => $this->notice($section),
            'profile_form' => $this->profile($section),
            default => $this->basic($section),
        };
    }

    private function basic(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => 'options', 'value' => '模板实际读取 options 作为按钮项']],
            <<<'CODE'
[
    [
        'type' => 'button_group',
        'name' => 'view_mode',
        'label' => '视图模式',
        'options' => [
            'table' => '表格',
            'card' => '卡片',
            'list' => '列表',
        ],
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::buttonGroup('view_mode', '视图模式')
    ->options([
        'table' => '表格',
        'card' => '卡片',
        'list' => '列表',
    ]);
CODE,
            ['button_group 的数组配置关键是 `options`，不是旧文档中的 `buttons`。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_button_group_basic_', false), '基础按钮组')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        ButtonGroup::make('view_mode', '视图模式')
                            ->id('view_mode_basic')
                            ->options([
                                'table' => '表格',
                                'card' => '卡片',
                                'list' => '列表',
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
            [['name' => 'value', 'value' => '根据 options 的 key 指定默认激活项']],
            <<<'CODE'
[
    [
        'type' => 'button_group',
        'name' => 'audit_status',
        'label' => '审核状态',
        'options' => [
            'pending' => '待审核',
            'approved' => '已通过',
            'rejected' => '已驳回',
        ],
        'value' => 'approved',
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::buttonGroup('audit_status', '审核状态')
    ->options([
        'pending' => '待审核',
        'approved' => '已通过',
        'rejected' => '已驳回',
    ])
    ->value('approved');
CODE,
            [
                'value 最终提交的是单个字符串 key，后端处理上和 radio 很接近。',
                '如果需要持久化展示模式、审核状态这类互斥值，后端直接按普通字符串字段接收即可。',
            ],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_button_group_default_', false), '默认值与回填')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        ButtonGroup::make('audit_status', '审核状态')
                            ->options([
                                'pending' => '待审核',
                                'approved' => '已通过',
                                'rejected' => '已驳回',
                            ])
                            ->value('approved')
                    )
                    ->fetch();
            }
        );
    }

    private function icon(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => 'icon(true)', 'value' => '把按钮组切成图标模式']],
            <<<'CODE'
[
    [
        'type' => 'button_group',
        'name' => 'quick_action',
        'label' => '快捷视图',
        'options' => [
            'dashboard' => '<i class="ti ti-layout-dashboard"></i>',
            'table' => '<i class="ti ti-table"></i>',
            'chart' => '<i class="ti ti-chart-bar"></i>',
        ],
        'icon' => true,
        'value' => 'table',
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::buttonGroup('quick_action', '快捷视图')
    ->options([
        'dashboard' => '<i class="ti ti-layout-dashboard"></i>',
        'table' => '<i class="ti ti-table"></i>',
        'chart' => '<i class="ti ti-chart-bar"></i>',
    ])
    ->icon()
    ->value('table');
CODE,
            ['图标按钮组适合工具切换，但最好仍配合 label 说明整体语义。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_button_group_icon_', false), '图标按钮组')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        ButtonGroup::make('quick_action', '快捷视图')
                            ->options([
                                'dashboard' => '<i class="ti ti-layout-dashboard"></i>',
                                'table' => '<i class="ti ti-table"></i>',
                                'chart' => '<i class="ti ti-chart-bar"></i>',
                            ])
                            ->icon()
                            ->value('table')
                    )
                    ->fetch();
            }
        );
    }

    private function vertical(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => 'vertical(true)', 'value' => '容器类切换为 btn-group-vertical']],
            <<<'CODE'
[
    [
        'type' => 'button_group',
        'name' => 'device_mode',
        'label' => '设备模式',
        'options' => [
            'desktop' => '桌面端',
            'tablet' => '平板端',
            'mobile' => '移动端',
        ],
        'vertical' => true,
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::buttonGroup('device_mode', '设备模式')
    ->options([
        'desktop' => '桌面端',
        'tablet' => '平板端',
        'mobile' => '移动端',
    ])
    ->vertical();
CODE,
            [
                '垂直布局适合窄侧栏或筛选工具区。',
                'vertical 会直接切成 `btn-group-vertical`，底层不会保留你原本设置的 group_class。',
            ],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_button_group_vertical_', false), '垂直布局')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        ButtonGroup::make('device_mode', '设备模式')
                            ->options([
                                'desktop' => '桌面端',
                                'tablet' => '平板端',
                                'mobile' => '移动端',
                            ])
                            ->vertical()
                    )
                    ->fetch();
            }
        );
    }

    private function groupClass(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => 'group_class', 'value' => '自定义按钮组容器类名']],
            <<<'CODE'
[
    [
        'type' => 'button_group',
        'name' => 'layout_mode',
        'label' => '布局模式',
        'options' => [
            'wide' => '宽屏',
            'boxed' => '盒式',
        ],
        'group_class' => 'btn-group w-100',
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::buttonGroup('layout_mode', '布局模式')
    ->options([
        'wide' => '宽屏',
        'boxed' => '盒式',
    ])
    ->groupClass('btn-group w-100');
CODE,
            [
                '如果需要铺满整行或接入自定义布局类，优先改 group_class。',
                'group_class 会被 vertical 覆盖，所以不要指望二者叠加出一个自定义纵向容器。',
            ],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_button_group_class_', false), '自定义 group_class')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        ButtonGroup::make('layout_mode', '布局模式')
                            ->options([
                                'wide' => '宽屏',
                                'boxed' => '盒式',
                            ])
                            ->groupClass('btn-group w-100')
                    )
                    ->fetch();
            }
        );
    }

    private function notice(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => '提交值', 'value' => '最终提交的是当前选中的 key']],
            <<<'CODE'
[
    [
        'type' => 'button_group',
        'name' => 'publish_state',
        'label' => '发布状态',
        'options' => [
            'draft' => '草稿',
            'review' => '待审核',
            'published' => '已发布',
        ],
        'value' => 'review',
        'tips' => 'button_group 与 radio 类似，最终提交的仍然是单个选中 key',
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::buttonGroup('publish_state', '发布状态')
    ->options([
        'draft' => '草稿',
        'review' => '待审核',
        'published' => '已发布',
    ])
    ->value('review')
    ->tips('button_group 与 radio 类似，最终提交的仍然是单个选中 key');
CODE,
            ['这个板块的重点是让开发者一眼看懂：button_group 提交的是单个互斥 key，不是一组动作按钮。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_button_group_notice_', false), '值处理提示')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        ButtonGroup::make('publish_state', '发布状态')
                            ->options([
                                'draft' => '草稿',
                                'review' => '待审核',
                                'published' => '已发布',
                            ])
                            ->value('review')
                            ->tips('button_group 与 radio 类似，最终提交的仍然是单个选中 key')
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
                ['name' => 'keyword + view_mode', 'value' => '搜索表单中常见的模式切换组合'],
            ],
            <<<'CODE'
[
    ['text', 'keyword', '关键词', '请输入关键词'],
    [
        'type' => 'button_group',
        'name' => 'view_mode',
        'label' => '展示模式',
        'options' => [
            'table' => '表格',
            'card' => '卡片',
            'chart' => '图表',
        ],
        'value' => 'table',
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::text('keyword', '关键词', '请输入关键词');

Field::buttonGroup('view_mode', '展示模式')
    ->options([
        'table' => '表格',
        'card' => '卡片',
        'chart' => '图表',
    ])
    ->value('table');
CODE,
            ['这是列表搜索区、数据中心视图切换中最典型的一种 button_group 组合。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_button_group_profile_', false), '业务表单片段')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(Text::make('keyword', '关键词', '请输入关键词'))
                    ->item(
                        ButtonGroup::make('view_mode', '展示模式')
                            ->id('view_mode_profile_form')
                            ->options([
                                'table' => '表格',
                                'card' => '卡片',
                                'chart' => '图表',
                            ])
                            ->value('table')
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
                'path' => 'app/showcase/service/components/form/button_group/ButtonGroupSectionBuilder.php',
                'label' => 'button_group 能力块',
                'description' => '按 section key 组装 button_group 的完整示例能力块。',
            ]],
        ];
    }
}
