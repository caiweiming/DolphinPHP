<?php
declare (strict_types=1);

namespace app\common\service;

use app\common\interface\UploadDriver;
use app\common\exception\UploadDriverException;
use app\common\plugin\PluginRegistry;
use Exception;
use ReflectionClass;
use Throwable;

/**
 * 上传驱动管理器
 * 负责驱动的注册、发现、实例化和管理
 */
class UploadDriverManager
{
    /**
     * 最大缓存实例数量
     */
    private const MAX_CACHE_SIZE = 100;

    /**
     * 驱动注册表
     * @var array
     */
    protected static array $drivers = [];

    /**
     * 驱动配置缓存
     * @var array
     */
    protected static array $configs = [];

    /**
     * 驱动实例缓存
     * @var array
     */
    protected static array $instances = [];

    /**
     * 是否已完成驱动发现
     * @var bool
     */
    protected static bool $discovered = false;

    /**
     * 缓存的根路径realpath结果
     * @var string|null
     */
    protected static ?string $cachedRootPath = null;

    /**
     * 注册驱动
     * @param string $name 驱动名称
     * @param string $class 驱动类名（支持完整命名空间）
     * @param array $config 驱动配置
     */
    public static function register(string $name, string $class, array $config = []): void
    {
        self::$drivers[$name] = [
            'class'         => $class,
            'config'        => $config,
            'registered_at' => time(),
            'trusted'       => !str_starts_with($class, 'upload\\'),
        ];

        self::$configs[$name] = $config;
    }

    /**
     * 自动发现驱动
     * 支持多个搜索路径和命名空间
     */
    public static function discover(): void
    {
        if (self::$discovered) {
            return;
        }

        $cacheKey = config('upload.discovery.cache_key', 'upload_drivers_cache');
        $useCache = config('upload.discovery.cache', true);

        // 尝试从缓存加载
        if ($useCache && ($cached = cache($cacheKey))) {
            self::$drivers    = $cached['drivers'];
            self::$configs    = $cached['configs'];
            self::$discovered = true;
            return;
        }

        $discoveryPaths = config('upload.discovery.paths', [
            'core'   => 'extend/upload',
            'vendor' => 'vendor/*/*/upload',
            'custom' => 'app/upload',
        ]);

        foreach ($discoveryPaths as $type => $basePath) {
            self::scanDriversInPath($basePath, $type);
        }

        self::registerPluginDrivers();

        // 缓存结果
        if ($useCache) {
            cache($cacheKey, [
                'drivers'   => self::$drivers,
                'configs'   => self::$configs,
                'timestamp' => time()
            ], config('upload.discovery.cache_time', 3600));
        }

        self::$discovered = true;
    }

