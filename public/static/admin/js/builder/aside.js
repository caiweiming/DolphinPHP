/*!
 *  Document   : aside.js
 *  Author     : caiweiming <314013107@qq.com>
 *  Description: 侧栏构建器
 */
jQuery(document).ready(function() {
    // 侧栏开关
    $('#aside .switch input:checkbox').on('click', function () {
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
        $.post(dolphin.aside_edit_url, $data).success(function(res) {
            Dolphin.loading('hide');
            if (res.code) {
                Dolphin.notify(res.msg, 'success');
            } else {
                Dolphin.notify(res.msg, 'danger');
                $switch.prop('checked', !$data.status);
                return false;
            }
        }).fail(function (res) {
            Dolphin.loading('hide');
            Dolphin.notify($(res.responseText).find('h1').text() || '服务器内部错误~', 'danger');
        });
    });
});