<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\checkbox_group;

use app\common\render\Form;
use app\common\render\form\items\checkbox_group\CheckboxGroup;
use app\common\render\form\items\text\Text;

/**
 * checkbox_group 能力块构建器
 */
final class CheckboxGroupSectionBuilder
{
    public function build(array $component, array $section): array
    {
        $key = (string) ($section['key'] ?? 'basic');

        return match ($key) {
            'basic' => $this->basic($section),
            'default_value' => $this->defaultValue($section),
            'required' => $this->requiredSection($section),
            'disabled' => $this->disabled($section),
            'layout' => $this->layout($section),
            'notice' => $this->notice($section),
            'profile_form' => $this->profile($section),
            default => $this->basic($section),
        };
    }

    private function basic(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => 'options', 'value' => '卡片式多选候选项']],
            <<<'CODE'
[
    [
        'type' => 'checkbox_group',
        'name' => 'modules',
        'label' => '功能模块',
        'options' => [
            'user' => '用户管理',
            'content' => '内容管理',
            'setting' => '系统设置',
        ],
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::checkboxGroup('modules', '功能模块')
    ->options([
        'user' => '用户管理',
        'content' => '内容管理',
        'setting' => '系统设置',
    ]);
CODE,
            ['checkbox_group 更适合强调“模块块状选择”而不是普通列表。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_checkbox_group_basic_', false), '基础卡片多选')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        CheckboxGroup::make('modules', '功能模块')
                            ->options(['user' => '用户管理', 'content' => '内容管理', 'setting' => '系统设置'])
                    )
                    ->fetch();
            }
        );
    }

    private function defaultValue(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => 'value', 'value' => '数组形式回显多张卡片']],
            <<<'CODE'
[
    [
        'type' => 'checkbox_group',
        'name' => 'permissions',
        'label' => '权限集合',
        'value' => ['view', 'edit'],
        'options' => [
            'view' => '查看权限',
            'edit' => '编辑权限',
            'delete' => '删除权限',
        ],
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::checkboxGroup('permissions', '权限集合')
    ->options([
        'view' => '查看权限',
        'edit' => '编辑权限',
        'delete' => '删除权限',
    ])
    ->value(['view', 'edit']);
CODE,
            ['卡片式多选和 checkbox 一样，默认值也用数组。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_checkbox_group_value_', false), '默认值与回填')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        CheckboxGroup::make('permissions', '权限集合')
                            ->options(['view' => '查看权限', 'edit' => '编辑权限', 'delete' => '删除权限'])
                            ->value(['view', 'edit'])
                    )
                    ->fetch();
            }
        );
    }

    private function requiredSection(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => 'required', 'value' => '要求至少选中一个卡片项']],
            <<<'CODE'
[
    [
        'type' => 'checkbox_group',
        'name' => 'package_abilities',
        'label' => '套餐能力',
        'required' => true,
        'options' => [
            'report' => '经营分析',
            'crm' => '客户管理',
            'order' => '订单中心',
        ],
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::checkboxGroup('package_abilities', '套餐能力')
    ->required()
    ->options([
        'report' => '经营分析',
        'crm' => '客户管理',
        'order' => '订单中心',
    ]);
CODE,
            ['套餐、模块开通类表单常常要求至少选择一个能力项。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_checkbox_group_required_', false), '必填场景')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        CheckboxGroup::make('package_abilities', '套餐能力')
                            ->required()
                            ->options(['report' => '经营分析', 'crm' => '客户管理', 'order' => '订单中心'])
                    )
                    ->fetch();
            }
        );
    }

    private function disabled(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => 'disabled', 'value' => '支持禁用全部或指定卡片']],
            <<<'CODE'
[
    [
        'type' => 'checkbox_group',
        'name' => 'plan_modules',
        'label' => '计划模块',
        'disabled' => ['finance'],
        'options' => [
            'finance' => '财务中心',
            'member' => '会员中心',
            'content' => '内容中心',
        ],
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::checkboxGroup('plan_modules', '计划模块')
    ->options([
        'finance' => '财务中心',
        'member' => '会员中心',
        'content' => '内容中心',
    ])
    ->disabled(['finance']);
CODE,
            ['禁用卡片适合展示已下线或当前套餐不可用的能力项。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_checkbox_group_disabled_', false), '禁用状态')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        CheckboxGroup::make('plan_modules', '计划模块')
                            ->options(['finance' => '财务中心', 'member' => '会员中心', 'content' => '内容中心'])
                            ->disabled(['finance'])
                    )
                    ->fetch();
            }
        );
    }

    private function layout(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => 'HTML 文案', 'value' => '支持用 HTML 增强卡片表现力']],
            <<<'CODE'
