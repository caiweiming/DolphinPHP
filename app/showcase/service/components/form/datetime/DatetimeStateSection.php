<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\datetime;

use app\common\render\Form;
use app\common\render\form\items\datetime\Datetime;

/**
 * datetime 只读与禁用能力块
 */
final class DatetimeStateSection
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
                ['name' => 'readonly', 'value' => '允许查看当前日期时间，但禁止直接编辑'],
                ['name' => 'disabled', 'value' => '完全锁定字段交互，适合审批后冻结时间点'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'datetime',
        'name' => 'readonly_datetime',
        'label' => '只读时间点',
        'value' => '2026-06-01 09:30',
        'readonly' => true,
    ],
    [
        'type' => 'datetime',
        'name' => 'disabled_datetime',
        'label' => '禁用时间点',
        'value' => '2026-06-01 18:00',
        'disabled' => true,
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::datetime('readonly_datetime', '只读时间点')
    ->value('2026-06-01 09:30')
    ->readonly();

Field::datetime('disabled_datetime', '禁用时间点')
    ->value('2026-06-01 18:00')
    ->disabled();
CODE,
            'notes' => [
                '只读更适合“看得见但不能改”，禁用更适合“当前不参与交互”的业务状态。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/datetime/DatetimeStateSection.php',
                    'label' => '只读与禁用能力块',
                    'description' => '展示 datetime 在 readonly 与 disabled 场景下的表现差异。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_datetime_section_state_', false), '只读与禁用')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                Datetime::make('readonly_datetime', '只读时间点')
                    ->value('2026-06-01 09:30')
                    ->readonly()
            )
            ->item(
                Datetime::make('disabled_datetime', '禁用时间点')
                    ->value('2026-06-01 18:00')
                    ->disabled()
            )
            ->fetch();
    }
}
