<?php
/**
 * Groups configuration for default Minify implementation
 * @package Minify
 */

/**
 * You may wish to use the Minify URI Builder app to suggest
 * changes. http://yourdomain/min/builder/
 *
 * See http://code.google.com/p/minify/wiki/CustomSource for other ideas
 **/

$static_path = dirname(__DIR__). "/static/";
$core_path   = $static_path. "admin/js/core/";
$libs_path   = $static_path. "libs/";

return [
    'core_js' => [ // 默认加载
        $core_path. "jquery.min.js",
        $core_path. "bootstrap.min.js",
        $core_path. "jquery.slimscroll.min.js",
        $core_path. "jquery.scrollLock.min.js",
        $core_path. "jquery.appear.min.js",
        $core_path. "jquery.countTo.min.js",
        $core_path. "jquery.placeholder.min.js",
        $core_path. "js.cookie.min.js",
        $libs_path. "magnific-popup/magnific-popup.min.js",
        $static_path. "admin/js/app.js",
        $static_path. "admin/js/dolphin.js",
        $static_path. "admin/js/builder/form.js",
        $static_path. "admin/js/builder/aside.js",
        $static_path. "admin/js/builder/table.js",
        $libs_path. "viewer/viewer.min.js"
    ],
    'core_css' => [ // 默认加载
        $libs_path. "magnific-popup/magnific-popup.min.css",
        $static_path. "admin/css/bootstrap.min.css",
        $static_path. "admin/css/oneui.css",
        $static_path. "admin/css/dolphin.css",
        $libs_path. "viewer/viewer.min.css"
    ],
    'libs_js' => [ // 默认加载
        $libs_path. "bootstrap-notify/bootstrap-notify.min.js",
        $libs_path. "sweetalert/sweetalert.min.js",
    ],
    'libs_css' => [ // 默认加载
        $libs_path. "sweetalert/sweetalert.min.css",
    ],
    'datepicker_js' => [ // 日期选择
        $libs_path. "bootstrap-datepicker/bootstrap-datepicker.min.js",
        $libs_path. "bootstrap-datepicker/locales/bootstrap-datepicker.zh-CN.min.js",
    ],
    'datepicker_css' => [ // 日期选择
        $libs_path. "bootstrap-datepicker/bootstrap-datepicker3.min.css",
    ],
    'datetimepicker_js' => [ // 日期时间选择
        $libs_path. "bootstrap-datetimepicker/moment.min.js",
        $libs_path. "bootstrap-datetimepicker/bootstrap-datetimepicker.min.js",
        $libs_path. "bootstrap-datetimepicker/locale/zh-cn.js",
    ],
    'moment_js' => [
        $libs_path. "bootstrap-datetimepicker/moment.min.js",
    ],
    'datetimepicker_css' => [ // 日期时间选择
        $libs_path. "bootstrap-datetimepicker/bootstrap-datetimepicker.min.css"
    ],
    'webuploader_js' => [ // 文件或图片上传
        $libs_path. "webuploader/webuploader.min.js",
    ],
    'webuploader_css' => [ // 文件或图片上传
        $libs_path. "webuploader/webuploader.css",
    ],
    'select2_js' => [ // 下拉框
        $libs_path. "select2/select2.full.min.js",
        $libs_path. "select2/i18n/zh-CN.js",
    ],
    'select2_css' => [ // 下拉框
        $libs_path. "select2/select2.min.css",
        $libs_path. "select2/select2-bootstrap.min.css",
    ],
    'tags_js' => [ // 标签
        $libs_path. "jquery-tags-input/jquery.tagsinput.min.js",
    ],
    'tags_css' => [ // 标签
        $libs_path. "jquery-tags-input/jquery.tagsinput.min.css",
    ],
    'validate_js' => [ // 验证
        $libs_path. "jquery-validation/jquery.validate.min.js",
    ],
    'editable_js' => [ // 快速编辑
        $libs_path. "bootstrap3-editable/js/bootstrap-editable.js",
    ],
    'editable_css' => [ // 快速编辑
        $libs_path. "bootstrap3-editable/css/bootstrap-editable.css",
    ],
    'colorpicker_js' => [ // 取色器
        $libs_path. "bootstrap-colorpicker/bootstrap-colorpicker.min.js",
    ],
    'colorpicker_css' => [ // 取色器
        $libs_path. "bootstrap-colorpicker/css/bootstrap-colorpicker.min.css",
    ],
    'editormd_js' => [ // markdown编辑器
        $libs_path. "editormd/editormd.min.js",
    ],
    'jcrop_js' => [ // 图片裁剪
        $libs_path. "jcrop/js/Jcrop.min.js",
    ],
    'jcrop_css' => [ // 图片裁剪
        $libs_path. "jcrop/css/Jcrop.min.css",
    ],
    'masked_inputs_js' => [ // 格式文本
        $libs_path. "masked-inputs/jquery.maskedinput.min.js",
    ],
    'rangeslider_js' => [ // 范围
        $libs_path. "ion-rangeslider/js/ion.rangeSlider.min.js",
    ],
    'rangeslider_css' => [ // 范围
        $libs_path. "ion-rangeslider/css/ion.rangeSlider.min.css",
        $libs_path. "ion-rangeslider/css/ion.rangeSlider.skinHTML5.min.css",
    ],
    'nestable_js' => [ // 拖拽排序
        $libs_path. "jquery-nestable/jquery.nestable.js",
    ],
    'nestable_css' => [ // 拖拽排序
        $libs_path. "jquery-nestable/jquery.nestable.css",
    ],
    'wangeditor_js' => [ // wang编辑器
        $libs_path. "wang-editor/js/wangEditor.min.js",
    ],
    'wangeditor_css' => [ // wang编辑器
        $libs_path. "wang-editor/css/wangEditor.min.css",
    ],
    'summernote_js' => [ // summernote编辑器
        $libs_path. "summernote/summernote.min.js",
        $libs_path. "summernote/lang/summernote-zh-CN.js",
    ],
    'summernote_css' => [ // summernote编辑器
        $libs_path. "summernote/summernote.min.css",
    ],
    'jqueryui_js' => [ // jqueryui
        $libs_path. "jquery-ui/jquery-ui.min.js",
    ],
    'daterangepicker_js' => [ // 日期时间范围
        $libs_path. "bootstrap-daterangepicker/daterangepicker.js",
    ],
    'daterangepicker_css' => [ // 日期时间范围
        $libs_path. "bootstrap-daterangepicker/daterangepicker.css",
    ]
];