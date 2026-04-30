<?php
declare(strict_types=1);

namespace app\common\plugin;

/**
 * 插件 PSR-4 自动加载器
 */
class PluginAutoloader
{
    /**
     * @var array<string, array<int, string>>
     */
    private array $prefixes = [];

    /**
     * @var bool
     */
    private bool $registered = false;

    /**
     * 注册自动加载器
     * @return void
     */
    public function register(): void
    {
        if ($this->registered) {
            return;
        }

        spl_autoload_register([$this, 'loadClass']);
        $this->registered = true;
    }

    /**
     * 添加 PSR-4 映射
     * @param string $prefix
     * @param string $baseDir
     * @return void
     */
    public function addPsr4(string $prefix, string $baseDir): void
    {
        $prefix  = trim($prefix, '\\') . '\\';
        $baseDir = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        if (!isset($this->prefixes[$prefix])) {
            $this->prefixes[$prefix] = [];
        }

        if (!in_array($baseDir, $this->prefixes[$prefix], true)) {
            $this->prefixes[$prefix][] = $baseDir;
        }
    }

    /**
     * 批量添加映射
     * @param array<string, string> $mappings
     * @param string $pluginRoot
     * @return void
     */
    public function addMappings(array $mappings, string $pluginRoot): void
    {
        foreach ($mappings as $prefix => $relativeDir) {
            $dir = rtrim($pluginRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . trim($relativeDir, '/\\');
            $this->addPsr4($prefix, $dir);
        }
    }

    /**
     * 自动加载类
     * @param string $class
     * @return void
     */
    public function loadClass(string $class): void
    {
        $prefixes = array_keys($this->prefixes);
        usort($prefixes, static fn(string $a, string $b): int => strlen($b) <=> strlen($a));

        foreach ($prefixes as $prefix) {
            if (!str_starts_with($class, $prefix)) {
                continue;
            }

            $relativeClass = substr($class, strlen($prefix));
            $relativePath  = str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

            foreach ($this->prefixes[$prefix] as $baseDir) {
                $file = $baseDir . $relativePath;
                if (is_file($file)) {
                    require_once $file;
                    return;
                }
            }
        }
    }
}
