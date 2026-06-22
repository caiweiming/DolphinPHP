<?php
declare(strict_types=1);

namespace app\showcase\service\components\form;

/**
 * Showcase switch 组件页组装器
 */
final class SwitchComponentPage
{
    /**
     * 组装 switch 组件页
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
                'summary' => 'Switch 组件用于承载二元状态切换，适合启用/禁用、公开/私有、管理员/普通用户等布尔场景。',
                'scenarios' => [
                    '启用/禁用、公开/私有、是否管理员等布尔状态切换',
                    '需要在编辑表单里快速表达“开/关”或“是/否”的后台配置项',
                    '希望让开发者同时看到前端配置方式和后端未选中时的提交处理方式',
                ],
                'capabilities' => [
                    '默认值与开关状态',
                    'title 标题说明',
                    '禁用态',
                    'inline 内联展示',
                    '多开关组合',
                    '后端提交默认值处理',
                    '业务组合表单',
                ],
                'quick_start' => [
                    'array_code' => <<<'CODE'
[
    ['switch', 'status', '状态', '是否启用', 1],
]
CODE,
                    'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::switch('status', '状态', '是否启用')
    ->value(1)
    ->title('启用后用户可以正常登录');
CODE,
                ],
            ],
            'param_groups' => $this->paramGroups(),
            'sections' => $sections,
            'related_components' => [
                ['key' => 'basic.text', 'title' => 'text 单行文本框', 'status' => 'available'],
                ['key' => 'basic.number', 'title' => 'number 数字输入', 'status' => 'available'],
                ['key' => 'choice.radio', 'title' => 'radio 单选框', 'status' => 'available'],
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
                    ['name' => 'name', 'summary' => '字段名，决定提交键名。'],
                    ['name' => 'label', 'summary' => '字段标签，用于说明这是哪个布尔开关。'],
                    ['name' => 'tips', 'summary' => '字段下方提示文案，适合说明开关影响。'],
                    ['name' => 'value', 'summary' => '默认值，通常用 1/0 表示开关默认状态。'],
                ],
            ],
            [
                'title' => '展示与状态',
                'items' => [
                    ['name' => 'title', 'summary' => '显示在开关右侧的文案说明。'],
                    ['name' => 'disabled', 'summary' => '禁用开关交互，适合不可编辑场景。'],
                    ['name' => 'inline', 'summary' => '多个开关时切换为内联展示。'],
                ],
            ],
            [
                'title' => '提交注意事项',
                'items' => [
                    ['name' => '未选中不提交', 'summary' => 'switch 未选中时字段不会出现在请求中，后端需补默认值。'],
                    ['name' => '0 / 1 语义', 'summary' => '通常约定 1 为启用，0 为禁用。'],
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
