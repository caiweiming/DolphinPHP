<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\extensions;

use app\common\render\Form;
use app\common\render\form\items\text\Text;
use form\data_table\DataTable;

/**
 * data_table 能力块构建器
 */
final class DataTableSectionBuilder
{
    public function build(array $component, array $section): array
    {
        $key = (string) ($section['key'] ?? 'basic');

        return match ($key) {
            'basic' => $this->basic($section),
            'default_value' => $this->defaultValue($section),
            'assoc_columns' => $this->assocColumns($section),
            'validation' => $this->validation($section),
            'sortable_confirm' => $this->sortableConfirm($section),
            'cell_types' => $this->cellTypes($section),
            'profile_form' => $this->profileForm($section),
            default => $this->basic($section),
        };
    }

    private function basic(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => 'columns', 'value' => '列表写法声明列配置']],
            <<<'CODE'
[
    [
        'type' => 'data_table',
        'name' => 'items',
        'label' => '规格明细',
        'options' => [
            'columns' => [
                ['key' => 'title', 'title' => '标题', 'type' => 'text'],
                ['key' => 'price', 'title' => '售价', 'type' => 'number'],
                ['key' => 'status', 'title' => '启用', 'type' => 'switch'],
            ],
        ],
    ],
]
CODE,
            <<<'CODE'
use form\data_table\DataTable;

$this->form->item(
    DataTable::make('items', '规格明细')
        ->columns([
            ['key' => 'title', 'title' => '标题', 'type' => 'text'],
            ['key' => 'price', 'title' => '售价', 'type' => 'number'],
            ['key' => 'status', 'title' => '启用', 'type' => 'switch'],
        ])
);
CODE,
            ['基础 data_table 适合直接展示“一个字段内维护多行结构化数据”的最小形态。'],
            function (): string {
                Form::clearInstances();

                return Form::make(uniqid('showcase_data_table_basic_', false), '基础数据表格')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        DataTable::make('items', '规格明细')
                            ->columns([
                                ['key' => 'title', 'title' => '标题', 'type' => 'text'],
                                ['key' => 'price', 'title' => '售价', 'type' => 'number'],
                                ['key' => 'status', 'title' => '启用', 'type' => 'switch'],
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
            [['name' => 'value', 'value' => '二维数组回填已存在行数据']],
            <<<'CODE'
[
    [
        'type' => 'data_table',
        'name' => 'items',
        'label' => '费用明细',
        'value' => [
            ['title' => '基础服务费', 'price' => '99', 'status' => '1'],
            ['title' => '附加支持费', 'price' => '30', 'status' => '0'],
        ],
        'options' => [
            'columns' => [
                ['key' => 'title', 'title' => '项目', 'type' => 'text'],
                ['key' => 'price', 'title' => '金额', 'type' => 'number'],
                ['key' => 'status', 'title' => '启用', 'type' => 'switch'],
            ],
        ],
    ],
]
CODE,
            <<<'CODE'
use form\data_table\DataTable;

$this->form->item(
    DataTable::make('items', '费用明细')
        ->value([
            ['title' => '基础服务费', 'price' => '99', 'status' => '1'],
            ['title' => '附加支持费', 'price' => '30', 'status' => '0'],
        ])
        ->columns([
            ['key' => 'title', 'title' => '项目', 'type' => 'text'],
            ['key' => 'price', 'title' => '金额', 'type' => 'number'],
            ['key' => 'status', 'title' => '启用', 'type' => 'switch'],
        ])
);
CODE,
            ['data_table 的编辑态回显完全依赖二维数组里的键名和列 key 对齐。'],
            function (): string {
                Form::clearInstances();

                return Form::make(uniqid('showcase_data_table_value_', false), '默认值与回填')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        DataTable::make('items', '费用明细')
                            ->value([
                                ['title' => '基础服务费', 'price' => '99', 'status' => '1'],
                                ['title' => '附加支持费', 'price' => '30', 'status' => '0'],
                            ])
                            ->columns([
                                ['key' => 'title', 'title' => '项目', 'type' => 'text'],
                                ['key' => 'price', 'title' => '金额', 'type' => 'number'],
                                ['key' => 'status', 'title' => '启用', 'type' => 'switch'],
                            ])
                    )
                    ->fetch();
            }
        );
    }

    private function assocColumns(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => 'columns 关联写法', 'value' => '数组键自动作为列 key']],
            <<<'CODE'
[
    [
        'type' => 'data_table',
        'name' => 'rules',
        'label' => '规则明细',
        'options' => [
            'columns' => [
                'title' => ['title' => '规则名', 'type' => 'text'],
                'category' => ['title' => '分类', 'type' => 'select', 'options' => ['base' => '基础', 'vip' => '会员']],
                'sort' => ['title' => '排序', 'type' => 'number'],
            ],
        ],
    ],
]
CODE,
            <<<'CODE'
use form\data_table\DataTable;

