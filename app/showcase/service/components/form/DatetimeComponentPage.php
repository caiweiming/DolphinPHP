<?php
declare(strict_types=1);

namespace app\showcase\service\components\form;

/**
 * Showcase datetime 组件页组装器
 */
final class DatetimeComponentPage
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
                'summary' => 'Datetime 组件用于承载日期和时间的组合输入，适合活动开始时间、预约时间、发布时间等需要精确到时间点的后台表单场景。',
                'scenarios' => [
                    '活动开始时间、发布时间、预约时间等需要精确到时分的输入场景',
                    '希望在一个字段里完成日期和时间录入，而不是拆成两个输入框的后台表单',
                    '需要通过原生 options 控制日期时间格式、分钟粒度和自动关闭行为的场景',
                ],
                'capabilities' => [
                    '基础用法',
                    '占位符与图标',
                    '默认值与回填',
                    '样式变体',
                    '只读与禁用',
                    '原生 options 配置',
                    '日期时间格式与粒度',
                    '业务表单片段',
                ],
                'quick_start' => [
                    'array_code' => <<<'CODE'
[
    [
        'type' => 'datetime',
        'name' => 'publish_at',
        'label' => '发布时间',
        'tips' => '请选择发布时间',
        'placeholder' => '请选择发布时间',
        'value' => '2026-06-01 09:30',
        'options' => [
            'dateFormat' => 'yyyy-MM-dd HH:mm',
            'timeFormat' => 'HH:mm',
            'autoClose' => true,
        ],
    ],
]
CODE,
                    'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::datetime('publish_at', '发布时间', '请选择发布时间')
    ->value('2026-06-01 09:30')
    ->placeholder('请选择发布时间')
    ->options([
        'dateFormat' => 'yyyy-MM-dd HH:mm',
        'timeFormat' => 'HH:mm',
        'autoClose' => true,
    ]);
CODE,
                ],
            ],
            'param_groups' => $this->paramGroups(),
            'sections' => $sections,
            'related_components' => [
                ['key' => 'datetime.date', 'title' => 'date 日期选择器', 'status' => 'available'],
                ['key' => 'datetime.time', 'title' => 'time 时间选择器', 'status' => 'available'],
                ['key' => 'datetime.datetime_range', 'title' => 'datetime_range 日期时间范围选择器', 'status' => 'available'],
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
                    ['name' => 'name / label / tips', 'summary' => '字段名、标题与提示文案，定义日期时间字段的业务语义。'],
                    ['name' => 'value', 'summary' => '默认值或编辑态回显值，通常是完整的日期时间字符串。'],
                    ['name' => 'placeholder', 'summary' => '未选择日期时间时的引导文案。'],
                    ['name' => 'required', 'summary' => '标记为必填日期时间输入。'],
                ],
            ],
            [
                'title' => '展示与状态',
                'items' => [
                    ['name' => 'icon', 'summary' => '控制日期时间图标位于左侧或右侧。'],
                    ['name' => 'rounded / flush', 'summary' => '切换圆角或扁平输入样式。'],
                    ['name' => 'readonly / disabled', 'summary' => '控制是否允许手动编辑或交互。'],
                    ['name' => 'size / class', 'summary' => '控制尺寸和自定义样式类。'],
                ],
            ],
            [
                'title' => '原生配置',
                'items' => [
                    ['name' => 'options.dateFormat', 'summary' => '控制最终输入框输出值的日期时间格式。'],
                    ['name' => 'options.timeFormat', 'summary' => '控制时间面板和输出中的时间部分格式。'],
                    ['name' => 'options.minutesStep', 'summary' => '控制分钟滑块步进粒度。'],
                    ['name' => 'options.autoClose', 'summary' => '选择后自动关闭日期时间面板。'],
                ],
            ],
            [
                'title' => '选型建议',
                'items' => [
                    ['name' => 'datetime / date', 'summary' => '只需要日期时用 date；需要精确到时间点时用 datetime。'],
                    ['name' => 'datetime / time', 'summary' => '只需要纯时间时用 time；日期和时间都重要时用 datetime。'],
                    ['name' => 'datetime / datetime_range', 'summary' => '单个时间点用 datetime；起止时间段优先 datetime_range。'],
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
