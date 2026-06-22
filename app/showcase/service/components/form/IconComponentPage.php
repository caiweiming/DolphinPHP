<?php
declare(strict_types=1);

namespace app\showcase\service\components\form;

/**
 * Showcase icon 组件页组装器
 */
final class IconComponentPage
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
                'summary' => 'Icon 组件用于在后台表单中选择图标 class，适合菜单图标、按钮图标、栏目图标等需要存储图标标识的场景。',
                'scenarios' => [
                    '菜单图标、按钮图标、栏目图标等需要选择并存储图标 class 的场景',
                    '需要从内置或扩展图标库中选择图标标识的后台配置场景',
                    '需要同时演示默认值、占位符和扩展图标库配置方式的场景',
                ],
                'capabilities' => [
                    '基础图标选择',
                    '默认值与回显',
                    '占位符与默认预览图标',
                    '扩展图标库文件',
                    '配置与使用提示',
                    '业务表单片段',
                ],
                'quick_start' => [
                    'array_code' => <<<'CODE'
[
    [
        'type' => 'icon',
        'name' => 'icon',
        'label' => '图标',
        'tips' => '请选择图标',
    ],
]
CODE,
                    'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::icon('icon', '图标')
    ->tips('请选择图标');
CODE,
                ],
            ],
            'param_groups' => $this->paramGroups(),
            'sections' => $sections,
            'related_components' => [
                ['key' => 'basic.button', 'title' => 'button 按钮', 'status' => 'available'],
                ['key' => 'choice.select', 'title' => 'select 下拉选择', 'status' => 'available'],
                ['key' => 'rich.tabs', 'title' => 'tabs 标签分组', 'status' => 'available'],
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
                    ['name' => 'name / label / tips', 'summary' => '字段名、标题与说明文案。'],
                    ['name' => 'value', 'summary' => '默认图标 class，如 `ti ti-home`。'],
                    ['name' => 'placeholder', 'summary' => '通过占位符引导用户搜索图标。'],
                ],
            ],
            [
                'title' => '扩展能力',
                'items' => [
                    ['name' => 'options.default_icon', 'summary' => '未选择时的默认预览图标。'],
                    ['name' => 'libsFile / libsFiles', 'summary' => '追加项目内自定义图标库定义文件。'],
                    ['name' => 'config/icon.php', 'summary' => '全局图标库和自动加载 CSS 配置。'],
                ],
            ],
            [
                'title' => '实现建议',
                'items' => [
                    ['name' => '返回值', 'summary' => '最终存储的通常是图标 class 字符串，而不是 SVG 内容。'],
                    ['name' => 'CSS 依赖', 'summary' => '扩展图标库必须保证对应 CSS 已加载。'],
                    ['name' => '选型建议', 'summary' => '需要图标 class 存储时用 icon，固定图标按钮展示时用 button。'],
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
