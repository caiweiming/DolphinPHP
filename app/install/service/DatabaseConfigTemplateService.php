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

use RuntimeException;

/**
 * 数据库配置模板服务
 */
class DatabaseConfigTemplateService
{
    public function __construct(private ?string $templatePath = null)
    {
        $this->templatePath ??= root_path() . 'app/install/stubs/database.install.php';
    }

    /**
     * 生成连接配置
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function buildConnectionConfig(array $payload): array
    {
        return [
            'type'            => 'mysql',
            'hostname'        => (string)($payload['hostname'] ?? ''),
            'database'        => (string)($payload['database'] ?? ''),
            'username'        => (string)($payload['username'] ?? ''),
            'password'        => (string)($payload['password'] ?? ''),
            'hostport'        => (string)($payload['hostport'] ?? '3306'),
            'params'          => [],
            'charset'         => 'utf8mb4',
            'prefix'          => (string)($payload['prefix'] ?? 'dp_'),
            'deploy'          => 0,
            'rw_separate'     => false,
            'master_num'      => 1,
            'slave_no'        => '',
            'fields_strict'   => true,
            'break_reconnect' => false,
            'trigger_sql'     => env('APP_DEBUG', true),
            'fields_cache'    => false,
        ];
    }

    /**
     * 渲染数据库配置
     * @param array<string, mixed> $payload
     * @return string
     */
    public function render(array $payload): string
    {
        $template = require $this->templatePath();
        $payload  = $this->buildConnectionConfig($payload);

        $replace = [];
        foreach (['hostname', 'database', 'username', 'password', 'hostport', 'prefix'] as $field) {
            $token = '{{' . $field . '}}';
            $value = var_export((string)($payload[$field] ?? ''), true);

            $replace["'{$token}'"] = $value;
            $replace["\"{$token}\""] = $value;
            $replace[$token] = $value;
        }

        return strtr($template, $replace);
    }

    /**
     * 获取模板文件路径
     * @return string
     */
    public function templatePath(): string
    {
        return $this->templatePath;
    }

    /**
     * 写入数据库配置文件
     * @param array<string, mixed> $payload
     * @param string|null $targetPath
     * @return void
     */
    public function write(array $payload, ?string $targetPath = null): void
    {
        $targetPath ??= root_path() . 'config/database.php';
        $content    = $this->render($payload);
        $targetDir  = dirname($targetPath);
        $tempFile   = @tempnam($targetDir, 'dbcfg_');

        if ($tempFile === false) {
            throw new RuntimeException('写入数据库配置失败：无法在配置目录创建临时文件');
        }

        if (@file_put_contents($tempFile, $content) === false) {
            @unlink($tempFile);
            throw new RuntimeException('写入数据库配置失败：无法写入临时配置文件');
        }

        if (@chmod($tempFile, 0664) === false && is_file($targetPath) === false) {
            @unlink($tempFile);
            throw new RuntimeException('写入数据库配置失败：无法设置配置文件权限');
        }

        if (!@rename($tempFile, $targetPath)) {
            @unlink($tempFile);
            throw new RuntimeException('写入数据库配置失败：无法替换目标配置文件');
        }
    }
}
