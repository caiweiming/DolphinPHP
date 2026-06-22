<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\checkbox;

use app\common\render\Form;
use app\common\render\form\items\checkbox\Checkbox;
use app\common\render\form\items\text\Text;

/**
 * checkbox 能力块构建器
 */
final class CheckboxSectionBuilder
{
    public function build(array $component, array $section): array
    {
        $key = (string) ($section['key'] ?? 'basic');

        return match ($key) {
            'basic' => $this->basic($section),
            'default_value' => $this->defaultValue($section),
            'inline' => $this->inline($section),
            'disabled' => $this->disabled($section),
            'remark' => $this->remark($section),
            'option_string' => $this->optionString($section),
            'notice' => $this->notice($section),
            'profile_form' => $this->profile($section),
            default => $this->basic($section),
        };
    }

    private function basic(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => 'options', 'value' => '标准键值对多选项']],
            <<<'CODE'
[
    [
        'type' => 'checkbox',
        'name' => 'role_ids',
        'label' => '角色',
        'options' => [
            1 => '管理员',
            2 => '编辑',
            3 => '作者',
        ],
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::checkbox('role_ids', '角色')
    ->options([
        1 => '管理员',
        2 => '编辑',
        3 => '作者',
    ]);
CODE,
            ['基础 checkbox 适合最常见的标准多选框场景。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_checkbox_basic_', false), '基础多选')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Checkbox::make('role_ids', '角色')
                            ->id('role_ids_basic')
                            ->options([1 => '管理员', 2 => '编辑', 3 => '作者'])
                    )
                    ->fetch();
            }
        );
    }

    private function defaultValue(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => 'value', 'value' => '数组形式回显多个默认值']],
            <<<'CODE'
[
    [
        'type' => 'checkbox',
        'name' => 'permissions',
        'label' => '权限',
        'value' => [1, 3],
        'options' => [
            1 => '查看',
            2 => '编辑',
            3 => '删除',
        ],
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::checkbox('permissions', '权限')
    ->options([
        1 => '查看',
        2 => '编辑',
        3 => '删除',
    ])
    ->value([1, 3]);
CODE,
            ['checkbox 的默认值和编辑态回显都使用数组。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_checkbox_value_', false), '默认值与回填')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Checkbox::make('permissions', '权限')
                            ->options([1 => '查看', 2 => '编辑', 3 => '删除'])
                            ->value([1, 3])
                    )
                    ->fetch();
            }
        );
    }

    private function inline(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => 'inline', 'value' => '横向排列少量选项']],
            <<<'CODE'
[
    [
        'type' => 'checkbox',
        'name' => 'week_days',
        'label' => '通知日',
        'inline' => true,
        'options' => [
            'mon' => '周一',
            'wed' => '周三',
            'fri' => '周五',
        ],
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::checkbox('week_days', '通知日')
    ->inline()
    ->options([
        'mon' => '周一',
        'wed' => '周三',
        'fri' => '周五',
    ]);
CODE,
            ['选项较少时横向排列更紧凑，也更适合放进设置类表单。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_checkbox_inline_', false), 'inline 横向排列')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Checkbox::make('week_days', '通知日')
                            ->inline()
                            ->options(['mon' => '周一', 'wed' => '周三', 'fri' => '周五'])
                    )
                    ->fetch();
            }
        );
    }

    private function disabled(array $section): array
    {
        return $this->wrap(
            $section,
            [
                ['name' => 'disabled => true', 'value' => '快速禁用全部选项，底层会自动转成全部 option key'],
                ['name' => 'disabled => [...]', 'value' => '也支持只禁用部分指定值'],
            ],
            <<<'CODE'
[
    [
        'type' => 'checkbox',
        'name' => 'role_scope',
        'label' => '角色范围',
        'disabled' => true,
        'options' => [
            'admin' => '管理员',
            'editor' => '编辑',
            'author' => '作者',
        ],
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::checkbox('role_scope', '角色范围')
    ->options([
        'admin' => '管理员',
        'editor' => '编辑',
        'author' => '作者',
    ])
    ->disabled();
CODE,
            [
                '禁用指定值适合“某些选项当前不可选但仍需展示”的后台场景。',
                '这里故意展示 `disabled(true)`，是为了让开发者知道底层支持一键禁用全部选项，而不必手动列出所有 key。',
            ],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_checkbox_disabled_', false), '禁用状态')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Checkbox::make('role_scope', '角色范围')
                            ->options(['admin' => '管理员', 'editor' => '编辑', 'author' => '作者'])
                            ->disabled()
                    )
                    ->fetch();
            }
        );
    }

    private function remark(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => 'remark', 'value' => '为每个候选项补充说明']],
            <<<'CODE'
