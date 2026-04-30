<?php
// +----------------------------------------------------------------------
// | 图标库配置
// +----------------------------------------------------------------------

return [
    // 是否自动加载扩展图标库 CSS
    'auto_load_css' => true,

    // 扩展图标库配置
    'libs'          => [
        [
            'file'     => 'extend/icon/simple-line.php',
            'css'      => '/static/libs/simplelineicons/css/simple-line-icons.css',
            'autoload' => true,
            'enabled'  => true,
        ],
    ],
];
