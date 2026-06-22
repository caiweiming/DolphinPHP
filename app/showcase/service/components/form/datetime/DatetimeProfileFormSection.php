<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\datetime;

use app\common\render\Form;
use app\common\render\form\items\datetime\Datetime;

/**
 * datetime 业务综合示例能力块
 */
final class DatetimeProfileFormSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'profile_form'),
            'title' => (string) ($section['title'] ?? '业务表单片段'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'required + autoClose', 'value' => '适合活动开始、上线发布时间等关键日期时间字段'],
                ['name' => 'default value + disabled', 'value' => '适合审批后锁定的结束时间点'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'datetime',
        'name' => 'event_start_at',
        'label' => '活动开始时间',
        'tips' => '活动开始后前台才会开放报名入口',
        'required' => true,
        'options' => [
            'dateFormat' => 'yyyy-MM-dd HH:mm',
            'timeFormat' => 'HH:mm',
            'autoClose' => true,
        ],
    ],
    [
        'type' => 'datetime',
        'name' => 'event_end_at',
        'label' => '活动结束时间',
        'value' => '2026-06-30 18:00',
        'disabled' => true,
        'tips' => '审批通过后自动锁定',
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::datetime('event_start_at', '活动开始时间', '活动开始后前台才会开放报名入口')
    ->required()
    ->options([
        'dateFormat' => 'yyyy-MM-dd HH:mm',
        'timeFormat' => 'HH:mm',
        'autoClose' => true,
    ]);

Field::datetime('event_end_at', '活动结束时间', '审批通过后自动锁定')
    ->value('2026-06-30 18:00')
    ->disabled();
CODE,
            'notes' => [
                '业务示例更适合开发者直接替换字段名和时间语义后落到自己的表单里。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/datetime/DatetimeProfileFormSection.php',
                    'label' => '业务综合示例能力块',
                    'description' => '把常见 datetime 用法组合成真实业务表单片段。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_datetime_section_profile_', false), '业务表单片段')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                Datetime::make('event_start_at', '活动开始时间', '活动开始后前台才会开放报名入口')
                    ->required()
                    ->options([
                        'dateFormat' => 'yyyy-MM-dd HH:mm',
                        'timeFormat' => 'HH:mm',
                        'autoClose' => true,
                    ])
            )
            ->item(
                Datetime::make('event_end_at', '活动结束时间', '审批通过后自动锁定')
                    ->value('2026-06-30 18:00')
                    ->disabled()
            )
            ->fetch();
    }
}
