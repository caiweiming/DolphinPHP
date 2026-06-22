<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\datetime_range;

use app\common\render\Form;

/**
 * datetime_range 原生 options 能力块
 */
final class DatetimeRangeNativeOptionsSection
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
                ['name' => 'options.dateFormat', 'value' => '控制输入框输出的完整范围格式'],
                ['name' => 'options.timeFormat', 'value' => '控制时间部分显示格式'],
                ['name' => 'options.multipleDatesSeparator', 'value' => '控制开始和结束时间之间的分隔符'],
                ['name' => 'options.minutesStep', 'value' => '控制分钟步进'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'datetime_range',
        'name' => 'delivery_period',
        'label' => '配送时间段',
        'tips' => '演示 AirDatepicker 原生时间范围配置透传',
        'options' => [
            'dateFormat' => 'yyyy-MM-dd HH:mm',
            'timeFormat' => 'HH:mm',
            'minutesStep' => 30,
            'multipleDatesSeparator' => ' ~ ',
        ],
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::datetimeRange('delivery_period', '配送时间段', '演示 AirDatepicker 原生时间范围配置透传')
    ->options([
        'dateFormat' => 'yyyy-MM-dd HH:mm',
        'timeFormat' => 'HH:mm',
        'minutesStep' => 30,
        'multipleDatesSeparator' => ' ~ ',
    ]);
CODE,
            'notes' => [
                '时间范围字段的解析稳定性，很大程度取决于前后端是否统一了分隔符与输出格式。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/datetime_range/DatetimeRangeNativeOptionsSection.php',
                    'label' => '原生 options 能力块',
                    'description' => '展示 datetime_range 如何透传 AirDatepicker 原生范围配置。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_datetime_range_section_options_', false), '原生 options 配置')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item([
                'type' => 'datetime_range',
                'name' => 'delivery_period',
                'label' => '配送时间段',
                'tips' => '演示 AirDatepicker 原生时间范围配置透传',
                'options' => [
                    'dateFormat' => 'yyyy-MM-dd HH:mm',
                    'timeFormat' => 'HH:mm',
                    'minutesStep' => 30,
                    'multipleDatesSeparator' => ' ~ ',
                ],
            ])
            ->fetch();
    }
}
