<?php
declare(strict_types=1);

namespace app\showcase\service\components\form;

/**
 * Showcase 扩展表单项组件页组装器
 */
final class ExtensionComponentPage
{
    /**
     * @param array<string, mixed> $component
     * @param list<array<string, string>> $sectionCatalog
     * @return array<string, mixed>
     */
    public function build(array $component, array $sectionCatalog): array
    {
        $key = (string) ($component['key'] ?? '');
        $sections = [];

        foreach ($sectionCatalog as $sectionMeta) {
            $builderClass = (string) ($sectionMeta['builder'] ?? '');
            if ($builderClass === '') {
                continue;
            }

            $sections[] = app($builderClass)->build($component, $sectionMeta);
        }

        $meta = $this->meta($key);

        return [
            'component' => $component,
            'overview' => [
                'summary' => $meta['summary'],
                'scenarios' => $meta['scenarios'],
                'capabilities' => $meta['capabilities'],
                'quick_start' => [
                    'array_code' => $meta['quick_start']['array_code'],
                    'field_code' => $meta['quick_start']['field_code'],
                ],
            ],
            'param_groups' => $meta['param_groups'],
            'sections' => $sections,
            'related_components' => $meta['related_components'],
            'doc_links' => (array) ($component['doc_links'] ?? []),
            'sidebar_source_refs' => array_slice($this->mergeSourceRefs((array) ($component['source_refs'] ?? []), $sections), 0, 3),
            'source_refs' => $this->mergeSourceRefs((array) ($component['source_refs'] ?? []), $sections),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function meta(string $key): array
    {
        return match ($key) {
            'extensions.data_table' => [
                'summary' => 'data_table 扩展项用于在一个字段中维护多行多列的结构化数据，适合规格、费用、规则项等可写明细场景。',
                'scenarios' => [
                    '商品规格、费用清单、规则明细等需要直接在表单内编辑多行数据的场景',
                    '希望通过一个字段统一提交二维数组，而不是拆成多个独立字段的场景',
                    '需要拖拽排序、行级删除确认和列级前端校验的后台场景',
                ],
                'capabilities' => [
                    '基础数据表格',
                    '默认值与回填',
                    '关联数组列定义',
                    '列级校验',
                    '排序与删除确认',
                    '单元格类型边界',
                    '业务表单片段',
                ],
                'quick_start' => [
                    'array_code' => <<<'CODE'
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
            'sortable' => true,
            'confirm_delete' => true,
        ],
    ],
]
CODE,
                    'field_code' => <<<'CODE'
use form\data_table\DataTable;

$this->form->item(
    DataTable::make('items', '规格明细')
        ->columns([
            ['key' => 'title', 'title' => '标题', 'type' => 'text'],
            ['key' => 'price', 'title' => '售价', 'type' => 'number'],
            ['key' => 'status', 'title' => '启用', 'type' => 'switch'],
        ])
        ->sortable()
        ->confirmDelete()
);
CODE,
                ],
                'param_groups' => [
                    [
                        'title' => '基础参数',
                        'items' => [
                            ['name' => 'name / label / value', 'summary' => '字段名、标题和二维数组回填值。'],
                            ['name' => 'columns', 'summary' => '列定义，支持列表写法和关联数组写法。'],
                            ['name' => 'add_text / empty_text', 'summary' => '控制空状态和添加按钮文案。'],
                        ],
                    ],
                    [
                        'title' => '表格交互',
                        'items' => [
                            ['name' => 'sortable', 'summary' => '是否启用前端拖拽排序。'],
                            ['name' => 'confirm_delete', 'summary' => '删除行前是否弹出确认。'],
                            ['name' => 'sort_notify / sort_notify_message', 'summary' => '控制排序完成后的提示行为。'],
                        ],
                    ],
                    [
                        'title' => '实现说明',
                        'items' => [
                            ['name' => '门面类', 'summary' => '使用 `form\\data_table\\DataTable` 接入；当前未映射到 `Field`。'],
                            ['name' => '单元格类型', 'summary' => '以 `extend/form/data_table/Item.php` 当前支持的类型为准。'],
                            ['name' => '源码参考', 'summary' => '详情页下方源码可直接查看扩展项门面、渲染器与本节实现。'],
                        ],
                    ],
                ],
                'related_components' => [
                    ['key' => 'extensions.fieldset', 'title' => 'fieldset 字段块', 'status' => 'available'],
                    ['key' => 'extensions.select_table', 'title' => 'select_table 选表器', 'status' => 'available'],
                    ['key' => 'rich.table', 'title' => 'table 表格展示', 'status' => 'available'],
                ],
            ],
            'extensions.fieldset' => [
                'summary' => 'fieldset 扩展项用于把一组普通字段组织成可视化字段块，适合复杂表单中的结构分区与整体联动控制。',
                'scenarios' => [
                    '联系人信息、SEO 设置、高级配置等需要做视觉分区的表单场景',
                    '想复用普通表单项能力，但希望从布局上把它们组织为一个字段块的场景',
                    '需要通过外层 when 控制整组字段显示隐藏的后台场景',
                ],
                'capabilities' => [
                    '基础字段块',
                    '复杂子项组合',
                    '外层联动控制',
                    '默认值与回显方式',
                    'help 与 tips',
                    '业务表单片段',
                ],
                'quick_start' => [
                    'array_code' => <<<'CODE'
[
    [
        'type' => 'fieldset',
        'name' => 'contact_block',
        'label' => '联系人信息',
        'tips' => '用于集中展示联系人相关字段',
        'options' => [
            ['type' => 'text', 'name' => 'contact_name', 'label' => '联系人'],
            ['type' => 'text', 'name' => 'contact_mobile', 'label' => '联系电话'],
        ],
    ],
]
CODE,
                    'field_code' => <<<'CODE'
use form\fieldset\Fieldset;

$this->form->item(
    Fieldset::make('contact_block', '联系人信息')
        ->tips('用于集中展示联系人相关字段')
        ->options([
            ['type' => 'text', 'name' => 'contact_name', 'label' => '联系人'],
            ['type' => 'text', 'name' => 'contact_mobile', 'label' => '联系电话'],
        ])
);
CODE,
                ],
                'param_groups' => [
                    [
                        'title' => '基础参数',
                        'items' => [
                            ['name' => 'name / label / tips', 'summary' => '外层字段标识、标题和补充说明。'],
                            ['name' => 'options', 'summary' => '子项数组，内部每项仍然是正常表单项配置。'],
                            ['name' => 'required / width / help', 'summary' => '外层依旧可复用 FormType 的常规链式方法。'],
                        ],
                    ],
                    [
                        'title' => '结构行为',
                        'items' => [
                            ['name' => '子项回显', 'summary' => '回显仍由子字段自己的 `name` 决定，外层 value 不会自动拆分。'],
                            ['name' => '资源收集', 'summary' => '子项原有脚本、样式、联动能力会继续生效。'],
                            ['name' => 'when', 'summary' => '控制的是整个字段块，而不是单个内部字段。'],
                        ],
                    ],
                    [
                        'title' => '实现说明',
                        'items' => [
                            ['name' => '门面类', 'summary' => '使用 `form\\fieldset\\Fieldset` 接入；当前未映射到 `Field`。'],
                            ['name' => '组件本质', 'summary' => '它更像布局容器，不是新的数据协议。'],
                            ['name' => '源码参考', 'summary' => '详情页下方源码可直接查看门面、递归渲染逻辑与本节实现。'],
                        ],
                    ],
                ],
                'related_components' => [
                    ['key' => 'extensions.data_table', 'title' => 'data_table 数据表格', 'status' => 'available'],
                    ['key' => 'extensions.select_table', 'title' => 'select_table 选表器', 'status' => 'available'],
                    ['key' => 'rich.tabs', 'title' => 'tabs 标签分组', 'status' => 'available'],
                ],
            ],
            default => [
                'summary' => 'select_table 扩展项用于在弹窗表格中选择数据并回填多列字段，适合成员、商品、角色等“选中即带回多列信息”的场景。',
                'scenarios' => [
                    '用户、商品、角色、节点等需要弹窗筛选并回填多列数据的场景',
                    '既要展示已选结果，又要把多列隐藏字段一起提交的后台表单场景',
                    '需要单选/多选切换、去重、条数限制和自定义弹窗表格 ID 的场景',
                ],
                'capabilities' => [
                    '基础选表',
                    '默认值与回填',
                    '单选模式',
                    '条数限制',
                    'extra_fields 附加字段',
                    'popup 参数',
                    '业务表单片段',
                ],
                'quick_start' => [
                    'array_code' => <<<'CODE'
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
                    'field_code' => <<<'CODE'
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
        ->selectionMode('multiple')
);
CODE,
                ],
                'param_groups' => [
                    [
                        'title' => '基础参数',
                        'items' => [
                            ['name' => 'name / label / value', 'summary' => '字段名、标题和默认回显二维数组。'],
                            ['name' => 'columns', 'summary' => '当前表单项展示列，支持数组列表或键值映射。'],
                            ['name' => 'popup', 'summary' => '弹窗 URL、标题、尺寸和表格 ID 配置。'],
                        ],
                    ],
                    [
                        'title' => '选择行为',
                        'items' => [
                            ['name' => 'selection_mode', 'summary' => '控制单选或多选模式。'],
                            ['name' => 'unique_key / select_limit', 'summary' => '控制去重键和最多保留条数。'],
                            ['name' => 'extra_fields', 'summary' => '追加隐藏提交字段，但不在当前表格中展示。'],
                        ],
                    ],
                    [
                        'title' => '实现说明',
                        'items' => [
                            ['name' => '门面类', 'summary' => '使用 `form\\select_table\\SelectTable` 接入；当前未映射到 `Field`。'],
                            ['name' => '弹窗数据协议', 'summary' => '弹窗页最终以 `dpSelectTableGetData()` 返回结果为准。'],
                            ['name' => '源码参考', 'summary' => '详情页下方源码可直接查看门面、渲染器、真实表格弹窗页与本节实现。'],
                        ],
                    ],
                ],
                'related_components' => [
                    ['key' => 'extensions.data_table', 'title' => 'data_table 数据表格', 'status' => 'available'],
                    ['key' => 'choice.select2', 'title' => 'select2 增强下拉选择', 'status' => 'available'],
                    ['key' => 'extensions.fieldset', 'title' => 'fieldset 字段块', 'status' => 'available'],
                ],
            ],
        };
    }

    /**
     * @param array<int, array<string, mixed>> $componentSources
     * @param list<array<string, mixed>> $sections
     * @return array<int, array<string, mixed>>
     */
    private function mergeSourceRefs(array $componentSources, array $sections): array
    {
        $result = $componentSources;
        $seen = [];

        foreach ($componentSources as $source) {
            $path = (string) ($source['path'] ?? '');
            if ($path !== '') {
                $seen[$path] = true;
            }
        }

        foreach ($sections as $section) {
            foreach ((array) ($section['source_refs'] ?? []) as $source) {
                $path = (string) ($source['path'] ?? '');
                if ($path === '' || isset($seen[$path])) {
                    continue;
                }

                $seen[$path] = true;
                $result[] = $source;
            }
        }

        return $result;
    }
}
