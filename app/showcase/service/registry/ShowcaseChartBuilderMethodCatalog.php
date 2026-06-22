<?php
declare(strict_types=1);

namespace app\showcase\service\registry;

/**
 * Showcase 图表构建器方法目录
 */
final class ShowcaseChartBuilderMethodCatalog
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function groups(): array
    {
        return [
            [
                'key' => 'basic',
                'title' => '基础展示',
                'summary' => '控制标题、副标题、尺寸、渲染器和主题。',
                'methods' => ['title', 'subtitle', 'height', 'minHeight', 'renderer', 'theme'],
            ],
            [
                'key' => 'data',
                'title' => '数据结构',
                'summary' => '控制图表类型、类目与系列数据。',
                'methods' => ['type', 'categories', 'series'],
            ],
            [
                'key' => 'dataset',
                'title' => '数据与交互',
                'summary' => '控制异步数据源、loading、empty、原生 option 与地图专项协议。',
                'methods' => ['dataset', 'datasetMerge', 'loading', 'empty', 'option', 'map', 'mapData'],
            ],
        ];
    }
}
