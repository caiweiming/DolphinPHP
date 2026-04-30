/**
 * 广东省地图专项扩展示例 hook
 */
(function () {
    window.DpChartMapHooks = window.DpChartMapHooks || {};

    window.DpChartMapHooks['china/guangdong'] = function (context) {
        const option = context.option || {};
        const providerPayload = context.providerPayload || {};
        const accentColor = providerPayload.accentColor || '#1f7a4d';

        option.tooltip = Object.assign(
            {
                trigger: 'item',
                formatter: function (params) {
                    const value = Array.isArray(params.value) ? params.value[2] : params.value;
                    const safeValue = value === undefined || value === null || value === '' ? '-' : value;
                    return params.name + '<br/>指标值：' + safeValue;
                }
            },
            option.tooltip || {}
        );

        if (option.geo && option.geo.emphasis && option.geo.emphasis.itemStyle) {
            option.geo.emphasis.itemStyle.areaColor = accentColor;
        }

        return option;
    };
}());
