<?php
// 中间件配置
return [
    // 别名或分组
    'alias'    => [
        // 多语言
        'lang'               => think\middleware\LoadLangPack::class,
        // Session初始化
        'session'            => think\middleware\SessionInit::class,
        // 全局请求缓存
        'cache'              => think\middleware\CheckRequestCache::class,
        // 表单令牌验证
        'csrf'               => think\middleware\FormTokenCheck::class,
        // 跨域请求支持
        'cross'              => think\middleware\AllowCrossDomain::class,
        // CSRF验证
        'dp_csrf'            => app\common\middleware\FormTokenCheck::class,
        // 后台注解登录验证
        'dp_annotation_auth' => app\common\middleware\AnnotationAuth::class,
        // 主题初始化
        'dp_theme'           => app\common\middleware\ThemeInit::class,
        // 轻量级日志中间件
        'dp_log'             => app\common\middleware\LogMiddleware::class,
        // 权限验证中间件
        'dp_permission'      => app\common\middleware\Permission::class,
    ],
    // 优先级设置，此数组中的中间件会按照数组中的顺序优先执行
    'priority' => [],
];
