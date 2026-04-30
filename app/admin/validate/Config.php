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
 * 动态配置验证器
 */
class Config extends Validate
{
    /**
     * 验证规则
     * @var array
     */
    protected $rule = [
        'id'                    => 'integer',
        'app|所属应用'           => 'require|max:60|alphaDash',
        'group|配置分组'         => 'require|max:60',
        'title|配置名称'         => 'require|max:100',
        'key|配置键名'           => 'require|max:120|regex:/^[a-z][a-z0-9_]*(\\.[a-z0-9_]+)*$/',
        'type|配置类型'          => 'require|in:text,textarea,number,switch,radio,checkbox,select,select2,tags,image,file,color,date,datetime,time',
        'remark|备注'            => 'max:500',
        'sort|排序'              => 'integer|egt:0',
        'status|状态'            => 'in:0,1',
        'is_system|系统内置配置' => 'in:0,1',
    ];

    /**
     * 自定义错误消息
     * @var array
     */
    protected $message = [
        'app.require'   => '所属应用不能为空',
        'app.max'       => '所属应用最多 60 个字符',
        'app.alphaDash' => '所属应用仅支持字母、数字、下划线和短横线',
        'group.require' => '配置分组不能为空',
        'group.max'     => '配置分组最多 60 个字符',
        'title.require' => '配置名称不能为空',
        'title.max'     => '配置名称最多 100 个字符',
        'key.require'   => '配置键名不能为空',
        'key.max'       => '配置键名最多 120 个字符',
        'key.regex'     => '配置键名仅支持小写字母、数字、下划线和点分命名，例如 site.name',
        'type.require'  => '请选择配置类型',
        'type.in'       => '配置类型不受支持',
        'remark.max'    => '备注最多 500 个字符',
        'sort.integer'  => '排序必须为整数',
        'sort.egt'      => '排序不能小于 0',
        'status.in'     => '状态值不正确',
        'is_system.in'  => '系统内置标记不正确',
    ];

    /**
     * 场景
     * @var array
     */
    protected $scene = [
        'create' => ['app', 'group', 'title', 'key', 'type', 'remark', 'sort', 'status', 'is_system'],
        'edit'   => ['id', 'app', 'group', 'title', 'key', 'type', 'remark', 'sort', 'status', 'is_system'],
    ];
}
