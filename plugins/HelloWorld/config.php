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

/**
 * 插件配置信息
 */
return [
    [
        'name'    => 'status',
        'title'   => '单选',
        'type'    => 'radio',
        'options' => [
            '1' => '开启',
            '0' => '关闭',
        ],
        'value' => '1',
    ],
    [
        'name'  => 'text',
        'title' => '单行文本',
        'type'  => 'text',
        'value' => 'x',
        'tip'   => '提示',
    ],
    [
        'name'  => 'textarea',
        'title' => '多行文本',
        'type'  => 'textarea',
        'value' => '',
        'tip'   => '提示',
    ],
    [
        'name'    => 'checkbox',
        'title'   => '多选',
        'type'    => 'checkbox',
        'options' => [
            '1' => '是',
            '0' => '否',
        ],
        'value' => '0',
        'tip'   => '提示',
    ],
    [
        'type'    => 'group',
        'options' => [
            '分组1' => [
                [
                    'name'    => 'status1',
                    'title'   => '单选',
                    'type'    => 'radio',
                    'options' => [
                        '1' => '开启',
                        '0' => '关闭',
                    ],
                    'value' => '1',
                ],
                [
                    'name'  => 'text1',
                    'title' => '单行文本',
                    'type'  => 'text',
                    'value' => 'x',
                    'tip'   => '提示',
                ],
                [
                    'name'  => 'textarea1',
                    'title' => '多行文本',
                    'type'  => 'textarea',
                    'value' => '',
                    'tip'   => '提示',
                ],
                [
                    'name'    => 'checkbox1',
                    'title'   => '多选',
                    'type'    => 'checkbox',
                    'options' => [
                        '1' => '是',
                        '0' => '否',
                    ],
                    'value' => '0',
                    'tip'   => '提示',
                ],
            ],
            '分组2' => [
                [
                    'name'  => 'textarea2',
                    'title' => '多行文本',
                    'type'  => 'textarea',
                    'value' => '',
                    'tip'   => '提示',
                ],
                [
                    'name'    => 'checkbox2',
                    'title'   => '多选',
                    'type'    => 'checkbox',
                    'options' => [
                        '1' => '是',
                        '0' => '否',
                    ],
                    'value' => '0',
                    'tip'   => '提示',
                ],
            ]
        ]
    ]
];
