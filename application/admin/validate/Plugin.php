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
 * 插件验证器
 * @package app\admin\validate
 * @author 蔡伟明 <314013107@qq.com>
 */
class Plugin extends Validate
{
    //定义验证规则
    protected $rule = [
        'name|插件名称'  => 'require|unique:admin_plugin',
        'title|插件标题' => 'require',
    ];
}
