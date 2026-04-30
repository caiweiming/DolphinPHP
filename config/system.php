<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2024 广东卓锐软件有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://www.dolphinphp.com
// +----------------------------------------------------------------------

// +----------------------------------------------------------------------
// | 系统配置
// +----------------------------------------------------------------------

return [
    // 后台用户session名
    'admin_session'   => 'dp_admin_auth',
    // 后台用户cookie用户id名称
    'admin_uid'       => 'dp_admin_uid',
    // 后台用户cookie登录token名称
    'admin_token'     => 'dp_admin_token',
    // 后台 remember-me cookie 名称
    'admin_remember'  => 'dp_admin_remember',

    // 默认登录地址
    'login_url'       => 'admin/login/index',
    // 后台免登录天数
    'login_days'      => 7,

    // 默认模板布局文件
    'layout'          => 'admin/view/layout/default.html',
    // 标签页显示配置：iframe | div
    'tab_mode'        => 'iframe',
    // 标签页运行配置
    'tabs'            => [
        // 最大可打开标签页数量
        'max_tabs_limit'               => 20,
        // 是否启用标签自动暂停
        'auto_pause_enabled'           => true,
        // 页面隐藏时是否触发自动暂停
        'auto_pause_on_hidden'         => true,
        // 自动暂停阈值（分钟）
        'auto_pause_threshold_minutes' => 10,
        // 触发自动暂停所需的最小候选标签数
        'auto_pause_min_tabs'          => 10,
    ],

    // iframe模式的详细配置
    'iframe_config'   => [
        // 延迟加载
        'lazy_loading'    => true,
        // 安全策略
        'security_policy' => 'same-origin',
        // 高度模式: dynamic | fixed
        'height_mode'     => 'dynamic',
        // 错误重试
        'error_retry'     => true,
        // 内存清理
        'memory_cleanup'  => true,
    ],

    // 工作台配置
    'workspace'       => [
        // 快捷入口配置
        'quick_links' => [
            // 单个用户最多可维护的快捷入口数量
            'max_links' => 12,
        ],
    ],

    // 日志开关，false-关闭，true-开启，['info', 'error']-表示只记录对应类别的日志
    'log'             => true,

    // 超级管理员配置
    'super_admin'     => [
        // 超级管理员用户ID列表(用于向后兼容和多重验证)
        'user_ids'        => [1],
        // 超级管理员角色ID列表
        'role_ids'        => [1],
        // 受保护的用户ID列表(不可删除)
        'protected_users' => [1],
        // 受保护的角色ID列表(不可删除)
        'protected_roles' => [1],
        // 系统内置角色ID列表(不可删除)
        'system_roles'    => [1],
    ],

    // 权限控制配置
    'permission'      => [
        // 分配超级管理员角色需要的条件
        'assign_super_role_requires' => 'is_super_admin',
        // 是否允许超级管理员降权其他超级管理员
        'allow_demote_super_admin'   => false,
        // 是否允许超级管理员自我降权
        'allow_self_demote'          => false,
    ],

    // 认证安全配置
    'auth_secret_key' => env('auth.secret_key', 'DolphinPHP-Secret-' . md5(__DIR__)),
    // Session 超时时间（秒），默认 2 小时
    'session_timeout' => env('auth.session_timeout', 7200),
];
