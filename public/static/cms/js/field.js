/*
 *  Document   : field.js
 *  Author     : CaiWeiMing <314013107@qq.com>
 */

jQuery(function () {
    // 字段定义列表
    var $field_define_list = {
        text: "varchar(128) NOT NULL",
        textarea: "varchar(256) NOT NULL",
        static: "varchar(128) NOT NULL",
        password: "varchar(128) NOT NULL",
        checkbox: "varchar(32) NOT NULL",
        radio: "varchar(32) NOT NULL",
        date: "int(11) UNSIGNED NOT NULL",
        time: "int(11) UNSIGNED NOT NULL",
        datetime: "int(11) UNSIGNED NOT NULL",
        hidden: "varchar(32) NOT NULL",
        switch: "varchar(16) NOT NULL",
        array: "varchar(32) NOT NULL",
        select: "varchar(32) NOT NULL",
        linkage: "varchar(32) NOT NULL",
        linkages: "varchar(32) NOT NULL",
        image: "int(11) UNSIGNED NOT NULL",
        images: "varchar(64) NOT NULL",
        file: "int(11) UNSIGNED NOT NULL",
        files: "varchar(64) NOT NULL",
        ueditor: "text NOT NULL",
        wangeditor: "text NOT NULL",
        editormd: "text NOT NULL",
        ckeditor: "text NOT NULL",
        summernote: "text NOT NULL",
        icon: "varchar(64) NOT NULL",
        tags: "varchar(128) NOT NULL",
        number: "int(11) UNSIGNED NOT NULL",
        bmap: "varchar(32) NOT NULL",
        colorpicker: "varchar(32) NOT NULL",
        jcrop: "int(11) UNSIGNED NOT NULL",
        masked: "varchar(64) NOT NULL",
        range: "varchar(128) NOT NULL"
    };
    // 选择自动类型，自动填写字段定义
    var $field_define = jQuery('input[name=define]');
    jQuery('select[name=type]').change(function () {
        $field_define.val($field_define_list[$(this).val()] || '');
    });
});