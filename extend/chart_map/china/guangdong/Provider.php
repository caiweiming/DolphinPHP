<?php
declare(strict_types=1);

namespace chart_map\china\guangdong;

use app\common\abstract\ChartMapProvider;

/**
 * 广东省地图专项扩展示例 Provider
 */
class Provider extends ChartMapProvider
{
    /**
     * @inheritDoc
     */
    public function meta(array $context = []): array
    {
        return [
            'title'       => '广东地图',
            'description' => '广东省地市级统计地图示例',
        ];
    }

    /**
     * @inheritDoc
     */
    public function definition(array $context = []): array
    {
        return [
            'registerName' => 'china_guangdong',
            'geoJsonUrl'   => dp_static_extend_chart_map_path() . 'china/guangdong/guangdong.geo.json',
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
                dp_static_extend_chart_map_path() . 'china/guangdong/guangdong.css',
            ],
            'js'  => [
                dp_static_extend_chart_map_path() . 'china/guangdong/guangdong.js',
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    public function payload(array $context = []): array
    {
        return [
            'accentColor' => '#1f7a4d',
            'focusCities' => ['广州市', '深圳市', '佛山市', '东莞市', '珠海市'],
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
            '广州' => '广州市',
            '韶关' => '韶关市',
            '深圳' => '深圳市',
            '珠海' => '珠海市',
            '汕头' => '汕头市',
            '佛山' => '佛山市',
            '江门' => '江门市',
            '湛江' => '湛江市',
            '茂名' => '茂名市',
            '肇庆' => '肇庆市',
            '惠州' => '惠州市',
            '梅州' => '梅州市',
            '汕尾' => '汕尾市',
            '河源' => '河源市',
            '阳江' => '阳江市',
            '清远' => '清远市',
            '东莞' => '东莞市',
            '中山' => '中山市',
            '潮州' => '潮州市',
            '揭阳' => '揭阳市',
            '云浮' => '云浮市',
        ];
    }

    /**
     * 行政区编码映射
     * @return array<string, string>
     */
    private function codeMap(): array
    {
        return [
            '440100' => '广州市',
            '440200' => '韶关市',
            '440300' => '深圳市',
            '440400' => '珠海市',
            '440500' => '汕头市',
            '440600' => '佛山市',
            '440700' => '江门市',
            '440800' => '湛江市',
            '440900' => '茂名市',
            '441200' => '肇庆市',
            '441300' => '惠州市',
            '441400' => '梅州市',
            '441500' => '汕尾市',
            '441600' => '河源市',
            '441700' => '阳江市',
            '441800' => '清远市',
            '441900' => '东莞市',
            '442000' => '中山市',
            '445100' => '潮州市',
            '445200' => '揭阳市',
            '445300' => '云浮市',
        ];
    }

    /**
     * 区域中心点坐标
     * @return array<string, array{0: float, 1: float}>
     */
    private function regionCoords(): array
    {
        return [
            '广州市' => [113.280637, 23.125178],
            '韶关市' => [113.591544, 24.801322],
            '深圳市' => [114.085947, 22.547],
            '珠海市' => [113.553986, 22.224979],
            '汕头市' => [116.708463, 23.37102],
            '佛山市' => [113.122717, 23.028762],
            '江门市' => [113.094942, 22.590431],
            '湛江市' => [110.364977, 21.274898],
            '茂名市' => [110.919229, 21.659751],
            '肇庆市' => [112.472529, 23.051546],
            '惠州市' => [114.412599, 23.079404],
            '梅州市' => [116.117582, 24.299112],
            '汕尾市' => [115.364238, 22.774485],
            '河源市' => [114.697802, 23.746266],
            '阳江市' => [111.975107, 21.859222],
            '清远市' => [113.051227, 23.685022],
            '东莞市' => [113.746262, 23.046237],
            '中山市' => [113.382391, 22.521113],
            '潮州市' => [116.632301, 23.661701],
            '揭阳市' => [116.355733, 23.543778],
            '云浮市' => [112.044439, 22.929801],
        ];
    }
}
