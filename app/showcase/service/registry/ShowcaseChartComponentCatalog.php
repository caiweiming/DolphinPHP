<?php
declare(strict_types=1);

namespace app\showcase\service\registry;

/**
 * Showcase 图表组件目录
 */
final class ShowcaseChartComponentCatalog
{
    /**
     * @return array<string, array<string, string>>
     */
    public static function groups(): array
    {
        return [
            'basic' => [
                'title' => '基础图表',
                'summary' => '覆盖 line、bar、pie、scatter 四类最常用图表能力。',
            ],
            'map' => [
                'title' => '地图图表',
                'summary' => '覆盖地图专项扩展协议、区域着色、覆盖物以及 mapData 动态合并能力。',
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function components(): array
    {
        return [
            'basic.line' => [
                'key' => 'basic.line',
                'renderer' => 'chart',
                'group' => 'basic',
                'group_title' => '基础图表',
                'title' => 'line 趋势折线图',
                'summary' => '用于展示随时间或序列变化的趋势，是后台报表里最常见的图表类型。',
                'status' => 'available',
                'tags' => ['趋势', '多系列', 'Ajax', '空态'],
                'doc_links' => ['docs/图表/chart.md', 'docs/图表/README.md'],
                'source_refs' => [
                    ['path' => 'app/common/render/Chart.php', 'label' => '图表构建器源码', 'description' => '图表通用构建入口。'],
                    ['path' => 'app/common/render/chart/type/Line.php', 'label' => 'line 类型构建器', 'description' => 'line 类型的 option 生成逻辑。'],
                ],
                'builder' => 'app\\showcase\\service\\components\\chart\\LineChartPage',
            ],
            'basic.bar' => [
                'key' => 'basic.bar',
                'renderer' => 'chart',
                'group' => 'basic',
                'group_title' => '基础图表',
                'title' => 'bar 对比柱状图',
                'summary' => '用于展示类目之间的数量或金额对比，适合排行榜、阶段统计和多维度并列比较。',
                'status' => 'available',
                'tags' => ['对比', '堆叠', '横向', '排行'],
                'doc_links' => ['docs/图表/chart.md', 'docs/图表/README.md'],
                'source_refs' => [
                    ['path' => 'app/common/render/Chart.php', 'label' => '图表构建器源码', 'description' => '图表通用构建入口。'],
                    ['path' => 'app/common/render/chart/type/Bar.php', 'label' => 'bar 类型构建器', 'description' => 'bar 类型的 option 生成逻辑。'],
                ],
                'builder' => 'app\\showcase\\service\\components\\chart\\BarChartPage',
            ],
            'basic.pie' => [
                'key' => 'basic.pie',
                'renderer' => 'chart',
                'group' => 'basic',
                'group_title' => '基础图表',
                'title' => 'pie 占比饼图',
                'summary' => '用于展示整体中各部分的占比关系，适合来源构成、状态占比和分类分布。',
                'status' => 'available',
                'tags' => ['占比', '环形', '构成', '分布'],
                'doc_links' => ['docs/图表/chart.md', 'docs/图表/README.md'],
                'source_refs' => [
                    ['path' => 'app/common/render/Chart.php', 'label' => '图表构建器源码', 'description' => '图表通用构建入口。'],
                    ['path' => 'app/common/render/chart/type/Pie.php', 'label' => 'pie 类型构建器', 'description' => 'pie 类型的 option 生成逻辑。'],
                ],
                'builder' => 'app\\showcase\\service\\components\\chart\\PieChartPage',
            ],
            'basic.scatter' => [
                'key' => 'basic.scatter',
                'renderer' => 'chart',
                'group' => 'basic',
                'group_title' => '基础图表',
                'title' => 'scatter 分布散点图',
                'summary' => '用于展示两个连续变量之间的分布与相关性，适合用户分层、质量评估和样本聚类。',
                'status' => 'available',
                'tags' => ['分布', '相关性', '气泡', '多系列'],
                'doc_links' => ['docs/图表/chart.md', 'docs/图表/README.md'],
                'source_refs' => [
                    ['path' => 'app/common/render/Chart.php', 'label' => '图表构建器源码', 'description' => '图表通用构建入口。'],
                    ['path' => 'app/common/render/chart/type/Scatter.php', 'label' => 'scatter 类型构建器', 'description' => 'scatter 类型的 option 生成逻辑。'],
                ],
                'builder' => 'app\\showcase\\service\\components\\chart\\ScatterChartPage',
            ],
            'map.demo_region' => [
                'key' => 'map.demo_region',
                'renderer' => 'chart',
                'group' => 'map',
                'group_title' => '地图图表',
                'title' => 'demo_region 示例区域地图',
                'summary' => '基于最小 GeoJSON 的地图专项协议示例，适合先理解 map()、regions 和 overlays 的基础写法。',
                'status' => 'available',
                'tags' => ['地图协议', '区域着色', 'effectScatter', 'lines'],
                'doc_links' => ['docs/图表/chart.md', 'docs/图表/README.md'],
                'source_refs' => [
                    ['path' => 'app/common/render/Chart.php', 'label' => '图表构建器源码', 'description' => 'map() / mapData() 图表地图专项入口。'],
                    ['path' => 'extend/chart_map/demo_region/Provider.php', 'label' => 'demo_region Provider', 'description' => '最小地图专项扩展包的区域归一化与资源定义。'],
                    ['path' => 'public/extend/chart_map/demo_region/demo_region.js', 'label' => 'demo_region 前端 hook', 'description' => '地图渲染后的额外 option 调整。'],
                ],
                'builder' => 'app\\showcase\\service\\components\\chart\\DemoRegionMapChartPage',
            ],
            'map.china_guangdong' => [
                'key' => 'map.china_guangdong',
                'renderer' => 'chart',
                'group' => 'map',
                'group_title' => '地图图表',
                'title' => 'china.guangdong 广东地图',
                'summary' => '基于广东省地市级 GeoJSON 的地图示例，适合展示省内区域分布、重点城市和业务流向。',
                'status' => 'available',
                'tags' => ['省级地图', '城市分布', '飞线', 'mapData'],
                'doc_links' => ['docs/图表/chart.md', 'docs/图表/README.md'],
                'source_refs' => [
                    ['path' => 'app/common/render/Chart.php', 'label' => '图表构建器源码', 'description' => 'map() / mapData() 图表地图专项入口。'],
                    ['path' => 'extend/chart_map/china/guangdong/Provider.php', 'label' => '广东地图 Provider', 'description' => '广东省地图扩展包的区域归一化与资源定义。'],
                    ['path' => 'public/extend/chart_map/china/guangdong/guangdong.js', 'label' => '广东地图前端 hook', 'description' => '地图渲染后的 tooltip 与强调态调整。'],
                ],
                'builder' => 'app\\showcase\\service\\components\\chart\\ChinaGuangdongMapChartPage',
            ],
        ];
    }
}
