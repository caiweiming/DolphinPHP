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
use think\facade\Db;

/**
 * 安装数据库检测与建库服务
 */
class DatabaseProvisionService
{
    private const SERVER_CONNECTION_NAME = 'install_runtime_server';
    private const DATABASE_CONNECTION_NAME = 'install_runtime_database';

    public function __construct(private readonly DatabaseConfigTemplateService $templateService)
    {
    }

    /**
     * 校验数据库连接并在需要时创建数据库
     * @param array<string, mixed> $payload
     * @return void
     */
    public function ensureReady(array $payload): void
    {
        $connectionConfig = $this->templateService->buildConnectionConfig($payload);

        if ($this->shouldCreateDatabase($payload)) {
            $databaseName = (string)($payload['database'] ?? '');
            $this->ensureValidDatabaseName($databaseName);

            $serverConfig = $connectionConfig;
            $serverConfig['database'] = '';

            $server = $this->connect(self::SERVER_CONNECTION_NAME, $serverConfig);
            $server->query('SELECT 1 AS ok');

            $this->createDatabaseIfMissing($server, $databaseName);
        }

        $database = $this->connect(self::DATABASE_CONNECTION_NAME, $connectionConfig);
        $database->query('SELECT 1 AS ok');
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function shouldCreateDatabase(array $payload): bool
    {
        return (string)($payload['create_database'] ?? '') === '1';
    }

    /**
     * @param array<string, mixed> $connectionConfig
     * @return object
     */
    private function connect(string $name, array $connectionConfig): object
    {
        $databaseConfig = (array)app()->config->get('database', []);
        $databaseConfig['connections'][$name] = $connectionConfig;
        app()->config->set($databaseConfig, 'database');
        Db::setConfig(app()->config);

        return Db::connect($name, true);
    }

    private function ensureValidDatabaseName(string $databaseName): void
    {
        if ($databaseName === '' || !preg_match('/^[A-Za-z0-9_]+$/', $databaseName)) {
            throw new RuntimeException('数据库名称仅支持字母、数字和下划线');
        }
    }

    private function createDatabaseIfMissing(object $connection, string $databaseName): void
    {
        $exists = $connection->query(
            "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '{$databaseName}'"
        );

        if (!empty($exists)) {
            return;
        }

        $connection->execute(
            sprintf(
                'CREATE DATABASE `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
                $databaseName
            )
        );
    }
}
