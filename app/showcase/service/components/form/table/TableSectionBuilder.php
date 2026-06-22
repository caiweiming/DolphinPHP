<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\table;

use app\common\render\Form;
use app\common\render\form\items\table\Table;
use app\common\render\form\items\text\Text;

/**
 * table 能力块构建器
 */
final class TableSectionBuilder
{
    public function build(array $component, array $section): array
    {
        $key = (string) ($section['key'] ?? 'basic');

        return match ($key) {
            'basic' => $this->basic($section),
            'value_rows' => $this->valueRows($section),
            'merge_headers' => $this->mergeHeaders($section),
            'raw_html' => $this->rawHtml($section),
            'style_wrapper' => $this->styleWrapper($section),
            'empty_text' => $this->emptyText($section),
            'import_preview' => $this->importPreview($section),
            'profile_form' => $this->profile($section),
            default => $this->basic($section),
        };
    }

    private function basic(array $section): array
    {
        return $this->wrap(
            $section,
            [
                ['name' => 'headers / rows', 'value' => '最基础的只读表格结构由表头和表体组成'],
            ],
            <<<'CODE'
[
    [
        'type' => 'table',
        'name' => 'report',
        'label' => '销售报表',
        'headers' => [
            ['日期', '订单数', '销售额'],
        ],
        'rows' => [
            ['2026-06-01', '152', '¥38,500'],
            ['2026-06-02', '168', '¥41,200'],
        ],
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::table('report', '销售报表')
    ->headers([
        ['日期', '订单数', '销售额'],
    ])
    ->rows([
        ['2026-06-01', '152', '¥38,500'],
        ['2026-06-02', '168', '¥41,200'],
    ]);
CODE,
            ['table 组件适合“看结果、做确认”，不适合当成可编辑 grid 使用。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_table_basic_', false), '基础表格')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Table::make('report', '销售报表')
                            ->headers([
                                ['日期', '订单数', '销售额'],
                            ])
                            ->rows([
                                ['2026-06-01', '152', '¥38,500'],
                                ['2026-06-02', '168', '¥41,200'],
                            ])
                    )
                    ->fetch();
            }
        );
    }

    private function valueRows(array $section): array
    {
        return $this->wrap(
            $section,
            [
                ['name' => 'value', 'value' => '直接把二维数组作为 value 传入，底层会兼容为 rows'],
            ],
            <<<'CODE'
[
    [
        'type' => 'table',
        'name' => 'sku_preview',
        'label' => 'SKU 预览',
        'headers' => [
            ['规格', '库存', '售价'],
        ],
        'value' => [
            ['标准版', '128', '¥199'],
            ['旗舰版', '64', '¥299'],
        ],
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::table('sku_preview', 'SKU 预览')
    ->headers([
        ['规格', '库存', '售价'],
    ])
    ->value([
        ['标准版', '128', '¥199'],
        ['旗舰版', '64', '¥299'],
    ]);
CODE,
            ['旧项目里如果已经在用 `value` 传表体数据，这类旧写法可以继续平滑兼容。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_table_value_', false), 'value 回显表体')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Table::make('sku_preview', 'SKU 预览')
                            ->headers([
                                ['规格', '库存', '售价'],
                            ])
                            ->value([
                                ['标准版', '128', '¥199'],
                                ['旗舰版', '64', '¥299'],
                            ])
                    )
                    ->fetch();
            }
        );
    }

    private function mergeHeaders(array $section): array
    {
        return $this->wrap(
            $section,
            [
                ['name' => 'headers[].rowspan / colspan', 'value' => '通过单元格对象配置复杂多表头'],
                ['name' => 'rows[].colspan', 'value' => '表体同样支持合并单元格'],
            ],
            <<<'CODE'
[
    [
        'type' => 'table',
        'name' => 'channel_report',
        'label' => '渠道报表',
        'headers' => [
            [
                ['content' => '渠道', 'rowspan' => 2],
                ['content' => '昨日数据', 'colspan' => 2],
                ['content' => '今日数据', 'colspan' => 2],
            ],
            [
                '订单数', '销售额', '订单数', '销售额',
            ],
        ],
        'rows' => [
            ['官网', '52', '¥12,300', '61', '¥14,800'],
            ['私域', '34', '¥8,400', '39', '¥9,600'],
            [
                ['content' => '汇总', 'colspan' => 2, 'class' => 'fw-bold text-end'],
                ['content' => '86', 'class' => 'fw-bold'],
                ['content' => '100', 'class' => 'fw-bold'],
                ['content' => '¥24,400', 'class' => 'fw-bold'],
            ],
        ],
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::table('channel_report', '渠道报表')
    ->headers([
        [
            ['content' => '渠道', 'rowspan' => 2],
            ['content' => '昨日数据', 'colspan' => 2],
            ['content' => '今日数据', 'colspan' => 2],
        ],
        ['订单数', '销售额', '订单数', '销售额'],
    ])
    ->rows([
        ['官网', '52', '¥12,300', '61', '¥14,800'],
        ['私域', '34', '¥8,400', '39', '¥9,600'],
        [
            ['content' => '汇总', 'colspan' => 2, 'class' => 'fw-bold text-end'],
            ['content' => '86', 'class' => 'fw-bold'],
            ['content' => '100', 'class' => 'fw-bold'],
            ['content' => '¥24,400', 'class' => 'fw-bold'],
        ],
    ]);
CODE,
            ['复杂报表建议先在 builder 中组织好 headers/rows，再统一传给 table。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_table_merge_', false), '多表头与合并单元格')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Table::make('channel_report', '渠道报表')
                            ->headers([
                                [
                                    ['content' => '渠道', 'rowspan' => 2],
                                    ['content' => '昨日数据', 'colspan' => 2],
                                    ['content' => '今日数据', 'colspan' => 2],
                                ],
                                ['订单数', '销售额', '订单数', '销售额'],
                            ])
                            ->rows([
                                ['官网', '52', '¥12,300', '61', '¥14,800'],
                                ['私域', '34', '¥8,400', '39', '¥9,600'],
                                [
                                    ['content' => '汇总', 'colspan' => 2, 'class' => 'fw-bold text-end'],
                                    ['content' => '86', 'class' => 'fw-bold'],
                                    ['content' => '100', 'class' => 'fw-bold'],
                                    ['content' => '¥24,400', 'class' => 'fw-bold'],
                                ],
                            ])
                    )
                    ->fetch();
            }
        );
    }

    private function rawHtml(array $section): array
    {
        return $this->wrap(
            $section,
            [
                ['name' => 'raw(true)', 'value' => '全表默认按 HTML 输出'],
                ['name' => 'cell.raw', 'value' => '也可以只对单元格级别单独开启 raw'],
            ],
            <<<'CODE'
[
    [
        'type' => 'table',
        'name' => 'status_table',
        'label' => '状态看板',
        'headers' => [
            ['模块', '状态', '负责人'],
        ],
        'rows' => [
            ['用户中心', '<span class="badge bg-success-lt">正常</span>', 'Luna'],
            ['支付中心', '<span class="badge bg-warning-lt">观察中</span>', 'Aria'],
        ],
        'raw' => true,
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::table('status_table', '状态看板')
    ->headers([
        ['模块', '状态', '负责人'],
    ])
    ->rows([
        ['用户中心', '<span class="badge bg-success-lt">正常</span>', 'Luna'],
        ['支付中心', '<span class="badge bg-warning-lt">观察中</span>', 'Aria'],
    ])
    ->raw();
CODE,
            ['一旦开启 raw，单元格内容要由你自己保证安全性和可信来源。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_table_raw_', false), '单元格 raw 与 HTML')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Table::make('status_table', '状态看板')
                            ->headers([
                                ['模块', '状态', '负责人'],
                            ])
                            ->rows([
                                ['用户中心', '<span class="badge bg-success-lt">正常</span>', 'Luna'],
                                ['支付中心', '<span class="badge bg-warning-lt">观察中</span>', 'Aria'],
                            ])
                            ->raw()
                    )
                    ->fetch();
            }
        );
    }

    private function styleWrapper(array $section): array
    {
        return $this->wrap(
            $section,
            [
                ['name' => 'table_class', 'value' => '控制表格本身的边框、紧凑、条纹等类名'],
                ['name' => 'wrapper_class', 'value' => '控制外层滚动容器或卡片容器类名'],
            ],
            <<<'CODE'
[
    [
        'type' => 'table',
        'name' => 'compact_table',
        'label' => '紧凑表格',
        'table_class' => 'table table-sm table-hover',
        'wrapper_class' => 'table-responsive border rounded',
        'headers' => [
            ['字段', '说明'],
        ],
        'rows' => [
            ['app_id', '应用唯一标识'],
            ['app_secret', '应用密钥，需妥善保管'],
        ],
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::table('compact_table', '紧凑表格')
    ->tableClass('table table-sm table-hover')
    ->wrapperClass('table-responsive border rounded')
    ->headers([
        ['字段', '说明'],
    ])
    ->rows([
        ['app_id', '应用唯一标识'],
        ['app_secret', '应用密钥，需妥善保管'],
    ]);
CODE,
            ['`wrapper_class` 很适合给长表格追加横向滚动和边框容器。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_table_style_', false), '样式类与外层容器')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Table::make('compact_table', '紧凑表格')
                            ->tableClass('table table-sm table-hover')
                            ->wrapperClass('table-responsive border rounded')
                            ->headers([
                                ['字段', '说明'],
                            ])
                            ->rows([
                                ['app_id', '应用唯一标识'],
                                ['app_secret', '应用密钥，需妥善保管'],
                            ])
                    )
                    ->fetch();
            }
        );
    }

    private function emptyText(array $section): array
    {
        return $this->wrap(
            $section,
            [
                ['name' => 'empty_text', 'value' => '当 rows 为空时展示占位文案'],
            ],
            <<<'CODE'
[
    [
        'type' => 'table',
        'name' => 'empty_preview',
        'label' => '空数据预览',
        'headers' => [
            ['字段', '当前值'],
        ],
        'rows' => [],
        'empty_text' => '当前还没有采集到任何统计数据',
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::table('empty_preview', '空数据预览')
    ->headers([
        ['字段', '当前值'],
    ])
    ->rows([])
    ->emptyText('当前还没有采集到任何统计数据');
CODE,
            ['空态文案可以直接告诉开发者或运营“为什么这里没数据”。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_table_empty_', false), '空数据占位')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Table::make('empty_preview', '空数据预览')
                            ->headers([
                                ['字段', '当前值'],
                            ])
                            ->rows([])
                            ->emptyText('当前还没有采集到任何统计数据')
                    )
                    ->fetch();
            }
        );
    }

    private function importPreview(array $section): array
    {
        return $this->wrap(
            $section,
            [
                ['name' => '导入结果预览', 'value' => '把 CSV/Excel 解析结果先做成只读预览'],
            ],
            <<<'CODE'
[
    [
        'type' => 'table',
        'name' => 'import_preview',
        'label' => '导入预览',
        'headers' => [
            ['姓名', '手机号', '状态'],
        ],
        'rows' => [
            ['张三', '13800000001', '<span class="badge bg-success-lt">待导入</span>'],
            ['李四', '13800000002', '<span class="badge bg-danger-lt">手机号重复</span>'],
        ],
        'raw' => true,
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::table('import_preview', '导入预览')
    ->headers([
        ['姓名', '手机号', '状态'],
    ])
    ->rows([
        ['张三', '13800000001', '<span class="badge bg-success-lt">待导入</span>'],
        ['李四', '13800000002', '<span class="badge bg-danger-lt">手机号重复</span>'],
    ])
    ->raw();
CODE,
            ['这是 table 在后台里最常见的业务落点之一：提交前核对。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_table_import_', false), '导入预览场景')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Table::make('import_preview', '导入预览')
                            ->headers([
                                ['姓名', '手机号', '状态'],
                            ])
                            ->rows([
                                ['张三', '13800000001', '<span class="badge bg-success-lt">待导入</span>'],
                                ['李四', '13800000002', '<span class="badge bg-danger-lt">手机号重复</span>'],
                            ])
                            ->raw()
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
                ['name' => 'batch_name / import_preview', 'value' => '导入任务确认表单的典型组合'],
            ],
            <<<'CODE'
[
    ['type' => 'text', 'name' => 'batch_name', 'label' => '批次名称', 'tips' => '用于区分本次导入任务'],
    [
        'type' => 'table',
        'name' => 'import_preview',
        'label' => '导入预览',
        'raw' => true,
        'headers' => [
            ['姓名', '手机号', '状态'],
        ],
        'rows' => [
            ['张三', '13800000001', '<span class="badge bg-success-lt">可导入</span>'],
            ['李四', '13800000002', '<span class="badge bg-warning-lt">需人工确认</span>'],
        ],
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::text('batch_name', '批次名称', '用于区分本次导入任务');

Field::table('import_preview', '导入预览')
    ->raw()
    ->headers([
        ['姓名', '手机号', '状态'],
    ])
    ->rows([
        ['张三', '13800000001', '<span class="badge bg-success-lt">可导入</span>'],
        ['李四', '13800000002', '<span class="badge bg-warning-lt">需人工确认</span>'],
    ]);
CODE,
            ['业务里常见做法是：文本字段说明本次动作，table 负责给出最终确认依据。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_table_profile_', false), '业务表单片段')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(Text::make('batch_name', '批次名称', '用于区分本次导入任务'))
                    ->item(
                        Table::make('import_preview', '导入预览')
                            ->raw()
                            ->headers([
                                ['姓名', '手机号', '状态'],
                            ])
                            ->rows([
                                ['张三', '13800000001', '<span class="badge bg-success-lt">可导入</span>'],
                                ['李四', '13800000002', '<span class="badge bg-warning-lt">需人工确认</span>'],
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
            'source_refs' => [[
                'path' => 'app/showcase/service/components/form/table/TableSectionBuilder.php',
                'label' => 'table 能力块',
                'description' => '按 section key 组装 table 的完整示例能力块。',
            ]],
        ];
    }
}