$this->form->item(
    DataTable::make('rules', '规则明细')
        ->columns([
            'title' => ['title' => '规则名', 'type' => 'text'],
            'category' => ['title' => '分类', 'type' => 'select', 'options' => ['base' => '基础', 'vip' => '会员']],
            'sort' => ['title' => '排序', 'type' => 'number'],
        ])
);
CODE,
            ['关联数组写法更适合列很多、希望 key 更直观可读的场景。'],
            function (): string {
                Form::clearInstances();

                return Form::make(uniqid('showcase_data_table_assoc_', false), '关联数组列定义')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        DataTable::make('rules', '规则明细')
                            ->columns([
                                'title' => ['title' => '规则名', 'type' => 'text'],
                                'category' => ['title' => '分类', 'type' => 'select', 'options' => ['base' => '基础', 'vip' => '会员']],
                                'sort' => ['title' => '排序', 'type' => 'number'],
                            ])
                    )
                    ->fetch();
            }
        );
    }

    private function validation(array $section): array
    {
        return $this->wrap(
            $section,
            [
                ['name' => 'required', 'value' => '限制某一列不能为空'],
                ['name' => 'pattern', 'value' => '限制列值格式'],
            ],
            <<<'CODE'
[
    [
        'type' => 'data_table',
        'name' => 'sku_rules',
        'label' => 'SKU 规则',
        'options' => [
            'columns' => [
                ['key' => 'code', 'title' => '编码', 'type' => 'text', 'required' => true, 'required_message' => '编码不能为空'],
                ['key' => 'sort', 'title' => '排序', 'type' => 'number', 'pattern' => '^\\d+$', 'pattern_message' => '排序必须是整数'],
            ],
        ],
    ],
]
CODE,
            <<<'CODE'
use form\data_table\DataTable;

$this->form->item(
    DataTable::make('sku_rules', 'SKU 规则')
        ->columns([
            ['key' => 'code', 'title' => '编码', 'type' => 'text', 'required' => true, 'required_message' => '编码不能为空'],
            ['key' => 'sort', 'title' => '排序', 'type' => 'number', 'pattern' => '^\\d+$', 'pattern_message' => '排序必须是整数'],
        ])
);
CODE,
            ['data_table 的列级校验主要依赖前端配置，适合直接演示真实错误提示文案写法。'],
            function (): string {
                Form::clearInstances();

                return Form::make(uniqid('showcase_data_table_validation_', false), '列级校验')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        DataTable::make('sku_rules', 'SKU 规则')
                            ->columns([
                                ['key' => 'code', 'title' => '编码', 'type' => 'text', 'required' => true, 'required_message' => '编码不能为空'],
                                ['key' => 'sort', 'title' => '排序', 'type' => 'number', 'pattern' => '^\\d+$', 'pattern_message' => '排序必须是整数'],
                            ])
                    )
                    ->fetch();
            }
        );
    }

    private function sortableConfirm(array $section): array
    {
        return $this->wrap(
            $section,
            [
                ['name' => 'sortable', 'value' => '启用拖拽排序'],
                ['name' => 'confirm_delete', 'value' => '删除前弹出确认'],
            ],
            <<<'CODE'
[
    [
        'type' => 'data_table',
        'name' => 'steps',
        'label' => '流程步骤',
        'options' => [
            'sortable' => true,
            'confirm_delete' => true,
            'columns' => [
                ['key' => 'title', 'title' => '步骤', 'type' => 'text'],
                ['key' => 'owner', 'title' => '负责人', 'type' => 'text'],
            ],
        ],
    ],
]
CODE,
            <<<'CODE'
use form\data_table\DataTable;

$this->form->item(
    DataTable::make('steps', '流程步骤')
        ->columns([
            ['key' => 'title', 'title' => '步骤', 'type' => 'text'],
            ['key' => 'owner', 'title' => '负责人', 'type' => 'text'],
        ])
        ->sortable()
        ->confirmDelete()
);
CODE,
            ['排序和删除确认是 data_table 最接近“真实后台明细维护”的两项交互配置。'],
            function (): string {
                Form::clearInstances();

                return Form::make(uniqid('showcase_data_table_sort_', false), '排序与删除确认')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        DataTable::make('steps', '流程步骤')
                            ->value([
                                ['title' => '提交申请', 'owner' => '张三'],
                                ['title' => '主管审批', 'owner' => '李四'],
                            ])
                            ->columns([
                                ['key' => 'title', 'title' => '步骤', 'type' => 'text'],
                                ['key' => 'owner', 'title' => '负责人', 'type' => 'text'],
                            ])
                            ->sortable()
                            ->confirmDelete()
                    )
                    ->fetch();
            }
        );
    }

    private function cellTypes(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => 'type', 'value' => '当前支持 text / textarea / select / number / switch']],
            <<<'CODE'