    /**
     * 获取驱动实例
     * @param string $name 驱动名称
     * @param array $context 上下文数据
     * @return UploadDriver
     * @throws Exception
     */
    public static function driver(string $name, array $context = []): UploadDriver
    {
        try {
            // 管理缓存大小，防止内存溢出
            static $instanceCount = 0;

            if ($instanceCount >= self::MAX_CACHE_SIZE) {
                // 批量清理策略：一次性清理到75%容量，减少频繁清理
                $targetCount  = (int)(self::MAX_CACHE_SIZE * 0.75);
                $keysToRemove = array_slice(array_keys(self::$instances), 0, $instanceCount - $targetCount);

                foreach ($keysToRemove as $key) {
                    unset(self::$instances[$key]);
                }
                $instanceCount = $targetCount;

                trace("缓存优化：清理 " . count($keysToRemove) . " 个实例，当前: $instanceCount", 'info');
            }

            $cacheKey = $name . '_' . md5(serialize($context));

            if (isset(self::$instances[$cacheKey])) {
                return self::$instances[$cacheKey];
            }

            // 避免不必要的函数调用
            if (!self::$discovered) {
                self::discover();
            }

            if (!isset(self::$drivers[$name])) {
                throw new UploadDriverException(
                    "驱动未注册: $name",
                    UploadDriverException::DRIVER_NOT_FOUND
                );
            }

            $driver    = self::$drivers[$name];
            $className = $driver['class'];

            // 再次验证类安全性
            if (!self::validateDriverClass($className)) {
                throw new UploadDriverException(
                    "驱动类安全验证失败: $className",
                    UploadDriverException::DRIVER_CLASS_INVALID
                );
            }
            // 安全实例化
            if (!class_exists($className)) {
                throw new UploadDriverException(
                    "驱动类不存在: $className",
                    UploadDriverException::DRIVER_CLASS_INVALID
                );
            }

            // 实例化驱动
            $instance = new $className(self::$configs[$name] ?? []);

            // 验证实例是否实现了正确的接口
            if (!$instance instanceof UploadDriver) {
                throw new UploadDriverException(
                    "驱动实例不符合接口要求: $className",
                    UploadDriverException::DRIVER_CLASS_INVALID
                );
            }

            self::$instances[$cacheKey] = $instance;
            $instanceCount++;
            return $instance;

        } catch (UploadDriverException $e) {
            // 直接重新抛出自定义异常
            throw $e;
        } catch (Throwable $e) {
            trace('驱动实例化异常: ' . $e->getMessage(), 'error');
            throw new UploadDriverException(
                '驱动创建失败: ' . $e->getMessage(),
                UploadDriverException::DRIVER_CREATION_FAILED,
                $e
            );
        }
    }

    /**
     * 处理表单项的上传配置
     * @param array $item 表单项数据
     * @param string|null $driver 指定驱动
     * @return array
     * @throws Exception
     */
    public static function processItem(array $item, string $driver = null): array
    {
        $driver         = $driver ?: $item['driver'] ?? config('upload.default', 'local');
        $driverInstance = self::driver($driver, $item);

        // 调用驱动处理
        $item = $driverInstance->handle($item);

        // 注册资源加载
        self::loadDriverAssets($driver, $driverInstance);

        return $item;
    }

    /**
     * 获取驱动配置
     * @param string $name 驱动名称
     * @return array
     * @throws Exception
     */
    public static function getDriverConfig(string $name): array
    {
        self::discover();

        if (!isset(self::$configs[$name])) {
            throw new UploadDriverException(
                "驱动配置未找到: $name",
                UploadDriverException::DRIVER_CONFIG_INVALID
            );
        }

        $config = self::$configs[$name];

        // 确保返回数组类型
        if (!is_array($config)) {
            trace("驱动 $name 配置不是数组类型: " . gettype($config), 'error');
            return [];
        }

        return $config;
    }

    /**
     * 获取驱动完整信息（包含配置文件内容）
     * @param string $name 驱动名称
     * @return array|null
     * @throws UploadDriverException
     */
    public static function getDriverFullConfig(string $name): ?array
    {
        self::discover();

        if (!isset(self::$drivers[$name])) {
            return null;
        }

        $driverInfo = self::$drivers[$name];
        $configFile = $driverInfo['backend_path'] . '/config.php';

        if (self::isSecureFile($configFile)) {
            try {
                return self::secureRequire($configFile);
            } catch (Throwable $e) {
                trace("加载驱动完整配置失败: $name - " . $e->getMessage(), 'error');
                return null;
            }
        }

        return null;
    }

