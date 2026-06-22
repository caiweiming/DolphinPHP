<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\date_range;

use app\common\render\Form;
use app\common\render\form\items\date_range\DateRange;

/**
 * date_range 占位符与图标能力块
 */
final class DateRangePlaceholderIconSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'placeholder_icon'),
            'title' => (string) ($section['title'] ?? '占位符与图标'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'placeholder', 'value' => '明确告诉用户要录入完整日期范围'],
                ['name' => 'icon', 'value' => '控制日期范围图标位于左侧或右侧'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'date_range',
        'name' => 'sale_period',
        'label' => '售卖日期',
        'placeholder' => '请选择售卖开始和结束日期',
        'icon' => 'left',
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::dateRange('sale_period', '售卖日期')
    ->placeholder('请选择售卖开始和结束日期')
    ->icon('left');
CODE,
            'notes' => [
                '日期范围字段的 placeholder 建议直接写出开始和结束语义，避免用户误以为只选单天。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/date_range/DateRangePlaceholderIconSection.php',
                    'label' => '占位符与图标能力块',
                    'description' => '展示 date_range 在 placeholder 与 icon 组合下的配置方式。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_date_range_section_placeholder_', false), '占位符与图标')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                DateRange::make('sale_period', '售卖日期')
                    ->placeholder('请选择售卖开始和结束日期')
                    ->icon('left')
            )
            ->fetch();
    }
}
