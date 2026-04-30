<?php
declare(strict_types=1);

namespace app\common\application;

use RuntimeException;
use think\file\UploadedFile;
use Throwable;

/**
 * 应用商店安装服务
 */
class AppStoreInstaller
{
    /**
     * @param AppPackageSourceManager $sourceManager
     * @param AppPackageIntegrityVerifier $integrityVerifier
     * @param AppPackageImporter $packageImporter
     * @param AppLifecycleManager $lifecycleManager
     */
    public function __construct(
        protected AppPackageSourceManager     $sourceManager,
        protected AppPackageIntegrityVerifier $integrityVerifier,
        protected AppPackageImporter          $packageImporter,
        protected AppLifecycleManager         $lifecycleManager,
    )
    {
    }

    /**
     * 从指定包源安装应用
     * @param string $source
     * @param string $packageId
     * @param int $operatorId
     * @return array<string, mixed>
     */
    public function install(string $source, string $packageId, int $operatorId = 0): array
    {
        $fetchedPackage = $this->sourceManager->fetchPackage($source, $packageId);
        $sourceConfig   = $this->sourceManager->getSourceConfig($source);
        try {
            $verifyResult = $this->integrityVerifier->verify(
                (string)$fetchedPackage['path'],
                $fetchedPackage,
                $sourceConfig
            );

            $uploadedFile = new UploadedFile(
                (string)$fetchedPackage['path'],
                (string)($fetchedPackage['filename'] ?? basename((string)$fetchedPackage['path'])),
                'application/zip',
                UPLOAD_ERR_OK,
                true
            );

            $imported = $this->packageImporter->import($uploadedFile);

            if (!config('app_package.store.allow_auto_install', true)) {
                return [
                    'source'  => $source,
                    'package' => $packageId,
                    'verify'  => $verifyResult,
                    'import'  => $imported,
                    'install' => null,
                    'message' => '应用包已从包源导入，当前配置未启用自动安装',
                ];
            }

            $installed = $this->lifecycleManager->install((string)$imported['name'], $operatorId);

            return [
                'source'  => $source,
                'package' => $packageId,
                'verify'  => $verifyResult,
                'import'  => $imported,
                'install' => $installed,
                'message' => '应用已从包源下载、导入并安装成功',
            ];
        } catch (Throwable $e) {
            throw new RuntimeException('从包源安装应用失败：' . $e->getMessage(), 0, $e);
        } finally {
            $path = (string)($fetchedPackage['path'] ?? '');
            if ($path !== '' && is_file($path)) {
                @unlink($path);
            }
        }
    }
}
