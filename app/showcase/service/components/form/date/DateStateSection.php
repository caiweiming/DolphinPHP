<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\date;

use app\common\render\Form;
use app\common\render\form\items\date\Date;

/**
 * date 只读与禁用能力块
 */
final class DateStateSection
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
                ['name' => 'readonly', 'value' => '允许查看当前日期，但禁止直接编辑'],
                ['name' => 'disabled', 'value' => '完全锁定字段交互，适合已生效记录'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'date',
        'name' => 'readonly_date',
        'label' => '只读日期',
        'value' => '2026-06-01',
        'readonly' => true,
    ],
    [
        'type' => 'date',
        'name' => 'disabled_date',
        'label' => '禁用日期',
        'value' => '2026-06-30',
        'disabled' => true,
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::date('readonly_date', '只读日期')
    ->value('2026-06-01')
    ->readonly();

Field::date('disabled_date', '禁用日期')
    ->value('2026-06-30')
    ->disabled();
CODE,
            'notes' => [
                '只读更适合“看得见但不能改”，禁用更适合“当前不参与交互”的业务状态。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/date/DateStateSection.php',
                    'label' => '只读与禁用能力块',
                    'description' => '展示 date 在 readonly 与 disabled 场景下的表现差异。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_date_section_state_', false), '只读与禁用')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                Date::make('readonly_date', '只读日期')
                    ->value('2026-06-01')
                    ->readonly()
            )
            ->item(
                Date::make('disabled_date', '禁用日期')
                    ->value('2026-06-30')
                    ->disabled()
            )
            ->fetch();
    }
}
