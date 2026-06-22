<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\datetime;

use app\common\render\Form;

/**
 * datetime 日期时间格式与粒度能力块
 */
final class DatetimeFormatGranularitySection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'format_granularity'),
            'title' => (string) ($section['title'] ?? '日期时间格式与粒度'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'dateFormat + timeFormat', 'value' => '控制最终输出字符串和面板展示的日期时间格式'],
                ['name' => 'minutesStep', 'value' => '适合控制 10 分钟、15 分钟、30 分钟等业务粒度'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'datetime',
        'name' => 'meeting_at',
        'label' => '会议开始时间',
        'tips' => '演示 30 分钟粒度的会议预约场景',
        'value' => '2026-06-01 14:00',
        'options' => [
            'dateFormat' => 'yyyy-MM-dd HH:mm',
            'timeFormat' => 'HH:mm',
            'minutesStep' => 30,
        ],
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::datetime('meeting_at', '会议开始时间', '演示 30 分钟粒度的会议预约场景')
    ->value('2026-06-01 14:00')
    ->options([
        'dateFormat' => 'yyyy-MM-dd HH:mm',
        'timeFormat' => 'HH:mm',
        'minutesStep' => 30,
    ]);
CODE,
            'notes' => [
                '分钟步进适合把用户输入约束到业务允许的粒度，比如整点、半点或 15 分钟。',
                '如果你调整了 dateFormat、timeFormat 或 minutesStep，后端解析和校验也要使用同一套时间格式与粒度约束。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/datetime/DatetimeFormatGranularitySection.php',
                    'label' => '日期时间格式与粒度能力块',
                    'description' => '展示 datetime 组件里输出格式和时间粒度的典型组合。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_datetime_section_format_', false), '日期时间格式与粒度')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item([
                'type' => 'datetime',
                'name' => 'meeting_at',
                'label' => '会议开始时间',
                'tips' => '演示 30 分钟粒度的会议预约场景',
                'value' => '2026-06-01 14:00',
                'options' => [
                    'dateFormat' => 'yyyy-MM-dd HH:mm',
                    'timeFormat' => 'HH:mm',
                    'minutesStep' => 30,
                ],
            ])
            ->fetch();
    }
}
