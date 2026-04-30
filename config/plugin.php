<?php
// +----------------------------------------------------------------------
// | 插件系统配置
// +----------------------------------------------------------------------

return [
    // 插件根目录
    'root'             => root_path() . 'plugins',

    // 插件协议版本
    'api_version'      => '1.0',

    // 插件权限声明文件
    'permission_file'  => 'permissions.php',

    // 是否在运行时自动装载已启用插件
    'auto_load'        => true,

    // 运行时缓存
    'cache'            => [
        'enabled_key' => 'plugin_runtime:enabled',
        'ttl'         => 300,
    ],

    // 静态资源前缀
    'asset_prefix'     => 'plugins',

    // 插件静态资源发布
    'asset'            => [
        'url_prefix'  => 'plugins',
        'public_root' => public_path() . 'plugins',
        'symlink'     => true,
    ],

    // 插件分发包导出
    'package'          => [
        'temp_root'           => runtime_path() . 'plugins/packages',
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

    // 插件分发包导入
    'import'           => [
        'temp_root'             => runtime_path() . 'plugins/imports',
        'keep_seconds'          => 3600,
        'allowed_extensions'    => ['zip'],
        'max_size'              => 52 * 1024 * 1024,
        'max_entries'           => 2000,
        'max_uncompressed_size' => 200 * 1024 * 1024,
        'allow_overwrite'       => false,
    ],

    // Composer 分发模式
    'composer'         => [
        'package_type'          => 'dolphinphp-plugin',
        'install_root'          => root_path() . 'plugins',
        'requires_default_root' => true,
    ],

    // 插件存储表
    'storage'          => [
        'plugin_table' => 'admin_plugin',
        'log_table'    => 'admin_plugin_log',
    ],

    // 插件状态
    'status'           => [
        'discovered' => 'discovered',
        'installed'  => 'installed',
        'enabled'    => 'enabled',
        'disabled'   => 'disabled',
        'invalid'    => 'invalid',
        'broken'     => 'broken',
    ],

    // 插件统一菜单组
    'menu_root'        => [
        'enabled'     => true,
        'code'        => 'plugin.extensions',
        'name'        => '插件扩展',
        'parent_code' => 'system',
        'icon'        => 'ti ti-plug',
        'sort'        => 110,
        'visible'     => 1,
        'remark'      => '自动生成的插件统一入口',
    ],

    // 插件菜单默认挂载点
    'menu_parent_code' => 'plugin.extensions',

    // 插件运行时资源索引
    'view_paths'       => [],
    'public_paths'     => [],
    'asset_urls'       => [],
];
