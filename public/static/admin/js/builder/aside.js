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
            table: $switch.data('table') || '',
            name: $switch.data('field') || '',
            type: 'switch',
            pk: $switch.data('id') || ''
        };

        // 发送ajax请求
        Dolphin.loading();
        $.post(dolphin.aside_edit_url, $data).success(function(res) {
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
});