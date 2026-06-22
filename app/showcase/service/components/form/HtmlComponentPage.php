<?php
declare(strict_types=1);

namespace app\showcase\service\components\form;

/**
 * Showcase html 组件页组装器
 */
final class HtmlComponentPage
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
                'summary' => 'Html 组件用于在表单中直接渲染说明区、警告区、卡片区等静态 HTML 内容，适合补充提示、上下文说明和危险操作确认。',
                'scenarios' => [
                    '说明区、警告区、信息卡片等需要直接输出 HTML 内容的场景',
                    '删除、重置、审核等危险动作前需要插入醒目提醒区域的后台表单',
                    '希望在输入项之间插入自定义说明、状态 badge 或业务卡片的场景',
                ],
                'capabilities' => [
                    '基础提示内容',
                    '说明列表',
                    '信息卡片',
                    '危险操作警告',
                    '图标与状态',
                    '与 static 的区别',
                    '业务表单片段',
                ],
                'quick_start' => [
                    'array_code' => <<<'CODE'
[
    [
        'type' => 'html',
        'name' => 'notice',
        'label' => '提示',
        'value' => '<div class="alert alert-info mb-0">请先阅读配置说明再提交表单</div>',
    ],
]
CODE,
                    'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::html('notice', '提示')
    ->value('<div class="alert alert-info mb-0">请先阅读配置说明再提交表单</div>');
CODE,
                ],
            ],
            'param_groups' => $this->paramGroups(),
            'sections' => $sections,
            'related_components' => [
                ['key' => 'basic.static', 'title' => 'static 静态文本', 'status' => 'available'],
                ['key' => 'basic.hidden', 'title' => 'hidden 隐藏域', 'status' => 'available'],
                ['key' => 'basic.textarea', 'title' => 'textarea 多行文本', 'status' => 'available'],
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
                    ['name' => 'name / label / tips', 'summary' => '字段名、标题与补充提示，用于组织说明区块。'],
                    ['name' => 'value', 'summary' => '直接输出的 HTML 片段，模板会按 raw 方式渲染。'],
                    ['name' => 'help', 'summary' => '可为标题补充悬浮帮助信息。'],
                ],
            ],
            [
                'title' => '内容建议',
                'items' => [
                    ['name' => 'alert / badge / card', 'summary' => '最常见的三类 HTML 结构：提示框、状态块、信息卡片。'],
                    ['name' => '转义责任', 'summary' => 'value 会直接输出，拼接用户数据时要自行做转义。'],
                    ['name' => '布局位置', 'summary' => '适合插在表单头部、字段之间或提交区前。'],
                ],
            ],
            [
                'title' => '选型建议',
                'items' => [
                    ['name' => 'html / static', 'summary' => '复杂 HTML 内容用 html；只读字段值展示优先用 static。'],
                    ['name' => 'html / text', 'summary' => '纯文本输入或只读文本不要滥用 html，避免后端值处理混乱。'],
                    ['name' => '安全性', 'summary' => '所有含用户输入的 HTML 片段都必须先转义后拼接。'],
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
