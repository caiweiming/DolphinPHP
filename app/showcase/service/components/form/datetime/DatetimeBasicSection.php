<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\datetime;

use app\common\render\Form;
use app\common\render\form\items\datetime\Datetime;

/**
 * datetime 基础用法能力块
 */
final class DatetimeBasicSection
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
                ['name' => 'name', 'value' => 'publish_at / appointment_at'],
                ['name' => 'label', 'value' => '发布时间 / 预约时间'],
            ],
            'array_code' => <<<'CODE'
[
    ['datetime', 'publish_at', '发布时间', '请选择发布时间'],
    ['datetime', 'appointment_at', '预约时间', '请选择预约时间'],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::datetime('publish_at', '发布时间', '请选择发布时间');
Field::datetime('appointment_at', '预约时间', '请选择预约时间');
CODE,
            'notes' => [
                '基础 datetime 适合最常见的单个时间点录入场景。',
                '底层默认开启时间选择，不需要开发者手动补 `timepicker=true`。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/datetime/DatetimeBasicSection.php',
                    'label' => '基础用法能力块',
                    'description' => '组装 datetime 的基础示例与代码片段。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_datetime_section_basic_', false), '基础用法')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(Datetime::make('publish_at', '发布时间', '请选择发布时间')->placeholder('例如 2026-06-01 09:30'))
            ->item(Datetime::make('appointment_at', '预约时间', '请选择预约时间')->placeholder('例如 2026-06-01 14:00'))
            ->fetch();
    }
}
