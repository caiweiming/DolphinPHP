<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\datetime_range;

use app\common\render\Form;
use app\common\render\form\items\datetime_range\DatetimeRange;

/**
 * datetime_range 样式变体能力块
 */
final class DatetimeRangeStyleSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'style'),
            'title' => (string) ($section['title'] ?? '样式变体'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'rounded', 'value' => '圆角范围输入，更适合偏轻量的筛选或设置页面'],
                ['name' => 'flush', 'value' => '扁平输入，更适合嵌入卡片或复杂业务表单'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'datetime_range',
        'name' => 'rounded_period',
        'label' => '圆角时间段',
        'rounded' => true,
    ],
    [
        'type' => 'datetime_range',
        'name' => 'flush_period',
        'label' => '扁平时间段',
        'flush' => true,
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::datetimeRange('rounded_period', '圆角时间段')->rounded();
Field::datetimeRange('flush_period', '扁平时间段')->flush();
CODE,
            'notes' => [
                '视觉样式不会影响范围选择逻辑，但会直接影响页面整体密度和识别感。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/datetime_range/DatetimeRangeStyleSection.php',
                    'label' => '样式变体能力块',
                    'description' => '展示 datetime_range 在 rounded 与 flush 风格下的表现。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_datetime_range_section_style_', false), '样式变体')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(DatetimeRange::make('rounded_period', '圆角时间段')->rounded())
            ->item(DatetimeRange::make('flush_period', '扁平时间段')->flush())
            ->fetch();
    }
}
