<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\date;

use app\common\render\Form;
use app\common\render\form\items\date\Date;

/**
 * date 默认值与回填能力块
 */
final class DateDefaultValueSection
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
                ['name' => 'value', 'value' => '传入字符串时用于默认日期或编辑态回显'],
                ['name' => 'selectedDates', 'value' => '底层会自动根据 value 同步到 AirDatepicker 的回显状态'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'date',
        'name' => 'publish_date',
        'label' => '发布日期',
        'tips' => '编辑文章时回显当前发布日期',
        'value' => '2026-06-01',
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::date('publish_date', '发布日期', '编辑文章时回显当前发布日期')
    ->value('2026-06-01');
CODE,
            'notes' => [
                '编辑页最常见的 date 场景就是回显数据库中的单日期值。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/date/DateDefaultValueSection.php',
                    'label' => '默认值与回填能力块',
                    'description' => '展示 date 如何通过 value 做默认值或编辑态回显。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_date_section_default_', false), '默认值与回填')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                Date::make('publish_date', '发布日期', '编辑文章时回显当前发布日期')
                    ->value('2026-06-01')
            )
            ->fetch();
    }
}
