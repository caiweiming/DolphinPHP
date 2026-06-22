<?php
declare(strict_types=1);

namespace app\showcase\service\components\form;

/**
 * Showcase number 组件页组装器
 */
final class NumberComponentPage
{
    /**
     * 组装 number 组件页
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
                'summary' => 'Number 组件用于承载整数或小数输入，适合价格、库存、排序、权重、年龄等数值场景。',
                'scenarios' => [
                    '价格、库存、排序值、年龄等需要数值约束的后台输入',
                    '带货币、单位、比例等数值上下文提示的表单字段',
                    '需要最小值、最大值、步进值和校验态反馈的数字输入场景',
                ],
                'capabilities' => [
                    '占位提示',
                    '最小值与最大值',
                    'step 步进值',
                    '前缀与后缀',
                    '默认值回显',
                    '只读与禁用',
                    '校验反馈状态',
                    '圆角 / 扁平 / 浮动标签',
                    '业务组合表单',
                ],
                'quick_start' => [
                    'array_code' => <<<'CODE'
[
    ['number', 'age', '年龄', '请输入年龄'],
]
CODE,
                    'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::number('age', '年龄', '请输入年龄')
    ->required()
    ->placeholder('请输入年龄')
    ->min(1)
    ->max(150)
    ->step(1);
CODE,
                ],
            ],
            'param_groups' => $this->paramGroups(),
            'sections' => $sections,
            'related_components' => [
                ['key' => 'basic.text', 'title' => 'text 单行文本框', 'status' => 'available'],
                ['key' => 'basic.textarea', 'title' => 'textarea 多行文本', 'status' => 'available'],
                ['key' => 'basic.switch', 'title' => 'switch 开关', 'status' => 'available'],
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
                    ['name' => 'name', 'summary' => '字段名，决定提交键名与 DOM 标识。'],
                    ['name' => 'label', 'summary' => '字段标题，用于说明数值的业务含义。'],
                    ['name' => 'tips', 'summary' => '字段下方提示文案，适合说明范围、单位和业务约束。'],
                    ['name' => 'placeholder', 'summary' => '未输入时的引导文字。'],
                    ['name' => 'value', 'summary' => '默认值或编辑态回显值。'],
                    ['name' => 'required', 'summary' => '标记为必填。'],
                ],
            ],
            [
                'title' => '数值限制',
                'items' => [
                    ['name' => 'min_length/max_length', 'summary' => '承载最小值和最大值范围限制。'],
                    ['name' => 'min()/max()', 'summary' => 'minLength()/maxLength() 的链式别名。'],
                    ['name' => 'step', 'summary' => '控制每次增减的步长，支持整数和小数。'],
                ],
            ],
            [
                'title' => '样式与状态',
                'items' => [
                    ['name' => 'prefix/suffix', 'summary' => '显示货币符号、单位、业务前缀等视觉上下文。'],
                    ['name' => 'readonly/disabled', 'summary' => '控制字段可编辑状态。'],
                    ['name' => 'valid', 'summary' => '展示成功态或失败态反馈样式。'],
                    ['name' => 'rounded/flush/float', 'summary' => '切换不同输入视觉风格。'],
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
