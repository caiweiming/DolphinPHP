<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\transfer;

use app\common\render\Form;
use app\common\render\form\items\text\Text;
use form\transfer\Transfer;

/**
 * transfer 能力块构建器
 */
final class TransferSectionBuilder
{
    public function build(array $component, array $section): array
    {
        $key = (string) ($section['key'] ?? 'basic');

        return match ($key) {
            'basic' => $this->basic($section),
            'default_value' => $this->defaultValue($section),
            'disabled' => $this->disabled($section),
            'props' => $this->props($section),
            'notice' => $this->notice($section),
            'extension_usage' => $this->extensionUsage($section),
            'profile_form' => $this->profile($section),
            default => $this->basic($section),
        };
    }

    private function basic(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => 'options', 'value' => '使用 value => label 配置双栏选项集合']],
            <<<'CODE'
[
    [
        'type' => 'transfer',
        'name' => 'role_keys',
        'label' => '角色分配',
        'options' => [
            'admin' => '管理员',
            'editor' => '编辑',
            'auditor' => '审核员',
            'guest' => '访客',
        ],
    ],
]
CODE,
            <<<'CODE'
use form\transfer\Transfer;

$this->form->item(
    Transfer::make('role_keys', '角色分配')
        ->options([
            'admin' => '管理员',
            'editor' => '编辑',
            'auditor' => '审核员',
            'guest' => '访客',
        ])
);
CODE,
            ['基础场景最适合做角色分配、白名单、标签授权这类中小规模静态选项。'],
            function (): string {
                Form::clearInstances();

                return Form::make(uniqid('showcase_transfer_basic_', false), '基础穿梭框')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Transfer::make('role_keys', '角色分配')
                            ->options([
                                'admin' => '管理员',
                                'editor' => '编辑',
                                'auditor' => '审核员',
                                'guest' => '访客',
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
            [['name' => 'value', 'value' => '默认选中值建议始终传数组']],
            <<<'CODE'
[
    [
        'type' => 'transfer',
        'name' => 'notify_roles',
        'label' => '通知角色',
        'value' => ['editor', 'auditor'],
        'options' => [
            'admin' => '管理员',
            'editor' => '编辑',
            'auditor' => '审核员',
            'guest' => '访客',
        ],
    ],
]
CODE,
            <<<'CODE'
use form\transfer\Transfer;

$this->form->item(
    Transfer::make('notify_roles', '通知角色')
        ->value(['editor', 'auditor'])
        ->options([
            'admin' => '管理员',
            'editor' => '编辑',
            'auditor' => '审核员',
            'guest' => '访客',
        ])
);
CODE,
            ['编辑态回填时最关键的是 value 与 options 的 key 保持一致。'],
            function (): string {
                Form::clearInstances();

                return Form::make(uniqid('showcase_transfer_value_', false), '默认值与回填')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Transfer::make('notify_roles', '通知角色')
                            ->value(['editor', 'auditor'])
                            ->options([
                                'admin' => '管理员',
                                'editor' => '编辑',
                                'auditor' => '审核员',
                                'guest' => '访客',
                            ])
                    )
                    ->fetch();
            }
        );
    }

    private function disabled(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => 'disabled', 'value' => '支持禁用指定项，按 options 的 key 生效']],
            <<<'CODE'
[
    [
        'type' => 'transfer',
        'name' => 'review_roles',
        'label' => '审核角色',
        'disabled' => ['guest'],
        'options' => [
            'admin' => '管理员',
            'editor' => '编辑',
            'auditor' => '审核员',
            'guest' => '访客',
        ],
    ],
]
CODE,
            <<<'CODE'
use form\transfer\Transfer;

$this->form->item(
    Transfer::make('review_roles', '审核角色')
        ->disabled(['guest'])
        ->options([
            'admin' => '管理员',
            'editor' => '编辑',
            'auditor' => '审核员',
            'guest' => '访客',
        ])
);
CODE,
            ['禁用指定项适合“已下线角色不可再分配”这类后台治理场景。'],
            function (): string {
                Form::clearInstances();

                return Form::make(uniqid('showcase_transfer_disabled_', false), '禁用指定选项')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Transfer::make('review_roles', '审核角色')
                            ->disabled(['guest'])
                            ->options([
                                'admin' => '管理员',
                                'editor' => '编辑',
                                'auditor' => '审核员',
                                'guest' => '访客',
                            ])
                    )
                    ->fetch();
            }
        );
    }

    private function props(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => 'props', 'value' => '透传双栏标题、筛选、移动方式等前端插件参数']],
            <<<'CODE'
