<?php
declare(strict_types=1);

namespace app\common\plugin;

use think\facade\Db;
use Throwable;

/**
 * 插件仓储
 */
class PluginRepository
{
    /**
     * @param PluginMetadataValidator $metadataValidator
     */
    public function __construct(
        protected PluginMetadataValidator $metadataValidator
    )
    {
    }

    /**
     * @var array<string, PluginDescriptor>|null
     */
    private ?array $descriptors = null;

    /**
     * 获取全部插件
     * @return array<string, PluginDescriptor>
     */
    public function all(): array
    {
        if ($this->descriptors !== null) {
            return $this->descriptors;
        }

        $descriptors = $this->scanPluginDescriptors();
        $records     = $this->getStoredPlugins();

        foreach ($records as $record) {
            $name = (string)($record['name'] ?? '');
            if ($name === '') {
                continue;
            }

            $runtime = [
                'status'    => (string)($record['status'] ?? config('plugin.status.discovered', 'discovered')),
                'installed' => (int)($record['installed'] ?? 0) === 1,
                'enabled'   => (int)($record['enabled'] ?? 0) === 1,
                'error'     => (string)($record['last_error'] ?? ''),
            ];

            if (isset($descriptors[$name])) {
                $descriptors[$name] = $descriptors[$name]->with($runtime);
                continue;
            }

            $descriptors[$name] = PluginDescriptor::fromArray([
                'name'        => $name,
                'title'       => (string)($record['title'] ?? $name),
                'description' => '',
                'version'     => (string)($record['version'] ?? ''),
                'author'      => '',
                'path'        => (string)($record['path'] ?? ''),
                'provider'    => (string)($record['provider'] ?? ''),
                'main'        => (string)($record['main_class'] ?? ''),
                'status'      => (string)($record['status'] ?? config('plugin.status.invalid', 'invalid')),
                'installed'   => (int)($record['installed'] ?? 0) === 1,
                'enabled'     => (int)($record['enabled'] ?? 0) === 1,
                'invalid'     => true,
                'error'       => (string)($record['last_error'] ?? '插件目录不存在或元数据不可用'),
            ]);
        }

        ksort($descriptors);
        $this->descriptors = $descriptors;

        return $this->descriptors;
    }

    /**
     * 查找插件
     * @param string $name
     * @return PluginDescriptor|null
     */
    public function find(string $name): ?PluginDescriptor
    {
        return $this->all()[$name] ?? null;
    }

    /**
     * 获取已启用插件
     * @return array<string, PluginDescriptor>
     */
    public function enabled(): array
    {
        return array_filter($this->all(), static fn(PluginDescriptor $descriptor): bool => $descriptor->isEnabled());
    }

    /**
     * 清空缓存
     * @return void
     */
    public function clear(): void
    {
        $this->descriptors = null;
    }

    /**
     * 插件表是否存在
     * @return bool
     */
    public function storageReady(): bool
    {
        $default = (string)config('database.default', 'mysql');
        $prefix  = (string)config('database.connections.' . $default . '.prefix', 'dp_');
        $table   = $prefix . config('plugin.storage.plugin_table', 'admin_plugin');

        try {
            $result = Db::query("SHOW TABLES LIKE '" . addslashes($table) . "'");
            return $result !== [];
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * 扫描插件目录
     * @return array<string, PluginDescriptor>
     */
    private function scanPluginDescriptors(): array
    {
        $root = rtrim((string)config('plugin.root'), DIRECTORY_SEPARATOR);
        if ($root === '' || !is_dir($root)) {
            return [];
        }

        $descriptors = [];
        foreach (glob($root . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR) ?: [] as $pluginPath) {
            $descriptor = $this->buildDescriptor($pluginPath);
            if ($descriptor === null) {
                continue;
            }

            $name = $descriptor->getName();
            if ($name === '') {
                continue;
            }

            if (isset($descriptors[$name])) {
                $descriptors[$name] = $descriptors[$name]->with([
                    'invalid' => true,
                    'status'  => config('plugin.status.invalid', 'invalid'),
                    'error'   => '存在重复插件标识',
                ]);
                continue;
            }

            $descriptors[$name] = $descriptor;
        }

        return $descriptors;
    }

    /**
     * 构建描述对象
     * @param string $pluginPath
     * @return PluginDescriptor|null
     */
    private function buildDescriptor(string $pluginPath): ?PluginDescriptor
    {
        $jsonFile = rtrim($pluginPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'plugin.json';
        if (!is_file($jsonFile)) {
            return null;
        }

        $raw  = (string)file_get_contents($jsonFile);
        $data = json_decode($raw, true);

        if (!is_array($data)) {
            return PluginDescriptor::fromArray([
                'name'    => basename(dirname($pluginPath)) . '/' . basename($pluginPath),
                'title'   => basename($pluginPath),
                'path'    => $pluginPath,
                'status'  => config('plugin.status.invalid', 'invalid'),
                'invalid' => true,
                'error'   => 'plugin.json 解析失败',
            ]);
        }

        $name  = trim((string)($data['name'] ?? ''));
        $error = $this->validateMetadata($data, $pluginPath);

        return PluginDescriptor::fromArray([
            ...$data,
            'name'    => $name,
            'title'   => (string)($data['title'] ?? $name),
            'path'    => $pluginPath,
            'status'  => $error === '' ? config('plugin.status.discovered', 'discovered') : config('plugin.status.invalid', 'invalid'),
            'invalid' => $error !== '',
            'error'   => $error,
        ]);
    }

    /**
     * 校验元数据
     * @param array<string, mixed> $data
     * @param string $pluginPath
     * @return string
     */
    private function validateMetadata(array $data, string $pluginPath): string
    {
        return $this->metadataValidator->validate($data, $pluginPath);
    }

    /**
     * 获取持久化插件记录
     * @return array<int, array<string, mixed>>
     */
    private function getStoredPlugins(): array
    {
        if (!$this->storageReady()) {
            return [];
        }

        return Db::name((string)config('plugin.storage.plugin_table', 'admin_plugin'))
            ->order('name', 'asc')
            ->select()
            ->toArray();
    }
}
