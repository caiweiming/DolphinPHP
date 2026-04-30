<?php
/**
 * 数据库配置模板服务测试
 *
 * 验证安装向导在不同文件权限条件下的配置写入行为。
 */

declare(strict_types=1);

namespace tests\Unit\Install;

use app\install\service\DatabaseConfigTemplateService;
use PHPUnit\Framework\TestCase;

class DatabaseConfigTemplateServiceTest extends TestCase
{
    private string $workspace;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workspace = sys_get_temp_dir() . '/dolphinphp-install-test-' . bin2hex(random_bytes(8));
        mkdir($this->workspace, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->workspace);

        parent::tearDown();
    }

    public function testWriteReplacesReadonlyTargetFileWhenDirectoryIsWritable(): void
    {
        $templatePath = $this->workspace . '/database.install.php';
        $targetPath   = $this->workspace . '/database.php';

        file_put_contents($templatePath, <<<'PHP'
<?php

return <<<'TPL'
<?php

return [
    'connections' => [
        'mysql' => [
            'hostname' => {{hostname}},
            'database' => {{database}},
            'username' => {{username}},
            'password' => {{password}},
            'hostport' => {{hostport}},
            'prefix' => {{prefix}},
        ],
    ],
];
TPL;
PHP);

        file_put_contents($targetPath, "<?php return ['old' => true];\n");
        chmod($targetPath, 0444);

        $service = new class($templatePath) extends DatabaseConfigTemplateService {
            public function buildConnectionConfig(array $payload): array
            {
                return [
                    'hostname' => (string)($payload['hostname'] ?? ''),
                    'database' => (string)($payload['database'] ?? ''),
                    'username' => (string)($payload['username'] ?? ''),
                    'password' => (string)($payload['password'] ?? ''),
                    'hostport' => (string)($payload['hostport'] ?? ''),
                    'prefix'   => (string)($payload['prefix'] ?? ''),
                ];
            }
        };

        $service->write([
            'hostname' => '127.0.0.1',
            'database' => 'demo',
            'username' => 'root',
            'password' => 'secret',
            'hostport' => '3306',
            'prefix'   => 'dp_',
        ], $targetPath);

        $content = file_get_contents($targetPath);

        self::assertIsString($content);
        self::assertStringContainsString("'hostname' => '127.0.0.1'", $content);
        self::assertStringContainsString("'database' => 'demo'", $content);
        self::assertStringNotContainsString("'old' => true", $content);
    }

    public function testRenderSupportsQuotedPlaceholdersInsideEnvDefaults(): void
    {
        $templatePath = $this->workspace . '/database.install.php';

        file_put_contents($templatePath, <<<'PHP'
<?php

return <<<'TPL'
<?php

return [
    'connections' => [
        'mysql' => [
            'hostname' => env('DB_HOST', '{{hostname}}'),
            'database' => env('DB_NAME', '{{database}}'),
            'username' => env('DB_USER', '{{username}}'),
            'password' => env('DB_PASS', '{{password}}'),
            'hostport' => env('DB_PORT', '{{hostport}}'),
            'prefix' => env('DB_PREFIX', '{{prefix}}'),
        ],
    ],
];
TPL;
PHP);

        $service = new class($templatePath) extends DatabaseConfigTemplateService {
            public function buildConnectionConfig(array $payload): array
            {
                return [
                    'hostname' => (string)($payload['hostname'] ?? ''),
                    'database' => (string)($payload['database'] ?? ''),
                    'username' => (string)($payload['username'] ?? ''),
                    'password' => (string)($payload['password'] ?? ''),
                    'hostport' => (string)($payload['hostport'] ?? ''),
                    'prefix'   => (string)($payload['prefix'] ?? ''),
                ];
            }
        };

        $content = $service->render([
            'hostname' => 'localhost',
            'database' => 'db',
            'username' => 'root',
            'password' => 'root',
            'hostport' => '3306',
            'prefix'   => 'dp_',
        ]);

        self::assertStringContainsString("'hostname' => env('DB_HOST', 'localhost')", $content);
        self::assertStringContainsString("'database' => env('DB_NAME', 'db')", $content);
        self::assertStringContainsString("'prefix' => env('DB_PREFIX', 'dp_')", $content);
        self::assertStringNotContainsString("''localhost''", $content);
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $items = scandir($directory);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . '/' . $item;
            if (is_dir($path)) {
                $this->removeDirectory($path);
                continue;
            }

            chmod($path, 0666);
            unlink($path);
        }

        rmdir($directory);
    }
}
