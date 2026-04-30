<?php
declare(strict_types=1);

namespace chart_map\demo_region;

use app\common\abstract\ChartMapProvider;

/**
 * 地图专项扩展示例 Provider
 */
class Provider extends ChartMapProvider
{
    /**
     * 扩展包元信息
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function meta(array $context = []): array
    {
        return [
            'title'       => '示例区域地图',
            'description' => '用于验证地图专项扩展协议的最小示例包',
        ];
    }

    /**
     * 地图资源定义
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function definition(array $context = []): array
    {
        return [
            'registerName' => 'demo_region',
            'geoJsonUrl'   => dp_static_extend_chart_map_path() . 'demo_region/demo_region.geo.json',
            'specialAreas' => [],
            'nameMap'      => [],
            'aliasMap'     => [
                'north' => '北区',
                'east'  => '东区',
                'south' => '南区',
            ],
            'codeMap'      => [
                '1001' => '北区',
                '1002' => '东区',
                '1003' => '南区',
            ],
            'regionCoords' => [
                '北区' => [104, 35],
                '东区' => [116, 33],
                '南区' => [108, 23],
            ],
        ];
    }

    /**
     * 地图数据归一化
     * @param array<string, mixed> $data
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function normalize(array $data, array $context = []): array
    {
        $definition = $this->definition($context);
        $regions = [];

        foreach ((array)($data['regions'] ?? []) as $item) {
            if (!is_array($item)) {
                continue;
            }

            $name = $this->normalizeRegionName((string)($item['name'] ?? ''), (string)($item['code'] ?? ''), $definition);
            if ($name === '') {
                continue;
            }

            unset($item['code']);
            $item['name'] = $name;
            $regions[] = $item;
        }

        $overlays = [];
        foreach ((array)($data['overlays'] ?? []) as $overlay) {
            if (!is_array($overlay)) {
                continue;
            }

            $overlay['data'] = $this->normalizeOverlayData((array)($overlay['data'] ?? []), $definition);
            $overlays[] = $overlay;
        }

        return [
            'dimension' => $data['dimension'] ?? [],
            'regions'   => $regions,
            'visualMap' => is_array($data['visualMap'] ?? null) ? $data['visualMap'] : [],
            'overlays'  => $overlays,
            'view'      => is_array($data['view'] ?? null) ? $data['view'] : [],
            'tooltip'   => is_array($data['tooltip'] ?? null) ? $data['tooltip'] : [],
            'geo'       => is_array($data['geo'] ?? null) ? $data['geo'] : [],
            'mapSeries' => is_array($data['mapSeries'] ?? null) ? $data['mapSeries'] : [],
        ];
    }

    /**
     * 地图专项资源
     * @param array<string, mixed> $context
     * @return array<string, array>
     */
    public function assets(array $context = []): array
    {
        return [
            'css' => [
                dp_static_extend_chart_map_path() . 'demo_region/demo_region.css',
            ],
            'js'  => [
                dp_static_extend_chart_map_path() . 'demo_region/demo_region.js',
            ],
        ];
    }

    /**
     * 地图扩展附加载荷
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function payload(array $context = []): array
    {
        return [
            'accentColor' => '#0d6efd',
        ];
    }

    /**
     * 归一化覆盖物数据
     * @param array<int, mixed> $items
     * @param array<string, mixed> $definition
     * @return array<int, mixed>
     */
    private function normalizeOverlayData(array $items, array $definition): array
    {
        $coordsMap = is_array($definition['regionCoords'] ?? null) ? $definition['regionCoords'] : [];
        $normalized = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $name = $this->normalizeRegionName((string)($item['name'] ?? ''), (string)($item['code'] ?? ''), $definition);
            $coord = $item['coord'] ?? ($coordsMap[$name] ?? null);

            $hasLineSource = isset($item['fromName']) || isset($item['fromCode']);
            $hasLineTarget = isset($item['toName']) || isset($item['toCode']);

            if ($hasLineSource && $hasLineTarget) {
                $fromName = $this->normalizeRegionName((string)($item['fromName'] ?? ''), (string)($item['fromCode'] ?? ''), $definition);
                $toName = $this->normalizeRegionName((string)($item['toName'] ?? ''), (string)($item['toCode'] ?? ''), $definition);
                $fromCoord = $item['fromCoord'] ?? ($coordsMap[$fromName] ?? null);
                $toCoord = $item['toCoord'] ?? ($coordsMap[$toName] ?? null);
                if (is_array($fromCoord) && is_array($toCoord)) {
                    $normalized[] = [
                        'fromName' => $fromName,
                        'toName'   => $toName,
                        'coords'   => [$fromCoord, $toCoord],
                        'value'    => $item['value'] ?? null,
                    ];
                }
                continue;
            }

            if (is_array($coord)) {
                $value = $item['value'] ?? null;
                $item['name'] = $name;
                $item['value'] = $value === null ? $coord : array_merge($coord, [$value]);
                unset($item['coord'], $item['code']);
                $normalized[] = $item;
            }
        }

        return $normalized;
    }
}
