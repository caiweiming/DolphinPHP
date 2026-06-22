<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\date;

use app\common\render\Form;

/**
 * date 值格式与多日期提示能力块
 */
final class DateValueFormatSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'value_format'),
            'title' => (string) ($section['title'] ?? '值格式与多日期提示'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'value(string)', 'value' => '单日期场景推荐直接传字符串'],
                ['name' => 'value(array)', 'value' => '数组值会自动转为 selectedDates，并以逗号拼接回显'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'date',
        'name' => 'holiday_dates',
        'label' => '节假日',
        'tips' => '数组值会自动做多日期回显',
        'value' => ['2026-06-01', '2026-06-08'],
        'options' => [
            'multipleDates' => 2,
        ],
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::date('holiday_dates', '节假日', '数组值会自动做多日期回显')
    ->value(['2026-06-01', '2026-06-08'])
    ->options([
        'multipleDates' => 2,
    ]);
CODE,
            'notes' => [
                '如果业务是明确的起止时间段，不要代替专门的 range 组件，仍然建议使用 `date_range`，而不是用多日期硬凑范围。',
                '无论是字符串值还是数组值，只要改了前端日期格式，后端解析规则也要同步调整，避免提交后出现日期反序列化错误。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/date/DateValueFormatSection.php',
                    'label' => '值格式能力块',
                    'description' => '展示 date 组件中字符串值和数组值的回显差异。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_date_section_value_', false), '值格式与多日期提示')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item([
                'type' => 'date',
                'name' => 'holiday_dates',
                'id' => 'holiday_dates_value_format',
                'label' => '节假日',
                'tips' => '数组值会自动做多日期回显',
                'value' => ['2026-06-01', '2026-06-08'],
                'options' => [
                    'multipleDates' => 2,
                ],
            ])
            ->fetch();
    }
}
