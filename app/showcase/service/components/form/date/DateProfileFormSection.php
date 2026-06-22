<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\date;

use app\common\render\Form;
use app\common\render\form\items\date\Date;

/**
 * date 业务综合示例能力块
 */
final class DateProfileFormSection
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
                ['name' => 'required + autoClose', 'value' => '适合活动开始、上线日期等关键时间点字段'],
                ['name' => 'default value + disabled', 'value' => '适合审批后锁定的截止日期'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'date',
        'name' => 'campaign_start',
        'label' => '活动开始日期',
        'tips' => '活动开始后前台才会展示报名入口',
        'required' => true,
        'options' => [
            'dateFormat' => 'yyyy-MM-dd',
            'autoClose' => true,
        ],
    ],
    [
        'type' => 'date',
        'name' => 'campaign_end',
        'label' => '活动结束日期',
        'value' => '2026-06-30',
        'disabled' => true,
        'tips' => '审批通过后自动锁定',
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::date('campaign_start', '活动开始日期', '活动开始后前台才会展示报名入口')
    ->required()
    ->options([
        'dateFormat' => 'yyyy-MM-dd',
        'autoClose' => true,
    ]);

Field::date('campaign_end', '活动结束日期', '审批通过后自动锁定')
    ->value('2026-06-30')
    ->disabled();
CODE,
            'notes' => [
                '业务示例更适合开发者直接替换字段名和日期语义后落到自己的表单里。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/date/DateProfileFormSection.php',
                    'label' => '业务综合示例能力块',
                    'description' => '把常见 date 用法组合成真实业务表单片段。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_date_section_profile_', false), '业务表单片段')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                Date::make('campaign_start', '活动开始日期', '活动开始后前台才会展示报名入口')
                    ->required()
                    ->options([
                        'dateFormat' => 'yyyy-MM-dd',
                        'autoClose' => true,
                    ])
            )
            ->item(
                Date::make('campaign_end', '活动结束日期', '审批通过后自动锁定')
                    ->value('2026-06-30')
                    ->disabled()
            )
            ->fetch();
    }
}
