/*!
 * Bubble chart extension hook
 * 负责把第三维数据映射为 symbolSize，并修正简化结构下的坐标轴配置。
 */
(function (window) {
    'use strict';

    window.DpChartTypeHooks = window.DpChartTypeHooks || {};

    window.DpChartTypeHooks.bubble = function (context) {
        const option = context.option || {};
        const payload = context.typePayload || {};
        const sizeDimension = Number(payload.sizeDimension ?? 2);
        const minSize = Number(payload.minSymbolSize ?? 12);
        const maxSize = Number(payload.maxSymbolSize ?? 36);
        const series = Array.isArray(option.series) ? option.series : [];

        const values = [];
        series.forEach(function (seriesItem) {
            const data = Array.isArray(seriesItem && seriesItem.data) ? seriesItem.data : [];
            data.forEach(function (point) {
                const value = Array.isArray(point)
                    ? point[sizeDimension]
                    : (point && Array.isArray(point.value) ? point.value[sizeDimension] : null);

                if (value !== null && value !== undefined && value !== '') {
                    values.push(Number(value));
                }
            });
        });

        const minValue = values.length ? Math.min.apply(null, values) : null;
        const maxValue = values.length ? Math.max.apply(null, values) : null;

        function resolveSize(point) {
            const rawValue = Array.isArray(point)
                ? point[sizeDimension]
                : (point && Array.isArray(point.value) ? point.value[sizeDimension] : null);

            if (rawValue === null || rawValue === undefined || rawValue === '' || Number.isNaN(Number(rawValue))) {
                return minSize;
            }

            const value = Number(rawValue);
            if (minValue === null || maxValue === null || minValue === maxValue) {
                return minSize;
            }

            const ratio = (value - minValue) / (maxValue - minValue);
            return minSize + ratio * (maxSize - minSize);
        }

        option.tooltip = option.tooltip && typeof option.tooltip === 'object'
            ? option.tooltip
            : { trigger: 'item' };
        option.tooltip.trigger = 'item';

        option.legend = option.legend && typeof option.legend === 'object'
            ? option.legend
            : { top: 0, left: 'center' };

        option.grid = option.grid && typeof option.grid === 'object'
            ? option.grid
            : {
                left: '3%',
                right: '4%',
                top: '56px',
                bottom: '40px',
                containLabel: true
            };

        option.xAxis = option.xAxis && typeof option.xAxis === 'object'
            ? option.xAxis
            : {};
        option.yAxis = option.yAxis && typeof option.yAxis === 'object'
            ? option.yAxis
            : {};

        option.xAxis.type = 'value';
        option.xAxis.scale = true;
        option.yAxis.type = 'value';
        option.yAxis.scale = true;

        option.series = series.map(function (seriesItem) {
            const current = Object.assign({}, seriesItem);
            current.type = 'scatter';
            if (!current.symbolSize) {
                current.symbolSize = function (point) {
                    return resolveSize(point);
                };
            }
            return current;
        });

        return option;
    };
})(window);
