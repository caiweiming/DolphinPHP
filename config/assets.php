<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2019 广东卓锐软件有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://dolphinphp.com
// +----------------------------------------------------------------------

// 资源路径配置
return [
    'core_js' => [ // 默认加载
        "__ADMIN_JS__/core/jquery.min.js",
        "__ADMIN_JS__/core/bootstrap.min.js",
        "__ADMIN_JS__/core/jquery.slimscroll.min.js",
        "__ADMIN_JS__/core/jquery.scrollLock.min.js",
        "__ADMIN_JS__/core/jquery.appear.min.js",
        "__ADMIN_JS__/core/jquery.countTo.min.js",
        "__ADMIN_JS__/core/jquery.placeholder.min.js",
        "__ADMIN_JS__/core/js.cookie.min.js",
        "__LIBS__/magnific-popup/magnific-popup.min.js",
        "__ADMIN_JS__/app.js",
        "__ADMIN_JS__/dolphin.js",
        "__ADMIN_JS__/builder/form.js",
        "__ADMIN_JS__/builder/aside.js",
        "__ADMIN_JS__/builder/table.js",
    ],
    'core_css' => [ // 默认加载
        "__LIBS__/magnific-popup/magnific-popup.min.css",
        "__ADMIN_CSS__/admin/css/bootstrap.min.css",
        "__ADMIN_CSS__/admin/css/oneui.css",
        "__ADMIN_CSS__/admin/css/dolphin.css",
    ],
    'libs_js' => [ // 默认加载
        "__LIBS__/bootstrap-notify/bootstrap-notify.min.js",
        "__LIBS__/sweetalert/sweetalert.min.js",
    ],
    'libs_css' => [ // 默认加载
        "__LIBS__/sweetalert/sweetalert.min.css",
    ],
    'datepicker_js' => [ // 日期选择
        "__LIBS__/bootstrap-datepicker/bootstrap-datepicker.min.js",
        "__LIBS__/bootstrap-datepicker/locales/bootstrap-datepicker.zh-CN.min.js",
    ],
    'datepicker_css' => [ // 日期选择
        "__LIBS__/bootstrap-datepicker/bootstrap-datepicker3.min.css",
    ],
    'datetimepicker_js' => [ // 日期时间选择
        "__LIBS__/bootstrap-datetimepicker/moment.min.js",
        "__LIBS__/bootstrap-datetimepicker/bootstrap-datetimepicker.min.js",
        "__LIBS__/bootstrap-datetimepicker/locale/zh-cn.js",
    ],
    'moment_js' => [
        "__LIBS__/bootstrap-datetimepicker/moment.min.js",
    ],
    'datetimepicker_css' => [ // 日期时间选择
        "__LIBS__/bootstrap-datetimepicker/bootstrap-datetimepicker.min.css"
    ],
    'webuploader_js' => [ // 文件或图片上传
        "__LIBS__/webuploader/webuploader.min.js",
    ],
    'webuploader_css' => [ // 文件或图片上传
        "__LIBS__/webuploader/webuploader.css",
    ],
    'select2_js' => [ // 下拉框
        "__LIBS__/select2/select2.full.min.js",
        "__LIBS__/select2/i18n/zh-CN.js",
    ],
    'select2_css' => [ // 下拉框
        "__LIBS__/select2/select2.min.css",
        "__LIBS__/select2/select2-bootstrap.min.css",
    ],
    'tags_js' => [ // 标签
        "__LIBS__/jquery-tags-input/jquery.tagsinput.min.js",
    ],
    'tags_css' => [ // 标签
        "__LIBS__/jquery-tags-input/jquery.tagsinput.min.css",
    ],
    'validate_js' => [ // 验证
        "__LIBS__/jquery-validation/jquery.validate.min.js",
    ],
    'editable_js' => [ // 快速编辑
        "__LIBS__/bootstrap3-editable/js/bootstrap-editable.js",
    ],
    'editable_css' => [ // 快速编辑
        "__LIBS__/bootstrap3-editable/css/bootstrap-editable.css",
    ],
    'colorpicker_js' => [ // 取色器
        "__LIBS__/bootstrap-colorpicker/bootstrap-colorpicker.min.js",
    ],
    'colorpicker_css' => [ // 取色器
        "__LIBS__/bootstrap-colorpicker/css/bootstrap-colorpicker.min.css",
    ],
    'editormd_js' => [ // markdown编辑器
        "__LIBS__/editormd/editormd.min.js",
    ],
    'jcrop_js' => [ // 图片裁剪
        "__LIBS__/jcrop/js/Jcrop.min.js",
    ],
    'jcrop_css' => [ // 图片裁剪
        "__LIBS__/jcrop/css/Jcrop.min.css",
    ],
    'masked_inputs_js' => [ // 格式文本
        "__LIBS__/masked-inputs/jquery.maskedinput.min.js",
    ],
    'rangeslider_js' => [ // 范围
        "__LIBS__/ion-rangeslider/js/ion.rangeSlider.min.js",
    ],
    'rangeslider_css' => [ // 范围
        "__LIBS__/ion-rangeslider/css/ion.rangeSlider.min.css",
        "__LIBS__/ion-rangeslider/css/ion.rangeSlider.skinHTML5.min.css",
    ],
    'nestable_js' => [ // 拖拽排序
        "__LIBS__/jquery-nestable/jquery.nestable.js",
    ],
    'nestable_css' => [ // 拖拽排序
        "__LIBS__/jquery-nestable/jquery.nestable.css",
    ],
    'wangeditor_js' => [ // wang编辑器
        "__LIBS__/wang-editor/js/wangEditor.min.js",
    ],
    'wangeditor_css' => [ // wang编辑器
        "__LIBS__/wang-editor/css/wangEditor.min.css",
    ],
    'summernote_js' => [ // summernote编辑器
        "__LIBS__/summernote/summernote.min.js",
        "__LIBS__/summernote/lang/summernote-zh-CN.js",
    ],
    'summernote_css' => [ // summernote编辑器
        "__LIBS__/summernote/summernote.min.css",
    ],
    'jqueryui_js' => [ // jqueryui
        "__LIBS__/jquery-ui/jquery-ui.min.js",
    ],
    'daterangepicker_js' => [ // 日期时间范围
        "__LIBS__/bootstrap-daterangepicker/daterangepicker.js",
    ],
    'daterangepicker_css' => [ // 日期时间范围
        "__LIBS__/bootstrap-daterangepicker/daterangepicker.css",
    ]
];
