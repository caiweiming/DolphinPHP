<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\datetime_range;

use app\common\render\Form;
use app\common\render\form\items\datetime_range\DatetimeRange;
use app\common\render\form\items\text\Text;

/**
 * datetime_range 业务表单片段能力块
 */
final class DatetimeRangeProfileFormSection
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
                ['name' => 'register_period', 'value' => '报名起止时间'],
                ['name' => 'display_period', 'value' => '前台展示时间段'],
            ],
            'array_code' => <<<'CODE'
[
    ['text', 'activity_name', '活动名称', '请输入活动名称'],
    [
        'type' => 'datetime_range',
        'name' => 'register_period',
        'label' => '报名时间段',
        'tips' => '控制用户可以报名的起止时间',
        'options' => [
            'dateFormat' => 'yyyy-MM-dd HH:mm',
            'timeFormat' => 'HH:mm',
            'multipleDatesSeparator' => ' ~ ',
        ],
    ],
    [
        'type' => 'datetime_range',
        'name' => 'display_period',
        'label' => '展示时间段',
        'tips' => '控制活动前台展示的时间窗口',
        'options' => [
            'dateFormat' => 'yyyy-MM-dd HH:mm',
            'timeFormat' => 'HH:mm',
            'multipleDatesSeparator' => ' ~ ',
        ],
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::text('activity_name', '活动名称', '请输入活动名称');

Field::datetimeRange('register_period', '报名时间段', '控制用户可以报名的起止时间')
    ->options([
        'dateFormat' => 'yyyy-MM-dd HH:mm',
        'timeFormat' => 'HH:mm',
        'multipleDatesSeparator' => ' ~ ',
    ]);

Field::datetimeRange('display_period', '展示时间段', '控制活动前台展示的时间窗口')
    ->options([
        'dateFormat' => 'yyyy-MM-dd HH:mm',
        'timeFormat' => 'HH:mm',
        'multipleDatesSeparator' => ' ~ ',
    ]);
CODE,
            'notes' => [
                '业务表单里常见的是多个时间范围配合文本字段一起使用，用于覆盖不同的运营周期。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/datetime_range/DatetimeRangeProfileFormSection.php',
                    'label' => '业务表单片段能力块',
                    'description' => '展示 datetime_range 在真实业务表单中的组合方式。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_datetime_range_section_profile_', false), '业务表单片段')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(Text::make('activity_name', '活动名称', '请输入活动名称'))
            ->item(
                DatetimeRange::make('register_period', '报名时间段', '控制用户可以报名的起止时间')
                    ->options([
                        'dateFormat' => 'yyyy-MM-dd HH:mm',
                        'timeFormat' => 'HH:mm',
                        'multipleDatesSeparator' => ' ~ ',
                    ])
            )
            ->item(
                DatetimeRange::make('display_period', '展示时间段', '控制活动前台展示的时间窗口')
                    ->options([
                        'dateFormat' => 'yyyy-MM-dd HH:mm',
                        'timeFormat' => 'HH:mm',
                        'multipleDatesSeparator' => ' ~ ',
                    ])
            )
            ->fetch();
    }
}