[
    [
        'type' => 'checkbox_group',
        'name' => 'delivery_abilities',
        'label' => '交付能力',
        'options' => [
            'saas' => '<div><strong>SaaS 部署</strong><div class="text-secondary">快速上线</div></div>',
            'private' => '<div><strong>私有部署</strong><div class="text-secondary">支持本地化</div></div>',
        ],
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::checkboxGroup('delivery_abilities', '交付能力')
    ->options([
        'saas' => '<div><strong>SaaS 部署</strong><div class="text-secondary">快速上线</div></div>',
        'private' => '<div><strong>私有部署</strong><div class="text-secondary">支持本地化</div></div>',
    ]);
CODE,
            ['卡片式组件最有价值的一点，就是能承载更丰富的说明文案。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_checkbox_group_layout_', false), '布局与文案')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        CheckboxGroup::make('delivery_abilities', '交付能力')
                            ->options([
                                'saas' => '<div><strong>SaaS 部署</strong><div class="text-secondary">快速上线</div></div>',
                                'private' => '<div><strong>私有部署</strong><div class="text-secondary">支持本地化</div></div>',
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
            [
                ['name' => '数组值处理', 'value' => 'checkbox_group 提交结果是数组'],
                ['name' => '落库建议', 'value' => '建议统一转 JSON 或逗号串存储，并保持候选 value 稳定'],
            ],
            <<<'CODE'
[
    [
        'type' => 'checkbox_group',
        'name' => 'feature_keys',
        'label' => '功能卡片',
        'options' => [
            'user' => '用户管理',
            'order' => '订单中心',
            'report' => '经营报表',
        ],
        'value' => ['user', 'report'],
        'tips' => 'checkbox_group 提交为数组；建议统一转 JSON 或逗号串；数据库里保存稳定 value',
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::checkboxGroup('feature_keys', '功能卡片')
    ->options([
        'user' => '用户管理',
        'order' => '订单中心',
        'report' => '经营报表',
    ])
    ->value(['user', 'report'])
    ->tips('checkbox_group 提交为数组；建议统一转 JSON 或逗号串；数据库里保存稳定 value');
CODE,
            [
                '这里保留真实 checkbox_group 预览，同时把值结构说明收拢到 tips 中，开发者更容易把“怎么配”和“怎么存”一起看懂。',
                '如果卡片文案经常调整，数据库里不要直接存显示文本，应该始终存稳定 value。',
            ],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_checkbox_group_notice_', false), '值处理提示')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        CheckboxGroup::make('feature_keys', '功能卡片')
                            ->options([
                                'user' => '用户管理',
                                'order' => '订单中心',
                                'report' => '经营报表',
                            ])
                            ->value(['user', 'report'])
                            ->tips('checkbox_group 提交为数组；建议统一转 JSON 或逗号串；数据库里保存稳定 value')
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
                ['name' => 'plan_name', 'value' => '套餐名称字段'],
                ['name' => 'module_ids', 'value' => '模块卡片多选字段'],
            ],
            <<<'CODE'
[
    ['text', 'plan_name', '套餐名称', '请输入套餐名称'],
    [
        'type' => 'checkbox_group',
        'name' => 'module_ids',
        'label' => '开放模块',
        'options' => [
            'user' => '用户管理',
            'order' => '订单中心',
            'report' => '经营报表',
        ],
        'value' => ['user', 'report'],
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::text('plan_name', '套餐名称', '请输入套餐名称');

Field::checkboxGroup('module_ids', '开放模块')
    ->options([
        'user' => '用户管理',
        'order' => '订单中心',
        'report' => '经营报表',
    ])
    ->value(['user', 'report']);
CODE,
            ['这是套餐、权限模块类表单中非常典型的卡片多选组合示例。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_checkbox_group_profile_', false), '业务表单片段')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(Text::make('plan_name', '套餐名称', '请输入套餐名称'))
                    ->item(
                        CheckboxGroup::make('module_ids', '开放模块')
                            ->options(['user' => '用户管理', 'order' => '订单中心', 'report' => '经营报表'])
                            ->value(['user', 'report'])
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
                'path' => 'app/showcase/service/components/form/checkbox_group/CheckboxGroupSectionBuilder.php',
                'label' => 'checkbox_group 能力块',
                'description' => '按 section key 组装 checkbox_group 的完整示例能力块。',
            ]],
        ];
    }
}
