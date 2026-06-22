<?php
declare(strict_types=1);

namespace app\showcase\service\components\form;

/**
 * Showcase date_range 组件页组装器
 */
final class DateRangeComponentPage
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
                'summary' => 'DateRange 组件用于承载单字段的日期区间输入，适合有效期、活动周期、报名日期等只关注起止日期而不关注具体时间的后台表单场景。',
                'scenarios' => [
                    '有效期、活动周期、报名日期等需要录入开始与结束日期的场景',
                    '希望把日期范围保存在一个字段内，并沿用统一日期选择交互的后台表单',
                    '需要通过原生 options 控制范围格式、分隔符与自动关闭行为的场景',
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
        'type' => 'date_range',
        'name' => 'valid_period',
        'label' => '有效期',
        'tips' => '请选择有效期',
        'placeholder' => '请选择有效期',
        'value' => '2026-06-01 ~ 2026-06-30',
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

Field::dateRange('valid_period', '有效期', '请选择有效期')
    ->value('2026-06-01 ~ 2026-06-30')
    ->placeholder('请选择有效期')
    ->options([
        'dateFormat' => 'yyyy-MM-dd',
        'multipleDatesSeparator' => ' ~ ',
        'autoClose' => true,
    ]);
CODE,
                ],
            ],
            'param_groups' => $this->paramGroups(),
            'sections' => $sections,
            'related_components' => [
                ['key' => 'datetime.date', 'title' => 'date 日期选择器', 'status' => 'available'],
                ['key' => 'datetime.datetime_range', 'title' => 'datetime_range 日期时间范围选择器', 'status' => 'available'],
                ['key' => 'datetime.datetime', 'title' => 'datetime 日期时间选择器', 'status' => 'available'],
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
                    ['name' => 'name / label / tips', 'summary' => '字段名、标题与提示文案，定义日期范围字段的业务语义。'],
                    ['name' => 'value', 'summary' => '默认值或编辑态回显值，通常是按分隔符拼接的完整日期范围字符串。'],
                    ['name' => 'placeholder', 'summary' => '未选择日期范围时的引导文案。'],
                    ['name' => 'required', 'summary' => '标记为必填日期范围输入。'],
                ],
            ],
            [
                'title' => '展示与状态',
                'items' => [
                    ['name' => 'icon', 'summary' => '控制日期图标位于左侧或右侧。'],
                    ['name' => 'rounded / flush', 'summary' => '切换圆角或扁平输入样式。'],
                    ['name' => 'readonly / disabled', 'summary' => '控制是否允许手动查看或交互。'],
                    ['name' => 'size / class', 'summary' => '控制尺寸和自定义样式类。'],
                ],
            ],
            [
                'title' => '原生配置',
                'items' => [
                    ['name' => 'options.dateFormat', 'summary' => '控制范围值中的日期输出格式。'],
                    ['name' => 'options.multipleDatesSeparator', 'summary' => '控制开始日期与结束日期之间的分隔符。'],
                    ['name' => 'options.autoClose', 'summary' => '选择完成后自动关闭日期面板。'],
                    ['name' => 'options.inline', 'summary' => '以内嵌形式展示日期面板。'],
                ],
            ],
            [
                'title' => '选型建议',
                'items' => [
                    ['name' => 'date_range / date', 'summary' => '单日输入用 date；需要开始结束日期时用 date_range。'],
                    ['name' => 'date_range / datetime_range', 'summary' => '如果只关心日期跨度，用 date_range；需要具体时间点时用 datetime_range。'],
                    ['name' => '范围存储', 'summary' => '如果后端拆分存储开始与结束日期，提交后要按分隔符进行统一解析。'],
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
