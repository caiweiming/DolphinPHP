<?php
// 全局中间件定义文件
return [
    \think\middleware\Throttle::class,
    // Session初始化
    \think\middleware\SessionInit::class
];
