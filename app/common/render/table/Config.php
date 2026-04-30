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

use think\facade\Config as SystemConfig;

/**
 * 表格配置管理类
 * 为将来的表格构建器提供统一的配置管理。
 *
 * 注意：当前版本（2026-03）尚未接入 Table 主流程，
 * 主流程仍以 `config/table.php` + `app/common/render/Table.php` 为准。
 * 本类作为预留层保留，避免后续重构时重复造轮子。
 *
 * @package app\common\render\table
 */
class Config
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
     * 获取表格默认配置
     * @return array
     */
    public static function getDefaults(): array
    {
        $defaults = [
            'dp_table_id' => '',
            'dp_table_title' => '',
            'dp_table_class' => 'dp-table table table-striped',
            'dp_table_responsive' => true,
            'dp_table_bordered' => false,
            'dp_table_hover' => true,
            'dp_table_striped' => true,
            'dp_table_small' => false,
            'dp_table_header' => true,
            'dp_table_footer' => true,
            'dp_table_pagination' => true,
            'dp_table_search' => true,
            'dp_table_export' => true,
            'dp_table_columns' => [],
            'dp_table_data' => [],
            'dp_table_actions' => [],
        ];

        // 合并工具栏配置
        $defaults = array_merge($defaults, static::getToolbarDefaults());
        
        // 合并分页配置
        $defaults = array_merge($defaults, static::getPaginationDefaults());
        
        // 合并操作配置
        return array_merge($defaults, static::getActionDefaults());
    }

    /**
     * 获取工具栏默认配置
     * @return array
     */
    private static function getToolbarDefaults(): array
    {
        return [
            'dp_table_toolbar' => [
                'left' => [],
                'right' => [],
                'center' => []
            ],
            'dp_table_toolbar_buttons' => [
                'add' => [
                    'title' => lang('dp#add'),
                    'class' => 'btn btn-primary',
                    'icon' => 'plus'
                ],
                'edit' => [
                    'title' => lang('dp#edit'),
                    'class' => 'btn btn-warning',
                    'icon' => 'edit'
                ],
                'delete' => [
                    'title' => lang('dp#delete'),
                    'class' => 'btn btn-danger',
                    'icon' => 'trash'
                ]
            ]
        ];
    }

    /**
     * 获取分页默认配置
     * @return array
     */
    private static function getPaginationDefaults(): array
    {
        return [
            'dp_table_page_size' => 20,
            'dp_table_page_sizes' => [10, 20, 50, 100],
            'dp_table_page_info' => true,
            'dp_table_page_jump' => true,
        ];
    }

    /**
     * 获取操作列默认配置
     * @return array
     */
    private static function getActionDefaults(): array
    {
        return [
            'dp_table_action_column' => [
                'title' => lang('dp#actions'),
                'width' => '120px',
                'align' => 'center',
                'fixed' => 'right'
            ],
            'dp_table_row_actions' => []
        ];
    }

    /**
     * 获取所有默认配置（包含系统配置）
     * @return array
     */
    public static function getAllDefaults(): array
    {
        $defaults = static::getDefaults();
        
        // 合并系统级别的配置
        $systemConfig = [
            'dp_table_export_config' => SystemConfig::get('table.export_config', []),
            'dp_table_search_config' => SystemConfig::get('table.search_config', []),
        ];
        
        return array_merge($defaults, $systemConfig);
    }
} 
