<?php
declare(strict_types=1);

namespace app\common\plugin;

/**
 * 插件元数据校验器
 */
class PluginMetadataValidator
{
    /**
     * 校验插件元数据
     * @param array<string, mixed> $data
     * @param string $pluginPath
     * @return string
     */
    public function validate(array $data, string $pluginPath): string
    {
        $name = trim((string)($data['name'] ?? ''));
        if ($name === '' || !preg_match('#^[a-z0-9]+(?:-[a-z0-9]+)*/[a-z0-9]+(?:-[a-z0-9]+)*$#', $name)) {
            return '插件 name 不合法';
        }

        $expected = basename(dirname($pluginPath)) . '/' . basename($pluginPath);
        if ($name !== $expected) {
            return '插件目录与 name 不匹配';
        }

        $version = trim((string)($data['version'] ?? ''));
        if ($version === '' || !preg_match('/^\d+\.\d+\.\d+(?:[-+][A-Za-z0-9.\-]+)?$/', $version)) {
            return '插件 version 不合法';
        }

        $apiVersion = trim((string)($data['plugin_api_version'] ?? ''));
        if ($apiVersion === '' || $apiVersion !== (string)config('plugin.api_version', '1.0')) {
            return '插件协议版本不兼容';
        }

        foreach (['provider', 'main'] as $field) {
            $class = trim((string)($data[$field] ?? ''));
            if ($class === '' || !preg_match('/^(?:[A-Za-z_][A-Za-z0-9_]*\\\\)*[A-Za-z_][A-Za-z0-9_]*$/', $class)) {
                return '插件 ' . $field . ' 类名不合法';
            }
        }

        $autoload = (array)($data['autoload']['psr-4'] ?? []);
        if ($autoload === []) {
            return '插件 autoload.psr-4 不能为空';
        }

        $pluginRoot = realpath($pluginPath) ?: $pluginPath;
        foreach ($autoload as $prefix => $relativePath) {
            if (!is_string($prefix) || !is_string($relativePath) || trim($prefix) === '' || trim($relativePath) === '') {
                return '插件 autoload.psr-4 配置不合法';
            }

            $targetPath = rtrim($pluginPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . trim($relativePath, '/\\');
            $targetBase = realpath(dirname($targetPath)) ?: dirname($targetPath);
            if (!str_starts_with($targetBase, $pluginRoot)) {
                return '插件 autoload 路径超出插件目录';
            }
        }

        $composerError = $this->validateComposerManifest($pluginPath, $name, $autoload);
        if ($composerError !== '') {
            return $composerError;
        }

        return '';
    }

    /**
     * 校验 Composer 包元数据
     * @param string $pluginPath
     * @param string $pluginName
     * @param array<string, string> $pluginPsr4
     * @return string
     */
    private function validateComposerManifest(string $pluginPath, string $pluginName, array $pluginPsr4): string
    {
        $composerFile = rtrim($pluginPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'composer.json';
        if (!is_file($composerFile)) {
            return '';
        }

        $composerData = json_decode((string)file_get_contents($composerFile), true);
        if (!is_array($composerData)) {
            return 'composer.json 解析失败';
        }

        $composerName = trim((string)($composerData['name'] ?? ''));
        if ($composerName !== '' && $composerName !== $pluginName) {
            return 'composer.json.name 必须与 plugin.json.name 一致';
        }

        $expectedType = trim((string)config('plugin.composer.package_type', 'dolphinphp-plugin'));
        $packageType  = trim((string)($composerData['type'] ?? ''));
        if ($packageType !== '' && $packageType !== $expectedType) {
            return 'composer.json.type 必须为 ' . $expectedType . ' 才能按插件协议分发';
        }

        $composerPsr4 = (array)($composerData['autoload']['psr-4'] ?? []);
        if ($composerPsr4 !== [] && $composerPsr4 !== $pluginPsr4) {
            return 'composer.json.autoload.psr-4 必须与 plugin.json 保持一致';
        }

        return '';
    }
}
