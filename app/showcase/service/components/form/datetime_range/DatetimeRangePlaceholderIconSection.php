<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\datetime_range;

use app\common\render\Form;
use app\common\render\form\items\datetime_range\DatetimeRange;

/**
 * datetime_range 占位符与图标能力块
 */
final class DatetimeRangePlaceholderIconSection
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
                ['name' => 'placeholder', 'value' => '明确告诉用户要录入完整时间范围'],
                ['name' => 'icon', 'value' => '控制时间范围图标位于左侧或右侧'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'datetime_range',
        'name' => 'sale_period',
        'label' => '售卖时间段',
        'placeholder' => '请选择售卖开始和结束时间',
        'icon' => 'left',
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::datetimeRange('sale_period', '售卖时间段')
    ->placeholder('请选择售卖开始和结束时间')
    ->icon('left');
CODE,
            'notes' => [
                '时间范围字段的 placeholder 建议直接写成“开始时间 ~ 结束时间”的完整语义，减少误解。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/datetime_range/DatetimeRangePlaceholderIconSection.php',
                    'label' => '占位符与图标能力块',
                    'description' => '展示 datetime_range 在 placeholder 与 icon 组合下的配置方式。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_datetime_range_section_placeholder_', false), '占位符与图标')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                DatetimeRange::make('sale_period', '售卖时间段')
                    ->placeholder('请选择售卖开始和结束时间')
                    ->icon('left')
            )
            ->fetch();
    }
}
