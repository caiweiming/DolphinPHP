<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\datetime;

use app\common\render\Form;
use app\common\render\form\items\datetime\Datetime;

/**
 * datetime 默认值与回填能力块
 */
final class DatetimeDefaultValueSection
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
                ['name' => 'value', 'value' => '传入字符串时用于默认日期时间或编辑态回显'],
                ['name' => 'selectedDates', 'value' => '底层会自动根据 value 同步到日期时间面板回显状态'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'datetime',
        'name' => 'start_at',
        'label' => '开始时间',
        'tips' => '编辑活动时回显当前开始时间',
        'value' => '2026-06-01 09:30',
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::datetime('start_at', '开始时间', '编辑活动时回显当前开始时间')
    ->value('2026-06-01 09:30');
CODE,
            'notes' => [
                '编辑页最常见的 datetime 场景就是回显数据库中的完整日期时间值。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/datetime/DatetimeDefaultValueSection.php',
                    'label' => '默认值与回填能力块',
                    'description' => '展示 datetime 如何通过 value 做默认值或编辑态回显。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_datetime_section_default_', false), '默认值与回填')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                Datetime::make('start_at', '开始时间', '编辑活动时回显当前开始时间')
                    ->value('2026-06-01 09:30')
            )
            ->fetch();
    }
}
