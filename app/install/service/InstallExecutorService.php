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
 * 安装执行器
 */
class InstallExecutorService
{
    private const CONNECTION_NAME = 'install_runtime';

    /**
     * 安装步骤定义
     * @return array<int, array{key:string,label:string}>
     */
    public function steps(): array
    {
        return [
            ['key' => 'write_config', 'label' => '写入数据库配置'],
            ['key' => 'init_connection', 'label' => '初始化数据库连接'],
            ['key' => 'import_database', 'label' => '导入数据库内容'],
            ['key' => 'configure_admin', 'label' => '配置超级管理员'],
            ['key' => 'write_lock', 'label' => '生成安装锁文件'],
            ['key' => 'clear_state', 'label' => '清理安装向导状态'],
        ];
    }

    public function __construct(
        private InstallStateService $installStateService,
        private DatabaseConfigTemplateService $databaseConfigTemplateService,
        private SqlImportService $sqlImportService,
    ) {
    }

    /**
     * 执行安装
     * @param array<string, mixed> $database
     * @param array<string, mixed> $admin
     * @param string $clientIp
     * @return void
     */
    public function execute(array $database, array $admin, string $clientIp = ''): void
    {
        $this->writeDatabaseConfig($database);
        $this->initializeConnection($database);
        $this->importDatabase($database);
        $this->configureAdmin($database, $admin, $clientIp);
        $this->writeInstallLock($database, $admin);
    }

    /**
     * 写入数据库配置
     * @param array<string, mixed> $database
     * @return void
     */
    public function writeDatabaseConfig(array $database): void
    {
        $this->ensureNotInstalled();
        $this->databaseConfigTemplateService->write($database);
    }

    /**
     * 初始化数据库连接
     * @param array<string, mixed> $database
     * @return void
     */
    public function initializeConnection(array $database): void
    {
        $this->ensureNotInstalled();
        $this->prepareRuntimeConnection($database);
        Db::connect(self::CONNECTION_NAME, true)->query('SELECT 1');
    }

    /**
     * 导入数据库内容
     * @param array<string, mixed> $database
     * @return void
     */
    public function importDatabase(array $database): void
    {
        $this->ensureNotInstalled();
        $connectionConfig = $this->prepareRuntimeConnection($database);
        $this->sqlImportService->import(root_path() . 'app/install/sql/dolphin.sql', $connectionConfig);
    }

    /**
     * 配置超级管理员
     * @param array<string, mixed> $database
     * @param array<string, mixed> $admin
     * @param string $clientIp
     * @return void
     */
    public function configureAdmin(array $database, array $admin, string $clientIp = ''): void
    {
        $this->ensureNotInstalled();
        $this->prepareRuntimeConnection($database);

        Db::connect(self::CONNECTION_NAME, true)->name('admin_user')->where('id', 1)->update([
            'username'    => (string)($admin['username'] ?? 'admin'),
            'nickname'    => (string)($admin['nickname'] ?? '超级管理员'),
            'password'    => dp_password_hash((string)($admin['password'] ?? '')),
            'password_updated_time' => time(),
            'email'       => (string)($admin['email'] ?? ''),
            'status'      => 1,
            'reg_ip'      => $clientIp,
            'update_time' => time(),
        ]);
    }

    /**
     * 写入安装锁文件
     * @param array<string, mixed> $database
     * @param array<string, mixed> $admin
     * @return void
     */
    public function writeInstallLock(array $database, array $admin): void
    {
        $this->ensureNotInstalled();
        $this->installStateService->writeLock([
            'installed_at'   => date('Y-m-d H:i:s'),
            'app_version'    => '1.0.0',
            'database'       => (string)($database['database'] ?? ''),
            'db_prefix'      => (string)($database['prefix'] ?? ''),
            'admin_username' => (string)($admin['username'] ?? 'admin'),
        ]);
    }

    /**
     * @param array<string, mixed> $database
     * @return array<string, mixed>
     */
    private function prepareRuntimeConnection(array $database): array
    {
        $connectionConfig = $this->databaseConfigTemplateService->buildConnectionConfig($database);
        $databaseConfig = (array)app()->config->get('database', []);
        $databaseConfig['connections'][self::CONNECTION_NAME] = $connectionConfig;
        app()->config->set($databaseConfig, 'database');
        Db::setConfig(app()->config);

        return $connectionConfig;
    }

    /**
     * @return void
     */
    private function ensureNotInstalled(): void
    {
        if ($this->installStateService->isInstalled()) {
            throw new RuntimeException('系统已安装，不能重复执行');
        }
    }
}
