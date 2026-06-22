<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\extensions;

use app\common\render\Form;
use app\common\render\form\items\text\Text;
use form\select_table\SelectTable;

/**
 * select_table 能力块构建器
 */
final class SelectTableSectionBuilder
{
    private const POPUP_URL = "showcase/admin.demo_api/selectTablePopup";

    public function build(array $component, array $section): array
    {
        $key = (string) ($section['key'] ?? 'basic');

        return match ($key) {
            'basic' => $this->basic($section),
            'default_value' => $this->defaultValue($section),
            'single' => $this->single($section),
            'limit' => $this->limit($section),
            'extra_fields' => $this->extraFields($section),
            'popup' => $this->popup($section),
            'profile_form' => $this->profileForm($section),
            default => $this->basic($section),
        };
    }

    private function basic(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => 'popup.url / popup.table_id', 'value' => '最小可用选表器必须明确弹窗地址和弹窗表格 ID']],
            <<<'CODE'
[
    [
        'type' => 'select_table',
        'name' => 'members',
        'label' => '成员选择',
        'options' => [
            'columns' => [
                ['key' => 'id', 'title' => 'ID'],
                ['key' => 'nickname', 'title' => '昵称'],
                ['key' => 'mobile', 'title' => '手机号'],
            ],
            'popup' => [
                'url' => (string) dp_url('showcase/admin.demo_api/selectTablePopup', ['dataset' => 'members']),
                'table_id' => 'showcase_select_table_members',
            ],
        ],
    ],
]
CODE,
            <<<'CODE'
use form\select_table\SelectTable;

$this->form->item(
    SelectTable::make('members', '成员选择')
        ->columns([
            ['key' => 'id', 'title' => 'ID'],
            ['key' => 'nickname', 'title' => '昵称'],
            ['key' => 'mobile', 'title' => '手机号'],
        ])
        ->popupUrl((string) dp_url('showcase/admin.demo_api/selectTablePopup', ['dataset' => 'members']))
        ->popupTableId('showcase_select_table_members')
);
CODE,
            ['最关键的是先让开发者看见 popup.url 和 popup.table_id 这两个必需配置。'],
            function (): string {
                Form::clearInstances();

                return Form::make(uniqid('showcase_select_table_basic_', false), '基础选表')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        SelectTable::make('members', '成员选择')
                            ->columns($this->baseColumns())
                            ->popupUrl($this->popupUrl())
                            ->popupTableId('showcase_select_table_members')
                    )
                    ->fetch();
            }
        );
    }

    private function defaultValue(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => 'value', 'value' => '二维数组回显已选成员']],
            <<<'CODE'
[
    [
        'type' => 'select_table',
        'name' => 'members',
        'label' => '成员选择',
        'value' => [
            ['id' => 1, 'nickname' => '张三', 'mobile' => '13800000001'],
            ['id' => 2, 'nickname' => '李四', 'mobile' => '13800000002'],
        ],
        'options' => [
            'columns' => [
                ['key' => 'id', 'title' => 'ID'],
                ['key' => 'nickname', 'title' => '昵称'],
                ['key' => 'mobile', 'title' => '手机号'],
            ],
            'popup' => [
                'url' => (string) dp_url('showcase/admin.demo_api/selectTablePopup', ['dataset' => 'members']),
                'table_id' => 'showcase_select_table_members',
            ],
        ],
    ],
]
CODE,
            <<<'CODE'
use form\select_table\SelectTable;

