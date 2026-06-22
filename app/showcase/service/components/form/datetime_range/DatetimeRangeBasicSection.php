<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\datetime_range;

use app\common\render\Form;
use app\common\render\form\items\datetime_range\DatetimeRange;

/**
 * datetime_range 基础用法能力块
 */
final class DatetimeRangeBasicSection
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
                ['name' => 'name', 'value' => 'booking_period / service_period'],
                ['name' => 'label', 'value' => '预约时间段 / 服务时段'],
            ],
            'array_code' => <<<'CODE'
[
    ['datetime_range', 'booking_period', '预约时间段', '请选择预约时间段'],
    ['datetime_range', 'service_period', '服务时段', '请选择服务时段'],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::datetimeRange('booking_period', '预约时间段', '请选择预约时间段');
Field::datetimeRange('service_period', '服务时段', '请选择服务时段');
CODE,
            'notes' => [
                '基础 datetime_range 适合最常见的单字段起止时间录入场景。',
                '底层自动开启 `range=true`，不需要开发者手动补这个原生配置。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/datetime_range/DatetimeRangeBasicSection.php',
                    'label' => '基础用法能力块',
                    'description' => '组装 datetime_range 的基础示例与代码片段。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_datetime_range_section_basic_', false), '基础用法')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(DatetimeRange::make('booking_period', '预约时间段', '请选择预约时间段')->placeholder('例如 2026-06-01 09:00 ~ 2026-06-01 18:00'))
            ->item(DatetimeRange::make('service_period', '服务时段', '请选择服务时段')->placeholder('例如 2026-06-02 10:00 ~ 2026-06-02 12:00'))
            ->fetch();
    }
}
