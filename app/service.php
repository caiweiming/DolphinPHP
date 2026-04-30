<?php

use app\common\AppService;
use app\common\provider\PermissionServiceProvider;
use app\common\provider\PluginServiceProvider;
use app\common\provider\UploadServiceProvider;
use app\common\provider\UserContextService;

// 系统服务定义文件
// 服务在完成全局初始化之后执行
return [
    AppService::class,
    PermissionServiceProvider::class,
    PluginServiceProvider::class,
    UploadServiceProvider::class,
    UserContextService::class,
];