$this->form->item(
    SelectTable::make('members', '成员选择')
        ->value([
            ['id' => 1, 'nickname' => '张三', 'mobile' => '13800000001'],
            ['id' => 2, 'nickname' => '李四', 'mobile' => '13800000002'],
        ])
        ->columns([
            ['key' => 'id', 'title' => 'ID'],
            ['key' => 'nickname', 'title' => '昵称'],
            ['key' => 'mobile', 'title' => '手机号'],
        ])
        ->popupUrl((string) dp_url('showcase/admin.demo_api/selectTablePopup', ['dataset' => 'members']))
        ->popupTableId('showcase_select_table_members');
CODE,
            ['默认值与回填的重点是 value 里的字段键名要覆盖当前 columns 和 extra_fields。'],
            function (): string {
                Form::clearInstances();

                return Form::make(uniqid('showcase_select_table_value_', false), '默认值与回填')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        SelectTable::make('members', '成员选择')
                            ->value([
                                ['id' => 1, 'nickname' => '张三', 'mobile' => '13800000001'],
                                ['id' => 2, 'nickname' => '李四', 'mobile' => '13800000002'],
                            ])
                            ->columns($this->baseColumns())
                            ->popupUrl($this->popupUrl())
                            ->popupTableId('showcase_select_table_members')
                    )
                    ->fetch();
            }
        );
    }

    private function single(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => 'selection_mode', 'value' => 'single 表示只能回填一条数据']],
            <<<'CODE'
[
    [
        'type' => 'select_table',
        'name' => 'owner',
        'label' => '负责人',
        'options' => [
            'selection_mode' => 'single',
            'columns' => [
                ['key' => 'id', 'title' => 'ID'],
                ['key' => 'nickname', 'title' => '昵称'],
            ],
            'popup' => [
                'url' => (string) dp_url('showcase/admin.demo_api/selectTablePopup', ['dataset' => 'members']),
                'table_id' => 'showcase_select_table_members',
            ],
        ],
    ],
]
CODE,
            <<<'CODE'
use form\select_table\SelectTable;

$this->form->item(
    SelectTable::make('owner', '负责人')
        ->columns([
            ['key' => 'id', 'title' => 'ID'],
            ['key' => 'nickname', 'title' => '昵称'],
        ])
        ->selectionMode('single')
        ->popupUrl((string) dp_url('showcase/admin.demo_api/selectTablePopup', ['dataset' => 'members']))
        ->popupTableId('showcase_select_table_members')
);
CODE,
            ['负责人、主联系人这类场景，单选模式比普通多选更符合真实业务语义。'],
            function (): string {
                Form::clearInstances();

                return Form::make(uniqid('showcase_select_table_single_', false), '单选模式')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        SelectTable::make('owner', '负责人')
                            ->columns([
                                ['key' => 'id', 'title' => 'ID'],
                                ['key' => 'nickname', 'title' => '昵称'],
                            ])
                            ->selectionMode('single')
                            ->popupUrl($this->popupUrl())
                            ->popupTableId('showcase_select_table_members')
                    )
                    ->fetch();
            }
        );
    }

    private function limit(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => 'select_limit', 'value' => '限制最多保留条数']],
            <<<'CODE'
[
    [
        'type' => 'select_table',
        'name' => 'members',
        'label' => '成员选择',
        'options' => [
            'select_limit' => 2,
            'columns' => [
                ['key' => 'id', 'title' => 'ID'],
                ['key' => 'nickname', 'title' => '昵称'],
            ],
            'popup' => [
                'url' => (string) dp_url('showcase/admin.demo_api/selectTablePopup', ['dataset' => 'members']),
                'table_id' => 'showcase_select_table_members',
            ],
        ],
    ],
]
CODE,
            <<<'CODE'
use form\select_table\SelectTable;

$this->form->item(
    SelectTable::make('members', '成员选择')
        ->columns([
            ['key' => 'id', 'title' => 'ID'],
            ['key' => 'nickname', 'title' => '昵称'],
        ])
        ->attr('select_limit', 2)
        ->popupUrl((string) dp_url('showcase/admin.demo_api/selectTablePopup', ['dataset' => 'members']))
        ->popupTableId('showcase_select_table_members')
);
CODE,
            ['条数限制适合“最多选择 2 个负责人”“最多配置 3 个推荐人”这类业务场景。'],
            function (): string {
                Form::clearInstances();

                return Form::make(uniqid('showcase_select_table_limit_', false), '条数限制')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item([
                        'type' => 'select_table',
                        'name' => 'members_limit',
                        'label' => '成员选择',
                        'options' => [
                            'select_limit' => 2,
                            'columns' => [
                                ['key' => 'id', 'title' => 'ID'],
                                ['key' => 'nickname', 'title' => '昵称'],
                            ],
                            'popup' => [
                                'url' => $this->popupUrl(),
                                'table_id' => 'showcase_select_table_members',
                            ],
                        ],
                    ])
                    ->fetch();
            }
        );
    }

    private function extraFields(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => 'extra_fields', 'value' => '追加隐藏提交字段，例如状态、部门']],
            <<<'CODE'
