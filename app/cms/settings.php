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

/**
 * CMS 应用声明型设置
 */
return [
    [
        'key'   => 'content',
        'title' => '内容设置',
        'sort'  => 10,
        'items' => [
            [
                'key'     => 'content.list_rows',
                'title'   => '后台默认分页数',
                'type'    => 'number',
                'default' => 20,
                'rules'   => 'require|integer|between:1,200',
                'remark'  => 'CMS 列表页面的默认分页条数。',
                'sort'    => 10,
            ],
            [
                'key'     => 'content.default_status',
                'title'   => '内容默认状态',
                'type'    => 'radio',
                'default' => 1,
                'options' => [
                    'options' => [
                        1 => '已发布',
                        0 => '草稿',
                    ],
                    'inline' => true,
                ],
                'rules'   => 'require|in:0,1',
                'remark'  => '新建内容时的默认发布状态。',
                'sort'    => 20,
            ],
            [
                'key'     => 'content.front_list_rows',
                'title'   => '前台首页显示条数',
                'type'    => 'number',
                'default' => 6,
                'rules'   => 'require|integer|between:1,50',
                'remark'  => 'CMS 前台首页默认显示的文章条数。',
                'sort'    => 30,
            ],
        ],
    ],
];
