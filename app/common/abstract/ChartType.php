<?php
declare(strict_types=1);

namespace app\common\abstract;

use app\common\interface\ChartTypeBuilder as ChartTypeBuilderInterface;

/**
 * 图表类型构建器抽象基类
 */
abstract class ChartType implements ChartTypeBuilderInterface
{
    /**
     * 返回扩展类型附加资源
     * @param array<string, mixed> $context
     * @return array<string, array>
     */
    public function assets(array $context = []): array
    {
        return [
            'css'       => [],
            'js'        => [],
            'extra_css' => [],
            'extra_js'  => [],
            'init_js'   => [],
        ];
    }

    /**
     * 返回扩展类型附加载荷
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function payload(array $context = []): array
    {
        return [];
    }

    /**
     * 构建标准笛卡尔坐标图配置
     * @param array<string, mixed> $context
     * @param string $seriesType
     * @param array<string, mixed> $xAxis
     * @param array<string, mixed> $yAxis
     * @param string $tooltipTrigger
     * @return array<string, mixed>
     */
    protected function buildCartesianOption(
        array  $context,
        string $seriesType,
        array  $xAxis,
        array  $yAxis,
        string $tooltipTrigger = 'axis'
    ): array
    {
        return [
            'tooltip' => ['trigger' => $tooltipTrigger],
            'legend'  => array_replace_recursive(
                [
                    'top'  => 0,
                    'left' => 'center',
                ],
                $this->normalizeLegend($context['option']['legend'] ?? ['show' => true])
            ),
            'grid'    => [
                'left'         => '3%',
                'right'        => '4%',
                'top'          => '56px',
                'bottom'       => '40px',
                'containLabel' => true,
            ],
            'xAxis'   => $xAxis,
            'yAxis'   => $yAxis,
            'series'  => $this->normalizeTypedSeries((array)($context['series'] ?? []), $seriesType),
        ];
    }

    /**
     * 为系列补齐默认类型
     * @param array $series
     * @param string $type
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeTypedSeries(array $series, string $type): array
    {
        $normalized = [];
        foreach ($this->normalizeSeries($series) as $item) {
            $item['type'] = $item['type'] ?? $type;
            $normalized[] = $item;
        }

        return $normalized;
    }

    /**
     * 归一化系列
     * @param array $series
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeSeries(array $series): array
    {
        if ($series === []) {
            return [];
        }

        if (isset($series['data'])) {
            return [$series];
        }

        if (!array_is_list($series)) {
            $normalized = [];
            foreach ($series as $name => $data) {
                $normalized[] = [
                    'name' => is_string($name) ? $name : '',
                    'data' => is_array($data) ? array_values($data) : [$data],
                ];
            }
            return $normalized;
        }

        $first = $series[0] ?? null;
        if (!is_array($first)) {
            return [[
                        'name' => '',
                        'data' => array_values($series),
                    ]];
        }

        if (array_key_exists('data', $first)) {
            return $series;
        }

        return [[
                    'name' => '',
                    'data' => $series,
                ]];
    }

    /**
     * 归一化饼图系列
     * @param array $series
     * @return array<int, array<string, mixed>>
     */
    protected function normalizePieSeries(array $series): array
    {
        if ($series === []) {
            return [[
                        'type'   => 'pie',
                        'radius' => '50%',
                        'data'   => [],
                    ]];
        }

        if (isset($series['data'])) {
            return [[
                        'type'   => $series['type'] ?? 'pie',
                        'radius' => $series['radius'] ?? '50%',
                        ...$series,
                    ]];
        }

        if (array_is_list($series) && isset($series[0]) && is_array($series[0]) && array_key_exists('value', $series[0])) {
            return [[
                        'type'   => 'pie',
                        'radius' => '50%',
                        'data'   => $series,
                    ]];
        }

        $data = [];
        foreach ($series as $name => $value) {
            if (is_array($value) && isset($value['value'])) {
                $data[] = $value + ['name' => is_string($name) ? $name : ($value['name'] ?? '')];
                continue;
            }

            $data[] = [
                'name'  => is_string($name) ? $name : '',
                'value' => $value,
            ];
        }

        return [[
                    'type'   => 'pie',
                    'radius' => '50%',
                    'data'   => $data,
                ]];
    }

    /**
     * 归一化图例
     * @param mixed $legend
     * @return array<string, mixed>
     */
    protected function normalizeLegend(mixed $legend): array
    {
        $legend = $legend ?? ['show' => true];
        if (is_bool($legend)) {
            return ['show' => $legend];
        }

        if (!is_array($legend)) {
            return ['show' => true];
        }

        if (!array_key_exists('show', $legend)) {
            $legend['show'] = true;
        }

        return $legend;
    }
}
