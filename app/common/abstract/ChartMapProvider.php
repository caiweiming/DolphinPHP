<?php
declare(strict_types=1);

namespace app\common\abstract;

use app\common\interface\ChartMapProvider as ChartMapProviderInterface;

/**
 * 图表地图专项扩展 Provider 抽象基类
 */
abstract class ChartMapProvider implements ChartMapProviderInterface
{
    /**
     * 扩展包元信息
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function meta(array $context = []): array
    {
        return [];
    }

    /**
     * 地图数据归一化
     * @param array<string, mixed> $data
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function normalize(array $data, array $context = []): array
    {
        return $data;
    }

    /**
     * 地图扩展资源
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
     * 地图扩展附加载荷
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function payload(array $context = []): array
    {
        return [];
    }

    /**
     * 归一化区域名称
     * @param string $name
     * @param string $code
     * @param array<string, mixed> $definition
     * @return string
     */
    protected function normalizeRegionName(string $name = '', string $code = '', array $definition = []): string
    {
        $aliasMap = is_array($definition['aliasMap'] ?? null) ? $definition['aliasMap'] : [];
        $codeMap  = is_array($definition['codeMap'] ?? null) ? $definition['codeMap'] : [];

        if ($code !== '' && isset($codeMap[$code]) && is_string($codeMap[$code])) {
            return $codeMap[$code];
        }

        if ($name !== '' && isset($aliasMap[$name]) && is_string($aliasMap[$name])) {
            return $aliasMap[$name];
        }

        return $name;
    }
}
