<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2024 广东卓锐软件有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://www.dolphinphp.com
// +----------------------------------------------------------------------

// 图表渲染配置文件

use app\common\render\chart\type\Bar;
use app\common\render\chart\type\Line;
use app\common\render\chart\type\Pie;
use app\common\render\chart\type\Scatter;

return [
    // 静态页面信息
    'view'       => [
        'layout' => 'layout.html',
    ],
    // 默认渲染器
    'renderer'   => 'canvas',
    // 默认主题
    'theme'      => '',
    // 默认高度
    'height'     => '320px',
    // 默认最小高度
    'min_height' => '240px',
    // 加载态
    'loading'    => [
        'show'      => false,
        'text'      => '加载中...',
        'maskColor' => 'rgba(255,255,255,0.65)',
    ],
    // 空态
    'empty'      => [
        'title'       => '暂无数据',
        'description' => '当前图表暂无可展示的数据',
    ],
    // 默认 option
    'option'     => [
        'animationDuration' => 300,
        'animationEasing'   => 'cubicOut',
        'textStyle'         => [
            'fontFamily' => 'inherit',
        ],
    ],
    // 图表类型构建器映射
    // 显式注册优先级最高，可用于覆盖内置类型或声明项目级固定映射
    // 未显式注册的类型会继续尝试按 extend/chart/<type>/Type.php 自动发现
    'types'      => [
        'line'    => Line::class,
        'bar'     => Bar::class,
        'pie'     => Pie::class,
        'scatter' => Scatter::class,
    ],
    // 地图专项扩展 Provider 映射
    // 显式注册优先级最高；未命中时继续尝试 extend/chart_map/<map-key>/Provider.php 自动发现
    'maps'       => [],
];
