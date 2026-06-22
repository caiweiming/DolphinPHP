<?php
declare(strict_types=1);

namespace app\cms\controller;

/**
 * CMS 生命周期演示后台兼容控制器
 *
 * 兼容旧入口 `cms/demo_admin/*`，实际逻辑统一复用
 * `app\cms\controller\admin\Demo`。
 */
class DemoAdmin extends \app\cms\controller\admin\Demo
{
}
