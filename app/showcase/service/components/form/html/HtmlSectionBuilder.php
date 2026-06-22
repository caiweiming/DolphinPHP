<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\html;

use app\common\render\Form;
use app\common\render\form\items\html\Html;
use app\common\render\form\items\password\Password;
use app\common\render\form\items\text\Text;

/**
 * html 能力块构建器
 */
final class HtmlSectionBuilder
{
    public function build(array $component, array $section): array
    {
        $key = (string) ($section['key'] ?? 'basic');

        return match ($key) {
            'basic' => $this->basic($section),
            'list' => $this->list($section),
            'card' => $this->card($section),
            'danger' => $this->danger($section),
            'status' => $this->status($section),
            'compare_static' => $this->compareStatic($section),
            'profile_form' => $this->profile($section),
            default => $this->basic($section),
        };
    }

    private function basic(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => 'value', 'value' => '直接输出 alert 等 HTML 片段']],
            <<<'CODE'
[
    [
        'type' => 'html',
        'name' => 'notice',
        'label' => '提示',
        'value' => '<div class="alert alert-info mb-0">请先阅读配置说明再提交表单</div>',
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::html('notice', '提示')
    ->value('<div class="alert alert-info mb-0">请先阅读配置说明再提交表单</div>');
CODE,
            ['html 组件本质是“在表单里插入一块原样输出的 HTML”。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_html_basic_', false), '基础提示内容')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Html::make('notice', '提示')
                            ->value('<div class="alert alert-info mb-0">请先阅读配置说明再提交表单</div>')
                    )
                    ->fetch();
            }
        );
    }

    private function list(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => '有序/无序列表', 'value' => '适合展示操作步骤和填写规则']],
            <<<'CODE'
[
    [
        'type' => 'html',
        'name' => 'steps',
        'label' => '操作说明',
        'value' => '<ol class="mb-0 ps-3"><li>确认基础配置</li><li>保存草稿</li><li>完成发布</li></ol>',
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::html('steps', '操作说明')
    ->value('<ol class="mb-0 ps-3"><li>确认基础配置</li><li>保存草稿</li><li>完成发布</li></ol>');
CODE,
            ['当说明有明显顺序关系时，html 比多行 tips 更清晰。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_html_list_', false), '说明列表')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Html::make('steps', '操作说明')
                            ->value('<ol class="mb-0 ps-3"><li>确认基础配置</li><li>保存草稿</li><li>完成发布</li></ol>')
                    )
                    ->fetch();
            }
        );
    }

    private function card(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => '卡片结构', 'value' => '适合展示关联信息、用户摘要等']],
            <<<'CODE'
[
    [
        'type' => 'html',
        'name' => 'user_card',
        'label' => '用户信息',
        'value' => '<div class="card card-sm"><div class="card-body"><div class="fw-bold">张三</div><div class="text-secondary">zhangsan@example.com</div></div></div>',
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::html('user_card', '用户信息')
    ->value('<div class="card card-sm"><div class="card-body"><div class="fw-bold">张三</div><div class="text-secondary">zhangsan@example.com</div></div></div>');
CODE,
            ['卡片型 html 适合在危险操作或审核流程里补充上下文摘要。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_html_card_', false), '信息卡片')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Html::make('user_card', '用户信息')
                            ->value('<div class="card card-sm"><div class="card-body"><div class="fw-bold">张三</div><div class="text-secondary">zhangsan@example.com</div></div></div>')
                    )
                    ->fetch();
            }
        );
    }

    private function danger(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => 'alert-danger', 'value' => '适合删除、重置等不可逆操作提醒']],
            <<<'CODE'
