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
 * SQL 导入服务
 */
class SqlImportService
{
    private const CONNECTION_NAME = 'install_runtime';
    private const DEFAULT_SQL_PREFIX = 'dp_';

    /**
     * 导入 SQL 文件
     * @param string $path
     * @param array<string, mixed>|null $connectionConfig
     * @return void
     */
    public function import(string $path, ?array $connectionConfig = null): void
    {
        if (!is_file($path)) {
            throw new RuntimeException('SQL 文件不存在：' . $path);
        }

        $contents = trim((string)file_get_contents($path));
        if ($contents === '') {
            throw new RuntimeException('SQL 文件为空：' . $path);
        }

        if ($connectionConfig !== null) {
            $contents = $this->replaceTablePrefix(
                $contents,
                self::DEFAULT_SQL_PREFIX,
                (string)($connectionConfig['prefix'] ?? self::DEFAULT_SQL_PREFIX)
            );

            $databaseConfig = (array)app()->config->get('database', []);
            $databaseConfig['connections'][self::CONNECTION_NAME] = $connectionConfig;
            app()->config->set($databaseConfig, 'database');
            Db::setConfig(app()->config);
        }

        $db = $connectionConfig === null
            ? Db::connect()
            : Db::connect(self::CONNECTION_NAME, true);
        foreach ($this->splitSqlStatements($contents) as $statement) {
            if ($statement === '') {
                continue;
            }

            $db->execute($statement);
        }
    }

    /**
     * 拆分 SQL 语句
     * @param string $sql
     * @return array<int, string>
     */
    private function splitSqlStatements(string $sql): array
    {
        $statements = [];
        $buffer     = '';
        $quote      = '';
        $length     = strlen($sql);

        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];
            $next = $i + 1 < $length ? $sql[$i + 1] : '';

            if ($quote === '' && $char === '-' && $next === '-') {
                while ($i < $length && !in_array($sql[$i], ["\n", "\r"], true)) {
                    $i++;
                }
                continue;
            }

            if ($quote === '' && $char === '#') {
                while ($i < $length && !in_array($sql[$i], ["\n", "\r"], true)) {
                    $i++;
                }
                continue;
            }

            if ($quote === '' && $char === '/' && $next === '*') {
                $i += 2;
                while ($i < $length - 1 && !($sql[$i] === '*' && $sql[$i + 1] === '/')) {
                    $i++;
                }
                $i++;
                continue;
            }

            if (($char === '\'' || $char === '"') && ($i === 0 || $sql[$i - 1] !== '\\')) {
                $quote = $quote === $char ? '' : ($quote === '' ? $char : $quote);
            }

            if ($char === ';' && $quote === '') {
                $statement = trim($buffer);
                if ($statement !== '') {
                    $statements[] = $statement;
                }
                $buffer = '';
                continue;
            }

            $buffer .= $char;
        }

        $buffer = trim($buffer);
        if ($buffer !== '') {
            $statements[] = $buffer;
        }

        return $statements;
    }

    /**
     * 替换 SQL 文件中的默认表前缀
     * @param string $sql
     * @param string $fromPrefix
     * @param string $toPrefix
     * @return string
     */
    private function replaceTablePrefix(string $sql, string $fromPrefix, string $toPrefix): string
    {
        if ($fromPrefix === '' || $fromPrefix === $toPrefix) {
            return $sql;
        }

        return str_replace('`' . $fromPrefix, '`' . $toPrefix, $sql);
    }
}
