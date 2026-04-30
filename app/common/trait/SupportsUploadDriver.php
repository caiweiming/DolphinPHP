<?php
declare (strict_types=1);

namespace app\common\trait;

use app\common\service\AssetManager;
use app\common\service\UploadDriverManager;
use Exception;
use think\facade\Config;
use Throwable;

/**
 * 上传驱动支持特征
 * 支持表单项自主指定和扩展驱动类，实现无侵入移植
 */
trait SupportsUploadDriver
{
    /**
     * 驱动类映射表（表单项级别配置）
     * @var array
     */
    protected array $driverClassMap = [];

    /**
     * 自定义驱动类搜索路径
     * @var array
     */
    protected array $customDriverPaths = [];

    /**
     * 表单项前端资源路径映射
     * @var array
     */
    protected array $formItemAssetPaths = [];

    /**
     * 处理上传驱动
     * @param array $params 表单项参数
     * @param object|null $render Form渲染器实例（用于加载驱动配置）
     * @return array
     * @throws Exception
     */
    protected function processUploadDriver(array $params, object $render = null): array
    {
        // 如果没有指定驱动，则取默认驱动
        $params['driver'] = $params['driver'] ?? Config::get('upload.default', 'local');

        // 优先使用表单项指定的驱动类
        $driverClass = $this->resolveDriverClass($params['driver'], $params['type'], $params);

        if ($driverClass) {
            $params = $this->processCustomDriver($params, $driverClass, $params['driver']);
        } else {
            // Fallback到默认流程
            $params = UploadDriverManager::processItem($params, $params['driver']);
        }

        // 加载驱动配置到渲染器变量
        if ($render) {
            $this->loadDriverConfigToVar($params, $render);
        }

        return $params;
    }

    /**
     * 加载驱动配置到表单渲染器
     * @param array $params 表单项配置
     * @param object $render Form渲染器实例
     */
    protected function loadDriverConfigToVar(array $params, object $render): void
    {
        try {
            // 获取驱动实例来获取配置
            $driverName = $params['driver'] . '.' . $params['type'];
            $driverInstance = UploadDriverManager::driver($driverName);
            $config = $driverInstance->config();

            // 获取表单上传驱动配置
            $uploadConfig = $render->getVar('dp_form_upload_config');
            $uploadConfig[$driverName] = $config;

            // 设置表单上传驱动配置
            $render->setVar('dp_form_upload_config', $uploadConfig);
            
            // 调试信息
            if (env('APP_DEBUG')) {
                trace("加载驱动配置到表单: $driverName", 'info');
            }
        } catch (Throwable $e) {
            trace("加载驱动配置失败: $driverName - " . $e->getMessage(), 'warning');
        }
    }

    /**
     * 解析驱动类（表单项主导）
     * @param string $driver 驱动名称
     * @param string $formItemType 表单项类型
     * @param array $params 参数
     * @return string|null
     */
    protected function resolveDriverClass(string $driver, string $formItemType, array $params): ?string
    {
        // 1. 检查参数中的直接指定
        if (isset($params['driver_class'])) {
            return $params['driver_class'];
        }

        // 2. 检查表单项的驱动映射表
        if (isset($this->driverClassMap[$driver])) {
            return $this->driverClassMap[$driver];
        }

        // 3. 自动查找表单项特定驱动
        $customClass = $this->findCustomDriver($driver, $formItemType);
        if ($customClass) {
            return $customClass;
        }

        return null;
    }

    /**
     * 自动查找表单项专用驱动
     * @param string $driver 驱动名称
     * @param string $formItemType 表单项类型
     * @return string|null
     */
    protected function findCustomDriver(string $driver, string $formItemType): ?string
    {
        $searchPaths = array_merge([
            // 表单项同级drivers目录
            $this->getFormItemNamespace() . '\\drivers\\' . ucfirst($driver) . 'Driver',
            // 表单项子目录  
            $this->getFormItemNamespace() . '\\driver\\' . ucfirst($driver) . 'Driver',
            // 通用自定义驱动目录
            'upload\\' . strtolower($driver) . '\\' . ucfirst($formItemType),
        ], $this->customDriverPaths);

        foreach ($searchPaths as $className) {
            if (class_exists($className)) {
                return $className;
            }
        }

        return null;
    }

    /**
     * 处理自定义驱动
     * @param array $params 参数
     * @param string $driverClass 驱动类名
     * @param string $driverName 驱动名称
     * @return array
     * @throws Exception
     */
    protected function processCustomDriver(array $params, string $driverClass, string $driverName): array
    {
        if (!class_exists($driverClass)) {
            throw new Exception("自定义驱动类不存在: $driverClass");
        }

        // 实例化自定义驱动
        $driverInstance = new $driverClass();

        // 处理参数
        $params = $driverInstance->handle($params);

        // 处理表单项自定义驱动的前端资源加载
        $this->loadFormItemDriverAssets($driverName, $driverInstance, $params['type']);

        return $params;
    }