[
    [
        'type' => 'html',
        'name' => 'danger_notice',
        'label' => '危险操作',
        'value' => '<div class="alert alert-danger mb-0"><strong>注意：</strong>该操作不可撤销，请确认后继续。</div>',
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::html('danger_notice', '危险操作')
    ->value('<div class="alert alert-danger mb-0"><strong>注意：</strong>该操作不可撤销，请确认后继续。</div>');
CODE,
            ['危险提示区域通常应放在真正的确认输入项前面。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_html_danger_', false), '危险操作警告')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Html::make('danger_notice', '危险操作')
                            ->value('<div class="alert alert-danger mb-0"><strong>注意：</strong>该操作不可撤销，请确认后继续。</div>')
                    )
                    ->fetch();
            }
        );
    }

    private function status(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => 'badge / icon', 'value' => '适合展示审核状态、发布状态等']],
            <<<'CODE'
[
    [
        'type' => 'html',
        'name' => 'publish_status',
        'label' => '发布状态',
        'value' => '<span class="badge bg-success-lt"><i class="ti ti-check me-1"></i>已发布</span>',
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::html('publish_status', '发布状态')
    ->value('<span class="badge bg-success-lt"><i class="ti ti-check me-1"></i>已发布</span>');
CODE,
            ['html 输出 badge 时更像“说明区块”，不适合作为真正的数据输入源。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_html_status_', false), '图标与状态')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Html::make('publish_status', '发布状态')
                            ->value('<span class="badge bg-success-lt"><i class="ti ti-check me-1"></i>已发布</span>')
                    )
                    ->fetch();
            }
        );
    }

    private function compareStatic(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => '选型说明', 'value' => '复杂结构用 html，字段值展示用 static']],
            <<<'CODE'
[
    [
        'type' => 'html',
        'name' => 'compare_notice',
        'label' => '选型建议',
        'value' => '<div class="alert alert-info mb-0">复杂说明区、卡片区请用 <code>html</code>；单个字段的只读值展示请优先用 <code>static</code>。</div>',
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::html('compare_notice', '选型建议')
    ->value('<div class="alert alert-info mb-0">复杂说明区、卡片区请用 <code>html</code>；单个字段的只读值展示请优先用 <code>static</code>。</div>');
CODE,
            ['这个区块是为了防止开发者把 html 当成 static 的替代品，所以也应该由 html 本体来承载说明。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_html_compare_', false), '与 static 的区别')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Html::make('compare_notice', '选型建议')
                            ->value('<div class="alert alert-info mb-0">复杂说明区、卡片区请用 <code>html</code>；单个字段的只读值展示请优先用 <code>static</code>。</div>')
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
                ['name' => 'user_card / password', 'value' => '危险操作确认前展示上下文并要求输入确认值'],
            ],
            <<<'CODE'
[
    [
        'type' => 'html',
        'name' => 'user_card',
        'label' => '当前用户',
        'value' => '<div class="alert alert-warning mb-0"><strong>用户名：</strong>showcase-admin<br><strong>角色：</strong>超级管理员</div>',
    ],
    [
        'type' => 'password',
        'name' => 'confirm_password',
        'label' => '确认密码',
        'tips' => '请输入当前密码确认本次操作',
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::html('user_card', '当前用户')
    ->value('<div class="alert alert-warning mb-0"><strong>用户名：</strong>showcase-admin<br><strong>角色：</strong>超级管理员</div>');

Field::password('confirm_password', '确认密码')
    ->tips('请输入当前密码确认本次操作');
CODE,
            ['这是后台危险操作确认页里非常常见的一种组合。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_html_profile_', false), '业务表单片段')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Html::make('user_card', '当前用户')
                            ->value('<div class="alert alert-warning mb-0"><strong>用户名：</strong>showcase-admin<br><strong>角色：</strong>超级管理员</div>')
                    )
                    ->item(
                        Password::make('confirm_password', '确认密码')
                            ->tips('请输入当前密码确认本次操作')
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
                'path' => 'app/showcase/service/components/form/html/HtmlSectionBuilder.php',
                'label' => 'html 能力块',
                'description' => '按 section key 组装 html 的完整示例能力块。',
            ]],
        ];
    }
}
