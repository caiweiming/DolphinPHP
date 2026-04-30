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
declare (strict_types=1);

namespace app\common\render\form;

use ReflectionClass;
use Exception;
use app\common\abstract\FormItem;

/**
 * 表单缓存管理类
 * @package app\common\render\form
 */
class Cache
{
    /**
     * 模板文件缓存
     * @var array
     */
    private static array $templateCache = [];

    /**
     * 表单项类实例缓存
     * @var array
     */
    private static array $itemInstanceCache = [];

    /**
     * 反射对象缓存
     * @var array
     */
    private static array $reflectionCache = [];

    /**
     * 上传驱动实例缓存
     * @var array
     */
    private static array $uploadDriverCache = [];

    /**
     * 获取缓存的模板内容
     * @param string $templatePath 模板文件路径
     * @return string
     * @throws Exception
     */
    public static function getTemplate(string $templatePath): string
    {
        // 检查路径长度（防止超长路径攻击）
        if (strlen($templatePath) > 255) {
            throw new Exception('模板路径过长');
        }

        // 检查非法字符（NULL字节注入）
        if (str_contains($templatePath, "\0")) {
            throw new Exception('无效路径');
        }

        // 获取真实路径
        $realPath = realpath($templatePath);
        if ($realPath === false) {
            throw new Exception(lang('dp#template not exists', ['file' => $templatePath]));
        }

        // 检查是否是文件（不是目录）
        if (!is_file($realPath)) {
            throw new Exception('模板路径必须是文件');
        }

        // 检查文件扩展名
        $allowedExt = ['html', 'htm'];
        $ext        = strtolower(pathinfo($realPath, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExt)) {
            throw new Exception("无效的模板后缀: .$ext");
        }

        // 检查文件目录路径
        if (!str_starts_with($realPath, root_path())) {
            throw new Exception('仅允许加载项目根目录下的模板文件');
        }

        if (!isset(self::$templateCache[$templatePath])) {
            if (!file_exists($templatePath)) {
                throw new Exception(lang('dp#template not exists', ['file' => $templatePath]));
            }
            self::$templateCache[$templatePath] = file_get_contents($templatePath);
        }
        return self::$templateCache[$templatePath];
    }

    /**
     * 获取表单项类实例（单例模式）
     * @param string $class 类名
     * @return FormItem
     * @throws Exception
     */
    public static function getItemInstance(string $class): FormItem
    {
        if (!isset(self::$itemInstanceCache[$class])) {
            if (!class_exists($class)) {
                throw new Exception(lang('dp#undefined class', ['class' => $class]));
            }

            $instance = new $class;
            if (!($instance instanceof FormItem)) {
                throw new Exception(lang("dp#class {:class} must extend FormItem", ['class' => $class]));
            }

            self::$itemInstanceCache[$class] = $instance;
        }
        return self::$itemInstanceCache[$class];
    }

    /**
     * 获取反射对象（缓存）
     * @param string $class 类名
     * @return ReflectionClass
     * @throws Exception
     */
    public static function getItemReflection(string $class): ReflectionClass
    {
        if (!isset(self::$reflectionCache[$class])) {
            if (!class_exists($class)) {
                throw new Exception(lang('dp#undefined class', ['class' => $class]));
            }
            self::$reflectionCache[$class] = new ReflectionClass($class);
        }
        return self::$reflectionCache[$class];
    }

    /**
     * 获取上传驱动实例（单例模式）
     * @param string $class 驱动类名
     * @return object
     * @throws Exception
     */
    public static function getUploadDriverInstance(string $class): object
    {
        if (!isset(self::$uploadDriverCache[$class])) {
            if (!class_exists($class)) {
                throw new Exception(lang('dp#invalid upload driver', ['class' => $class]));
            }
            self::$uploadDriverCache[$class] = new $class;
        }
        return self::$uploadDriverCache[$class];
    }

    /**
     * 清空指定类型的缓存
     * @param string $type 缓存类型: 'template', 'item', 'reflection', 'driver', 'all'
     */
    public static function clearCache(string $type = 'all'): void
    {
        switch ($type) {
            case 'template':
                self::$templateCache = [];
                break;
            case 'item':
                self::$itemInstanceCache = [];
                break;
            case 'reflection':
                self::$reflectionCache = [];
                break;
            case 'driver':
                self::$uploadDriverCache = [];
                break;
            case 'all':
            default:
                self::$templateCache     = [];
                self::$itemInstanceCache = [];
                self::$reflectionCache   = [];
                self::$uploadDriverCache = [];
                break;
        }
    }

    /**
     * 获取缓存统计信息
     * @return array
     */
    public static function getCacheStats(): array
    {
        return [
            'template_count'       => count(self::$templateCache),
            'item_instance_count'  => count(self::$itemInstanceCache),
            'reflection_count'     => count(self::$reflectionCache),
            'upload_driver_count'  => count(self::$uploadDriverCache),
            'total_cached_objects' => count(self::$templateCache) + count(self::$itemInstanceCache) +
                count(self::$reflectionCache) + count(self::$uploadDriverCache),
        ];
    }

    /**
     * 检查特定缓存是否存在
     * @param string $type 缓存类型
     * @param string $key 缓存键
     * @return bool
     */
    public static function hasCache(string $type, string $key): bool
    {
        return match ($type) {
            'template'      => isset(self::$templateCache[$key]),
            'item'          => isset(self::$itemInstanceCache[$key]),
            'reflection'    => isset(self::$reflectionCache[$key]),
            'driver'        => isset(self::$uploadDriverCache[$key]),
            default         => false,
        };
    }
}
