<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2019 广东卓锐软件有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://dolphinphp.com
// +----------------------------------------------------------------------

namespace plugins\HelloWorld;

use app\common\controller\Plugin;

/**
 * 演示插件
 * @package plugin\HelloWorld
 * @author 蔡伟明 <314013107@qq.com>
 */
class HelloWorld extends Plugin
{
    /**
     * @var array 插件信息
     */
    public $info = [
        // 插件名[必填]
        'name'        => 'HelloWorld',
        // 插件标题[必填]
        'title'       => '你好，世界',
        // 插件唯一标识[必填],格式：插件名.开发者标识.plugin
        'identifier'  => 'helloworld.ming.plugin',
        // 插件图标[选填]
        'icon'        => 'fa fa-fw fa-globe',
        // 插件描述[选填]
        'description' => '这是一个演示插件，会在每个页面生成一个提示“Hello World”。您可以查看源码，里面包含了绝大部分插件所用到的方法，以及能做的事情。',
        // 插件作者[必填]
        'author'      => '蔡伟明',
        // 作者主页[选填]
        'author_url'  => 'http://www.dolphinphp.com',
        // 插件版本[必填],格式采用三段式：主版本号.次版本号.修订版本号
        'version'     => '1.0.0',
        // 是否有后台管理功能
        'admin'       => '1',
    ];

    /**
     * @var array 管理界面字段信息
     */
    public $admin = [
        'title'        => '后台列表', // 后台管理标题
        'table_name'   => 'plugin_hello', // 数据库表名，如果没有用到数据库，则留空
        'order'        => 'said,name', // 需要排序功能的字段，多个字段用逗号隔开
        'filter'       => '', // 需要筛选功能的字段，多个字段用逗号隔开
        'search_title' => '', // 搜索框提示文字,一般不用填写
        'search_field' => [ // 需要搜索的字段，如果需要搜索，则必填，否则不填
            'said' => '名言',
            'name' => '出处'
        ],
        'search_url' => '', // 搜索框url链接,如：'user/index'，一般不用填写

        // 后台列表字段
        'columns' => [
            ['id', 'ID'],
            ['said', '名言'],
            ['name', '出处'],
            ['status', '状态', 'switch'],
            ['right_button', '操作', 'btn']
        ],

        // 右侧按钮
        'right_buttons' => [
            'edit',          // 使用系统自带的编辑按钮
            'enable',       // 使用系统自带的启用按钮
            'disable',      // 使用系统自带的禁用按钮
            'delete',        // 使用系统自带的删除按钮

            // 自定义按钮，可定义多个
            'customs' => [
                [
                    'title'  => '自定义按钮1,新窗口打开',
                    'icon'  => 'fa fa-list',
                    'href'   => [
                        'url' => 'HelloWorld/Admin/testTable',
                    ],
                    'target' => '_blank',
                ],
                // 自定义按钮并带有参数
                [
                    'title' => '自定义按钮2,自定义参数',
                    'icon'  => 'fa fa-user',
                    'href'  => [
                        'url'   => 'HelloWorld/Admin/testForm',
                        'params' => [
                            'id'    => '__id__',
                            'table' => '__table__',
                            'name'  => 'molly',
                            'age'   => 12
                        ]
                    ],
                ],
                [
                    'title' => '自定义页面',
                    'icon'  => 'fa fa-file',
                    'href'  => [
                        'url' => 'HelloWorld/Admin/testPage'
                    ],
                ],
            ],
        ],

        // 顶部栏按钮
        'top_buttons' => [
            'add',    // 使用系统自带的添加按钮
            'enable', // 使用系统自带的启用按钮
            'disable',// 使用系统自带的禁用按钮
            'delete', // 使用系统自带的删除按钮

            // 自定义按钮，可定义多个
            'customs' => [
                [
                    'title'  => '<i class="fa fa-list"></i> 自定义按钮1',
                    'href'   => [
                        'url' => 'HelloWorld/Admin/testTable',
                    ],
                    'target' => '_blank',
                ],
                // 自定义按钮并带有参数
                [
                    'title' => '<i class="fa fa-user"></i> 自定义按钮2',
                    'href'  => [
                        'url'   => 'HelloWorld/Admin/testForm',
                        'params' => [
                            'name' => 'molly',
                            'age'  => 12
                        ]
                    ],
                ],
                [
                    'title' => '<i class="fa fa-file"></i> 自定义页面',
                    'href'  => [
                        'url' => 'HelloWorld/Admin/testPage'
                    ],
                ],
            ],
        ],
    ];

    /**
     * @var array 新增或编辑的字段
     */
    public $fields = [
        [
            'name'  => 'name',
            'title' => '出处',
            'type'  => 'text',
            'value' => '',
        ],
        [
            'name'  => 'said',
            'title' => '名言',
            'type'  => 'text',
            'value' => '',
            'tip'   => '提示',
        ]
    ];

    /**
     * @var string 原数据库表前缀
     * 用于在导入插件sql时，将原有的表前缀转换成系统的表前缀
     * 一般插件自带sql文件时才需要配置
     */
    public $database_prefix = 'dolphin_';

    /**
     * @var array 插件钩子
     */
    public $hooks = [
        // 钩子名称 => 钩子说明
        // 如果是系统钩子，则钩子说明不用填写
        'page_tips',
        'my_hook' => '我的钩子',
    ];

    /**
     * page_tips钩子方法
     * @param $params
     * @author 蔡伟明 <314013107@qq.com>
     */
    public function pageTips($params)
    {
        echo '<div class="alert alert-success alert-dismissable">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
            <p>Hello World</p>
        </div>';
    }

    /**
     * 安装方法必须实现
     * 一般只需返回true即可
     * 如果安装前有需要实现一些业务，可在此方法实现
     * @author 蔡伟明 <314013107@qq.com>
     * @return bool
     */
    public function install(){
        return true;
    }

    /**
     * 卸载方法必须实现
     * 一般只需返回true即可
     * 如果安装前有需要实现一些业务，可在此方法实现
     * @author 蔡伟明 <314013107@qq.com>
     * @return bool
     */
    public function uninstall(){
        return true;
    }
}
