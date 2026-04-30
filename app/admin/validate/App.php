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
declare(strict_types=1);

namespace app\admin\validate;

use think\Validate;

/**
 * 应用注册表验证器
 */
class App extends Validate
{
    /**
     * 验证规则
     * @var array
     */
    protected $rule = [
        'id'       => 'require|integer|egt:1',
        'icon'     => 'max:120|regex:/^[A-Za-z0-9 _-]*$/',
        'sort'     => 'integer|egt:0',
    ];

    /**
     * 自定义错误信息
     * @var array
     */
    protected $message = [
        'id.require'   => '应用ID不能为空',
        'id.integer'   => '应用ID必须为整数',
        'id.egt'       => '应用ID必须大于0',
        'icon.max'     => '图标类名最多 120 个字符',
        'icon.regex'   => '图标类名格式不正确，只支持字母、数字、空格、下划线和短横线',
        'sort.integer' => '排序必须为整数',
        'sort.egt'     => '排序不能小于 0',
    ];

    /**
     * 验证场景
     * @var array
     */
    protected $scene = [
        'edit' => ['id', 'icon', 'sort'],
    ];
}