[
    [
        'type' => 'select_table',
        'name' => 'members',
        'label' => '成员选择',
        'options' => [
            'extra_fields' => ['status', 'department'],
            'columns' => [
                ['key' => 'id', 'title' => 'ID'],
                ['key' => 'nickname', 'title' => '昵称'],
            ],
            'popup' => [
                'url' => (string) dp_url('showcase/admin.demo_api/selectTablePopup', ['dataset' => 'members']),
                'table_id' => 'showcase_select_table_members',
            ],
        ],
    ],
]
CODE,
            <<<'CODE'
use form\select_table\SelectTable;

$this->form->item(
    SelectTable::make('members', '成员选择')
        ->columns([
            ['key' => 'id', 'title' => 'ID'],
            ['key' => 'nickname', 'title' => '昵称'],
        ])
        ->extraFields(['status', 'department'])
        ->popupUrl((string) dp_url('showcase/admin.demo_api/selectTablePopup', ['dataset' => 'members']))
        ->popupTableId('showcase_select_table_members')
);
CODE,
            ['extra_fields 适合“展示少量字段，但提交时需要带回更多上下文字段”的场景。'],
            function (): string {
                Form::clearInstances();

                return Form::make(uniqid('showcase_select_table_extra_', false), 'extra_fields 附加字段')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        SelectTable::make('members', '成员选择')
                            ->columns([
                                ['key' => 'id', 'title' => 'ID'],
                                ['key' => 'nickname', 'title' => '昵称'],
                            ])
                            ->extraFields(['status', 'department'])
                            ->popupUrl($this->popupUrl())
                            ->popupTableId('showcase_select_table_members')
                    )
                    ->fetch();
            }
        );
    }

    private function popup(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => 'popup.title / width / height / table_id', 'value' => '控制弹窗展示参数和读取哪个表格实例']],
            <<<'CODE'
[
    [
        'type' => 'select_table',
        'name' => 'members',
        'label' => '成员选择',
        'options' => [
            'columns' => [
                ['key' => 'id', 'title' => 'ID'],
                ['key' => 'nickname', 'title' => '昵称'],
            ],
            'popup' => [
                'url' => (string) dp_url('showcase/admin.demo_api/selectTablePopup', ['dataset' => 'members']),
                'title' => '选择成员',
                'width' => '1100px',
                'height' => '680px',
                'table_id' => 'showcase_select_table_members',
            ],
        ],
    ],
]
CODE,
            <<<'CODE'
use form\select_table\SelectTable;

