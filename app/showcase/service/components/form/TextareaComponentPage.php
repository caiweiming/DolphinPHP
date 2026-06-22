<?php
declare(strict_types=1);

namespace app\showcase\service\components\form;

/**
 * Showcase textarea 组件页组装器
 */
final class TextareaComponentPage
{
    /**
     * 组装 textarea 组件页
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
                'summary' => 'Textarea 组件用于承载多行纯文本输入，适合摘要、备注、地址、说明等中短文本场景。',
                'scenarios' => [
                    '文章摘要、备注说明、详细地址等多行内容输入',
                    '需要限制字数但又不适合富文本编辑器的后台表单',
                    '内容长度不稳定，希望通过 rows 或 autosize 优化输入体验的场景',
                ],
                'capabilities' => [
                    '占位提示',
                    '必填与长度限制',
                    '默认值回显',
                    'rows 行数控制',
                    'autosize 自动增高',
                    '只读与禁用',
                    'flush 扁平样式',
                    'help / id / props 扩展参数',
                    '业务组合表单',
                ],
                'quick_start' => [
                    'array_code' => <<<'CODE'
[
    ['textarea', 'description', '描述', '请输入描述信息'],
]
CODE,
                    'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::textarea('description', '描述', '请输入描述信息')
    ->required()
    ->placeholder('请输入描述')
    ->rows(5)
    ->autosize()
    ->max(500);
CODE,
                ],
            ],
            'param_groups' => $this->paramGroups(),
            'sections' => $sections,
            'related_components' => [
                ['key' => 'basic.text', 'title' => 'text 单行文本框', 'status' => 'available'],
                ['key' => 'rich.vditor', 'title' => 'vditor 富文本编辑器', 'status' => 'available'],
                ['key' => 'basic.number', 'title' => 'number 数字输入', 'status' => 'available'],
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
                    ['name' => 'label', 'summary' => '字段标题，用于描述这段多行内容的业务含义。'],
                    ['name' => 'tips', 'summary' => '字段下方提示文案，适合说明字数和填写要求。'],
                    ['name' => 'help', 'summary' => '标签右侧帮助提示，适合解释输入来源或限制。'],
                    ['name' => 'placeholder', 'summary' => '未输入时的引导文字。'],
                    ['name' => 'value', 'summary' => '默认值或回显内容。'],
                ],
            ],
            [
                'title' => '布局与样式',
                'items' => [
                    ['name' => 'rows', 'summary' => '设置 textarea 初始显示行数。'],
                    ['name' => 'autosize', 'summary' => '启用后根据内容自动增高。'],
                    ['name' => 'flush', 'summary' => '切换为扁平化输入风格。'],
                    ['name' => 'size', 'summary' => '控制尺寸，常用 sm / lg。'],
                    ['name' => 'class', 'summary' => '追加 textarea 自定义样式类。'],
                ],
            ],
            [
                'title' => '状态与限制',
                'items' => [
                    ['name' => 'required', 'summary' => '标记为必填。'],
                    ['name' => 'min_length/max_length', 'summary' => '限制输入长度。'],
                    ['name' => 'readonly/disabled', 'summary' => '控制可编辑状态。'],
                    ['name' => 'max()', 'summary' => 'maxLength() 的链式别名。'],
                ],
            ],
            [
                'title' => '扩展能力',
                'items' => [
                    ['name' => 'id', 'summary' => '自定义 DOM id，便于脚本挂载。'],
                    ['name' => 'props', 'summary' => '透传原生 HTML 属性，如 data-*。'],
                    ['name' => 'inner_width', 'summary' => '控制字段块内部宽度布局。'],
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
