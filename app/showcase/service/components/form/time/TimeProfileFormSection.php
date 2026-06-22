<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\time;

use app\common\render\Form;
use app\common\render\form\items\time\Time;

/**
 * time 业务综合示例能力块
 */
final class TimeProfileFormSection
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
                ['name' => 'required + minutesStep', 'value' => '适合营业开始、开放报名等关键时间点字段'],
                ['name' => 'default value + disabled', 'value' => '适合审核后锁定的结束时间'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'time',
        'name' => 'business_open_time',
        'label' => '营业开始时间',
        'tips' => '门店开始接单时间',
        'required' => true,
        'options' => [
            'timeFormat' => 'HH:mm',
            'minutesStep' => 15,
        ],
    ],
    [
        'type' => 'time',
        'name' => 'business_close_time',
        'label' => '营业结束时间',
        'value' => '22:00',
        'disabled' => true,
        'tips' => '审批通过后自动锁定',
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::time('business_open_time', '营业开始时间', '门店开始接单时间，按 15 分钟粒度预约')
    ->required()
    ->options([
        'timeFormat' => 'HH:mm',
        'minutesStep' => 15,
    ]);

Field::time('business_close_time', '营业结束时间', '审批通过后自动锁定')
    ->value('22:00')
    ->disabled();
CODE,
            'notes' => [
                '业务示例更适合开发者直接替换字段名和时间语义后落到自己的表单里。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/time/TimeProfileFormSection.php',
                    'label' => '业务综合示例能力块',
                    'description' => '把常见 time 用法组合成真实业务表单片段。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_time_section_profile_', false), '业务表单片段')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                Time::make('business_open_time', '营业开始时间', '门店开始接单时间')
                    ->required()
                    ->options([
                        'timeFormat' => 'HH:mm',
                        'minutesStep' => 15,
                    ])
            )
            ->item(
                Time::make('business_close_time', '营业结束时间', '审批通过后自动锁定')
                    ->value('22:00')
                    ->disabled()
            )
            ->fetch();
    }
}
