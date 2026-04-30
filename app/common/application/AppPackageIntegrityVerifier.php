<?php
declare(strict_types=1);

namespace app\common\application;

use RuntimeException;

/**
 * 应用包完整性校验器
 */
class AppPackageIntegrityVerifier
{
    /**
     * 校验应用包摘要与签名
     * @param string $path
     * @param array<string, mixed> $package
     * @param array<string, mixed> $sourceConfig
     * @return array<string, mixed>
     */
    public function verify(string $path, array $package, array $sourceConfig = []): array
    {
        if (!is_file($path)) {
            throw new RuntimeException('应用包文件不存在，无法校验');
        }

        $digest = hash_file('sha256', $path);
        if (!is_string($digest) || $digest === '') {
            throw new RuntimeException('应用包摘要计算失败');
        }

        $expectedDigest   = strtolower(trim((string)($package['sha256'] ?? '')));
        $requireIntegrity = !empty($sourceConfig['require_integrity']);
        if ($requireIntegrity && $expectedDigest === '') {
            throw new RuntimeException('当前包源要求提供应用包摘要，但源数据中未声明 sha256');
        }

        if ($expectedDigest !== '' && !hash_equals($expectedDigest, strtolower($digest))) {
            throw new RuntimeException('应用包摘要校验失败');
        }

        $signature = trim((string)($package['signature'] ?? ''));
        $algorithm = trim((string)($package['signature_algorithm'] ?? 'hmac-sha256-digest'));
        if ($signature !== '') {
            $secret = trim((string)($sourceConfig['signature_secret'] ?? ''));
            if ($secret === '') {
                throw new RuntimeException('应用包提供了签名，但当前包源未配置签名密钥');
            }

            $expectedSignature = match ($algorithm) {
                '', 'hmac-sha256-digest' => hash_hmac('sha256', $digest, $secret),
                default => throw new RuntimeException('不支持的应用包签名算法：' . $algorithm),
            };

            if (!hash_equals(strtolower($signature), strtolower($expectedSignature))) {
                throw new RuntimeException('应用包签名校验失败');
            }
        }

        return [
            'sha256'              => $digest,
            'signature_verified'  => $signature !== '',
            'signature_algorithm' => $signature !== '' ? $algorithm : '',
        ];
    }
}
