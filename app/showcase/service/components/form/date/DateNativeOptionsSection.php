<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\date;

use app\common\render\Form;

/**
 * date 原生 options 能力块
 */
final class DateNativeOptionsSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'native_options'),
            'title' => (string) ($section['title'] ?? '原生 options 配置'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'options.dateFormat', 'value' => '控制输入框输出值格式'],
                ['name' => 'options.autoClose', 'value' => '选择后自动关闭日期面板'],
                ['name' => 'options.multipleDates', 'value' => '开启多日期模式，用于多个离散日期选择'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'date',
        'name' => 'holiday_dates',
        'label' => '节假日',
        'tips' => '演示 AirDatepicker 原生配置透传',
        'options' => [
            'dateFormat' => 'yyyy-MM-dd',
            'autoClose' => true,
            'multipleDates' => 2,
        ],
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::date('holiday_dates', '节假日', '演示 AirDatepicker 原生配置透传')
    ->options([
        'dateFormat' => 'yyyy-MM-dd',
        'autoClose' => true,
        'multipleDates' => 2,
    ]);
CODE,
            'notes' => [
                'date 的高级行为主要来自底层 AirDatepicker 原生配置。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/date/DateNativeOptionsSection.php',
                    'label' => '原生 options 能力块',
                    'description' => '展示 date 如何透传 AirDatepicker 原生配置。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_date_section_options_', false), '原生 options 配置')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item([
                'type' => 'date',
                'name' => 'holiday_dates',
                'id' => 'holiday_dates_native_options',
                'label' => '节假日',
                'tips' => '演示 AirDatepicker 原生配置透传',
                'options' => [
                    'dateFormat' => 'yyyy-MM-dd',
                    'autoClose' => true,
                    'multipleDates' => 2,
                ],
            ])
            ->fetch();
    }
}
