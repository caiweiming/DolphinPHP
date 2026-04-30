/**
 * select_table 扩展项前端逻辑
 * 负责弹窗选取表格数据并回填到表单项
 */
(function ($) {
    'use strict';

    function parseJsonFromScript($root, selector, fallback) {
        const text = ($root.find(selector).first().text() || '').trim();
        if (!text) {
            return fallback;
        }

        try {
            return JSON.parse(text);
        } catch (e) {
            return fallback;
        }
    }

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function normalizeColumns(columns) {
        if (!Array.isArray(columns)) {
            return [];
        }
        return columns.filter(function (column) {
            return column && typeof column === 'object' && !Array.isArray(column) && column.key;
        }).map(function (column) {
            return {
                key: String(column.key),
                title: String(column.title || column.key)
            };
        });
    }

    function normalizeRows(rows) {
        if (!Array.isArray(rows)) {
            return [];
        }
        return rows.filter(function (row) {
            return row && typeof row === 'object' && !Array.isArray(row);
        });
    }

    function normalizeExtraFields(fields) {
        if (!Array.isArray(fields)) {
            return [];
        }

        const map = {};
        fields.forEach(function (field) {
            if (typeof field !== 'string') {
                return;
            }

            const key = field.trim();
            if (!key) {
                return;
            }

            map[key] = key;
        });

        return Object.values(map);
    }

    function normalizeConfig(config) {
        if (!config || typeof config !== 'object' || Array.isArray(config)) {
            config = {};
        }
        const popup = (config.popup && typeof config.popup === 'object' && !Array.isArray(config.popup)) ? config.popup : {};

        let mode = String(config.selection_mode || 'multiple');
        if (mode !== 'single' && mode !== 'multiple') {
            mode = 'multiple';
        }

        let limit = parseInt(config.select_limit, 10);
        if (Number.isNaN(limit) || limit < 0) {
            limit = 0;
        }

        return {
            popup: {
                url: String(popup.url || ''),
                title: String(popup.title || '选择数据'),
                width: String(popup.width || '1000px'),
                height: String(popup.height || '650px'),
                table_id: String(popup.table_id || '')
            },
            unique_key: String(config.unique_key || 'id'),
            selection_mode: mode,
            select_limit: limit,
            extra_fields: normalizeExtraFields(config.extra_fields || []),
            empty_text: String(config.empty_text || '暂无已选数据，请点击“选择数据”')
        };
    }

    function getSubmitKeys(columns, config) {
        const keyMap = {};

        columns.forEach(function (column) {
            if (column && column.key) {
                keyMap[String(column.key)] = true;
            }
        });

        (config.extra_fields || []).forEach(function (field) {
            keyMap[String(field)] = true;
        });

        return Object.keys(keyMap);
    }

    function buildInputsHtml(name, submitKeys, rows) {
        let html = '';

        rows.forEach(function (row, rowIndex) {
            submitKeys.forEach(function (fieldKey) {
                const fieldName = name + '[' + rowIndex + '][' + fieldKey + ']';
                const value = row[fieldKey] == null ? '' : row[fieldKey];
                html += '<input type="hidden" name="' + escapeHtml(fieldName) + '" value="' + escapeHtml(value) + '">';
            });
        });

        return html;
    }

    function buildRowsHtml(columns, rows) {
        let html = '';

        rows.forEach(function (row, rowIndex) {
            html += '<tr data-row-index="' + rowIndex + '">';
            columns.forEach(function (column) {
                const value = row[column.key] == null ? '' : row[column.key];
                html += '<td data-col-key="' + escapeHtml(column.key) + '">' + escapeHtml(value) + '</td>';
            });
            html += '<td class="dp-select-table-op-col"><button type="button" class="btn btn-sm btn-outline-danger js-select-table-delete" data-row-index="' + rowIndex + '">移除</button></td>';
            html += '</tr>';
        });

        return html;
    }

    function getUniqueRowKey(row, config) {
        const key = config.unique_key || 'id';
        if (row[key] != null && String(row[key]) !== '') {
            return String(row[key]);
        }
        if (row.dp_pk != null && String(row.dp_pk) !== '') {
            return String(row.dp_pk);
        }
        return JSON.stringify(row);
    }

    function mergeRows(rows, config) {
        const uniqueMap = {};
        const result = [];

        rows.forEach(function (row) {
            const uniqueRowKey = getUniqueRowKey(row, config);
            if (uniqueMap[uniqueRowKey]) {
                return;
            }
            uniqueMap[uniqueRowKey] = true;
            result.push(row);
        });

        if (config.selection_mode === 'single') {
            return result.slice(0, 1);
        }

        if (config.select_limit > 0) {
            return result.slice(0, config.select_limit);
        }

        return result;
    }

    function detectPopupTableId(iframeWindow, config) {
        if (config.popup && config.popup.table_id) {
            return config.popup.table_id;
        }

        const doc = iframeWindow.document;
        if (!doc) {
            return '';
        }

        const tableNode = doc.querySelector('.dp-table table[id]') || doc.querySelector('table[id]');
        return tableNode ? String(tableNode.getAttribute('id') || '') : '';
    }

    function collectPopupSelectedRows(layerIndex, config) {
        const iframeWindow = window['layui-layer-iframe' + layerIndex];
        if (!iframeWindow) {
            window.layer.msg('无法获取弹窗窗口，请稍后重试');
            return null;
        }

        if (typeof iframeWindow.dpSelectTableGetData === 'function') {
            const customRows = iframeWindow.dpSelectTableGetData();
            return normalizeRows(customRows);
        }

        if (!iframeWindow.layui || !iframeWindow.layui.table || typeof iframeWindow.layui.table.checkStatus !== 'function') {
            window.layer.msg('弹窗页面未找到可读取的表格实例，可在弹窗页定义 dpSelectTableGetData() 返回选中数据');
            return null;
        }

        const tableId = detectPopupTableId(iframeWindow, config);
        if (!tableId) {
            window.layer.msg('未检测到弹窗表格ID，请通过 popup.table_id 指定');
            return null;
        }

        const checkStatus = iframeWindow.layui.table.checkStatus(tableId);
        const rows = checkStatus && Array.isArray(checkStatus.data) ? checkStatus.data : [];
        return normalizeRows(rows);
    }

    function renderWidget($widget, columns, rows, config) {
        const submitKeys = getSubmitKeys(columns, config);
        const bodyHtml = buildRowsHtml(columns, rows);
        $widget.find('.js-select-table-body').html(bodyHtml);
        $widget.find('.js-select-table-inputs').html(buildInputsHtml($widget.data('name'), submitKeys, rows));

        if (rows.length > 0) {
            $widget.find('.js-select-table-empty').hide();
        } else {
            $widget.find('.js-select-table-empty').text(config.empty_text).show();
        }
    }

    function initWidget($widget) {
        const columns = normalizeColumns(parseJsonFromScript($widget, '.js-select-table-columns', []));
        let rows = normalizeRows(parseJsonFromScript($widget, '.js-select-table-rows', []));
        const config = normalizeConfig(parseJsonFromScript($widget, '.js-select-table-config', {}));

        rows = mergeRows(rows, config);
        renderWidget($widget, columns, rows, config);

        $widget.on('click', '.js-select-table-delete', function () {
            const rowIndex = parseInt($(this).data('row-index'), 10);
            if (Number.isNaN(rowIndex)) {
                return;
            }

            rows.splice(rowIndex, 1);
            rows = mergeRows(rows, config);
            renderWidget($widget, columns, rows, config);
        });

        $widget.on('click', '.js-select-table-open', function () {
            if (!config.popup || !config.popup.url) {
                window.layer.msg('请先配置 popup.url');
                return;
            }

            if (!window.layer || typeof window.layer.open !== 'function') {
                window.layer.msg('弹窗组件未加载');
                return;
            }

            window.layer.open({
                type: 2,
                title: config.popup.title,
                area: [config.popup.width, config.popup.height],
                content: config.popup.url,
                btn: ['确定', '取消'],
                yes: function (index) {
                    const selectedRows = collectPopupSelectedRows(index, config);
                    if (selectedRows === null) {
                        return;
                    }

                    if (selectedRows.length === 0) {
                        window.layer.msg('请先勾选要选择的数据');
                        return;
                    }

                    if (config.selection_mode === 'single' && selectedRows.length > 1) {
                        window.layer.msg('当前为单选模式，只能选择一条数据');
                        return;
                    }

                    if (config.selection_mode !== 'single' && config.select_limit > 0 && selectedRows.length > config.select_limit) {
                        window.layer.msg('最多只能选择 ' + config.select_limit + ' 条数据');
                        return;
                    }

                    rows = mergeRows(selectedRows, config);
                    renderWidget($widget, columns, rows, config);
                    window.layer.close(index);
                }
            });
        });
    }

    $(function () {
        $('.js-select-table').each(function () {
            initWidget($(this));
        });
    });
}(jQuery));