[
    [
        'type' => 'data_table',
        'name' => 'matrix',
        'label' => '能力矩阵',
        'options' => [
            'columns' => [
                ['key' => 'title', 'title' => '标题', 'type' => 'text'],
                ['key' => 'remark', 'title' => '备注', 'type' => 'textarea'],
                ['key' => 'scene', 'title' => '场景', 'type' => 'select', 'options' => ['base' => '基础', 'vip' => '高级']],
                ['key' => 'sort', 'title' => '排序', 'type' => 'number'],
                ['key' => 'status', 'title' => '启用', 'type' => 'switch'],
            ],
        ],
    ],
]
CODE,
            <<<'CODE'
use form\data_table\DataTable;

$this->form->item(
    DataTable::make('matrix', '能力矩阵')
        ->columns([
            ['key' => 'title', 'title' => '标题', 'type' => 'text'],
            ['key' => 'remark', 'title' => '备注', 'type' => 'textarea'],
            ['key' => 'scene', 'title' => '场景', 'type' => 'select', 'options' => ['base' => '基础', 'vip' => '高级']],
            ['key' => 'sort', 'title' => '排序', 'type' => 'number'],
            ['key' => 'status', 'title' => '启用', 'type' => 'switch'],
        ])
);
CODE,
            ['这个板块的重点是告诉开发者当前真实支持哪些单元格类型，以及不支持什么。'],
            function (): string {
                Form::clearInstances();

                return Form::make(uniqid('showcase_data_table_types_', false), '单元格类型边界')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        DataTable::make('matrix', '能力矩阵')
                            ->value([
                                ['title' => '基础套餐', 'remark' => '默认开通', 'scene' => 'base', 'sort' => '1', 'status' => '1'],
                            ])
                            ->columns([
                                ['key' => 'title', 'title' => '标题', 'type' => 'text'],
                                ['key' => 'remark', 'title' => '备注', 'type' => 'textarea'],
                                ['key' => 'scene', 'title' => '场景', 'type' => 'select', 'options' => ['base' => '基础', 'vip' => '高级']],
                                ['key' => 'sort', 'title' => '排序', 'type' => 'number'],
                                ['key' => 'status', 'title' => '启用', 'type' => 'switch'],
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
            [['name' => 'title / items', 'value' => '商品规格维护的真实业务组合']],
            <<<'CODE'
[
    ['type' => 'text', 'name' => 'product_name', 'label' => '商品名称', 'tips' => '请输入商品名称'],
    [
        'type' => 'data_table',
        'name' => 'items',
        'label' => '规格明细',
        'value' => [
            ['title' => '标准版', 'price' => '99', 'status' => '1'],
        ],
        'options' => [
            'sortable' => true,
            'columns' => [
                ['key' => 'title', 'title' => '规格', 'type' => 'text'],
                ['key' => 'price', 'title' => '售价', 'type' => 'number'],
                ['key' => 'status', 'title' => '启用', 'type' => 'switch'],
            ],
        ],
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;
use form\data_table\DataTable;

Field::text('product_name', '商品名称', '请输入商品名称');

$this->form->item(
    DataTable::make('items', '规格明细')
        ->value([
            ['title' => '标准版', 'price' => '99', 'status' => '1'],
        ])
        ->columns([
            ['key' => 'title', 'title' => '规格', 'type' => 'text'],
            ['key' => 'price', 'title' => '售价', 'type' => 'number'],
            ['key' => 'status', 'title' => '启用', 'type' => 'switch'],
        ])
        ->sortable()
);
CODE,
            ['商品规格、费用清单、规则矩阵，是最适合直接照抄的 data_table 业务场景。'],
            function (): string {
                Form::clearInstances();

                return Form::make(uniqid('showcase_data_table_profile_', false), '业务表单片段')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(Text::make('product_name', '商品名称', '请输入商品名称'))
                    ->item(
                        DataTable::make('items', '规格明细')
                            ->value([
                                ['title' => '标准版', 'price' => '99', 'status' => '1'],
                            ])
                            ->columns([
                                ['key' => 'title', 'title' => '规格', 'type' => 'text'],
                                ['key' => 'price', 'title' => '售价', 'type' => 'number'],
                                ['key' => 'status', 'title' => '启用', 'type' => 'switch'],
                            ])
                            ->sortable()
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
                    'path' => 'app/showcase/service/components/form/extensions/DataTableSectionBuilder.php',
                    'label' => 'data_table 能力块',
                    'description' => '按 section key 组装 data_table 的完整示例能力块。',
                ],
                [
                    'path' => 'extend/form/data_table/DataTable.php',
                    'label' => '扩展项门面类',
                    'description' => 'data_table 的独立门面入口，供业务表单直接调用。',
                ],
                [
                    'path' => 'extend/form/data_table/Item.php',
                    'label' => '扩展项渲染器',
                    'description' => 'data_table 的真实渲染、列标准化与资源注入逻辑。',
                ],
            ],
        ];
    }
}
