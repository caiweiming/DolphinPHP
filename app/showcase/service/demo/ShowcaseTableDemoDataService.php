<?php
declare(strict_types=1);

namespace app\showcase\service\demo;

use RuntimeException;
use think\facade\Db;

/**
 * 表格示例演示数据服务
 *
 * 用于初始化和重置表格示例页依赖的演示数据。
 * 数据源统一复用 app/showcase/database/install.sql，避免 SQL 与 PHP 双份维护。
 */
final class ShowcaseTableDemoDataService
{
    /**
     * 重置表格示例演示数据。
     */
    public function resetDemoData(): void
    {
        $this->ensureTables();

        Db::transaction(function (): void {
            $this->truncateTables();
            $this->runInstallSql();
        });
    }

    /**
     * 确保演示表存在。
     */
    private function ensureTables(): void
    {
        $this->runInstallSql();
    }

    /**
     * 执行 Showcase 安装 SQL。
     */
    private function runInstallSql(): void
    {
        $sqlFile = root_path() . 'app/showcase/database/install.sql';

        if (!is_file($sqlFile)) {
            throw new RuntimeException(sprintf('SQL 文件不存在: %s', $sqlFile));
        }

        $contents = file_get_contents($sqlFile);
        if ($contents === false) {
            throw new RuntimeException(sprintf('SQL 文件读取失败: %s', $sqlFile));
        }

        $contents = preg_replace('/^\s*--.*$/m', '', $contents) ?? '';
        $contents = trim($contents);
        $statements = preg_split('/;\s*(?:\R|$)/', $contents) ?: [];

        foreach ($statements as $statement) {
            $statement = trim($statement);
            if ($statement === '') {
                continue;
            }

            Db::execute($statement);
        }
    }

    /**
     * 清空三张演示表旧数据。
     */
    private function truncateTables(): void
    {
        Db::execute('DELETE FROM `dp_showcase_table_media`');
        Db::execute('DELETE FROM `dp_showcase_table_complex`');
        Db::execute('DELETE FROM `dp_showcase_table`');
    }
}
