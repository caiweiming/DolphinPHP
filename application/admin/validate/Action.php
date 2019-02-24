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
 * 行为验证器
 * @package app\admin\validate
 * @author 蔡伟明 <314013107@qq.com>
 */
class Action extends Validate
{
    //定义验证规则
    protected $rule = [
        'module|所属模块' => 'require',
        'name|行为标识'   => 'require|regex:^[a-zA-Z]\w{0,39}$|unique:admin_action,name^module',
        'title|行为名称'  => 'require|length:1,80',
        'remark|行为描述' => 'require|length:1,128'
    ];

    //定义验证提示
    protected $message = [
        'name.regex' => '行为标识由字母和下划线组成',
    ];
}
