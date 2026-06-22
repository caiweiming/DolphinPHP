<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\mask;

use app\common\render\Form;
use app\common\render\form\items\mask\Mask;
use app\common\render\form\items\text\Text;

/**
 * mask 能力块构建器
 */
final class MaskSectionBuilder
{
    public function build(array $component, array $section): array
    {
        $key = (string) ($section['key'] ?? 'basic');

        return match ($key) {
            'basic' => $this->basic($section),
            'phone' => $this->phone($section),
            'date_time' => $this->dateTime($section),
            'id_code' => $this->idCode($section),
            'default_value' => $this->defaultValue($section),
            'notice' => $this->notice($section),
            'profile_form' => $this->profile($section),
            default => $this->basic($section),
        };
    }

    private function basic(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => 'options', 'value' => '直接使用掩码字符串']],
            <<<'CODE'
[
    [
        'type' => 'mask',
        'name' => 'order_month',
        'label' => '结算月份',
        'options' => '0000-00',
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::mask('order_month', '结算月份')
    ->options('0000-00');
CODE,
            ['当前 mask 组件的真实掩码参数是 `options`，不是独立的 `mask()` 链式方法。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_mask_basic_', false), '基础掩码')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(Mask::make('order_month', '结算月份')->options('0000-00'))
                    ->fetch();
            }
        );
    }

    private function phone(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => '手机号', 'value' => '用固定分隔格式引导输入']],
            <<<'CODE'
[
    [
        'type' => 'mask',
        'name' => 'phone',
        'label' => '手机号',
        'options' => '000-0000-0000',
        'tips' => '示例格式：138-1234-5678',
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::mask('phone', '手机号')
    ->options('000-0000-0000')
    ->tips('示例格式：138-1234-5678');
CODE,
            ['手机号类掩码能显著减少用户输入时的格式犹豫。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_mask_phone_', false), '手机号格式')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Mask::make('phone', '手机号')
                            ->id('phone_phone_mask')
                            ->options('000-0000-0000')
                            ->tips('示例格式：138-1234-5678')
                    )
                    ->fetch();
            }
        );
    }

    private function dateTime(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => '日期 / 时间', 'value' => '适合纯文本日期或时间格式占位']],
            <<<'CODE'
[
    ['type' => 'mask', 'name' => 'start_date', 'label' => '开始日期', 'options' => '0000-00-00'],
    ['type' => 'mask', 'name' => 'start_time', 'label' => '开始时间', 'options' => '00:00'],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::mask('start_date', '开始日期')
    ->options('0000-00-00');

Field::mask('start_time', '开始时间')
    ->options('00:00');
CODE,
            ['如果只是想控制字符格式，mask 比日期组件更轻；需要日历弹层时应使用 date/time 系列组件。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_mask_date_', false), '日期与时间格式')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(Mask::make('start_date', '开始日期')->options('0000-00-00'))
                    ->item(Mask::make('start_time', '开始时间')->options('00:00'))
                    ->fetch();
            }
        );
    }

    private function idCode(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => '混合编号', 'value' => '字母数字分段格式也适合用 mask 约束']],
            <<<'CODE'
[
    [
        'type' => 'mask',
        'name' => 'contract_code',
        'label' => '合同编号',
        'options' => 'aa-0000-0000',
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::mask('contract_code', '合同编号')
    ->options('aa-0000-0000');
CODE,
            ['证件、合同、工单等固定结构编号非常适合通过 mask 统一录入格式。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_mask_code_', false), '证件与编号格式')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(Mask::make('contract_code', '合同编号')->id('contract_code_id_code')->options('aa-0000-0000'))
                    ->fetch();
            }
        );
    }

    private function defaultValue(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => 'value', 'value' => '编辑态回显时通常直接写入带格式的值']],
            <<<'CODE'
[
    [
        'type' => 'mask',
        'name' => 'invoice_code',
        'label' => '票据编号',
        'options' => '0000-0000',
        'value' => '2026-1008',
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::mask('invoice_code', '票据编号')
    ->options('0000-0000')
    ->value('2026-1008');
CODE,
            ['如果历史数据未带格式，建议回显前先在服务端统一格式化。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_mask_value_', false), '默认值与回填')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Mask::make('invoice_code', '票据编号')
                            ->options('0000-0000')
                            ->value('2026-1008')
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
                ['name' => '格式职责', 'value' => 'mask 只负责前端输入格式，不负责后端合法性校验'],
                ['name' => '提交结果', 'value' => '提交值可能包含分隔符，后端应自行清洗并校验'],
            ],
            <<<'CODE'
[
    [
        'type' => 'mask',
        'name' => 'customer_phone',
        'label' => '联系电话',
        'options' => '000-0000-0000',
        'value' => '138-1234-5678',
        'tips' => 'mask 当前使用 options 传递掩码；提交值可能带分隔符；后端应自行清洗并校验',
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::mask('customer_phone', '联系电话')
    ->options('000-0000-0000')
    ->value('138-1234-5678')
    ->tips('mask 当前使用 options 传递掩码；提交值可能带分隔符；后端应自行清洗并校验');
CODE,
            ['这个能力块直接把真实实现边界放回 mask 本体里，开发者可以一边看掩码效果，一边理解后端处理边界。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_mask_notice_', false), '实现与提交注意事项')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Mask::make('customer_phone', '联系电话')
                            ->options('000-0000-0000')
                            ->value('138-1234-5678')
                            ->tips('mask 当前使用 options 传递掩码；提交值可能带分隔符；后端应自行清洗并校验')
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
                ['name' => 'phone / contract_code', 'value' => '业务表单中常见的两类格式化字段'],
            ],
            <<<'CODE'
[
    ['type' => 'text', 'name' => 'customer_name', 'label' => '客户名称', 'tips' => '请输入客户名称'],
    ['type' => 'mask', 'name' => 'phone', 'label' => '联系方式', 'options' => '000-0000-0000'],
    ['type' => 'mask', 'name' => 'contract_code', 'label' => '合同编号', 'options' => 'aa-0000-0000'],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::text('customer_name', '客户名称', '请输入客户名称');
Field::mask('phone', '联系方式')->options('000-0000-0000');
Field::mask('contract_code', '合同编号')->options('aa-0000-0000');
CODE,
            ['这类业务组合最能体现 mask 在“普通文本表单中局部增强格式控制”的价值。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_mask_profile_', false), '业务表单片段')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(Text::make('customer_name', '客户名称', '请输入客户名称'))
                    ->item(Mask::make('phone', '联系方式')->id('phone_profile_form')->options('000-0000-0000'))
                    ->item(Mask::make('contract_code', '合同编号')->id('contract_code_profile_form')->options('aa-0000-0000'))
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
                'path' => 'app/showcase/service/components/form/mask/MaskSectionBuilder.php',
                'label' => 'mask 能力块',
                'description' => '按 section key 组装 mask 的完整示例能力块。',
            ]],
        ];
    }
}
