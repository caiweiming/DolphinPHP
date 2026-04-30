<?php
// +----------------------------------------------------------------------
// | 轻量级日志系统配置文件
// | 管理操作日志的记录和存储
// +----------------------------------------------------------------------

return [
    // 基础配置
    'enable'       => env('LOG_ENABLE', true),            // 日志系统总开关
    'debug'        => env('LOG_DEBUG', false),             // 调试模式

    // 存储配置
    'storage'      => [
        'default' => env('LOG_STORAGE_DEFAULT', 'database'),
        'drivers' => [
            // 数据库存储
            'database' => [
                'table'      => 'admin_log',             // 日志表名（不包含前缀，Db::name会自动添加）
                'connection' => 'default',          // 数据库连接
            ],
            // 文件存储
            'file'     => [
                'path'         => runtime_path('logs/operation'),  // 日志文件路径
                'max_size'     => '10MB',               // 单文件最大大小
                'rotate'       => true,                   // 是否启用日志轮转
                'rotate_count' => 30,               // 保留文件数量
            ],
            // 队列存储(异步处理)
            'queue'    => [
                'connection' => 'default',          // 队列连接
                'queue'      => 'log',                   // 队列名称
                'delay'      => 0,                       // 延迟时间(秒)
            ],
        ],
    ],

    // 日志级别配置
    'levels'       => ['debug', 'info', 'warning', 'error'],
    'level_filter' => env('LOG_LEVEL_FILTER', 'info'),  // 最低记录级别

    // 自动数据收集配置
    'auto_collect' => [
        'user_info'    => true,                    // 自动收集用户信息
        'request_info' => true,                 // 自动收集请求信息
        'session_info' => false,                // 自动收集会话信息
        'environment'  => false,                 // 自动收集环境信息
    ],

    // 性能优化配置
    'performance'  => [
        'async'         => env('LOG_ASYNC', false),    // 异步处理（默认关闭，避免队列问题）
        'batch_size'    => 100,                   // 批量写入大小
        'batch_timeout' => 5,                  // 批量写入超时(秒)
        'sample_rate'   => 100,                  // 全局采样率(%)
        'cache_enable'  => true,                // 启用缓存
        'cache_timeout' => 60,                 // 缓存超时(秒)
    ],

    // 数据过滤配置
    'filter'       => [
        'sensitive_fields' => [
            'password', 'token', 'secret', 'key',
            'credential', 'auth', 'session_id'
        ],
        'max_context_size' => 2048,            // 上下文数据最大大小(字节)
        'max_title_length' => 255,             // 标题最大长度
        'exclude_urls'     => [                    // 排除的URL路径
                                                   '/health', '/ping', '/favicon.ico'
        ],
        'exclude_methods'  => ['OPTIONS'],      // 排除的HTTP方法
    ],

    // 中间件配置
    'middleware'   => [
        'enable'            => env('LOG_MIDDLEWARE_ENABLE', true), // 中间件开关
        'auto_annotation'   => true,             // 自动处理注解
        'force_async'       => false,                // 强制异步处理
        'exception_silence' => true,           // 静默异常处理
        'queue_fallback'    => true,              // 队列失败时自动降级为同步处理
    ],

    // 格式配置
    'format'       => [
        'datetime_format' => 'Y-m-d H:i:s',   // 时间格式
        'json_options'    => JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        'include_trace'   => false,              // 是否包含调用栈
    ],

    // 环境特定配置
    'environments' => [
        'testing'     => [
            'enable'       => false,                 // 测试环境默认禁用
            'storage'      => [
                'default' => 'file'            // 仅文件存储
            ],
            'level_filter' => 'warning',       // 仅记录警告及以上
        ],
        'production'  => [
            'performance' => [
                'async'       => true,               // 生产环境强制异步
                'sample_rate' => 50,           // 降低采样率
            ],
            'filter'      => [
                'max_context_size' => 1024,    // 减少上下文大小
            ],
        ],
        'development' => [
            'debug'        => true,                   // 开发环境开启调试
            'level_filter' => 'debug',        // 记录所有级别
            'performance'  => [
                'async' => false,              // 开发环境同步处理
            ],
        ],
    ],
];