    /**
     * 加载驱动资源
     * @param string $name 驱动名称
     * @param UploadDriver $driver 驱动实例
     */
    protected static function loadDriverAssets(string $name, UploadDriver $driver): void
    {
        try {
            // 获取AssetManager实例
            $assetManager = AssetManager::instance();
            $assetId      = 'upload_driver_' . $name;

            // 检查是否已加载，避免重复加载
            if (!$assetManager->isLoaded($assetId)) {
                // 获取资源列表
                $jsFiles  = $driver->js();
                $cssFiles = $driver->css();

                // 处理JS资源
                if (!empty($jsFiles)) {
                    $assetManager->addJs($jsFiles, 25);
                }

                // 处理CSS资源
                if (!empty($cssFiles)) {
                    $assetManager->addCss($cssFiles, 25);
                }

                // 标记驱动资源为已加载
                $assetManager->markAsLoaded($assetId);

                // 调试信息
                if (env('APP_DEBUG')) {
                    $jsCount  = !empty($jsFiles) ? count($jsFiles) : 0;
                    $cssCount = !empty($cssFiles) ? count($cssFiles) : 0;
                    trace("加载上传驱动资源: $name (JS: $jsCount, CSS: $cssCount)", 'info');
                }
            }
        } catch (Throwable $e) {
            trace("资源加载失败: $name - " . $e->getMessage(), 'warning');
        }
    }

    /**
     * 扫描指定路径下的驱动
     * @param string $basePath 基础路径
     * @param string $type 驱动类型
     */
    protected static function scanDriversInPath(string $basePath, string $type): void
    {
        try {
            $rootPath      = root_path();
            $sanitizedPath = self::sanitizePath($basePath);

            // 处理通配符路径
            $fullPath = $rootPath . $sanitizedPath;
            if (str_contains($sanitizedPath, '*')) {
                $directories = glob($fullPath, GLOB_ONLYDIR | GLOB_NOSORT);
            } else {
                $directories = is_dir($fullPath) ? glob($fullPath . '/*', GLOB_ONLYDIR | GLOB_NOSORT) : [];
            }

            if ($directories === false) {
                trace("驱动扫描失败: 无效路径 $basePath", 'warning');
                return;
            }

            foreach ($directories as $driverDir) {
                if (!self::isValidDriverDirectory($driverDir)) {
                    continue;
                }

                $driverName = self::getSecureBasename($driverDir);

                // 检查必需文件
                $configFile = $driverDir . '/config.php';

                if (self::isSecureFile($configFile)) {
                    self::registerDriverFromPath($driverName, $driverDir, $type);
                }
            }
        } catch (Throwable $e) {
            trace("驱动扫描异常: " . $e->getMessage(), 'error');
        }
    }

    /**
     * 从驱动路径注册驱动
     * @param string $name 驱动名称
     * @param string $driverPath 驱动路径
     * @param string $type 驱动类型
     * @throws UploadDriverException
     */
    protected static function registerDriverFromPath(string $name, string $driverPath, string $type): void
    {
        $configFile = $driverPath . '/config.php';

        if (!self::isSecureFile($configFile)) {
            trace("配置文件安全检查失败: $configFile", 'warning');
            return;
        }

        try {
            // 安全加载配置文件
            $driverConfig = self::secureRequire($configFile);

            // 验证配置格式
            if (!self::validateDriverConfig($driverConfig)) {
                throw new Exception("驱动配置格式无效: $name");
            }

            // 构建驱动信息（支持多驱动类自动发现）
            if (!empty($driverConfig['driver']['class'])) {
                // 如果配置了 class，优先使用配置
                $driverClasses = [$driverConfig['driver']['class']];
                trace("驱动 $name 使用配置的 class: {$driverConfig['driver']['class']}", 'info');
            } else {
                // 如果没有配置 class，自动发现目录下的所有驱动类
                $driverClasses = self::discoverDriverClasses($name, $driverPath);
                trace("驱动 $name 自动发现的类: " . implode(', ', $driverClasses), 'info');
            }

            // 注册所有发现的驱动类
            foreach ($driverClasses as $driverClass) {
                $classBaseName = self::getClassBaseName($driverClass);
                $driverKey     = $classBaseName === 'Driver' ? $name : $name . '.' . strtolower($classBaseName);

                $driverInfo = [
                    'name'          => $driverKey,
                    'class'         => $driverClass,
                    'type'          => $type,
                    'backend_path'  => $driverPath,
                    'frontend_path' => public_path() . 'extend/upload/' . $name,
                    'meta'          => $driverConfig['meta'] ?? [],
                    'registered_at' => time(),
                    'base_driver'   => $name // 标记基础驱动名
                ];

                // 注册驱动
                self::$drivers[$driverKey] = $driverInfo;

                // 确保配置是数组类型
                $config = $driverConfig['config'] ?? [];
                if (!is_array($config)) {
                    trace("驱动 $name 的 config 节点不是数组: " . gettype($config), 'error');
                    $config = [];
                }

                self::$configs[$driverKey] = $config;

                trace("驱动类 $driverKey ($driverClass) 注册成功", 'info');
            }
        } catch (Throwable $e) {
            // 记录错误日志，但不中断发现过程
            trace("驱动注册失败: $name, 错误: " . $e->getMessage(), 'error');
        }
    }

