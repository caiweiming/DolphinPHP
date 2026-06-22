<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\datetime_range;

use app\common\render\Form;
use app\common\render\form\items\datetime_range\DatetimeRange;

/**
 * datetime_range 默认值与回填能力块
 */
final class DatetimeRangeDefaultValueSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'default_value'),
            'title' => (string) ($section['title'] ?? '默认值与回填'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'value', 'value' => '编辑态常用为完整范围字符串回显'],
                ['name' => 'options.multipleDatesSeparator', 'value' => '确保回显字符串和分隔符一致'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'datetime_range',
        'name' => 'event_period',
        'label' => '活动时间段',
        'value' => '2026-06-01 09:00 ~ 2026-06-01 18:00',
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

Field::datetimeRange('event_period', '活动时间段')
    ->value('2026-06-01 09:00 ~ 2026-06-01 18:00')
    ->options([
        'dateFormat' => 'yyyy-MM-dd HH:mm',
        'timeFormat' => 'HH:mm',
        'multipleDatesSeparator' => ' ~ ',
    ]);
CODE,
            'notes' => [
                '范围字段回填时，后端生成的字符串格式必须和前端分隔符配置保持一致。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/datetime_range/DatetimeRangeDefaultValueSection.php',
                    'label' => '默认值与回填能力块',
                    'description' => '展示 datetime_range 在编辑态场景下的回显写法。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_datetime_range_section_value_', false), '默认值与回填')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                DatetimeRange::make('event_period', '活动时间段')
                    ->value('2026-06-01 09:00 ~ 2026-06-01 18:00')
                    ->options([
                        'dateFormat' => 'yyyy-MM-dd HH:mm',
                        'timeFormat' => 'HH:mm',
                        'multipleDatesSeparator' => ' ~ ',
                    ])
            )
            ->fetch();
    }
}
