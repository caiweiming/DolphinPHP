<?php
declare(strict_types=1);

namespace app\showcase\service\components\form;

/**
 * Showcase 地图组件页组装器
 */
final class MapComponentPage
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

        $meta = $this->meta((string) ($component['key'] ?? ''));

        return [
            'component' => $component,
            'overview' => [
                'summary' => $meta['summary'],
                'scenarios' => $meta['scenarios'],
                'capabilities' => [
                    '基础选点',
                    '默认值与地址回填',
                    '仅地址初始化',
                    '中心点与缩放',
                    '地址字段与占位符',
                    '只读模式',
                    $meta['vendor_title'],
                    '业务表单片段',
                ],
                'quick_start' => [
                    'array_code' => $meta['quick_start']['array_code'],
                    'field_code' => $meta['quick_start']['field_code'],
                ],
            ],
            'param_groups' => $this->paramGroups((string) ($component['key'] ?? '')),
            'sections' => $sections,
            'related_components' => $meta['related_components'],
            'doc_links' => (array) ($component['doc_links'] ?? []),
            'sidebar_source_refs' => array_slice($this->mergeSourceRefs((array) ($component['source_refs'] ?? []), $sections), 0, 3),
            'source_refs' => $this->mergeSourceRefs((array) ($component['source_refs'] ?? []), $sections),
        ];
    }

    private function meta(string $key): array
    {
        return match ($key) {
            'rich.amap' => [
                'summary' => 'Amap 组件用于在表单中完成高德地图选点，适合门店地址、仓库位置和需要同时保存坐标与地址的后台场景。',
                'scenarios' => [
                    '门店、仓库、办公地点等需要地图选点并保存坐标与地址的场景',
                    '依赖高德地图生态，并且需要安全密钥配置的后台场景',
                    '需要把坐标字段和地址字段同时写入表单提交结果的业务场景',
                ],
                'vendor_title' => 'Key 与安全密钥',
                'quick_start' => [
                    'array_code' => <<<'CODE'
[
    [
        'type' => 'amap',
        'name' => 'location',
        'label' => '高德地图',
        'options' => [
            'key' => 'your-amap-key',
            'securityJsCode' => 'your-amap-security-code',
        ],
    ],
]
CODE,
                    'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::amap('location', '高德地图')
    ->key('your-amap-key')
    ->securityJsCode('your-amap-security-code');
CODE,
                ],
                'related_components' => [
                    ['key' => 'rich.qmap', 'title' => 'qmap 腾讯地图', 'status' => 'available'],
                    ['key' => 'rich.bmap', 'title' => 'bmap 百度地图', 'status' => 'available'],
                    ['key' => 'basic.text', 'title' => 'text 单行文本框', 'status' => 'available'],
                ],
            ],
            'rich.bmap' => [
                'summary' => 'Bmap 组件用于在表单中完成百度地图选点，适合门店地址、服务区域和需要双字段保存坐标与地址的后台场景。',
                'scenarios' => [
                    '门店、服务区域、办公地点等需要地图选点并保存坐标与地址的场景',
                    '依赖百度地图搜索与逆地址服务的后台配置场景',
                    '需要把坐标字段和地址字段同时写入表单提交结果的业务场景',
                ],
                'vendor_title' => 'AK 与区域限制',
                'quick_start' => [
                    'array_code' => <<<'CODE'
[
    [
        'type' => 'bmap',
        'name' => 'location',
        'label' => '百度地图',
        'options' => [
            'ak' => 'your-bmap-ak',
        ],
    ],
]
CODE,
                    'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::bmap('location', '百度地图')
    ->ak('your-bmap-ak');
CODE,
                ],
                'related_components' => [
                    ['key' => 'rich.amap', 'title' => 'amap 高德地图', 'status' => 'available'],
                    ['key' => 'rich.qmap', 'title' => 'qmap 腾讯地图', 'status' => 'available'],
                    ['key' => 'basic.text', 'title' => 'text 单行文本框', 'status' => 'available'],
                ],
            ],
            default => [
                'summary' => 'Qmap 组件用于在表单中完成腾讯地图选点，适合门店地址、配送范围和需要坐标与地址双存的后台场景。',
                'scenarios' => [
                    '门店、配送范围、办公地点等需要地图选点并保存坐标与地址的场景',
                    '依赖腾讯地图逆地址解析与输入提示能力的后台配置场景',
                    '需要把坐标字段和地址字段同时写入表单提交结果的业务场景',
                ],
                'vendor_title' => 'Key 与逆地址配置',
                'quick_start' => [
                    'array_code' => <<<'CODE'
[
    [
        'type' => 'qmap',
        'name' => 'location',
        'label' => '腾讯地图',
        'options' => [
            'key' => 'your-qmap-key',
        ],
    ],
]
CODE,
                    'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::qmap('location', '腾讯地图')
    ->key('your-qmap-key');
CODE,
                ],
                'related_components' => [
                    ['key' => 'rich.amap', 'title' => 'amap 高德地图', 'status' => 'available'],
                    ['key' => 'rich.bmap', 'title' => 'bmap 百度地图', 'status' => 'available'],
                    ['key' => 'basic.text', 'title' => 'text 单行文本框', 'status' => 'available'],
                ],
            ],
        };
    }

    private function paramGroups(string $key): array
    {
        $vendor = match ($key) {
            'rich.amap' => [
                ['name' => 'key / securityJsCode', 'summary' => '高德地图 JS API Key 与安全密钥。'],
                ['name' => 'options(string)', 'summary' => '也支持直接传 `key,securityJsCode` 字符串简写；无坐标时可再配合初始地址定位地图。'],
                ['name' => 'vendor 选型', 'summary' => '适合已经使用高德地图生态的项目。'],
            ],
            'rich.bmap' => [
                ['name' => 'ak', 'summary' => '百度地图 AK。'],
                ['name' => 'options(string)', 'summary' => '也支持直接传字符串 AK；同时可补充 `region` 做搜索联想区域限制。'],
                ['name' => 'vendor 选型', 'summary' => '适合依赖百度地图搜索与地址能力的项目。'],
            ],
            default => [
                ['name' => 'key', 'summary' => '腾讯地图 Key。'],
                ['name' => 'options(string)', 'summary' => '也支持直接传字符串 Key；同时可补充 `get_poi` 控制逆地址是否返回 POI。'],
                ['name' => 'vendor 选型', 'summary' => '适合依赖腾讯地图逆地址与建议接口的项目。'],
            ],
        };

        return [
            [
                'title' => '基础参数',
                'items' => [
                    ['name' => 'name / label / tips', 'summary' => '字段名、标题和提示文案。'],
                    ['name' => 'value', 'summary' => '坐标值，格式通常为 `lng,lat`。'],
                    ['name' => 'addressField()', 'summary' => '自定义地址字段名，默认是 `{name}_address`。'],
                ],
            ],
            [
                'title' => '地图控制',
                'items' => [
                    ['name' => 'center() / zoom()', 'summary' => '控制地图默认中心点和缩放级别。'],
                    ['name' => 'height()', 'summary' => '控制地图画布高度。'],
                    ['name' => 'readonly()', 'summary' => '详情或审批场景下只读展示地图。'],
                ],
            ],
            [
                'title' => '厂商配置',
                'items' => $vendor,
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
