<?php
declare(strict_types=1);

namespace app\cms\validate;

use think\Validate;

/**
 * CMS 分类后台验证器
 */
class AdminCategory extends Validate
{
    /**
     * 验证规则
     * @var array
     */
    protected $rule = [
        'name'   => 'require|max:120',
        'slug'   => 'require|max:120',
        'status' => 'in:0,1',
        'sort'   => 'integer',
    ];

    /**
     * 验证提示
     * @var array
     */
    protected $message = [
        'name.require' => '分类名称不能为空',
        'name.max'     => '分类名称最多 120 个字符',
        'slug.require' => '分类别名不能为空',
        'slug.max'     => '分类别名最多 120 个字符',
        'status.in'    => '分类状态不合法',
        'sort.integer' => '分类排序必须为整数',
    ];
}
