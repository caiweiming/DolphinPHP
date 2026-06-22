<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\date_range;

use app\common\render\Form;
use app\common\render\form\items\date_range\DateRange;

/**
 * date_range 样式变体能力块
 */
final class DateRangeStyleSection
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
                ['name' => 'rounded', 'value' => '圆角范围输入，更适合轻量设置或筛选页面'],
                ['name' => 'flush', 'value' => '扁平输入，更适合嵌入密集型业务表单'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'date_range',
        'name' => 'rounded_period',
        'label' => '圆角日期范围',
        'rounded' => true,
    ],
    [
        'type' => 'date_range',
        'name' => 'flush_period',
        'label' => '扁平日期范围',
        'flush' => true,
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::dateRange('rounded_period', '圆角日期范围')->rounded();
Field::dateRange('flush_period', '扁平日期范围')->flush();
CODE,
            'notes' => [
                '视觉样式不会影响日期范围逻辑，但会影响页面密度和层级感。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/date_range/DateRangeStyleSection.php',
                    'label' => '样式变体能力块',
                    'description' => '展示 date_range 在 rounded 与 flush 风格下的表现。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_date_range_section_style_', false), '样式变体')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(DateRange::make('rounded_period', '圆角日期范围')->rounded())
            ->item(DateRange::make('flush_period', '扁平日期范围')->flush())
            ->fetch();
    }
}
