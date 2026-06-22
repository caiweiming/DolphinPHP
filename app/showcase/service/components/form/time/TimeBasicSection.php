<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\time;

use app\common\render\Form;
use app\common\render\form\items\time\Time;

/**
 * time 基础用法能力块
 */
final class TimeBasicSection
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
                ['name' => 'name', 'value' => 'open_time / close_time'],
                ['name' => 'label', 'value' => '营业开始 / 营业结束'],
            ],
            'array_code' => <<<'CODE'
[
    ['time', 'open_time', '营业开始时间', '请选择营业开始时间'],
    ['time', 'close_time', '营业结束时间', '请选择营业结束时间'],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::time('open_time', '营业开始时间', '请选择营业开始时间');
Field::time('close_time', '营业结束时间', '请选择营业结束时间');
CODE,
            'notes' => [
                '基础 time 适合最常见的营业、提醒、预约等纯时间录入场景。',
                '底层会自动开启 `onlyTimepicker`，开发者不用手动关闭日期面板。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/time/TimeBasicSection.php',
                    'label' => '基础用法能力块',
                    'description' => '组装 time 的基础示例与代码片段。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_time_section_basic_', false), '基础用法')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(Time::make('open_time', '营业开始时间', '请选择营业开始时间')->placeholder('例如 09:00'))
            ->item(Time::make('close_time', '营业结束时间', '请选择营业结束时间')->id('close_time_basic')->placeholder('例如 18:00'))
            ->fetch();
    }
}
