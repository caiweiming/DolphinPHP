<?php
declare(strict_types=1);

namespace app\showcase\service\components\form;

/**
 * Showcase time 组件页组装器
 */
final class TimeComponentPage
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
                'summary' => 'Time 组件用于承载纯时间输入，适合营业时间、提醒时间、预约时段等只需要时分而不需要日期的后台表单场景。',
                'scenarios' => [
                    '营业时间、提醒时间、签到截止时间等纯时间输入场景',
                    '希望复用统一时间选择器交互，但明确只录入时分而不录入日期的后台表单',
                    '需要通过原生 options 控制时间格式、小时/分钟步进等行为的场景',
                ],
                'capabilities' => [
                    '基础用法',
                    '占位符与图标',
                    '默认值与回填',
                    '样式变体',
                    '只读与禁用',
                    '原生 options 配置',
                    '时间格式与步进',
                    '业务表单片段',
                ],
                'quick_start' => [
                    'array_code' => <<<'CODE'
[
    [
        'type' => 'time',
        'name' => 'open_time',
        'label' => '营业开始时间',
        'tips' => '请选择营业开始时间',
        'placeholder' => '请选择营业开始时间',
        'value' => '09:00',
        'options' => [
            'timeFormat' => 'HH:mm',
            'minutesStep' => 15,
        ],
    ],
]
CODE,
                    'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::time('open_time', '营业开始时间', '请选择营业开始时间')
    ->value('09:00')
    ->placeholder('请选择营业开始时间')
    ->options([
        'timeFormat' => 'HH:mm',
        'minutesStep' => 15,
    ]);
CODE,
                ],
            ],
            'param_groups' => $this->paramGroups(),
            'sections' => $sections,
            'related_components' => [
                ['key' => 'datetime.date', 'title' => 'date 日期选择器', 'status' => 'available'],
                ['key' => 'datetime.datetime', 'title' => 'datetime 日期时间选择器', 'status' => 'available'],
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
                    ['name' => 'name / label / tips', 'summary' => '字段名、标题与提示文案，定义时间字段的业务语义。'],
                    ['name' => 'value', 'summary' => '默认值或编辑态回显值，通常是 `HH:mm` 或 `HH:mm:ss`。'],
                    ['name' => 'placeholder', 'summary' => '未选择时间时的引导文案。'],
                    ['name' => 'required', 'summary' => '标记为必填时间输入。'],
                ],
            ],
            [
                'title' => '展示与状态',
                'items' => [
                    ['name' => 'icon', 'summary' => '控制时间图标位于左侧或右侧。'],
                    ['name' => 'rounded / flush', 'summary' => '切换圆角或扁平输入样式。'],
                    ['name' => 'readonly / disabled', 'summary' => '控制是否允许手动编辑或交互。'],
                    ['name' => 'size / class', 'summary' => '控制尺寸和自定义样式类。'],
                ],
            ],
            [
                'title' => '原生配置',
                'items' => [
                    ['name' => 'options.timeFormat', 'summary' => '控制最终输入框输出值的时间格式。'],
                    ['name' => 'options.hoursStep', 'summary' => '控制小时滑块步进。'],
                    ['name' => 'options.minutesStep', 'summary' => '控制分钟滑块步进。'],
                    ['name' => 'options.inline', 'summary' => '以内嵌形式展示时间选择面板。'],
                ],
            ],
            [
                'title' => '选型建议',
                'items' => [
                    ['name' => 'time / datetime', 'summary' => '只需要时分时用 time；需要同时录入日期时用 datetime。'],
                    ['name' => 'time / datetime_range', 'summary' => '单个时间点用 time；起止时间段优先 datetime_range。'],
                    ['name' => '时间格式', 'summary' => '后端解析不要写死格式，应和 `timeFormat` 保持一致。'],
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
