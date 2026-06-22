<?php
declare(strict_types=1);

namespace app\showcase\service\components\form;

/**
 * Showcase tabs 组件页组装器
 */
final class TabsComponentPage
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
                'summary' => 'Tabs 组件用于把一组内容拆分到标签页中展示，适合长表单分组、分渠道配置和多块内容分区呈现。',
                'scenarios' => [
                    '长表单分组、分渠道配置、多块内容分区展示等需要标签页切换的场景',
                    '希望将一组 HTML 或表单项内容拆成多个标签页组织的场景',
                    '需要演示图标标签、右侧标签、等宽标签和禁用标签差异的复杂表单场景',
                ],
                'capabilities' => [
                    '基础 HTML 标签页',
                    '图标标签页',
                    'right 右侧标签',
                    'fill 等宽标签',
                    'disabled 禁用标签',
                    '表单项数组内容',
                    'HTML 与表单混合内容',
                    '业务表单片段',
                ],
                'quick_start' => [
                    'array_code' => <<<'CODE'
[
    [
        'type' => 'tabs',
        'name' => 'pay_tabs',
        'label' => '支付配置',
        'options' => [
            ['title' => '微信支付', 'content' => '<div>微信配置内容</div>'],
            ['title' => '支付宝支付', 'content' => '<div>支付宝配置内容</div>'],
        ],
    ],
]
CODE,
                    'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::tabs('pay_tabs', '支付配置')
    ->options([
        ['title' => '微信支付', 'content' => '<div>微信配置内容</div>'],
        ['title' => '支付宝支付', 'content' => '<div>支付宝配置内容</div>'],
    ]);
CODE,
                ],
            ],
            'param_groups' => $this->paramGroups(),
            'sections' => $sections,
            'related_components' => [
                ['key' => 'basic.html', 'title' => 'html HTML 内容', 'status' => 'available'],
                ['key' => 'choice.icon', 'title' => 'icon 图标选择器', 'status' => 'available'],
                ['key' => 'basic.text', 'title' => 'text 单行文本框', 'status' => 'available'],
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
                    ['name' => 'options', 'summary' => '模板真实读取的是 options，每项至少包含 `title` 和 `content`。'],
                    ['name' => 'value', 'summary' => 'tabs 本身不直接提交值，核心在内容组织。'],
                ],
            ],
            [
                'title' => '布局能力',
                'items' => [
                    ['name' => 'fill(true)', 'summary' => '让标签头均分宽度。'],
                    ['name' => 'right(true)', 'summary' => '将整组标签右对齐。'],
                    ['name' => 'disabled', 'summary' => '禁用指定标签或全部标签。'],
                ],
            ],
            [
                'title' => '内容能力',
                'items' => [
                    ['name' => 'content 字符串', 'summary' => '可直接输出 HTML 字符串。'],
                    ['name' => 'content 表单项数组', 'summary' => '可把表单项数组直接转成标签内容。'],
                    ['name' => '选型建议', 'summary' => '一组内容分块展示用 tabs；多个独立页面应用页面级 `Page::tabs()`。'],
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
