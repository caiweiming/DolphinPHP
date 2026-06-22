<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\datetime_range;

use app\common\render\Form;

/**
 * datetime_range 范围格式与分隔符能力块
 */
final class DatetimeRangeFormatSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'range_format'),
            'title' => (string) ($section['title'] ?? '范围格式与分隔符'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'dateFormat + timeFormat', 'value' => '控制范围值中开始与结束时间的输出格式'],
                ['name' => 'multipleDatesSeparator', 'value' => '控制开始和结束时间在单字段中的拼接符号'],
                ['name' => 'minutesStep', 'value' => '适合控制 15 分钟、30 分钟等预约粒度'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'datetime_range',
        'name' => 'meeting_period',
        'label' => '会议时间段',
        'tips' => '演示 30 分钟粒度与标准分隔符的组合',
        'value' => '2026-06-02 10:00 ~ 2026-06-02 12:00',
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

Field::datetimeRange('meeting_period', '会议时间段', '演示 30 分钟粒度与标准分隔符的组合')
    ->value('2026-06-02 10:00 ~ 2026-06-02 12:00')
    ->options([
        'dateFormat' => 'yyyy-MM-dd HH:mm',
        'timeFormat' => 'HH:mm',
        'minutesStep' => 30,
        'multipleDatesSeparator' => ' ~ ',
    ]);
CODE,
            'notes' => [
                '如果后端需要拆分开始/结束时间字段，建议封装统一解析器，而不是在控制器里直接 `explode()`。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/datetime_range/DatetimeRangeFormatSection.php',
                    'label' => '范围格式与分隔符能力块',
                    'description' => '展示 datetime_range 在范围字符串格式与分隔符控制上的典型组合。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_datetime_range_section_format_', false), '范围格式与分隔符')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item([
                'type' => 'datetime_range',
                'name' => 'meeting_period',
                'label' => '会议时间段',
                'tips' => '演示 30 分钟粒度与标准分隔符的组合',
                'value' => '2026-06-02 10:00 ~ 2026-06-02 12:00',
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
