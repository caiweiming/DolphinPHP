<?php
namespace app\common;

/**
 * 应用请求对象类
 * @property mixed $csrf
 * @property mixed $annotationAuth
 */
class Request extends \think\Request
{
    /**
     * CSRF配置
     * @var mixed
     */
    protected mixed $csrf = null;

    /**
     * 后台注解登录验证
     * @var mixed
     */
    protected mixed $annotationAuth = null;
}
