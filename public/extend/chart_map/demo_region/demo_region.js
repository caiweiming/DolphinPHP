(function (window) {
    'use strict';

    window.DpChartMapHooks = window.DpChartMapHooks || {};

    window.DpChartMapHooks.demo_region = function (context) {
        const option = context.option || {};
        const payload = context.providerPayload || {};

        if (option.series && option.series[0] && payload.accentColor) {
            option.series[0].itemStyle = option.series[0].itemStyle || {};
            option.series[0].itemStyle.borderColor = payload.accentColor;
            option.series[0].itemStyle.borderWidth = 1;
        }

        return option;
    };
})(window);
