<?php
declare(strict_types=1);

namespace app\showcase\service\components\form;

/**
 * Showcase datetime_range 组件页组装器
 */
final class DatetimeRangeComponentPage
{
    /**
     * @param array<string, mixed> $component
     * @param list<array<string, string>> $sectionCatalog
     * @return array<string, mixed>
     */
    public function build(array $component, array $sectionCatalog): array
    {
        $sections = [];

        foreach ($sectionCatalog as $sectionMeta) {
            $builderClass = (string) ($sectionMeta['builder'] ?? '');

            if ($builderClass === '') {
                continue;
            }

            $sections[] = app($builderClass)->build($component, $sectionMeta);
        }

        return [
            'component' => $component,
            'overview' => [
                'summary' => 'DatetimeRange 组件用于承载单字段的日期时间区间输入，适合预约时段、活动周期、报名窗口等需要同时表达开始和结束时间的后台表单场景。',
                'scenarios' => [
                    '预约时段、活动周期、报名窗口等需要录入开始与结束时间的场景',
                    '希望把时间范围保存在一个字段内，并沿用统一日期时间选择交互的后台表单',
                    '需要通过原生 options 控制范围格式、分隔符、分钟粒度与自动关闭行为的场景',
                ],
                'capabilities' => [
                    '基础用法',
                    '占位符与图标',
                    '默认值与回填',
                    '样式变体',
                    '只读与禁用',
                    '原生 options 配置',
                    '范围格式与分隔符',
                    '业务表单片段',
                ],
                'quick_start' => [
                    'array_code' => <<<'CODE'
[
    [
        'type' => 'datetime_range',
        'name' => 'booking_period',
        'label' => '预约时间段',
        'tips' => '请选择预约时间段',
        'placeholder' => '请选择预约时间段',
        'value' => '2026-06-01 09:00 ~ 2026-06-01 18:00',
        'options' => [
            'dateFormat' => 'yyyy-MM-dd HH:mm',
            'timeFormat' => 'HH:mm',
            'multipleDatesSeparator' => ' ~ ',
        ],
    ],
]
CODE,
                    'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::datetimeRange('booking_period', '预约时间段', '请选择预约时间段')
    ->value('2026-06-01 09:00 ~ 2026-06-01 18:00')
    ->placeholder('请选择预约时间段')
    ->options([
        'dateFormat' => 'yyyy-MM-dd HH:mm',
        'timeFormat' => 'HH:mm',
        'multipleDatesSeparator' => ' ~ ',
    ]);
CODE,
                ],
            ],
            'param_groups' => $this->paramGroups(),
            'sections' => $sections,
            'related_components' => [
                ['key' => 'datetime.datetime', 'title' => 'datetime 日期时间选择器', 'status' => 'available'],
                ['key' => 'datetime.date_range', 'title' => 'date_range 日期范围选择器', 'status' => 'available'],
                ['key' => 'datetime.time', 'title' => 'time 时间选择器', 'status' => 'available'],
            ],
            'doc_links' => (array) ($component['doc_links'] ?? []),
            'sidebar_source_refs' => array_slice($this->mergeSourceRefs((array) ($component['source_refs'] ?? []), $sections), 0, 3),
            'source_refs' => $this->mergeSourceRefs((array) ($component['source_refs'] ?? []), $sections),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function paramGroups(): array
    {
        return [
            [
                'title' => '基础参数',
                'items' => [
                    ['name' => 'name / label / tips', 'summary' => '字段名、标题与提示文案，定义时间范围字段的业务语义。'],
                    ['name' => 'value', 'summary' => '默认值或编辑态回显值，通常是按分隔符拼接的完整时间段字符串。'],
                    ['name' => 'placeholder', 'summary' => '未选择时间段时的引导文案。'],
                    ['name' => 'required', 'summary' => '标记为必填时间范围输入。'],
                ],
            ],
            [
                'title' => '展示与状态',
                'items' => [
                    ['name' => 'icon', 'summary' => '控制日期时间图标位于左侧或右侧。'],
                    ['name' => 'rounded / flush', 'summary' => '切换圆角或扁平输入样式。'],
                    ['name' => 'readonly / disabled', 'summary' => '控制是否允许手动查看或交互。'],
                    ['name' => 'size / class', 'summary' => '控制尺寸和自定义样式类。'],
                ],
            ],
            [
                'title' => '原生配置',
                'items' => [
                    ['name' => 'options.dateFormat', 'summary' => '控制范围值中的日期时间输出格式。'],
                    ['name' => 'options.timeFormat', 'summary' => '控制时间面板与输出中的时间部分格式。'],
                    ['name' => 'options.multipleDatesSeparator', 'summary' => '控制开始时间与结束时间之间的分隔符。'],
                    ['name' => 'options.minutesStep', 'summary' => '控制分钟步进粒度。'],
                ],
            ],
            [
                'title' => '选型建议',
                'items' => [
                    ['name' => 'datetime_range / datetime', 'summary' => '单个时间点用 datetime；开始结束一体录入用 datetime_range。'],
                    ['name' => 'datetime_range / date_range', 'summary' => '如果只关心日期跨度，用 date_range 更直接。'],
                    ['name' => '范围存储', 'summary' => '如果后端拆分存储开始与结束时间，提交后要按分隔符进行解析。'],
                ],
            ],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $componentSources
     * @param list<array<string, mixed>> $sections
     * @return array<int, array<string, mixed>>
     */
    private function mergeSourceRefs(array $componentSources, array $sections): array
    {
        $result = $componentSources;
        $seen = [];

        foreach ($componentSources as $source) {
            $path = (string) ($source['path'] ?? '');
            if ($path !== '') {
                $seen[$path] = true;
            }
        }

        foreach ($sections as $section) {
            foreach ((array) ($section['source_refs'] ?? []) as $source) {
                $path = (string) ($source['path'] ?? '');

                if ($path === '' || isset($seen[$path])) {
                    continue;
                }

                $seen[$path] = true;
                $result[] = $source;
            }
        }

        return $result;
    }
}
