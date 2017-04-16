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

namespace app\cms\validate;

use think\Validate;

/**
 * 导航验证器
 * @package app\cms\validate
 * @author 蔡伟明 <314013107@qq.com>
 */
class Nav extends Validate
{
    // 定义验证规则
    protected $rule = [
        'tag|菜单标识' => 'require|length:1,30|unique:cms_nav',
        'title|菜单标题' => 'require|length:1,30|unique:cms_nav'
    ];

    // 定义验证场景
    protected $scene = [
        'tag' => ['tag'],
        'title' => ['title']
    ];
}
