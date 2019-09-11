/*!
 *  Document   : table.js
 *  Author     : caiweiming <314013107@qq.com>
 *  Description: 表格构建器
 */
jQuery(document).ready(function() {
    if ($.fn.editable) {
        // 快速编辑的url提交地址
        $.fn.editable.defaults.url = dolphin.quick_edit_url;
        // 值为空时显示的信息
        $.fn.editable.defaults.emptytext = '空值';
        // 提交时的额外参数
        $.fn.editable.defaults.params = function (params) {
            params._t       = $(this).data('table') || '';
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
    }

    // 跳转链接
    var goto = function (url, _curr_params, remove_page) {
        var params = {};

        if (remove_page && dolphin.curr_params['page'] !== undefined) {
            delete dolphin.curr_params['page'];
        }

        if ($.isEmptyObject(dolphin.curr_params)) {
            params = jQuery.param(_curr_params);
        } else {
            $.extend(dolphin.curr_params, _curr_params);
            params = jQuery.param(dolphin.curr_params);
        }

        location.href = url + '?'+ params;
    };

    // 初始化搜索
    var search_field = dolphin.search_field;
    var search_input_placeholder = $('#search-input').attr('placeholder');
    if (search_field !== '') {
        $('.search-bar .dropdown-menu a').each(function () {
            var self = $(this);
            if (self.data('field') === search_field) {
                $('#search-btn').html(self.text() + ' <span class="caret"></span>');
                if (self.text() === '不限') {
                    $('#search-input').attr('placeholder', search_input_placeholder);
                } else {
                    $('#search-input').attr('placeholder', '请输入'+self.text());
                }
            }
        })
    }

    // 搜索
    $('.search-bar .dropdown-menu a').click(function () {
        var field = $(this).data('field') || '';
        $('#search-field').val(field);
        $('#search-btn').html($(this).text() + ' <span class="caret"></span>');
        if ($(this).text() === '不限') {
            $('#search-input').attr('placeholder', search_input_placeholder);
        } else {
            $('#search-input').attr('placeholder', '请输入'+$(this).text());
        }
    });
    $('#search-input').on('keyup', function (e) {
        if (e.keyCode === 13) {
            var $url = $(this).data('url');
            var $filed = $('#search-field').val();
            var $keyword = $(this).val();
            var _curr_params = {
                'search_field': $filed || '',
                'keyword': $keyword || ''
            };

            goto($url, _curr_params, true);
        }
    });
    $('#search-submit-btn').click(function () {
        var $url = $('#search-input').data('url');
        var $filed = $('#search-field').val();
        var $keyword = $('#search-input').val();
        var _curr_params = {
            'search_field': $filed || '',
            'keyword': $keyword || ''
        };

        goto($url, _curr_params, true);
    });

    // 筛选
    $('.table-builder .field-filter').click(function () {
        var self             = $(this),
            $field_display   = self.data('field-display'), // 当前表格字段显示的字段名，未必是数据库字段名
            $filter          = self.data('filter'), // 要筛选的字段
            $_type           = self.data('type'), // 筛选方式
            $_filter         = dolphin._filter,
            $_filter_content = dolphin._filter_content,
            $_field_display  = dolphin._field_display,
            $data  = {
                token: self.data('token') || '', // Token
                map: self.data('map') || '', // 筛选条件
                options: self.data('options') || '', // 选项
                list: self.data('list') || ''
            };

        var width = $(window).width();
        if (width > 500) {
            width = 500;
        }

        layer.open({
            type: 1,
            title: '<i class="fa fa-filter"></i> 筛选',
            shadeClose: true,
            area: [width+'px', '530px'],
            btn:['确定', '取消'],
            content: '<div class="block-content" id="filter-check-content"><i class="fa fa-cog fa-spin"></i> 正在读取...</div>',
            success: function () {
                var $curr_filter_content = '';
                var $curr_filter = '';
                if ($_filter !== '') {
                    $curr_filter = $_filter.split('|');
                    var filed_index = $.inArray($filter, $curr_filter);
                    if (filed_index !== -1) {
                        $curr_filter_content = $_filter_content.split('|');
                        $curr_filter_content = $curr_filter_content[filed_index];
                        $curr_filter_content = $curr_filter_content.split(',');
                    }
                }
                // 获取数据
                $.post(dolphin.get_filter_list, $data).success(function(res) {
                    if (1 !== res.code) {
                        $('#filter-check-content').html(res.msg);
                        return false;
                    }

                    var list = '<div class="row push-10"><div class="col-sm-12"><div class="input-group"><div class="input-group-addon"><i class="fa fa-search"></i></div><input class="js-field-search form-control" type="text" placeholder="查找要筛选的字段"></div></div></div>';
                    if ($_type === 'checkbox') {
                        list += '<div class="row"><div class="col-sm-12"><label class="css-input css-checkbox css-checkbox-primary">';
                        list += '<input type="checkbox" id="filter-check-all"><span></span> 全选';
                        list += '</label></div></div>';
                    }
                    list += '<div class="filter-field-list">';
                    for(var key in res.list) {
                        // 如果不是该对象自身直接创建的属性（也就是该属//性是原型中的属性），则跳过显示
                        if (!res.list.hasOwnProperty(key)) {
                            continue;
                        }

                        list += '<div class="row" data-field="'+res.list[key]+'"><div class="col-sm-12">';
                        if ($_type === 'checkbox') {
                            list += '<label class="css-input css-checkbox css-checkbox-primary">';
                            list += '<input type="checkbox" ';
                            if ($curr_filter_content !== '' && $.inArray(key, $curr_filter_content) !== -1) {
                                list += 'checked ';
                            }
                            list += 'value="'+ key +'" class="check-item"><span></span> '+res.list[key];
                            list += '</label>';
                        } else {
                            list += '<label class="css-input css-radio css-radio-primary">';
                            list += '<input type="radio" name="_filter_'+$field_display+'" ';
                            if ($curr_filter_content !== '' && $curr_filter_content == key) {
                                list += 'checked ';
                            }
                            list += 'value="'+ key +'" class="check-item"><span></span> '+res.list[key];
                            list += '</label>';
                        }
                        list += '</div></div>';
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
                }).fail(function (res) {
                    Dolphin.notify($(res.responseText).find('h1').text() || '服务器内部错误~', 'danger');
                });
            },
            yes: function () {
                var filed_index = -1;
                if ($('#filter-check-content input[class=check-item]:checked').length == 0) {
                    // 没有选择筛选字段，则删除原先该字段的筛选
                    $_filter        = $_filter.split('|');
                    filed_index = $.inArray($filter, $_filter);
                    if (filed_index !== -1) {
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
                        if ($(this).val() !== '') {
                            $fields.push($(this).val())
                        }
                    });
                    $fields = $fields.join(',');

                    if ($_filter !== '') {
                        $_filter = $_filter.split('|');
                        filed_index = $.inArray($filter, $_filter);
                        $_filter = $_filter.join('|');

                        if (filed_index === -1) {
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
                var _curr_params = {
                    _filter: $filter || '',
                    _filter_content: $fields || '',
                    _field_display: $field_display || ''
                };

                goto(dolphin.curr_url, _curr_params, true);
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
            _t: $switch.data('table') || '',
            name: $switch.data('field') || '',
            type: 'switch',
            pk: $switch.data('id') || ''
        };

        // 发送ajax请求
        Dolphin.loading();
        $.post(dolphin.quick_edit_url, $data).success(function(res) {
            Dolphin.loading('hide');
            if (res.code) {
                Dolphin.notify(res.msg, 'success');
            } else {
                Dolphin.notify(res.msg, 'danger');
                $switch.prop('checked', !$data.value);
                return false;
            }
        }).fail(function (res) {
            Dolphin.loading('hide');
            Dolphin.notify($(res.responseText).find('h1').text() || '服务器内部错误~', 'danger');
        });
    });

    // 分页搜索
    $('.pagination-info input').click(function () {
        $(this).select();
    });
    $('#go-page').on('keyup', function (e) {
        if (e.keyCode === 13) {
            var _curr_params = {
                'page': $('#go-page').val(),
                'list_rows': $('#list-rows').val()
            };

            goto(dolphin.curr_url, _curr_params);
        }
    });
    $('#list-rows').on('keyup', function (e) {
        if (e.keyCode === 13) {
            var _curr_params = {
                'page': 1,
                'list_rows': $('#list-rows').val()
            };

            goto(dolphin.curr_url, _curr_params);
        }
    });

    // 时间段搜索
    $('#btn-filter-time').click(function () {
        var _curr_params = {
            '_filter_time_from': $('#_filter_time_from').val(),
            '_filter_time_to': $('#_filter_time_to').val(),
            '_filter_time': $('#_filter_time').val()
        };

        goto(dolphin.curr_url, _curr_params, true);
    });

    // 弹出框显示页面
    $('#page-container').delegate('a.pop', 'click', function () {
        var $url   = $(this).attr('href');
        var $title = $(this).attr('title') || $(this).data('original-title');
        var $layer = $(this).data('layer');

        // 是否需要获取表格数据
        if ($(this).hasClass('js-get')) {
            var target_form = $(this).attr("target-form");
            var form        = jQuery('form[name=' + target_form + ']');
            var form_data   = form.serialize() || [];

            if (form.length === 0) {
                form = jQuery('.' + target_form + '[type=checkbox]:checked');
                form.each(function () {
                    form_data.push($(this).val());
                });
                form_data = form_data.join(',');
            }

            if (form_data === '') {
                Dolphin.notify('请选择要操作的数据', 'warning');
                return false;
            }

            if ($url.indexOf('?') !== -1) {
                $url += '&' + target_form + '=' + form_data;
            } else {
                $url += '?' + target_form + '=' + form_data;
            }
        }

        var $options = {
            title: $title,
            content: $url
        };

        // 处理各种回调方法
        dolphin.layer.success = dolphin.layer.success ? window[dolphin.layer.success] : null;
        dolphin.layer.yes     = dolphin.layer.yes ? window[dolphin.layer.yes] : null;
        dolphin.layer.cancel  = dolphin.layer.cancel ? window[dolphin.layer.cancel] : null;
        dolphin.layer.end     = dolphin.layer.end ? window[dolphin.layer.end] : null;
        dolphin.layer.full    = dolphin.layer.full ? window[dolphin.layer.full] : null;
        dolphin.layer.min     = dolphin.layer.min ? window[dolphin.layer.min] : null;
        dolphin.layer.max     = dolphin.layer.max ? window[dolphin.layer.max] : null;
        dolphin.layer.restore = dolphin.layer.restore ? window[dolphin.layer.restore] : null;

        if ($layer !== undefined) {
            // 处理各种回调方法
            $layer.success = $layer.success ? window[$layer.success] : dolphin.layer.success;
            $layer.yes     = $layer.yes ? window[$layer.yes] : dolphin.layer.yes;
            $layer.cancel  = $layer.cancel ? window[$layer.cancel] : dolphin.layer.cancel;
            $layer.end     = $layer.end ? window[$layer.end] : dolphin.layer.end;
            $layer.full    = $layer.full ? window[$layer.full] : dolphin.layer.full;
            $layer.min     = $layer.min ? window[$layer.min] : dolphin.layer.min;
            $layer.max     = $layer.max ? window[$layer.max] : dolphin.layer.max;
            $layer.restore = $layer.restore ? window[$layer.restore] : dolphin.layer.restore;

            $.extend($options, dolphin.layer, $layer);
        } else {
            $.extend($options, dolphin.layer);
        }

        if ($options.cancel === null && (typeof window.layer_cancel === "function")) {
            $options.cancel = window.layer_cancel;
        }
        if ($options.success === null && (typeof window.layer_success === "function")) {
            $options.success = window.layer_success;
        }
        if ($options.yes === null && (typeof window.layer_yes === "function")) {
            $options.yes = window.layer_yes;
        }
        if ($options.end === null && (typeof window.layer_end === "function")) {
            $options.end = window.layer_end;
        }
        if ($options.full === null && (typeof window.layer_full === "function")) {
            $options.full = window.layer_full;
        }
        if ($options.min === null && (typeof window.layer_min === "function")) {
            $options.min = window.layer_min;
        }
        if ($options.max === null && (typeof window.layer_max === "function")) {
            $options.max = window.layer_max;
        }
        if ($options.restore === null && (typeof window.layer_restore === "function")) {
            $options.restore = window.layer_restore;
        }

        layer.open($options);
        return false;
    });

    // 顶部下拉菜单
    $('.select-change').change(function(){
        var $url = $(this).find('option:selected').data('url');
        if ($url) {
            window.location.href = $url;
        }
    });

    // 搜索区域
    $('#search-area').submit(function () {
        var items = $('#search-area').serializeArray();
        var op  = $('#_o').val();
        var str = [];
        $.each(items, function (index, e) {
            str.push(e.name + '=' + e.value)
        });
        str = str.join('|');
        location.href = $(this).attr('action')+'?_s='+str+'&_o='+op;
        return false;
    });
});