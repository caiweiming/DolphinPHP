<?php
declare(strict_types=1);

namespace app\common\interface;

/**
 * 图表类型构建器接口
 */
interface ChartTypeBuilder
{
    /**
     * 构建类型基础 option
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function build(array $context): array;

    /**
     * 声明扩展资源
     * @param array<string, mixed> $context
     * @return array<string, array>
     */
    public function assets(array $context = []): array;

    /**
     * 下发给前端 hook 的额外载荷
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function payload(array $context = []): array;
}
