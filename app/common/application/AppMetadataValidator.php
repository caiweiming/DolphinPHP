<?php
declare(strict_types=1);

namespace app\common\application;

/**
 * 应用分发元数据校验器
 */
class AppMetadataValidator
{
    /**
     * 校验应用静态元数据
     * @param array<string, mixed> $data
     * @param string $appPath
     * @return string
     */
    public function validate(array $data, string $appPath): string
    {
        $name = trim((string)($data['name'] ?? ''));
        if ($name === '' || !preg_match('/^[a-z][a-z0-9_-]{0,59}$/', $name)) {
            return '应用 name 不合法';
        }

        if (in_array($name, (array)config('app_package.reserved_names', []), true)) {
            return '应用标识属于保留名称，禁止分发导入';
        }

        $expected = basename(rtrim($appPath, DIRECTORY_SEPARATOR));
        if ($expected !== '' && $name !== $expected) {
            return '应用目录与 name 不匹配';
        }

        $title = trim((string)($data['title'] ?? ''));
        if ($title === '') {
            return '应用 title 不能为空';
        }

        $version = trim((string)($data['version'] ?? ''));
        if ($version === '' || !preg_match('/^\d+\.\d+\.\d+(?:[-+][A-Za-z0-9.\-]+)?$/', $version)) {
            return '应用 version 不合法';
        }

        $apiVersion = trim((string)($data['app_api_version'] ?? ''));
        if ($apiVersion === '' || $apiVersion !== (string)config('app_package.api_version', '1.0')) {
            return '应用分发协议版本不兼容';
        }

        $code = trim((string)($data['code'] ?? ''));
        if ($code !== '' && !preg_match('/^[A-Za-z][A-Za-z0-9._-]*$/', $code)) {
            return '应用 code 不合法';
        }

        $icon = trim((string)($data['icon'] ?? ''));
        if ($icon !== '' && !preg_match('/^[A-Za-z0-9 _-]+$/', $icon)) {
            return '应用 icon 格式不正确';
        }

        $dependencies = $data['dependencies'] ?? [];
        if (!is_array($dependencies)) {
            return '应用 dependencies 必须为数组';
        }

        foreach ($dependencies as $dependency) {
            $dependency = trim((string)$dependency);
            if ($dependency === '' || !preg_match('/^[a-z][a-z0-9_-]{0,59}$/', $dependency)) {
                return '应用 dependencies 包含非法依赖标识';
            }
        }

        $phpConstraint = trim((string)($data['require']['php'] ?? ''));
        if ($phpConstraint !== '' && !$this->satisfiesPhpConstraint($phpConstraint)) {
            return '当前 PHP 版本不满足应用要求';
        }

        $appConfigFile = rtrim($appPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'app.php';
        if (!is_file($appConfigFile)) {
            return '应用包缺少运行时 app.php 文件';
        }

        return '';
    }

    /**
     * 校验 PHP 版本约束
     * @param string $constraint
     * @return bool
     */
    private function satisfiesPhpConstraint(string $constraint): bool
    {
        $constraint = trim($constraint);
        if ($constraint === '') {
            return true;
        }

        if (!preg_match('/^(>=|<=|>|<|=)?\s*(\d+(?:\.\d+){1,2})$/', $constraint, $matches)) {
            return false;
        }

        $operator = $matches[1] !== '' ? $matches[1] : '>=';
        $version  = $matches[2];

        return version_compare(PHP_VERSION, $version, $operator);
    }
}
