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
 * 部门验证器
 * @package app\admin\validate
 */
class Department extends Validate
{
    /**
     * 验证规则
     * @var string[]
     */
    protected $rule = [
        'name|部门名称'       => 'require|max:100',
        'code|部门编码'       => 'require|max:50|unique:admin_department',
        'parent_id|上级部门'  => 'integer|egt:0',
        'leader_id|部门负责人' => 'integer|egt:0',
        'sort|排序'           => 'integer|egt:0',
        'status|状态'         => 'in:0,1',
        'remark|备注'         => 'max:500',
    ];

    /**
     * 自定义验证规则消息
     * @var array
     */
    protected $message = [
        'name.require'       => '部门名称不能为空',
        'name.max'           => '部门名称最多100个字符',
        'code.max'           => '部门编码最多50个字符',
        'code.unique'        => '部门编码已存在',
        'parent_id.integer'  => '上级部门ID必须是整数',
        'parent_id.egt'      => '上级部门ID必须大于等于0',
        'leader_id.integer'  => '部门负责人ID必须是整数',
        'leader_id.egt'      => '部门负责人ID必须大于等于0',
        'sort.integer'       => '排序必须是整数',
        'sort.egt'           => '排序必须大于等于0',
        'status.in'          => '状态值必须是0或1',
        'remark.max'         => '备注最多500个字符',
    ];
}
