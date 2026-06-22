<?php
declare(strict_types=1);

namespace app\showcase\service\components\form;

/**
 * Showcase mask 组件页组装器
 */
final class MaskComponentPage
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
                'summary' => 'Mask 组件用于在输入过程中套用固定格式模板，适合手机号、日期、证件号、编号等需要前端格式约束的后台表单场景。',
                'scenarios' => [
                    '手机号、日期、证件号、业务编号等需要固定录入格式的场景',
                    '希望用户边输入边获得格式引导的表单场景',
                    '需要明确区分“前端格式约束”和“后端数据校验”边界的后台表单',
                ],
                'capabilities' => [
                    '基础掩码',
                    '手机号格式',
                    '日期与时间格式',
                    '证件与编号格式',
                    '默认值与回填',
                    '实现与提交注意事项',
                    '业务表单片段',
                ],
                'quick_start' => [
                    'array_code' => <<<'CODE'
[
    [
        'type' => 'mask',
        'name' => 'phone',
        'label' => '手机号',
        'options' => '000-0000-0000',
    ],
]
CODE,
                    'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::mask('phone', '手机号')
    ->options('000-0000-0000');
CODE,
                ],
            ],
            'param_groups' => $this->paramGroups(),
            'sections' => $sections,
            'related_components' => [
                ['key' => 'basic.text', 'title' => 'text 单行文本框', 'status' => 'available'],
                ['key' => 'basic.number', 'title' => 'number 数字输入', 'status' => 'available'],
                ['key' => 'basic.textarea', 'title' => 'textarea 多行文本', 'status' => 'available'],
            ],
            'doc_links' => (array) ($component['doc_links'] ?? []),
            'sidebar_source_refs' => array_slice($this->mergeSourceRefs((array) ($component['source_refs'] ?? []), $sections), 0, 3),
            'source_refs' => $this->mergeSourceRefs((array) ($component['source_refs'] ?? []), $sections),
        ];
    }

    private function paramGroups(): array
    {
        return [
            [
                'title' => '基础参数',
                'items' => [
                    ['name' => 'name / label / tips', 'summary' => '字段名、标题与提示文案。'],
                    ['name' => 'options', 'summary' => '模板真实把 options 当作掩码字符串使用。'],
                    ['name' => 'value', 'summary' => '默认值或编辑态回显值，通常包含格式字符。'],
                ],
            ],
            [
                'title' => '实现边界',
                'items' => [
                    ['name' => 'placeholder', 'summary' => '当前模板会直接使用 options 作为 placeholder。'],
                    ['name' => '前端约束', 'summary' => 'mask 只是前端格式引导，不等于后端合法性校验。'],
                    ['name' => '提交值', 'summary' => '提交结果通常带有分隔符，后端需按业务决定是否清洗。'],
                ],
            ],
            [
                'title' => '选型建议',
                'items' => [
                    ['name' => 'mask / text', 'summary' => '固定格式输入用 mask，自由输入用 text。'],
                    ['name' => 'mask / number', 'summary' => '如果包含分隔符或混合字符，用 mask 比 number 更合适。'],
                    ['name' => '数据清洗', 'summary' => '身份证、手机号、编号类字段建议入库前统一去格式化。'],
                ],
            ],
        ];
    }

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