[
    [
        'type' => 'transfer',
        'name' => 'city_keys',
        'label' => '城市白名单',
        'props' => [
            'nonSelectedListLabel' => '待选择城市',
            'selectedListLabel' => '已加入白名单',
            'moveOnSelect' => false,
        ],
        'options' => [
            'gz' => '广州',
            'sz' => '深圳',
            'sh' => '上海',
            'hz' => '杭州',
        ],
    ],
]
CODE,
            <<<'CODE'
use form\transfer\Transfer;

$this->form->item(
    Transfer::make('city_keys', '城市白名单')
        ->props([
            'nonSelectedListLabel' => '待选择城市',
            'selectedListLabel' => '已加入白名单',
            'moveOnSelect' => false,
        ])
        ->options([
            'gz' => '广州',
            'sz' => '深圳',
            'sh' => '上海',
            'hz' => '杭州',
        ])
);
CODE,
            ['props 是 transfer 最值得直接展示给开发者的一层，因为它决定了双栏的真实交互体验。'],
            function (): string {
                Form::clearInstances();

                return Form::make(uniqid('showcase_transfer_props_', false), '透传 props 配置')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Transfer::make('city_keys', '城市白名单')
                            ->props([
                                'nonSelectedListLabel' => '待选择城市',
                                'selectedListLabel' => '已加入白名单',
                                'moveOnSelect' => false,
                            ])
                            ->options([
                                'gz' => '广州',
                                'sz' => '深圳',
                                'sh' => '上海',
                                'hz' => '杭州',
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
                ['name' => '规模建议', 'value' => '更适合中小规模静态选项，不适合超大规模远程搜索数据'],
                ['name' => '选型边界', 'value' => '如果需要远程搜索、海量候选项或多级筛选，优先考虑 select2 / linkage'],
            ],
            <<<'CODE'
[
    [
        'type' => 'transfer',
        'name' => 'dept_keys',
        'label' => '可用部门',
        'value' => ['product', 'ops'],
        'options' => [
            'product' => '产品部',
            'tech' => '技术部',
            'ops' => '运营部',
            'finance' => '财务部',
        ],
        'props' => [
            'nonSelectedListLabel' => '候选部门',
            'selectedListLabel' => '已选部门',
        ],
        'tips' => 'transfer 更适合中小规模静态选项；如果选项量很大，请优先考虑 select2 或分步选择。',
    ],
]
CODE,
            <<<'CODE'
use form\transfer\Transfer;

$this->form->item(
    Transfer::make('dept_keys', '可用部门')
        ->value(['product', 'ops'])
        ->props([
            'nonSelectedListLabel' => '候选部门',
            'selectedListLabel' => '已选部门',
        ])
        ->options([
            'product' => '产品部',
            'tech' => '技术部',
            'ops' => '运营部',
            'finance' => '财务部',
        ])
        ->tips('transfer 更适合中小规模静态选项；如果选项量很大，请优先考虑 select2 或分步选择。')
);
CODE,
            ['这个板块主要回答两个问题：transfer 适合多大规模的静态选项，以及什么时候该改用 select2 或 linkage。'],
            function (): string {
                Form::clearInstances();

                return Form::make(uniqid('showcase_transfer_notice_', false), '选项规模与使用提示')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Transfer::make('dept_keys', '可用部门')
                            ->value(['product', 'ops'])
                            ->props([
                                'nonSelectedListLabel' => '候选部门',
                                'selectedListLabel' => '已选部门',
                            ])
                            ->options([
                                'product' => '产品部',
                                'tech' => '技术部',
                                'ops' => '运营部',
                                'finance' => '财务部',
                            ])
                            ->tips('transfer 更适合中小规模静态选项；如果选项量很大，请优先考虑 select2 或分步选择。')
                    )
                    ->fetch();
            }
        );
    }

    private function extensionUsage(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => '扩展项门面', 'value' => '当前通过 form\\transfer\\Transfer 接入，而不是 Field::transfer']],
            <<<'CODE'
