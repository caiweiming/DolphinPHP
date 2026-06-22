<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\date_range;

use app\common\render\Form;

/**
 * date_range 范围格式与分隔符能力块
 */
final class DateRangeFormatSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'range_format'),
            'title' => (string) ($section['title'] ?? '范围格式与分隔符'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'dateFormat', 'value' => '控制范围值中开始与结束日期的输出格式'],
                ['name' => 'multipleDatesSeparator', 'value' => '控制开始日期和结束日期在单字段中的拼接符号'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'date_range',
        'name' => 'campaign_period',
        'label' => '活动周期',
        'tips' => '演示标准日期格式与分隔符组合',
        'value' => '2026-06-05 ~ 2026-06-20',
        'options' => [
            'dateFormat' => 'yyyy-MM-dd',
            'multipleDatesSeparator' => ' ~ ',
        ],
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::dateRange('campaign_period', '活动周期', '演示标准日期格式与分隔符组合')
    ->value('2026-06-05 ~ 2026-06-20')
    ->options([
        'dateFormat' => 'yyyy-MM-dd',
        'multipleDatesSeparator' => ' ~ ',
    ]);
CODE,
            'notes' => [
                '如果后端需要拆分开始/结束日期，建议封装统一解析器，而不是在控制器里直接按字符串拆分。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/date_range/DateRangeFormatSection.php',
                    'label' => '范围格式与分隔符能力块',
                    'description' => '展示 date_range 在范围字符串格式与分隔符控制上的典型组合。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_date_range_section_format_', false), '范围格式与分隔符')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item([
                'type' => 'date_range',
                'name' => 'campaign_period',
                'label' => '活动周期',
                'tips' => '演示标准日期格式与分隔符组合',
                'value' => '2026-06-05 ~ 2026-06-20',
                'options' => [
                    'dateFormat' => 'yyyy-MM-dd',
                    'multipleDatesSeparator' => ' ~ ',
                ],
            ])
            ->fetch();
    }
}
