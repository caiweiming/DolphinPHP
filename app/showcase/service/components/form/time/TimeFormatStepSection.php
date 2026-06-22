<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\time;

use app\common\render\Form;

/**
 * time 时间格式与步进能力块
 */
final class TimeFormatStepSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'format_step'),
            'title' => (string) ($section['title'] ?? '时间格式与步进'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'timeFormat', 'value' => '常见为 `HH:mm`，也可扩展到秒级格式'],
                ['name' => 'hoursStep / minutesStep', 'value' => '适合控制整点、半点、15 分钟等业务粒度'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'time',
        'name' => 'meeting_time',
        'label' => '会议时间',
        'tips' => '演示 30 分钟粒度的预约场景',
        'value' => '09:00',
        'options' => [
            'timeFormat' => 'HH:mm',
            'minutesStep' => 30,
        ],
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::time('meeting_time', '会议时间', '演示 30 分钟粒度的预约场景')
    ->value('09:00')
    ->options([
        'timeFormat' => 'HH:mm',
        'minutesStep' => 30,
    ]);
CODE,
            'notes' => [
                '时间步进适合把用户输入约束到业务允许的粒度，比如整点、半点或 15 分钟。',
                '只要修改了 timeFormat、hoursStep 或 minutesStep，后端解析和营业时间校验也要同步按同一套格式与步进执行。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/time/TimeFormatStepSection.php',
                    'label' => '时间格式与步进能力块',
                    'description' => '展示 time 组件里输出格式和步进粒度的典型组合。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_time_section_format_', false), '时间格式与步进')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item([
                'type' => 'time',
                'name' => 'meeting_time',
                'label' => '会议时间',
                'tips' => '演示 30 分钟粒度的预约场景',
                'value' => '09:00',
                'options' => [
                    'timeFormat' => 'HH:mm',
                    'minutesStep' => 30,
                ],
            ])
            ->fetch();
    }
}
