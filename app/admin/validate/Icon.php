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
 * 在线图标库验证器
 */
class Icon extends Validate
{
    /**
     * 验证规则
     * @var array
     */
    protected $rule = [
        'id'          => 'integer',
        'lib_id'      => 'require|max:64|alphaDash|unique:admin_icon,lib_id^id',
        'label'       => 'require|max:100',
        'source_type' => 'require|in:iconfont',
        'source_url'  => 'require|max:500',
        'prefix'      => 'require|max:64|alphaDash',
        'base_class'  => 'max:64|alphaDash',
        'autoload'    => 'in:0,1',
        'status'      => 'in:0,1',
        'sort'        => 'integer|egt:0',
    ];

    /**
     * 验证提示
     * @var array
     */
    protected $message = [
        'lib_id.require'       => '图标库标识不能为空',
        'lib_id.max'           => '图标库标识最多64个字符',
        'lib_id.alphaDash'     => '图标库标识仅支持字母、数字、下划线和短横线',
        'lib_id.unique'        => '图标库标识已存在',
        'label.require'        => '图标库名称不能为空',
        'label.max'            => '图标库名称最多100个字符',
        'source_type.require'  => '图标库来源不能为空',
        'source_type.in'       => '图标库来源不支持',
        'source_url.require'   => '图标库 CSS 链接不能为空',
        'source_url.max'       => '图标库 CSS 链接最多500个字符',
        'prefix.require'       => '图标类前缀不能为空',
        'prefix.max'           => '图标类前缀最多64个字符',
        'prefix.alphaDash'     => '图标类前缀仅支持字母、数字、下划线和短横线',
        'base_class.max'       => '基础类名最多64个字符',
        'base_class.alphaDash' => '基础类名仅支持字母、数字、下划线和短横线',
        'autoload.in'          => '自动加载 CSS 配置不正确',
        'status.in'            => '状态值不正确',
        'sort.integer'         => '排序值必须为整数',
        'sort.egt'             => '排序值不能小于0',
    ];

    /**
     * 验证场景
     * @var array
     */
    protected $scene = [
        'create' => ['lib_id', 'label', 'source_type', 'source_url', 'prefix', 'base_class', 'autoload', 'status', 'sort'],
        'edit'   => ['id', 'lib_id', 'label', 'source_type', 'source_url', 'prefix', 'base_class', 'autoload', 'status', 'sort'],
    ];
}
