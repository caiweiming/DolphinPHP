<?php
declare(strict_types=1);

namespace app\showcase\service\components\form;

/**
 * Showcase color 组件页组装器
 */
final class ColorComponentPage
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
                'summary' => 'Color 组件用于自由选择颜色值，适合主题色、标签色、品牌色和可视化配置等需要精确取色的后台场景。',
                'scenarios' => [
                    '主题色、标签色、品牌色等需要自由取色的配置场景',
                    '需要支持透明度、预设色板和多种颜色格式的视觉配置场景',
                    '希望开发者直接看到 Coloris 原生 options 如何落到表单组件上的场景',
                ],
                'capabilities' => [
                    '基础取色器',
                    '默认值与回填',
                    '颜色格式与透明度',
                    '预设色与主题样式',
                    '显示按钮与交互开关',
                    'group_class 与布局',
                    '业务表单片段',
                ],
                'quick_start' => [
                    'array_code' => <<<'CODE'
[
    [
        'type' => 'color',
        'name' => 'theme_color',
        'label' => '主题色',
        'value' => '#206bc4',
    ],
]
CODE,
                    'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::color('theme_color', '主题色')
    ->value('#206bc4');
CODE,
                ],
            ],
            'param_groups' => $this->paramGroups(),
            'sections' => $sections,
            'related_components' => [
                ['key' => 'choice.color_select', 'title' => 'color_select 预设颜色选择', 'status' => 'available'],
                ['key' => 'basic.text', 'title' => 'text 单行文本框', 'status' => 'available'],
                ['key' => 'choice.select', 'title' => 'select 下拉选择', 'status' => 'available'],
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
                    ['name' => 'name / label / tips', 'summary' => '字段名、标题和提示文案。'],
                    ['name' => 'value', 'summary' => '默认颜色值或编辑态回显值，如 `#206bc4`。'],
                    ['name' => 'required', 'summary' => '标记该颜色字段必须选择。'],
                ],
            ],
            [
                'title' => 'Coloris 配置',
                'items' => [
                    ['name' => 'options.theme / themeMode', 'summary' => '控制 Coloris 弹层的主题风格。'],
                    ['name' => 'options.alpha / format', 'summary' => '控制透明度支持和颜色输出格式。'],
                    ['name' => 'options.swatches', 'summary' => '提供预设色板，方便快速选色。'],
                ],
            ],
            [
                'title' => '布局与选型',
                'items' => [
                    ['name' => 'groupClass()', 'summary' => '控制颜色输入容器类名。'],
                    ['name' => 'color / color_select', 'summary' => '自由取色用 color，固定色板选择用 color_select。'],
                    ['name' => '后端格式统一', 'summary' => '建议全站统一使用 HEX 或 RGBA，避免字段格式混乱。'],
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
