<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2026 广东卓锐软件有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://www.dolphinphp.com
// +----------------------------------------------------------------------
// | 作者: 蔡伟明 <314013107@qq.com>
// +----------------------------------------------------------------------
declare (strict_types = 1);

namespace app\common\middleware;

use Closure;
use app\common\Request;
use think\facade\Config;
use think\facade\View;

/**
 * 主题中间件
 * @package app\common\middleware
 */
class ThemeInit
{
    /**
     * Request
     * @var Request
     */
    protected Request $request;

    /**
     * handle
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $this->request = $request;

        // 解析模板内容替换
        $this->parseTplReplace();

        return $next($request);
    }

    /**
     * 解析模板内容替换
     * @return void
     */
    private function parseTplReplace(): void
    {
        // 应用名
        $app = app('http')->getName();
        // URL访问根目录
        $base_dir = dp_base_dir();
        // 获取模板内容替换
        $tpl_replace_string = Config::get('view.tpl_replace_string');
        // 添加应用模板内容替换
        $tpl_replace_string['__APP__']      = $base_dir . 'apps/' . $app;
        $tpl_replace_string['__APP_JS__']   = $base_dir . 'apps/' . $app . '/js';
        $tpl_replace_string['__APP_CSS__']  = $base_dir . 'apps/' . $app . '/css';
        $tpl_replace_string['__APP_IMG__']  = $base_dir . 'apps/' . $app . '/img';
        $tpl_replace_string['__APP_LIBS__'] = $base_dir . 'apps/' . $app . '/libs';

        // 主题布局文件
        View::assign('dp_theme_layout', dp_theme_layout());
        // 页面布局文件
        View::assign('dp_page_layout', dp_page_layout());
        // 设置模板内容替换
        Config::set(['tpl_replace_string' => $tpl_replace_string], 'view');
    }
}