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

namespace app\common\render\table;

use Exception;

/**
 * 表格缓存管理类
 * 为将来的表格构建器提供统一的缓存管理。
 *
 * 注意：当前版本（2026-03）尚未接入 Table 主流程，
 * Table 列组件缓存仍在 `app/common/render/Table.php::$columnInstances` 维护。
 * 本类作为预留层保留，供后续重构统一缓存策略时接入。
 *
 * @package app\common\render\table
 */
class Cache
{
    /**
     * 是否已接入主流程
     */
    public const INTEGRATED = false;

    /**
     * 预留层状态说明
     * @return array
     */
    public static function status(): array
    {
        return [
            'integrated' => self::INTEGRATED,
            'owner' => 'table-builder-refactor',
            'note' => '当前未接入主流程，保留为后续重构预留层',
        ];
    }

    /**
     * 表格列处理器实例缓存
     * @var array
     */
    private static array $columnHandlerCache = [];

    /**
     * 表格数据处理器实例缓存
     * @var array
     */
    private static array $dataProcessorCache = [];

    /**
     * 导出器实例缓存
     * @var array
     */
    private static array $exporterCache = [];

    /**
     * 搜索处理器实例缓存
     * @var array
     */
    private static array $searchHandlerCache = [];

    /**
     * 获取列处理器实例（单例模式）
     * @param string $class 类名
     * @return object
     * @throws Exception
     */
    public static function getColumnHandler(string $class): object
    {
        if (!isset(self::$columnHandlerCache[$class])) {
            if (!class_exists($class)) {
                throw new Exception("Column handler class not found: $class");
            }
            self::$columnHandlerCache[$class] = new $class;
        }
        return self::$columnHandlerCache[$class];
    }

    /**
     * 获取数据处理器实例（单例模式）
     * @param string $class 类名
     * @return object
     * @throws Exception
     */
    public static function getDataProcessor(string $class): object
    {
        if (!isset(self::$dataProcessorCache[$class])) {
            if (!class_exists($class)) {
                throw new Exception("Data processor class not found: $class");
            }
            self::$dataProcessorCache[$class] = new $class;
        }
        return self::$dataProcessorCache[$class];
    }

    /**
     * 获取导出器实例（单例模式）
     * @param string $class 类名
     * @return object
     * @throws Exception
     */
    public static function getExporter(string $class): object
    {
        if (!isset(self::$exporterCache[$class])) {
            if (!class_exists($class)) {
                throw new Exception("Exporter class not found: $class");
            }
            self::$exporterCache[$class] = new $class;
        }
        return self::$exporterCache[$class];
    }

    /**
     * 获取搜索处理器实例（单例模式）
     * @param string $class 类名
     * @return object
     * @throws Exception
     */
    public static function getSearchHandler(string $class): object
    {
        if (!isset(self::$searchHandlerCache[$class])) {
            if (!class_exists($class)) {
                throw new Exception("Search handler class not found: $class");
            }
            self::$searchHandlerCache[$class] = new $class;
        }
        return self::$searchHandlerCache[$class];
    }

    /**
     * 清空指定类型的缓存
     * @param string $type 缓存类型: 'column', 'data', 'exporter', 'search', 'all'
     */
    public static function clearCache(string $type = 'all'): void
    {
        switch ($type) {
            case 'column':
                self::$columnHandlerCache = [];
                break;
            case 'data':
                self::$dataProcessorCache = [];
                break;
            case 'exporter':
                self::$exporterCache = [];
                break;
            case 'search':
                self::$searchHandlerCache = [];
                break;
            case 'all':
            default:
                self::$columnHandlerCache = [];
                self::$dataProcessorCache = [];
                self::$exporterCache = [];
                self::$searchHandlerCache = [];
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
            'column_handler_count' => count(self::$columnHandlerCache),
            'data_processor_count' => count(self::$dataProcessorCache),
            'exporter_count' => count(self::$exporterCache),
            'search_handler_count' => count(self::$searchHandlerCache),
            'total_cached_objects' => count(self::$columnHandlerCache) + count(self::$dataProcessorCache) + 
                                     count(self::$exporterCache) + count(self::$searchHandlerCache),
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
            'column' => isset(self::$columnHandlerCache[$key]),
            'data' => isset(self::$dataProcessorCache[$key]),
            'exporter' => isset(self::$exporterCache[$key]),
            'search' => isset(self::$searchHandlerCache[$key]),
            default => false,
        };
    }
} 
