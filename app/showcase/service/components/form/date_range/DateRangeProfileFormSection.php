<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\date_range;

use app\common\render\Form;
use app\common\render\form\items\date_range\DateRange;
use app\common\render\form\items\text\Text;

/**
 * date_range 业务表单片段能力块
 */
final class DateRangeProfileFormSection
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
                ['name' => 'register_period', 'value' => '报名日期范围'],
                ['name' => 'display_period', 'value' => '展示日期范围'],
            ],
            'array_code' => <<<'CODE'
[
    ['text', 'activity_name', '活动名称', '请输入活动名称'],
    [
        'type' => 'date_range',
        'name' => 'register_period',
        'label' => '报名日期',
        'tips' => '控制用户可以报名的起止日期',
        'options' => [
            'dateFormat' => 'yyyy-MM-dd',
            'multipleDatesSeparator' => ' ~ ',
        ],
    ],
    [
        'type' => 'date_range',
        'name' => 'display_period',
        'label' => '展示日期',
        'tips' => '控制活动前台展示的日期窗口',
        'options' => [
            'dateFormat' => 'yyyy-MM-dd',
            'multipleDatesSeparator' => ' ~ ',
        ],
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::text('activity_name', '活动名称', '请输入活动名称');

Field::dateRange('register_period', '报名日期', '控制用户可以报名的起止日期')
    ->options([
        'dateFormat' => 'yyyy-MM-dd',
        'multipleDatesSeparator' => ' ~ ',
    ]);

Field::dateRange('display_period', '展示日期', '控制活动前台展示的日期窗口')
    ->options([
        'dateFormat' => 'yyyy-MM-dd',
        'multipleDatesSeparator' => ' ~ ',
    ]);
CODE,
            'notes' => [
                '业务表单里常见的是多个日期范围配合文本字段一起使用，用于覆盖不同的运营周期。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/date_range/DateRangeProfileFormSection.php',
                    'label' => '业务表单片段能力块',
                    'description' => '展示 date_range 在真实业务表单中的组合方式。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_date_range_section_profile_', false), '业务表单片段')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(Text::make('activity_name', '活动名称', '请输入活动名称'))
            ->item(
                DateRange::make('register_period', '报名日期', '控制用户可以报名的起止日期')
                    ->options([
                        'dateFormat' => 'yyyy-MM-dd',
                        'multipleDatesSeparator' => ' ~ ',
                    ])
            )
            ->item(
                DateRange::make('display_period', '展示日期', '控制活动前台展示的日期窗口')
                    ->options([
                        'dateFormat' => 'yyyy-MM-dd',
                        'multipleDatesSeparator' => ' ~ ',
                    ])
            )
            ->fetch();
    }
}
