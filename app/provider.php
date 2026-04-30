<?php
use app\common\ExceptionHandle;
use app\common\Request;

// 容器Provider定义文件
return [
    'think\Request'          => Request::class,
    'think\exception\Handle' => ExceptionHandle::class,
];
