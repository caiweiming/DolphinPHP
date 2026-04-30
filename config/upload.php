<?php
// +----------------------------------------------------------------------
// | 上传驱动设置
// +----------------------------------------------------------------------

return [
    // 默认驱动
    'default'         => env('UPLOAD_DEFAULT_DRIVER', 'local'),

    // 默认上传url
    'url'             => 'admin/api/upload',

    // 驱动发现配置
    'discovery'       => [
        // 驱动路径（插件驱动通过 PluginRegistry 注册，不走 discovery.paths）
        'paths'         => [
            // 核心驱动路径
            'core'   => 'extend/upload',
            // 第三方包驱动路径
            'vendor' => 'vendor/*/*/upload',
            // 自定义驱动路径
            'custom' => 'app/upload'
        ],
        // 是否开启自动发现
        'auto_discover' => true,
        // 是否开启缓存
        'cache'         => true,
        // 缓存时间
        'cache_time'    => 3600,
        // 缓存名称
        'cache_key'     => 'upload_drivers_cache'
    ],

    // 空数组表示允许所有已注册的驱动
    'allowed_drivers' => [],

    // 文件大小限制（字节）
    'size_limit'      => [
        'image'   => 5 * 1024 * 1024,    // 图片：5MB
        'video'   => 100 * 1024 * 1024,  // 视频：100MB
        'audio'   => 20 * 1024 * 1024,   // 文档：20MB
        'file'    => 20 * 1024 * 1024,   // 文档：20MB
        'default' => 10 * 1024 * 1024,   // 默认：10MB
    ],

    // 允许上传的文件扩展名（白名单）
    'allowed_ext'     => [
        'image' => ['png', 'gif', 'jpg', 'jpeg', 'webp', 'bmp', 'webp'],
        'video' => ['mp4', 'avi', 'wmv', 'mpg', 'mpeg', 'mov', 'rm', 'rmvb', 'flv', 'webm'],
        'audio' => ['mp3', 'wav', 'aac', 'flac', 'ogg', 'wma'],
        'file'  => [
            'zip', 'rar', '7z', 'tar', 'gz',
            'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
            'pdf', 'txt', 'md',
        ],
    ],

    // 默认禁止上传的MIME类型（黑名单）
    'deny_mime'       => [
        'text/x-php',
        'text/html',
        'application/x-httpd-php',
        'application/x-php',
        'application/php',
        'application/x-httpd-php-source',
        'application/x-sh',
        'application/x-csh',
        'text/x-python',
        'text/x-perl',
        'text/x-bash',
    ],

    // 默认禁止上传的文件后缀（黑名单）
    'deny_ext'        => [
        // PHP 相关（直接执行风险）
        'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'pht', 'phps', 'phpt',
        // Web 脚本（可能包含恶意脚本）
        'html', 'htm', 'js', 'jsx', 'ts', 'tsx',
        // 服务器配置文件（可能修改服务器行为）
        'htaccess', 'htpasswd', 'ini', 'conf', 'config',
        // SVG 可以包含 JavaScript
        'svg',
        // 可执行文件
        'exe', 'bat', 'cmd', 'com', 'pif', 'scr', 'vbs', 'sh', 'bash',
        // 脚本语言
        'py', 'pl', 'rb', 'lua', 'asp', 'aspx', 'jsp', 'cgi',
        // 其他危险文件
        'cer', 'csr', 'der', 'key', 'p12', 'pem', 'pfx',
    ],
];
