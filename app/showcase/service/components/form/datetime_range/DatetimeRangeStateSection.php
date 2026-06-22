<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\datetime_range;

use app\common\render\Form;
use app\common\render\form\items\datetime_range\DatetimeRange;

/**
 * datetime_range 只读与禁用能力块
 */
final class DatetimeRangeStateSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'state'),
            'title' => (string) ($section['title'] ?? '只读与禁用'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'readonly', 'value' => '允许查看当前时间段，但禁止直接修改'],
                ['name' => 'disabled', 'value' => '完全锁定字段交互，适合审批后冻结时段'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'datetime_range',
        'name' => 'readonly_period',
        'label' => '只读时间段',
        'value' => '2026-06-01 09:00 ~ 2026-06-01 18:00',
        'readonly' => true,
    ],
    [
        'type' => 'datetime_range',
        'name' => 'disabled_period',
        'label' => '禁用时间段',
        'value' => '2026-06-02 10:00 ~ 2026-06-02 12:00',
        'disabled' => true,
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::datetimeRange('readonly_period', '只读时间段')
    ->value('2026-06-01 09:00 ~ 2026-06-01 18:00')
    ->readonly();

Field::datetimeRange('disabled_period', '禁用时间段')
    ->value('2026-06-02 10:00 ~ 2026-06-02 12:00')
    ->disabled();
CODE,
            'notes' => [
                '只读和禁用的区别在于前者仍可聚焦和复制内容，后者则完全不参与交互。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/datetime_range/DatetimeRangeStateSection.php',
                    'label' => '只读与禁用能力块',
                    'description' => '展示 datetime_range 在 readonly 与 disabled 场景下的表现差异。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_datetime_range_section_state_', false), '只读与禁用')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                DatetimeRange::make('readonly_period', '只读时间段')
                    ->value('2026-06-01 09:00 ~ 2026-06-01 18:00')
                    ->readonly()
            )
            ->item(
                DatetimeRange::make('disabled_period', '禁用时间段')
                    ->value('2026-06-02 10:00 ~ 2026-06-02 12:00')
                    ->disabled()
            )
            ->fetch();
    }
}
