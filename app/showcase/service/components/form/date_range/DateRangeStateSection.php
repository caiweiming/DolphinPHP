<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\date_range;

use app\common\render\Form;
use app\common\render\form\items\date_range\DateRange;

/**
 * date_range 只读与禁用能力块
 */
final class DateRangeStateSection
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
                ['name' => 'readonly', 'value' => '允许查看当前日期范围，但禁止直接修改'],
                ['name' => 'disabled', 'value' => '完全锁定字段交互，适合已生效或已归档记录'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'date_range',
        'name' => 'readonly_period',
        'label' => '只读日期范围',
        'value' => '2026-06-01 ~ 2026-06-30',
        'readonly' => true,
    ],
    [
        'type' => 'date_range',
        'name' => 'disabled_period',
        'label' => '禁用日期范围',
        'value' => '2026-07-01 ~ 2026-07-15',
        'disabled' => true,
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::dateRange('readonly_period', '只读日期范围')
    ->value('2026-06-01 ~ 2026-06-30')
    ->readonly();

Field::dateRange('disabled_period', '禁用日期范围')
    ->value('2026-07-01 ~ 2026-07-15')
    ->disabled();
CODE,
            'notes' => [
                '只读和禁用的区别在于前者仍可查看和复制，后者则完全不参与交互。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/date_range/DateRangeStateSection.php',
                    'label' => '只读与禁用能力块',
                    'description' => '展示 date_range 在 readonly 与 disabled 场景下的表现差异。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_date_range_section_state_', false), '只读与禁用')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                DateRange::make('readonly_period', '只读日期范围')
                    ->value('2026-06-01 ~ 2026-06-30')
                    ->readonly()
            )
            ->item(
                DateRange::make('disabled_period', '禁用日期范围')
                    ->value('2026-07-01 ~ 2026-07-15')
                    ->disabled()
            )
            ->fetch();
    }
}
