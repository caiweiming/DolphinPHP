(function (window, document) {
    'use strict';

    const instances = new Map();
    const mapJsonCache = new Map();
    const mapReadyCache = new Map();
    let resizeBound = false;

    function getJQuery() {
        return window.jQuery || window.$ || null;
    }

    function parsePayload(element) {
        const raw = element.getAttribute('data-chart');
        if (!raw) {
            return null;
        }

        try {
            return JSON.parse(raw);
        } catch (error) {
            console.error('[DpChart] 图表配置解析失败', error);
            return null;
        }
    }

    function bindResize() {
        if (resizeBound) {
            return;
        }

        resizeBound = true;
        window.addEventListener('resize', function () {
            instances.forEach(function (instance) {
                if (instance && typeof instance.resize === 'function') {
                    instance.resize();
                }
            });
        });
    }

    function getTypeHooks() {
        if (!window.DpChartTypeHooks || typeof window.DpChartTypeHooks !== 'object') {
            window.DpChartTypeHooks = {};
        }

        return window.DpChartTypeHooks;
    }

    function getMapHooks() {
        if (!window.DpChartMapHooks || typeof window.DpChartMapHooks !== 'object') {
            window.DpChartMapHooks = {};
        }

        return window.DpChartMapHooks;
    }

    function isPlainObject(value) {
        return Object.prototype.toString.call(value) === '[object Object]';
    }

    function deepMerge(target, source) {
        const output = isPlainObject(target) ? Object.assign({}, target) : {};
        if (!isPlainObject(source)) {
            return output;
        }

        Object.keys(source).forEach(function (key) {
            const sourceValue = source[key];
            const targetValue = output[key];

            if (Array.isArray(sourceValue)) {
                output[key] = sourceValue.slice();
                return;
            }

            if (isPlainObject(sourceValue)) {
                output[key] = deepMerge(isPlainObject(targetValue) ? targetValue : {}, sourceValue);
                return;
            }

            output[key] = sourceValue;
        });

        return output;
    }

    function cloneObject(value) {
        return isPlainObject(value) ? deepMerge({}, value) : {};
    }

    function toggleEmptyState(element, visible) {
        const wrapper = element.closest('.dp-chart-wrapper');
        if (!wrapper) {
            return;
        }

        const emptyState = wrapper.querySelector('.dp-chart-empty-state');
        if (!emptyState) {
            return;
        }

        emptyState.classList.toggle('d-none', !visible);
    }

    function hasDataItem(item) {
        if (item == null) {
            return false;
        }

        if (Array.isArray(item)) {
            if (item.length === 0) {
                return false;
            }

            return item.some(function (value) {
                return value !== null && value !== '';
            });
        }

        if (typeof item === 'object') {
            if (Array.isArray(item.value)) {
                return item.value.some(function (value) {
                    return value !== null && value !== '';
                });
            }

            return item.value !== undefined || item.coords !== undefined || item.coord !== undefined;
        }

        return item !== '';
    }

    function hasSeriesData(option) {
        if (!option || !Array.isArray(option.series) || option.series.length === 0) {
            return !!(option && option.geo && option.geo.map);
        }

        return option.series.some(function (seriesItem) {
            if (!seriesItem || typeof seriesItem !== 'object') {
                return false;
            }

            if (seriesItem.type === 'map' && seriesItem.map) {
                return true;
            }

            if (!Array.isArray(seriesItem.data) || seriesItem.data.length === 0) {
                return false;
            }

            return seriesItem.data.some(function (item) {
                return hasDataItem(item);
            });
        });
    }

    function normalizeSeries(type, series) {
        if (!Array.isArray(series) || series.length === 0) {
            return [];
        }

        if (!Array.isArray(series[0]) && series[0] && typeof series[0] === 'object' && Object.prototype.hasOwnProperty.call(series[0], 'data')) {
            return series.map(function (item) {
                const current = Object.assign({}, item);
                if (!current.type) {
                    current.type = type;
                }
                return current;
            });
        }

        if (!Array.isArray(series[0]) && series[0] && typeof series[0] === 'object') {
            return [{
                type: type,
                data: series.slice()
            }];
        }

        if (!Array.isArray(series[0]) && (!series[0] || typeof series[0] !== 'object')) {
            return [{
                type: type,
                data: series.slice()
            }];
        }

        if (Array.isArray(series[0])) {
            return [{
                type: type,
                data: series.slice()
            }];
        }

        return series.map(function (item) {
            if (item && typeof item === 'object' && !Array.isArray(item)) {
                const current = Object.assign({}, item);
                if (!current.type) {
                    current.type = type;
                }
                return current;
            }

            return {
                type: type,
                data: Array.isArray(item) ? item : [item]
            };
        });
    }

    function buildOptionFromData(payload, data) {
        if (!data || typeof data !== 'object') {
            return null;
        }

        if (data.option && typeof data.option === 'object') {
            return data.option;
        }

        const type = data.type || payload.type || 'line';
        const categories = Array.isArray(data.categories) ? data.categories : [];
        const series = normalizeSeries(type, Array.isArray(data.series) ? data.series : []);

        if (type === 'pie') {
            const pieSeries = series.length > 0 ? series : [{
                type: 'pie',
                radius: '50%',
                data: Array.isArray(data.data) ? data.data : []
            }];

            return {
                tooltip: { trigger: 'item' },
                legend: { top: 'bottom' },
                series: pieSeries
            };
        }

        if (type === 'scatter') {
            return {
                tooltip: { trigger: 'item' },
                legend: { top: 0, left: 'center' },
                grid: {
                    left: '3%',
                    right: '4%',
                    top: '56px',
                    bottom: '40px',
                    containLabel: true
                },
                xAxis: {
                    type: 'value',
                    scale: true
                },
                yAxis: {
                    type: 'value',
                    scale: true
                },
                series: series
            };
        }

        return {
            tooltip: { trigger: 'axis' },
            xAxis: {
                type: 'category',
                data: categories
            },
            yAxis: {
                type: 'value'
            },
            series: series
        };
    }

    function normalizeBody(response) {
        if (!response || typeof response !== 'object') {
            return null;
        }

        if (response.data && typeof response.data === 'object' && !Array.isArray(response.data)) {
            return response.data;
        }

        return response;
    }

    function getRegisteredMap(registerName) {
        if (!window.echarts || typeof window.echarts.getMap !== 'function') {
            return null;
        }

        try {
            return window.echarts.getMap(registerName) || null;
        } catch (error) {
            return null;
        }
    }

    function getMapCacheKey(mapPayload) {
        const definition = isPlainObject(mapPayload && mapPayload.definition) ? mapPayload.definition : {};
        const registerName = typeof definition.registerName === 'string' ? definition.registerName : '';
        const geoJsonUrl = typeof definition.geoJsonUrl === 'string' ? definition.geoJsonUrl : '';

        return registerName + '::' + geoJsonUrl;
    }

    function loadMapJson(url) {
        if (!url) {
            return Promise.resolve(null);
        }

        if (mapJsonCache.has(url)) {
            return mapJsonCache.get(url);
        }

        const promise = new Promise(function (resolve, reject) {
            if (typeof window.fetch === 'function') {
                window.fetch(url, {
                    credentials: 'same-origin'
                }).then(function (response) {
                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status);
                    }

                    return response.json();
                }).then(resolve).catch(reject);
                return;
            }

            const $ = getJQuery();
            if (!$ || typeof $.ajax !== 'function') {
                reject(new Error('浏览器不支持 fetch，且未检测到 jQuery.ajax'));
                return;
            }

            $.ajax({
                url: url,
                type: 'GET',
                dataType: 'json',
                success: resolve,
                error: function () {
                    reject(new Error('GeoJSON 加载失败'));
                }
            });
        }).catch(function (error) {
            mapJsonCache.delete(url);
            throw error;
        });

        mapJsonCache.set(url, promise);
        return promise;
    }

    function registerMapDefinition(definition, geoJson) {
        if (!window.echarts || typeof window.echarts.registerMap !== 'function') {
            throw new Error('ECharts.registerMap 不可用');
        }

        const registerName = typeof definition.registerName === 'string' ? definition.registerName : '';
        if (!registerName) {
            throw new Error('地图扩展缺少 registerName');
        }

        if (getRegisteredMap(registerName)) {
            return;
        }

        window.echarts.registerMap(
            registerName,
            geoJson,
            isPlainObject(definition.specialAreas) ? definition.specialAreas : {}
        );
    }

    function isMapEnabled(payload) {
        return !!(
            payload &&
            payload.map &&
            payload.map.enabled === true &&
            payload.map.definition &&
            typeof payload.map.definition.registerName === 'string' &&
            payload.map.definition.registerName
        );
    }

    function ensureMapRegistered(mapPayload) {
        if (!isMapEnabled({ map: mapPayload })) {
            return Promise.resolve(mapPayload);
        }

        const definition = isPlainObject(mapPayload.definition) ? mapPayload.definition : {};
        const registerName = typeof definition.registerName === 'string' ? definition.registerName : '';
        const cacheKey = getMapCacheKey(mapPayload);

        if (!registerName) {
            return Promise.resolve(mapPayload);
        }

        if (getRegisteredMap(registerName)) {
            return Promise.resolve(mapPayload);
        }

        if (mapReadyCache.has(cacheKey)) {
            return mapReadyCache.get(cacheKey);
        }

        const promise = new Promise(function (resolve, reject) {
            if (isPlainObject(definition.geoJson)) {
                try {
                    registerMapDefinition(definition, definition.geoJson);
                    resolve(mapPayload);
                } catch (error) {
                    reject(error);
                }
                return;
            }

            if (getRegisteredMap(registerName)) {
                resolve(mapPayload);
                return;
            }

            const geoJsonUrl = typeof definition.geoJsonUrl === 'string' ? definition.geoJsonUrl : '';
            if (!geoJsonUrl) {
                resolve(mapPayload);
                return;
            }

            loadMapJson(geoJsonUrl).then(function (geoJson) {
                registerMapDefinition(definition, geoJson);
                resolve(mapPayload);
            }).catch(reject);
        }).catch(function (error) {
            mapReadyCache.delete(cacheKey);
            throw error;
        });

        mapReadyCache.set(cacheKey, promise);
        return promise;
    }

    function buildMapGeoOption(mapPayload) {
        const definition = isPlainObject(mapPayload.definition) ? mapPayload.definition : {};
        const mapData = isPlainObject(mapPayload.data) ? mapPayload.data : {};
        const view = isPlainObject(mapData.view) ? mapData.view : {};
        const geo = isPlainObject(mapData.geo) ? mapData.geo : {};

        const baseGeo = {
            map: definition.registerName,
            roam: view.roam !== undefined ? view.roam : true,
            zoom: view.zoom !== undefined ? view.zoom : 1,
            center: Array.isArray(view.center) ? view.center : undefined,
            selectedMode: view.selectedMode !== undefined ? view.selectedMode : false,
            nameMap: isPlainObject(definition.nameMap) ? definition.nameMap : {},
            itemStyle: {
                areaColor: 'rgba(0, 0, 0, 0)',
                borderColor: 'rgba(0, 0, 0, 0)'
            },
            emphasis: {
                disabled: true
            },
            label: {
                show: false
            }
        };

        return deepMerge(baseGeo, geo);
    }

    function normalizeMapSeriesItem(seriesItem, definition, mapData, meta) {
        const current = isPlainObject(seriesItem) ? cloneObject(seriesItem) : {};
        const view = isPlainObject(mapData.view) ? mapData.view : {};
        const geo = isPlainObject(mapData.geo) ? mapData.geo : {};

        const baseSeries = {
            name: (meta && meta.title) || '地图',
            type: 'map',
            map: definition.registerName,
            roam: view.roam !== undefined ? view.roam : true,
            zoom: view.zoom !== undefined ? view.zoom : 1,
            center: Array.isArray(view.center) ? view.center : undefined,
            selectedMode: view.selectedMode !== undefined ? view.selectedMode : false,
            nameMap: isPlainObject(definition.nameMap) ? definition.nameMap : {},
            data: Array.isArray(mapData.regions) ? mapData.regions.slice() : []
        };

        if (isPlainObject(geo.label)) {
            baseSeries.label = cloneObject(geo.label);
        }

        if (isPlainObject(geo.emphasis)) {
            baseSeries.emphasis = cloneObject(geo.emphasis);
        }

        if (isPlainObject(geo.select)) {
            baseSeries.select = cloneObject(geo.select);
        }

        if (isPlainObject(geo.blur)) {
            baseSeries.blur = cloneObject(geo.blur);
        }

        if (isPlainObject(geo.itemStyle)) {
            baseSeries.itemStyle = cloneObject(geo.itemStyle);
        }

        return deepMerge(baseSeries, current);
    }

    function buildMapSeries(mapPayload) {
        const definition = isPlainObject(mapPayload.definition) ? mapPayload.definition : {};
        const mapData = isPlainObject(mapPayload.data) ? mapPayload.data : {};
        const meta = isPlainObject(mapPayload.meta) ? mapPayload.meta : {};
        const mapSeries = mapData.mapSeries;

        if (Array.isArray(mapSeries) && mapSeries.length > 0) {
            return mapSeries.map(function (seriesItem) {
                return normalizeMapSeriesItem(seriesItem, definition, mapData, meta);
            });
        }

        if (isPlainObject(mapSeries)) {
            return [normalizeMapSeriesItem(mapSeries, definition, mapData, meta)];
        }

        return [normalizeMapSeriesItem({}, definition, mapData, meta)];
    }

    function buildOverlaySeries(overlays) {
        if (!Array.isArray(overlays)) {
            return [];
        }

        return overlays.map(function (overlay) {
            if (!isPlainObject(overlay)) {
                return null;
            }

            const current = cloneObject(overlay);
            current.type = typeof current.type === 'string' && current.type ? current.type : 'scatter';

            if (current.type !== 'map' && current.coordinateSystem === undefined) {
                current.coordinateSystem = 'geo';
            }

            if (
                current.type !== 'map' &&
                current.type !== 'pie' &&
                current.type !== 'bar' &&
                current.type !== 'line' &&
                current.geoIndex === undefined
            ) {
                current.geoIndex = 0;
            }

            return current;
        }).filter(function (item) {
            return !!item;
        });
    }

    function buildMapOption(payload, mapPayload) {
        const baseOption = isPlainObject(payload && payload.option) ? cloneObject(payload.option) : {};
        const mapData = isPlainObject(mapPayload.data) ? mapPayload.data : {};
        const tooltip = isPlainObject(mapData.tooltip) ? mapData.tooltip : { trigger: 'item' };
        const visualMap = mapData.visualMap;

        const nextOption = {
            tooltip: tooltip,
            geo: buildMapGeoOption(mapPayload),
            series: buildMapSeries(mapPayload).concat(buildOverlaySeries(mapData.overlays))
        };

        if (Array.isArray(visualMap)) {
            nextOption.visualMap = visualMap.slice();
        } else if (isPlainObject(visualMap) && Object.keys(visualMap).length > 0) {
            nextOption.visualMap = cloneObject(visualMap);
        }

        return deepMerge(baseOption, nextOption);
    }

    function mergeMapPayload(basePayload, incomingPayload, mergeData) {
        const base = isPlainObject(basePayload) ? cloneObject(basePayload) : {};
        const incoming = isPlainObject(incomingPayload) ? incomingPayload : {};

        if (isPlainObject(incoming.meta)) {
            base.meta = deepMerge(isPlainObject(base.meta) ? base.meta : {}, incoming.meta);
        }

        if (isPlainObject(incoming.definition)) {
            base.definition = deepMerge(isPlainObject(base.definition) ? base.definition : {}, incoming.definition);
        }

        if (isPlainObject(incoming.providerPayload)) {
            base.providerPayload = deepMerge(isPlainObject(base.providerPayload) ? base.providerPayload : {}, incoming.providerPayload);
        }

        if (Object.prototype.hasOwnProperty.call(incoming, 'enabled')) {
            base.enabled = incoming.enabled;
        }

        if (Object.prototype.hasOwnProperty.call(incoming, 'mapKey')) {
            base.mapKey = incoming.mapKey;
        }

        if (Object.prototype.hasOwnProperty.call(incoming, 'data')) {
            const incomingData = isPlainObject(incoming.data) ? incoming.data : {};
            base.data = mergeData
                ? deepMerge(isPlainObject(base.data) ? base.data : {}, incomingData)
                : cloneObject(incomingData);
        }

        return base;
    }

    function normalizeMapResponse(payload, response, mergeData) {
        const body = normalizeBody(response);
        if (!body) {
            return null;
        }

        if (body.option && typeof body.option === 'object') {
            return {
                option: body.option,
                mapPayload: isPlainObject(payload.map) ? payload.map : null
            };
        }

        const incomingMapPayload = isPlainObject(body.map)
            ? body.map
            : { data: body };
        const nextMapPayload = mergeMapPayload(payload.map || {}, incomingMapPayload, mergeData);

        return {
            option: buildMapOption(payload, nextMapPayload),
            mapPayload: nextMapPayload
        };
    }

    function normalizeResponse(payload, response, mergeData) {
        if (isMapEnabled(payload)) {
            return normalizeMapResponse(payload, response, mergeData);
        }

        const body = normalizeBody(response);
        return {
            option: buildOptionFromData(payload, body),
            mapPayload: null
        };
    }

    function applyTypeHook(element, payload, chart, option) {
        if (!payload || !payload.type || !option || typeof option !== 'object') {
            return option;
        }

        const hook = getTypeHooks()[payload.type];
        if (typeof hook !== 'function') {
            return option;
        }

        try {
            const nextOption = hook({
                element: element,
                chart: chart,
                option: option,
                payload: payload,
                typePayload: payload.typePayload || {}
            });

            return nextOption && typeof nextOption === 'object' ? nextOption : option;
        } catch (error) {
            console.error('[DpChart] 图表类型 hook 执行失败', error);
            return option;
        }
    }

    function applyMapHook(element, payload, chart, option, mapPayload) {
        if (!isMapEnabled({ map: mapPayload }) || !option || typeof option !== 'object') {
            return option;
        }

        const mapKey = typeof mapPayload.mapKey === 'string' ? mapPayload.mapKey : '';
        const hook = mapKey ? getMapHooks()[mapKey] : null;
        if (typeof hook !== 'function') {
            return option;
        }

        try {
            const nextOption = hook({
                element: element,
                chart: chart,
                option: option,
                payload: payload,
                mapPayload: mapPayload,
                meta: mapPayload.meta || {},
                definition: mapPayload.definition || {},
                data: mapPayload.data || {},
                providerPayload: mapPayload.providerPayload || {}
            });

            return nextOption && typeof nextOption === 'object' ? nextOption : option;
        } catch (error) {
            console.error('[DpChart] 地图扩展 hook 执行失败', error);
            return option;
        }
    }

    function applyOption(element, payload, chart, option, merge, mapPayload) {
        const nextOption = isMapEnabled({ map: mapPayload || payload.map })
            ? applyMapHook(element, payload, chart, option, mapPayload || payload.map)
            : applyTypeHook(element, payload, chart, option);

        const hasData = hasSeriesData(nextOption);
        toggleEmptyState(element, !hasData);

        if (!hasData) {
            chart.clear();
            return;
        }

        chart.setOption(nextOption, !merge);
        chart.resize();
    }

    function createInstance(element, payload) {
        if (!window.echarts || typeof window.echarts.init !== 'function') {
            console.error('[DpChart] ECharts 未加载');
            return null;
        }

        let chart = window.echarts.getInstanceByDom(element);
        if (!chart) {
            chart = window.echarts.init(element, payload.theme || null, {
                renderer: payload.renderer || 'canvas'
            });
        }

        instances.set(element.id, chart);
        bindResize();
        return chart;
    }

    function resolveStaticOption(payload) {
        if (!isMapEnabled(payload)) {
            return Promise.resolve({
                option: isPlainObject(payload.option) ? payload.option : {},
                mapPayload: null
            });
        }

        return ensureMapRegistered(payload.map).then(function (mapPayload) {
            return {
                option: buildMapOption(payload, mapPayload),
                mapPayload: mapPayload
            };
        });
    }

    function handleChartError(element, chart, payload, error) {
        console.error('[DpChart] 图表渲染失败', error);
        toggleEmptyState(element, true);
        if (chart && typeof chart.hideLoading === 'function') {
            chart.hideLoading();
        }
        if (chart && typeof chart.clear === 'function') {
            chart.clear();
        }
        return null;
    }

    function loadRemoteData(element, payload, chart) {
        const dataset = payload.dataset || {};
        if (!dataset.url) {
            return;
        }

        const $ = getJQuery();
        if (!$ || typeof $.ajax !== 'function') {
            console.error('[DpChart] Ajax 数据源依赖 jQuery.ajax');
            return;
        }

        chart.showLoading('default', payload.loading || {});

        $.ajax({
            url: dataset.url,
            type: dataset.method || 'GET',
            data: dataset.params || {},
            dataType: 'json',
            success: function (response) {
                const merge = dataset.merge !== false;
                const normalized = normalizeResponse(payload, response, merge);

                if (!normalized || !normalized.option) {
                    chart.hideLoading();
                    toggleEmptyState(element, true);
                    chart.clear();
                    return;
                }

                const applyResolved = function (mapPayload) {
                    const option = !isMapEnabled({ map: mapPayload })
                        ? (merge
                            ? deepMerge(isPlainObject(payload.option) ? payload.option : {}, normalized.option)
                            : normalized.option)
                        : normalized.option;

                    applyOption(element, payload, chart, option, merge, mapPayload || normalized.mapPayload);
                    chart.hideLoading();
                };

                if (normalized.mapPayload && isMapEnabled({ map: normalized.mapPayload })) {
                    ensureMapRegistered(normalized.mapPayload).then(function (mapPayload) {
                        applyResolved(mapPayload);
                    }).catch(function (error) {
                        handleChartError(element, chart, payload, error);
                    });
                    return;
                }

                applyResolved(null);
            },
            error: function () {
                chart.hideLoading();
                toggleEmptyState(element, true);
                chart.clear();
            }
        });
    }

    function initElement(element) {
        const payload = parsePayload(element);
        if (!payload) {
            return null;
        }

        const chart = createInstance(element, payload);
        if (!chart) {
            return null;
        }

        if (payload.loading && payload.loading.show) {
            chart.showLoading('default', payload.loading);
        }

        return resolveStaticOption(payload).then(function (resolved) {
            applyOption(element, payload, chart, resolved.option, true, resolved.mapPayload);

            if (!payload.dataset || !payload.dataset.url) {
                chart.hideLoading();
            }

            loadRemoteData(element, payload, chart);
            return chart;
        }).catch(function (error) {
            return handleChartError(element, chart, payload, error);
        });
    }

    const DpChart = {
        scan(selector) {
            const context = selector
                ? (typeof selector === 'string' ? document.querySelector(selector) : selector)
                : document;

            if (!context) {
                return this;
            }

            const elements = context.matches && context.matches('.dp-chart[data-chart]')
                ? [context]
                : context.querySelectorAll('.dp-chart[data-chart]');

            Array.prototype.forEach.call(elements, function (element) {
                initElement(element);
            });

            return this;
        },

        init(selector) {
            return this.scan(selector);
        },

        get(id) {
            return instances.get(id) || null;
        },

        resize(id) {
            if (id) {
                const instance = instances.get(id);
                if (instance) {
                    instance.resize();
                }
                return this;
            }

            instances.forEach(function (instance) {
                instance.resize();
            });
            return this;
        },

        destroy(id) {
            const instance = instances.get(id);
            if (!instance) {
                return this;
            }

            instance.dispose();
            instances.delete(id);
            return this;
        }
    };

    window.DpChart = DpChart;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            DpChart.scan();
        });
    } else {
        DpChart.scan();
    }
})(window, document);
