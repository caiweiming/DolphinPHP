<?php
declare(strict_types=1);

namespace app\showcase\service\components\form;

/**
 * Showcase button 组件页组装器
 */
final class ButtonComponentPage
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
                'summary' => 'Button 组件用于在表单上下文中补充自定义操作按钮，适合跳转、预览、辅助动作和轻量工具入口等后台场景。',
                'scenarios' => [
                    '跳转、预览、辅助动作等需要自定义按钮入口的表单场景',
                    '表单中部或底部需要插入轻量工具按钮，而不是标准提交按钮的场景',
                    '希望开发者快速比较颜色、图标、链接与形状差异的组件演示场景',
                ],
                'capabilities' => [
                    '基础按钮',
                    '颜色与样式',
                    '图标按钮',
                    '链接按钮',
                    '形状变体',
                    '禁用状态',
                    'SVG 图标',
                    '业务表单片段',
                ],
                'quick_start' => [
                    'array_code' => <<<'CODE'
[
    [
        'type' => 'button',
        'name' => 'preview',
        'label' => '预览',
        'color' => 'primary',
    ],
]
CODE,
                    'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::button('preview', '预览')
    ->color('primary');
CODE,
                ],
            ],
            'param_groups' => $this->paramGroups(),
            'sections' => $sections,
            'related_components' => [
                ['key' => 'basic.button_group', 'title' => 'button_group 按钮组', 'status' => 'available'],
                ['key' => 'basic.html', 'title' => 'html HTML 内容', 'status' => 'available'],
                ['key' => 'basic.static', 'title' => 'static 静态文本', 'status' => 'available'],
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
                    ['name' => 'name / label / tips', 'summary' => '按钮字段名、显示文案和补充提示。'],
                    ['name' => 'color', 'summary' => '按钮颜色，支持 primary、ghost-secondary、outline-warning 等主题类。'],
                    ['name' => 'class / id / title', 'summary' => '补充 DOM 类名、id 与 title。'],
                ],
            ],
            [
                'title' => '交互能力',
                'items' => [
                    ['name' => 'icon / svg', 'summary' => '支持图标类名或内联 SVG，二者同时设置时 SVG 优先。'],
                    ['name' => 'href / target', 'summary' => '存在 href 时会自动渲染为链接按钮。'],
                    ['name' => 'disabled', 'summary' => '控制按钮禁用态。'],
                ],
            ],
            [
                'title' => '视觉变体',
                'items' => [
                    ['name' => 'shape / square() / pill()', 'summary' => '控制方形、药丸形等按钮外观。'],
                    ['name' => '纯图标按钮', 'summary' => '将 label 留空时可做成纯图标工具按钮。'],
                    ['name' => '颜色策略', 'summary' => '高风险动作用 danger，辅助动作用 secondary/ghost。'],
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