[
    [
        'type' => 'transfer',
        'name' => 'role_keys',
        'label' => '角色分配',
        'options' => [
            'admin' => '管理员',
            'editor' => '编辑',
        ],
        'tips' => 'transfer 当前是扩展项，建议通过 form\\transfer\\Transfer::make() 接入，而不是使用 Field::transfer()。',
    ],
]
CODE,
            <<<'CODE'
use form\transfer\Transfer;

$this->form->item(
    Transfer::make('role_keys', '角色分配')
        ->options([
            'admin' => '管理员',
            'editor' => '编辑',
        ])
        ->tips('transfer 当前是扩展项，建议通过 form\\transfer\\Transfer::make() 接入，而不是使用 Field::transfer()。')
);
CODE,
            ['这里重点强调接入入口，避免开发者误以为 transfer 已经有内置的 Field 门面。'],
            function (): string {
                Form::clearInstances();

                return Form::make(uniqid('showcase_transfer_extension_', false), '扩展项接入方式')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Transfer::make('role_keys', '角色分配')
                            ->options([
                                'admin' => '管理员',
                                'editor' => '编辑',
                            ])
                            ->tips('transfer 当前是扩展项，建议通过 form\\transfer\\Transfer::make() 接入，而不是使用 Field::transfer()。')
                    )
                    ->fetch();
            }
        );
    }

    private function profile(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => 'role_group_name / role_keys', 'value' => '后台角色包和角色分配的真实业务场景']],
            <<<'CODE'
[
    ['type' => 'text', 'name' => 'role_group_name', 'label' => '角色组名称', 'tips' => '请输入角色组名称'],
    [
        'type' => 'transfer',
        'name' => 'role_keys',
        'label' => '角色权限分配',
        'value' => ['editor', 'auditor'],
        'props' => [
            'nonSelectedListLabel' => '待分配角色',
            'selectedListLabel' => '已分配角色',
        ],
        'options' => [
            'admin' => '管理员',
            'editor' => '编辑',
            'auditor' => '审核员',
            'guest' => '访客',
        ],
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;
use form\transfer\Transfer;

Field::text('role_group_name', '角色组名称', '请输入角色组名称');

$this->form->item(
    Transfer::make('role_keys', '角色权限分配')
        ->value(['editor', 'auditor'])
        ->props([
            'nonSelectedListLabel' => '待分配角色',
            'selectedListLabel' => '已分配角色',
        ])
        ->options([
            'admin' => '管理员',
            'editor' => '编辑',
            'auditor' => '审核员',
            'guest' => '访客',
        ])
);
CODE,
            ['这类角色包、白名单包、标签授权包，是最适合开发者直接照抄的 transfer 业务示例。'],
            function (): string {
                Form::clearInstances();

                return Form::make(uniqid('showcase_transfer_profile_', false), '业务表单片段')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(Text::make('role_group_name', '角色组名称', '请输入角色组名称'))
                    ->item(
                        Transfer::make('role_keys', '角色权限分配')
                            ->value(['editor', 'auditor'])
                            ->props([
                                'nonSelectedListLabel' => '待分配角色',
                                'selectedListLabel' => '已分配角色',
                            ])
                            ->options([
                                'admin' => '管理员',
                                'editor' => '编辑',
                                'auditor' => '审核员',
                                'guest' => '访客',
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
                    'path' => 'app/showcase/service/components/form/transfer/TransferSectionBuilder.php',
                    'label' => 'transfer 能力块',
                    'description' => '按 section key 组装 transfer 的完整示例能力块。',
                ],
                [
                    'path' => 'extend/form/transfer/Transfer.php',
                    'label' => '扩展项门面类',
                    'description' => 'transfer 的独立门面入口，供业务表单直接调用。',
                ],
                [
                    'path' => 'extend/form/transfer/item.html',
                    'label' => '扩展项模板',
                    'description' => '双栏穿梭框的实际模板输出。',
                ],
            ],
        ];
    }
}
