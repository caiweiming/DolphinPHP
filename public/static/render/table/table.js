// 表格对象集合
const DolphinTable = {};
window.DolphinTable = DolphinTable;

// ============ 表格事件系统：统一注册 API ============

// 事件存储结构
const TableEventHandlers = {
    global: {
        done: {},       // { handlerId: callback }
        expand: {},
        collapse: {}
    },
    specific: {
        done: {},       // { tableId: { handlerId: callback } }
        expand: {},
        collapse: {}
    }
};

/**
 * 统一的表格事件注册函数
 * @param {string} tableId - 表格ID，为空则注册为全局回调
 * @param {string} event - 事件名称：'done' | 'expand' | 'collapse'
 * @param {string} handlerId - 处理器唯一标识（用于自动去重）
 * @param {function} callback - 回调函数 (table, tableId, ...args) => void
 */
window.DolphinTableOn = function(tableId, event, handlerId, callback) {
    // 验证事件类型
    if (!['done', 'expand', 'collapse'].includes(event)) {
        console.error('[DolphinTableOn] 未知的事件类型:', event);
        return;
    }

    // 判断是全局回调还是表格特定回调
    if (!tableId || tableId === '') {
        // 全局回调
        TableEventHandlers.global[event][handlerId] = callback;
    } else {
        // 表格特定回调
        if (!TableEventHandlers.specific[event][tableId]) {
            TableEventHandlers.specific[event][tableId] = {};
        }
        TableEventHandlers.specific[event][tableId][handlerId] = callback;
    }
};

/**
 * 触发表格事件（内部使用）
 * @param {string} event - 事件名称
 * @param {object} table - 表格对象
 * @param {string} tableId - 表格ID
 * @param  {...any} args - 其他参数
 */
function triggerTableEvent(event, table, tableId, ...args) {
    // 1. 触发全局回调
    const globalHandlers = TableEventHandlers.global[event] || {};
    Object.values(globalHandlers).forEach(callback => {
        callback(table, tableId, ...args);
    });

    // 2. 触发表格特定回调
    const specificHandlers = TableEventHandlers.specific[event][tableId] || {};
    Object.values(specificHandlers).forEach(callback => {
        callback(table, tableId, ...args);
    });
}

// 记录每个表格的事件绑定状态，避免重复绑定
const TableBindingState = {};
function getTableBindingState(tableId) {
    if (!TableBindingState[tableId]) {
        TableBindingState[tableId] = {
            editBound: false,
            toolbarBound: false,
            searchBound: false
        };
    }
    return TableBindingState[tableId];
}

