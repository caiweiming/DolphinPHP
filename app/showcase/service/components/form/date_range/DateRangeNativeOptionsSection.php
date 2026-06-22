<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\date_range;

use app\common\render\Form;

/**
 * date_range 原生 options 能力块
 */
final class DateRangeNativeOptionsSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'native_options'),
            'title' => (string) ($section['title'] ?? '原生 options 配置'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'options.dateFormat', 'value' => '控制输入框输出的完整日期范围格式'],
                ['name' => 'options.multipleDatesSeparator', 'value' => '控制开始和结束日期之间的分隔符'],
                ['name' => 'options.autoClose', 'value' => '选择完成后自动关闭面板'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'date_range',
        'name' => 'delivery_period',
        'label' => '配送周期',
        'tips' => '演示 AirDatepicker 原生日期范围配置透传',
        'options' => [
            'dateFormat' => 'yyyy-MM-dd',
            'multipleDatesSeparator' => ' ~ ',
            'autoClose' => true,
        ],
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::dateRange('delivery_period', '配送周期', '演示 AirDatepicker 原生日期范围配置透传')
    ->options([
        'dateFormat' => 'yyyy-MM-dd',
        'multipleDatesSeparator' => ' ~ ',
        'autoClose' => true,
    ]);
CODE,
            'notes' => [
                '日期范围字段的解析稳定性，取决于前后端是否统一了分隔符和输出格式。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/date_range/DateRangeNativeOptionsSection.php',
                    'label' => '原生 options 能力块',
                    'description' => '展示 date_range 如何透传 AirDatepicker 原生范围配置。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_date_range_section_options_', false), '原生 options 配置')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item([
                'type' => 'date_range',
                'name' => 'delivery_period',
                'label' => '配送周期',
                'tips' => '演示 AirDatepicker 原生日期范围配置透传',
                'options' => [
                    'dateFormat' => 'yyyy-MM-dd',
                    'multipleDatesSeparator' => ' ~ ',
                    'autoClose' => true,
                ],
            ])
            ->fetch();
    }
}
