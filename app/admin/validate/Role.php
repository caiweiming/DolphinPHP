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
declare (strict_types=1);

namespace app\admin\validate;

use think\Validate;

/**
 * 角色验证器
 * @package app\admin\validate
 */
class Role extends Validate
{
    /**
     * 验证规则
     * @var string[]
     */
    protected $rule = [
        'name|角色名称'       => 'require|max:100|unique:admin_role',
        'code|角色标识'       => 'require|max:50|unique:admin_role',
        'data_scope|数据范围' => 'require|in:1,2,3,4,5',
        'sort|排序'           => 'integer',
        'status|状态'         => 'in:0,1',
    ];
}
