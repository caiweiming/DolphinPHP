<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2024 广东卓锐软件有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://www.dolphinphp.com
// +----------------------------------------------------------------------

return [
    // 操作成功跳转页面模板
    'dispatch_success_tmpl' => base_path() . 'common/tpl/jump/dispatch_jump.tpl',
    // 成功跳转页停留时间(秒)
    'default_success_wait'  => 2,
    // 成功跳转 code 值
    'default_success_code'  => 1,

    // 操作失败跳转页面模板
    'dispatch_error_tmpl'   => base_path() . 'common/tpl/jump/dispatch_jump.tpl',
    // 错误跳转页停留时间(秒)
    'default_error_wait'    => 3,
    // 错误跳转 code 值
    'default_error_code'    => 0,

    // 默认输出类型
    'default_return_type'   => 'html',
    // 默认 AJAX 请求返回数据格式，可用：Json,Jsonp,Xml
    'default_ajax_return'   => 'json',
];
