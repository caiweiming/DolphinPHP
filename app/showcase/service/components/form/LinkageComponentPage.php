<?php
declare(strict_types=1);

namespace app\showcase\service\components\form;

/**
 * Showcase linkage 组件页组装器
 */
final class LinkageComponentPage
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
                'summary' => 'Linkage 组件用于基于远程数据源实现多级联动选择，适合地区、省市区、组织路径和任意分级数据选择场景。',
                'scenarios' => [
                    '地区、省市区、组织路径等需要远程多级联动选择的场景',
                    '不同层级的请求参数、请求地址和回填结构都需要灵活控制的场景',
                    '需要演示 submit_all、末级多选和按级别请求映射的复杂选择场景',
                ],
                'capabilities' => [
                    '基础远程联动',
                    '默认值与回填',
                    '级别占位符与标签',
                    '按级别覆盖 url',
                    'submit_all 与末级提交',
                    'multiple 末级多选',
                    'request_fields 与 params',
                    '业务表单片段',
                ],
                'quick_start' => [
                    'array_code' => <<<'CODE'
[
    [
        'type' => 'linkage',
        'name' => 'district',
        'label' => '地区',
        'url' => '/admin/api/regions',
        'levels' => [
            ['key' => 'province', 'label' => '省份', 'placeholder' => '请选择省份'],
            ['key' => 'city', 'label' => '城市', 'placeholder' => '请选择城市'],
            ['key' => 'district', 'label' => '区县', 'placeholder' => '请选择区县'],
        ],
    ],
]
CODE,
                    'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::linkage('district', '地区')
    ->url('/admin/api/regions')
    ->levels([
        ['key' => 'province', 'label' => '省份', 'placeholder' => '请选择省份'],
        ['key' => 'city', 'label' => '城市', 'placeholder' => '请选择城市'],
        ['key' => 'district', 'label' => '区县', 'placeholder' => '请选择区县'],
    ]);
CODE,
                ],
            ],
            'param_groups' => $this->paramGroups(),
            'sections' => $sections,
            'related_components' => [
                ['key' => 'choice.linkages', 'title' => 'linkages 快速联动', 'status' => 'available'],
                ['key' => 'choice.select', 'title' => 'select 下拉选择', 'status' => 'available'],
                ['key' => 'choice.select2', 'title' => 'select2 增强下拉', 'status' => 'available'],
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
                    ['name' => 'url(string)', 'summary' => '设置远程联动接口地址。'],
                    ['name' => 'levels(array)', 'summary' => '定义每一级的 key、label、placeholder 等信息。'],
                    ['name' => 'options', 'summary' => '可为首级直接提供静态候选项，减少首屏远程请求。'],
                ],
            ],
            [
                'title' => '联动控制',
                'items' => [
                    ['name' => 'submitAll(bool)', 'summary' => '控制提交所有级别值，还是只提交最终级结果。'],
                    ['name' => 'multiple()', 'summary' => '让最后一级支持多选。'],
                    ['name' => 'value', 'summary' => '支持按级别回填，如 `province/city/district` 结构。'],
                ],
            ],
            [
                'title' => '请求映射',
                'items' => [
                    ['name' => 'levels[].url', 'summary' => '按级别覆盖接口地址。'],
                    ['name' => 'levels[].request_keys / request_fields', 'summary' => '控制 request_fields 字段映射以及上游值在请求里的参数名。'],
                    ['name' => 'levels[].params', 'summary' => '附加固定参数，如渠道、租户、数据源标识。'],
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
