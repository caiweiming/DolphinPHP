<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\static;

use app\common\render\Form;
use app\common\render\form\items\static\StaticText;
use app\common\render\form\items\text\Text;

/**
 * static 能力块构建器
 */
final class StaticSectionBuilder
{
    public function build(array $component, array $section): array
    {
        $key = (string) ($section['key'] ?? 'basic');

        return match ($key) {
            'basic' => $this->basic($section),
            'send' => $this->send($section),
            'default_value' => $this->defaultValue($section),
            'raw' => $this->raw($section),
            'relation_info' => $this->relationInfo($section),
            'compare_hidden' => $this->compareHidden($section),
            'profile_form' => $this->profile($section),
            default => $this->basic($section),
        };
    }

    private function basic(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => 'value', 'value' => '展示只读文本，不允许用户编辑']],
            <<<'CODE'
[
    [
        'type' => 'static',
        'name' => 'order_no',
        'label' => '订单号',
        'value' => 'SO20260602001',
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::static('order_no', '订单号')
    ->value('SO20260602001');
CODE,
            ['static 默认只负责展示，不会随表单再次提交。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_static_basic_', false), '基础静态展示')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        StaticText::make('order_no', '订单号')
                            ->value('SO20260602001')
                    )
                    ->fetch();
            }
        );
    }

    private function send(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => 'send(true)', 'value' => '显示只读值的同时继续提交原值']],
            <<<'CODE'
[
    [
        'type' => 'static',
        'name' => 'username',
        'label' => '用户名',
        'value' => 'showcase-admin',
        'send' => true,
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::static('username', '用户名')
    ->value('showcase-admin')
    ->send();
CODE,
            ['send 适合“用户可见但不可改，同时后端还需要拿到这个值”的场景。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_static_send_', false), 'send 提交数据')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        StaticText::make('username', '用户名')
                            ->value('showcase-admin')
                            ->send()
                    )
                    ->fetch();
            }
        );
    }

    private function defaultValue(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => '编辑态回填', 'value' => '通过 value 展示已有记录值']],
            <<<'CODE'
[
    [
        'type' => 'static',
        'name' => 'created_at',
        'label' => '创建时间',
        'value' => '2026-06-02 10:30:00',
        'tips' => '系统自动生成',
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::static('created_at', '创建时间')
    ->value('2026-06-02 10:30:00')
    ->tips('系统自动生成');
CODE,
            ['只读字段回填常用于创建时间、创建人、系统编号等不可编辑数据。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_static_default_', false), '默认值与回填')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        StaticText::make('created_at', '创建时间')
                            ->value('2026-06-02 10:30:00')
                            ->tips('系统自动生成')
                    )
                    ->fetch();
            }
        );
    }

    private function raw(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => 'raw(true)', 'value' => '允许把 value 当作 HTML 渲染']],
            <<<'CODE'
[
    [
        'type' => 'static',
        'name' => 'status',
        'label' => '审核状态',
        'value' => '<span class="badge bg-success-lt">已通过</span>',
        'raw' => true,
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::static('status', '审核状态')
    ->value('<span class="badge bg-success-lt">已通过</span>')
    ->raw();
CODE,
            ['raw 只适合展示已知安全的 HTML 片段，不要直接拼接未经处理的用户输入。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_static_raw_', false), 'HTML 内容 raw')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        StaticText::make('status', '审核状态')
                            ->value('<span class="badge bg-success-lt">已通过</span>')
                            ->raw()
                    )
                    ->fetch();
            }
        );
    }

    private function relationInfo(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => '关联摘要', 'value' => '适合展示部门、角色、来源等关联只读值']],
            <<<'CODE'
[
    [
        'type' => 'static',
        'name' => 'department',
        'label' => '所属部门',
        'value' => '产品研发中心 / 平台架构组',
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::static('department', '所属部门')
    ->value('产品研发中心 / 平台架构组');
CODE,
            ['如果只是展示一段关联摘要，用 static 通常比 html 更稳妥。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_static_relation_', false), '关联信息展示')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        StaticText::make('department', '所属部门')
                            ->value('产品研发中心 / 平台架构组')
                    )
                    ->fetch();
            }
        );
    }

    private function compareHidden(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => '选型说明', 'value' => 'visible but readonly vs invisible but submitted']],
            <<<'CODE'
[
    [
        'type' => 'static',
        'name' => 'order_owner',
        'label' => '处理人',
        'value' => 'showcase-admin',
        'tips' => '需要展示给用户看时用 static；完全不显示、只提交时用 hidden',
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::static('order_owner', '处理人')
    ->value('showcase-admin')
    ->tips('需要展示给用户看时用 static；完全不显示、只提交时用 hidden');
CODE,
            ['很多误用都来自于这两个组件边界不清，这里直接在 static 本体旁边把差异说透。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_static_compare_', false), '与 hidden 的区别')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        StaticText::make('order_owner', '处理人')
                            ->value('showcase-admin')
                            ->tips('需要展示给用户看时用 static；完全不显示、只提交时用 hidden')
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
                ['name' => 'order_no / amount', 'value' => '订单只读字段'],
                ['name' => 'remark', 'value' => '真实可编辑字段'],
            ],
            <<<'CODE'
[
    ['type' => 'static', 'name' => 'order_no', 'label' => '订单号', 'value' => 'SO20260602001'],
    ['type' => 'static', 'name' => 'amount', 'label' => '订单金额', 'value' => '¥ 1,299.00'],
    ['text', 'remark', '备注', '请输入处理备注'],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::static('order_no', '订单号')
    ->value('SO20260602001');

Field::static('amount', '订单金额')
    ->value('¥ 1,299.00');

Field::text('remark', '备注', '请输入处理备注');
CODE,
            ['这是订单、工单、审核单等详情编辑页非常常见的组合方式。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_static_profile_', false), '业务表单片段')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(StaticText::make('order_no', '订单号')->value('SO20260602001'))
                    ->item(StaticText::make('amount', '订单金额')->value('¥ 1,299.00'))
                    ->item(Text::make('remark', '备注', '请输入处理备注'))
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
                'path' => 'app/showcase/service/components/form/static/StaticSectionBuilder.php',
                'label' => 'static 能力块',
                'description' => '按 section key 组装 static 的完整示例能力块。',
            ]],
        ];
    }
}
