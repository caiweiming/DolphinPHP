<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2024 广东卓锐软件有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://www.dolphinphp.com
// +----------------------------------------------------------------------

// 表格渲染配置文件

return [
    // 静态页面信息
    'view'     => [
        'layout' => 'layout.html'
    ],
    // 表格id变量参数名
    'var'      => 'tid',
    // 分页参数
    'page'     => [
        'limits' => [10, 20, 50, 100, 500],
        'layout' => ['count', 'prev', 'page', 'next', 'skip', 'limit'],
        'prev'   => '<i class="layui-icon"></i>',
        'next'   => '<i class="layui-icon"></i>',
        'theme'  => '#066FD1',
        'curr'   => 1,
        'limit'  => 20,
    ],
    // 搜索配置
    'search'   => [
        'param'            => '_s',
        'class'            => 'layui-form layui-row layui-col-space16 dp-table-search-form',
        'field_col_class'  => 'layui-col-md2',
        'button_col_class' => 'layui-col-xs12',
        'fields'           => [],
        'buttons'          => [
            'submit' => [
                'show'  => true,
                'text'  => '搜索',
                'class' => 'layui-btn layui-btn-primary',
            ],
            'reset'  => [
                'show'  => true,
                'text'  => '重置',
                'class' => 'layui-btn',
            ],
        ],
    ],
    // 表格默认配置
    'options'  => [
        'page'  => true,
        'where' => [],
        'url'   => ''
    ],
    // 安全策略
    'security' => [
        // 是否强制校验 CRUD token（_t）
        'require_token'                => true,
        // 需要 token 校验的快捷操作
        'token_actions'                => ['quickEdit', 'enable', 'disable', 'delete', 'sort', 'refresh'],
        // quickEdit 是否要求显式配置字段白名单
        'quick_edit_require_whitelist' => true,
        // callback 列允许调用的字符串函数白名单
        'callback_allowed_functions'   => [],
        // callback 列允许调用的函数模式（支持 fnmatch 和正则）
        // fnmatch 示例: dp_*、cms_*
        // 正则示例: /^dp_[a-zA-Z0-9_]+$/
        'callback_allowed_patterns'    => [
            'dp_*',
        ],
    ],
    // 弹出层参数
    'dialog'   => [
        'type'       => 2,
        'area'       => ['80%', '90%'],
        'shadeClose' => true,
        'isOutAnim'  => false,
        'scrollbar'  => false,
        'anim'       => -1
    ],
    // 工具栏参数
    'toolbar'  => [
        // 左侧工具按钮
        'left' => [
            [
                'title' => '新增',
                'name'  => 'add',
                'event' => 'add',
                'icon'  => 'layui-icon layui-icon-add-1',
                'url'   => 'create',
                'pop'   => ['title' => '新增']
            ],
            [
                'title'   => '删除',
                'name'    => 'delete',
                'event'   => 'delete',
                'icon'    => 'layui-icon layui-icon-delete',
                'url'     => 'delete',
                'ajax'    => 'post',
                'field'   => 'id',
                'confirm' => '确定要删除吗？',
            ],
            [
                'title' => '导入',
                'name'  => 'import',
                'event' => 'import',
                'icon'  => 'ti ti-file-import',
                'url'   => 'import',
            ],
            [
                'title' => '导出',
                'name'  => 'export',
                'event' => 'export',
                'icon'  => 'ti ti-file-export',
                'url'   => 'export',
            ],
            [
                'title'   => '启用',
                'name'    => 'enable',
                'event'   => 'enable',
                'icon'    => 'ti ti-check',
                'url'     => 'enable',
                'ajax'    => 'post',
                'field'   => 'id',
                'confirm' => '确定要启用吗？',
            ],
            [
                'title'   => '禁用',
                'name'    => 'disable',
                'event'   => 'disable',
                'icon'    => 'ti ti-ban',
                'url'     => 'disable',
                'ajax'    => 'post',
                'field'   => 'id',
                'confirm' => '确定要禁用吗？',
            ],
            [
                'title' => '展开全部',
                'name'  => 'expand',
                'event' => 'expand',
                'icon'  => 'layui-icon layui-icon-triangle-d',
            ],
            [
                'title' => '收起全部',
                'name'  => 'collapse',
                'event' => 'collapse',
                'icon'  => 'layui-icon layui-icon-triangle-r',
            ],
            [
                'title' => '刷新',
                'name'  => 'reload',
                'event' => 'reload',
                'icon'  => 'ti ti-refresh',
                'auth'  => ''
            ],
        ]
    ],
    // 操作按钮
    'actions'  => [
        'edit'    => [
            'title' => '编辑',
            'event' => 'edit',
            'pop'   => true,
            'class' => 'layui-btn layui-btn-xs layui-btn-default'
        ],
        'delete'  => [
            'title'   => '删除',
            'event'   => 'delete',
            'class'   => 'layui-btn layui-btn-xs layui-btn-danger',
            'confirm' => '确定要删除吗？',
            'ajax'    => 'get'
        ],
        'enable'  => [
            'title'   => '启用',
            'event'   => 'enable',
            'class'   => 'layui-btn layui-btn-xs layui-bg-blue',
            'confirm' => '确定要启用吗？',
            'ajax'    => 'get'
        ],
        'disable' => [
            'title'   => '禁用',
            'event'   => 'disable',
            'class'   => 'layui-btn layui-btn-xs layui-bg-orange',
            'confirm' => '确定要禁用吗？',
            'ajax'    => 'get'
        ],
        'import'  => [
            'title' => '导入',
            'event' => 'import',
            'class' => 'layui-btn layui-btn-xs layui-bg-blue'
        ],
        'export'  => [
            'title' => '导出',
            'event' => 'export',
            'class' => 'layui-btn layui-btn-xs layui-bg-orange'
        ],
        'more'    => [
            'title' => '更多',
            'event' => 'more',
            'class' => 'layui-btn layui-btn-xs layui-btn-default'
        ],
        '-'       => [
            'type' => '-',
        ]
    ],
    // 内置表格项
    'types'    => [
        'actions'       => app\common\render\table\actions\Item::class,
        'callback'      => app\common\render\table\callback\Item::class,
        'color'         => app\common\render\table\color\Item::class,
        'date'          => app\common\render\table\datetime\Item::class,
        'date.edit'     => app\common\render\table\datetime\Item::class,
        'datetime'      => app\common\render\table\datetime\Item::class,
        'datetime.edit' => app\common\render\table\datetime\Item::class,
        'preview'       => app\common\render\table\preview\Item::class,
        'icon'          => app\common\render\table\icon\Item::class,
        'image'         => app\common\render\table\image\Item::class,
        'pretty_time'   => app\common\render\table\datetime\Item::class,
        'select'        => app\common\render\table\select\Item::class,
        'status'        => app\common\render\table\status\Item::class,
        'switch'        => app\common\render\table\switch\Item::class,
        'time'          => app\common\render\table\datetime\Item::class,
        'time.edit'     => app\common\render\table\datetime\Item::class,
        'url'           => app\common\render\table\url\Item::class,
        'yes_no'        => app\common\render\table\yes_no\Item::class,
    ]
];
