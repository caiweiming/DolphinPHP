<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\time;

use app\common\render\Form;
use app\common\render\form\items\time\Time;

/**
 * time 默认值与回填能力块
 */
final class TimeDefaultValueSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'default_value'),
            'title' => (string) ($section['title'] ?? '默认值与回填'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'value', 'value' => '传入字符串时用于默认时间或编辑态回显'],
                ['name' => 'selectedDates', 'value' => '底层会自动根据 value 同步到时间面板回显状态'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'time',
        'name' => 'close_time',
        'label' => '结束营业时间',
        'tips' => '编辑门店信息时回显当前结束营业时间',
        'value' => '18:00',
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::time('close_time', '结束营业时间', '编辑门店信息时回显当前结束营业时间')
    ->value('18:00');
CODE,
            'notes' => [
                '编辑页最常见的 time 场景就是回显数据库中的单个时间值。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/time/TimeDefaultValueSection.php',
                    'label' => '默认值与回填能力块',
                    'description' => '展示 time 如何通过 value 做默认值或编辑态回显。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_time_section_default_', false), '默认值与回填')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                Time::make('close_time', '结束营业时间', '编辑门店信息时回显当前结束营业时间')
                    ->id('close_time_default_value')
                    ->value('18:00')
            )
            ->fetch();
    }
}
