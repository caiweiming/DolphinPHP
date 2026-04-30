<?php
return [
    // 驱动元信息
    'meta'         => [
        'name'        => '本地存储',
        'version'     => '1.0.0',
        'description' => '基于本地文件系统的上传驱动',
        'author'      => 'DolphinPHP',
        'homepage'    => 'https://www.dolphinphp.com',
        'requires'    => [
            'php' => '>=8.0'
        ]
    ],

    // 驱动类信息
    'driver'       => [
        'name' => 'local'
    ],

    // 默认配置
    'config'       => [
        // 本地存储无需配置参数，使用系统默认设置
        'upload_path' => 'uploads',
        'domain'      => '',
    ]
];
