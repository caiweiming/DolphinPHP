<?php
// +----------------------------------------------------------------------
// | 模板设置
// +----------------------------------------------------------------------

// URL访问根地址
$root = request()->rootUrl();

return [
    // 模板引擎类型使用Think
    'type'               => 'Think',
    // 默认模板渲染规则 1 解析为小写+下划线 2 全部转换小写 3 保持操作方法
    'auto_rule'          => 1,
    // 模板目录名
    'view_dir_name'      => 'view',
    // 模板后缀
    'view_suffix'        => 'html',
    // 模板文件名分隔符
    'view_depr'          => DIRECTORY_SEPARATOR,
    // 模板引擎普通标签开始标记
    'tpl_begin'          => '{',
    // 模板引擎普通标签结束标记
    'tpl_end'            => '}',
    // 标签库标签开始标记
    'taglib_begin'       => '{',
    // 标签库标签结束标记
    'taglib_end'         => '}',
    // 模板内容替换
    'tpl_replace_string' => [
        // 静态资源目录
        '__STATIC__'           => $root . '/static',
        // 第三方扩展目录
        '__LIBS__'             => $root . '/static/libs',
        // JS目录
        '__JS__'               => $root . '/static/js',
        // CSS目录
        '__CSS__'              => $root . '/static/css',
        // 图片目录
        '__IMG__'              => $root . '/static/img',
        // 渲染器根目录
        '__RENDER__'           => $root . '/static/render',
        // 表单渲染器目录
        '__RENDER_FORM__'      => $root . '/static/render/form',
        // 表格渲染器目录
        '__RENDER_TABLE__'     => $root . '/static/render/table',
        // 主题目录
        '__THEME__'            => $root . '/static/tabler',
        // 主题libs目录
        '__THEME_LIBS__'       => $root . '/static/theme/libs',
        // 主题js目录
        '__THEME_JS__'         => $root . '/static/theme/js',
        // 主题css目录
        '__THEME_CSS__'        => $root . '/static/theme/css',
        // 主题img目录
        '__THEME_IMG__'        => $root . '/static/theme/img',
        // 主题static目录
        '__THEME_STATIC__'     => $root . '/static/theme/static',
        // 扩展项静态资源目录
        '__EXTEND__'           => $root . '/extend',
        // 表单扩展项静态资源目录
        '__EXTEND_FORM__'      => $root . '/extend/form',
        // 表格扩展项静态资源目录
        '__EXTEND_TABLE__'     => $root . '/extend/table',
        // 图表扩展静态资源目录
        '__EXTEND_CHART__'     => $root . '/extend/chart',
        // 图表地图专项扩展静态资源目录
        '__EXTEND_CHART_MAP__' => $root . '/extend/chart_map',
        // 上传驱动静态资源目录
        '__EXTEND_UPLOAD__'    => $root . '/extend/upload',
    ]
];
