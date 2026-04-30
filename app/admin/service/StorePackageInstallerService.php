<?php
declare(strict_types=1);

namespace app\admin\service;

use app\common\application\AppLifecycleManager;
use app\common\application\AppPackageImporter;
use app\common\plugin\PluginManager;
use app\common\plugin\PluginPackageImporter;
use think\file\UploadedFile;

/**
 * 后台商店安装桥接服务
 */
final class StorePackageInstallerService
{
    /**
     * @param AppPackageImporter $appImporter
     * @param AppLifecycleManager $appLifecycleManager
     * @param PluginPackageImporter $pluginImporter
     * @param PluginManager $pluginManager
     */
    public function __construct(
        private AppPackageImporter $appImporter,
        private AppLifecycleManager $appLifecycleManager,
        private PluginPackageImporter $pluginImporter,
        private PluginManager $pluginManager,
    ) {
    }

    /**
     * 安装已下载的应用或插件包
     * @param string $type
     * @param string $path
     * @param string $filename
     * @param int $operatorId
     * @return array
     */
    public function installDownloadedPackage(string $type, string $path, string $filename, int $operatorId): array
    {
        $uploadedFile = new UploadedFile($path, $filename, 'application/zip', UPLOAD_ERR_OK, true);

        if ($type === 'app') {
            $import = $this->appImporter->import($uploadedFile);
            $install = $this->appLifecycleManager->install((string)$import['name'], $operatorId);

            return ['import' => $import, 'install' => $install];
        }

        $import = $this->pluginImporter->import($uploadedFile);
        $install = $this->pluginManager->install((string)$import['name']);

        return ['import' => $import, 'install' => $install];
    }
}
