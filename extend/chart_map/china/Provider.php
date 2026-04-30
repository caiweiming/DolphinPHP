<?php
declare(strict_types=1);

namespace chart_map\china;

use app\common\abstract\ChartMapProvider;

/**
 * 中国地图专项扩展 Provider
 */
class Provider extends ChartMapProvider
{
    /**
     * @inheritDoc
     */
    public function meta(array $context = []): array
    {
        return [
            'title'       => '中国地图',
            'description' => '基于 Apache ECharts 地图脚本封装的中国地图扩展示例',
        ];
    }

    /**
     * @inheritDoc
     */
    public function definition(array $context = []): array
    {
        return [
            'registerName' => 'china',
            'specialAreas' => [],
            'nameMap'      => [],
            'aliasMap'     => $this->aliasMap(),
            'codeMap'      => $this->codeMap(),
            'regionCoords' => $this->regionCoords(),
        ];
    }

    /**
     * @inheritDoc
     */
    public function normalize(array $data, array $context = []): array
    {
        $definition = $this->definition($context);
        $regions    = [];

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
            $regions[]    = $item;
        }

        $overlays = [];
        foreach ((array)($data['overlays'] ?? []) as $overlay) {
            if (!is_array($overlay)) {
                continue;
            }

            $overlay['data'] = $this->normalizeOverlayData((array)($overlay['data'] ?? []), $definition);
            $overlays[]      = $overlay;
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
     * @inheritDoc
     */
    public function assets(array $context = []): array
    {
        return [
            'css' => [
                dp_static_extend_chart_map_path() . 'china/china.css',
            ],
            'js'  => [
                dp_static_extend_chart_map_path() . 'china/china.js',
            ],
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
        $coordsMap  = is_array($definition['regionCoords'] ?? null) ? $definition['regionCoords'] : [];
        $normalized = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $name  = $this->normalizeRegionName((string)($item['name'] ?? ''), (string)($item['code'] ?? ''), $definition);
            $coord = $item['coord'] ?? ($coordsMap[$name] ?? null);

            $hasLineSource = isset($item['fromName']) || isset($item['fromCode']);
            $hasLineTarget = isset($item['toName']) || isset($item['toCode']);

            if ($hasLineSource && $hasLineTarget) {
                $fromName  = $this->normalizeRegionName((string)($item['fromName'] ?? ''), (string)($item['fromCode'] ?? ''), $definition);
                $toName    = $this->normalizeRegionName((string)($item['toName'] ?? ''), (string)($item['toCode'] ?? ''), $definition);
                $fromCoord = $item['fromCoord'] ?? ($coordsMap[$fromName] ?? null);
                $toCoord   = $item['toCoord'] ?? ($coordsMap[$toName] ?? null);

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
                $value         = $item['value'] ?? null;
                $item['name']  = $name;
                $item['value'] = $value === null ? $coord : array_merge($coord, [$value]);
                unset($item['coord'], $item['code']);
                $normalized[] = $item;
            }
        }

        return $normalized;
    }

    /**
     * 区域别名映射
     * @return array<string, string>
     */
    private function aliasMap(): array
    {
        return [
            '北京市'           => '北京',
            '天津市'           => '天津',
            '河北省'           => '河北',
            '山西省'           => '山西',
            '内蒙古自治区'     => '内蒙古',
            '辽宁省'           => '辽宁',
            '吉林省'           => '吉林',
            '黑龙江省'         => '黑龙江',
            '上海市'           => '上海',
            '江苏省'           => '江苏',
            '浙江省'           => '浙江',
            '安徽省'           => '安徽',
            '福建省'           => '福建',
            '江西省'           => '江西',
            '山东省'           => '山东',
            '河南省'           => '河南',
            '湖北省'           => '湖北',
            '湖南省'           => '湖南',
            '广东省'           => '广东',
            '广西壮族自治区'   => '广西',
            '海南省'           => '海南',
            '重庆市'           => '重庆',
            '四川省'           => '四川',
            '贵州省'           => '贵州',
            '云南省'           => '云南',
            '西藏自治区'       => '西藏',
            '陕西省'           => '陕西',
            '甘肃省'           => '甘肃',
            '青海省'           => '青海',
            '宁夏回族自治区'   => '宁夏',
            '新疆维吾尔自治区' => '新疆',
            '香港特别行政区'   => '香港',
            '澳门特别行政区'   => '澳门',
            '台湾省'           => '台湾',
        ];
    }

    /**
     * 行政区编码映射
     * @return array<string, string>
     */
    private function codeMap(): array
    {
        return [
            '110000' => '北京',
            '120000' => '天津',
            '130000' => '河北',
            '140000' => '山西',
            '150000' => '内蒙古',
            '210000' => '辽宁',
            '220000' => '吉林',
            '230000' => '黑龙江',
            '310000' => '上海',
            '320000' => '江苏',
            '330000' => '浙江',
            '340000' => '安徽',
            '350000' => '福建',
            '360000' => '江西',
            '370000' => '山东',
            '410000' => '河南',
            '420000' => '湖北',
            '430000' => '湖南',
            '440000' => '广东',
            '450000' => '广西',
            '460000' => '海南',
            '500000' => '重庆',
            '510000' => '四川',
            '520000' => '贵州',
            '530000' => '云南',
            '540000' => '西藏',
            '610000' => '陕西',
            '620000' => '甘肃',
            '630000' => '青海',
            '640000' => '宁夏',
            '650000' => '新疆',
            '710000' => '台湾',
            '810000' => '香港',
            '820000' => '澳门',
        ];
    }

    /**
     * 区域中心点坐标
     * @return array<string, array{0: float, 1: float}>
     */
    private function regionCoords(): array
    {
        return [
            '北京'   => [116.405285, 39.904989],
            '天津'   => [117.190182, 39.125596],
            '河北'   => [114.502461, 38.045474],
            '山西'   => [112.549248, 37.857014],
            '内蒙古' => [111.670801, 40.818311],
            '辽宁'   => [123.429096, 41.796767],
            '吉林'   => [125.3245, 43.886841],
            '黑龙江' => [126.642464, 45.756967],
            '上海'   => [121.472644, 31.231706],
            '江苏'   => [118.767413, 32.041544],
            '浙江'   => [120.153576, 30.287459],
            '安徽'   => [117.283042, 31.86119],
            '福建'   => [119.306239, 26.075302],
            '江西'   => [115.892151, 28.676493],
            '山东'   => [117.000923, 36.675807],
            '河南'   => [113.665412, 34.757975],
            '湖北'   => [114.298572, 30.584355],
            '湖南'   => [112.982279, 28.19409],
            '广东'   => [113.280637, 23.125178],
            '广西'   => [108.320004, 22.82402],
            '海南'   => [110.33119, 20.031971],
            '重庆'   => [106.504962, 29.533155],
            '四川'   => [104.065735, 30.659462],
            '贵州'   => [106.713478, 26.578343],
            '云南'   => [102.712251, 25.040609],
            '西藏'   => [91.132212, 29.660361],
            '陕西'   => [108.948024, 34.263161],
            '甘肃'   => [103.823557, 36.058039],
            '青海'   => [101.778916, 36.623178],
            '宁夏'   => [106.278179, 38.46637],
            '新疆'   => [87.617733, 43.792818],
            '台湾'   => [121.509062, 25.044332],
            '香港'   => [114.173355, 22.320048],
            '澳门'   => [113.54909, 22.198951],
        ];
    }
}
