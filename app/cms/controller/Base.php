<?php
declare(strict_types=1);

namespace app\cms\controller;

use app\common\trait\Jump;
use app\common\trait\ValidatesRequest;
use think\App;
use think\facade\View;
use think\Request;

/**
 * CMS 前台控制器基类
 */
abstract class Base
{
    use Jump;
    use ValidatesRequest;

    /**
     * 应用实例
     * @var App
     */
    protected App $app;

    /**
     * Request 实例
     * @var Request
     */
    protected Request $request;

    /**
     * 是否批量验证
     * @var bool
     */
    protected bool $batchValidate = false;

    /**
     * 构造方法
     * @param App $app
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->request = $this->app->request;

        $this->initialize();
    }

    /**
     * 初始化
     * @return void
     */
    protected function initialize(): void
    {
    }

    /**
     * 模板变量赋值
     * @param string|array $name
     * @param mixed|null $value
     * @return $this
     */
    public function assign(string|array $name, mixed $value = null): static
    {
        View::assign($name, $value);
        return $this;
    }

    /**
     * 渲染模板
     * @param string $template
     * @param array<string, mixed> $vars
     * @return string
     */
    public function fetch(string $template = '', array $vars = []): string
    {
        return View::fetch($template, $vars);
    }
}
