<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\datetime;

use app\common\render\Form;

/**
 * datetime 原生 options 能力块
 */
final class DatetimeNativeOptionsSection
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
                ['name' => 'options.timeFormat', 'value' => '控制时间部分显示格式'],
                ['name' => 'options.minutesStep', 'value' => '控制分钟滑块步进'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'datetime',
        'name' => 'delivery_at',
        'label' => '送达时间',
        'tips' => '演示 AirDatepicker 原生日期时间配置透传',
        'options' => [
            'dateFormat' => 'yyyy-MM-dd HH:mm',
            'timeFormat' => 'HH:mm',
            'minutesStep' => 10,
        ],
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::datetime('delivery_at', '送达时间', '演示 AirDatepicker 原生日期时间配置透传')
    ->options([
        'dateFormat' => 'yyyy-MM-dd HH:mm',
        'timeFormat' => 'HH:mm',
        'minutesStep' => 10,
    ]);
CODE,
            'notes' => [
                'datetime 的高级行为主要来自底层 AirDatepicker 原生日期时间配置。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/datetime/DatetimeNativeOptionsSection.php',
                    'label' => '原生 options 能力块',
                    'description' => '展示 datetime 如何透传 AirDatepicker 原生日期时间配置。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_datetime_section_options_', false), '原生 options 配置')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item([
                'type' => 'datetime',
                'name' => 'delivery_at',
                'label' => '送达时间',
                'tips' => '演示 AirDatepicker 原生日期时间配置透传',
                'options' => [
                    'dateFormat' => 'yyyy-MM-dd HH:mm',
                    'timeFormat' => 'HH:mm',
                    'minutesStep' => 10,
                ],
            ])
            ->fetch();
    }
}
