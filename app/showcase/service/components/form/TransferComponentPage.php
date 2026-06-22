<?php
declare(strict_types=1);

namespace app\showcase\service\components\form;

/**
 * Showcase transfer 组件页组装器
 */
final class TransferComponentPage
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
                'summary' => 'Transfer 组件用于在双栏穿梭框中分配一组选项，适合角色授权、白名单维护和中小规模静态资源分配等后台场景。',
                'scenarios' => [
                    '角色分配、标签授权、白名单维护等需要双栏转移选择的场景',
                    '希望直接得到“安装后即用”双栏分配体验的后台场景',
                    '需要透传 bootstrap-duallistbox 配置来控制标题、筛选和交互方式的场景',
                ],
                'capabilities' => [
                    '基础穿梭框',
                    '默认值与回填',
                    '禁用指定选项',
                    '透传 props 配置',
                    '选项规模与使用提示',
                    '扩展项接入方式',
                    '业务表单片段',
                ],
                'quick_start' => [
                    'array_code' => <<<'CODE'
[
    [
        'type' => 'transfer',
        'name' => 'role_keys',
        'label' => '角色分配',
        'value' => ['editor'],
        'options' => [
            'admin' => '管理员',
            'editor' => '编辑',
            'auditor' => '审核员',
        ],
    ],
]
CODE,
                    'field_code' => <<<'CODE'
use form\transfer\Transfer;

$this->form->item(
    Transfer::make('role_keys', '角色分配')
        ->value(['editor'])
        ->options([
            'admin' => '管理员',
            'editor' => '编辑',
            'auditor' => '审核员',
        ])
);
CODE,
                ],
            ],
            'param_groups' => $this->paramGroups(),
            'sections' => $sections,
            'related_components' => [
                ['key' => 'choice.checkbox_group', 'title' => 'checkbox_group 多选框组', 'status' => 'available'],
                ['key' => 'choice.select_group', 'title' => 'select_group 多选标签', 'status' => 'available'],
                ['key' => 'choice.select2', 'title' => 'select2 增强下拉选择', 'status' => 'available'],
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
                    ['name' => 'options', 'summary' => '通常写成 `value => label` 结构，适合静态选项集合。'],
                    ['name' => 'value', 'summary' => '默认选中值，建议始终传数组。'],
                ],
            ],
            [
                'title' => '交互配置',
                'items' => [
                    ['name' => 'disabled', 'summary' => '支持禁用全部或指定选项。'],
                    ['name' => 'props', 'summary' => '透传给 bootstrap-duallistbox 的配置，如列表标题、筛选和 moveOnSelect。'],
                    ['name' => '多选提交', 'summary' => '底层以 `name[]` 提交，后端接收时应按数组处理。'],
                ],
            ],
            [
                'title' => '实现建议',
                'items' => [
                    ['name' => '扩展项接入', 'summary' => 'transfer 是项目级扩展项，入口类为 `form\\transfer\\Transfer`。'],
                    ['name' => '适用规模', 'summary' => '更适合中小规模静态选项，不适合超大数据量远程搜索场景。'],
                    ['name' => '选型建议', 'summary' => '双栏分配用 transfer；普通多选更适合 checkbox_group 或 select_group。'],
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
