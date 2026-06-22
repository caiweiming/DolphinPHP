<?php
declare(strict_types=1);

namespace app\showcase\service\registry;

/**
 * Showcase 渲染器入口卡片目录
 */
final class ShowcaseRendererCatalog
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        return [
            'form' => [
                'key' => 'form',
                'title' => '表单渲染器',
                'summary' => '字段、联动、上传和复杂结构示例',
                'status' => 'available',
                'route' => 'showcase/admin.form/index',
            ],
            'table' => [
                'key' => 'table',
                'title' => '表格渲染器',
                'summary' => '列表、搜索、工具栏和快速编辑示例',
                'status' => 'available',
                'route' => 'showcase/admin.table/index',
            ],
            'chart' => [
                'key' => 'chart',
                'title' => '图表渲染器',
                'summary' => 'ECharts 配置、数据源与地图示例',
                'status' => 'available',
                'route' => 'showcase/admin.chart/index',
            ],
            'page' => [
                'key' => 'page',
                'title' => '页面渲染器',
                'summary' => '页面动作、标签页和容器布局示例',
                'status' => 'available',
                'route' => 'showcase/admin.page/index',
            ],
        ];
    }
}