[
    [
        'type' => 'checkbox',
        'name' => 'permission_ids',
        'label' => '权限',
        'options' => [
            1 => '查看',
            2 => '编辑',
            3 => '删除',
        ],
        'remark' => [
            1 => '可以查看所有数据',
            2 => '可以编辑业务数据',
            3 => '高风险操作',
        ],
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::checkbox('permission_ids', '权限')
    ->options([
        1 => '查看',
        2 => '编辑',
        3 => '删除',
    ])
    ->remark([
        1 => '可以查看所有数据',
        2 => '可以编辑业务数据',
        3 => '高风险操作',
    ]);
CODE,
            ['权限、能力类选项经常需要说明文案，remark 比额外文本更集中。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_checkbox_remark_', false), '备注说明')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Checkbox::make('permission_ids', '权限')
                            ->options([1 => '查看', 2 => '编辑', 3 => '删除'])
                            ->remark([1 => '可以查看所有数据', 2 => '可以编辑业务数据', 3 => '高风险操作'])
                    )
                    ->fetch();
            }
        );
    }

    private function optionString(array $section): array
    {
        return $this->wrap(
            $section,
            [
                ['name' => 'options 中的 #', 'value' => '自动拆分主文案和备注文案'],
                ['name' => 'remark 自动生成', 'value' => '适合静态备注较短、不想额外维护 remark 数组的场景'],
            ],
            <<<'CODE'
[
    [
        'type' => 'checkbox',
        'name' => 'abilities',
        'label' => '能力范围',
        'options' => [
            'report' => '报表分析#可查看经营分析数据',
            'member' => '会员管理#可编辑会员资料',
            'export' => '数据导出#下载 Excel 报表',
        ],
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::checkbox('abilities', '能力范围')
    ->options([
        'report' => '报表分析#可查看经营分析数据',
        'member' => '会员管理#可编辑会员资料',
        'export' => '数据导出#下载 Excel 报表',
    ]);
CODE,
            [
                '如果备注只是静态说明，直接写进 options 字符串会更省配置。',
                '这也是 checkbox 独有的一个“低配置写法”，开发者看过示例后通常就能直接上手。',
            ],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_checkbox_option_', false), '选项字符串解析')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Checkbox::make('abilities', '能力范围')
                            ->options([
                                'report' => '报表分析#可查看经营分析数据',
                                'member' => '会员管理#可编辑会员资料',
                                'export' => '数据导出#下载 Excel 报表',
                            ])
                    )
                    ->fetch();
            }
        );
    }

    private function notice(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => '数组值处理', 'value' => '未选中时后端通常需要兜底空数组']],
            <<<'CODE'
[
    [
        'type' => 'checkbox',
        'name' => 'ability_keys',
        'label' => '能力范围',
        'options' => [
            'report' => '报表分析',
            'member' => '会员管理',
            'export' => '数据导出',
        ],
        'value' => ['report', 'export'],
        'tips' => 'checkbox 提交为数组，未选中时后端应兜底为空数组',
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::checkbox('ability_keys', '能力范围')
    ->options([
        'report' => '报表分析',
        'member' => '会员管理',
        'export' => '数据导出',
    ])
    ->value(['report', 'export'])
    ->tips('checkbox 提交为数组，未选中时后端应兜底为空数组');
CODE,
            ['这一块强调的是值处理约束，但仍然应该由真实 checkbox 预览来承载，开发者更容易理解“前端长什么样、后端收什么值”。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_checkbox_notice_', false), '联动提示')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Checkbox::make('ability_keys', '能力范围')
                            ->options([
                                'report' => '报表分析',
                                'member' => '会员管理',
                                'export' => '数据导出',
                            ])
                            ->value(['report', 'export'])
                            ->tips('checkbox 提交为数组，未选中时后端应兜底为空数组')
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
                ['name' => 'username', 'value' => '用户名字段'],
                ['name' => 'role_ids', 'value' => '角色多选字段'],
            ],
            <<<'CODE'
[
    ['text', 'username', '用户名', '请输入用户名'],
    [
        'type' => 'checkbox',
        'name' => 'role_ids',
        'label' => '分配角色',
        'tips' => '可选择多个角色',
        'inline' => true,
        'options' => [
            1 => '管理员',
            2 => '编辑',
            3 => '作者',
        ],
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::text('username', '用户名', '请输入用户名');

Field::checkbox('role_ids', '分配角色', '可选择多个角色')
    ->inline()
    ->options([
        1 => '管理员',
        2 => '编辑',
        3 => '作者',
    ]);
CODE,
            ['这是用户管理、权限管理后台里最常见的 checkbox 组合场景。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_checkbox_profile_', false), '业务表单片段')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(Text::make('username', '用户名', '请输入用户名'))
                    ->item(
                        Checkbox::make('role_ids', '分配角色', '可选择多个角色')
                            ->id('role_ids_profile_form')
                            ->inline()
                            ->options([1 => '管理员', 2 => '编辑', 3 => '作者'])
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
                'path' => 'app/showcase/service/components/form/checkbox/CheckboxSectionBuilder.php',
                'label' => 'checkbox 能力块',
                'description' => '按 section key 组装 checkbox 的完整示例能力块。',
            ]],
        ];
    }
}
