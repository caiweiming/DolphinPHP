<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2024 广东卓锐软件有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://www.dolphinphp.com
// +----------------------------------------------------------------------

// 表单渲染配置文件

return [
    // 静态页面信息
    'view'              => [
        'layout'          => 'layout.html',
        'content'         => 'content.html',
        'control_sidebar' => 'control-sidebar.html',
        'nav_bar'         => 'nav-bar.html',
        'footer'          => 'footer.html',
        'sidebar'         => 'sidebar.html',
    ],
    // 内置表单项类型
    'types'             => [
        'amap'           => \app\common\render\form\items\amap\Item::class,
        'bmap'           => \app\common\render\form\items\bmap\Item::class,
        'button'         => \app\common\render\form\items\button\Item::class,
        'button_group'   => \app\common\render\form\items\button_group\Item::class,
        'color'          => \app\common\render\form\items\color\Item::class,
        'color_select'   => \app\common\render\form\items\color_select\Item::class,
        'checkbox'       => \app\common\render\form\items\checkbox\Item::class,
        'checkbox_group' => \app\common\render\form\items\checkbox_group\Item::class,
        'cropper'        => \app\common\render\form\items\cropper\Item::class,
        'date'           => \app\common\render\form\items\date\Item::class,
        'date_range'     => \app\common\render\form\items\date_range\Item::class,
        'datetime'       => \app\common\render\form\items\datetime\Item::class,
        'datetime_range' => \app\common\render\form\items\datetime_range\Item::class,
        'file'           => \app\common\render\form\items\file\Item::class,
        'hidden'         => \app\common\render\form\items\hidden\Item::class,
        'html'           => \app\common\render\form\items\html\Item::class,
        'image_select'   => \app\common\render\form\items\image_select\Item::class,
        'image'          => \app\common\render\form\items\image\Item::class,
        'mask'           => \app\common\render\form\items\mask\Item::class,
        'number'         => \app\common\render\form\items\number\Item::class,
        'password'       => \app\common\render\form\items\password\Item::class,
        'qmap'           => \app\common\render\form\items\qmap\Item::class,
        'radio'          => \app\common\render\form\items\radio\Item::class,
        'radio_group'    => \app\common\render\form\items\radio_group\Item::class,
        'icon'           => \app\common\render\form\items\icon\Item::class,
        'linkage'        => \app\common\render\form\items\linkage\Item::class,
        'linkages'       => \app\common\render\form\items\linkages\Item::class,
        'static'         => \app\common\render\form\items\static\Item::class,
        'select'         => \app\common\render\form\items\select\Item::class,
        'select2'        => \app\common\render\form\items\select2\Item::class,
        'select_group'   => \app\common\render\form\items\select_group\Item::class,
        'switch'         => \app\common\render\form\items\switch\Item::class,
        'table'          => \app\common\render\form\items\table\Item::class,
        'tags'           => \app\common\render\form\items\tags\Item::class,
        'tabs'           => \app\common\render\form\items\tabs\Item::class,
        'text'           => \app\common\render\form\items\text\Item::class,
        'textarea'       => \app\common\render\form\items\textarea\Item::class,
        'time'           => \app\common\render\form\items\time\Item::class,
        'ueditor'        => \app\common\render\form\items\ueditor\Item::class,
        'vditor'         => \app\common\render\form\items\vditor\Item::class,
    ],
    // 表单项模板
    'item_template'     => [],
    // 文本域尺寸前缀
    'input_size_prefix' => 'form-control-',
    // 列的默认样式类名
    'col_class'         => 'col',
    // 表单吸附
    'sticky'            => [
        'top'    => false,
        'bottom' => true
    ],
    // 换行代码
    'newline'           => '<div class="w-100 p-0 m-0"></div>',
    // 底部按钮
    'button'            => [
        // 提交按钮
        'submit' => [
            // 标题
            'text'  => '<i class="dp-icon fa-regular fa-floppy-disk"></i> 提 交',
            // 位置
            'pos'   => 'right',
            // 颜色
            'color' => 'primary',
            // 样式: btn-square,btn-pill
            'style' => '',
            // 类型
            'type'  => 'submit'
        ],
        // 返回按钮
        'back'   => [
            // 标题
            'text'  => '<i class="dp-icon fa-solid fa-rotate-left"></i> 返 回',
            // 位置
            'pos'   => 'left',
            // 颜色
            'color' => 'ghost-info',
            // 样式: btn-square,btn-pill
            'style' => '',
            // 类型
            'type'  => 'button'
        ]
    ],
];
