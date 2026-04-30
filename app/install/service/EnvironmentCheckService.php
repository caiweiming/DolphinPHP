<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2026 广东卓锐软件有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://www.dolphinphp.com
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\install\service;

/**
 * 安装环境检查服务
 */
class EnvironmentCheckService
{
    /**
     * 执行环境检查
     * @return array<string, mixed>
     */
    public function check(): array
    {
        $extensions = $this->extensions();
        $paths      = $this->paths();
        $php        = [
            'required' => '8.2.0',
            'current'  => PHP_VERSION,
            'passed'   => version_compare(PHP_VERSION, '8.2.0', '>='),
        ];

        return [
            'php'        => $php,
            'extensions' => $extensions,
            'paths'      => $paths,
            'all_passed' => $php['passed']
                && !in_array(false, array_column($extensions, 'passed'), true)
                && !in_array(false, array_column($paths, 'passed'), true),
        ];
    }

    /**
     * 检查扩展
     * @return array<int, array<string, mixed>>
     */
    private function extensions(): array
    {
        $extensions = ['openssl', 'mbstring', 'pdo', 'pdo_mysql', 'curl', 'fileinfo'];

        return array_map(static fn(string $name): array => [
            'name'   => $name,
            'passed' => extension_loaded($name),
        ], $extensions);
    }

    /**
     * 检查写权限
     * @return array<int, array<string, mixed>>
     */
    private function paths(): array
    {
        return [
            [
                'label'  => 'config',
                'path'   => root_path() . 'config',
                'passed' => is_writable(root_path() . 'config'),
            ],
            [
                'label'  => 'runtime',
                'path'   => root_path() . 'runtime',
                'passed' => is_writable(root_path() . 'runtime'),
            ],
        ];
    }
}
