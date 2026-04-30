$(function () {
    jQuery('.js-transfer').each(function () {
        let $transfer = jQuery(this);
        let options = {
            filterTextClear: '清除',
            filterPlaceHolder: '筛选',
            moveSelectedLabel: '确认所选',
            moveAllLabel: '全选',
            removeSelectedLabel: '取消所选',
            removeAllLabel: '取消全选',
            moveOnSelect: false,
            infoText: '共计 {0} 项',
            infoTextFiltered: '筛选结果：{0} 项',
            infoTextEmpty: '暂无数据'
        };

        $.extend(options, $transfer.data('options'));
        $transfer.bootstrapDualListbox(options);
    });
});