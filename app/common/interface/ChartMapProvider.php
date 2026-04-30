<?php
declare(strict_types=1);

namespace app\common\interface;

/**
 * 图表地图专项扩展 Provider 接口
 */
interface ChartMapProvider
{
    /**
     * 扩展包元信息
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function meta(array $context = []): array;

    /**
     * 地图资源定义
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function definition(array $context = []): array;

    /**
     * 地图数据归一化
     * @param array<string, mixed> $data
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function normalize(array $data, array $context = []): array;

    /**
     * 地图扩展资源
     * @param array<string, mixed> $context
     * @return array<string, array>
     */
    public function assets(array $context = []): array;

    /**
     * 地图扩展附加载荷
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function payload(array $context = []): array;
}
