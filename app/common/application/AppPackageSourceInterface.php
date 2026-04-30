<?php
declare(strict_types=1);

namespace app\common\application;

/**
 * 应用包源接口
 */
interface AppPackageSourceInterface
{
    /**
     * 获取源名称
     * @return string
     */
    public function getName(): string;

    /**
     * 获取源标题
     * @return string
     */
    public function getTitle(): string;

    /**
     * 列出可用应用包
     * @param string $keyword
     * @return array<int, array<string, mixed>>
     */
    public function listPackages(string $keyword = ''): array;

    /**
     * 获取应用包到本地临时路径
     * @param string $packageId
     * @return array<string, mixed>
     */
    public function fetchPackage(string $packageId): array;
}
