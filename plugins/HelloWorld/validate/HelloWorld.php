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

namespace plugins\HelloWorld\validate;

use think\Validate;

/**
 * 后台插件验证器
 * @package app\plugins\HelloWorld\validate
 */
class HelloWorld extends Validate
{
    //定义验证规则
    protected $rule = [
        'name|出处' => 'require',
        'said|名言' => 'require',
    ];
}
