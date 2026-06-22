<?php
declare(strict_types=1);

namespace app\showcase\service\components\form;

/**
 * Showcase linkages 组件页组装器
 */
final class LinkagesComponentPage
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
                'summary' => 'Linkages 组件用于基于数据库表快速构建多级联动选择，无需手写远程接口，适合行政区、分类树、组织架构等标准树形数据场景。',
                'scenarios' => [
                    '行政区、分类树、组织架构等可直接基于数据库表联动的场景',
                    '希望复用框架内置接口，减少手写远程查询接口工作的场景',
                    '需要同时演示 levels、fields、filters、submit_all 和默认值回填的场景',
                ],
                'capabilities' => [
                    '基础快速联动',
                    'levels 数量与命名',
                    'fields 与 root_pid',
                    'prefix 与 connection',
                    'filters 分级筛选',
                    'submit_all 与末级提交',
                    '默认值与回填',
                    '业务表单片段',
                ],
                'quick_start' => [
                    'array_code' => <<<'CODE'
[
    [
        'type' => 'linkages',
        'name' => 'area_id',
        'label' => '快速联动',
        'table' => 'region',
        'levels' => ['省份', '城市', '区县'],
    ],
]
CODE,
                    'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::linkages('area_id', '快速联动')
    ->table('region')
    ->levels(['省份', '城市', '区县']);
CODE,
                ],
            ],
            'param_groups' => $this->paramGroups(),
            'sections' => $sections,
            'related_components' => [
                ['key' => 'choice.linkage', 'title' => 'linkage 多级联动', 'status' => 'available'],
                ['key' => 'choice.select', 'title' => 'select 下拉选择', 'status' => 'available'],
                ['key' => 'choice.radio_group', 'title' => 'radio_group 单选标签组', 'status' => 'available'],
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
                    ['name' => 'table(string)', 'summary' => '指定树形数据表，是 linkages 的核心参数。'],
                    ['name' => 'levels(int|array)', 'summary' => '定义联动级数与显示名称。'],
                    ['name' => 'url(string) / apiUrl(string)', 'summary' => '覆盖默认内置接口地址。'],
                ],
            ],
            [
                'title' => '数据映射',
                'items' => [
                    ['name' => 'fields(array)', 'summary' => '映射 `id/name/pid` 等树结构字段，适配不同树表结构。'],
                    ['name' => 'rootPid(int|string)', 'summary' => '指定根节点 pid，适配不同根级结构。'],
                    ['name' => 'filters(array)', 'summary' => '附加固定筛选条件，约束可选数据范围。'],
                ],
            ],
            [
                'title' => '连接与提交',
                'items' => [
                    ['name' => 'prefix(bool)', 'summary' => '控制查询时是否使用带前缀表名。'],
                    ['name' => 'connection(string)', 'summary' => '指定数据库连接标识。'],
                    ['name' => 'submitAll(bool) / multiple()', 'summary' => '控制提交所有级别值，以及末级是否允许多选。'],
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
