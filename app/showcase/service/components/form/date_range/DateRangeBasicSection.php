<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\date_range;

use app\common\render\Form;
use app\common\render\form\items\date_range\DateRange;

/**
 * date_range 基础用法能力块
 */
final class DateRangeBasicSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'basic'),
            'title' => (string) ($section['title'] ?? '基础用法'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'name', 'value' => 'valid_period / service_period'],
                ['name' => 'label', 'value' => '有效期 / 服务周期'],
            ],
            'array_code' => <<<'CODE'
[
    ['date_range', 'valid_period', '有效期', '请选择有效期'],
    ['date_range', 'service_period', '服务周期', '请选择服务周期'],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::dateRange('valid_period', '有效期', '请选择有效期');
Field::dateRange('service_period', '服务周期', '请选择服务周期');
CODE,
            'notes' => [
                '基础 date_range 适合最常见的单字段起止日期录入场景。',
                '底层自动开启 `range=true` 且关闭时间选择，不需要开发者额外设置。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/date_range/DateRangeBasicSection.php',
                    'label' => '基础用法能力块',
                    'description' => '组装 date_range 的基础示例与代码片段。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_date_range_section_basic_', false), '基础用法')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(DateRange::make('valid_period', '有效期', '请选择有效期')->placeholder('例如 2026-06-01 ~ 2026-06-30'))
            ->item(DateRange::make('service_period', '服务周期', '请选择服务周期')->placeholder('例如 2026-07-01 ~ 2026-07-15'))
            ->fetch();
    }
}
