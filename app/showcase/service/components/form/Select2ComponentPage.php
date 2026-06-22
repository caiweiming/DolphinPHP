<?php
declare(strict_types=1);

namespace app\showcase\service\components\form;

/**
 * Showcase select2 组件页组装器
 */
final class Select2ComponentPage
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
                'summary' => 'Select2 组件用于承载带搜索、异步加载和多选增强能力的下拉选择，适合用户、部门、角色、标签等选项较多的后台表单场景。',
                'scenarios' => [
                    '用户、部门、角色等选项较多且需要搜索能力的下拉选择场景',
                    '希望在一个字段里完成远程搜索、多选和回显，而不是切换到复杂弹窗组件',
                    '需要对接框架内置 Ajax 数据源契约或自定义远程接口的管理表单',
                ],
                'capabilities' => [
                    '基础增强下拉',
                    '默认值与回填',
                    '占位符提示',
                    'multiple 多选',
                    '多选默认值',
                    '禁用指定选项',
                    '分组选项',
                    '内置 ajax 数据源',
                    '自定义 ajax URL',
                    '原生 _options 配置',
                    '业务表单片段',
                ],
                'quick_start' => [
                    'array_code' => <<<'CODE'
[
    [
        'type' => 'select2',
        'name' => 'department_id',
        'label' => '所属部门',
        'tips' => '请选择所属部门',
        'options' => [
            'product' => '产品中心',
            'engineering' => '研发中心',
            'operations' => '运营中心',
        ],
        'placeholder' => '请选择所属部门',
        'value' => 'engineering',
    ],
]
CODE,
                    'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::select2('department_id', '所属部门', '请选择所属部门')
    ->options([
        'product' => '产品中心',
        'engineering' => '研发中心',
        'operations' => '运营中心',
    ])
    ->value('engineering')
    ->placeholder('请选择所属部门');
CODE,
                ],
            ],
            'param_groups' => $this->paramGroups(),
            'sections' => $sections,
            'related_components' => [
                ['key' => 'choice.select', 'title' => 'select 下拉选择', 'status' => 'available'],
                ['key' => 'choice.radio', 'title' => 'radio 单选框', 'status' => 'available'],
                ['key' => 'choice.radio_group', 'title' => 'radio_group 单选标签组', 'status' => 'available'],
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
                    ['name' => 'name / label / tips', 'summary' => '字段名、标题和提示文案，和 select 保持一致。'],
                    ['name' => 'options / value', 'summary' => '本地静态选项与默认值/编辑态回显。'],
                    ['name' => 'placeholder', 'summary' => '搜索型下拉的空态提示文案。'],
                    ['name' => 'multiple', 'summary' => '启用多选模式，提交 name 会自动补成数组形式。'],
                ],
            ],
            [
                'title' => '远程数据',
                'items' => [
                    ['name' => 'ajax', 'summary' => '支持数组或字符串；数组可走内置数据契约，字符串适合自定义接口。'],
                    ['name' => 'ajax.url', 'summary' => '远程接口地址；未传时数组模式默认走 `admin/api/getSelectAjax`。'],
                    ['name' => 'ajax.rows', 'summary' => '每页请求数量，默认 15。'],
                    ['name' => 'ajax.table/title/search/where', 'summary' => '内置 Ajax 数据源常用配置。'],
                    ['name' => 'ajax.callback', 'summary' => '可对返回结果进行二次格式化处理。'],
                ],
            ],
            [
                'title' => '增强配置',
                'items' => [
                    ['name' => '_options', 'summary' => '透传 Select2 原生配置，如 `minimumResultsForSearch`、`theme`、`width`。'],
                    ['name' => 'Field 链式写法', 'summary' => '链式调用可通过 `->attr(\'_options\', [...])` 透传原生 Select2 配置。'],
                    ['name' => 'disabled', 'summary' => '可禁用指定选项，和 select 行为保持一致。'],
                    ['name' => 'group', 'summary' => '支持 optgroup 分组渲染。'],
                    ['name' => 'select / select2', 'summary' => '选项少且无需搜索时用 select；选项多、需搜索或远程拉取时用 select2。'],
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
