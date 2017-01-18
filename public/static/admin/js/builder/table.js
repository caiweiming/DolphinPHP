/*!
 *  Document   : table.js
 *  Author     : caiweiming <314013107@qq.com>
 *  Description: 表格构建器
 */
jQuery(document).ready(function() {
    // 快速编辑的url提交地址
    $.fn.editable.defaults.url = dolphin.quick_edit_url;
    // 值为空时显示的信息
    $.fn.editable.defaults.emptytext = '空值';
    // 提交时的额外参数
    $.fn.editable.defaults.params = function (params) {
        params.table    = $(this).data('table') || '';
        params.type     = $(this).data('type') || '';
        params.validate = dolphin.validate;
        params.validate_fields = dolphin.validate_fields;
        return params;
    };
    // 提交成功时的回调函数
    $.fn.editable.defaults.success = function (res) {
        if (res.code) {
            Dolphin.notify(res.msg, 'success');
        } else {
            return res.msg;
        }
    };
    // 提交失败时的回调函数
    $.fn.editable.defaults.error = function(res) {
        if(res.status === 500) {
            return '服务器内部错误. 请稍后重试.';
        } else {
            return res.responseText;
        }
    };

    // 可编辑单行文本
    $('.text-edit').editable();

    // 可编辑多行文本
    $('.textarea-edit').editable({
        showbuttons: 'bottom'
    });

    // 下拉编辑
    $('.select-edit').editable();
    $('.select2-edit').editable({
        select2: {
            multiple: true,
            tokenSeparators: [',', ' ']
        }
    });

    // 日期时间
    $('.combodate-edit').editable({
        combodate: {
            maxYear: 2036,
            minuteStep: 1
        }
    });

    // 初始化搜索
    var search_field = dolphin.search_field;
    if (search_field != '') {
        $('.search-bar .dropdown-menu a').each(function () {
            var self = $(this);
            if (self.data('field') == search_field) {
                $('#search-btn').html(self.text() + ' <span class="caret"></span>');
            }
        })
    }

    // 搜索
    $('.search-bar .dropdown-menu a').click(function () {
        var field = $(this).data('field') || '';
        $('#search-field').val(field);
        $('#search-btn').html($(this).text() + ' <span class="caret"></span>');
    });

    // 筛选
    $('.table-builder .field-filter').click(function () {
        var self             = $(this),
            $field_display   = self.data('field-display'), // 当前表格字段显示的字段名，未必是数据库字段名
            $filter          = self.data('filter'), // 要筛选的字段
            $_filter         = dolphin._filter,
            $_filter_content = dolphin._filter_content,
            $_field_display  = dolphin._field_display,
            $data  = {
                table: self.data('table') || '', // 数据表名
                field: self.data('field') || '', // 数据库字段名
                map: self.data('map') || '', // 筛选条件
                options: self.data('options') || '' // 选项
            };

        layer.open({
            type: 1,
            title: '<i class="fa fa-filter"></i> 筛选',
            area: ['500px', '530px'],
            btn:['确定', '取消'],
            content: '<div class="block-content" id="filter-check-content"><i class="fa fa-cog fa-spin"></i> 正在读取...</div>',
            success: function () {
                var $curr_filter_content = '';
                var $curr_filter = '';
                if ($_filter != '') {
                    $curr_filter = $_filter.split('|');
                    var filed_index = $.inArray($filter, $curr_filter);
                    if (filed_index != -1) {
                        $curr_filter_content = $_filter_content.split('|');
                        $curr_filter_content = $curr_filter_content[filed_index];
                        $curr_filter_content = $curr_filter_content.split(',');
                    }
                }
                // 获取数据
                $.post(dolphin.get_filter_list, $data).success(function(res) {
                    if (1 != res.code) {
                        $('#filter-check-content').html(res.msg);
                        return false;
                    }

                    var list = '<div class="row push-10"><div class="col-sm-12"><div class="input-group"><div class="input-group-addon"><i class="fa fa-search"></i></div><input class="js-field-search form-control" type="text" placeholder="查找要筛选的字段"></div></div></div>';
                    list += '<div class="row"><div class="col-sm-12"><label class="css-input css-checkbox css-checkbox-primary">';
                    list += '<input type="checkbox" id="filter-check-all"><span></span> 全选';
                    list += '</label></div></div>';
                    list += '<div class="filter-field-list">';
                    for(var key in res.list) {
                        list += '<div class="row" data-field="'+res.list[key]+'"><div class="col-sm-12"><label class="css-input css-checkbox css-checkbox-primary">';
                        list += '<input type="checkbox" ';
                        if ($curr_filter_content != '' && $.inArray(key, $curr_filter_content) != -1) {
                            list += 'checked="" ';
                        }
                        list += 'value="'+ key +'" class="check-item"><span></span> '+res.list[key];
                        list += '</label></div></div>';
                    }
                    list += '</div>';
                    $('#filter-check-content').html(list);

                    // 查找要筛选的字段
                    var $searchItems = jQuery('.filter-field-list > div');
                    var $searchValue = '';
                    var reg;
                    $('.js-field-search').on('keyup', function(){
                        $searchValue = $(this).val().toLowerCase();

                        if ($searchValue.length >= 1) {
                            $searchItems.hide().removeClass('field-show');

                            $($searchItems).each(function(){
                                reg = new RegExp($searchValue, 'i');
                                if ($(this).text().match(reg)) {
                                    $(this).show().addClass('field-show');
                                }
                            });
                        } else if ($searchValue.length === 0) {
                            $searchItems.show().removeClass('field-show');
                        }
                    });
                }).fail(function () {
                    Dolphin.notify('服务器发生错误~', 'danger');
                });
            },
            yes: function () {
                if ($('#filter-check-content input[class=check-item]:checked').length == 0) {
                    // 没有选择筛选字段，则删除原先该字段的筛选
                    $_filter        = $_filter.split('|');
                    var filed_index = $.inArray($filter, $_filter);
                    if (filed_index != -1) {
                        $_filter.splice(filed_index, 1);
                        $filter         = $_filter.join('|');

                        $_field_display = $_field_display.split(',');
                        $_field_display.splice(filed_index, 1);
                        $field_display  = $_field_display.join(',');

                        $_filter_content = $_filter_content.split('|');
                        $_filter_content.splice(filed_index, 1);
                        $fields          = $_filter_content.join('|');
                    }
                } else {
                    // 当前要筛选字段内容
                    var $fields = [];
                    $('#filter-check-content input[class=check-item]:checked').each(function () {
                        $fields.push($(this).val())
                    });
                    $fields = $fields.join(',');

                    if ($_filter != '') {
                        $_filter = $_filter.split('|');
                        var filed_index = $.inArray($filter, $_filter);
                        $_filter = $_filter.join('|');

                        if (filed_index == -1) {
                            $filter = $_filter + '|' + $filter;
                            $fields = $_filter_content + '|' + $fields;
                            $field_display = $_field_display + ',' + $field_display;
                        } else {
                            $filter = $_filter;
                            $field_display = $_field_display;
                            $_filter_content = $_filter_content.split('|');
                            $_filter_content[filed_index] = $fields;
                            $fields = $_filter_content.join('|');
                        }
                    }
                }

                location.href = dolphin.curr_url + '?_filter='+ $filter + '&_filter_content=' + $fields + '&_field_display=' + $field_display;
            }
        });
        return false;
    });

    // 筛选框全选或取消全选
    $('body').delegate('#filter-check-all', 'click', function () {
        var $checkStatus = $(this).prop('checked');
        if ($('.js-field-search').val()) {
            $('#filter-check-content .field-show .check-item').each(function () {
                $(this).prop('checked', $checkStatus);
            });
        } else {
            $('#filter-check-content .check-item').each(function () {
                $(this).prop('checked', $checkStatus);
            });
        }
    });

    // 开关
    $('.table-builder .switch input:checkbox').on('click', function () {
        var $switch = $(this);
        var $data = {
            value: $switch.prop('checked'),
            table: $switch.data('table') || '',
            name: $switch.data('field') || '',
            type: 'switch',
            pk: $switch.data('id') || '',
        };

        // 发送ajax请求
        Dolphin.loading();
        $.post(dolphin.quick_edit_url, $data).success(function(res) {
            Dolphin.loading('hide');
            if (1 != res.code) {
                Dolphin.notify(res.msg, 'danger');
                $switch.prop('checked', !$data.status);
                return false;
            } else {
                Dolphin.notify(res.msg, 'success');
            }
        }).fail(function () {
            Dolphin.loading('hide');
            Dolphin.notify('服务器发生错误~', 'danger');
        });
    });

    // 分页搜索
    $('.pagination-info input').click(function () {
        $(this).select();
    });

    // 弹出框显示页面
    $('.pop').click(function () {
        var $url = $(this).attr('href');
        var $title = $(this).attr('title') || $(this).data('original-title');
        layer.open({
            type: 2,
            title: $title,
            area: ['80%', '90%'],
            content: $url
        });
        return false;
    });
});