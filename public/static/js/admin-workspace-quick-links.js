(function ($, window, document) {
    'use strict';

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function WorkspaceQuickLinks(root) {
        this.root = root;
        this.state = this.parseState(root.getAttribute('data-state') || '{}');
        this.grid = root.querySelector('[data-dp-quick-links-grid]');
        this.empty = root.querySelector('[data-dp-quick-links-empty]');
        this.host = root.closest('.dp-home-tab-pane');
        this.shell = this.host ? this.host.querySelector('[data-dp-quick-links-shell]') : null;
        this.panel = this.host ? this.host.querySelector('[data-dp-quick-links-panel]') : null;
        this.overlay = this.host ? this.host.querySelector('[data-dp-quick-links-overlay]') : null;
        this.closeButton = this.host ? this.host.querySelector('[data-dp-quick-links-panel-close]') : null;
        this.candidates = this.panel ? this.panel.querySelector('[data-dp-quick-links-candidates]') : null;
        this.search = this.panel ? this.panel.querySelector('[data-dp-quick-links-search]') : null;
        this.openButtons = this.host ? this.host.querySelectorAll('[data-action="open-panel"]') : [];
        this.clearButtons = this.host ? this.host.querySelectorAll('[data-action="clear-all"]') : [];
        this.toggleEditButtons = this.host ? this.host.querySelectorAll('[data-action="toggle-edit"]') : [];
        this.lastTriggerButton = null;
        this.isPanelOpen = false;
        this.isEditing = false;
        this.sortable = null;

        if (!this.grid || !this.empty || !this.host || !this.shell || !this.panel || !this.overlay || !this.closeButton || !this.candidates || !this.search) {
            return;
        }

        this.bind();
        this.syncPanelState();
        this.render();
    }

    WorkspaceQuickLinks.prototype.parseState = function (raw) {
        try {
            return JSON.parse(raw);
        } catch (error) {
            return {
                links: [],
                candidates: [],
                count: 0,
                limit: 12,
                is_empty: true
            };
        }
    };

    WorkspaceQuickLinks.prototype.bind = function () {
        var self = this;

        Array.prototype.forEach.call(this.openButtons, function (button) {
            button.addEventListener('click', function (event) {
                event.preventDefault();
                self.openPanel(button);
            });
        });

        Array.prototype.forEach.call(this.toggleEditButtons, function (button) {
            button.addEventListener('click', function () {
                self.toggleEditMode();
            });
        });

        Array.prototype.forEach.call(this.clearButtons, function (button) {
            button.addEventListener('click', function () {
                self.requestClearAll();
            });
        });

        this.overlay.addEventListener('click', function () {
            self.closePanel();
        });

        this.closeButton.addEventListener('click', function () {
            self.closePanel();
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && self.isPanelOpen) {
                self.closePanel();
            }
        });

        this.search.addEventListener('input', function () {
            self.renderCandidates(this.value || '');
        });

        this.grid.addEventListener('click', function (event) {
            var removeButton = event.target.closest('[data-dp-quick-link-remove]');
            var link = event.target.closest('[data-dp-quick-link-target]');

            if (removeButton) {
                event.preventDefault();
                self.requestRemove(removeButton.getAttribute('data-key'));
                return;
            }

            if (link) {
                event.preventDefault();

                if (self.isEditing) {
                    return;
                }

                self.openQuickLink(link);
            }
        });

        this.candidates.addEventListener('click', function (event) {
            var button = event.target.closest('[data-action]');
            var action;

            if (!button || button.disabled) {
                return;
            }

            action = button.getAttribute('data-action');
            if (action === 'add') {
                self.requestAdd(button.getAttribute('data-key'));
                return;
            }

            if (action === 'remove') {
                self.requestRemove(button.getAttribute('data-key'));
            }
        });
    };

    WorkspaceQuickLinks.prototype.requestAdd = function (key) {
        var self = this;

        Dolphin.ajax(DolphinConfig.url.addWorkspaceQuickLink, 'POST', {
            key: key
        }, {
            success: function (res) {
                if (res.code !== 1) {
                    return false;
                }

                self.updateState(res.data);
            }
        });
    };

    WorkspaceQuickLinks.prototype.requestRemove = function (key) {
        var self = this;

        Dolphin.ajax(DolphinConfig.url.removeWorkspaceQuickLink, 'POST', {
            key: key
        }, {
            success: function (res) {
                if (res.code !== 1) {
                    return false;
                }

                self.updateState(res.data);
            }
        });
    };

    WorkspaceQuickLinks.prototype.requestClearAll = function () {
        var self = this;

        Dolphin.confirm('确认清空当前快捷入口吗？', '清空后当前账号的快捷入口会立即移除。', function () {
            Dolphin.ajax(DolphinConfig.url.clearWorkspaceQuickLinks, 'POST', {}, {
                success: function (res) {
                    if (res.code !== 1) {
                        return false;
                    }

                    self.isEditing = false;
                    self.updateState(res.data);
                }
            });
        }, {
            confirmButtonText: '确认清空',
            confirmButtonColor: '#d63939'
        });
    };

    WorkspaceQuickLinks.prototype.openQuickLink = function (link) {
        var item;

        if (!link || this.isEditing) {
            return false;
        }

        item = {
            title: link.getAttribute('data-title') || '',
            url: link.getAttribute('data-url') || link.getAttribute('href') || '',
            iframe_url: link.getAttribute('data-iframe-url') || link.getAttribute('href') || '',
            icon: link.getAttribute('data-icon') || 'ti ti-point',
            app_name: link.getAttribute('data-app-name') || '',
            is_workspace: link.getAttribute('data-is-workspace') === '1'
        };

        if (window.Dolphin && typeof Dolphin.openAdminMenuItem === 'function') {
            return Dolphin.openAdminMenuItem(item);
        }

        if (item.url) {
            window.location.href = item.url;
            return true;
        }

        return false;
    };

    WorkspaceQuickLinks.prototype.focusSearchWithoutScroll = function () {
        var self = this;
        var focus = function () {
            var hostScrollTop;
            var hostScrollLeft;

            if (!self.isPanelOpen || !self.search) {
                return;
            }

            hostScrollTop = self.host ? self.host.scrollTop : 0;
            hostScrollLeft = self.host ? self.host.scrollLeft : 0;

            try {
                self.search.focus({
                    preventScroll: true
                });
            } catch (error) {
                self.search.focus();

                if (self.host) {
                    self.host.scrollTop = hostScrollTop;
                    self.host.scrollLeft = hostScrollLeft;
                }
            }
        };

        if (typeof window.requestAnimationFrame === 'function') {
            window.requestAnimationFrame(focus);
            return;
        }

        window.setTimeout(focus, 0);
    };

    WorkspaceQuickLinks.prototype.syncPanelState = function () {
        var expanded = this.isPanelOpen ? 'true' : 'false';

        this.host.classList.toggle('is-quick-links-panel-open', this.isPanelOpen);
        this.panel.setAttribute('aria-hidden', this.isPanelOpen ? 'false' : 'true');
        this.shell.setAttribute('aria-hidden', this.isPanelOpen ? 'false' : 'true');

        Array.prototype.forEach.call(this.openButtons, function (button) {
            button.setAttribute('aria-expanded', expanded);
        });

        if (this.isPanelOpen) {
            this.focusSearchWithoutScroll();
        }
    };

    WorkspaceQuickLinks.prototype.openPanel = function (triggerButton) {
        if (triggerButton) {
            this.lastTriggerButton = triggerButton;
        }

        if (this.isPanelOpen) {
            return;
        }

        this.isPanelOpen = true;
        this.syncPanelState();
    };

    WorkspaceQuickLinks.prototype.restoreFocusBeforeClose = function () {
        var activeElement = document.activeElement;

        if (!activeElement || activeElement === document.body) {
            return;
        }

        if (!this.panel.contains(activeElement) && !this.shell.contains(activeElement)) {
            return;
        }

        if (this.lastTriggerButton && document.body.contains(this.lastTriggerButton) && typeof this.lastTriggerButton.focus === 'function') {
            try {
                this.lastTriggerButton.focus({
                    preventScroll: true
                });
                return;
            } catch (error) {
                this.lastTriggerButton.focus();
                return;
            }
        }

        if (typeof activeElement.blur === 'function') {
            activeElement.blur();
        }
    };

    WorkspaceQuickLinks.prototype.closePanel = function () {
        if (!this.isPanelOpen) {
            return;
        }

        this.restoreFocusBeforeClose();
        this.isPanelOpen = false;
        this.syncPanelState();
    };

    WorkspaceQuickLinks.prototype.toggleEditMode = function (force) {
        this.isEditing = typeof force === 'boolean' ? force : !this.isEditing;
        this.syncEditState();
    };

    WorkspaceQuickLinks.prototype.syncEditState = function () {
        var editing = this.isEditing ? 'true' : 'false';
        var hasLinks = Number(this.state.count || 0) > 0;

        this.root.classList.toggle('is-editing', this.isEditing);

        Array.prototype.forEach.call(this.toggleEditButtons, function (button) {
            var defaultText = button.getAttribute('data-default-text') || '整理';
            var editingText = button.getAttribute('data-editing-text') || '完成';
            var icon = this.isEditing ? 'ti ti-check' : 'ti ti-edit';

            button.innerHTML = '<i class="' + icon + ' me-1"></i>' + (this.isEditing ? editingText : defaultText);
            button.setAttribute('aria-pressed', editing);
        }, this);

        Array.prototype.forEach.call(this.clearButtons, function (button) {
            button.classList.toggle('d-none', !this.isEditing || !hasLinks);
        }, this);

        this.syncSortable();
    };

    WorkspaceQuickLinks.prototype.syncSortable = function () {
        var self = this;

        if (typeof window.Sortable === 'undefined' || !this.grid) {
            return;
        }

        if (this.sortable) {
            this.sortable.destroy();
            this.sortable = null;
        }

        if (!this.isEditing) {
            return;
        }

        this.sortable = window.Sortable.create(this.grid, {
            animation: 150,
            ghostClass: 'dp-quick-links-sortable-ghost',
            handle: '[data-dp-quick-link-handle]',
            onEnd: function () {
                var keys = Array.prototype.map.call(
                    self.grid.querySelectorAll('[data-dp-quick-link-item]'),
                    function (item) {
                        return item.getAttribute('data-key');
                    }
                );

                Dolphin.ajax(DolphinConfig.url.sortWorkspaceQuickLinks, 'POST', {
                    keys: keys
                }, {
                    showDefaultMsg: false,
                    success: function (res) {
                        if (res.code !== 1) {
                            return false;
                        }

                        self.updateState(res.data);
                    }
                });
            }
        });
    };

    WorkspaceQuickLinks.prototype.updateState = function (state) {
        this.state = state || {
            links: [],
            candidates: [],
            count: 0,
            limit: 12,
            is_empty: true
        };
        this.root.setAttribute('data-state', JSON.stringify(this.state));
        this.render();
    };

    WorkspaceQuickLinks.prototype.renderGrid = function () {
        var html = (this.state.links || []).map(function (item) {
            return ''
                + '<div class="dp-workspace-quick-link" data-dp-quick-link-item data-key="' + escapeHtml(item.key) + '">'
                + '<button type="button" class="dp-workspace-quick-link-handle" data-dp-quick-link-handle aria-label="拖拽排序 ' + escapeHtml(item.title) + '">'
                + '<i class="ti ti-grip-vertical"></i>'
                + '</button>'
                + '<button type="button" class="dp-workspace-quick-link-remove" data-dp-quick-link-remove data-key="' + escapeHtml(item.key) + '" aria-label="移除 ' + escapeHtml(item.title) + '">'
                + '<i class="ti ti-x"></i>'
                + '</button>'
                + '<a class="dp-workspace-quick-link-target" href="' + escapeHtml(item.url) + '" data-dp-quick-link-target'
                + ' data-title="' + escapeHtml(item.title) + '"'
                + ' data-url="' + escapeHtml(item.url) + '"'
                + ' data-iframe-url="' + escapeHtml(item.iframe_url || item.url) + '"'
                + ' data-icon="' + escapeHtml(item.icon || 'ti ti-point') + '"'
                + ' data-app-name="' + escapeHtml(item.app_name || '') + '"'
                + ' data-is-workspace="' + (item.is_workspace ? '1' : '0') + '">'
                + '<span class="dp-workspace-quick-link-body">'
                + '<span class="dp-workspace-quick-link-icon"><i class="' + escapeHtml(item.icon || 'ti ti-point') + '"></i></span>'
                + '<span class="dp-workspace-quick-link-title">' + escapeHtml(item.title) + '</span>'
                + '</span>'
                + '</a>'
                + '</div>';
        });

        this.grid.innerHTML = html.join('');
    };

    WorkspaceQuickLinks.prototype.renderCandidates = function (keyword) {
        var selectedMap = (this.state.links || []).reduce(function (carry, item) {
            carry[item.key] = true;
            return carry;
        }, {});
        var reachedLimit = Number(this.state.count || 0) >= Number(this.state.limit || 12);
        var query = String(keyword || '').trim().toLowerCase();
        var html = (this.state.candidates || []).filter(function (item) {
            if (query === '') {
                return true;
            }

            return String(item.title || '').toLowerCase().indexOf(query) !== -1;
        }).map(function (item) {
            var isSelected = !!selectedMap[item.key];
            var disabled = !isSelected && reachedLimit;
            var buttonHtml;

            if (isSelected) {
                buttonHtml = '<button type="button" class="btn btn-sm btn-outline-danger" data-action="remove" data-key="' + escapeHtml(item.key) + '">移除</button>';
            } else {
                buttonHtml = '<button type="button" class="btn btn-sm btn-primary" data-action="add" data-key="' + escapeHtml(item.key) + '"' + (disabled ? ' disabled' : '') + '>' + (disabled ? '已满' : '加入') + '</button>';
            }

            return ''
                + '<div class="dp-quick-links-candidate-item">'
                + '<div class="dp-quick-links-candidate-item-main">'
                + '<span class="text-secondary"><i class="' + escapeHtml(item.icon || 'ti ti-point') + '"></i></span>'
                + '<span class="dp-quick-links-candidate-item-title">' + escapeHtml(item.title) + '</span>'
                + '</div>'
                + buttonHtml
                + '</div>';
        }).join('');

        if (html === '') {
            html = '<div class="dp-quick-links-empty-state">没有可添加的菜单</div>';
        }

        this.candidates.innerHTML = html;
    };

    WorkspaceQuickLinks.prototype.render = function () {
        this.empty.classList.toggle('d-none', !(this.state.is_empty));
        this.renderGrid();
        this.renderCandidates(this.search.value || '');
        this.syncEditState();
    };

    $(function () {
        $('[data-dp-workspace-quick-links]').each(function () {
            new WorkspaceQuickLinks(this);
        });
    });
}(jQuery, window, window.document));
