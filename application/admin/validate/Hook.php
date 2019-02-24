<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2019 广东卓锐软件有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://dolphinphp.com
// +----------------------------------------------------------------------

namespace app\admin\validate;

use think\Validate;

/**
 * 钩子验证器
 * @package app\admin\validate
 * @author 蔡伟明 <314013107@qq.com>
 */
class Hook extends Validate
{
    //定义验证规则
    protected $rule = [
        'name|钩子名称'  => 'require|regex:^[a-zA-Z]\w{0,39}$|unique:admin_hook'
    ];

    //定义验证提示
    protected $message = [
        'name.regex' => '钩子名称由字母和下划线组成',
    ];
}
