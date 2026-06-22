<?php
declare(strict_types=1);

namespace app\showcase\service\components\form;

/**
 * Showcase static 组件页组装器
 */
final class StaticComponentPage
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
                'summary' => 'Static 组件用于在表单中展示只读字段值，并可按需决定是否随表单提交，适合展示系统编号、状态结果、关联信息等不可编辑内容。',
                'scenarios' => [
                    '系统编号、状态结果、关联信息等需要只读展示的表单场景',
                    '编辑页需要显示不可修改字段，同时保留部分值继续提交给后端的场景',
                    '希望安全地区分“只读展示值”和“直接渲染 HTML 说明区”的场景',
                ],
                'capabilities' => [
                    '基础静态展示',
                    'send 提交数据',
                    '默认值与回填',
                    'HTML 内容 raw',
                    '关联信息展示',
                    '与 hidden 的区别',
                    '业务表单片段',
                ],
                'quick_start' => [
                    'array_code' => <<<'CODE'
[
    [
        'type' => 'static',
        'name' => 'order_no',
        'label' => '订单号',
        'value' => 'SO20260602001',
    ],
]
CODE,
                    'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::static('order_no', '订单号')
    ->value('SO20260602001');
CODE,
                ],
            ],
            'param_groups' => $this->paramGroups(),
            'sections' => $sections,
            'related_components' => [
                ['key' => 'basic.html', 'title' => 'html HTML 内容', 'status' => 'available'],
                ['key' => 'basic.hidden', 'title' => 'hidden 隐藏域', 'status' => 'available'],
                ['key' => 'basic.text', 'title' => 'text 单行文本框', 'status' => 'available'],
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
                    ['name' => 'name / label / tips', 'summary' => '字段名、标题和说明文案，定义只读值的业务语义。'],
                    ['name' => 'value', 'summary' => '最终展示的只读内容，可为文本或格式化后的值。'],
                    ['name' => 'help', 'summary' => '给标题增加额外帮助说明。'],
                ],
            ],
            [
                'title' => '核心能力',
                'items' => [
                    ['name' => 'send(true)', 'summary' => '在显示只读值的同时，以 hidden 方式继续提交原值。'],
                    ['name' => 'raw(true)', 'summary' => '把 value 当作 HTML 输出，不再转义。'],
                    ['name' => 'props', 'summary' => '可给只读容器增加自定义属性。'],
                ],
            ],
            [
                'title' => '选型建议',
                'items' => [
                    ['name' => 'static / hidden', 'summary' => '需要展示给用户看时用 static；完全不展示时用 hidden。'],
                    ['name' => 'static / html', 'summary' => '展示单个字段值用 static；说明区、卡片区用 html。'],
                    ['name' => '提交策略', 'summary' => '只有确实需要后端复用展示值时再开启 send。'],
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