    /**
     * 自动发现驱动目录下的所有驱动类
     * @param string $name 驱动名称
     * @param string $driverPath 驱动路径
     * @return array 驱动类列表
     */
    protected static function discoverDriverClasses(string $name, string $driverPath): array
    {
        $driverClasses = [];
        try {
            // 扫描目录下的所有PHP文件（排除config.php）
            $phpFiles = glob($driverPath . '/*.php');

            foreach ($phpFiles as $phpFile) {
                $fileName = basename($phpFile, '.php');

                // 跳过配置文件
                if ($fileName === 'config') {
                    continue;
                }

                // 验证文件名格式
                if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $fileName)) {
                    trace("跳过无效的驱动类文件: $fileName", 'warning');
                    continue;
                }

                // 构建类名
                $className = 'upload\\' . $name . '\\' . $fileName;

                // 验证类的安全性
                if (self::validateDriverClass($className)) {
                    $driverClasses[] = $className;
                } else {
                    trace("驱动类安全验证失败，跳过: $className", 'warning');
                }
            }

            // 如果没有找到任何驱动类，使用默认的 Driver 约定
            if (empty($driverClasses)) {
                $defaultClass = 'upload\\' . $name . '\\Driver';
                trace("未找到有效的驱动类，使用默认约定: $defaultClass", 'info');
                $driverClasses[] = $defaultClass;
            }

        } catch (Throwable $e) {
            trace("自动发现驱动类失败: " . $e->getMessage(), 'error');
            // 出错时回退到默认约定
            $driverClasses = ['upload\\' . $name . '\\Driver'];
        }

        return $driverClasses;
    }

    /**
     * 获取类的基本名称（不含命名空间）
     * @param string $className 完整类名
     * @return string 类的基本名称
     */
    protected static function getClassBaseName(string $className): string
    {
        $parts = explode('\\', $className);
        return end($parts);
    }

    /**
     * 验证驱动配置格式
     * @param array $config 驱动配置
     * @return bool
     */
    protected static function validateDriverConfig(array $config): bool
    {
        // 检查基本结构（meta.name 必须存在，driver.class 可选）
        if (!isset($config['meta']['name'])) {
            return false;
        }

        // 如果配置了 driver.class，验证其安全性
        if (isset($config['driver']['class'])) {
            $className = $config['driver']['class'];
            if (!self::validateDriverClass($className)) {
                trace("驱动类验证失败: $className", 'warning');
                return false;
            }
        }

        // 验证驱动名称（如果存在）
        if (isset($config['driver']['name'])) {
            $driverName = $config['driver']['name'];
            if (!preg_match('/^[a-zA-Z0-9_\-]+$/', $driverName)) {
                trace("驱动名称格式无效: $driverName", 'warning');
                return false;
            }
        }

        return true;
    }

    /**
     * 验证驱动类安全性
     * @param string $className 类名
     * @return bool
     */
    private static function validateDriverClass(string $className): bool
    {
        $trusted = self::isTrustedDriverClass($className);

        // 检查类名格式
        if (!preg_match('/^[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff\\\\]*$/', $className)) {
            return false;
        }

        // 检查类是否存在（但不自动加载可能的恶意类）
        if (!class_exists($className, false)) {
            if ($trusted) {
                if (!class_exists($className)) {
                    return false;
                }
            } elseif (!str_starts_with($className, 'upload\\')) {
                return false;
            }
        }

        // 如果类已存在，验证接口实现
        if (class_exists($className, false)) {
            try {
                $reflection = new ReflectionClass($className);

                // 检查是否实现了UploadDriver接口
                if (!$reflection->implementsInterface(UploadDriver::class)) {
                    return false;
                }

                // 检查命名空间安全性
                $namespace = $reflection->getNamespaceName();
                if (!$trusted && !str_starts_with($namespace, 'upload\\')) {
                    return false;
                }

                // 检查类文件是否在安全目录
                $filename = $reflection->getFileName();
                if ($filename !== false) {
                    $realPath = realpath(dirname($filename));
                    if ($realPath === false) {
                        return false;
                    }

                    $rootPath = self::getCachedRootPath();
                    if (!str_starts_with($realPath, $rootPath)) {
                        return false;
                    }
                }

                return true;
            } catch (Throwable $e) {
                trace("类反射检查失败: $className - " . $e->getMessage(), 'error');
                return false;
            }
        }

        return true;
    }

    /**
     * 注册插件驱动
     * @return void
     */
    private static function registerPluginDrivers(): void
    {
        try {
            $registry = app(PluginRegistry::class);
            if (!$registry instanceof PluginRegistry) {
                return;
            }

            foreach ($registry->all()['upload_drivers'] ?? [] as $name => $class) {
                if (!is_string($name) || !is_string($class) || $name === '' || $class === '') {
                    continue;
                }

                $pluginInfo           = self::guessPluginInfoFromClass($class, $name);
                self::$drivers[$name] = [
                    'name'          => $name,
                    'class'         => $class,
                    'type'          => 'plugin',
                    'backend_path'  => $pluginInfo['backend_path'],
                    'frontend_path' => $pluginInfo['frontend_path'],
                    'meta'          => [],
                    'registered_at' => time(),
                    'trusted'       => true,
                ];
                self::$configs[$name] = self::$configs[$name] ?? [];
            }
        } catch (Throwable $e) {
            trace('插件上传驱动注册失败: ' . $e->getMessage(), 'warning');
        }
    }

    /**
     * 判断是否为受信任驱动类
     * @param string $className
     * @return bool
     */
    private static function isTrustedDriverClass(string $className): bool
    {
        foreach (self::$drivers as $driver) {
            if (($driver['class'] ?? '') === $className && ($driver['trusted'] ?? false)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 通过类名推断插件资源路径
     * @param string $className
     * @param string $driverName
     * @return array{backend_path:string,frontend_path:string}
     */
    private static function guessPluginInfoFromClass(string $className, string $driverName): array
    {
        if (!class_exists($className)) {
            return [
                'backend_path'  => '',
                'frontend_path' => '',
            ];
        }

        try {
            $file       = (string)(new ReflectionClass($className))->getFileName();
            $pluginRoot = self::guessPluginRootFromFile($file);
            if ($pluginRoot === '') {
                return [
                    'backend_path'  => dirname($file),
                    'frontend_path' => '',
                ];
            }

            $pluginBase = str_replace('\\', '/', rtrim((string)config('plugin.root', root_path() . 'plugins'), DIRECTORY_SEPARATOR)) . '/';
            $relative   = trim(str_replace($pluginBase, '', str_replace('\\', '/', $pluginRoot)), '/');
            $segments   = explode('/', $relative);
            $vendor     = $segments[0] ?? '';
            $plugin     = $segments[1] ?? '';

            return [
                'backend_path'  => $pluginRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'upload' . DIRECTORY_SEPARATOR . $driverName,
                'frontend_path' => rtrim(public_path(), DIRECTORY_SEPARATOR)
                    . DIRECTORY_SEPARATOR
                    . trim((string)config('plugin.asset.url_prefix', 'plugins'), '/')
                    . DIRECTORY_SEPARATOR
                    . $vendor
                    . DIRECTORY_SEPARATOR
                    . $plugin
                    . DIRECTORY_SEPARATOR
                    . 'upload'
                    . DIRECTORY_SEPARATOR
                    . $driverName,
            ];
        } catch (Throwable) {
            return [
                'backend_path'  => '',
                'frontend_path' => '',
            ];
        }
    }

    /**
     * 从文件路径推断插件根目录
     * @param string $file
     * @return string
     */
    private static function guessPluginRootFromFile(string $file): string
    {
        $pluginRoot = str_replace('\\', '/', rtrim((string)config('plugin.root', root_path() . 'plugins'), DIRECTORY_SEPARATOR)) . '/';
        $normalized = str_replace('\\', '/', $file);
        if (!str_starts_with($normalized, $pluginRoot)) {
            return '';
        }

        $relative = trim(substr($normalized, strlen($pluginRoot)), '/');
        $segments = explode('/', $relative);
        if (count($segments) < 2) {
            return '';
        }

        return rtrim((string)config('plugin.root', root_path() . 'plugins'), DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . $segments[0]
            . DIRECTORY_SEPARATOR
            . $segments[1];
    }

    /**
     * 获取所有已注册的驱动
     * @return array
     */
    public static function getAvailableDrivers(): array
    {
        self::discover();
        return self::$drivers;
    }

    /**
     * 获取驱动的前端资源文件
     * @param string $name 驱动名称
     * @return array
     */
    public static function getDriverAssets(string $name): array
    {
        self::discover();

        if (!isset(self::$drivers[$name])) {
            return ['js' => [], 'css' => []];
        }

        $driver       = self::$drivers[$name];
        $frontendPath = $driver['frontend_path'] ?? '';
        $assets       = ['js' => [], 'css' => []];

        // 检查前端资源文件
        if (is_dir($frontendPath)) {
            // JS文件
            $jsFiles = glob($frontendPath . '/*.js');
            foreach ($jsFiles as $jsFile) {
                $relativePath   = str_replace(public_path(), '', $jsFile);
                $assets['js'][] = '__ROOT__' . $relativePath;
            }

            // CSS文件
            $cssFiles = glob($frontendPath . '/*.css');
            foreach ($cssFiles as $cssFile) {
                $relativePath    = str_replace(public_path(), '', $cssFile);
                $assets['css'][] = '__ROOT__' . $relativePath;
            }
        }

        return $assets;
    }

    /**
     * 热加载新驱动（运行时注册）
     * @param string $driverPath 驱动路径
     * @return bool
     */
    public static function hotRegister(string $driverPath): bool
    {
        $driverName = basename($driverPath);
        $configFile = $driverPath . '/config.php';

        if (!file_exists($configFile)) {
            return false;
        }

        try {
            self::registerDriverFromPath($driverName, $driverPath, 'custom');

            // 清除缓存
            $cacheKey = config('upload.discovery.cache_key', 'upload_drivers_cache');
            cache($cacheKey, null);

            return true;
        } catch (Exception) {
            return false;
        }
    }

    /**
     * 清除驱动缓存
     */
    public static function clearCache(): void
    {
        $cacheKey = config('upload.discovery.cache_key', 'upload_drivers_cache');
        cache($cacheKey, null);
        self::$discovered = false;
        self::$drivers    = [];
        self::$configs    = [];
        self::$instances  = [];
    }

    /**
     * 清理和验证路径安全性
     * @param string $path 路径
     * @return string 清理后的路径
     * @throws UploadDriverException
     */
    private static function sanitizePath(string $path): string
    {
        // 移除危险字符
        $path = str_replace(['../', '.\\', '\\'], '', $path);
        $path = trim($path, '/\\');

        // 验证路径格式（允许字母数字、下划线、连字符和斜杠及通配符）
        if (!preg_match('/^[a-zA-Z0-9_\-\/*]+$/', $path)) {
            throw new UploadDriverException(
                "非法路径格式: $path",
                UploadDriverException::PATH_SECURITY_VIOLATION
            );
        }

        // 限制路径长度
        if (strlen($path) > 255) {
            throw new UploadDriverException(
                "路径过长: $path",
                UploadDriverException::PATH_SECURITY_VIOLATION
            );
        }

        return $path;
    }

    /**
     * 安全获取基名（替代basename函数）
     * @param string $path 路径
     * @return string 基名
     * @throws UploadDriverException
     */
    private static function getSecureBasename(string $path): string
    {
        $path     = rtrim($path, '/\\');
        $parts    = explode('/', str_replace('\\', '/', $path));
        $basename = end($parts);

        // 验证基名不包含危险字符
        if (!preg_match('/^[a-zA-Z0-9_\-]+$/', $basename)) {
            throw new UploadDriverException(
                "非法目录名: $basename",
                UploadDriverException::PATH_SECURITY_VIOLATION
            );
        }

        return $basename;
    }

    /**
     * 验证驱动目录是否安全
     * @param string $driverDir 驱动目录
     * @return bool
     * @throws UploadDriverException
     */
    private static function isValidDriverDirectory(string $driverDir): bool
    {
        $realPath = realpath($driverDir);
        if ($realPath === false) {
            return false;
        }

        $rootPath = self::getCachedRootPath();
        if (!str_starts_with($realPath, $rootPath)) {
            trace("驱动目录超出安全范围: $driverDir", 'warning');
            return false;
        }

        return is_dir($realPath);
    }

    /**
     * 安全文件检查
     * @param string $filePath 文件路径
     * @return bool
     * @throws UploadDriverException
     */
    private static function isSecureFile(string $filePath): bool
    {
        if (!file_exists($filePath)) {
            return false;
        }

        $realPath = realpath($filePath);
        if ($realPath === false) {
            return false;
        }

        $rootPath = self::getCachedRootPath();
        if (!str_starts_with($realPath, $rootPath)) {
            trace("文件超出安全范围: $filePath", 'warning');
            return false;
        }

        // 检查文件扩展名
        $extension = pathinfo($realPath, PATHINFO_EXTENSION);
        if (strtolower($extension) !== 'php') {
            trace("非法文件类型: $filePath", 'warning');
            return false;
        }

        return is_readable($realPath);
    }

    /**
     * 安全加载PHP配置文件
     * @param string $filePath 文件路径
     * @return array
     * @throws Exception
     */
    private static function secureRequire(string $filePath): array
    {
        if (!self::isSecureFile($filePath)) {
            throw new UploadDriverException(
                "文件安全检查失败: $filePath",
                UploadDriverException::FILE_SECURITY_VIOLATION
            );
        }

        // 使用输出缓冲防止意外输出
        ob_start();
        try {
            $result = require $filePath;
            $output = ob_get_contents();

            // 检查是否有意外输出
            if (!empty($output)) {
                trace("配置文件存在意外输出: $filePath", 'warning');
            }

            if (!is_array($result)) {
                throw new UploadDriverException(
                    "配置文件必须返回数组: $filePath",
                    UploadDriverException::DRIVER_CONFIG_INVALID
                );
            }

            return $result;
        } finally {
            ob_end_clean();
        }
    }

    /**
     * 获取缓存的根路径
     * @return string
     * @throws UploadDriverException
     */
    private static function getCachedRootPath(): string
    {
        if (self::$cachedRootPath === null) {
            self::$cachedRootPath = realpath(root_path());
            if (self::$cachedRootPath === false) {
                throw new UploadDriverException(
                    '无法获取项目根路径',
                    UploadDriverException::PATH_SECURITY_VIOLATION
                );
            }
        }
        return self::$cachedRootPath;
    }
}
