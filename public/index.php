<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2019 广东卓锐软件有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://dolphinphp.com
// +----------------------------------------------------------------------

// [ 应用入口文件 ]
namespace think;

// [ PHP版本检查 ]
header("Content-type: text/html; charset=utf-8");
if (version_compare(PHP_VERSION, '5.6', '<')) {
    die('PHP版本过低，最少需要PHP5.6，请升级PHP版本！');
}

// 定义后台入口文件
define('ADMIN_FILE', 'admin.php');

// 加载基础文件
require __DIR__ . '/../thinkphp/base.php';

// 支持事先使用静态方法设置Request对象和Config对象

// 检查是否安装
if(!is_file('../data/install.lock')){
    define('BIND_MODULE', 'install');
    Container::get('app')->bind('install')->run()->send();
} else {
    // 执行应用并响应
    Container::get('app')->run()->send();
}
