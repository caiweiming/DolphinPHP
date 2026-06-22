<?php
declare(strict_types=1);

namespace app\cms\validate;

use think\Validate;

/**
 * CMS 文章后台验证器
 */
class AdminArticle extends Validate
{
    /**
     * 验证规则
     * @var array
     */
    protected $rule = [
        'category_id' => 'require|integer|gt:0',
        'title'       => 'require|max:150',
        'slug'        => 'require|max:150',
        'status'      => 'in:0,1',
        'sort'        => 'integer',
    ];

    /**
     * 验证提示
     * @var array
     */
    protected $message = [
        'category_id.require' => '文章分类不能为空',
        'category_id.integer' => '文章分类不合法',
        'category_id.gt'      => '文章分类不合法',
        'title.require'       => '文章标题不能为空',
        'title.max'           => '文章标题最多 150 个字符',
        'slug.require'        => '文章别名不能为空',
        'slug.max'            => '文章别名最多 150 个字符',
        'status.in'           => '文章状态不合法',
        'sort.integer'        => '文章排序必须为整数',
    ];
}
