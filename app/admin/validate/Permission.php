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
 * 权限验证器
 * @package app\admin\validate
 */
class Permission extends Validate
{
    /**
     * 验证规则
     * @var string[]
     */
    protected $rule = [
        'name|权限名称'       => 'require|max:100',
        'code|权限标识'       => 'require|max:100|unique:admin_permission',
        'type|权限类型'       => 'require|in:menu,button,api',
        'parent_id|上级权限'  => 'integer|egt:0',
        'route|路由/接口'     => 'max:200',
        'icon|图标'           => 'max:100',
        'sort|排序'           => 'integer|egt:0',
        'status|状态'         => 'in:0,1',
        'remark|备注'         => 'max:500',
    ];

    /**
     * 自定义验证规则消息
     * @var array
     */
    protected $message = [
        'name.require'       => '权限名称不能为空',
        'name.max'           => '权限名称最多100个字符',
        'code.require'       => '权限标识不能为空',
        'code.max'           => '权限标识最多100个字符',
        'code.unique'        => '权限标识已存在',
        'type.require'       => '权限类型不能为空',
        'type.in'            => '权限类型必须是menu、button或api',
        'parent_id.integer'  => '上级权限ID必须是整数',
        'parent_id.egt'      => '上级权限ID必须大于等于0',
        'route.max'          => '路由/接口最多200个字符',
        'icon.max'           => '图标最多100个字符',
        'sort.integer'       => '排序必须是整数',
        'sort.egt'           => '排序必须大于等于0',
        'status.in'          => '状态值必须是0或1',
        'remark.max'         => '备注最多500个字符',
    ];
}
