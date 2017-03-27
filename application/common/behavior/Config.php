<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2017 河源市卓锐科技有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://dolphinphp.com
// +----------------------------------------------------------------------
// | 开源协议 ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------

namespace app\common\behavior;

use app\admin\model\Config as ConfigModel;
use app\admin\model\Module as ModuleModel;

/**
 * 初始化配置信息行为
 * 将系统配置信息合并到本地配置
 * @package app\common\behavior
 * @author CaiWeiMing <314013107@qq.com>
 */
class Config
{
    /**
     * 执行行为 run方法是Behavior唯一的接口
     * @access public
     * @param mixed $params  行为参数
     * @return void
     */
    public function run(&$params)
    {
        // 如果是安装操作，直接返回
        if(defined('BIND_MODULE') && BIND_MODULE === 'install') return;

        // 获取当前模块名称
        $module = '';
        $dispatch = request()->dispatch();
        if (isset($dispatch['module'])) {
            $module = $dispatch['module'][0];
        }

        // 获取入口目录
        $base_file = request()->baseFile();
        $base_dir  = preg_replace(['/index.php$/', '/admin.php$/'], ['', ''], $base_file);
        define('PUBLIC_PATH', $base_dir. 'public/');

        // 视图输出字符串内容替换
        $view_replace_str = [
            // 静态资源目录
            '__STATIC__'    => PUBLIC_PATH. 'static',
            // 文件上传目录
            '__UPLOADS__'   => PUBLIC_PATH. 'uploads',
            // JS插件目录
            '__LIBS__'      => PUBLIC_PATH. 'static/libs',
            // 后台CSS目录
            '__ADMIN_CSS__' => PUBLIC_PATH. 'static/admin/css',
            // 后台JS目录
            '__ADMIN_JS__'  => PUBLIC_PATH. 'static/admin/js',
            // 后台IMG目录
            '__ADMIN_IMG__' => PUBLIC_PATH. 'static/admin/img',
            // 前台CSS目录
            '__HOME_CSS__'  => PUBLIC_PATH. 'static/home/css',
            // 前台JS目录
            '__HOME_JS__'   => PUBLIC_PATH. 'static/home/js',
            // 前台IMG目录
            '__HOME_IMG__'  => PUBLIC_PATH. 'static/home/img'
        ];
        config('view_replace_str', $view_replace_str);

        // 如果定义了入口为admin，则修改默认的访问控制器层
        if(defined('ENTRANCE') && ENTRANCE == 'admin') {
            if ($dispatch['type'] == 'module' && $module == '') {
                header("Location: ".$base_dir.'admin.php/admin', true, 302);exit();
            }
            if ($module != '' && $module != 'admin' && $module != 'common' && $module != 'index' && $module != 'extra') {
                // 修改默认访问控制器层
                config('url_controller_layer', 'admin');
                // 修改视图模板路径
                config('template.view_path', APP_PATH. $module. '/view/admin/');
            }
            // 插件静态资源目录
            config('view_replace_str.__PLUGINS__', '/plugins');
        } else {
            if ($dispatch['type'] == 'module' && $module == 'admin') {
                header("Location: ".$base_dir.'admin.php/admin', true, 302);exit();
            }
            // 修改默认访问控制器层
            if ($module != '' && $module != 'index') {
                config('url_controller_layer', 'home');
            }
        }

        // 定义模块资源目录
        config('view_replace_str.__MODULE_CSS__', PUBLIC_PATH. 'static/'. $module .'/css');
        config('view_replace_str.__MODULE_JS__', PUBLIC_PATH. 'static/'. $module .'/js');
        config('view_replace_str.__MODULE_IMG__', PUBLIC_PATH. 'static/'. $module .'/img');
        config('view_replace_str.__MODULE_LIBS__', PUBLIC_PATH. 'static/'. $module .'/libs');
        // 静态文件目录
        config('public_static_path', PUBLIC_PATH. 'static/');

        // 读取系统配置
        $system_config = cache('system_config');
        if (!$system_config) {
            $ConfigModel   = new ConfigModel();
            $system_config = $ConfigModel->getConfig();
            // 所有模型配置
            $module_config = ModuleModel::where('config', 'neq', '')->column('config', 'name');
            foreach ($module_config as $module_name => $config) {
                $system_config[strtolower($module_name).'_config'] = json_decode($config, true);
            }
            // 非开发模式，缓存系统配置
            if ($system_config['develop_mode'] == 0) {
                cache('system_config', $system_config);
            }
        }

        // 设置配置信息
        config($system_config);
    }
}