$(document).ready(function () {
    // 初始化表格
    $('.dp-table').each(function () {
        const $container = $(this);
        let $table = $(this).find('table');
        if ($table.length > 0) {
            layui.use(function () {
                const tableId = $table.attr('id');
                const options = $table.data('options');
                const isTree = $table.data('tree');
                const table = isTree ? layui.treeTable : layui.table;
                const crudToken = options['crudToken'] || '';
                const searchParam = options['searchParam'] || '_s';
                const bindingState = getTableBindingState(tableId);
                const baseWhere = $.extend({}, options.where || {});

                options['elem'] = '#' + tableId;

                if (typeof window[options['page']['limitTemplet']] === "function") {
                    options['page']['limitTemplet'] = window[options['page']['limitTemplet']];
                }

                // 获取当前行数据
                table.getRowData = function (elem) {
                    const index = $(elem).closest('tr').data('index');
                    return isTree ? (table.getNodeDataByIndex(tableId, index) || {}) : (table.cache[tableId][index] || {});
                };

                // 获取选中行数据
                table.getCheckData = function (key) {
                    const checkStatus = table.checkStatus(tableId)
                    const data = checkStatus.data;
                    if (key) {
                        return data.filter(item => item?.[key] !== undefined).map(item => item[key]);
                    }
                    return data;
                }

                // 获取当前页数据
                table.getPageData = function () {
                    return table.getData(tableId);
                }

                // 单元格编辑事件（同一 tableId 仅绑定一次）
                if (!bindingState.editBound) {
                    bindingState.editBound = true;
                    table.on('edit(' + tableId + ')', function (obj) {
                        const field = obj.field;
                        const value = obj.value;
                        const oldValue = obj.oldValue;
                        const id = obj.data['dp_pk'];

                        Dolphin.loading('处理中...');

                        // 提交更新请求
                        const requestData = {
                            id: id,
                            field: field,
                            value: value
                        };
                        if (crudToken) {
                            requestData._t = crudToken;
                        }

                        $.ajax({
                            url: DolphinConfig.url.quickEdit,
                            type: 'POST',
                            data: requestData,
                            dataType: 'json',
                            success: function (res) {
                                Dolphin.loading('hide')

                                if (res.code === 1) {
                                    // 更新当前缓存数据
                                    let update = {};
                                    update[field] = value;
                                    obj.update(update, true);
                                } else {
                                    Dolphin.error(res.msg || '修改失败');

                                    // 恢复旧值
                                    let rollback = {};
                                    rollback[field] = oldValue;
                                    obj.update(rollback, true);
                                }
                            },
                            error: function (xhr, status, error) {
                                Dolphin.loading('hide');
                                Dolphin.error('网络错误，修改失败');

                                // 恢复旧值
                                let rollback = {};
                                rollback[field] = oldValue;
                                obj.update(rollback, true);
                            }
                        });
                    });
                }

                const withCrudToken = function (sourceUrl) {
                    if (!sourceUrl || !crudToken || sourceUrl.indexOf('_t=') > -1) {
                        return sourceUrl;
                    }
                    if (/^https?:\/\//i.test(sourceUrl)) {
                        try {
                            const u = new URL(sourceUrl, window.location.origin);
                            if (u.host !== window.location.host) {
                                return sourceUrl;
                            }
                        } catch (e) {
                            return sourceUrl;
                        }
                    }
                    return sourceUrl + (sourceUrl.indexOf('?') > -1 ? '&' : '?') + '_t=' + encodeURIComponent(crudToken);
                };

                // 工具栏事件（同一 tableId 仅绑定一次）
                if (!bindingState.toolbarBound) {
                    bindingState.toolbarBound = true;
                    table.on('toolbar(' + tableId + ')', function (obj) {
                    const self = $(this);
                    let url = self.data('url');
                    const confirm = self.data('confirm');
                    const ajaxOptions = self.data('ajax');
                    const popOptions = self.data('pop');
                    const onClick = self.data('click') ?? '';
                    const field = self.data('field') || '';
                    const param = self.data('param') || 'ids';
                    const target = self.data('target') || '_self';

                    // 处理需要选择数据的情况
                    const processSelectedData = () => {
                        if (!field) return true;

                        const checkData = table.getCheckData(field);
                        if (checkData.length === 0) {
                            Dolphin.error('请选择要操作的数据');
                            return false;
                        }

                        if (ajaxOptions) {
                            if (!ajaxOptions.data) ajaxOptions.data = {};
                            ajaxOptions.data[param] = checkData;
                        }

                        if (ajaxOptions && crudToken) {
                            if (!ajaxOptions.data) ajaxOptions.data = {};
                            ajaxOptions.data._t = crudToken;
                        }

                        return true;
                    };

                    // 执行确认后的操作
                    const executeAction = () => {
                        // 内置事件处理
                        if (popOptions) {
                            url = withCrudToken(url);
                            url += (url.indexOf('?') > -1 ? '&' : '?') + '_pop=1';
                            Dolphin.pop(url, '', popOptions);
                        } else if (ajaxOptions) {
                            if (crudToken) {
                                if (!ajaxOptions.data) ajaxOptions.data = {};
                                ajaxOptions.data._t = crudToken;
                            }
                            if (processSelectedData()) {
                                ajaxOptions.success = function (res) {
                                    if (res.code) {
                                        table.reloadData(tableId);
                                    }
                                }
                                Dolphin.ajax(url, '', {}, ajaxOptions);
                            }
                        } else if (obj.event === 'expand') {
                            // 展开所有节点
                            table.expandAll(tableId, true);
                            setTimeout(function () {
                                triggerTableEvent('expand', table, tableId, null);
                            }, 10)
                        } else if (obj.event === 'collapse') {
                            // 收起所有节点
                            table.expandAll(tableId, false);
                            setTimeout(function () {
                                triggerTableEvent('collapse', table, tableId, null);
                            }, 10)
                        } else if (obj.event === 'reload') {
                            table.reloadData(tableId);
                        } else if (url) {
                            url = withCrudToken(url);
                            window.open(url, target);
                        }
                    };

                    // 主逻辑
                    if (onClick && typeof window[onClick] === 'function') {
                        // 使用 onClick 自定义回调
                        window[onClick](table, tableId, obj, self);
                    } else {
                        if (confirm) {
                            Dolphin.confirm(confirm[0] ?? '', confirm[1] ?? '', executeAction);
                        } else {
                            executeAction();
                        }
                    }
                    });
                }

                const bindTreeExpandEvents = function () {
                    if (!isTree) {
                        return;
                    }

                    const selector = '.layui-table-view[lay-id="' + tableId + '"]';
                    const tableView = document.querySelector(selector);

                    if (!tableView || tableView.dataset.treeExpandBound === '1') {
                        return;
                    }

                    // 标记已绑定，避免重复
                    tableView.dataset.treeExpandBound = '1';

                    // 使用事件捕获阶段监听（capture: true），避免被 layui 的 stopPropagation 阻止
                    tableView.addEventListener('click', function (e) {

                        // 判断是展开还是收起
                        const isExpanding = e.target.classList.contains('layui-icon-triangle-r'); // 向右箭头：收起状态，点击后展开
                        const isCollapsing = e.target.classList.contains('layui-icon-triangle-d'); // 向下箭头：展开状态，点击后收起

                        if (isExpanding) {
                            // 延迟执行回调，确保 DOM 已更新
                            setTimeout(function () {
                                // 触发树形表格展开事件
                                triggerTableEvent('expand', table, tableId, e.target);
                            }, 10);
                        } else if (isCollapsing) {
                            // 延迟执行回调，确保 DOM 已更新
                            setTimeout(function () {
                                // 触发树形表格收起事件
                                triggerTableEvent('collapse', table, tableId, e.target);
                            }, 10);
                        }
                    }, true);
                };

                const collectSearchWhere = function ($form) {
                    const where = $.extend({}, baseWhere);
                    delete where[searchParam];

                    const searchMap = {};
                    const formData = $form.serializeArray();

                    formData.forEach(function (item) {
                        const name = item.name;
                        if (!name) {
                            return;
                        }

                        const value = typeof item.value === 'string' ? item.value.trim() : item.value;
                        if (value === '') {
                            return;
                        }

                        searchMap[name] = value;
                    });

                    if (Object.keys(searchMap).length > 0) {
                        where[searchParam] = searchMap;
                    }

                    return where;
                };

                const initSearchDateInputs = function ($form) {
                    if (!layui.laydate) {
                        return;
                    }

                    $form.find('.dp-table-search-date').each(function () {
                        const $input = $(this);
                        if ($input.data('laydate-initialized')) {
                            return;
                        }

                        layui.laydate.render({
                            elem: this,
                            trigger: 'click',
                            range: $input.data('range') === true || $input.data('range') === 'true',
                            format: $input.data('format') || 'yyyy-MM-dd'
                        });
                        $input.data('laydate-initialized', true);
                    });
                };

                const bindSearchForm = function () {
                    if (bindingState.searchBound) {
                        return;
                    }

                    const $searchForm = $container.find('form.dp-table-search-form[data-table-id="' + tableId + '"]');
                    if ($searchForm.length === 0) {
                        return;
                    }

                    bindingState.searchBound = true;
                    initSearchDateInputs($searchForm);

                    if (layui.form) {
                        layui.form.render('select');
                    }

                    $searchForm.on('submit', function (e) {
                        e.preventDefault();
                        const where = collectSearchWhere($searchForm);
                        table.reloadData(tableId, {
                            page: {curr: 1},
                            where: where
                        });
                    });

                    $searchForm.on('reset', function () {
                        setTimeout(function () {
                            const where = $.extend({}, baseWhere);
                            delete where[searchParam];

                            table.reloadData(tableId, {
                                page: {curr: 1},
                                where: where
                            });
                        }, 0);
                    });
                };

                const userDone = options.done;
                options.done = function (res, curr, count, origin) {
                    if (typeof userDone === 'function') {
                        userDone.call(this, res, curr, count, origin);
                    }
                    const self = this;
                    // 触发表格加载完成事件
                    triggerTableEvent('done', table, tableId, self);
                    bindTreeExpandEvents();
                }

                // 处理右侧工具栏
                if (options['defaultToolbar'] && options['defaultToolbar'].length > 0) {
                    options['defaultToolbar'].forEach(function (item) {
                        if (item['onClick'] && typeof window[item['onClick']] === 'function') {
                            // 将回调函数添加到表格对象中，并往函数中，带入参数 table
                            item['onClick'] = window[item['onClick']];
                        } else {
                            delete item['onClick'];
                        }
                    })
                }

                table.render(options);
                DolphinTable[tableId] = table;
                bindTreeExpandEvents();
                bindSearchForm();
            });
        }
    });
});