    /**
     * 加载表单项自定义驱动的前端资源
     * @param string $driverName 驱动名称
     * @param object $driverInstance 驱动实例
     * @param string $formItemType 表单项类型
     */
    protected function loadFormItemDriverAssets(string $driverName, object $driverInstance, string $formItemType): void
    {
        // 1. 优先加载驱动本身的资源
        if (method_exists($driverInstance, 'js') && method_exists($driverInstance, 'css')) {
            $js  = $driverInstance->js();
            $css = $driverInstance->css();

            if (!empty($js) || !empty($css)) {
                // 直接集成 AssetManager（类似 Form::parseUploadDriverLegacy 的做法）
                try {
                    $assetManager = AssetManager::instance();
                    $assetId      = 'form_item_driver_' . $formItemType . '_' . $driverName;

                    if (!$assetManager->isLoaded($assetId)) {
                        if (!empty($js)) {
                            $assetManager->addJs($js, 25);
                        }
                        if (!empty($css)) {
                            $assetManager->addCss($css, 25);
                        }
                        $assetManager->markAsLoaded($assetId);

                        // 调试信息
                        if (env('APP_DEBUG')) {
                            $jsCount  = !empty($js) ? count($js) : 0;
                            $cssCount = !empty($css) ? count($css) : 0;
                            trace("加载表单项自定义驱动资源: $formItemType-$driverName (JS: $jsCount, CSS: $cssCount)", 'info');
                        }
                    }
                } catch (Throwable $e) {
                    trace("表单项驱动资源加载失败: $formItemType-$driverName - " . $e->getMessage(), 'warning');
                }
                return;
            }
        }

        // 2. 查找表单项特定的前端资源文件
        $possiblePaths = [
            // 统一在public/extend/目录下查找表单项特定资源
            'public/extend/upload/' . $driverName . '/' . $formItemType . '/',
            // 自定义资源路径（通过 addFormItemAssetPath 添加）
            ...$this->getFormItemAssetPaths($formItemType, $driverName)
        ];

        foreach ($possiblePaths as $path) {
            if ($this->loadAssetsFromPath($path, $driverName, $formItemType)) {
                break; // 找到有效路径就停止搜索
            }
        }
    }

    /**
     * 从指定路径加载前端资源
     * @param string $path 资源路径
     * @param string $driverName 驱动名称
     * @param string $formItemType 表单项类型
     * @return bool 是否成功加载
     */
    protected function loadAssetsFromPath(string $path, string $driverName, string $formItemType): bool
    {
        $rootPath = root_path();
        $fullPath = $rootPath . ltrim($path, '/');

        if (!is_dir($fullPath)) {
            return false;
        }

        $jsFiles  = glob($fullPath . '*.js');
        $cssFiles = glob($fullPath . '*.css');

        if (empty($jsFiles) && empty($cssFiles)) {
            return false;
        }

        try {
            $assetManager = app('asset.manager');
            $assetId      = 'form_item_path_' . $formItemType . '_' . $driverName;

            if (!$assetManager->isLoaded($assetId)) {
                // 转换文件路径为前端可访问的URL格式
                $jsAssets  = [];
                $cssAssets = [];

                foreach ($jsFiles as $jsFile) {
                    $relativePath = str_replace(public_path(), '', $jsFile);
                    $jsAssets[]   = '__ROOT__' . $relativePath;
                }

                foreach ($cssFiles as $cssFile) {
                    $relativePath = str_replace(public_path(), '', $cssFile);
                    $cssAssets[]  = '__ROOT__' . $relativePath;
                }

                if (!empty($jsAssets)) {
                    $assetManager->addJs($jsAssets, 25);
                }

                if (!empty($cssAssets)) {
                    $assetManager->addCss($cssAssets, 25);
                }

                $assetManager->markAsLoaded($assetId);

                // 调试信息
                if (env('APP_DEBUG')) {
                    $jsCount  = count($jsAssets);
                    $cssCount = count($cssAssets);
                    trace("从路径加载表单项驱动资源: $formItemType-$driverName 路径: $path (JS: $jsCount, CSS: $cssCount)", 'info');
                }
            }

            return true;
        } catch (Throwable $e) {
            trace("从路径加载资源失败: $formItemType-$driverName 路径: $path - " . $e->getMessage(), 'warning');
            return false;
        }
    }

    /**
     * 获取表单项的自定义前端资源路径
     * @param string $formItemType 表单项类型
     * @param string $driverName 驱动名称
     * @return array
     */
    protected function getFormItemAssetPaths(string $formItemType, string $driverName): array
    {
        $key = $formItemType . '.' . $driverName;
        return $this->formItemAssetPaths[$key] ?? [];
    }

    /**
     * 获取表单项命名空间
     * @return string
     */
    protected function getFormItemNamespace(): string
    {
        $class = get_class($this);
        $parts = explode('\\', $class);
        array_pop($parts); // 移除类名（如 'Item'）
        return implode('\\', $parts);
    }

    /**
     * 注册驱动类映射（供子类调用）
     * @param string $driver 驱动名称
     * @param string $className 驱动类名
     */
    protected function registerDriverClass(string $driver, string $className): void
    {
        $this->driverClassMap[$driver] = $className;
    }

    /**
     * 添加自定义驱动搜索路径
     * @param string $path 搜索路径模式
     */
    protected function addCustomDriverPath(string $path): void
    {
        $this->customDriverPaths[] = $path;
    }

    /**
     * 为表单项特定驱动添加前端资源路径
     * @param string $formItemType 表单项类型
     * @param string $driverName 驱动名称
     * @param string $assetPath 前端资源路径（相对于项目根目录）
     */
    protected function addFormItemAssetPath(string $formItemType, string $driverName, string $assetPath): void
    {
        $key = $formItemType . '.' . $driverName;
        if (!isset($this->formItemAssetPaths[$key])) {
            $this->formItemAssetPaths[$key] = [];
        }
        $this->formItemAssetPaths[$key][] = $assetPath;
    }

    /**
     * 检查是否支持上传驱动
     * @param array $params
     * @return bool
     */
    protected function supportsUploadDriver(array $params): bool
    {
        return isset($params['driver']) || (isset($params['upload']) && $params['upload'] !== false);
    }
}