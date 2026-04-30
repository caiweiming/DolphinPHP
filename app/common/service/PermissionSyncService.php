<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2026 广东卓锐软件有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://www.dolphinphp.com
// +----------------------------------------------------------------------
// | 作者: 蔡伟明 <314013107@qq.com>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\common\service;

use app\common\attribute\Permission as PermissionAttribute;
use app\common\interface\PermissionService as PermissionServiceInterface;
use app\common\model\App as AppModel;
use app\common\model\Permission as PermissionModel;
use Exception;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\db\Query;
use think\facade\Db;
use think\facade\Log;

/**
 * 权限同步服务
 *
 * 负责扫描控制器并自动生成权限数据（菜单权限 + 按钮权限）
 *
 * @package app\common\service
 */
class PermissionSyncService
{
    /**
     * 标准权限动作列表
     */
    private const STANDARD_METHODS = [
        'create', 'add',           // 新增
        'edit', 'update',          // 编辑
        'delete', 'remove',        // 删除
        'view', 'detail',          // 查看
        'enable', 'disable',       // 启用/禁用
        'sort',                    // 排序
        'export', 'import',        // 导入/导出
    ];

    /**
     * 排除的方法
     */
    private const EXCLUDED_METHODS = [
        'initialize', '__construct', '__destruct',
        'beforeAction', 'afterAction',
        'data', 'fetch', 'display', 'assign',
    ];

    /**
     * 权限模型
     */
    protected PermissionModel $permissionModel;

