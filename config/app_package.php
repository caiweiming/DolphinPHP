<?php
// +----------------------------------------------------------------------
// | 应用分发系统配置
// +----------------------------------------------------------------------

return [
    // 应用根目录
    'root'        => app()->getBasePath(),

    // 应用分发协议版本
    'api_version' => '1.0',

    // 元数据文件名
    'manifest'    => 'app.json',

    // 预留/保留应用名，禁止通过导入覆盖
    'reserved_names' => [
        'common',
        'lang',
    ],

    // 生命周期状态
    'lifecycle'   => [
        'imported'  => 'imported',
        'installed' => 'installed',
        'broken'    => 'broken',
    ],

    // 存储配置
    'storage'     => [
        'log_table' => 'admin_app_log',
    ],

    // 应用分发包导出
    'package'     => [
        'temp_root'           => runtime_path() . 'apps/packages',
        'keep_seconds'        => 3600,
        'exclude_directories' => [
            '.git',
            '.svn',
            '.hg',
            '.idea',
            '.vscode',
            'runtime',
        ],
        'exclude_files'       => [
            '.DS_Store',
            'Thumbs.db',
        ],
        'exclude_patterns'    => [
            '*.log',
            '*.tmp',
            '*.temp',
            '*~',
        ],
    ],

    // 应用分发包导入
    'import'      => [
        'temp_root'             => runtime_path() . 'apps/imports',
        'keep_seconds'          => 3600,
        'allowed_extensions'    => ['zip'],
        'max_size'              => 52 * 1024 * 1024,
        'max_entries'           => 3000,
        'max_uncompressed_size' => 300 * 1024 * 1024,
        'allow_overwrite'       => false,
    ],

    // 应用安装后的资源发布
    'publish'     => [
        'enabled'      => true,
        'symlink'      => true,
        'source_dir'   => 'public',
        'target_root'  => public_path() . 'apps',
    ],

    // 应用包源配置，为后续在线商店预留统一入口
    'sources'     => [
        'local' => [
            'driver'            => 'directory',
            'title'             => '本地目录源',
            'enabled'           => true,
            'root'              => runtime_path() . 'apps/store/local',
            'recursive'         => false,
            'require_integrity' => false,
            'signature_secret'  => '',
        ],
        'remote' => [
            'driver'            => 'remote_catalog',
            'title'             => '远程商城源',
            'enabled'           => false,
            'catalog_url'       => '',
            'timeout'           => 15,
            'headers'           => [],
            'require_integrity' => true,
            'signature_secret'  => '',
        ],
    ],

    // 商店安装链路
    'store'       => [
        'temp_root'          => runtime_path() . 'apps/store',
        'allow_auto_install' => true,
    ],
];
