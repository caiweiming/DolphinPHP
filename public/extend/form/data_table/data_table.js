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
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function normalizeRows(rows) {
        if (!Array.isArray(rows)) {
            return [];
        }
        return rows.filter(function (row) {
            return row && typeof row === 'object' && !Array.isArray(row);
        });
    }

    function normalizeConfig(config) {
        if (!config || typeof config !== 'object' || Array.isArray(config)) {
            config = {};
        }

        return {
            confirm_delete: !!config.confirm_delete,
            confirm_title: config.confirm_title || '确认删除',
            confirm_text: config.confirm_text || '确定要删除这一行吗？',
            confirm_confirm_text: config.confirm_confirm_text || '确认',
            confirm_cancel_text: config.confirm_cancel_text || '取消',
            confirm_status: config.confirm_status || 'warning',
            sortable: config.sortable === true,
            sort_notify: !!config.sort_notify,
            sort_notify_message: config.sort_notify_message || '排序已更新'
        };
    }

    function buildValidationAttrs(column) {
        let attrs = '';

        if (column.required) {
            attrs += ' required data-rule-required="1"';
        }

        if (column.required_message) {
            attrs += ' data-msg-required="' + escapeHtml(column.required_message) + '"';
        }

        if (column.pattern) {
            attrs += ' data-rule-pattern="' + escapeHtml(column.pattern) + '"';
        }

        if (column.pattern_message) {
            attrs += ' data-msg-pattern="' + escapeHtml(column.pattern_message) + '"';
        }

        return attrs;
    }

    function createCell(name, rowIndex, column, value) {
        const fieldName = name + '[' + rowIndex + '][' + column.key + ']';
        const cellValue = value == null ? '' : String(value);
        const placeholder = column.placeholder ? ' placeholder="' + escapeHtml(column.placeholder) + '"' : '';
        const validationAttrs = buildValidationAttrs(column);

        if (column.type === 'select') {
            let options = '';
            if (column.allow_empty !== false) {
                const emptySelected = cellValue === '' ? ' selected' : '';
                options += '<option value=""' + emptySelected + '>' + escapeHtml(column.empty_option_text || '请选择') + '</option>';
            }
            (column.options || []).forEach(function (option) {
                const selected = String(option.value) === cellValue ? ' selected' : '';
                options += '<option value="' + escapeHtml(option.value) + '"' + selected + '>' + escapeHtml(option.label) + '</option>';
            });

            return '<select class="form-select form-select-sm" data-value-field="1" name="' + escapeHtml(fieldName) + '"' + validationAttrs + '>' + options + '</select>';
        }

        if (column.type === 'textarea') {
            return '<textarea class="form-control form-control-sm" data-value-field="1" name="' + escapeHtml(fieldName) + '"' + placeholder + validationAttrs + '>' + escapeHtml(cellValue) + '</textarea>';
        }

        if (column.type === 'switch') {
            const trueValue = String(column.switch_true_value == null ? '1' : column.switch_true_value);
            const checked = cellValue === trueValue ? ' checked' : '';
            const onText = escapeHtml(column.switch_on_text || '开启');
            const offText = escapeHtml(column.switch_off_text || '关闭');

            return '' +
                '<div class="form-check form-switch d-flex align-items-center gap-2 mb-0">' +
                '<input type="checkbox" class="form-check-input" data-value-field="1" name="' + escapeHtml(fieldName) + '" value="' + escapeHtml(trueValue) + '"' + checked + validationAttrs + '>' +
                '<span class="text-secondary small">' + onText + '/' + offText + '</span>' +
                '</div>';
        }

        if (column.type === 'number') {
            return '<input type="number" class="form-control form-control-sm" data-value-field="1" name="' + escapeHtml(fieldName) + '" value="' + escapeHtml(cellValue) + '"' + placeholder + validationAttrs + '>';
        }

        return '<input type="text" class="form-control form-control-sm" data-value-field="1" name="' + escapeHtml(fieldName) + '" value="' + escapeHtml(cellValue) + '"' + placeholder + validationAttrs + '>';
    }

    function buildRowHtml(name, rowIndex, columns, rowData, config) {
        const sortableClass = config.sortable ? ' class="dp-data-table-row-sortable"' : '';
        let html = '<tr' + sortableClass + '>';
        if (config.sortable) {
            html += '<td class="dp-data-table-sort-cell">';
            html += '<button type="button" class="btn btn-sm btn-ghost-secondary dp-data-table-drag-handle" title="拖拽排序">';
            html += '<i class="fa-solid fa-grip-vertical"></i>';
            html += '</button>';
            html += '</td>';
        }

        columns.forEach(function (column) {
            let value = rowData[column.key];
            if ((value == null || value === '') && column.default != null) {
                value = column.default;
            }

            html += '<td data-col-key="' + escapeHtml(column.key) + '">' + createCell(name, rowIndex, column, value) + '</td>';
        });

        html += '<td class="dp-data-table-op-col">';
        html += '<button type="button" class="btn btn-sm btn-outline-danger js-data-table-delete">删除</button>';
        html += '</td>';
        html += '</tr>';

        return html;
    }

    function collectRows($widget, columns) {
        const rows = [];

        $widget.find('.js-data-table-body tr').each(function () {
            const row = {};
            columns.forEach((column) => {
                const $field = getFieldByColumn($(this), column.key);
                row[column.key] = readFieldValue($field);
            });
            rows.push(row);
        });

        return rows;
    }

    function getFieldByColumn($row, key) {
        return $row.find('td[data-col-key="' + key + '"] [data-value-field="1"]').first();
    }

    function readFieldValue($field) {
        if (!$field.length) {
            return '';
        }

        if ($field.is(':checkbox')) {
            return $field.is(':checked') ? String($field.val()) : '';
        }

        return $field.val();
    }

    function reindexFieldNames($widget, columns) {
        const name = $widget.data('name');
        $widget.find('.js-data-table-body tr').each(function (rowIndex) {
            columns.forEach((column) => {
                const $field = getFieldByColumn($(this), column.key);
                if ($field.length) {
                    $field.attr('name', name + '[' + rowIndex + '][' + column.key + ']');
                }
            });
        });
    }

    function showWarning(message) {
        if (window.Dolphin && typeof window.Dolphin.warning === 'function') {
            window.Dolphin.warning(message);
        } else {
            window.alert(message);
        }
    }

    function validateWidget($widget, columns) {
        let firstError = null;

        $widget.find('.is-invalid').removeClass('is-invalid');

        $widget.find('.js-data-table-body tr').each(function (rowIndex) {
            columns.forEach((column) => {
                if (firstError) {
                    return;
                }

                const $field = getFieldByColumn($(this), column.key);
                const rawValue = readFieldValue($field);
                const value = String(rawValue == null ? '' : rawValue).trim();

                if (column.type === 'switch' && column.required) {
                    const trueValue = String(column.switch_true_value == null ? '1' : column.switch_true_value);
                    if (value !== trueValue) {
                        firstError = {
                            message: column.required_message || ('第' + (rowIndex + 1) + '行「' + (column.title || column.key) + '」必须开启'),
                            $field: $field
                        };
                        return;
                    }
                }

                if (column.required && value === '') {
                    firstError = {
                        message: column.required_message || ('第' + (rowIndex + 1) + '行「' + (column.title || column.key) + '」为必填项'),
                        $field: $field
                    };
                    return;
                }

                if (column.pattern && value !== '') {
                    let valid = true;
                    try {
                        valid = new RegExp(column.pattern).test(value);
                    } catch (e) {
                        valid = true;
                    }

                    if (!valid) {
                        firstError = {
                            message: column.pattern_message || ('第' + (rowIndex + 1) + '行「' + (column.title || column.key) + '」格式不正确'),
                            $field: $field
                        };
                    }
                }
            });
        });

        if (!firstError) {
            return true;
        }

        firstError.$field.addClass('is-invalid').focus();
        showWarning(firstError.message);
        return false;
    }

    function destroySortable($widget) {
        const instance = $widget.data('dpDragSortInstance');
        if (instance && typeof instance.destroy === 'function') {
            instance.destroy();
        }
        $widget.removeData('dpDragSortInstance');
    }

    function renderRows($widget, columns, rows, config) {
        const name = $widget.data('name');
        const $tbody = $widget.find('.js-data-table-body');
        const $empty = $widget.find('.js-data-table-empty');

        destroySortable($widget);
        $tbody.empty();

        rows.forEach(function (row, index) {
            $tbody.append(buildRowHtml(name, index, columns, row, config));
        });

        $empty.toggle(rows.length === 0);

        if (config.sortable && typeof window.Sortable !== 'undefined' && rows.length > 1) {
            const sortable = new window.Sortable($tbody.get(0), {
                animation: 150,
                draggable: 'tr',
                handle: '.dp-data-table-drag-handle',
                ghostClass: 'dp-data-table-sort-ghost',
                onEnd: function () {
                    reindexFieldNames($widget, columns);
                    if (config.sort_notify && window.Dolphin && typeof window.Dolphin.notify === 'function') {
                        window.Dolphin.notify(config.sort_notify_message, 'info');
                    }
                }
            });
            $widget.data('dpDragSortInstance', sortable);
        }
    }

    function triggerTargetsCurrentForm(trigger, form) {
        const targetForm = trigger.getAttribute('data-form');
        if (!targetForm) {
            return trigger.closest('form') === form;
        }

        const formName = form.getAttribute('name');
        if (formName && targetForm === formName) {
            return true;
        }

        if (form.classList && form.classList.contains(targetForm)) {
            return true;
        }

        return false;
    }

    function bindAjaxPostValidation($widget, columns) {
        const form = $widget.closest('form').get(0);
        if (!form) {
            return;
        }

        if (!window.__dpDataTableCaptureHandlers) {
            window.__dpDataTableCaptureHandlers = new WeakMap();
        }

        if (window.__dpDataTableCaptureHandlers.has(form)) {
            return;
        }

        const handler = function (event) {
            const trigger = event.target.closest('.dp-ajax-post');
            if (!trigger) {
                return;
            }

            if (!triggerTargetsCurrentForm(trigger, form)) {
                return;
            }

            $(form).find('.js-data-table').each(function () {
                const $dt = $(this);
                const dtColumns = parseJsonFromScript($dt, '.js-data-table-columns', []);
                if (!validateWidget($dt, dtColumns)) {
                    event.preventDefault();
                    event.stopPropagation();
                    event.stopImmediatePropagation();
                }
            });
        };

        document.addEventListener('click', handler, true);
        window.__dpDataTableCaptureHandlers.set(form, handler);
    }

    $(function () {
        $('.js-data-table').each(function () {
            const $widget = $(this);
            const columns = parseJsonFromScript($widget, '.js-data-table-columns', []);
            const rows = normalizeRows(parseJsonFromScript($widget, '.js-data-table-rows', []));
            const config = normalizeConfig(parseJsonFromScript($widget, '.js-data-table-config', {}));

            if (!Array.isArray(columns) || columns.length === 0) {
                return;
            }

            renderRows($widget, columns, rows, config);
            bindAjaxPostValidation($widget, columns);

            $widget.on('click', '.js-data-table-add', function () {
                const currentRows = collectRows($widget, columns);
                currentRows.push({});
                renderRows($widget, columns, currentRows, config);
            });

            $widget.on('click', '.js-data-table-delete', function () {
                const doDelete = () => {
                    $(this).closest('tr').remove();
                    const currentRows = collectRows($widget, columns);
                    renderRows($widget, columns, currentRows, config);
                };

                if (!config.confirm_delete) {
                    doDelete();
                    return;
                }

                if (window.Dolphin && typeof window.Dolphin.modalConfirm === 'function') {
                    window.Dolphin.modalConfirm(
                        config.confirm_title,
                        config.confirm_text,
                        function (modal) {
                            modal.hide();
                            doDelete();
                        },
                        config.confirm_confirm_text,
                        config.confirm_cancel_text,
                        config.confirm_status
                    );
                    return;
                }

                if (window.confirm(config.confirm_text)) {
                    doDelete();
                }
            });

        });
    });
})(jQuery);