    /**
     * 应用注册表模型
     */
    protected AppModel $appModel;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->permissionModel = new PermissionModel();
        $this->appModel        = new AppModel();
    }

    /**
     * 同步权限
     *
     * @param string $app 应用名称（admin/cms/api）或 'all'
     * @param array $options 选项配置
     *   - incremental: bool 增量更新（默认 true）
     *       true: 跳过已存在的权限（推荐）
     *       false: 尝试添加所有权限，已存在的会失败
     *         ⚠️ 如需完全重建，请先调用 clearPermissions() 清空权限
     *   - dryRun: bool 预览模式（默认 false）
     *   - excludeControllers: array 排除的控制器
     *   - customPath: string 自定义扫描路径
     * @return array 同步结果
     */
    public function sync(string $app, array $options = []): array
    {
        $startTime = microtime(true);

        // 默认选项
        $options = array_merge([
            'incremental'        => true,
            'dryRun'             => false,
            'excludeControllers' => [],
            'customPath'         => '',
        ], $options);

        try {
            Log::info("开始同步权限", ['app' => $app, 'options' => $options]);

            // 1. 获取应用列表
            $apps = $this->getApps($app, $options['customPath']);

            $totalResult = [
                'added'       => 0,
                'skipped'     => 0,
                'failed'      => 0,
                'menuCount'   => 0,
                'buttonCount' => 0,
                'details'     => [],
                'apps'        => [],
            ];

            // 2. 遍历每个应用
            foreach ($apps as $appName) {
                $appResult = $this->syncSingleApp($appName, $options);

                // 合并结果
                $totalResult['added']       += $appResult['added'];
                $totalResult['skipped']     += $appResult['skipped'];
                $totalResult['failed']      += $appResult['failed'];
                $totalResult['menuCount']   += $appResult['menuCount'];
                $totalResult['buttonCount'] += $appResult['buttonCount'];
                $totalResult['details']     = array_merge($totalResult['details'], $appResult['details']);
                $totalResult['apps'][]      = $appName;
            }

            // 3. 计算耗时
            $duration                = round((microtime(true) - $startTime), 2);
            $totalResult['duration'] = $duration;

            Log::info("权限同步完成", $totalResult);

            return $totalResult;

        } catch (Exception $e) {
            Log::error("权限同步失败: " . $e->getMessage(), [
                'app'   => $app,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'added'       => 0,
                'skipped'     => 0,
                'failed'      => 0,
                'menuCount'   => 0,
                'buttonCount' => 0,
                'details'     => [],
                'apps'        => [],
                'duration'    => 0,
                'error'       => $e->getMessage(),
            ];
        }
    }

    /**
     * 同步单个应用的顶级菜单信息
     *
     * @param string $app 应用名称
     * @return void
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function syncAppMenu(string $app): void
    {
        $appInfo = $this->loadAppConfig($app);
        if ($appInfo === null) {
            return;
        }

        $this->createOrUpdateAppMenu($app, $appInfo);
    }

    /**
     * 同步插件权限与菜单
     *
     * 插件通过根目录下的 `permissions.php` 显式声明权限项，避免与现有
     * “扫描应用控制器”的流程耦合。
     *
     * @param string $pluginName 插件标识，如 demo/hello
     * @param string $pluginPath 插件根目录
     * @param array $options 选项
     *   - clearExisting: bool 是否先清理同插件旧权限，默认 true
     *   - dryRun: bool 是否仅预览，默认 false
     * @return array
     */
    public function syncPlugin(string $pluginName, string $pluginPath, array $options = []): array
    {
        $startTime = microtime(true);
        $options   = array_merge([
            'clearExisting' => true,
            'dryRun'        => false,
        ], $options);

        try {
            $definition = $this->loadPluginPermissionDefinition($pluginName, $pluginPath);
            $items      = $this->extractPluginPermissionItems($definition);
            if ($items === []) {
                return [
                    'plugin'      => $pluginName,
                    'added'       => 0,
                    'skipped'     => 0,
                    'failed'      => 0,
                    'menuCount'   => 0,
                    'buttonCount' => 0,
                    'details'     => [],
                    'duration'    => round((microtime(true) - $startTime), 2),
                ];
            }

            $defaultParentCode = $this->resolvePluginDefaultParentCode($definition);
            $permissions       = $this->normalizePluginPermissions($pluginName, $items, $defaultParentCode);

            if ($options['dryRun']) {
                $result             = $this->previewPermissions($permissions);
                $result['plugin']   = $pluginName;
                $result['duration'] = round((microtime(true) - $startTime), 2);
                return $result;
            }

            $result = Db::transaction(function () use ($pluginName, $options, $permissions): array {
                if ($options['clearExisting']) {
                    $this->deletePluginPermissions($pluginName);
                }

                $this->ensurePluginMenuRoot();
                return $this->savePluginPermissions($permissions);
            });

            $result['plugin']   = $pluginName;
            $result['duration'] = round((microtime(true) - $startTime), 2);

            $this->clearPermissionRuntimeCache();

            Log::info('插件权限同步完成', $result);

            return $result;
        } catch (Exception $e) {
            Log::error('插件权限同步失败: ' . $e->getMessage(), [
                'plugin' => $pluginName,
                'path'   => $pluginPath,
            ]);

            return [
                'plugin'      => $pluginName,
                'added'       => 0,
                'skipped'     => 0,
                'failed'      => 0,
                'menuCount'   => 0,
                'buttonCount' => 0,
                'details'     => [],
                'duration'    => round((microtime(true) - $startTime), 2),
                'error'       => $e->getMessage(),
            ];
        }
    }

    /**
     * 同步单个应用
     *
     * @param string $app 应用名称
     * @param array $options 选项配置
     * @return array 同步结果
     * @throws Exception
     */
    private function syncSingleApp(string $app, array $options): array
    {
        // 1. 读取应用配置
        $appInfo = $this->loadAppConfig($app);

        // 2. 如果配置了应用信息,先创建/更新应用顶级菜单
        if ($appInfo) {
            $this->createOrUpdateAppMenu($app, $appInfo);
        }

        // 3. 扫描控制器
        $controllers = $this->scanControllers($app, $options['customPath']);

        // 4. 过滤排除的控制器
        if (!empty($options['excludeControllers'])) {
            $controllers = $this->filterExcludedControllers($controllers, $options['excludeControllers']);
        }

        // 5. 生成权限数据(传递应用配置)
        $permissions = $this->generatePermissions($controllers, $app, $appInfo);

        // 6. 保存权限
        if ($options['dryRun']) {
            return $this->previewPermissions($permissions);
        }

        return $this->savePermissions($permissions, $options['incremental']);
    }

    /**
     * 获取应用列表
     *
     * @param string $input 输入（应用名或 'all'）
     * @param string $customPath 自定义路径
     * @return array 应用列表
     */
    private function getApps(string $input, string $customPath = ''): array
    {
        // 自定义路径
        if ($customPath !== '') {
            return ['custom'];
        }

        // 所有应用
        if ($input === 'all') {
            return $this->scanAvailableApps();
        }

        // 多个应用：admin,cms,api
        if (str_contains($input, ',')) {
            return array_filter(explode(',', $input));
        }

        // 单个应用
        return [$input];
    }

    /**
     * 扫描可用应用
     *
     * @return array 应用列表
     */
    private function scanAvailableApps(): array
    {
        $appPath = app()->getBasePath();
        $apps    = [];

        $dirs = scandir($appPath);
        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..' || $dir === 'common') {
                continue;
            }

            $controllerPath = $appPath . '/' . $dir . '/controller';
            if (is_dir($controllerPath)) {
                $apps[] = $dir;
            }
        }

        Log::info("扫描到可用应用", ['apps' => $apps]);

        return $apps;
    }

    /**
     * 扫描控制器
     *
     * @param string $app 应用名称
     * @param string $customPath 自定义路径
     * @return array 控制器列表
     * @throws Exception
     */
    private function scanControllers(string $app, string $customPath = ''): array
    {
        // 确定扫描路径
        if ($customPath !== '') {
            $path = $customPath;
        } else {
            $path = app()->getBasePath() . '/' . $app . '/controller';
        }

        if (!is_dir($path)) {
            throw new Exception("应用 $app 的控制器目录不存在: $path");
        }

        $controllers = [];
        $files       = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $className = $this->getClassNameFromFile($file->getPathname());

                if ($className && $this->isAuthController($className)) {
                    $methods = $this->getPublicMethods($className);

                    if (!empty($methods)) {
                        $controllers[] = [
                            'class'   => $className,
                            'file'    => $file->getPathname(),
                            'methods' => $methods,
                        ];
                    }
                }
            }
        }

        Log::info("扫描到控制器", ['app' => $app, 'count' => count($controllers)]);

        return $controllers;
    }

    /**
     * 从文件路径获取类名
     *
     * @param string $filePath 文件路径
     * @return string|null 类名
     */
    private function getClassNameFromFile(string $filePath): ?string
    {
        try {
            $content = file_get_contents($filePath);
            if (!$content) {
                return null;
            }

            // 提取命名空间
            if (preg_match('/namespace\s+(.+?);/', $content, $namespaceMatches)) {
                $namespace = $namespaceMatches[1];
            } else {
                return null;
            }

            // 提取类名
            if (preg_match('/class\s+(\w+)/', $content, $classMatches)) {
                $className = $classMatches[1];
                return $namespace . '\\' . $className;
            }

            return null;
        } catch (Exception $e) {
            Log::warning("解析类名失败: $filePath", ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * 判断是否为需要扫描的控制器
     *
     * 规则：
     * 1. 扫描带类级 #[Permission] 注解的控制器
     * 2. 扫描存在方法级 #[Permission] 注解的控制器
     *
     * @param string $className 类名
     * @return bool 是否应该处理此控制器
     */
    private function isAuthController(string $className): bool
    {
        try {
            if (!class_exists($className)) {
                return false;
            }

            $reflection   = new ReflectionClass($className);
            $excludedList = ['Auth', 'Index', 'Ajax', 'Base'];
            $shortName    = $reflection->getShortName();

            // 排除基础控制器
            if (in_array($shortName, $excludedList)) {
                return false;
            }

            $attributes = $reflection->getAttributes(PermissionAttribute::class);
            if (!empty($attributes)) {
                return true;
            }

            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                if ($method->class !== $className || !$this->canSyncMethod($method)) {
                    continue;
                }

                if (!empty($method->getAttributes(PermissionAttribute::class))) {
                    return true;
                }
            }

            return false;

        } catch (Exception $e) {
            Log::warning("检查控制器失败: $className", ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * 获取类的公共方法
     *
     * 智能规则：
     * 1. 方法级带注解的方法始终包含
     * 2. 带类级注解的控制器，额外自动包含 index/标准动作
     * 3. 其他方法不包含
     *
     * @param string $className 类名
     * @return array 方法列表
     */
    private function getPublicMethods(string $className): array
    {
        try {
            $reflection = new ReflectionClass($className);
            $methods    = [];
            $hasClassPermission = !empty($reflection->getAttributes(PermissionAttribute::class));

            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                // 只扫描控制器自身声明的方法，避免把父类/trait 的公共动作同步成权限
                if ($method->class !== $className || !$this->canSyncMethod($method)) {
                    continue;
                }

                $methodName       = $method->getName();
                $methodAttributes = $method->getAttributes(PermissionAttribute::class);
                $hasAttribute     = !empty($methodAttributes);

                if ($hasAttribute) {
                    $attribute = $methodAttributes[0]->newInstance();
                    if (!$attribute->generate) {
                        continue;
                    }
                }

                if ($hasAttribute) {
                    $methods[$methodName] = $methodName;
                    continue;
                }

                if ($hasClassPermission && ($methodName === 'index' || in_array($methodName, self::STANDARD_METHODS, true))) {
                    $methods[$methodName] = $methodName;
                }
            }

            return array_values($methods);
        } catch (Exception $e) {
            Log::warning("获取公共方法失败: $className", ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * 判断方法是否允许参与权限同步
     *
     * @param ReflectionMethod $method
     * @return bool
     */
    private function canSyncMethod(ReflectionMethod $method): bool
    {
        if (!$method->isPublic() || $method->isConstructor() || $method->isDestructor()) {
            return false;
        }

        return !in_array($method->getName(), self::EXCLUDED_METHODS, true);
    }

    /**
     * 过滤排除的控制器
     *
     * @param array $controllers 控制器列表
     * @param array $excludeList 排除列表
     * @return array 过滤后的控制器列表
     * @throws ReflectionException
     */
    private function filterExcludedControllers(array $controllers, array $excludeList): array
    {
        return array_filter($controllers, function ($controller) use ($excludeList) {
            $reflection = new ReflectionClass($controller['class']);
            $shortName  = $reflection->getShortName();

            return !in_array($shortName, $excludeList);
        });
    }

    /**
     * 生成权限数据
     *
     * 优先策略：
     * 1. 如果有注解，从注解中读取权限信息
     * 2. 否则使用映射表生成（向后兼容）
     *
     * @param array $controllers 控制器列表
     * @param string $app 应用名称
     * @param array|null $appInfo 应用配置信息
     * @return array 权限数据
     * @throws ReflectionException
     */
    private function generatePermissions(array $controllers, string $app, ?array $appInfo = null): array
    {
        $permissions = [];

        foreach ($controllers as $controller) {
            $reflection = new ReflectionClass($controller['class']);
            $controllerKey = $this->getControllerKey($controller['class']);

            // 检查是否使用注解模式
            $classAttributes  = $reflection->getAttributes(PermissionAttribute::class);
            $useAttributeMode = !empty($classAttributes);
            $menuPermission   = null;

            // 生成菜单权限
            if ($useAttributeMode) {
                // 从注解读取
                $attribute      = $classAttributes[0]->newInstance();
                $menuPermission = [
                    'name'        => $attribute->name,
                    'code'        => $attribute->code ?? $this->getPermissionCode($app, $controllerKey),
                    'type'        => $attribute->type ?? PermissionModel::TYPE_MENU,  // 支持自定义类型
                    'route'       => $this->getControllerRoute($app, $controllerKey),
                    'parent_code' => $this->getDefaultParentCode($app, $appInfo, $attribute->parent),  // 智能推断父级权限代码
                    'icon'        => $attribute->icon,
                    'sort'        => $attribute->sort,
                    'status'      => 1,
                    'remark'      => $attribute->remark ?? '自动生成',
                ];
                $permissions[] = $menuPermission;
            }

            // 生成按钮权限
            foreach ($controller['methods'] as $method) {
                if ($this->shouldGenerateButton($method)) {
                    $methodReflection = $reflection->getMethod($method);
                    $methodAttributes = $methodReflection->getAttributes(PermissionAttribute::class);

                    if (!empty($methodAttributes)) {
                        $attribute        = $methodAttributes[0]->newInstance();
                        $buttonPermission = [
                            'name'        => $attribute->name,
                            'code'        => $attribute->code ?? $this->getPermissionCode($app, $controllerKey, $method),
                            'type'        => $attribute->type ?? PermissionModel::TYPE_BUTTON,  // 支持自定义类型
                            'route'       => $this->getMethodRoute($app, $controllerKey, $method),
                            'parent_code' => $menuPermission['code'] ?? $this->getDefaultParentCode($app, $appInfo, $attribute->parent),
                            'icon'        => $attribute->icon,
                            'sort'        => $attribute->sort,
                            'status'      => 1,
                            'remark'      => $attribute->remark ?? '自动生成',
                        ];
                    } elseif ($useAttributeMode && $menuPermission !== null) {
                        // 类级注解模式下，标准方法允许回退生成按钮权限
                        $buttonPermission = [
                            'name'        => $this->getMethodName($method),
                            'code'        => $this->getPermissionCode($app, $controllerKey, $method),
                            'type'        => PermissionModel::TYPE_BUTTON,
                            'route'       => $this->getMethodRoute($app, $controllerKey, $method),
                            'parent_code' => $menuPermission['code'],
                            'icon'        => null,
                            'sort'        => 0,
                            'status'      => 1,
                            'remark'      => '自动生成',
                        ];
                    } else {
                        continue;
                    }

                    $permissions[] = $buttonPermission;
                }
            }
        }

        Log::info("生成权限数据", ['count' => count($permissions)]);

        return $permissions;
    }

    /**
     * 判断是否应该生成按钮
     *
     * @param string $method 方法名
     * @return bool 是否生成按钮
     */
    private function shouldGenerateButton(string $method): bool
    {
        // 排除 index 方法（已作为菜单）
        return $method !== 'index';
    }

    /**
     * 获取控制器名称
     *
     * @param string $className 类名
     * @return string 控制器名称
     */
    private function getControllerName(string $className): string
    {
        // 命名映射
        $nameMapping = [
            'User'       => '用户管理',
            'Role'       => '角色管理',
            'Permission' => '权限管理',
            'Department' => '部门管理',
            'Menu'       => '菜单管理',
            'Config'     => '系统配置',
            'Attachment' => '附件管理',
            'Log'        => '日志管理',
        ];

        return $nameMapping[$className] ?? $className . '管理';
    }

    /**
     * 获取方法名称
     *
     * @param string $methodName 方法名
     * @return string 方法名称
     */
    private function getMethodName(string $methodName): string
    {
        // 操作名称映射
        $actionMapping = [
            'index'   => '列表',
            'create'  => '新增',
            'add'     => '新增',
            'edit'    => '编辑',
            'update'  => '更新',
            'delete'  => '删除',
            'remove'  => '删除',
            'view'    => '查看',
            'detail'  => '详情',
            'enable'  => '启用',
            'disable' => '禁用',
            'sort'    => '排序',
            'export'  => '导出',
            'import'  => '导入',
        ];

        if (isset($actionMapping[$methodName])) {
            return $actionMapping[$methodName];
        }

        // 驼峰转下划线再转中文（简单处理）
        $underscoreName = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $methodName));
        return str_replace('_', ' ', $underscoreName);
    }

    /**
     * 生成权限代码
     *
     * 格式: {app}.{controller}[.{method}]
     *
     * @param string $app 应用名称
     * @param string $controller 控制器名
     * @param string|null $method 方法名
     * @return string 权限代码
     */
    private function getPermissionCode(string $app, string $controller, ?string $method = null): string
    {
        $parts = [$app, $this->normalizeControllerIdentifier($controller)];

        if ($method !== null) {
            $parts[] = $this->camelToUnderscore($method);
        }

        return implode('.', $parts);
    }

    /**
     * 获取控制器路由
     *
     * @param string $app 应用名称
     * @param string $controller 控制器名
     * @return string 路由地址
     */
    private function getControllerRoute(string $app, string $controller): string
    {
        return $app . '/' . $this->normalizeControllerIdentifier($controller) . '/index';
    }

    /**
     * 获取方法路由
     *
     * @param string $app 应用名称
     * @param string $controller 控制器名
     * @param string $method 方法名
     * @return string 路由地址
     */
    private function getMethodRoute(string $app, string $controller, string $method): string
    {
        return $app . '/' . $this->normalizeControllerIdentifier($controller) . '/' . $this->camelToUnderscore($method);
    }

    /**
     * 从完整控制器类名提取控制器标识
     *
     * 例如：
     * - app\store\controller\Product => product
     * - app\store\controller\admin\Product => admin.product
     *
     * @param string $className
     * @return string
     */
    private function getControllerKey(string $className): string
    {
        $marker = '\\controller\\';
        $position = stripos($className, $marker);

        if ($position === false) {
            return $this->normalizeControllerIdentifier($className);
        }

        $relative = substr($className, $position + strlen($marker));
        return $this->normalizeControllerIdentifier($relative);
    }

    /**
     * 归一化控制器标识
     *
     * 支持：
     * - Product => product
     * - admin\Product => admin.product
     * - admin.Product => admin.product
     *
     * @param string $controller
     * @return string
     */
    private function normalizeControllerIdentifier(string $controller): string
    {
        $normalized = str_replace('\\', '.', trim($controller, '.\\'));
        $segments = array_values(array_filter(explode('.', $normalized), static fn(string $item): bool => $item !== ''));

        $segments = array_map(function (string $segment): string {
            return $this->camelToUnderscore($segment);
        }, $segments);

        return implode('.', $segments);
    }

    /**
     * 驼峰转下划线
     *
     * @param string $str 字符串
     * @return string 下划线字符串
     */
    private function camelToUnderscore(string $str): string
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $str));
    }

    /**
     * 预览权限（dry-run 模式）
     *
     * @param array $permissions 权限数据
     * @return array 预览结果
     */
    private function previewPermissions(array $permissions): array
    {
        $menuCount   = 0;
        $buttonCount = 0;
        $details     = [];

        foreach ($permissions as $permission) {
            if ($permission['type'] === PermissionModel::TYPE_MENU) {
                $menuCount++;
            } else {
                $buttonCount++;
            }

            $details[] = "[{$permission['type']}] {$permission['name']} ({$permission['code']})";
        }

        return [
            'added'       => 0,
            'skipped'     => 0,
            'failed'      => 0,
            'menuCount'   => $menuCount,
            'buttonCount' => $buttonCount,
            'details'     => $details,
            'preview'     => true,
        ];
    }

    /**
     * 保存权限数据
     *
     * @param array $permissions 权限数据
     * @param bool $incremental 增量更新
     *   - true: 跳过已存在的权限（推荐）
     *   - false: 尝试添加所有权限，已存在的会失败
     *     非增量模式下，如需完全重建权限，请先调用 clearPermissions() 清空权限
     * @return array 保存结果
     */
    private function savePermissions(array $permissions, bool $incremental): array
    {
        $added       = 0;
        $skipped     = 0;
        $failed      = 0;
        $menuCount   = 0;
        $buttonCount = 0;
        $details     = [];

        foreach ($permissions as $permission) {
            try {
                // 检查权限是否已存在
                if ($incremental) {
                    $exists = $this->permissionModel->where('code', $permission['code'])->find();

                    if ($exists) {
                        $skipped++;
                        $details[] = "[跳过] {$permission['name']} ({$permission['code']})";
                        continue;
                    }
                }

                // 如果有 parent_code，找到父级 ID
                if (isset($permission['parent_code'])) {
                    $parent                  = $this->permissionModel->where('code', $permission['parent_code'])->find();
                    $permission['parent_id'] = $parent ? $parent['id'] : 0;
                    unset($permission['parent_code']);
                }

                // 创建权限
                $this->permissionModel->create($permission);
                $added++;

                // 统计
                if ($permission['type'] === PermissionModel::TYPE_MENU) {
                    $menuCount++;
                } else {
                    $buttonCount++;
                }

                $details[] = "[添加] {$permission['name']} ({$permission['code']})";

            } catch (Exception $e) {
                $failed++;
                $details[] = "[失败] {$permission['name']} - " . $e->getMessage();
                Log::error("保存权限失败", [
                    'permission' => $permission,
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        return [
            'added'       => $added,
            'skipped'     => $skipped,
            'failed'      => $failed,
            'menuCount'   => $menuCount,
            'buttonCount' => $buttonCount,
            'details'     => $details,
        ];
    }

    /**
     * 清空指定应用的权限
     *
     * 危险操作：此方法会删除指定应用的所有权限数据
     *
     * 使用场景：
     * - 完全重建权限前需要清空旧数据
     * - 卸载应用模块时清理权限
     *
     * @param string $app 应用名称（admin/cms/api）或 'all' 清空所有应用
     * @return array 清空结果
     *   - deleted: 删除的权限数量
     *   - menuCount: 删除的菜单权限数量
     *   - buttonCount: 删除的按钮权限数量
     *   - details: 详细信息数组
     */
    public function clearPermissions(string $app): array
    {
        $startTime = microtime(true);

        try {
            Log::warning("开始清空权限", ['app' => $app]);

            // 构建查询条件
            $query = $this->permissionModel;

            if ($app !== 'all') {
                // 清空指定应用：权限代码以 "app." 开头
                $query = $query->where('code', 'like', $app . '.%');
            }

            // 获取将要删除的权限（用于统计和日志）
            $permissions = $query->select();

            if ($permissions->isEmpty()) {
                Log::info("没有找到需要清空的权限", ['app' => $app]);

                return [
                    'deleted'     => 0,
                    'menuCount'   => 0,
                    'buttonCount' => 0,
                    'details'     => [],
                    'duration'    => 0,
                ];
            }

            // 统计
            $menuCount   = 0;
            $buttonCount = 0;
            $details     = [];

            foreach ($permissions as $permission) {
                if ($permission->type === PermissionModel::TYPE_MENU) {
                    $menuCount++;
                } else {
                    $buttonCount++;
                }
                $details[] = "[删除] $permission->name ($permission->code)";
            }

            // 执行删除
            $query = $this->permissionModel;
            if ($app !== 'all') {
                $query = $query->where('code', 'like', $app . '.%');
            }
            $deleted = $query->delete();

            // 计算耗时
            $duration = round((microtime(true) - $startTime), 2);

            $result = [
                'deleted'     => $deleted,
                'menuCount'   => $menuCount,
                'buttonCount' => $buttonCount,
                'details'     => $details,
                'duration'    => $duration,
            ];

            Log::warning("权限清空完成", $result);

            return $result;

        } catch (Exception $e) {
            Log::error("清空权限失败: " . $e->getMessage(), [
                'app'   => $app,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'deleted'     => 0,
                'menuCount'   => 0,
                'buttonCount' => 0,
                'details'     => [],
                'duration'    => 0,
                'error'       => $e->getMessage(),
            ];
        }
    }

    /**
     * 清理指定应用的权限与菜单
     *
     * @param string $app
     * @return array
     */
    public function clearAppPermissions(string $app): array
    {
        $app       = trim($app);
        $startTime = microtime(true);

        if ($app === '') {
            return [
                'app'         => '',
                'deleted'     => 0,
                'menuCount'   => 0,
                'buttonCount' => 0,
                'details'     => [],
                'duration'    => 0,
                'error'       => '缺少应用标识',
            ];
        }

        try {
            $appInfo  = $this->loadAppConfig($app) ?? [];
            $menuCode = trim((string)($appInfo['code'] ?? $app));
            $routePre = $app . '/';

            $permissions = $this->permissionModel
                ->where(function (Query $query) use ($app, $menuCode, $routePre): void {
                    $query->where('route', 'like', $routePre . '%')
                        ->whereOr('code', 'like', $app . '.%')
                        ->whereOr('code', '=', $app);

                    if ($menuCode !== '' && $menuCode !== $app) {
                        $query->whereOr('code', 'like', $menuCode . '.%')
                            ->whereOr('code', '=', $menuCode);
                    }
                })
                ->select();

            $rootMenu      = $menuCode !== ''
                ? $this->permissionModel->where('code', $menuCode)->find()
                : null;
            $permissionMap = [];
            foreach ($permissions as $permission) {
                $permissionMap[(int)$permission->getAttr('id')] = $permission;
            }

            if ($rootMenu instanceof PermissionModel) {
                $childIds = $this->permissionModel->getChildrenIds((int)$rootMenu->getAttr('id'), true);
                if ($childIds !== []) {
                    foreach ($this->permissionModel->whereIn('id', $childIds)->select() as $child) {
                        $permissionMap[(int)$child->getAttr('id')] = $child;
                    }
                }
            }

            if ($permissionMap === []) {
                return [
                    'app'         => $app,
                    'deleted'     => 0,
                    'menuCount'   => 0,
                    'buttonCount' => 0,
                    'details'     => [],
                    'duration'    => round((microtime(true) - $startTime), 2),
                ];
            }

            $ids         = [];
            $menuCount   = 0;
            $buttonCount = 0;
            $details     = [];

            foreach ($permissionMap as $permission) {
                $id = (int)$permission->getAttr('id');
                if ($id <= 0) {
                    continue;
                }

                $ids[] = $id;
                if ((string)$permission->getAttr('type') === PermissionModel::TYPE_MENU) {
                    $menuCount++;
                } else {
                    $buttonCount++;
                }

                $details[] = '[删除] ' . $permission->getAttr('name') . ' (' . $permission->getAttr('code') . ')';
            }

            $deleted = 0;
            if ($ids !== []) {
                $deleted = Db::transaction(function () use ($ids) {
                    return $this->permissionModel->whereIn('id', $ids)->delete();
                });
            }

            $this->clearPermissionRuntimeCache();

            return [
                'app'         => $app,
                'deleted'     => $deleted,
                'menuCount'   => $menuCount,
                'buttonCount' => $buttonCount,
                'details'     => $details,
                'duration'    => round((microtime(true) - $startTime), 2),
            ];
        } catch (Exception $e) {
            Log::error('应用权限清理失败: ' . $e->getMessage(), [
                'app' => $app,
            ]);

            return [
                'app'         => $app,
                'deleted'     => 0,
                'menuCount'   => 0,
                'buttonCount' => 0,
                'details'     => [],
                'duration'    => round((microtime(true) - $startTime), 2),
                'error'       => $e->getMessage(),
            ];
        }
    }

    /**
     * 清理指定插件的权限与菜单
     *
     * @param string $pluginName 插件标识，如 demo/hello
     * @return array
     */
    public function clearPluginPermissions(string $pluginName): array
    {
        $startTime = microtime(true);

        try {
            $query       = $this->buildPluginPermissionQuery($pluginName);
            $permissions = $query->select();

            if ($permissions->isEmpty()) {
                return [
                    'plugin'      => $pluginName,
                    'deleted'     => 0,
                    'menuCount'   => 0,
                    'buttonCount' => 0,
                    'details'     => [],
                    'duration'    => round((microtime(true) - $startTime), 2),
                ];
            }

            $menuCount   = 0;
            $buttonCount = 0;
            $details     = [];

            foreach ($permissions as $permission) {
                if ($permission->type === PermissionModel::TYPE_MENU) {
                    $menuCount++;
                } else {
                    $buttonCount++;
                }

                $details[] = "[删除] $permission->name ($permission->code)";
            }

            $deleted = Db::transaction(function () use ($pluginName): int {
                return $this->deletePluginPermissions($pluginName);
            });

            $this->clearPermissionRuntimeCache();

            $result = [
                'plugin'      => $pluginName,
                'deleted'     => $deleted,
                'menuCount'   => $menuCount,
                'buttonCount' => $buttonCount,
                'details'     => $details,
                'duration'    => round((microtime(true) - $startTime), 2),
            ];

            Log::info('插件权限清理完成', $result);

            return $result;
        } catch (Exception $e) {
            Log::error('插件权限清理失败: ' . $e->getMessage(), [
                'plugin' => $pluginName,
            ]);

            return [
                'plugin'      => $pluginName,
                'deleted'     => 0,
                'menuCount'   => 0,
                'buttonCount' => 0,
                'details'     => [],
                'duration'    => round((microtime(true) - $startTime), 2),
                'error'       => $e->getMessage(),
            ];
        }
    }

    /**
     * 读取插件权限定义
     *
     * @param string $pluginName
     * @param string $pluginPath
     * @return array
     * @throws Exception
     */
    private function loadPluginPermissionDefinition(string $pluginName, string $pluginPath): array
    {
        $file = rtrim($pluginPath, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . config('plugin.permission_file', 'permissions.php');

        if (!is_file($file)) {
            return [];
        }

        $definition = include $file;
        if (!is_array($definition)) {
            throw new Exception("插件 $pluginName 的权限定义文件必须返回数组");
        }

        return $definition;
    }

    /**
     * 提取插件权限项
     *
     * @param array $definition
     * @return array
     */
    private function extractPluginPermissionItems(array $definition): array
    {
        if ($definition === []) {
            return [];
        }

        if (array_is_list($definition)) {
            return $definition;
        }

        $items = $definition['items'] ?? [];
        return is_array($items) ? $items : [];
    }

    /**
     * 解析插件默认菜单挂载点
     *
     * @param array $definition
     * @return string|null
     */
    private function resolvePluginDefaultParentCode(array $definition): ?string
    {
        $parentCode = $definition['menu_parent_code'] ?? config('plugin.menu_parent_code', '');
        $parentCode = is_string($parentCode) ? trim($parentCode) : '';

        return $parentCode === '' ? null : $parentCode;
    }

    /**
     * 规范化插件权限定义
     *
     * @param string $pluginName
     * @param array $items
     * @param string|null $defaultParentCode
     * @return array
     * @throws Exception
     */
    private function normalizePluginPermissions(string $pluginName, array $items, ?string $defaultParentCode): array
    {
        $normalized = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                throw new Exception("插件 $pluginName 的权限项必须为数组");
            }

            $normalized[] = $this->normalizePluginPermissionItem($pluginName, $item, $defaultParentCode);
        }

        usort($normalized, function (array $left, array $right): int {
            $leftDepth  = substr_count((string)$left['code'], '.');
            $rightDepth = substr_count((string)$right['code'], '.');

            if ($leftDepth === $rightDepth) {
                return strcmp((string)$left['code'], (string)$right['code']);
            }

            return $leftDepth <=> $rightDepth;
        });

        return $normalized;
    }

    /**
     * 规范化单个插件权限项
     *
     * @param string $pluginName
     * @param array $item
     * @param string|null $defaultParentCode
     * @return array
     * @throws Exception
     */
    private function normalizePluginPermissionItem(string $pluginName, array $item, ?string $defaultParentCode): array
    {
        $prefix = dp_plugin_permission_prefix($pluginName);
        if ($prefix === '') {
            throw new Exception('插件标识不合法，无法生成权限前缀');
        }

        $name = trim((string)($item['name'] ?? ''));
        if ($name === '') {
            throw new Exception("插件 $pluginName 的权限名称不能为空");
        }

        $code = trim((string)($item['code'] ?? ''));
        if ($code === '') {
            throw new Exception("插件 $pluginName 的权限标识不能为空");
        }

        if ($code !== $prefix && !str_starts_with($code, $prefix . '.')) {
            throw new Exception("插件 $pluginName 的权限标识必须以 $prefix 开头");
        }

        $type = trim((string)($item['type'] ?? PermissionModel::TYPE_MENU));
        if (!in_array($type, [PermissionModel::TYPE_MENU, PermissionModel::TYPE_BUTTON, PermissionModel::TYPE_API], true)) {
            throw new Exception("插件 $pluginName 的权限类型不支持: $type");
        }

        $parentCode = trim((string)($item['parent_code'] ?? ''));
        if ($parentCode === '') {
            $parentCode = $code === $prefix
                ? (string)$defaultParentCode
                : (string)($this->inferParentCode($code) ?? $defaultParentCode);
        }

        if ($parentCode === $code) {
            throw new Exception("插件 $pluginName 的权限 $code 不能将自己设为父级");
        }

        return [
            'name'        => $name,
            'code'        => $code,
            'type'        => $type,
            'route'       => $this->normalizePluginPermissionRoute($item['route'] ?? null),
            'parent_code' => $parentCode !== '' ? $parentCode : null,
            'icon'        => $item['icon'] ?? null,
            'sort'        => (int)($item['sort'] ?? 0),
            'status'      => (int)($item['status'] ?? 1),
            'visible'     => (int)($item['visible'] ?? 1),
            'cache'       => (int)($item['cache'] ?? 0),
            'remark'      => trim((string)($item['remark'] ?? '插件声明')),
        ];
    }

    /**
     * 标准化插件权限路由
     *
     * @param mixed $route
     * @return string|null
     */
    private function normalizePluginPermissionRoute(mixed $route): ?string
    {
        if (!is_string($route)) {
            return null;
        }

        $route = trim($route);
        return $route === '' ? null : $route;
    }

    /**
     * 保存插件权限（严格模式）
     *
     * @param array $permissions
     * @return array
     * @throws Exception
     */
    private function savePluginPermissions(array $permissions): array
    {
        $added       = 0;
        $menuCount   = 0;
        $buttonCount = 0;
        $details     = [];

        foreach ($permissions as $permission) {
            $exists = (new PermissionModel())->where('code', $permission['code'])->find();
            if ($exists) {
                throw new Exception('权限标识已存在: ' . $permission['code']);
            }

            if (!empty($permission['parent_code'])) {
                $parent = (new PermissionModel())->where('code', $permission['parent_code'])->find();
                if (!$parent) {
                    throw new Exception('父级权限不存在: ' . $permission['parent_code']);
                }
                $permission['parent_id'] = (int)$parent['id'];
            } else {
                $permission['parent_id'] = 0;
            }

            unset($permission['parent_code']);

            (new PermissionModel())->create($permission);
            $added++;

            if ($permission['type'] === PermissionModel::TYPE_MENU) {
                $menuCount++;
            } else {
                $buttonCount++;
            }

            $details[] = "[添加] {$permission['name']} ({$permission['code']})";
        }

        return [
            'added'       => $added,
            'skipped'     => 0,
            'failed'      => 0,
            'menuCount'   => $menuCount,
            'buttonCount' => $buttonCount,
            'details'     => $details,
        ];
    }

    /**
     * 构建插件权限查询
     * @param string $pluginName
     * @return PermissionModel|Query
     */
    private function buildPluginPermissionQuery(string $pluginName): PermissionModel|Query
    {
        $prefix = dp_plugin_permission_prefix($pluginName);

        return (new PermissionModel())->where(function ($query) use ($prefix) {
            $query->where('code', $prefix)
                ->whereOr('code', 'like', $prefix . '.%');
        });
    }

    /**
     * 删除插件权限
     * @param string $pluginName
     * @return int
     * @throws DbException
     */
    private function deletePluginPermissions(string $pluginName): int
    {
        return $this->buildPluginPermissionQuery($pluginName)->delete();
    }

    /**
     * 确保插件统一菜单组存在
     *
     * @return void
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws Exception
     */
    private function ensurePluginMenuRoot(): void
    {
        $root = (array)config('plugin.menu_root', []);
        if (!($root['enabled'] ?? false)) {
            return;
        }

        $code = trim((string)($root['code'] ?? ''));
        if ($code === '') {
            return;
        }

        $parentCode = trim((string)($root['parent_code'] ?? ''));
        $parentId   = 0;

        if ($parentCode !== '') {
            $parent = (new PermissionModel())->where('code', $parentCode)->find();
            if (!$parent) {
                Log::warning('插件菜单挂载点不存在，已降级为顶级菜单', [
                    'parent_code' => $parentCode,
                    'menu_code'   => $code,
                ]);
            } else {
                $parentId = (int)$parent['id'];
            }
        }

        $exists   = (new PermissionModel())->where('code', $code)->find();
        $menuData = [
            'parent_id'   => $parentId,
            'name'        => trim((string)($root['name'] ?? '插件扩展')),
            'code'        => $code,
            'type'        => PermissionModel::TYPE_MENU,
            'route'       => $root['route'] ?? null,
            'icon'        => $root['icon'] ?? 'ti ti-plug',
            'sort'        => (int)($root['sort'] ?? 999),
            'status'      => 1,
            'visible'     => (int)($root['visible'] ?? 1),
            'remark'      => trim((string)($root['remark'] ?? '自动生成的插件统一入口')),
            'update_time' => time(),
        ];

        if ($exists) {
            (new PermissionModel())->where('code', $code)->update($menuData);
            return;
        }

        $menuData['create_time'] = time();
        (new PermissionModel())->create($menuData);
    }

    /**
     * 清理权限运行时缓存
     *
     * @return void
     */
    private function clearPermissionRuntimeCache(): void
    {
        try {
            app(PermissionServiceInterface::class)->clearAllCache();
        } catch (Exception $e) {
            Log::warning('插件权限缓存清理失败: ' . $e->getMessage());
        }
    }

    /**
     * 智能推断父级权限代码
     *
     * 根据权限标识自动推断父级权限代码
     * 推断逻辑:
     * - admin.user.create → admin.user
     * - plugin.demo.hello.ping → plugin.demo.hello
     *
     * @param string $code 权限代码
     * @return string|null 父级权限代码,无父级返回null
     */
    private function inferParentCode(string $code): ?string
    {
        $parts = explode('.', $code);

        // 至少2段才有父级
        if (count($parts) >= 2) {
            array_pop($parts);
            return implode('.', $parts);
        }

        // 只有1段时,视为顶级菜单
        return null;
    }

    /**
     * 加载应用配置
     *
     * @param string $app 应用名称
     * @return array|null 应用配置数组,不存在返回null
     */
    private function loadAppConfig(string $app): ?array
    {
        try {
            $config = app(AppService::class)->getAppDefinition($app);
            if ($config === []) {
                return null;
            }

            return $this->mergeRegistryAppConfig($app, $config);
        } catch (Exception $e) {
            Log::warning("加载应用配置失败: $app", [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * 合并后台应用注册表中维护的展示属性
     *
     * @param string $app
     * @param array $config
     * @return array
     */
    private function mergeRegistryAppConfig(string $app, array $config): array
    {
        try {
            $record = $this->appModel->where('name', $app)->find();
        } catch (Exception $e) {
            Log::warning("读取应用注册表失败: $app", [
                'error' => $e->getMessage(),
            ]);
            return $config;
        }

        if (!$record) {
            return $config;
        }

        $icon = trim((string)$record->getAttr('icon'));
        if ($icon !== '') {
            $config['icon'] = $icon;
        }

        $config['sort'] = (int)$record->getAttr('sort');

        return $config;
    }

    /**
     * 创建或更新应用顶级菜单
     *
     * @param string $app 应用名称
     * @param array $appInfo 应用配置信息
     * @return void
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    private function createOrUpdateAppMenu(string $app, array $appInfo): void
    {
        // 检查是否需要创建菜单
        if (!($appInfo['create_menu'] ?? true)) {
            return;
        }

        $code = $appInfo['code'] ?? $app;

        // 检查菜单是否已存在
        $exists = $this->permissionModel->where('code', $code)->find();

        $menuData = [
            'parent_id'   => 0,
            'name'        => $appInfo['name'],
            'code'        => $code,
            'type'        => PermissionModel::TYPE_MENU,
            'route'       => null,
            'icon'        => $appInfo['icon'] ?? null,
            'sort'        => $appInfo['sort'] ?? 0,
            'status'      => 1,
            'visible'     => $appInfo['visible'] ?? 1,
            'remark'      => $appInfo['description'] ?? '自动生成的应用菜单',
            'update_time' => time(),
        ];

        if ($exists) {
            $this->permissionModel->where('code', $code)->update($menuData);
            Log::info("更新应用菜单: {$appInfo['name']} ($code)");
        } else {
            $menuData['create_time'] = time();
            $this->permissionModel->create($menuData);
            Log::info("创建应用菜单: {$appInfo['name']} ($code)");
        }
    }

    /**
     * 获取默认父级权限代码
     *
     * @param string $app 应用名称
     * @param array|null $appInfo 应用配置
     * @param string|null $annotationParent 注解中指定的父级
     * @return string|null 父级权限代码
     */
    private function getDefaultParentCode(string $app, ?array $appInfo, ?string $annotationParent): ?string
    {
        // 优先级1: 注解中指定的父级
        if ($annotationParent !== null) {
            return $annotationParent;
        }

        // 优先级2: 应用配置中的code
        if ($appInfo && ($appInfo['create_menu'] ?? true)) {
            return $appInfo['code'] ?? $app;
        }

        // 优先级3: 无父级,顶级菜单
        return null;
    }
}
