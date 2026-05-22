<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2024 广东卓锐软件有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://www.dolphinphp.com
// +----------------------------------------------------------------------
// | 作者: 蔡伟明 <314013107@qq.com>
// +----------------------------------------------------------------------

use think\Request;
use think\facade\Route;
use think\facade\Config;

// 生成表单令牌
Route::rule('_token', function (Request $request) {
    return $request->buildToken(Config::get('csrf.token_name'), Config::get('csrf.token_type'));
});

// 上传驱动自定义方法
Route::rule('_uploader/:driver/[:type]/:action', '\app\admin\controller\Uploader@index');

// 渲染组件 HTTP 请求处理（如：UEditor、Select2、Switch 等组件）
// 路由格式：/_:type/:component/:action
// 示例：
//   /_form/ueditor/config        # 表单组件 UEditor 的配置接口
//   /_form/select2/search        # 表单组件 Select2 的搜索接口
//   /_table/switch/toggle        # 表格组件 Switch 的切换接口
Route::rule('_:type/:component/:action', '\app\admin\controller\RenderComponent@handle');