$this->form->item(
    SelectTable::make('members', '成员选择')
        ->columns([
            ['key' => 'id', 'title' => 'ID'],
            ['key' => 'nickname', 'title' => '昵称'],
        ])
        ->popup([
            'url' => (string) dp_url('showcase/admin.demo_api/selectTablePopup', ['dataset' => 'members']),
            'title' => '选择成员',
            'width' => '1100px',
            'height' => '680px',
            'table_id' => 'showcase_select_table_members',
        ])
);
CODE,
            ['popup.table_id 是很多人最容易漏掉的配置，这里必须直接给出真实写法。'],
            function (): string {
                Form::clearInstances();

                return Form::make(uniqid('showcase_select_table_popup_', false), 'popup 参数')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        SelectTable::make('members', '成员选择')
                            ->columns([
                                ['key' => 'id', 'title' => 'ID'],
                                ['key' => 'nickname', 'title' => '昵称'],
                            ])
                            ->popup([
                                'url' => $this->popupUrl(),
                                'title' => '选择成员',
                                'width' => '1100px',
                                'height' => '680px',
                                'table_id' => 'showcase_select_table_members',
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
            [['name' => 'owner / members', 'value' => '业务中常见的负责人单选 + 协作成员多选组合']],
            <<<'CODE'
[
    ['type' => 'text', 'name' => 'project_name', 'label' => '项目名称', 'tips' => '请输入项目名称'],
    [
        'type' => 'select_table',
        'name' => 'owner',
        'label' => '负责人',
        'options' => [
            'selection_mode' => 'single',
            'columns' => [
                ['key' => 'id', 'title' => 'ID'],
                ['key' => 'nickname', 'title' => '昵称'],
            ],
            'popup' => [
                'url' => (string) dp_url('showcase/admin.demo_api/selectTablePopup', ['dataset' => 'members']),
                'table_id' => 'showcase_select_table_members',
            ],
        ],
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;
use form\select_table\SelectTable;

Field::text('project_name', '项目名称', '请输入项目名称');

$this->form->item(
    SelectTable::make('owner', '负责人')
        ->columns([
            ['key' => 'id', 'title' => 'ID'],
            ['key' => 'nickname', 'title' => '昵称'],
        ])
        ->selectionMode('single')
        ->popupUrl((string) dp_url('showcase/admin.demo_api/selectTablePopup', ['dataset' => 'members']))
        ->popupTableId('showcase_select_table_members')
);
CODE,
            ['这类“负责人/成员/协作人”配置，是 select_table 最适合让开发者直接照抄的业务片段。'],
            function (): string {
                Form::clearInstances();

                return Form::make(uniqid('showcase_select_table_profile_', false), '业务表单片段')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(Text::make('project_name', '项目名称', '请输入项目名称'))
                    ->item(
                        SelectTable::make('owner', '负责人')
                            ->columns([
                                ['key' => 'id', 'title' => 'ID'],
                                ['key' => 'nickname', 'title' => '昵称'],
                            ])
                            ->selectionMode('single')
                            ->popupUrl($this->popupUrl())
                            ->popupTableId('showcase_select_table_members')
                    )
                    ->fetch();
            }
        );
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function baseColumns(): array
    {
        return [
            ['key' => 'id', 'title' => 'ID'],
            ['key' => 'nickname', 'title' => '昵称'],
            ['key' => 'mobile', 'title' => '手机号'],
        ];
    }

    private function popupUrl(): string
    {
        return (string) dp_url(self::POPUP_URL, ['dataset' => 'members']);
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
                    'path' => 'app/showcase/service/components/form/extensions/SelectTableSectionBuilder.php',
                    'label' => 'select_table 能力块',
                    'description' => '按 section key 组装 select_table 的完整示例能力块。',
                ],
                [
                    'path' => 'extend/form/select_table/SelectTable.php',
                    'label' => '扩展项门面类',
                    'description' => 'select_table 的独立门面入口，供业务表单直接调用。',
                ],
                [
                    'path' => 'extend/form/select_table/Item.php',
                    'label' => '扩展项渲染器',
                    'description' => 'select_table 的真实渲染、配置标准化与提交协议。',
                ],
                [
                    'path' => 'app/showcase/controller/admin/DemoApi.php',
                    'label' => '真实表格渲染器弹窗页',
                    'description' => 'select_table 的弹窗页直接由 DemoApi::selectTablePopup() 输出真实 Table 渲染器页面。',
                ],
                [
                    'path' => 'app/showcase/view/demo_api/select_table_popup.html',
                    'label' => '弹窗页面模板',
                    'description' => '承载弹窗页后台布局与表格容器输出，负责把 Table 渲染结果放进标准页面壳。',
                ],
                [
                    'path' => 'public/extend/form/select_table/select_table.js',
                    'label' => '前端交互脚本',
                    'description' => '负责打开弹窗、读取表格勾选结果并回填到当前 select_table 字段。',
                ],
            ],
        ];
    }
}
