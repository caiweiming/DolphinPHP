<?php
// 全局中间件定义文件
return [
    // Session初始化
    'session',
    // 安装守卫
    app\common\middleware\InstallGuard::class,
];
