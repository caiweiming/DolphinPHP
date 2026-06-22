<?php
declare(strict_types=1);

namespace app\cms\service;

use app\common\application\AppLifecycleContext;
use RuntimeException;

/**
 * CMS 应用生命周期钩子
 */
class Hook
{
    /**
     * 安装钩子
     * @param AppLifecycleContext $context
     * @return void
     */
    public function install(AppLifecycleContext $context): void
    {
        $directory = runtime_path() . 'cms';
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('CMS 生命周期演示目录创建失败：' . $directory);
        }

        $payload = [
            'app' => $context->getName(),
            'hook' => 'install',
            'version' => $context->getCurrentVersion(),
            'installed_version' => $context->getInstalledVersion(),
            'database_script' => 'database/install.sql',
            'message' => 'CMS install 钩子已执行，可配合 install.sql 演示 PHP 与 SQL 的协作。',
            'generated_at' => date('Y-m-d H:i:s'),
        ];

        $result = file_put_contents(
            $directory . DIRECTORY_SEPARATOR . 'install-demo.json',
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );

        if ($result === false) {
            throw new RuntimeException('CMS 生命周期演示文件写入失败');
        }
    }

    /**
     * 卸载钩子
     * @param AppLifecycleContext $context
     * @return void
     */
    public function uninstall(AppLifecycleContext $context): void
    {
        $path = runtime_path() . 'cms' . DIRECTORY_SEPARATOR . 'install-demo.json';
        if (is_file($path) && !@unlink($path)) {
            throw new RuntimeException('CMS 生命周期演示文件删除失败：' . $path);
        }
    }

    /**
     * 启用钩子
     * @param AppLifecycleContext $context
     * @return void
     */
    public function enable(AppLifecycleContext $context): void
    {
        $this->writeJsonDemo('status-demo.json', [
            'app' => $context->getName(),
            'hook' => 'enable',
            'status' => 'enabled',
            'message' => 'CMS enable 钩子已执行，可用于接入启用后的运行态初始化。',
            'generated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * 禁用钩子
     * @param AppLifecycleContext $context
     * @return void
     */
    public function disable(AppLifecycleContext $context): void
    {
        $this->writeJsonDemo('status-demo.json', [
            'app' => $context->getName(),
            'hook' => 'disable',
            'status' => 'disabled',
            'message' => 'CMS disable 钩子已执行，可用于停用运行态资源或清理缓存。',
            'generated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * 升级钩子
     * @param string $fromVersion
     * @param string $toVersion
     * @param AppLifecycleContext $context
     * @return void
     */
    public function upgrade(string $fromVersion, string $toVersion, AppLifecycleContext $context): void
    {
        $this->writeJsonDemo('upgrade-demo.json', [
            'app' => $context->getName(),
            'hook' => 'upgrade',
            'from_version' => $fromVersion,
            'to_version' => $toVersion,
            'database_script' => 'database/upgrade.sql',
            'message' => 'CMS upgrade 钩子已执行，可配合 upgrade.sql 演示版本迁移后的 PHP 收尾逻辑。',
            'generated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * 写入 JSON 演示文件
     * @param string $filename
     * @param array<string, mixed> $payload
     * @return void
     */
    private function writeJsonDemo(string $filename, array $payload): void
    {
        $directory = runtime_path() . 'cms';
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('CMS 生命周期演示目录创建失败：' . $directory);
        }

        $result = file_put_contents(
            $directory . DIRECTORY_SEPARATOR . $filename,
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );

        if ($result === false) {
            throw new RuntimeException('CMS 生命周期演示文件写入失败：' . $filename);
        }
    }
}
