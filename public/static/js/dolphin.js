/*
 *  Document   : dolphin.js
 *  Author     : CaiWeiMing <314013107@qq.com>
 */

jQuery.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': jQuery('meta[name="csrf-token"]').attr('content')
    }
});

// 全局配置
const DolphinOption = {
    toast: {},
    debug: true,
    logLevel: 'info'
};
window.DolphinOption = DolphinOption;

const Logger = {
    levelOrder: ['debug', 'info', 'warn', 'error'],
    shouldLog(level) {
        const configLevel = (window.DolphinOption && window.DolphinOption.logLevel) || 'warn';
        return this.levelOrder.indexOf(level) >= this.levelOrder.indexOf(configLevel);
    },
    debug(msg, ...args) {
        if ((window.DolphinOption && window.DolphinOption.debug) || this.shouldLog('debug')) {
            console.log(msg, ...args);
        }
    },
    info(msg, ...args) {
        if (this.shouldLog('info')) {
            console.info(msg, ...args);
        }
    },
    warn(msg, ...args) {
        if (this.shouldLog('warn')) {
            console.warn(msg, ...args);
        }
    },
    error(msg, ...args) {
        if (this.shouldLog('error')) {
            console.error(msg, ...args);
        }
    }
};

const Dolphin = function ($) {
    "use strict";

    let notify = null;
    let globalIframeManager = null;  // 保存iframe管理器实例
    let globalFavoriteManager = null; // 保存收藏管理器实例

    // =====================================================
    // 模块化标签页管理系统 - OpenSpec: optimize-tabs-initialization
    // =====================================================

    /**
     * 事件管理器 - 统一事件委托和生命周期管理
     * 解决重复事件监听器和内存泄漏问题
     */
    class EventManager {
        constructor() {
            this.boundEvents = new Set();
            this.eventHandlers = new Map();
            this.initialized = false;
            Logger.debug('[EventManager] 初始化完成');
        }

        /**
         * 初始化事件委托系统
         */
        init() {
            if (this.initialized) {
                Logger.warn('[EventManager] 重复初始化被阻止');
                return false;
            }

            this.setupDocumentEventDelegation();
            this.initialized = true;
            Logger.info('[EventManager] 事件委托系统已启动');
            return true;
        }

        /**
         * 设置文档级事件委托
         */
        setupDocumentEventDelegation() {
            // 统一的点击事件委托
            this.bindOnce('document:click', () => {
                document.addEventListener('click', this.handleDocumentClick.bind(this));
            });

            // 统一的右键菜单事件委托
            this.bindOnce('document:contextmenu', () => {
                document.addEventListener('contextmenu', this.handleContextMenu.bind(this));
            });
        }

        /**
         * 处理文档级点击事件
         */
        handleDocumentClick(event) {
            const target = event.target;

            // 标签页点击
            if (target.matches('.nav-link[data-bs-toggle="tab"]') || target.closest('.nav-link[data-bs-toggle="tab"]')) {
                Logger.debug('[EventManager] 标签页点击事件');
                this.handleTabClick(event);
                return;
            }

            // 标签页关闭按钮
            if (target.matches('.btn-close-tab') || target.closest('.btn-close-tab')) {
                Logger.debug('[EventManager] 标签页关闭按钮点击');
                this.handleTabCloseClick(event);
                return;
            }

            // 滚动按钮
            if (target.matches('#tabScrollLeft, #tabScrollRight') || target.closest('#tabScrollLeft, #tabScrollRight')) {
                Logger.debug('[EventManager] 滚动按钮点击');
                this.handleScrollButtonClick(event);
            }
        }

        /**
         * 处理标签页点击
         */
        handleTabClick(event) {
            const tabElement = event.target.closest('.nav-link[data-bs-toggle="tab"]');
            if (!tabElement) return;

            Logger.debug('[EventManager] 横向菜单点击，立即开始加载');

            // 触发标签页激活事件
            this.emit('tab:activate', { tabElement, event });
        }

        /**
         * 处理标签页关闭点击
         */
        handleTabCloseClick(event) {
            event.preventDefault();
            event.stopPropagation();

            const closeBtn = event.target.closest('.btn-close-tab');
            const tabItem = closeBtn.closest('.nav-item');

            this.emit('tab:close', { tabItem, event });
        }

        /**
         * 处理滚动按钮点击
         */
        handleScrollButtonClick(event) {
            const button = event.target.closest('#tabScrollLeft, #tabScrollRight');
            const direction = button.id === 'tabScrollLeft' ? 'left' : 'right';

            this.emit('tab:scroll', { direction, event });
        }

        /**
         * 处理右键菜单事件
         */
        handleContextMenu(event) {
            const tabElement = event.target.closest('.nav-link[data-bs-toggle="tab"]');
            if (!tabElement) return;

            event.preventDefault();
            this.emit('tab:contextmenu', { tabElement, event });
        }

        /**
         * 防重复绑定机制
         */
        bindOnce(eventKey, bindFunction) {
            if (this.boundEvents.has(eventKey)) {
                Logger.debug(`[EventManager] 跳过重复绑定: ${eventKey}`);
                return false;
            }

            bindFunction();
            this.boundEvents.add(eventKey);
            Logger.debug(`[EventManager] 绑定事件: ${eventKey}`);
            return true;
        }

        /**
         * 注册事件处理器
         */
        on(eventName, handler) {
            if (!this.eventHandlers.has(eventName)) {
                this.eventHandlers.set(eventName, []);
            }
            this.eventHandlers.get(eventName).push(handler);
            Logger.debug(`[EventManager] 注册事件处理器: ${eventName}`);
        }

        /**
         * 触发事件
         */
        emit(eventName, data = {}) {
            const handlers = this.eventHandlers.get(eventName);
            if (!handlers || handlers.length === 0) {
                Logger.debug(`[EventManager] 没有找到事件处理器: ${eventName}`);
                return;
            }

            Logger.debug(`[EventManager] 触发事件: ${eventName}`, data);
            handlers.forEach(handler => {
                try {
                    handler(data);
                } catch (error) {
                    Logger.error(`[EventManager] 事件处理器执行错误 ${eventName}:`, error);
                }
            });
        }

        /**
         * 清理所有事件监听器
         */
        destroy() {
            this.boundEvents.clear();
            this.eventHandlers.clear();
            this.initialized = false;
            Logger.info('[EventManager] 事件管理器已销毁');
        }
    }

    /**
     * 标签页管理器 - 标签页生命周期和状态管理
     */
    class TabsManager {
        constructor(eventManager) {
            this.eventManager = eventManager;
            this.activeTabs = new Map();
            this.permanentTabs = new Set();
            this.initialized = false;
            Logger.debug('[TabsManager] 初始化完成');
        }

        /**
         * 初始化标签页管理器
         */
        init() {
            if (this.initialized) {
                Logger.warn('[TabsManager] 重复初始化被阻止');
                return false;
            }

            this.setupEventHandlers();
            this.scanExistingTabs();
            this.initialized = true;
            Logger.info('[TabsManager] 标签页管理器已启动');
            return true;
        }

        /**
         * 设置事件处理器
         */
        setupEventHandlers() {
            this.eventManager.on('tab:activate', ({ tabElement, event }) => {
                this.activate(tabElement);
            });

            this.eventManager.on('tab:close', ({ tabItem, event }) => {
                this.close(tabItem);
            });
        }

        /**
         * 扫描现有标签页
         */
        scanExistingTabs() {
            document.querySelectorAll('.nav-link[data-bs-toggle="tab"]').forEach(tab => {
                const navItem = tab.closest('.nav-item');
                const tabId = this.getTabId(navItem);

                this.activeTabs.set(tabId, {
                    element: navItem,
                    tabLink: tab,
                    title: tab.textContent.trim(),
                    href: tab.getAttribute('href'),
                    permanent: tab.hasAttribute('data-tab-permanent'),
                    lastActive: Date.now()
                });

                if (tab.hasAttribute('data-tab-permanent')) {
                    this.permanentTabs.add(tabId);
                }
            });

            Logger.info(`[TabsManager] 扫描到 ${this.activeTabs.size} 个标签页`);
        }

        /**
         * 创建新标签页
         */
        create(config) {
            const tabId = config.id || this.generateTabId();

            if (this.activeTabs.has(tabId)) {
                Logger.warn(`[TabsManager] 标签页已存在: ${tabId}`);
                return this.activate(this.activeTabs.get(tabId).tabLink);
            }

            const tabData = {
                id: tabId,
                title: config.title,
                href: config.href,
                permanent: config.permanent || false,
                lastActive: Date.now()
            };

            // 创建DOM元素
            const navItem = this.createTabElement(tabData);

            // 添加到管理器
            this.activeTabs.set(tabId, {
                element: navItem,
                tabLink: navItem.querySelector('.nav-link'),
                ...tabData
            });

            if (config.permanent) {
                this.permanentTabs.add(tabId);
            }

            // 触发事件
            this.eventManager.emit('tab:created', { tabId, tabData });

            Logger.info(`[TabsManager] 创建标签页: ${tabId}`);
            return tabId;
        }

        /**
         * 激活标签页
         */
        activate(tabElement) {
            if (!tabElement) return false;

            const navItem = tabElement.closest('.nav-item');
            const tabId = this.getTabId(navItem);

            // 清除所有活跃状态
            document.querySelectorAll('.nav-link.active').forEach(link => {
                link.classList.remove('active');
                link.setAttribute('aria-selected', 'false');
            });

            // 激活当前标签
            tabElement.classList.add('active');
            tabElement.setAttribute('aria-selected', 'true');

            // 更新最后活跃时间
            if (this.activeTabs.has(tabId)) {
                this.activeTabs.get(tabId).lastActive = Date.now();
            }

            // 处理暂停状态恢复
            if (tabElement.classList.contains('iframe-paused')) {
                this.resumePausedTab(tabElement);
            }

            // 显示对应内容面板
            this.showTabContent(tabElement);

            // 触发事件
            this.eventManager.emit('tab:activated', { tabId, tabElement });

            Logger.info(`[TabsManager] 激活标签页: ${tabId}`);
            return true;
        }

        /**
         * 关闭标签页
         */
        close(tabItem) {
            if (!tabItem) return false;

            const tabId = this.getTabId(tabItem);
            const tabData = this.activeTabs.get(tabId);

            // 检查是否为永久标签
            if (this.isPermanent(tabId)) {
                Logger.warn(`[TabsManager] 尝试关闭永久标签页: ${tabId}`);
                Dolphin.warning('此标签页无法关闭');
                return false;
            }

            // 检查最小标签数量
            if (!this.checkMinTabCount()) {
                return false;
            }

            // 如果关闭的是当前活跃标签，激活相邻标签
            const tabLink = tabData.tabLink;
            if (tabLink.classList.contains('active')) {
                this.activateAdjacentTab(tabItem);
            }

            // 移除DOM元素
            tabItem.remove();

            // 从管理器中移除
            this.activeTabs.delete(tabId);
            this.permanentTabs.delete(tabId);

            // 触发事件
            this.eventManager.emit('tab:closed', { tabId });

            Logger.info(`[TabsManager] 关闭标签页: ${tabId}`);
            return true;
        }

        /**
         * 获取当前活跃标签页
         */
        getActive() {
            const activeLink = document.querySelector('.nav-link.active[data-bs-toggle="tab"]');
            if (!activeLink) return null;

            const navItem = activeLink.closest('.nav-item');
            const tabId = this.getTabId(navItem);

            return this.activeTabs.get(tabId);
        }

        /**
         * 获取所有标签页
         */
        getAllTabs() {
            return Array.from(this.activeTabs.values());
        }

        /**
         * 检查标签页是否为永久标签
         */
        isPermanent(tabId) {
            return this.permanentTabs.has(tabId);
        }

        /**
         * 检查标签页是否可关闭
         */
        isClosable(tabId) {
            return !this.isPermanent(tabId);
        }

        /**
         * 生成标签页ID
         */
        generateTabId() {
            return 'tab-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
        }

        /**
         * 获取标签页ID
         */
        getTabId(navItem) {
            return navItem.getAttribute('data-tab-id') ||
                   navItem.querySelector('.nav-link').getAttribute('href')?.replace('#', '') ||
                   this.generateTabId();
        }

        /**
         * 创建标签页DOM元素
         */
        createTabElement(tabData) {
            // 这里将调用现有的创建逻辑
            // 暂时返回null，后续在重构中实现
            return null;
        }

        /**
         * 显示标签页内容
         */
        showTabContent(tabElement) {
            const targetId = tabElement.getAttribute('href') || tabElement.getAttribute('data-bs-target');
            if (!targetId) return;

            // 隐藏所有标签页内容
            document.querySelectorAll('.tab-pane').forEach(pane => {
                pane.classList.remove('show', 'active');
            });

            // 显示对应的标签页内容
            const targetPane = document.querySelector(targetId);
            if (targetPane) {
                targetPane.classList.add('show', 'active');
            }

            // 更新URL hash但不触发页面跳转
            if (targetId.startsWith('#')) {
                history.replaceState(null, null, targetId);
            }
        }

        /**
         * 恢复暂停的标签页
         */
        resumePausedTab(tabElement) {
            const iconElement = tabElement.querySelector('.icon');
            if (iconElement && iconElement.hasAttribute('data-original-icon')) {
                const originalIcon = iconElement.getAttribute('data-original-icon');
                iconElement.innerHTML = originalIcon;
                iconElement.removeAttribute('data-original-icon');
            }

            // 移除暂停状态类
            tabElement.classList.remove('iframe-paused');
            const title = tabElement.getAttribute('title');
            if (title && title.includes(' (已暂停)')) {
                tabElement.setAttribute('title', title.replace(' (已暂停)', ''));
            }

            Logger.debug(`[TabsManager] 恢复暂停标签页: ${tabElement.getAttribute('href')}`);
        }

        /**
         * 激活相邻标签页
         */
        activateAdjacentTab(tabItem) {
            const nextTab = tabItem.nextElementSibling?.querySelector('.nav-link[data-bs-toggle="tab"]') ||
                           tabItem.previousElementSibling?.querySelector('.nav-link[data-bs-toggle="tab"]');

            if (nextTab) {
                this.activate(nextTab);
            }
        }

        /**
         * 检查最小标签数量
         */
        checkMinTabCount() {
            const closableTabs = Array.from(this.activeTabs.values()).filter(tab => !tab.permanent);

            if (closableTabs.length <= 1) {
                Dolphin.warning('无法关闭更多标签页');
                return false;
            }
            return true;
        }
    }

    /**
     * 滚动管理器 - 标签页水平滚动控制
     */
    class ScrollManager {
        constructor(eventManager) {
            this.eventManager = eventManager;
            this.tabsWrapper = null;
            this.leftBtn = null;
            this.rightBtn = null;
            this.scrollDistance = 200;
            this.debounceTimer = null;
            this.initialized = false;
            Logger.debug('[ScrollManager] 初始化完成');
        }

        /**
         * 初始化滚动管理器
         */
        init() {
            if (this.initialized) {
                Logger.warn('[ScrollManager] 重复初始化被阻止');
                return false;
            }

            this.setupElements();
            this.setupEventHandlers();
            this.updateButtonsState();
            this.setupResizeObserver();
            this.initialized = true;
            Logger.info('[ScrollManager] 滚动管理器已启动');
            return true;
        }

        /**
         * 设置DOM元素引用
         */
        setupElements() {
            this.leftBtn = document.getElementById('tabScrollLeft');
            this.rightBtn = document.getElementById('tabScrollRight');
            this.tabsWrapper = document.querySelector('.nav-tabs-scrollable');

            if (!this.tabsWrapper) {
                Logger.warn('[ScrollManager] 未找到滚动容器');
                return false;
            }

            return true;
        }

        /**
         * 设置事件处理器
         */
        setupEventHandlers() {
            this.eventManager.on('tab:scroll', ({ direction, event }) => {
                this.scroll(direction);
            });

            this.eventManager.on('tab:activated', ({ tabId, tabElement }) => {
                this.scrollToActiveTab(tabElement);
            });

            this.eventManager.on('tab:created', ({ tabId }) => {
                this.updateButtonsState();
                this.scrollToActiveTab();
            });

            // 滚动事件防抖
            if (this.tabsWrapper) {
                this.tabsWrapper.addEventListener('scroll', () => {
                    this.debounceUpdateButtons();
                });
            }
        }

        /**
         * 设置窗口大小变化监听
         */
        setupResizeObserver() {
            if (window.ResizeObserver) {
                const resizeObserver = new ResizeObserver(() => {
                    this.updateButtonsState();
                });

                if (this.tabsWrapper) {
                    resizeObserver.observe(this.tabsWrapper);
                }
            } else {
                // 降级到window resize事件
                window.addEventListener('resize', () => {
                    this.debounceUpdateButtons();
                });
            }
        }

        /**
         * 滚动到指定方向
         */
        scroll(direction) {
            if (!this.tabsWrapper) return;

            const scrollAmount = this.getAdaptiveScrollDistance();
            const currentScroll = this.tabsWrapper.scrollLeft;
            const targetScroll = direction === 'left'
                ? Math.max(0, currentScroll - scrollAmount)
                : currentScroll + scrollAmount;

            this.tabsWrapper.scrollTo({
                left: targetScroll,
                behavior: 'smooth'
            });

            Logger.debug(`[ScrollManager] 滚动 ${direction}: ${targetScroll}px`);
        }

        /**
         * 滚动到活跃标签页
         */
        scrollToActiveTab(tabElement = null) {
            if (!tabElement) {
                tabElement = document.querySelector('.nav-link.active[data-bs-toggle="tab"]');
            }

            if (!tabElement || !this.tabsWrapper) return;

            const tabRect = tabElement.getBoundingClientRect();
            const wrapperRect = this.tabsWrapper.getBoundingClientRect();

            // 检查标签是否在可视区域内
            if (tabRect.left < wrapperRect.left || tabRect.right > wrapperRect.right) {
                tabElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'nearest',
                    inline: 'center'
                });

                Logger.debug('[ScrollManager] 自动滚动到活跃标签页');
            }
        }

        /**
         * 获取自适应滚动距离
         */
        getAdaptiveScrollDistance() {
            if (!this.tabsWrapper) return this.scrollDistance;

            const wrapperWidth = this.tabsWrapper.clientWidth;

            // 根据容器宽度调整滚动距离
            if (wrapperWidth < 600) {
                return Math.floor(wrapperWidth * 0.4); // 小屏：40%
            } else if (wrapperWidth < 1200) {
                return Math.floor(wrapperWidth * 0.3); // 中屏：30%
            } else {
                return Math.floor(wrapperWidth * 0.25); // 大屏：25%
            }
        }

        /**
         * 防抖更新按钮状态
         */
        debounceUpdateButtons() {
            clearTimeout(this.debounceTimer);
            this.debounceTimer = setTimeout(() => {
                this.updateButtonsState();
            }, 100);
        }

        /**
         * 更新滚动按钮状态
         */
        updateButtonsState() {
            if (!this.tabsWrapper || !this.leftBtn || !this.rightBtn) return;

            const scrollLeft = this.tabsWrapper.scrollLeft;
            const scrollWidth = this.tabsWrapper.scrollWidth;
            const clientWidth = this.tabsWrapper.clientWidth;

            // 检查是否需要显示滚动按钮
            const needsScrolling = scrollWidth > clientWidth;

            if (needsScrolling) {
                this.leftBtn.style.display = 'block';
                this.rightBtn.style.display = 'block';

                // 更新按钮可用状态
                this.leftBtn.disabled = scrollLeft <= 0;
                this.rightBtn.disabled = scrollLeft >= scrollWidth - clientWidth - 1;
            } else {
                this.leftBtn.style.display = 'none';
                this.rightBtn.style.display = 'none';
            }

            Logger.debug('[ScrollManager] 更新滚动按钮状态', {
                needsScrolling,
                leftDisabled: this.leftBtn.disabled,
                rightDisabled: this.rightBtn.disabled
            });
        }

        /**
         * 销毁滚动管理器
         */
        destroy() {
            clearTimeout(this.debounceTimer);
            this.initialized = false;
            Logger.info('[ScrollManager] 滚动管理器已销毁');
        }
    }

    /**
     * iframe管理器 - 管理iframe内容加载和生命周期
     */
    class IFrameManager {
        constructor(eventManager) {
            this.eventManager = eventManager;
            this.iframes = new Map();
            this.pausedIframes = new Set();
            this.initialized = false;
            Logger.debug('[IFrameManager] 初始化完成');
        }

        /**
         * 初始化iframe管理器
         */
        init() {
            if (this.initialized) {
                Logger.warn('[IFrameManager] 重复初始化被阻止');
                return false;
            }

            this.setupEventHandlers();
            this.initialized = true;
            Logger.info('[IFrameManager] iframe管理器已启动');
            return true;
        }

        /**
         * 设置事件处理器
         */
        setupEventHandlers() {
            this.eventManager.on('tab:activated', ({ tabId, tabElement }) => {
                this.loadContent(tabElement);
            });

            this.eventManager.on('tab:closed', ({ tabId }) => {
                this.cleanupIframe(tabId);
            });
        }

        /**
         * 加载iframe内容 - 立即开始网络请求
         */
        loadContent(tabElement) {
            const targetId = tabElement.getAttribute('href') || tabElement.getAttribute('data-bs-target');
            if (!targetId) return;

            const contentPane = document.querySelector(targetId);
            if (!contentPane) return;

            let iframe = contentPane.querySelector('iframe');
            if (!iframe) return;

            // 检查是否有待加载的src
            const dataSrc = iframe.getAttribute('data-src');
            if (dataSrc && !iframe.src) {
                Logger.debug('[IFrameManager] 立即开始iframe网络请求');

                // 立即设置src，触发网络请求
                iframe.src = dataSrc;
                iframe.removeAttribute('data-src');

                // 设置加载属性以确保立即加载
                iframe.loading = 'eager';

                // 优化可见性以触发浏览器立即加载
                iframe.style.visibility = 'visible';
                iframe.style.opacity = '1';

                Logger.info(`[IFrameManager] iframe加载开始: ${dataSrc}`);
            }

            // 如果iframe处于暂停状态，恢复它
            if (this.pausedIframes.has(targetId)) {
                this.resumeIframe(targetId);
            }
        }

        /**
         * 暂停iframe
         */
        pauseIframe(tabId) {
            const iframe = this.iframes.get(tabId);
            if (!iframe) return;

            // 保存当前状态
            iframe.setAttribute('data-paused-src', iframe.src);
            iframe.src = '';

            this.pausedIframes.add(tabId);
            Logger.debug(`[IFrameManager] iframe已暂停: ${tabId}`);
        }

        /**
         * 恢复暂停的iframe
         */
        resumeIframe(tabId) {
            const iframe = this.iframes.get(tabId);
            if (!iframe || !this.pausedIframes.has(tabId)) return;

            const pausedSrc = iframe.getAttribute('data-paused-src');
            if (pausedSrc) {
                iframe.src = pausedSrc;
                iframe.removeAttribute('data-paused-src');
            }

            this.pausedIframes.delete(tabId);
            Logger.debug(`[IFrameManager] iframe已恢复: ${tabId}`);
        }

        /**
         * 清理iframe
         */
        cleanupIframe(tabId) {
            this.iframes.delete(tabId);
            this.pausedIframes.delete(tabId);
            Logger.debug(`[IFrameManager] iframe已清理: ${tabId}`);
        }

        /**
         * 销毁管理器
         */
        destroy() {
            this.iframes.clear();
            this.pausedIframes.clear();
            this.initialized = false;
            Logger.info('[IFrameManager] iframe管理器已销毁');
        }
    }

    /**
     * 主题管理器 - 管理主题同步
     */
    class ThemeManager {
        constructor(eventManager) {
            this.eventManager = eventManager;
            this.currentTheme = null;
            this.observer = null;
            this.debounceTimer = null;
            this.initialized = false;
            Logger.debug('[ThemeManager] 初始化完成');
        }

        /**
         * 初始化主题管理器
         */
        init() {
            if (this.initialized) {
                Logger.warn('[ThemeManager] 重复初始化被阻止');
                return false;
            }

            this.setupThemeObserver();
            this.loadStoredTheme();
            this.initialized = true;
            Logger.info('[ThemeManager] 主题管理器已启动');
            return true;
        }

        /**
         * 设置主题变更监听器
         */
        setupThemeObserver() {
            if (!window.MutationObserver) return;

            this.observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    if (mutation.type === 'attributes' &&
                        (mutation.attributeName === 'data-bs-theme' ||
                         mutation.attributeName === 'class')) {
                        this.debounceThemeSync();
                    }
                });
            });

            // 观察document.documentElement的属性变化
            this.observer.observe(document.documentElement, {
                attributes: true,
                attributeFilter: ['data-bs-theme', 'class']
            });
        }

        /**
         * 防抖主题同步
         */
        debounceThemeSync() {
            clearTimeout(this.debounceTimer);
            this.debounceTimer = setTimeout(() => {
                this.syncThemeToIframes();
            }, 100);
        }

        /**
         * 同步主题到所有iframe
         */
        syncThemeToIframes() {
            const themeConfig = this.getCurrentThemeConfig();
            if (!themeConfig) return;

            // 广播主题配置到所有iframe
            document.querySelectorAll('iframe').forEach(iframe => {
                try {
                    iframe.contentWindow.postMessage({
                        type: 'theme-update',
                        theme: themeConfig
                    }, '*');
                } catch (error) {
                    Logger.debug('[ThemeManager] 跨域iframe跳过主题同步');
                }
            });

            Logger.debug('[ThemeManager] 主题已同步到所有iframe', themeConfig);
        }

        /**
         * 获取当前主题配置
         */
        getCurrentThemeConfig() {
            const docElement = document.documentElement;
            return {
                theme: docElement.getAttribute('data-bs-theme'),
                primary: docElement.getAttribute('data-bs-theme-primary'),
                classes: docElement.className
            };
        }

        /**
         * 加载存储的主题
         */
        loadStoredTheme() {
            const stored = localStorage.getItem('tabler-theme');
            if (!stored) {
                return;
            }

            let themeConfig = null;
            try {
                if (stored.trim().startsWith('{') || stored.trim().startsWith('[')) {
                    themeConfig = JSON.parse(stored);
                } else {
                    themeConfig = { theme: stored.trim() };
                }
            } catch (error) {
                Logger.warn('[ThemeManager] 主题恢复失败，使用回退方案', error);
                themeConfig = { theme: stored.trim() };
            }

            if (themeConfig && typeof themeConfig === 'object') {
                this.applyTheme(themeConfig);
            }
        }

        /**
         * 保存主题设置
         */
        saveTheme(theme) {
            try {
                localStorage.setItem('tabler-theme', JSON.stringify(theme));
                Logger.debug('[ThemeManager] 主题已保存', theme);
            } catch (error) {
                Logger.warn('[ThemeManager] 主题保存失败', error);
            }
        }

        /**
         * 应用主题
         */
        applyTheme(theme) {
            const docElement = document.documentElement;

            if (theme.theme) {
                docElement.setAttribute('data-bs-theme', theme.theme);
            }
            if (theme.primary) {
                docElement.setAttribute('data-bs-theme-primary', theme.primary);
            }
            if (theme.classes) {
                docElement.className = theme.classes;
            }

            this.currentTheme = theme;
            this.syncThemeToIframes();
        }

        /**
         * 销毁管理器
         */
        destroy() {
            if (this.observer) {
                this.observer.disconnect();
                this.observer = null;
            }
            clearTimeout(this.debounceTimer);
            this.initialized = false;
            Logger.info('[ThemeManager] 主题管理器已销毁');
        }
    }

    /**
     * 标签页核心管理器 - 统一管理所有标签页相关功能
     */
    class TabsCore {
        constructor() {
            this.managers = new Map();
            this.initialized = false;
            Logger.debug('[TabsCore] 初始化完成');
        }

        /**
         * 初始化标签页系统
         */
        init() {
            if (this.initialized) {
                Logger.warn('[TabsCore] 重复初始化被阻止');
                return false;
            }

            // 检查页面是否包含标签栏
            if (!this.hasTabContainer()) {
                Logger.info('[TabsCore] 页面不包含标签栏，跳过初始化');
                return false;
            }

            try {
                const eventManager = new EventManager();
                const themeManager = new ThemeManager(eventManager);
                const legacyManagers = initializeTabsFeatureSet(eventManager) || {};

                this.managers.set('event', eventManager);
                this.managers.set('theme', themeManager);

                if (legacyManagers.tabBatchActions) {
                    this.managers.set('tabs', legacyManagers.tabBatchActions);
                }
                if (legacyManagers.tabScrollManager) {
                    this.managers.set('scroll', legacyManagers.tabScrollManager);
                }
                if (legacyManagers.iframeManager) {
                    this.managers.set('iframe', legacyManagers.iframeManager);
                }
                if (legacyManagers.favoriteManager) {
                    this.managers.set('favorite', legacyManagers.favoriteManager);
                }

                eventManager.init();
                themeManager.init();

                this.initialized = true;
                Logger.info('[TabsCore] 标签页系统初始化完成');

                // 向后兼容：保存实例到全局变量
                if (typeof window !== 'undefined') {
                    window.tabsCore = this;
                }

                return true;
            } catch (error) {
                Logger.error('[TabsCore] 初始化失败:', error);
                return false;
            }
        }

        /**
         * 获取管理器实例
         */
        getManager(name) {
            return this.managers.get(name);
        }

        /**
         * 获取所有管理器
         */
        getAllManagers() {
            return this.managers;
        }

        /**
         * 销毁标签页系统
         */
        destroy() {
            try {
                this.managers.forEach((manager, name) => {
                    if (manager && typeof manager.destroy === 'function') {
                        manager.destroy();
                    }
                });

                this.managers.clear();
                this.initialized = false;
                Logger.info('[TabsCore] 标签页系统已销毁');
                return true;
            } catch (error) {
                Logger.error('[TabsCore] 销毁失败:', error);
                return false;
            }
        }

        /**
         * 向后兼容API
         */
        getEventManager() {
            return this.getManager('event');
        }

        getTabsManager() {
            return this.getManager('tabs');
        }

        getScrollManager() {
            return this.getManager('scroll');
        }

        getIFrameManager() {
            return this.getManager('iframe');
        }

        getThemeManager() {
            return this.getManager('theme');
        }

        /**
         * 判断是否已完成初始化
         */
        isInitialized() {
            return this.initialized;
        }

        /**
         * 判断页面是否包含标签容器
         */
        hasTabContainer() {
            return typeof document !== 'undefined' &&
                   typeof document.querySelector === 'function' &&
                   !!document.querySelector('#openedTabs');
        }
    }

    // 创建全局TabsCore实例
    let globalTabsCore = null;

    /**
     * 获取全局TabsCore实例
     */
    function getTabsCore() {
        if (!globalTabsCore) {
            globalTabsCore = new TabsCore();
        }
        return globalTabsCore;
    }

    /**
     * 将驱动管理器和适配器逻辑直接集成到Dolphin闭包中
     */
    const UploadDriverManager = class {
        constructor() {
            this.drivers = new Map();
            this.debugMode = false;
            this.logDebug('UploadDriverManager 初始化完成');
        }
        register(name, driver) {
            // 验证命名空间格式
            if (name.includes(':')) {
                const parts = name.split(':');
                if (parts.length !== 2 || !parts[0] || !parts[1]) {
                    this.logError(`驱动名称格式错误: "${name}". 正确格式: "driver:component" 或 "driver"`);
                    return false;
                }
            }
            
            this.drivers.set(name, driver);
            this.logDebug(`注册${name.includes(':') ? '组件专用' : '通用'}驱动: ${name}`);
            return true;
        }
        
        logDebug(message) {
            if (this.debugMode || (typeof window !== 'undefined' && window.DolphinOption && DolphinOption.debug)) {
                Logger.debug(`[UploadDriverManager] ${message}`);
            }
        }
        
        logError(message, error = null) {
            Logger.error(`[UploadDriverManager] ${message}`, error || '');
        }
        get(name) { return this.drivers.get(name); }
        has(name) { return this.drivers.has(name); }
        remove(name) { return this.drivers.delete(name); }
        list() { return Array.from(this.drivers.keys()); }
        
        /**
         * 智能查找驱动（支持组件命名空间）
         * @param {string} driverName 驱动名称
         * @param {string|null} componentType 组件类型
         * @returns {object|null} 驱动信息对象
         */
        findDriver(driverName, componentType = null) {
            // 1. 优先查找组件专用驱动
            if (componentType) {
                const namespacedName = `${driverName}:${componentType}`;
                if (this.drivers.has(namespacedName)) {
                    this.logDebug(`找到组件专用驱动: ${namespacedName}`);
                    return {
                        name: namespacedName,
                        originalName: driverName,
                        componentType: componentType,
                        driver: this.drivers.get(namespacedName),
                        isNamespaced: true
                    };
                }
            }
            
            // 2. 回退到通用驱动
            if (this.drivers.has(driverName)) {
                this.logDebug(`找到通用驱动: ${driverName}`);
                return {
                    name: driverName,
                    originalName: driverName,
                    componentType: null,
                    driver: this.drivers.get(driverName),
                    isNamespaced: false
                };
            }
            
            // 3. 未找到任何匹配的驱动
            return null;
        }
        
        /**
         * 按组件类型列出专用驱动
         * @param {string} componentType 组件类型
         * @returns {Array} 驱动名称数组
         */
        listByComponent(componentType) {
            const suffix = `:${componentType}`;
            return Array.from(this.drivers.keys())
                .filter(name => name.endsWith(suffix))
                .map(name => name.substring(0, name.length - suffix.length));
        }
        
        /**
         * 列出所有通用驱动（不包含命名空间的）
         * @returns {Array} 通用驱动名称数组
         */
        listGeneric() {
            return Array.from(this.drivers.keys())
                .filter(name => !name.includes(':'));
        }
        
        /**
         * 获取驱动的详细信息
         * @param {string} driverName 驱动名称
         * @param {string|null} componentType 组件类型
         * @returns {object|null} 详细信息
         */
        getDriverInfo(driverName, componentType = null) {
            const driverInfo = this.findDriver(driverName, componentType);
            if (!driverInfo) return null;
            
            return {
                ...driverInfo,
                methods: {
                    hasBefore: typeof driverInfo.driver.before === 'function',
                    hasPrepare: typeof driverInfo.driver.prepare === 'function',
                    hasSuccess: typeof driverInfo.driver.success === 'function',
                    hasError: typeof driverInfo.driver.error === 'function',
                    hasCancel: typeof driverInfo.driver.cancel === 'function'
                }
            };
        }
    };

    const UploadDriverAdapter = class {
        constructor(uploader, driverManager, driverName, componentType = null) {
            if (!uploader || typeof uploader.element.dispatchEvent !== 'function') throw new Error('A valid DolphinUploader instance is required.');
            if (!driverManager || typeof driverManager.findDriver !== 'function') throw new Error('A valid UploadDriverManager instance is required.');

            this.uploader = uploader;
            this.driverManager = driverManager;
            this.driverName = driverName;
            this.componentType = componentType;
            this.driver = null;
            this.driverInfo = null;
            this.eventListeners = [];
            this.init();
        }
        init() {
            // 使用智能查找方法
            this.driverInfo = this.driverManager.findDriver(this.driverName, this.componentType);
            
            if (!this.driverInfo) {
                this.logError('驱动未找到');
                return;
            }
            
            this.driver = this.driverInfo.driver;
            this.bindEvents();
            
            this.logDebug('适配器初始化成功', {
                driverType: this.driverInfo.isNamespaced ? '组件专用' : '通用',
                actualDriverName: this.driverInfo.name,
                componentType: this.componentType
            });
        }
        bindEvents() {
            const addListener = (eventName, handler) => {
                const boundHandler = handler.bind(this);
                this.uploader.element.addEventListener(eventName, boundHandler);
                this.eventListeners.push({ eventName, handler: boundHandler });
            };
            addListener('uploader:beforeUpload', this.handleBeforeUpload);
            addListener('uploader:prepareUpload', this.handlePrepareUpload);
            addListener('uploader:postProcess', this.handlePostProcess);
            addListener('uploader:uploadError', this.handleError);
            addListener('uploader:uploadCancelled', this.handleCancel);
        }
        async handleBeforeUpload(e) {
            const [fileItem] = e.detail;
            if (typeof this.driver.before === 'function') {
                try {
                    const result = await this.driver.before(fileItem.file, fileItem);
                    if (result === false) {
                        e.preventDefault();
                        this.uploader.handleUploadError(fileItem, new Error('上传被驱动 before 钩子阻止'));
                    } else if (typeof result === 'object' && result !== null) {
                        // 添加驱动信息到结果中
                        const enhancedResult = {
                            ...result,
                            _driverInfo: {
                                name: this.driverInfo.name,
                                isNamespaced: this.driverInfo.isNamespaced,
                                componentType: this.componentType
                            }
                        };
                        
                        // 确保 extraData 是对象，如果是 null 则初始化为空对象
                        if (!this.uploader.options.extraData || typeof this.uploader.options.extraData !== 'object') {
                            this.uploader.options.extraData = {};
                        }
                        Object.assign(this.uploader.options.extraData, enhancedResult);
                        Object.assign(fileItem.driverData, enhancedResult);
                    }
                } catch (error) {
                    e.preventDefault();
                    this.uploader.handleUploadError(fileItem, error);
                    this.logError('before钩子执行失败', error);
                }
            }
        }
        async handlePrepareUpload(e) {
            const [fileItem] = e.detail;
            if (typeof this.driver.prepare === 'function') {
                // 如果是异步事件，将Promise添加到异步Promise数组中
                if (e._isAsync && e._asyncPromises) {
                    const asyncPromise = (async () => {
                        try {
                            const result = await this.driver.prepare(fileItem.file, fileItem);
                            if (result === false) {
                                e.preventDefault();
                                this.uploader.handleUploadError(fileItem, new Error('上传被驱动 prepare 钩子阻止'));
                            } else if (typeof result === 'object' && result !== null) {
                                // 添加驱动信息到结果中
                                const enhancedResult = {
                                    ...result,
                                    _driverInfo: {
                                        name: this.driverInfo.name,
                                        isNamespaced: this.driverInfo.isNamespaced,
                                        componentType: this.componentType
                                    }
                                };
                                
                                // 确保 extraData 是对象，如果是 null 则初始化为空对象
                                if (!this.uploader.options.extraData || typeof this.uploader.options.extraData !== 'object') {
                                    this.uploader.options.extraData = {};
                                }
                                Object.assign(this.uploader.options.extraData, enhancedResult);
                                Object.assign(fileItem.driverData, enhancedResult);
                            }
                        } catch (error) {
                            e.preventDefault();
                            fileItem.__driverHandledError = true; // 标记驱动已处理，避免重复派发错误事件
                            await this.uploader.handleUploadError(fileItem, error);
                            this.logError('prepare钩子执行失败（异步）', error);
                        }
                    })();
                    e._asyncPromises.push(asyncPromise);
                } else {
                    // 同步事件处理（保持向后兼容）
                    try {
                        const result = await this.driver.prepare(fileItem.file, fileItem);
                        if (result === false) {
                            e.preventDefault();
                            this.uploader.handleUploadError(fileItem, new Error('上传被驱动 prepare 钩子阻止'));
                        } else if (typeof result === 'object' && result !== null) {
                            // 添加驱动信息到结果中
                            const enhancedResult = {
                                ...result,
                                _driverInfo: {
                                    name: this.driverInfo.name,
                                    isNamespaced: this.driverInfo.isNamespaced,
                                    componentType: this.componentType
                                }
                            };
                            
                            // 确保 extraData 是对象，如果是 null 则初始化为空对象
                            if (!this.uploader.options.extraData || typeof this.uploader.options.extraData !== 'object') {
                                this.uploader.options.extraData = {};
                            }
                            Object.assign(this.uploader.options.extraData, enhancedResult);
                            Object.assign(fileItem.driverData, enhancedResult);
                        }
                    } catch (error) {
                        e.preventDefault();
                        fileItem.__driverHandledError = true; // 标记驱动已处理，避免重复派发错误事件
                        await this.uploader.handleUploadError(fileItem, error);
                        this.logError('prepare钩子执行失败（同步）', error);
                    }
                }
            }
        }
        async handlePostProcess(e) {
            if (typeof this.driver.success !== 'function') {
                // 如果驱动没有success钩子，直接将上传标记为最终成功
                const [fileItem, result] = e.detail;
                if (this.uploader && typeof this.uploader.handleUploadSuccess === 'function') {
                    this.uploader.handleUploadSuccess(fileItem, result);
                }
                return;
            }

            const [fileItem, result] = e.detail;
            try {
                const driverResult = await this.driver.success(fileItem.file, result, fileItem);
                // 驱动的success回调成功，现在调用uploader的最终成功方法
                if (this.uploader && typeof this.uploader.handleUploadSuccess === 'function') {
                    this.uploader.handleUploadSuccess(fileItem, driverResult || result);
                }
            } catch (error) {
                this.logError('success钩子执行失败', error);
                // 如果驱动的success回调失败，则调用uploader的错误处理方法
                if (this.uploader && typeof this.uploader.handleUploadError === 'function') {
                    this.uploader.handleUploadError(fileItem, error);
                }
            }
        }

        async handleError(e) {
            if (typeof this.driver.error !== 'function') return;
            const [fileItem, error] = e.detail;
            try {
                await this.driver.error(fileItem.file, error, fileItem);
            } catch (driverError) {
                this.logError('error钩子执行失败', driverError);
            }
        }
        async handleCancel(e) {
            if (typeof this.driver.cancel !== 'function') return;
            const [fileItem] = e.detail;
            try {
                await this.driver.cancel(fileItem.file, fileItem);
            } catch (driverError) {
                this.logError('cancel钩子执行失败', driverError);
            }
        }
        switchDriver(newDriverName) {
            this.destroy();
            this.driverName = newDriverName;
            this.init();
        }
        
        /**
         * 获取当前驱动信息
         */
        getDriverInfo() {
            return this.driverInfo;
        }
        
        /**
         * 检查驱动是否支持特定方法
         */
        supports(method) {
            return this.driver && typeof this.driver[method] === 'function';
        }
        
        logDebug(message, data = null) {
            if (typeof window !== 'undefined' && window.DolphinOption && DolphinOption.debug) {
                const prefix = `[UploadDriverAdapter:${this.driverInfo?.name || 'unknown'}]`;
                Logger.debug(`${prefix} ${message}`, data || '');
            }
        }
        
        logError(message, error = null) {
            const prefix = `[UploadDriverAdapter:${this.driverInfo?.name || 'unknown'}]`;
            Logger.error(`${prefix} ${message}`, error || '');
        }
        
        destroy() {
            this.eventListeners.forEach(({ eventName, handler }) => {
                this.uploader.element.removeEventListener(eventName, handler);
            });
            this.eventListeners = [];
            this.driver = null;
            this.driverInfo = null;
        }
    };

    // 创建唯一的驱动管理器实例
    const uploaderManager = new UploadDriverManager();

    /**
     * 全新的上传驱动API
     */
    const _uploaderAPI = {
        register: (name, driver) => {
            uploaderManager.register(name, driver);
        },
        create: (uploaderInstance, driverName, componentType = null) => {
            const driverInfo = uploaderManager.findDriver(driverName, componentType);
            if (!driverInfo) {
                // 生成友好的错误信息
                let errorMsg = `Driver "${driverName}" is not registered.`;
                if (componentType) {
                    errorMsg = `Neither component-specific driver "${driverName}:${componentType}" nor generic driver "${driverName}" is registered.`;
                    
                    // 提供可用驱动建议
                    const availableNamespaced = uploaderManager.listByComponent(componentType);
                    const availableGeneric = uploaderManager.listGeneric();
                    
                    let suggestions = [];
                    if (availableNamespaced.length > 0) {
                        suggestions.push(`Available ${componentType} drivers: ${availableNamespaced.join(', ')}`);
                    }
                    if (availableGeneric.length > 0) {
                        suggestions.push(`Available generic drivers: ${availableGeneric.join(', ')}`);
                    }
                    
                    if (suggestions.length > 0) {
                        errorMsg += `\n${suggestions.join('\n')}`;
                    }
                }
                Logger.error(errorMsg);
                return null;
            }
            return new UploadDriverAdapter(uploaderInstance, uploaderManager, driverName, componentType);
        },
        get: (name) => {
            return uploaderManager.get(name);
        },
        has: (name) => {
            return uploaderManager.has(name);
        },
        list: () => {
            return uploaderManager.list();
        },
        
        /**
         * 查找驱动（支持组件命名空间）
         * @param {string} driverName 驱动名称
         * @param {string|null} componentType 组件类型
         * @returns {object|null} 驱动信息对象
         */
        find: (driverName, componentType = null) => {
            return uploaderManager.findDriver(driverName, componentType);
        },
        
        /**
         * 获取驱动详细信息
         * @param {string} driverName 驱动名称
         * @param {string|null} componentType 组件类型
         * @returns {object|null} 包含驱动信息和元数据的详细对象
         */
        info: (driverName, componentType = null) => {
            return uploaderManager.getDriverInfo(driverName, componentType);
        },
        
        /**
         * 获取指定组件类型的所有专用驱动
         * @param {string} componentType 组件类型
         * @returns {string[]} 驱动名称数组
         */
        listByComponent: (componentType) => {
            return uploaderManager.listByComponent(componentType);
        },
        
        /**
         * 获取所有通用驱动
         * @returns {string[]} 通用驱动名称数组
         */
        listGeneric: () => {
            return uploaderManager.listGeneric();
        }
    };

    /**
     * 通用的 AJAX 请求处理方法
     * @param {jQuery} self - 触发元素
     * @param {string} method - HTTP 方法 ('GET' 或 'POST')
     * @private
     */
    const _handleAjaxRequest = function (self, method) {
        let url = self.attr('href') || self.data('url') || undefined;
        let data = {};

        const targetForm = self.data('form') || '';
        let $form = self.parents('form');
        const $nameForm = targetForm ? jQuery('form[name=' + targetForm + ']') : undefined;
        const $classForm = targetForm ? jQuery('.' + targetForm) : undefined;

        if ($nameForm && $nameForm.length) {
            $form = $nameForm;
        } else if ($classForm && $classForm.length) {
            $form = $classForm;
        }

        if ($form.length) {
            data = $form.serialize();
        }

        url = !url ? ($form.attr('action') ? $form.attr('action') : '') : url;

        // GET 特有：验证 URL
        if (method === 'GET' && !url) {
            _toast('请求地址不能为空', 'error');
            return false;
        }

        // 重置按钮状态的方法
        function resetState() {
            self.attr("autocomplete", "on").prop("disabled", false);
        }

        // 执行请求
        function executeRequest() {
            self.attr("autocomplete", "off").prop("disabled", true);
            _loading();

            $.ajax({
                url: url,
                method: method,
                data: data,
                timeout: 10000,
                dataType: 'json'
            }).then(function (res) {
                _loading('hide');

                if (typeof res !== 'object' || res === null) {
                    _toast('服务器返回格式不正确', 'error', 3000);
                    resetState();
                    return false;
                }

                const msg = res.msg;
                const isSuccess = res.code === 1;

                if (isSuccess) {
                    if (res.data && res.data.refresh_user_summary && res.data.current_user_summary) {
                        _updateAdminCurrentUserSummary(res.data.current_user_summary);
                    }

                    // 检查是否需要显示 Modal
                    if (res.data && res.data.modal) {
                        resetState();

                        // 如果有提示消息，先显示 toast
                        if (msg) {
                            _toast($('<div>').text(msg).html(), 'success', res.wait * 1000 || 2000);
                        }

                        // 延迟显示 modal（等待 toast 显示）
                        setTimeout(() => {
                            const modalData = res.data.modal;
                            // 如果指定了方法名，调用对应的快捷方法
                            if (modalData.method && typeof Dolphin[modalData.method] === 'function') {
                                Dolphin[modalData.method].apply(null, modalData.params || []);
                            } else {
                                // 否则使用基础 modal 方法
                                _modal(modalData);
                            }
                        }, msg ? res.wait * 1000 || 0 : 0);

                        return;
                    }

                    let successMsg = msg || '操作完成';
                    if (res.url && !self.hasClass("no-refresh")) {
                        successMsg += "，页面即将自动跳转~";
                    }

                    _toast($('<div>').text(successMsg).html(), 'success');

                    setTimeout(() => {
                        resetState();
                        if (self.hasClass("no-refresh")) return;

                        // 刷新父窗口
                        if (res.data && (res.data === 'reload-parent' || res.data['reload-parent'])) {
                            res.url === '' || res.url === location.href ? parent.location.reload() : parent.location.href = res.url;
                            return false;
                        }

                        // 刷新父窗口表格数据并关闭弹窗
                        if (res.data && (res.data === 'reload-table' || res.data['reload-table'])) {
                            parent.DolphinTable['dp_table'].reloadData('dp_table');
                            const index = parent.layer.getFrameIndex(window.name);
                            parent.layer.close(index);
                            return false;
                        }

                        // 刷新当前窗口
                        if (res.data && (res.data === 'reload-self' || res.data['reload-self'])) {
                            location.reload();
                            return false;
                        }

                        // 关闭弹出框
                        if (res.data && (res.data === 'close-pop' || res.data['close-pop'])) {
                            const index = parent.layer.getFrameIndex(window.name);
                            parent.layer.close(index);
                            return false;
                        }

                        // 跳转逻辑
                        const useForward = !self.hasClass("no-forward");
                        if (res.url && useForward) {
                            if (res.data && (res.data === '_blank' || res.data['_blank'])) {
                                window.open(res.url);
                                return false;
                            } else {
                                location.href = res.url;
                            }
                        } else {
                            location.reload();
                        }
                    }, (res.wait || 3) * 1000);
                } else {
                    // 失败时也检查是否需要显示 Modal
                    if (res.data && res.data.modal) {
                        resetState();

                        // 如果有提示消息，先显示 toast
                        if (msg) {
                            _toast($('<div>').text(msg).html(), 'error', res.wait * 1000 || 3000);
                        }

                        // 延迟显示 modal（等待 toast 显示）
                        setTimeout(() => {
                            const modalData = res.data.modal;

                            // 如果指定了方法名，调用对应的快捷方法
                            if (modalData.method && typeof Dolphin[modalData.method] === 'function') {
                                Dolphin[modalData.method].apply(null, modalData.params || []);
                            } else {
                                // 否则使用基础 modal 方法
                                _modal(modalData);
                            }
                        }, msg ? res.wait * 1000 || 0 : 0);

                        return;
                    }

                    _toast($('<div>').text(msg).html(), 'error', 3000);
                    _refreshToken();
                    setTimeout(resetState, (res.wait || 3) * 1000);
                }
            }).catch(function(err) {
                _loading('hide');

                let errorMsg = '请求失败，请稍后重试';
                if (err['responseJSON']) {
                    errorMsg = err['responseJSON'].message || errorMsg;
                } else if (err.responseText) {
                    try {
                        errorMsg = $(err.responseText).find('h1:first').text().trim() || errorMsg;
                    } catch (e) {}
                }

                _toast($('<div>').text(errorMsg).html(), 'error');
                _refreshToken();
                resetState();
            });
        }

        // 处理确认对话框
        const confirm = self.data('form-confirm');
        if ($.isEmptyObject(confirm)) {
            executeRequest();
        } else {
            Dolphin.modalConfirm(confirm?.title, confirm?.text, function (modal) {
                if (modal && typeof modal.hide === 'function') {
                    modal.hide();
                }
                executeRequest();
            }, confirm?.confirmText, confirm?.cancelText, confirm?.type);
        }

        return false;
    }

    /**
     * 将后台当前用户摘要同步到指定文档中的用户菜单
     * @param {Document} targetDocument
     * @param {Object} summary
     * @returns {boolean}
     * @private
     */
    const _applyAdminCurrentUserSummaryToDocument = function (targetDocument, summary) {
        if (!targetDocument || !summary || typeof summary !== 'object') {
            return false;
        }

        const dropdowns = targetDocument.querySelectorAll('[data-dp-current-user-dropdown="1"]');
        if (!dropdowns.length) {
            return false;
        }

        const displayName = String(summary.display_name || '').trim() || '管理员';
        const secondaryText = String(summary.secondary_text || '').trim() || '当前登录用户';
        const avatarUrl = String(summary.avatar_url || '').trim();

        dropdowns.forEach((dropdown) => {
            const displayNode = dropdown.querySelector('[data-dp-current-user-display-name]');
            const secondaryNode = dropdown.querySelector('[data-dp-current-user-secondary-text]');
            const avatarNode = dropdown.querySelector('[data-dp-current-user-avatar]');

            if (displayNode) {
                displayNode.textContent = displayName;
            }

            if (secondaryNode) {
                secondaryNode.textContent = secondaryText;
            }

            if (avatarNode && avatarUrl !== '') {
                avatarNode.style.backgroundImage = `url(${avatarUrl})`;
            }
        });

        return true;
    };

    /**
     * 更新后台右上角当前用户摘要，兼容 iframe 子页面更新父级壳层
     * @param {Object} summary
     * @returns {boolean}
     * @private
     */
    const _updateAdminCurrentUserSummary = function (summary) {
        let updated = false;

        updated = _applyAdminCurrentUserSummaryToDocument(document, summary) || updated;

        try {
            if (window.parent && window.parent !== window && window.parent.document) {
                updated = _applyAdminCurrentUserSummaryToDocument(window.parent.document, summary) || updated;
            }
        } catch (error) {}

        return updated;
    };

    /**
     * 解析表单提交来源，兼容点击提交按钮和回车触发表单提交
     * @param {jQuery} $form
     * @param {Event} event
     * @returns {jQuery}
     * @private
     */
    const _resolveAjaxFormSubmitter = function ($form, event) {
        const nativeSubmitter = event?.originalEvent?.submitter || event?.submitter;
        if (nativeSubmitter instanceof HTMLElement) {
            const $submitter = jQuery(nativeSubmitter);
            if ($submitter.hasClass('dp-ajax-post') || $submitter.hasClass('dp-ajax-get')) {
                return $submitter;
            }
        }

        const $submitter = $form.find([
            'button[type="submit"].dp-ajax-post',
            'input[type="submit"].dp-ajax-post',
            'button[type="submit"].dp-ajax-get',
            'input[type="submit"].dp-ajax-get'
        ].join(',')).filter(':enabled').first();

        return $submitter;
    }

    /**
     * 判断表单是否包含 ajax 提交按钮，兼容按钮被临时禁用的场景
     * @param {jQuery} $form
     * @returns {boolean}
     * @private
     */
    const _hasAjaxFormSubmitter = function ($form) {
        return $form.find([
            'button[type="submit"].dp-ajax-post',
            'input[type="submit"].dp-ajax-post',
            'button[type="submit"].dp-ajax-get',
            'input[type="submit"].dp-ajax-get'
        ].join(',')).length > 0;
    }

    /**
     * 处理 ajax 方式的 POST 提交
     * @private
     */
    const _initAjaxPost = function () {
        jQuery(document).delegate('.dp-ajax-post', 'click', function () {
            return _handleAjaxRequest(jQuery(this), 'POST');
        });
    }

    /**
     * 处理表单原生 submit 事件，兼容回车提交
     * @private
     */
    const _initAjaxFormSubmit = function () {
        jQuery(document).delegate('form.dp-form', 'submit', function (event) {
            const $form = jQuery(this);
            const hasAjaxSubmitter = _hasAjaxFormSubmitter($form);
            const $submitter = _resolveAjaxFormSubmitter($form, event);
            if (hasAjaxSubmitter) {
                event.preventDefault();
            }

            if (!hasAjaxSubmitter) {
                return true;
            }

            if (!$submitter.length) {
                return false;
            }

            if ($submitter.prop('disabled')) {
                return false;
            }

            const method = $submitter.hasClass('dp-ajax-get') ? 'GET' : 'POST';
            return _handleAjaxRequest($submitter, method);
        });
    }

    /**
     * 处理 ajax 方式的 GET 请求
     * @private
     */
    const _initAjaxGet = function () {
        jQuery(document).delegate('.dp-ajax-get', 'click', function () {
            return _handleAjaxRequest(jQuery(this), 'GET');
        });
    }

    /**
     * 显示 Bootstrap Modal 弹窗
     * @param {object} options - 配置选项
     * @param {string} options.title - 标题（可选）
     * @param {string} options.content - 内容（HTML 字符串或纯文本）
     * @param {string} [options.size=''] - 尺寸：'sm'（小）、'lg'（大）、'xl'（超大）、'full'（全宽）、''（默认）
     * @param {boolean} [options.scrollable=false] - 是否可滚动
     * @param {boolean} [options.centered=true] - 是否垂直居中
     * @param {boolean} [options.blur=true] - 是否显示模糊背景
     * @param {string} [options.footer=''] - 底部按钮 HTML
     * @param {string} [options.status=''] - 状态颜色：'success'、'danger'、'warning'、'info'
     * @param {boolean} [options.closeButton=true] - 是否显示右上角关闭按钮
     * @param {function} [options.onShow] - 显示后回调
     * @param {function} [options.onHide] - 隐藏后回调
     * @param {boolean} [options.backdrop=true] - 是否显示背景遮罩，false 或 'static'
     * @param {boolean} [options.keyboard=true] - 是否允许 ESC 键关闭
     * @private
     */
    const _modal = function (options = {}) {
        // 默认配置
        const defaults = {
            title: '',
            content: '',
            size: '',
            scrollable: false,
            centered: true,
            blur: true,
            footer: '',
            status: '',
            closeButton: true,
            onShow: null,
            onHide: null,
            backdrop: true,
            keyboard: true
        };

        // 合并配置
        const config = $.extend({}, defaults, options);

        // 生成唯一 ID
        const modalId = 'dp-modal-' + Date.now();

        // 构建尺寸类
        let sizeClass = '';
        switch (config.size) {
            case 'sm':
                sizeClass = 'modal-sm';
                break;
            case 'lg':
                sizeClass = 'modal-lg';
                break;
            case 'xl':
                sizeClass = 'modal-xl';
                break;
            case 'full':
                sizeClass = 'modal-full-width';
                break;
        }

        // 构建对话框类
        const dialogClasses = ['modal-dialog'];
        if (sizeClass) dialogClasses.push(sizeClass);
        if (config.centered) dialogClasses.push('modal-dialog-centered');
        if (config.scrollable) dialogClasses.push('modal-dialog-scrollable');

        // 构建模态框类
        const modalClasses = ['modal', 'fade'];
        if (config.blur) modalClasses.push('modal-blur');

        // 构建状态条
        let statusBar = '';
        if (config.status) {
            if (config.size === '') {
                dialogClasses.push('modal-sm');
            }
            statusBar = `<div class="modal-status bg-${config.status}"></div>`;
        }

        // 构建关闭按钮
        const closeBtn = config.closeButton
            ? '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>'
            : '';

        // 构建标题
        let header = '';
        if (config.title) {
            // 有标题：显示标题栏 + 关闭按钮
            header = `
                <div class="modal-header">
                    <h5 class="modal-title">${$('<div>').text(config.title).html()}</h5>
                    ${closeBtn}
                </div>
            `;
        } else if (config.closeButton) {
            // 没有标题但需要关闭按钮：添加一个独立的关闭按钮（无论是否有状态条）
            header = closeBtn;
        }

        // 构建底部
        let footer = '';
        if (config.footer) {
            footer = `<div class="modal-footer">${config.footer}</div>`;
        }

        // 构建完整的 modal HTML
        const modalHtml = `
            <div class="${modalClasses.join(' ')}" id="${modalId}" tabindex="-1" aria-hidden="true">
                <div class="${dialogClasses.join(' ')}" role="document">
                    <div class="modal-content">
                        ${statusBar}
                        ${header}
                        <div class="modal-body">
                            ${config.content}
                        </div>
                        ${footer}
                    </div>
                </div>
            </div>
        `;

        // 移除已存在的同 ID modal（如果有）
        $(`#${modalId}`).remove();

        // 添加到 body
        $('body').append(modalHtml);

        // 获取 modal 元素
        const $modal = $(`#${modalId}`);

        const bootstrapNamespace = (typeof window !== 'undefined' && window.bootstrap)
            || (typeof bootstrap !== 'undefined' ? bootstrap : null);

        if (!bootstrapNamespace || typeof bootstrapNamespace.Modal !== 'function') {
            const plainTitle = $('<div>').html(config.title || '').text() || '提示';
            const plainContent = $('<div>').html(config.content || '').text() || '';

            if (typeof Swal !== 'undefined' && Swal.fire) {
                Swal.fire({
                    title: plainTitle,
                    text: plainContent,
                    icon: config.status || 'info',
                    confirmButtonText: '确定'
                });
            } else {
                window.alert([plainTitle, plainContent].filter(Boolean).join('\n\n'));
            }

            return {
                element: $modal,
                instance: null,
                show: () => {},
                hide: () => {},
                dispose: () => {
                    $modal.remove();
                }
            };
        }

        // 初始化 Bootstrap Modal
        const bsModal = new bootstrapNamespace.Modal($modal[0], {
            backdrop: config.backdrop,
            keyboard: config.keyboard
        });

        // 绑定事件
        if (config.onShow) {
            $modal.on('shown.bs.modal', config.onShow);
        }

        if (config.onHide) {
            $modal.on('hidden.bs.modal', config.onHide);
        }

        // 销毁后移除 DOM 元素
        $modal.on('hidden.bs.modal', function () {
            bsModal.dispose();
            $modal.remove();
        });

        // 显示 modal
        bsModal.show();

        // 返回 modal 实例，方便外部控制
        return {
            element: $modal,
            instance: bsModal,
            show: () => bsModal.show(),
            hide: () => bsModal.hide(),
            dispose: () => {
                bsModal.dispose();
                $modal.remove();
            }
        };
    }

    /**
     * 快捷方法：通用状态提示 Modal
     * @param {string} title - 标题
     * @param {string} content - 内容
     * @param {string} status - 状态：'success'、'danger'、'warning'、'info'
     * @param {string} size - 尺寸，默认 'sm'
     * @param {string} buttonText - 按钮文字，默认根据状态自动设置
     * @returns {object} Modal 实例
     */
    const _modalAlert = function (title, content, status = 'info', size = 'sm', buttonText = '') {
        const icons = {
            'success': '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon mb-2 text-green icon-lg"> <path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"></path> <path d="M9 12l2 2l4 -4"></path> </svg>',
            'danger': '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon mb-2 text-danger icon-lg"> <path d="M12 9v4"></path> <path d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z"></path> <path d="M12 16h.01"></path> </svg>',
            'warning': '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" class="icon mb-2 text-warning icon-lg"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" /><path d="M12 8v4" /><path d="M12 16h.01" /></svg>',
            'info': '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" class="icon mb-2 text-info icon-lg"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" /><path d="M12 9h.01" /><path d="M11 12h1v4h1" /></svg>'
        };

        const defaultButtons = {
            'success': '确定',
            'danger': '知道了',
            'warning': '我知道了',
            'info': '确定'
        };

        const icon = icons[status] || icons['info'];
        const btnText = buttonText || defaultButtons[status] || '确定';
        const btnClass = 'btn-' + status;

        return _modal({
            status: status,
            size: size,
            content: `
                    <div class="text-center py-4">
                        ${icon}
                        <h3>${$('<div>').text(title).html()}</h3>
                        <div class="text-secondary">${content}</div>
                    </div>
                `,
            footer: `
                    <div class="w-100">
                        <button type="button" class="btn ${btnClass} w-100" data-bs-dismiss="modal">${$('<div>').text(btnText).html()}</button>
                    </div>
                `
        });
    }

    /**
     * 快捷方法：确认对话框 Modal
     * @param {string} title - 标题
     * @param {string} content - 内容描述
     * @param {function} onConfirm - 确认回调函数
     * @param {string} confirmText - 确认按钮文字，默认"确认"
     * @param {string} cancelText - 取消按钮文字，默认"取消"
     * @param {string} status - 状态：'danger'、'warning'、'info'，默认 'warning'
     * @returns {object} Modal 实例
     */
    const _modalConfirm = function (title, content, onConfirm, confirmText = '确认', cancelText = '取消', status = 'warning') {
        const icons = {
            'success': '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon mb-2 text-green icon-lg"> <path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"></path> <path d="M9 12l2 2l4 -4"></path> </svg>',
            'danger': '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon mb-2 text-danger icon-lg"> <path d="M12 9v4"></path> <path d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z"></path> <path d="M12 16h.01"></path> </svg>',
            'warning': '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" class="icon mb-2 text-warning icon-lg"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" /><path d="M12 8v4" /><path d="M12 16h.01" /></svg>',
            'info': '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" class="icon mb-2 text-info icon-lg"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" /><path d="M12 9h.01" /><path d="M11 12h1v4h1" /></svg>'
        };

        const icon = icons[status] || icons['warning'];
        const btnClass = 'btn-' + status;
        const bootstrapNamespace = (typeof window !== 'undefined' && window.bootstrap)
            || (typeof bootstrap !== 'undefined' ? bootstrap : null);

        const handleConfirm = function (modal = null) {
            if (typeof onConfirm === 'function') {
                onConfirm(modal);
                return;
            }

            if (typeof onConfirm === 'string') {
                if (typeof window[onConfirm] === 'function') {
                    window[onConfirm](modal);
                    return;
                }

                _loading()
                $.ajax({
                    url: onConfirm,
                    type: 'POST',
                    dataType: 'json',
                    success: function(res) {
                        _loading('hide');
                        const msg = res.msg;
                        const isSuccess = res.code === 1;

                        if (isSuccess) {
                            // 检查是否需要显示 Modal
                            if (res.data && res.data.modal) {
                                // 如果有提示消息，先显示 toast
                                if (msg) {
                                    _toast($('<div>').text(msg).html(), 'success', res.wait * 1000 || 2000);
                                }

                                // 延迟显示 modal（等待 toast 显示）
                                setTimeout(() => {
                                    const modalData = res.data.modal;
                                    // 如果指定了方法名，调用对应的快捷方法
                                    if (modalData.method && typeof Dolphin[modalData.method] === 'function') {
                                        Dolphin[modalData.method].apply(null, modalData.params || []);
                                    } else {
                                        // 否则使用基础 modal 方法
                                        _modal(modalData);
                                    }
                                }, msg ? res.wait * 1000 || 0 : 0);

                                return;
                            }

                            if (modal && typeof modal.hide === 'function') {
                                modal.hide();
                            }

                            // 显示成功消息
                            let successMsg = msg || '操作完成';
                            if (res.url) {
                                successMsg += "，页面即将自动跳转~";
                            }
                            _toast($('<div>').text(successMsg).html(), 'success');
                        } else {
                            // 失败时也检查是否需要显示 Modal
                            if (res.data && res.data.modal) {
                                // 如果有提示消息，先显示 toast
                                if (msg) {
                                    _toast($('<div>').text(msg).html(), 'error', res.wait * 1000 || 3000);
                                }

                                // 延迟显示 modal（等待 toast 显示）
                                setTimeout(() => {
                                    const modalData = res.data.modal;

                                    // 如果指定了方法名，调用对应的快捷方法
                                    if (modalData.method && typeof Dolphin[modalData.method] === 'function') {
                                        Dolphin[modalData.method].apply(null, modalData.params || []);
                                    } else {
                                        // 否则使用基础 modal 方法
                                        _modal(modalData);
                                    }
                                }, msg ? res.wait * 1000 || 0 : 0);

                                return;
                            }

                            _toast($('<div>').text(msg).html(), 'error', 3000);
                            _refreshToken();
                        }
                    },
                    error: function(xhr) {
                        _loading('hide');
                        _refreshToken();
                        _toast('请求失败，请稍后重试', 'error');
                    }
                });
            }
        };

        if (!bootstrapNamespace || typeof bootstrapNamespace.Modal !== 'function') {
            const plainTitle = $('<div>').html(title || '').text() || '确认操作';
            const plainContent = $('<div>').html(content || '').text() || '';

            if (typeof Swal !== 'undefined' && Swal.fire) {
                Swal.fire({
                    title: plainTitle,
                    text: plainContent,
                    icon: status || 'warning',
                    showCancelButton: true,
                    confirmButtonText: confirmText,
                    cancelButtonText: cancelText
                }).then((result) => {
                    if (result.isConfirmed) {
                        handleConfirm(null);
                    }
                });
            } else if (window.confirm([plainTitle, plainContent].filter(Boolean).join('\n\n'))) {
                handleConfirm(null);
            }

            return null;
        }

        // 生成唯一 ID
        const confirmBtnId = 'modal-confirm-btn-' + Date.now();

        const modal = _modal({
            status: status,
            size: 'sm',
            content: `
                    <div class="text-center py-4">
                        ${icon}
                        <h3>${$('<div>').text(title).html()}</h3>
                        <div class="text-secondary">${content}</div>
                    </div>
                `,
            footer: `
                    <div class="w-100">
                        <div class="row">
                            <div class="col">
                                <button type="button" class="btn w-100" data-bs-dismiss="modal">${$('<div>').text(cancelText).html()}</button>
                            </div>
                            <div class="col">
                                <button type="button" class="btn ${btnClass} w-100" id="${confirmBtnId}">${$('<div>').text(confirmText).html()}</button>
                            </div>
                        </div>
                    </div>
                `
        });

        // 绑定确认按钮事件
        setTimeout(() => {
            $(`#${confirmBtnId}`).on('click', function() {
                handleConfirm(modal);
            });
        }, 100);

        return modal;
    }

    /**
     * 弹窗监听
     * @private
     */
    const _initPopup = function () {
        jQuery(document).delegate('a.dp-pop', 'click', function () {
            const $self = $(this);
            const $url = $self.attr('href');
            const $title = $self.attr('title') || $self.data('original-title');
            const $layer = $self.data('layer') || {};

            let options = {
                title: $title,
                content: $url
            };

            $.extend(options, DolphinConfig.dialog, $layer);

            options['title'] = layui.util.escape(options['title']);

            const callbacks = ['success', 'yes', 'cancel', 'beforeEnd', 'end', 'moveEnd', 'resizing', 'full', 'min', 'restore'];
            callbacks.forEach(callback => {
                const callbackVal = options[callback];
                if (!callbackVal) return;

                if (typeof callbackVal === 'string') {
                    const globalFunc = window[callbackVal?.trim()];
                    options[callback] = typeof globalFunc === 'function' ? globalFunc : void delete options[callback];
                }
            });

            layer.open(options);
            return false;
        });
    }

    /**
     * 横向导航
     * @private
     */
    const _initTabs = function () {
        let tabsCore = null;
        let hasTabContainer = null;

        try {
            tabsCore = getTabsCore();
            hasTabContainer = (tabsCore && typeof tabsCore.hasTabContainer === 'function')
                ? tabsCore.hasTabContainer()
                : !!document.querySelector('#openedTabs');

            if (!hasTabContainer) {
                Logger.info('[_initTabs] 页面没有可用的标签容器，初始化轻量功能');
                initializeTabsFeatureSet(null, { allowWithoutTabs: true });
                return;
            }

            if (tabsCore.init()) {
                Logger.info('[_initTabs] 新模块化标签页系统已启动');
                return;
            }

            if (typeof tabsCore.isInitialized === 'function' && tabsCore.isInitialized()) {
                Logger.info('[_initTabs] 标签系统已处于启动状态，忽略重复初始化请求');
                return;
            }
        } catch (error) {
            Logger.error('[_initTabs] 新架构初始化失败，回退到原有实现:', error);
        }

        if (hasTabContainer === null) {
            hasTabContainer = !!document.querySelector('#openedTabs');
        }

        if (!hasTabContainer) {
            Logger.info('[_initTabs] 未检测到标签容器，跳过回退方案');
            return;
        }

        Logger.warn('[_initTabs] tabsCore 初始化失败，启动回退方案');
        initializeTabsFeatureSet();
    };

    function initializeTabsFeatureSet(eventManager = null, options = {}) {
        const hasTabContainer = !!document.querySelector('#openedTabs');
        const allowWithoutTabs = !!options.allowWithoutTabs;

        // 非标签页页面默认不初始化，除非显式允许仅初始化轻量功能
        if (!hasTabContainer && !allowWithoutTabs) return;

        if (hasTabContainer) {
            // 拦截并禁用 Bootstrap Tab 的自动行为
            // 我们需要完全控制标签激活逻辑，避免与 Bootstrap 的竞态条件
            document.addEventListener('show.bs.tab', function(event) {
                const target = event.target;
                if (target && (target.closest('#openedTabs') || target.closest('#pinnedTabs'))) {
                    // 阻止 Bootstrap Tab 的默认行为
                    event.preventDefault();
                    event.stopImmediatePropagation();
                    Logger.debug('[initializeTabsFeatureSet] 拦截 Bootstrap Tab 事件:', event.target.getAttribute('href'));
                }
            }, true); // 使用捕获阶段，优先级最高

            document.addEventListener('shown.bs.tab', function(event) {
                const target = event.target;
                if (target && (target.closest('#openedTabs') || target.closest('#pinnedTabs'))) {
                    event.preventDefault();
                    event.stopImmediatePropagation();
                }
            }, true);
        }

        // 全局事件注册器，确保多次初始化不会重复绑定事件
        const globalEventBindings = window.__dolphinTabEventBindings =
            window.__dolphinTabEventBindings || {};

        const bindGlobalEvent = function(key, target, eventName, handler, options) {
            if (!target || typeof target.addEventListener !== 'function' || typeof handler !== 'function') {
                return;
            }
            const existing = globalEventBindings[key];
            if (existing) {
                existing.target.removeEventListener(existing.eventName, existing.handler, existing.options);
            }
            target.addEventListener(eventName, handler, options);
            globalEventBindings[key] = { target, eventName, handler, options };
        };

        bindGlobalEvent('current.user.menu.openAdminMenuItem', document, 'click', function(event) {
            const trigger = event.target.closest('[data-open-admin-menu="1"]');
            let item = null;
            let handled = false;

            if (!trigger) {
                return;
            }

            item = {
                title: String(trigger.getAttribute('data-title') || trigger.textContent || '').trim(),
                url: String(trigger.getAttribute('data-url') || trigger.getAttribute('href') || '').trim(),
                iframe_url: String(
                    trigger.getAttribute('data-iframe-url')
                    || trigger.getAttribute('data-url')
                    || trigger.getAttribute('href')
                    || ''
                ).trim(),
                icon: String(trigger.getAttribute('data-icon') || 'ti ti-user-circle').trim(),
                app_name: String(trigger.getAttribute('data-app-name') || '').trim(),
                route: String(trigger.getAttribute('data-route') || '').trim(),
                is_workspace: false
            };

            if (window.Dolphin && typeof Dolphin.openAdminMenuItem === 'function') {
                handled = Boolean(Dolphin.openAdminMenuItem(item));
            }

            if (handled) {
                event.preventDefault();
            }
        });

        const syncFavoriteActiveState = function() {
            if (globalFavoriteManager && typeof globalFavoriteManager.updateActiveState === 'function') {
                globalFavoriteManager.updateActiveState();
            }
        };

        // 工具函数 - 减少代码重复
        const utils = {
            // 设置tooltip的通用函数
            setTooltip: function(element, title, placement = 'top') {
                if (!element) return;
                const isTabNavLink = typeof element.matches === 'function' && element.matches('.nav-link[data-bs-toggle="tab"]');

                // Tab 链接的tooltip挂到li.nav-item，避免与 bootstrap tab 实例冲突
                if (isTabNavLink) {
                    const tooltipTarget = element.closest('.nav-item') || element;

                    try {
                        if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                            const instance = bootstrap.Tooltip.getInstance(element);
                            if (instance) {
                                instance.dispose();
                            }

                            if (tooltipTarget) {
                                const childInstance = bootstrap.Tooltip.getInstance(tooltipTarget);
                                if (childInstance) {
                                    childInstance.dispose();
                                }
                            }
                        }
                    } catch (e) {}

                    element.removeAttribute('title');
                    element.removeAttribute('data-bs-placement');
                    element.removeAttribute('data-bs-original-title');

                    tooltipTarget.setAttribute('data-bs-toggle', 'tooltip');
                    tooltipTarget.setAttribute('data-bs-placement', placement);
                    tooltipTarget.setAttribute('data-bs-original-title', title || '');
                    tooltipTarget.setAttribute('title', title || '');
                    return;
                }

                element.setAttribute('data-bs-toggle', 'tooltip');
                element.setAttribute('data-bs-placement', placement);
                element.setAttribute('data-bs-original-title', title);
            },

            // 移除tooltip的通用函数
            removeTooltip: function(element) {
                if (!element) return;
                const isTabNavLink = typeof element.matches === 'function' && element.matches('.nav-link[data-bs-toggle="tab"]');

                if (isTabNavLink) {
                    const tooltipTarget = element.closest('.nav-item') || element;

                    try {
                        if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                            const instance = bootstrap.Tooltip.getInstance(element);
                            if (instance) {
                                instance.dispose();
                            }

                            if (tooltipTarget) {
                                const childInstance = bootstrap.Tooltip.getInstance(tooltipTarget);
                                if (childInstance) {
                                    childInstance.dispose();
                                }
                            }
                        }
                    } catch (e) {}

                    element.removeAttribute('title');
                    element.removeAttribute('data-bs-placement');
                    element.removeAttribute('data-bs-original-title');

                    tooltipTarget.removeAttribute('data-bs-toggle');
                    tooltipTarget.removeAttribute('data-bs-placement');
                    tooltipTarget.removeAttribute('data-bs-original-title');
                    tooltipTarget.removeAttribute('title');
                    return;
                }

                element.removeAttribute('data-bs-toggle');
                element.removeAttribute('data-bs-placement');
                element.removeAttribute('data-bs-original-title');
            },

            // 统一的标签数量检查（排除永久标签）
            checkMinTabCount: function() {
                const allTabs = document.querySelectorAll('[data-tab-id]');
                const closableTabs = Array.from(allTabs).filter(tab => !tab.hasAttribute('data-tab-permanent'));

                if (closableTabs.length === 0) {
                    Dolphin.warning('无法关闭更多标签页');
                    return false;
                }
                return true;
            },

            // 检查是否为永久标签（首页标签）
            isPermanentTab: function(tabItem) {
                return tabItem && tabItem.hasAttribute('data-tab-permanent');
            },

            // 检查是否为固定标签
            isPinnedTab: function(tabItem) {
                return tabItem && tabItem.closest('#pinnedTabs') !== null;
            },

            // 清除所有标签的active状态
            clearAllActiveStates: function() {
                document.querySelectorAll('.nav-link.active').forEach(function(link) {
                    link.classList.remove('active');
                    link.setAttribute('aria-selected', 'false');
                });
            },

            // 激活指定标签
            activateTab: function(tabElement) {
                // 清除所有活跃状态
                this.clearAllActiveStates();

                // 激活点击的标签
                tabElement.classList.add('active');
                tabElement.setAttribute('aria-selected', 'true');

                // 记录标签页激活时间（用于智能内存管理）
                const navItem = tabElement.closest('.nav-item');
                if (navItem) {
                    navItem.setAttribute('data-last-active', Date.now().toString());
                }

                // 如果标签页处于暂停状态，恢复原始图标
                if (tabElement.classList.contains('iframe-paused')) {
                    // 移除暂停状态类
                    tabElement.classList.remove('iframe-paused');
                    const title = tabElement.getAttribute('title');
                    if (title && title.includes(' (已暂停)')) {
                        tabElement.setAttribute('title', title.replace(' (已暂停)', ''));
                    }
                }

                // 获取目标内容面板ID
                const targetId = tabElement.getAttribute('href') || tabElement.getAttribute('data-bs-target');
                if (targetId) {
                    const targetPane = document.querySelector(targetId);

                    // 分两个阶段处理类切换，避免竞态条件
                    // 阶段1：移除所有旧的激活状态
                    document.querySelectorAll('.tab-pane').forEach(pane => {
                        pane.classList.remove('show', 'active');
                    });

                    if (targetPane) {
                        // 使用 requestAnimationFrame 确保在下一帧执行
                        // 这可以彻底避免浏览器批量优化导致的类丢失问题
                        requestAnimationFrame(() => {
                            // 再次检查并移除，确保清理彻底
                            targetPane.classList.remove('show', 'active');

                            // 强制重排
                            void targetPane.offsetHeight;

                            // 使用第二个 requestAnimationFrame 确保在新的渲染帧添加类
                            requestAnimationFrame(() => {
                                // 阶段2：添加新的激活状态
                                targetPane.classList.add('active');
                                targetPane.classList.add('show');

                                // 再次验证状态
                                setTimeout(() => {
                                    if (targetPane.classList.contains('active') && !targetPane.classList.contains('show')) {
                                        Logger.warn(`[utils.activateTab] 检测到 show 类再次缺失，强制修复: ${targetId}`);
                                        targetPane.classList.add('show');
                                    }
                                }, 10);
                            });
                        });
                    }

                    // 更新URL hash但不触发页面跳转
                    if (targetId.startsWith('#')) {
                        history.replaceState(null, null, targetId);
                    }

                    // 同步菜单状态
                    // 由于utils.activateTab不触发Bootstrap的shown.bs.tab事件
                    // 需要在这里手动调用菜单同步逻辑
                    if (typeof iframeManager !== 'undefined') {
                        // 同步侧栏菜单
                        if (typeof iframeManager.syncSidebarWithActiveTab === 'function') {
                            iframeManager.syncSidebarWithActiveTab(tabElement);
                        }

                        // 同步横向菜单
                        if (typeof iframeManager.updateHorizontalNavActive === 'function') {
                            const tabId = targetId.startsWith('#') ? targetId.substring(1) : targetId;
                            iframeManager.updateHorizontalNavActive(tabId);
                        }
                    }
                }

                syncFavoriteActiveState();
            }
        };

        // 标签页滚动管理器
        const tabScrollManager = {
            tabsWrapper: null,
            leftBtn: document.getElementById('tabScrollLeft'),
            rightBtn: document.getElementById('tabScrollRight'),
            scrollDistance: 200,
            initialized: false,

            init: function() {
                if (this.initialized) return;

                // 使用缓存的元素，优先选择普通标签容器
                this.tabsWrapper = document.getElementById('openedTabs') ||
                                  document.querySelector('.normal-tabs') ||
                                  document.querySelector('.tabs-wrapper .nav-tabs:not(.pinned-tabs)');

                if (!this.tabsWrapper || !this.leftBtn || !this.rightBtn) {
                    Logger.warn('[Dolphin Tabs] 滚动管理器初始化失败');
                    return;
                }

                // 绑定事件（只绑定一次）
                this.leftBtn.addEventListener('click', this.scrollLeft.bind(this));
                this.rightBtn.addEventListener('click', this.scrollRight.bind(this));
                window.addEventListener('resize', this.updateButtonState.bind(this));
                this.tabsWrapper.addEventListener('scroll', this.updateButtonState.bind(this));

                // 延迟检查按钮状态
                setTimeout(() => this.updateButtonState(), 100);
                this.initialized = true;
            },

            scrollLeft: function() {
                if (this.tabsWrapper) {
                    this.tabsWrapper.scrollBy({
                        left: -this.scrollDistance,
                        behavior: 'smooth'
                    });
                }
            },

            scrollRight: function() {
                if (this.tabsWrapper) {
                    this.tabsWrapper.scrollBy({
                        left: this.scrollDistance,
                        behavior: 'smooth'
                    });
                }
            },

            updateButtonState: function() {
                if (!this.tabsWrapper || !this.leftBtn || !this.rightBtn) return;

                const scrollLeft = this.tabsWrapper.scrollLeft;
                const scrollWidth = this.tabsWrapper.scrollWidth;
                const clientWidth = this.tabsWrapper.clientWidth;

                // 检查是否可以滚动
                this.leftBtn.disabled = scrollLeft <= 0;
                this.rightBtn.disabled = scrollLeft >= (scrollWidth - clientWidth);

                // 如果内容没有超出容器，隐藏滚动按钮
                const needScroll = scrollWidth > clientWidth;
                this.leftBtn.style.display = needScroll ? 'flex' : 'none';
                this.rightBtn.style.display = needScroll ? 'flex' : 'none';
            }
        };

        // iframe管理器
        const iframeManager = {
            loadedTabs: new Set(),
            loadingTabs: new Set(),
            autoPauseEnabled: true,
            autoPauseThresholdMinutes: 10,
            autoPauseMinTabs: 10,
            autoPauseOnHidden: true,
            autoPauseCloseTabs: false,
            autoPauseDebug: false,
            pendingInactiveTabs: null,
            menuRegistry: [],
            menuPathIndex: new Map(),
            tabUrlIndex: new Map(),
            allowedIframeMessageTypes: new Set(['iframe_ready', 'iframe_title_change', 'iframe_error', 'iframe_close_request', 'request_theme_sync', 'theme_changed_from_iframe']),
            activeSidebarDropdownItem: null,
            activeSidebarTopItem: null,
            activeSidebarParentDropdowns: [],
            sidebarStateInitialized: false,

            // 初始化iframe管理功能
            init: function() {
                this.applyRuntimeConfig();
                this.buildMenuRegistry();
                this.rebuildTabUrlIndex();
                this.bindTabEvents();
                this.loadActiveTabContent();
                this.initThemeSync();
                this.bindSidebarMenuEvents();
                this.startPerformanceMonitoring();
                this.initEnhancedMessageHandling();
            },

            /**
             * 解析布尔配置
             */
            toBooleanConfig: function(value, defaultValue) {
                if (typeof value === 'boolean') return value;
                if (typeof value === 'number') return value !== 0;
                if (typeof value === 'string') {
                    const normalized = value.trim().toLowerCase();
                    if (normalized === 'true' || normalized === '1' || normalized === 'yes' || normalized === 'on') return true;
                    if (normalized === 'false' || normalized === '0' || normalized === 'no' || normalized === 'off') return false;
                }
                return defaultValue;
            },

            /**
             * 解析整数配置
             */
            toIntConfig: function(value, defaultValue, minValue = 1) {
                const parsed = parseInt(value, 10);
                if (Number.isFinite(parsed) && parsed >= minValue) {
                    return parsed;
                }
                return defaultValue;
            },

            /**
             * 读取后端注入的标签页运行配置
             */
            applyRuntimeConfig: function() {
                const runtimeConfig = (typeof DolphinConfig !== 'undefined' && DolphinConfig)
                    ? DolphinConfig
                    : ((typeof window !== 'undefined' && window.DolphinConfig) ? window.DolphinConfig : null);
                const tabsConfig = runtimeConfig && runtimeConfig.tabs ? runtimeConfig.tabs : null;

                if (!tabsConfig || typeof tabsConfig !== 'object') {
                    return;
                }

                const getConfigValue = (camelKey, snakeKey, fallback) => {
                    if (Object.prototype.hasOwnProperty.call(tabsConfig, camelKey)) {
                        return tabsConfig[camelKey];
                    }
                    if (Object.prototype.hasOwnProperty.call(tabsConfig, snakeKey)) {
                        return tabsConfig[snakeKey];
                    }
                    return fallback;
                };

                this.autoPauseOnHidden = this.toBooleanConfig(
                    getConfigValue('autoPauseOnHidden', 'auto_pause_on_hidden', this.autoPauseOnHidden),
                    this.autoPauseOnHidden
                );
                this.autoPauseEnabled = this.toBooleanConfig(
                    getConfigValue('autoPauseEnabled', 'auto_pause_enabled', this.autoPauseEnabled),
                    this.autoPauseEnabled
                );
                this.autoPauseThresholdMinutes = this.toIntConfig(
                    getConfigValue('autoPauseThresholdMinutes', 'auto_pause_threshold_minutes', this.autoPauseThresholdMinutes),
                    this.autoPauseThresholdMinutes,
                    1
                );
                this.autoPauseMinTabs = this.toIntConfig(
                    getConfigValue('autoPauseMinTabs', 'auto_pause_min_tabs', this.autoPauseMinTabs),
                    this.autoPauseMinTabs,
                    1
                );
                this.maxTabsLimit = this.toIntConfig(
                    getConfigValue('maxTabsLimit', 'max_tabs_limit', this.maxTabsLimit),
                    this.maxTabsLimit,
                    1
                );
            },

            /**
             * 初始化增强的消息处理
             */
            initEnhancedMessageHandling: function() {
                if (!this.boundEnhancedMessageHandler) {
                    this.boundEnhancedMessageHandler = (event) => {
                        this.enhancedMessageHandler(event);
                    };
                }
                bindGlobalEvent('tabs.window.message.enhanced', window, 'message', this.boundEnhancedMessageHandler);
            },

            /**
             * 构建菜单注册表，避免硬编码映射
             */
            buildMenuRegistry: function() {
                const menuItems = document.querySelectorAll('.navbar-vertical .dropdown-item[href], .navbar-vertical .nav-item:not(.dropdown) > .nav-link[href]');
                this.menuRegistry = [];
                this.menuPathIndex = new Map();

                menuItems.forEach(menuItem => {
                    if (menuItem.classList.contains('dropdown-toggle')) return;
                    if (menuItem.getAttribute('data-bs-toggle') === 'dropdown') return;

                    const href = menuItem.getAttribute('href');
                    if (!href || href === '#' || href.startsWith('javascript:')) return;

                    const iconElement = menuItem.querySelector('.dp-icon, .icon');

                    // 确定菜单项所属的横向菜单
                    // 当前简化实现: 所有后台功能菜单都归属于"首页"横向菜单
                    // 未来可通过 data-top-menu 属性或其他方式支持多个横向菜单
                    const topMenuId = menuItem.getAttribute('data-top-menu') || 'home';
                    const normalizedPath = this.normalizeMenuHref(href);
                    const currentPath = this.normalizeMenuHref(window.location.pathname || '');
                    const menuTitle = this.extractMenuTitle(menuItem);
                    const isHomeMenu = menuItem.hasAttribute('data-dp-workspace')
                        || normalizedPath === currentPath
                        || menuTitle === '后台首页'
                        || menuTitle === '工作台'
                        || /\/admin\/index\/index(?:\.html)?$/i.test(href);

                    this.menuRegistry.push({
                        element: menuItem,
                        href: href,
                        normalizedPath: normalizedPath,
                        iframeUrl: this.buildIframeUrlFromHref(href),
                        title: menuTitle,
                        iconClass: this.extractIconClass(iconElement),
                        topMenuId: topMenuId,  // 新增: 横向菜单关联
                        isHomeMenu: isHomeMenu
                    });

                    if (normalizedPath && !this.menuPathIndex.has(normalizedPath)) {
                        this.menuPathIndex.set(normalizedPath, this.menuRegistry[this.menuRegistry.length - 1]);
                    }
                });
            },

            /**
             * 规范化菜单href/iframe src，确保匹配可靠
             */
            normalizeMenuHref: function(href) {
                if (!href) return '';
                try {
                    const url = new URL(href, window.location.origin);
                    return url.pathname.replace(/\/+$/, '').replace(/\.html$/i, '');
                } catch (e) {
                    return href.replace(/\/+$/, '').replace(/\.html$/i, '');
                }
            },

            /**
             * 构建iframe加载地址（保留查询参数）
             */
            buildIframeUrlFromHref: function(href) {
                if (!href) return '';
                try {
                    const url = new URL(href, window.location.origin);
                    const basePath = url.pathname.replace(/\/+$/, '').replace(/\.html$/i, '');
                    const search = url.search || '';
                    return `${basePath}${search}`;
                } catch (e) {
                    return this.normalizeMenuHref(href);
                }
            },

            /**
             * 提取菜单项标题
             */
            extractMenuTitle: function(menuItem) {
                if (!menuItem) return '';
                return menuItem.textContent.replace(/\s+/g, ' ').trim();
            },

            /**
             * 提取菜单项图标类
             */
            extractIconClass: function(iconElement) {
                if (!iconElement || !iconElement.classList) return 'ti-file';
                const classList = Array.from(iconElement.classList);
                const tiClass = classList.find(cls => cls.indexOf('ti-') === 0);
                return tiClass || 'ti-file';
            },

            /**
             * 根据iframe地址查找菜单项
             */
            findMenuByIframeSrc: function(src) {
                if (!this.menuRegistry.length) {
                    this.buildMenuRegistry();
                }

                if (!src) return null;
                const normalized = this.normalizeMenuHref(src);
                if (!normalized) return null;
                return this.menuPathIndex.get(normalized) || null;
            },

            // 初始化主题同步功能
            initThemeSync: function() {
                // 监听主题变化
                this.observeThemeChanges();

                if (!this.boundThemeMessageHandler) {
                    this.boundThemeMessageHandler = (event) => {
                        if (!event.data || !this.validateIframeMessageEvent(event)) return;

                        if (event.data.type === 'request_theme_sync') {
                            this.syncThemeToIframe(event.source);
                        }

                        if (event.data.type === 'theme_changed_from_iframe') {
                            this.handleThemeChangeFromIframe(event.data);
                        }
                    };
                }

                bindGlobalEvent('tabs.window.message.theme', window, 'message', this.boundThemeMessageHandler);
            },

            // 监听主题变化
            observeThemeChanges: function() {
                if (typeof MutationObserver === 'undefined') return;

                const observer = new MutationObserver((mutations) => {
                    mutations.forEach((mutation) => {
                        if (mutation.type === 'attributes') {
                            const target = mutation.target;

                            // 检查是否是主题相关的属性变化
                            if (mutation.attributeName === 'data-bs-theme' ||
                                mutation.attributeName === 'data-bs-theme-primary' ||
                                mutation.attributeName === 'data-bs-theme-mode') {

                                // 延迟同步，确保主题已完全应用
                                setTimeout(() => {
                                    this.syncThemeToAllIframes();
                                }, 100);
                            }
                        }
                    });
                });

                // 监听html元素的属性变化
                observer.observe(document.documentElement, {
                    attributes: true,
                    attributeFilter: ['data-bs-theme', 'data-bs-theme-primary', 'data-bs-theme-mode', 'class']
                });

                // 监听body元素的类名变化（某些主题系统可能在body上设置类）
                observer.observe(document.body, {
                    attributes: true,
                    attributeFilter: ['class', 'data-bs-theme', 'data-bs-theme-primary']
                });
            },

            // 获取当前主题配置
            getCurrentTheme: function() {
                const htmlEl = document.documentElement;
                const bodyEl = document.body;

                return {
                    theme: htmlEl.getAttribute('data-bs-theme') || 'light',
                    primary: htmlEl.getAttribute('data-bs-theme-primary') || '',
                    mode: htmlEl.getAttribute('data-bs-theme-mode') || '',
                    htmlClass: htmlEl.className,
                    bodyClass: bodyEl.className,
                    // 获取所有data-bs-theme开头的属性
                    customAttributes: this.getThemeAttributes(htmlEl)
                };
            },

            // 获取所有主题相关属性
            getThemeAttributes: function(element) {
                const attributes = {};
                if (element && element.attributes) {
                    for (let i = 0; i < element.attributes.length; i++) {
                        const attr = element.attributes[i];
                        if (attr.name.startsWith('data-bs-theme')) {
                            attributes[attr.name] = attr.value;
                        }
                    }
                }
                return attributes;
            },

            // 同步主题到所有iframe
            syncThemeToAllIframes: function() {
                const themeConfig = this.getCurrentTheme();

                // 获取所有iframe元素
                const iframes = document.querySelectorAll('.content-iframe');

                iframes.forEach(iframe => {
                    if (iframe.contentWindow && iframe.src && iframe.src !== '') {
                        try {
                            iframe.contentWindow.postMessage({
                                type: 'theme_sync',
                                theme: themeConfig
                            }, '*');
                        } catch (e) {
                            // 忽略跨域错误
                        }
                    }
                });
            },

            // 同步主题到特定iframe
            syncThemeToIframe: function(iframeWindow) {
                if (!iframeWindow) return;

                const themeConfig = this.getCurrentTheme();

                try {
                    iframeWindow.postMessage({
                        type: 'theme_sync',
                        theme: themeConfig
                    }, '*');
                } catch (e) {
                    // 忽略跨域错误
                }
            },

            // 处理来自iframe的主题变化
            handleThemeChangeFromIframe: function(data) {
                if (!data.themeKey || typeof data.themeValue === 'undefined') return;

                // 在主页面应用主题变化
                const attrName = 'data-bs-' + data.themeKey;
                document.documentElement.setAttribute(attrName, data.themeValue);

                // 同步到localStorage
                window.localStorage.setItem('tabler-' + data.themeKey, data.themeValue);

                // 同步到其他iframe
                setTimeout(() => {
                    this.syncThemeToAllIframes();
                }, 50);
            },

            // 绑定标签页切换事件
            bindTabEvents: function() {
                if (!this.tabShowHandler) {
                    this.tabShowHandler = (event) => {
                        const tabTrigger = event.target;
                        if (tabTrigger && tabTrigger.matches('[data-bs-toggle="tab"]')) {
                            this.loadTabContent(tabTrigger);
                        }
                    };
                }

                if (!this.tabShownHandler) {
                    this.tabShownHandler = (event) => {
                        const tabTrigger = event.target;
                        if (!tabTrigger || !tabTrigger.matches('[data-bs-toggle="tab"]')) {
                            return;
                        }

                        this.syncSidebarWithActiveTab(tabTrigger);

                        const targetId = tabTrigger.getAttribute('href');
                        if (targetId) {
                            this.saveActiveTab(targetId);
                            const normalizedId = targetId.startsWith('#') ? targetId.substring(1) : targetId;
                            this.updateHorizontalNavActive(normalizedId);
                        }
                    };
                }

                bindGlobalEvent('tabs.document.show', document, 'show.bs.tab', this.tabShowHandler);
                bindGlobalEvent('tabs.document.shown', document, 'shown.bs.tab', this.tabShownHandler);
            },

            // 加载当前活跃标签页的内容
            loadActiveTabContent: function() {
                const activeTab = document.querySelector('[data-bs-toggle="tab"].active');
                if (activeTab) {
                    this.loadTabContent(activeTab);
                }
            },

            // 加载标签页内容
            loadTabContent: function(tabElement, forceReload = false) {
                const targetId = tabElement.getAttribute('href');
                if (!targetId) return;

                if (!forceReload && this.loadingTabs.has(targetId)) {
                    Logger.debug(`[Dolphin Tabs] 标签 ${targetId} 正在加载，忽略重复请求`);
                    return;
                }

                const context = this.getTabContext(targetId);
                if (!context || !context.iframe || !context.loadingIndicator) return;

                const { tabPane, iframe, loadingIndicator } = context;

                // 检查是否有暂停的iframe需要恢复
                if (!forceReload && iframe.hasAttribute('data-paused-src')) {
                    Logger.debug('[Dolphin Tabs] 检测到暂停的iframe，正在恢复...');
                    const restored = this.resumeTabIframe(targetId);
                    if (restored) {
                        return; // 恢复成功，无需重新加载
                    }
                }

                if (forceReload) {
                    this.loadingTabs.delete(targetId);
                    this.prepareForceReload(targetId, context);
                } else if (this.loadedTabs.has(targetId)) {
                    return;
                }

                let dataSrc = iframe.getAttribute('data-src');
                if (!dataSrc) {
                    const currentSrc = iframe.getAttribute('src');
                    if (currentSrc && currentSrc !== 'about:blank') {
                        dataSrc = currentSrc;
                        iframe.setAttribute('data-src', dataSrc);
                    }
                }
                if (!dataSrc) return;

                this.loadingTabs.add(targetId);

                // 显示加载指示器
                loadingIndicator.style.display = 'flex';
                iframe.style.display = 'none';
                this.setTabLoadingState(targetId, true);

                // 清除之前的事件监听器
                iframe.onload = null;
                iframe.onerror = null;

                // 设置加载超时计时器
                const timeoutId = setTimeout(() => {
                    if (!this.loadedTabs.has(targetId)) {
                        this.onIframeTimeout(iframe, loadingIndicator, targetId);
                    }
                }, 20000); // 20秒超时

                // 处理iframe加载成功事件
                iframe.onload = () => {
                    clearTimeout(timeoutId);
                    this.onIframeLoad(iframe, loadingIndicator, targetId);
                };

                // 处理iframe加载错误事件
                iframe.onerror = () => {
                    clearTimeout(timeoutId);
                    this.onIframeError(iframe, loadingIndicator, targetId);
                };

                // 立即设置src以第一时间触发加载
                iframe.removeAttribute('loading');
                iframe.src = dataSrc;

                // 额外的内容检查机制（针对跨域限制）
                const checkInterval = setInterval(() => {
                    if (this.loadedTabs.has(targetId)) {
                        clearInterval(checkInterval);
                        return;
                    }

                    try {
                        // 尝试检查iframe文档状态
                        if (iframe.contentDocument && iframe.contentDocument.readyState === 'complete') {
                            clearInterval(checkInterval);
                            clearTimeout(timeoutId);
                            this.onIframeLoad(iframe, loadingIndicator, targetId);
                        }
                    } catch (e) {
                        // 跨域情况下无法访问contentDocument，依赖onload事件
                    }
                }, 1000);

                // 15秒后清理检查interval
                setTimeout(() => {
                    clearInterval(checkInterval);
                }, 15000);
            },

            // iframe加载成功处理
            onIframeLoad: function(iframe, loadingIndicator, targetId) {
                if (this.loadedTabs.has(targetId)) return;

                // 隐藏加载指示器
                if (loadingIndicator) {
                    loadingIndicator.style.display = 'none';
                    loadingIndicator.style.visibility = 'hidden';
                    loadingIndicator.style.opacity = '0';
                    loadingIndicator.classList.add('d-none');
                }

                // 显示iframe
                if (iframe) {
                    iframe.style.display = 'block';
                    iframe.style.visibility = 'visible';
                    iframe.style.opacity = '1';
                    iframe.classList.remove('d-none');
                }

                this.loadedTabs.add(targetId);
                this.setTabLoadingState(targetId, false);
                this.loadingTabs.delete(targetId);
            },

            // iframe加载错误处理
            onIframeError: function(iframe, loadingIndicator, targetId) {
                loadingIndicator.innerHTML = `
                    <div class="alert alert-warning d-flex align-items-center" role="alert">
                        <div class="me-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon alert-icon">
                                <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
                                <path d="M12 9v4"/>
                                <path d="m12 17 .01 0"/>
                            </svg>
                        </div>
                        <div>
                            <h4 class="alert-title">加载失败</h4>
                            <div class="text-secondary">无法加载页面内容，请检查网络连接或稍后重试。</div>
                            <button class="btn btn-sm btn-outline-warning mt-2" onclick="Dolphin.getIframeManager().retryLoad('${targetId}')">重新加载</button>
                        </div>
                    </div>
                `;
                this.setTabLoadingState(targetId, false);
                this.loadingTabs.delete(targetId);
            },

            // iframe加载超时处理
            onIframeTimeout: function(iframe, loadingIndicator, targetId) {
                if (this.loadedTabs.has(targetId)) return;

                // 检查iframe是否实际有内容
                if (iframe.src && iframe.src !== '' && iframe.src !== 'about:blank') {
                    loadingIndicator.innerHTML = `
                        <div class="alert alert-info d-flex align-items-center" role="alert">
                            <div class="me-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon alert-icon">
                                    <circle cx="12" cy="12" r="10"/>
                                    <path d="M12 6v6l4 2"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="alert-title">检测到内容加载</h4>
                                <div class="text-secondary">页面内容似乎已加载完成，但加载事件未触发。</div>
                                <div class="btn-group mt-2">
                                    <button class="btn btn-sm btn-success" onclick="Dolphin.getIframeManager().forceComplete('${targetId}')">显示内容</button>
                                    <button class="btn btn-sm btn-outline-info" onclick="Dolphin.getIframeManager().retryLoad('${targetId}')">重新加载</button>
                                </div>
                            </div>
                        </div>
                    `;
                } else {
                    loadingIndicator.innerHTML = `
                        <div class="alert alert-warning d-flex align-items-center" role="alert">
                            <div class="me-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon alert-icon">
                                    <circle cx="12" cy="12" r="10"/>
                                    <path d="M12 6v6l4 2"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="alert-title">加载超时</h4>
                                <div class="text-secondary">页面加载时间过长，请检查网络连接或稍后重试。</div>
                                <button class="btn btn-sm btn-outline-warning mt-2" onclick="Dolphin.getIframeManager().retryLoad('${targetId}')">重新加载</button>
                            </div>
                        </div>
                    `;
                }
                this.setTabLoadingState(targetId, false);
                this.loadingTabs.delete(targetId);
            },

            // 对外提供刷新标签页的能力
            refreshTab: function(tabId) {
                const navItem = document.querySelector(`#openedTabs [data-tab-id="${tabId}"], #pinnedTabs [data-tab-id="${tabId}"]`);
                if (!navItem) {
                    Dolphin.warning('未找到对应的标签页');
                    return;
                }

                const navLink = navItem.querySelector('.nav-link');
                if (!navLink) {
                    Dolphin.warning('标签缺少可刷新的链接');
                    return;
                }

                const targetId = navLink.getAttribute('href');
                if (!targetId) {
                    Dolphin.warning('无法定位标签内容面板');
                    return;
                }

                // 如果标签处于暂停状态，先恢复再退出
                if (this.resumeTabIframe(targetId)) {
                    return;
                }

                const context = this.getTabContext(targetId);
                if (!context) {
                    this.loadTabContent(navLink, true);
                    return;
                }

                this.prepareForceReload(targetId, context);

                const hasRealSrc = context.iframe && context.iframe.getAttribute('src') &&
                    context.iframe.getAttribute('src') !== '' &&
                    context.iframe.getAttribute('src') !== 'about:blank';

                if (hasRealSrc && this.attemptDirectReload(context.iframe, targetId, context.loadingIndicator)) {
                    return;
                }

                this.loadTabContent(navLink, true);
            },

            // 重新加载标签页
            retryLoad: function(targetId, triggerLink = null) {
                const navLink = triggerLink || document.querySelector(`[href="${targetId}"]`);
                if (!navLink) return;
                setTimeout(() => {
                    this.loadTabContent(navLink, true);
                }, 50);
            },

            // 强制完成加载
            forceComplete: function(targetId) {
                const tabPane = document.querySelector(targetId);
                if (!tabPane) return;

                const iframe = tabPane.querySelector('.content-iframe');
                const loadingIndicator = tabPane.querySelector('.loading-indicator');

                if (iframe && loadingIndicator) {
                    this.onIframeLoad(iframe, loadingIndicator, targetId);
                }
            },

            // 获取加载文本
            getLoadingText: function(target) {
                const tabPane = typeof target === 'string' ? document.querySelector(target) : target;
                if (!tabPane) return '正在加载...';
                const indicator = tabPane.querySelector('.loading-indicator');
                if (!indicator) return '正在加载...';
                return indicator.getAttribute('data-loading-text') || indicator.textContent.trim() || '正在加载...';
            },

            resetLoadingIndicator: function(indicator, text) {
                if (!indicator) return;
                const content = text || indicator.getAttribute('data-loading-text') || '正在加载...';
                indicator.innerHTML = `
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">加载中...</span>
                    </div>
                    <span class="ms-2">${content}</span>
                `;
                indicator.style.display = 'flex';
                indicator.style.visibility = 'visible';
                indicator.style.opacity = '1';
                indicator.classList.remove('d-none');
            },

            getTabContext: function(targetId) {
                const tabPane = document.querySelector(targetId);
                if (!tabPane) return null;
                return {
                    tabPane,
                    iframe: tabPane.querySelector('.content-iframe'),
                    loadingIndicator: tabPane.querySelector('.loading-indicator')
                };
            },

            prepareForceReload: function(targetId, context) {
                if (!context) return;
                const { tabPane, iframe, loadingIndicator } = context;
                this.loadedTabs.delete(targetId);
                this.loadingTabs.delete(targetId);
                this.setTabLoadingState(targetId, true);

                if (loadingIndicator) {
                    this.resetLoadingIndicator(loadingIndicator, this.getLoadingText(tabPane));
                }

                if (iframe) {
                    iframe.onload = null;
                    iframe.onerror = null;
                    iframe.style.display = 'none';
                    iframe.classList.add('d-none');

                    if (!iframe.getAttribute('data-src')) {
                        const currentSrc = iframe.getAttribute('src');
                        if (currentSrc && currentSrc !== 'about:blank') {
                            iframe.setAttribute('data-src', currentSrc);
                        }
                    }
                }
            },

            attemptDirectReload: function(iframe, targetId, loadingIndicator) {
                let canDirectReload = false;
                try {
                    canDirectReload = !!(iframe &&
                        iframe.contentWindow &&
                        iframe.contentWindow.location &&
                        iframe.getAttribute('src') &&
                        iframe.getAttribute('src') !== '' &&
                        iframe.getAttribute('src') !== 'about:blank');
                } catch (err) {
                    canDirectReload = false;
                }

                if (!canDirectReload) {
                    return false;
                }

                const timeoutId = setTimeout(() => {
                    if (!this.loadedTabs.has(targetId)) {
                        this.onIframeTimeout(iframe, loadingIndicator, targetId);
                    }
                }, 20000);

                iframe.onload = () => {
                    clearTimeout(timeoutId);
                    iframe.onload = null;
                    iframe.onerror = null;
                    this.onIframeLoad(iframe, loadingIndicator, targetId);
                };

                iframe.onerror = () => {
                    clearTimeout(timeoutId);
                    iframe.onload = null;
                    iframe.onerror = null;
                    this.onIframeError(iframe, loadingIndicator, targetId);
                };

                try {
                    iframe.contentWindow.location.reload();
                    return true;
                } catch (reloadErr) {
                    clearTimeout(timeoutId);
                    iframe.onload = null;
                    iframe.onerror = null;
                    Logger.debug('[Dolphin Tabs] iframe直接刷新失败，回退到重新加载', reloadErr);
                    return false;
                }
            },

            // 清理单个标签的iframe资源
            cleanupTabIframe: function(targetId) {
                this.loadedTabs.delete(targetId);
                this.loadingTabs.delete(targetId);
                this.setTabLoadingState(targetId, false);

                const tabPane = document.querySelector(targetId);
                if (!tabPane) return;

                const iframe = tabPane.querySelector('.content-iframe');
                if (iframe) {
                    const dataSrc = iframe.getAttribute('data-src') || iframe.getAttribute('src');
                    if (dataSrc) {
                        this.tabUrlIndex.delete(dataSrc);
                    }
                    // 清空iframe src，释放资源
                    iframe.src = 'about:blank';
                    iframe.onload = null;
                    iframe.onerror = null;
                }

                // 移除整个tab-pane元素
                tabPane.remove();
            },

            // 批量清理多个标签的iframe资源
            cleanupMultipleIframes: function(tabItems) {
                let cleanedCount = 0;
                tabItems.forEach(tabItem => {
                    const tabLink = tabItem.querySelector('.nav-link');
                    if (tabLink) {
                        const targetId = tabLink.getAttribute('href');
                        if (targetId) {
                            this.cleanupTabIframe(targetId);
                            this.removeTabState(targetId);
                            cleanedCount++;
                        }
                    }
                });
            },

            // ========================
            // 动态标签页创建功能
            // ========================

            // 标签页计数器
            tabCounter: 100,
            // 最大标签页数量限制
            maxTabsLimit: 15,

            /**
             * 创建新的iframe标签页
             * @param {Object} options 配置选项
             * @param {string} options.id 标签页唯一ID
             * @param {string} options.title 标签页标题
             * @param {string} options.url 加载的URL
             * @param {string} options.icon 图标类名（可选）
             * @param {boolean} options.closable 是否可关闭（默认true）
             * @param {boolean} options.activate 是否立即激活（默认true）
             */
            createDynamicTab: function(options) {
                const defaults = {
                    id: null,
                    title: '新标签页',
                    url: '',
                    icon: 'ti-file',
                    closable: true,
                    activate: true
                };

                const config = Object.assign({}, defaults, options);

                // 生成唯一ID
                if (!config.id) {
                    config.id = `dynamic-tab-${this.tabCounter++}`;
                }

                // 检查标签页数量限制
                if (!this.checkTabLimit()) {
                    return false;
                }

                // 检查是否已存在相同URL的标签页
                const existingTab = this.findTabByUrl(config.url);
                if (existingTab) {
                    this.activateExistingTab(existingTab);
                    return existingTab.id;
                }

                // 创建标签页HTML结构
                const tabId = `tab-${config.id}`;
                const navItem = this.createTabNavItem(config, tabId);
                const tabPane = this.createTabPane(config, tabId);

                // 添加到DOM
                this.insertTabToDOM(navItem, tabPane);

                // 保存标签页状态到localStorage
                this.saveTabState(config, tabId);

                this.tabUrlIndex.set(config.url, `#${tabId}`);

                // 激活标签页
                if (config.activate) {
                    const navLink = navItem.querySelector('.nav-link');
                    if (navLink) {
                        this.activateTabByLink(navLink);
                    }
                }

                return tabId;
            },

            /**
             * 检查标签页数量限制
             */
            checkTabLimit: function() {
                const currentTabs = document.querySelectorAll('#openedTabs .nav-item').length;
                if (currentTabs >= this.maxTabsLimit) {
                    Dolphin.warning(`标签页数量已达到上限（${this.maxTabsLimit}个），请关闭一些标签页后再试`);
                    return false;
                }
                return true;
            },

            /**
             * 根据URL查找已存在的标签页
             */
            findTabByUrl: function(url) {
                const indexedTargetId = this.tabUrlIndex.get(url);
                if (indexedTargetId) {
                    const indexedPane = document.querySelector(`#tabContent ${indexedTargetId}`);
                    const indexedNavItem = document.querySelector(`#openedTabs [href="${indexedTargetId}"], #pinnedTabs [href="${indexedTargetId}"]`)?.closest('.nav-item');
                    if (indexedPane && indexedNavItem) {
                        return {
                            id: indexedPane.id,
                            navItem: indexedNavItem,
                            tabPane: indexedPane,
                            url: url
                        };
                    }
                    this.tabUrlIndex.delete(url);
                }

                const iframes = document.querySelectorAll('.content-iframe[data-src]');
                for (let iframe of iframes) {
                    if (iframe.getAttribute('data-src') === url) {
                        const tabPane = iframe.closest('.tab-pane');
                        const tabId = tabPane ? tabPane.id : null;
                        const navItem = tabId ? document.querySelector(`[href="#${tabId}"]`)?.closest('.nav-item') : null;

                        if (navItem) {
                            this.tabUrlIndex.set(url, `#${tabId}`);
                            return {
                                id: tabId,
                                navItem: navItem,
                                tabPane: tabPane,
                                url: url
                            };
                        }
                    }
                }
                return null;
            },

            /**
             * 激活已存在的标签页
             */
            activateExistingTab: function(tabInfo) {
                const navLink = tabInfo.navItem.querySelector('.nav-link');
                if (navLink) {
                    this.activateTabByLink(navLink);
                }
            },

            /**
             * 创建标签页导航项
             */
            createTabNavItem: function(config, tabId) {
                const navItem = document.createElement('li');
                navItem.className = 'nav-item';
                navItem.setAttribute('role', 'presentation');
                navItem.setAttribute('data-tab-id', config.id);

                const closeButton = config.closable ?
                    '<button type="button" class="btn-close btn-close-tab" aria-label="关闭标签页"></button>' : '';

                // 使用真实的图标HTML或回退到SVG图标
                let iconHTML;
                if (config.realIconHTML) {
                    // 使用从侧边栏提取的真实图标
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = config.realIconHTML;
                    const iconElement = tempDiv.querySelector('.dp-icon');

                    if (iconElement) {
                        // 创建新的图标元素，保持原有的类名和内容，但调整大小属性
                        iconElement.className = iconElement.className.replace(/dp-icon/g, 'icon');
                        iconElement.style.fontSize = ''; // 清除字体大小样式
                        iconElement.setAttribute('width', '14');
                        iconElement.setAttribute('height', '14');
                        iconHTML = iconElement.outerHTML;
                    } else {
                        // 如果没有找到图标元素，使用原始HTML并简单替换类名
                        iconHTML = config.realIconHTML.replace(/dp-icon/g, 'icon');
                    }
                } else {
                    // 回退到SVG图标
                    iconHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                             stroke-linejoin="round" class="icon">
                            ${this.getIconPath ? this.getIconPath(config.icon) : '<circle cx="12" cy="12" r="10"/>'}
                        </svg>`;
                }
                iconHTML = this.wrapIconWithLoader(iconHTML);

                navItem.innerHTML = `
                    <a href="#${tabId}" class="nav-link d-flex align-items-center"
                       data-bs-toggle="tab" role="tab" aria-controls="${tabId}" aria-selected="false">
                        ${iconHTML}
                        <span class="tab-title">${config.title}</span>
                        ${closeButton}
                    </a>
                `;

                // 为新建标签设置默认的活跃时间戳，避免内存管理缺失数据
                const now = Date.now();
                navItem.setAttribute('data-last-active', (now - 30000).toString());

                return navItem;
            },

            /**
             * 创建标签页内容面板
             */
            createTabPane: function(config, tabId) {
                const tabPane = document.createElement('div');
                tabPane.className = 'tab-pane fade iframe-container';
                tabPane.id = tabId;
                tabPane.setAttribute('role', 'tabpanel');
                tabPane.setAttribute('aria-labelledby', tabId);

                const loadingText = `正在加载 ${config.title}...`;

                tabPane.innerHTML = `
                    <div class="loading-indicator d-flex align-items-center justify-content-center" style="height: 400px;">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">加载中...</span>
                        </div>
                        <span class="ms-2">${loadingText}</span>
                    </div>
                    <iframe class="content-iframe"
                            data-src="${config.url}"
                            referrerpolicy="strict-origin-when-cross-origin">
                    </iframe>
                `;

                const indicator = tabPane.querySelector('.loading-indicator');
                if (indicator) {
                    indicator.setAttribute('data-loading-text', loadingText);
                }

                return tabPane;
            },

            /**
             * 将标签页插入DOM
             */
            insertTabToDOM: function(navItem, tabPane) {
                // 添加导航项到标签栏
                const openedTabs = document.getElementById('openedTabs');
                if (openedTabs) {
                    openedTabs.appendChild(navItem);
                }

                // 添加内容面板到内容区
                const tabContent = document.getElementById('tabContent');
                if (tabContent) {
                    tabContent.appendChild(tabPane);
                }

                // 绑定事件
                this.bindNewTabEvents(navItem);
            },

            /**
             * 为新标签页绑定事件
             */
            bindNewTabEvents: function(navItem) {
                const navLink = navItem.querySelector('.nav-link');
                const closeBtn = navItem.querySelector('.btn-close-tab');

                // 绑定标签页切换事件
                if (navLink) {
                    navLink.addEventListener('click', (event) => {
                        const linkTarget = event.currentTarget || event.target;
                        this.loadTabContent(linkTarget);
                    });
                }

                // 绑定关闭按钮事件
                if (closeBtn) {
                    closeBtn.addEventListener('click', (event) => {
                        event.preventDefault();
                        event.stopPropagation();
                        this.closeDynamicTab(navItem);
                    });
                }
            },

            /**
             * 关闭动态标签页
             */
            closeDynamicTab: function(navItem) {
                const navLink = navItem.querySelector('.nav-link');
                if (!navLink) return;

                const targetId = navLink.getAttribute('href');

                // 清理资源
                if (targetId) {
                    this.cleanupTabIframe(targetId);
                }

                // 如果是当前活跃标签，先激活其他标签
                if (navLink.classList.contains('active')) {
                    tabBatchActions.activateNextTab(navItem);
                }

                // 删除标签页状态
                this.removeTabState(targetId);

                // 移除DOM元素
                navItem.remove();

                // 更新滚动按钮状态
                setTimeout(() => {
                    if (typeof tabScrollManager !== 'undefined') {
                        tabScrollManager.updateButtonState();
                    }
                }, 100);

                syncFavoriteActiveState();
            },

            /**
             * 激活标签页
             */
            activateTab: function(navLink, options = {}) {
                this.activateTabByLink(navLink, options);
            },

            /**
             * 统一激活入口：激活标签 + 加载内容 + 同步菜单 + 持久化
             * @param {Element} navLink
             * @param {Object} options
             */
            activateTabByLink: function(navLink, options = {}) {
                if (!navLink) return;

                const {
                    loadContent = true,
                    syncMenus = true,
                    persist = true
                } = options;

                if (typeof utils !== 'undefined' && typeof utils.activateTab === 'function') {
                    utils.activateTab(navLink);
                } else if (typeof bootstrap !== 'undefined') {
                    const tabTrigger = new bootstrap.Tab(navLink);
                    tabTrigger.show();
                }

                const navItem = navLink.closest('.nav-item');
                if (navItem) {
                    navItem.setAttribute('data-last-active', Date.now().toString());
                }

                const targetId = navLink.getAttribute('href') || navLink.getAttribute('data-bs-target');
                if (persist && targetId && typeof this.saveActiveTab === 'function') {
                    this.saveActiveTab(targetId);
                }

                if (syncMenus) {
                    this.syncSidebarWithActiveTab(navLink);
                    if (targetId) {
                        const tabId = targetId.startsWith('#') ? targetId.substring(1) : targetId;
                        this.updateHorizontalNavActive(tabId);
                    }
                }

                if (loadContent && typeof this.loadTabContent === 'function') {
                    this.loadTabContent(navLink);
                }
            },

            /**
             * 同步侧边栏菜单状态与当前活跃标签页
             * @param {Element} navLink - 活跃的标签页链接
             */
            syncSidebarWithActiveTab: function(navLink) {
                if (!this.menuRegistry.length) {
                    this.buildMenuRegistry();
                }

                // 检查是否是首页标签
                if (navLink.getAttribute('href') === '#tab-home') {
                    this.clearSidebarMenuActive();

                    // 激活"后台首页"菜单项（优先使用注册表匹配，避免href差异）
                    const homeEntry = this.menuRegistry.find(entry => entry && entry.isHomeMenu);
                    const homeMenuItem = homeEntry?.element || document.querySelector('.navbar-vertical .nav-link[href="./"]');
                    if (homeMenuItem) {
                        this.updateSidebarMenuActive(homeMenuItem);
                    }
                    return;
                }

                // 获取标签页对应的URL
                const tabPane = document.querySelector(navLink.getAttribute('href'));
                if (!tabPane) return;

                const iframe = tabPane.querySelector('.content-iframe');
                if (!iframe) return;

                const iframeSrc = iframe.getAttribute('src') || iframe.getAttribute('data-src');
                if (!iframeSrc) return;

                const matchedMenu = this.findMenuByIframeSrc(iframeSrc);

                if (matchedMenu && matchedMenu.element) {
                    this.updateSidebarMenuActive(matchedMenu.element);
                } else {
                    // 如果没有匹配的iframe菜单项，清除所有active状态
                    this.clearSidebarMenuActive();
                }
            },

            /**
             * 获取图标SVG路径
             */
            getIconPath: function(iconClass) {
                const iconPaths = {
                    'ti-users': '<path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" /><path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" />',
                    'ti-user-check': '<path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" /><path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" /><path d="m9 12 2 2 4-4" />',
                    'ti-settings': '<path d="M10.325 4.317c.426 -1.756 2.924 -1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543 -.94 3.31 .826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756 .426 1.756 2.924 0 3.35a1.724 1.724 0 0 0 -1.066 2.573c.94 1.543 -.826 3.31 -2.37 2.37a1.724 1.724 0 0 0 -2.572 1.065c-.426 1.756 -2.924 1.756 -3.35 0a1.724 1.724 0 0 0 -2.573 -1.066c-1.543 .94 -3.31 -.826 -2.37 -2.37a1.724 1.724 0 0 0 -1.065 -2.572c-1.756 -.426 -1.756 -2.924 0 -3.35a1.724 1.724 0 0 0 1.066 -2.573c-.94 -1.543 .826 -3.31 2.37 -2.37c1 .608 2.296 .07 2.572 -1.065z" /><path d="M9 12a3 3 0 1 0 6 0a3 3 0 0 0 -6 0" />',
                    'ti-tool': '<path d="M9 6l11 0" /><path d="M9 12l11 0" /><path d="M9 18l11 0" /><path d="M5 6l0 .01" /><path d="M5 12l0 .01" /><path d="M5 18l0 .01" />',
                    'ti-hierarchy-2': '<path d="M10 6h4" /><path d="M12 4v2" /><path d="M12 12v2" /><path d="M8 16h8" /><path d="M8 20h8" /><path d="M9 8h6a1 1 0 0 1 1 1v1a1 1 0 0 1 -1 1h-6a1 1 0 0 1 -1 -1v-1a1 1 0 0 1 1 -1z" />',
                    'ti-files': '<path d="M14 3v4a1 1 0 0 0 1 1h4" /><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" />',
                    'ti-file-text': '<path d="M14 3v4a1 1 0 0 0 1 1h4" /><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" /><path d="M9 9l1 0" /><path d="M9 13l6 0" /><path d="M9 17l6 0" />',
                    'ti-components': '<path d="M3 12l3 3l3 -3l-3 -3z" /><path d="M15 12l3 3l3 -3l-3 -3z" /><path d="M9 6l3 3l3 -3l-3 -3z" /><path d="M9 18l3 3l3 -3l-3 -3z" />',
                    'ti-plug': '<path d="M9.785 6l8.215 8.215l-2.054 2.054a5.81 5.81 0 1 1 -8.215 -8.215l2.054 -2.054z" /><path d="M4 20l3.5 -3.5" /><path d="M15 4l-3.5 3.5" /><path d="M20 9l-3.5 3.5" />',
                    'ti-icons': '<path d="M6.5 6.5m-3.5 0a3.5 3.5 0 1 0 7 0a3.5 3.5 0 1 0 -7 0" /><path d="M2.5 21h8l-4 -7z" /><path d="M14 3l7 7" /><path d="M14 10l7 -7" /><path d="M14 14h7v7h-7z" />',
                    'ti-file': '<path d="M14 3v4a1 1 0 0 0 1 1h4" /><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" />'
                };

                return iconPaths[iconClass] || iconPaths['ti-file'];
            },

            wrapIconWithLoader: function(iconHTML) {
                return `<span class="icon-wrapper"><span class="icon-loading-ring"></span>${iconHTML}</span>`;
            },

            getNavLinksByTarget: function(targetId) {
                if (!targetId) return [];
                return Array.from(document.querySelectorAll(`#pinnedTabs .nav-link[href="${targetId}"], #openedTabs .nav-link[href="${targetId}"]`));
            },

            setTabLoadingState: function(targetId, isLoading) {
                const links = this.getNavLinksByTarget(targetId);
                links.forEach(link => {
                    if (isLoading) {
                        link.classList.add('loading');
                    } else {
                        link.classList.remove('loading');
                    }
                });
            },

            /**
             * 绑定侧边栏菜单事件
             */
            bindSidebarMenuEvents: function() {
                if (!this.menuRegistry.length) {
                    this.buildMenuRegistry();
                }

                const handlerKey = '__dolphinTabMenuClickHandler';
                this.menuRegistry.forEach(entry => {
                    const menuItem = entry.element;
                    if (!menuItem) return;

                    if (menuItem[handlerKey]) {
                        menuItem.removeEventListener('click', menuItem[handlerKey]);
                    }

                    const menuClickHandler = (event) => {
                        event.preventDefault(); // 阻止默认链接行为

                        // 1. 更新侧边栏菜单active状态
                        this.updateSidebarMenuActive(menuItem);

                        // 首页菜单直接激活默认首页标签
                        if (entry.isHomeMenu || entry.href === './' || /admin\/index\/index/i.test(entry.href)) {
                            const homeTabLink = document.querySelector('#pinnedTabs .nav-link[href="#tab-home"], #openedTabs .nav-link[href="#tab-home"]');
                            if (homeTabLink) {
                                this.activateTabByLink(homeTabLink);
                            }
                            return;
                        }

                        // 2. 创建或激活标签页
                        const iconElement = menuItem.querySelector('.dp-icon, .icon');
                        const realIconHTML = iconElement ? iconElement.outerHTML : null;
                        const tabTitle = entry.title || this.extractMenuTitle(menuItem) || '新标签页';
                        const iframeUrl = entry.iframeUrl || entry.normalizedPath || entry.href;

                        this.createDynamicTab({
                            id: this.generateTabId(entry.href),
                            title: tabTitle,
                            url: iframeUrl,
                            icon: entry.iconClass || this.extractIconClass(iconElement),
                            realIconHTML: realIconHTML,
                            closable: true,
                            activate: true
                        });
                    };

                    menuItem.addEventListener('click', menuClickHandler);
                    menuItem[handlerKey] = menuClickHandler;
                });
            },

            /**
             * 根据href生成标签页ID
             */
            generateTabId: function(href) {
                // 提取路径并转换为合法的ID格式
                return href
                    .replace(/\W+/g, '-')
                    .replace(/^-+|-+$/g, '')
                    .replace(/-+/g, '-');
            },

            /**
             * 更新侧边栏菜单active状态
             * @param {Element} clickedItem - 被点击的菜单项
             */
            updateSidebarMenuActive: function(clickedItem) {
                this.clearSidebarMenuActive({
                    collapseDropdowns: this.shouldAutoCollapseSidebarDropdowns()
                });

                // 顶级无子菜单：active加到li.nav-item
                const directNavItem = clickedItem.matches('.nav-link')
                    ? clickedItem.closest('.navbar-vertical .nav-item:not(.dropdown)')
                    : null;
                if (directNavItem) {
                    directNavItem.classList.add('active');
                    this.activeSidebarTopItem = directNavItem;
                    return;
                }

                // 下拉子菜单：保持子项高亮
                clickedItem.classList.add('active');
                this.activeSidebarDropdownItem = clickedItem;

                this.activateSidebarParentDropdown(clickedItem);
            },

            /**
             * 是否自动折叠侧边栏下拉菜单
             * 移动端保持自动收起，桌面端保留展开状态
             * @returns {boolean}
             */
            shouldAutoCollapseSidebarDropdowns: function() {
                if (typeof window === 'undefined') return true;
                if (typeof window.matchMedia === 'function') {
                    return window.matchMedia('(max-width: 991.98px)').matches;
                }
                return window.innerWidth < 992;
            },

            /**
             * 清理侧边栏菜单active状态
             * @param {Object} options
             * @param {boolean} options.collapseDropdowns - 是否折叠父级下拉菜单
             */
            clearSidebarMenuActive: function(options = {}) {
                const {
                    collapseDropdowns = this.shouldAutoCollapseSidebarDropdowns()
                } = options;

                // 首次执行做一次全量清理，后续使用已记录节点做增量清理
                if (!this.sidebarStateInitialized) {
                    const allDropdownItems = document.querySelectorAll('.navbar-vertical .dropdown-item');
                    allDropdownItems.forEach(item => item.classList.remove('active'));

                    const allNavItems = document.querySelectorAll('.navbar-vertical .nav-item');
                    allNavItems.forEach(item => item.classList.remove('active'));
                    const allDropends = document.querySelectorAll('.navbar-vertical .dropend');
                    allDropends.forEach(item => item.classList.remove('active'));

                    const allDropdownToggles = document.querySelectorAll('.navbar-vertical .nav-item.dropdown > .nav-link.dropdown-toggle');
                    const allNestedDropdownToggles = document.querySelectorAll('.navbar-vertical .dropend > .dropdown-item.dropdown-toggle');
                    allDropdownToggles.forEach(toggle => {
                        toggle.classList.remove('active');
                        if (collapseDropdowns) {
                            toggle.setAttribute('aria-expanded', 'false');
                        }
                    });
                    allNestedDropdownToggles.forEach(toggle => {
                        toggle.classList.remove('active');
                        if (collapseDropdowns) {
                            toggle.setAttribute('aria-expanded', 'false');
                        }
                    });

                    if (collapseDropdowns) {
                        const allDropdownMenus = document.querySelectorAll('.navbar-vertical .nav-item.dropdown > .dropdown-menu, .navbar-vertical .dropend > .dropdown-menu');
                        allDropdownMenus.forEach(menu => menu.classList.remove('show'));
                    }

                    this.sidebarStateInitialized = true;
                    this.activeSidebarDropdownItem = null;
                    this.activeSidebarTopItem = null;
                    this.activeSidebarParentDropdowns = [];
                    return;
                }

                if (this.activeSidebarDropdownItem) {
                    this.activeSidebarDropdownItem.classList.remove('active');
                }

                if (this.activeSidebarTopItem) {
                    this.activeSidebarTopItem.classList.remove('active');
                }

                if (Array.isArray(this.activeSidebarParentDropdowns)) {
                    this.activeSidebarParentDropdowns.forEach(parentDropdown => {
                        if (!parentDropdown) return;
                        parentDropdown.classList.remove('active');

                        const dropdownToggle = parentDropdown.querySelector(':scope > .nav-link.dropdown-toggle, :scope > .dropdown-item.dropdown-toggle');
                        if (dropdownToggle) {
                            dropdownToggle.classList.remove('active');
                            if (collapseDropdowns) {
                                dropdownToggle.setAttribute('aria-expanded', 'false');
                            }
                        }

                        if (collapseDropdowns) {
                            const dropdownMenu = parentDropdown.querySelector(':scope > .dropdown-menu');
                            if (dropdownMenu) {
                                dropdownMenu.classList.remove('show');
                            }
                        }
                    });
                }

                this.activeSidebarDropdownItem = null;
                this.activeSidebarTopItem = null;
                this.activeSidebarParentDropdowns = [];
            },

            /**
             * 激活侧边栏父级dropdown菜单
             * @param {Element} menuItem
             */
            activateSidebarParentDropdown: function(menuItem) {
                let parentDropdown = menuItem.closest('.dropend, .nav-item.dropdown');
                const parents = [];
                while (parentDropdown) {
                    parents.push(parentDropdown);
                    const isTopLevelDropdown = parentDropdown.matches('.navbar-vertical .nav-item.dropdown');
                    if (isTopLevelDropdown) {
                        parentDropdown.classList.add('active');
                    }

                    const dropdownToggle = parentDropdown.querySelector(':scope > .nav-link.dropdown-toggle, :scope > .dropdown-item.dropdown-toggle');
                    if (dropdownToggle) {
                        dropdownToggle.setAttribute('aria-expanded', 'true');
                        if (isTopLevelDropdown) {
                            dropdownToggle.classList.add('active');
                        } else {
                            dropdownToggle.classList.remove('active');
                        }
                    }

                    const dropdownMenu = parentDropdown.querySelector(':scope > .dropdown-menu');
                    if (dropdownMenu) {
                        dropdownMenu.classList.add('show');
                    }

                    const parentMenu = parentDropdown.parentElement?.closest('.dropend, .nav-item.dropdown');
                    parentDropdown = parentMenu || null;
                }
                this.activeSidebarParentDropdowns = parents;
            },

            /**
             * 同步激活横向导航菜单
             * 根据标签页的iframe URL智能匹配对应的横向菜单项
             * @param {string} tabId - 标签页ID
             */
            updateHorizontalNavActive: function(tabId) {
                try {
                    // 参数有效性检查
                    if (!tabId || typeof tabId !== 'string') {
                        this.activateDefaultTopMenu();
                        return;
                    }

                    // 移除所有横向导航菜单项的active类
                    const allNavItems = document.querySelectorAll('#navbar-menu .nav-item');
                    allNavItems.forEach(navItem => {
                        navItem.classList.remove('active');
                        const navLink = navItem.querySelector('.nav-link');
                        if (navLink) {
                            navLink.classList.remove('active');
                        }
                    });

                    // 特殊处理: 首页标签直接激活首页横向菜单
                    if (tabId === 'tab-home') {
                        this.activateDefaultTopMenu();
                        return;
                    }

                    // 获取标签页的iframe URL
                    const normalizedTabId = tabId.startsWith('#') ? tabId.substring(1) : tabId;
                    const tabPane = document.querySelector(`#${normalizedTabId}`);

                    if (!tabPane) {
                        this.activateDefaultTopMenu();
                        return;
                    }

                    const iframe = tabPane.querySelector('.content-iframe');
                    if (!iframe) {
                        this.activateDefaultTopMenu();
                        return;
                    }

                    const iframeUrl = iframe.getAttribute('src') || iframe.getAttribute('data-src');
                    if (!iframeUrl) {
                        this.activateDefaultTopMenu();
                        return;
                    }

                    // 确保menuRegistry已初始化
                    if (!this.menuRegistry || !this.menuRegistry.length) {
                        this.buildMenuRegistry();
                        if (!this.menuRegistry.length) {
                            this.activateDefaultTopMenu();
                            return;
                        }
                    }

                    // 通过iframe URL查找匹配的侧栏菜单项
                    const matchedMenu = this.findMenuByIframeSrc(iframeUrl);

                    if (matchedMenu && matchedMenu.topMenuId) {
                        // 激活对应的横向菜单
                        this.activateTopMenuById(matchedMenu.topMenuId);
                    } else {
                        this.activateDefaultTopMenu();
                    }

                } catch (error) {
                    Logger.error('[Menu Sync] 同步横向菜单时发生错误:', error);
                    this.activateDefaultTopMenu();
                }
            },

            /**
             * 激活指定ID的横向菜单
             * @param {string} topMenuId - 横向菜单ID
             */
            activateTopMenuById: function(topMenuId) {
                // 当前简化实现: 所有菜单都归属于首页
                // 未来可根据topMenuId激活不同的横向菜单项
                this.activateDefaultTopMenu();
            },

            /**
             * 激活默认的横向菜单(首页)
             */
            activateDefaultTopMenu: function() {
                const homeNavItem = document.querySelector('#navbar-menu .nav-item');
                const homeNavLink = document.querySelector('#navbar-menu .nav-link');

                if (homeNavItem) {
                    homeNavItem.classList.add('active');
                }

                if (homeNavLink) {
                    homeNavLink.classList.add('active');
                }
            },

            // ========================
            // 性能优化和内存管理
            // ========================

            // 内存使用监控
            memoryManager: {
                checkInterval: 30000, // 30秒检查一次（更频繁的检查）
                maxMemoryUsage: 150, // 提高到150MB限制（更宽松的阈值）
                intervalId: null,
                parent: null, // 存储对父对象的引用

                start: function() {
                    if (this.intervalId) return;

                    this.intervalId = setInterval(() => {
                        this.checkMemoryUsage();
                    }, this.checkInterval);
                },

                stop: function() {
                    if (this.intervalId) {
                        clearInterval(this.intervalId);
                        this.intervalId = null;
                    }
                },

                checkMemoryUsage: function() {
                    let shouldCleanup = false;
                    let cleanupReason = 'memory';

                    if (typeof performance !== 'undefined' && performance.memory) {
                        const memoryInfo = performance.memory;
                        const usedMB = memoryInfo.usedJSHeapSize / (1024 * 1024);

                        if (usedMB > this.maxMemoryUsage) {
                            Logger.warn(`[Dolphin Tabs] 内存使用过高: ${usedMB.toFixed(2)}MB`);
                            shouldCleanup = true;
                        }
                    }

                    if (!shouldCleanup && this.parent) {
                        if (!this.parent.autoPauseEnabled) {
                            return;
                        }
                        const threshold = this.parent.autoPauseThresholdMinutes || 1;
                        const minCount = this.parent.autoPauseMinTabs || 1;
                        const inactiveTabs = this.parent.getInactiveTabs(threshold);
                        const eligibleTabs = this.parent.getAutoPauseCandidates(inactiveTabs);

                        if (eligibleTabs.length >= minCount) {
                            this.parent.logAutoPauseDebug(`[Dolphin Tabs] 自动暂停检测：发现 ${eligibleTabs.length} 个超过${threshold}分钟未激活的标签`);
                            this.parent.pendingInactiveTabs = inactiveTabs;
                            shouldCleanup = true;
                            cleanupReason = 'inactive';
                        }
                    }

                    if (shouldCleanup && this.parent) {
                        if (cleanupReason === 'inactive' && Array.isArray(this.parent.pendingInactiveTabs)) {
                            this.parent.performMemoryCleanup(this.parent.pendingInactiveTabs);
                            this.parent.pendingInactiveTabs = null;
                        } else {
                            this.parent.performMemoryCleanup();
                        }
                    }
                }
            },

            /**
             * 启动性能监控
             */
            startPerformanceMonitoring: function() {
                // 设置memoryManager对父对象的引用
                this.memoryManager.parent = this;
                this.memoryManager.start();

                // 监听页面可见性变化，优化性能
                if (!this.visibilityChangeHandler) {
                    this.visibilityChangeHandler = () => {
                        if (!this.autoPauseEnabled) {
                            return;
                        }
                        if (document.hidden && this.autoPauseOnHidden) {
                            this.pauseInactiveIframes();
                        } else {
                            this.resumeActiveIframes();
                        }
                    };
                }
                bindGlobalEvent('tabs.document.visibility', document, 'visibilitychange', this.visibilityChangeHandler);
            },

            /**
             * 执行内存清理 - 智能iframe管理，保留标签页
             */
            performMemoryCleanup: function(precomputedTabs = null) {
                if (!this.autoPauseEnabled) {
                    return;
                }
                this.logAutoPauseDebug('[Dolphin Tabs] 开始执行内存清理...');

                const threshold = this.autoPauseThresholdMinutes || 1;
                const inactiveTabs = Array.isArray(precomputedTabs) ? precomputedTabs : this.getInactiveTabs(threshold);
                const pauseCandidates = this.getAutoPauseCandidates(inactiveTabs);
                const minCount = this.autoPauseMinTabs || 1;

                if (pauseCandidates.length < minCount) {
                    this.logAutoPauseDebug(`[Dolphin Tabs] 自动暂停候选不足（${pauseCandidates.length}/${minCount}），跳过清理`);
                    return;
                }

                if (pauseCandidates.length > 3) {
                    // 优先暂停iframe而不是关闭标签页
                    const tabsToPause = pauseCandidates.slice(0, Math.ceil(pauseCandidates.length * 0.7)); // 暂停70%的非活跃标签

                    this.logAutoPauseDebug(`[Dolphin Tabs] 发现 ${pauseCandidates.length} 个超过${threshold}分钟非活跃的标签，将暂停 ${tabsToPause.length} 个标签的iframe`);

                    let pausedCount = 0;
                    tabsToPause.forEach(tab => {
                        const navItem = tab.navItem;
                        if (navItem && !utils.isPermanentTab(navItem) && !utils.isPinnedTab(navItem)) {
                            const targetId = navItem.querySelector('.nav-link')?.getAttribute('href');
                            if (targetId) {
                                const success = this.pauseTabIframe(targetId);
                                if (success) pausedCount++;
                            }
                        }
                    });

                    Logger.info(`[Dolphin Tabs] 自动暂停完成，本轮暂停 ${pausedCount} 个标签`);

                    // 可选：标签页数量过多时才考虑关闭最老标签，默认关闭此行为
                    if (this.autoPauseCloseTabs && pauseCandidates.length > 12) {
                        Logger.debug('[Dolphin Tabs] 标签页数量过多，关闭最老的几个标签页');
                        const tabsToClose = pauseCandidates
                            .slice(0, Math.min(3, pauseCandidates.length - 10)) // 最多关闭3个，但至少保留10个标签页
                            .filter(tab => utils.isPermanentTab ? (!utils.isPermanentTab(tab.navItem) && !utils.isPinnedTab(tab.navItem)) : true);

                        tabsToClose.forEach(tab => {
                            if (tab.navItem) {
                                this.closeDynamicTab(tab.navItem);
                            }
                        });
                    }
                } else if (pauseCandidates.length > 0) {
                    // 标签页数量不多时，只暂停最老的iframe
                    const tabsToPause = pauseCandidates.slice(0, Math.max(1, Math.floor(pauseCandidates.length / 2)));

                    this.logAutoPauseDebug(`[Dolphin Tabs] 标签页较少，发现 ${pauseCandidates.length} 个超过${threshold}分钟非活跃的标签，将暂停 ${tabsToPause.length} 个最老的iframe`);

                    let pausedCount = 0;
                    tabsToPause.forEach(tab => {
                        const navItem = tab.navItem;
                        if (navItem && !utils.isPermanentTab(navItem) && !utils.isPinnedTab(navItem)) {
                            const targetId = navItem.querySelector('.nav-link')?.getAttribute('href');
                            if (targetId) {
                                const success = this.pauseTabIframe(targetId);
                                if (success) pausedCount++;
                            }
                        }
                    });

                    Logger.info(`[Dolphin Tabs] 自动暂停完成，本轮暂停 ${pausedCount} 个标签`);
                } else {
                    this.logAutoPauseDebug(`[Dolphin Tabs] 没有找到超过${threshold}分钟非活跃的标签，无需清理内存`);
                }

                // 强制垃圾回收（如果可用）
                if (typeof window.gc === 'function') {
                    window.gc();
                }
            },

            /**
             * 获取非活跃的标签页
             * @param {number} minInactiveMinutes - 最小非活跃时间（分钟），默认5分钟
             */
            getInactiveTabs: function(minInactiveMinutes = 5) {
                const tabs = [];
                const allNavItems = document.querySelectorAll('#openedTabs .nav-item, #pinnedTabs .nav-item');
                const now = Date.now();
                const minInactiveTime = minInactiveMinutes * 60000; // 转换为毫秒

                allNavItems.forEach(navItem => {
                    const navLink = navItem.querySelector('.nav-link');
                    const isPermanent = utils.isPermanentTab(navItem);
                    const isPinned = utils.isPinnedTab(navItem);
                    const isActive = navLink && navLink.classList.contains('active');

                    if (isPermanent) {
                        return; // 跳过永久标签
                    }
                    if (isPinned) {
                        return; // 跳过固定标签
                    }
                    if (isActive) {
                        return; // 跳过活跃标签
                    }

                    // 只处理非永久、非固定、非活跃的标签
                    if (navLink) {
                        const targetId = navLink.getAttribute('href');
                        const tabPane = targetId ? document.querySelector(targetId) : null;

                        if (tabPane) {
                            // 获取最后活跃时间
                            const lastActiveStr = navItem.getAttribute('data-last-active') || '0';
                            const lastActive = parseInt(lastActiveStr);

                            // 计算非活跃时长（毫秒和分钟）
                            const inactiveTime = now - lastActive;
                            const inactiveMinutes = Math.floor(inactiveTime / 60000);

                            if (lastActive === 0) {
                                const tabTitle = navItem.querySelector('.tab-title')?.textContent || '未知标签';
                                this.logAutoPauseDebug(`[Dolphin Tabs] 时间警告 ${tabTitle}: 时间戳为0，可能未正确初始化`);
                            }

                            // 只有超过最小非活跃时间的标签页才被考虑
                            if (inactiveTime >= minInactiveTime) {
                                // 检查是否有iframe且是否已加载
                                const iframe = tabPane.querySelector('.content-iframe');
                                // 检查iframe是否真正加载了内容（有src且不是about:blank）
                                const hasLoadedContent = iframe && iframe.src && iframe.src !== 'about:blank';
                                const isPaused = iframe && iframe.hasAttribute('data-paused-src');
                                const isLoading = this.loadingTabs.has(targetId);
                                // 总的来说，如果有已加载的内容或已暂停，才认为是有效的iframe
                                const hasIframe = hasLoadedContent || isPaused;

                                tabs.push({
                                    navItem: navItem,
                                    tabPane: tabPane,
                                    lastActive: lastActive,
                                    inactiveMinutes: inactiveMinutes,
                                    hasIframe: hasIframe,
                                    hasLoadedContent: hasLoadedContent,
                                    isPaused: isPaused,
                                    isLoading: isLoading,
                                    targetId: targetId,
                                    iframeSrc: iframe ? iframe.src : 'no-iframe',
                                    hasDataSrc: iframe ? !!iframe.getAttribute('data-src') : false
                                });
                            }
                        }
                    }
                });

                // 按最后活跃时间排序（最久没活跃的在前面）
                return tabs.sort((a, b) => {
                    // 优先选择已经有加载内容但未暂停的iframe标签页
                    const aCanPause = a.hasLoadedContent && !a.isPaused;
                    const bCanPause = b.hasLoadedContent && !b.isPaused;

                    if (aCanPause && !bCanPause) return -1;
                    if (bCanPause && !aCanPause) return 1;

                    // 然后按最后活跃时间排序
                    return a.lastActive - b.lastActive;
                });
            },

            /**
             * 获取可自动暂停的标签候选
             */
            getAutoPauseCandidates: function(inactiveTabs) {
                if (!Array.isArray(inactiveTabs)) return [];
                return inactiveTabs.filter(tab => tab && tab.hasLoadedContent && !tab.isPaused && !tab.isLoading);
            },

            /**
             * 自动暂停调试日志（默认关闭）
             */
            logAutoPauseDebug: function(message) {
                if (this.autoPauseDebug) {
                    Logger.debug(message);
                }
            },

            /**
             * 暂停指定标签页的iframe（智能内存管理）
             * @param {string} targetId - 标签页ID
             * @returns {boolean} - 是否成功暂停
             */
            pauseTabIframe: function(targetId) {
                const tabPane = document.querySelector(targetId);
                if (!tabPane) {
                    this.logAutoPauseDebug(`[Dolphin Tabs] 暂停失败: 未找到标签页 ${targetId}`);
                    return false;
                }

                const iframe = tabPane.querySelector('.content-iframe');
                if (!iframe) {
                    this.logAutoPauseDebug(`[Dolphin Tabs] 暂停失败: 未找到iframe在 ${targetId}`);
                    return false;
                }

                if (this.loadingTabs.has(targetId)) {
                    this.logAutoPauseDebug(`[Dolphin Tabs] 暂停失败: 标签仍在加载 ${targetId}`);
                    return false;
                }

                // 检查iframe是否有内容（src或data-src）
                const hasContent = iframe.src && iframe.src !== 'about:blank';
                const hasDataSrc = iframe.getAttribute('data-src');

                if (!hasContent && !hasDataSrc) {
                    this.logAutoPauseDebug(`[Dolphin Tabs] 暂停失败: iframe没有内容 ${targetId}`);
                    return false;
                }

                // 如果iframe还没加载（只有data-src），不需要暂停
                if (!hasContent && hasDataSrc) {
                    this.logAutoPauseDebug(`[Dolphin Tabs] 跳过暂停: iframe尚未加载 ${targetId}`);
                    return false;
                }

                // 检查是否为活跃标签，不暂停活跃标签
                if (tabPane.classList.contains('active')) {
                    this.logAutoPauseDebug(`[Dolphin Tabs] 暂停失败: 不能暂停活跃标签 ${targetId}`);
                    return false;
                }

                // 确保状态集同步，避免被视为已加载
                this.loadedTabs.delete(targetId);
                this.loadingTabs.delete(targetId);

                // 如果已经暂停，不重复操作
                if (iframe.hasAttribute('data-paused-src')) {
                    this.logAutoPauseDebug(`[Dolphin Tabs] 标签页已暂停 ${targetId}`);
                    return true;
                }

                // 保存原始src并设置为空白页
                iframe.setAttribute('data-paused-src', iframe.src);
                iframe.setAttribute('data-paused-time', Date.now().toString());
                iframe.src = 'about:blank';

                // 在标签页上添加暂停标识
                const navLink = document.querySelector(`[href="${targetId}"]`);
                if (navLink) {
                    navLink.classList.add('iframe-paused');
                    const currentTitle = navLink.getAttribute('title') || '';
                    if (!currentTitle.includes(' (已暂停)')) {
                        navLink.setAttribute('title', currentTitle + ' (已暂停)');
                    }
                }

                this.logAutoPauseDebug(`[Dolphin Tabs] 成功暂停标签页 ${targetId}`);
                return true;
            },

            /**
             * 恢复指定标签页的iframe
             * @param {string} targetId - 标签页ID
             * @returns {boolean} - 是否成功恢复
             */
            resumeTabIframe: function(targetId) {
                const tabPane = document.querySelector(targetId);
                if (!tabPane) return false;

                const iframe = tabPane.querySelector('.content-iframe');
                if (!iframe || !iframe.hasAttribute('data-paused-src')) return false;

                this.setTabLoadingState(targetId, true);
                this.loadingTabs.add(targetId);

                const originalSrc = iframe.getAttribute('data-paused-src');
                const pausedTime = iframe.getAttribute('data-paused-time');

                // 显示加载提示器
                const loadingIndicator = tabPane.querySelector('.loading-indicator');
                if (loadingIndicator) {
                    loadingIndicator.style.display = '';
                    loadingIndicator.style.visibility = '';
                    loadingIndicator.style.opacity = '';
                    loadingIndicator.classList.remove('d-none');

                    // 更新加载文本为恢复状态
                    const loadingText = loadingIndicator.querySelector('span');
                    if (loadingText) {
                        const navLink = document.querySelector(`[href="${targetId}"]`);
                        const tabTitle = navLink ? navLink.querySelector('.tab-title')?.textContent || '标签页' : '标签页';
                        loadingText.textContent = `正在恢复 ${tabTitle}...`;
                    }
                }

                // 隐藏iframe，准备重新加载
                iframe.style.display = 'none';

                // 从加载管理器中移除，使其重新被当作新标签处理
                this.loadedTabs.delete(targetId);

                // 设置iframe加载事件处理
                const timeoutId = setTimeout(() => {
                    if (!this.loadedTabs.has(targetId)) {
                        this.onIframeTimeout(iframe, loadingIndicator, targetId);
                    }
                }, 20000);

                iframe.onload = () => {
                    clearTimeout(timeoutId);
                    setTimeout(() => {
                        this.onIframeLoad(iframe, loadingIndicator, targetId);
                    }, 500);
                };

                iframe.onerror = () => {
                    clearTimeout(timeoutId);
                    this.onIframeError(iframe, loadingIndicator, targetId);
                };

                // 恢复iframe源地址
                iframe.src = originalSrc;
                iframe.removeAttribute('data-paused-src');
                iframe.removeAttribute('data-paused-time');

                // 移除标签页上的暂停标识
                const navLink = document.querySelector(`[href="${targetId}"]`);
                if (navLink) {
                    navLink.classList.remove('iframe-paused');
                    const title = navLink.getAttribute('title');
                    if (title && title.includes(' (已暂停)')) {
                        navLink.setAttribute('title', title.replace(' (已暂停)', ''));
                    }
                }

                this.logAutoPauseDebug(`[Dolphin Tabs] 开始恢复标签页 ${targetId} 的iframe，暂停时长: ${pausedTime ? Math.round((Date.now() - parseInt(pausedTime)) / 1000) : '未知'}秒`);

                return true;
            },

            /**
             * 暂停非活跃iframe（页面可见性变化时使用）
             * 这个函数用于页面失去焦点时暂停所有非活跃iframe，不考虑时间阈值
             */
            pauseInactiveIframes: function() {
                if (!this.autoPauseEnabled) {
                    return;
                }
                const threshold = this.autoPauseThresholdMinutes || 1;
                const candidates = this.getAutoPauseCandidates(this.getInactiveTabs(threshold));
                let pausedCount = 0;

                candidates.forEach(tab => {
                    const targetId = tab.targetId;
                    if (this.pauseTabIframe(targetId)) {
                        pausedCount++;
                    }
                });

                this.logAutoPauseDebug(`[Dolphin Tabs] 页面失去焦点，暂停 ${pausedCount} 个非活跃iframe`);
            },

            /**
             * 恢复活跃iframe
             */
            resumeActiveIframes: function() {
                const activeTabPanes = document.querySelectorAll('.tab-pane.active');

                activeTabPanes.forEach(tabPane => {
                    if (!tabPane.id) {
                        return;
                    }
                    this.resumeTabIframe(`#${tabPane.id}`);
                });
            },

            // ========================
            // iframe通信增强
            // ========================

            /**
             * 向指定iframe发送消息
             */
            postMessageToIframe: function(tabId, message) {
                const tabPane = document.getElementById(tabId);
                if (!tabPane) return false;

                const iframe = tabPane.querySelector('.content-iframe');
                if (!iframe || !iframe.contentWindow) return false;

                try {
                    iframe.contentWindow.postMessage(message, '*');
                    return true;
                } catch (e) {
                    Logger.warn('[Dolphin Tabs] 发送消息到iframe失败:', e);
                    return false;
                }
            },

            /**
             * 向所有iframe广播消息
             */
            broadcastToAllIframes: function(message) {
                const iframes = document.querySelectorAll('.content-iframe');
                let sentCount = 0;

                iframes.forEach(iframe => {
                    if (iframe.contentWindow && iframe.src && iframe.src !== 'about:blank') {
                        try {
                            iframe.contentWindow.postMessage(message, '*');
                            sentCount++;
                        } catch (e) {
                            // 忽略跨域错误
                        }
                    }
                });

                return sentCount;
            },

            /**
             * 增强的iframe事件处理
             */
            enhancedMessageHandler: function(event) {
                if (!event.data || !event.data.type || !this.validateIframeMessageEvent(event)) return;

                switch (event.data.type) {
                    case 'iframe_ready':
                        this.handleIframeReady(event);
                        break;
                    case 'iframe_title_change':
                        this.handleTitleChange(event);
                        break;
                    case 'iframe_error':
                        this.handleIframeError(event);
                        break;
                    case 'iframe_close_request':
                        this.handleCloseRequest(event);
                        break;
                }
            },

            /**
             * 根据窗口句柄查找iframe
             */
            getIframeByWindow: function(iframeWindow) {
                if (!iframeWindow) return null;
                const iframes = document.querySelectorAll('.content-iframe');
                for (let i = 0; i < iframes.length; i++) {
                    if (iframes[i].contentWindow === iframeWindow) {
                        return iframes[i];
                    }
                }
                return null;
            },

            /**
             * 校验 iframe 消息来源与消息类型
             */
            validateIframeMessageEvent: function(event) {
                if (!event || !event.data || !event.data.type) return false;

                if (!this.allowedIframeMessageTypes.has(event.data.type)) {
                    return false;
                }

                const iframe = this.getIframeByWindow(event.source);
                if (!iframe) return false;

                if (!event.origin || event.origin === 'null') {
                    return false;
                }

                const iframeSrc = iframe.getAttribute('src') || iframe.getAttribute('data-src');
                if (!iframeSrc) return false;

                try {
                    const expectedOrigin = new URL(iframeSrc, window.location.origin).origin;
                    return expectedOrigin === event.origin;
                } catch (e) {
                    return false;
                }
            },

            /**
             * 处理iframe准备就绪
             */
            handleIframeReady: function(event) {
                // 同步主题到新加载的iframe
                this.syncThemeToIframe(event.source);
            },

            /**
             * 处理标题变化
             */
            handleTitleChange: function(event) {
                if (!event.data.title) return;

                // 查找对应的标签页并更新标题
                const iframes = document.querySelectorAll('.content-iframe');
                for (let iframe of iframes) {
                    if (iframe.contentWindow === event.source) {
                        const tabPane = iframe.closest('.tab-pane');
                        if (tabPane) {
                            const navLink = document.querySelector(`[href="#${tabPane.id}"]`);
                            if (navLink) {
                                const titleSpan = navLink.querySelector('.tab-title');
                                if (titleSpan) {
                                    titleSpan.textContent = event.data.title;
                                }
                            }
                        }
                        break;
                    }
                }
            },

            /**
             * 处理iframe错误
             */
            handleIframeError: function(event) {
                Logger.error('[Dolphin Tabs] iframe报告错误:', event.data.error);

                // 显示错误提示
                if (event.data.error) {
                    Dolphin.error(`页面加载出错: ${event.data.error}`);
                }
            },

            /**
             * 处理关闭请求
             */
            handleCloseRequest: function(event) {
                // 查找并关闭对应的标签页
                const iframes = document.querySelectorAll('.content-iframe');
                for (let iframe of iframes) {
                    if (iframe.contentWindow === event.source) {
                        const tabPane = iframe.closest('.tab-pane');
                        if (tabPane) {
                            const navItem = document.querySelector(`[href="#${tabPane.id}"]`)?.closest('.nav-item');
                            if (navItem) {
                                this.closeDynamicTab(navItem);
                            }
                        }
                        break;
                    }
                }
            },

            // ========================
            // 标签页状态持久化管理
            // ========================

            /**
             * 保存标签页状态到localStorage
             */
            saveTabState: function(config, tabId) {
                try {
                    const tabStates = this.getTabStates();

                    // 保存标签页信息
                    tabStates[config.id] = {
                        id: config.id,
                        title: config.title,
                        url: config.url,
                        icon: config.icon,
                        realIconHTML: config.realIconHTML,
                        closable: config.closable,
                        tabId: tabId,
                        createdAt: Date.now(),
                        lastActiveAt: Date.now()
                    };

                    localStorage.setItem('dolphin_tab_states', JSON.stringify(tabStates));
                    Logger.debug(`[Dolphin Tabs] 已保存标签页状态: ${config.title}`);
                } catch (e) {
                    Logger.warn('[Dolphin Tabs] 保存标签页状态失败:', e);
                }
            },

            /**
             * 删除标签页状态
             */
            removeTabState: function(tabId) {
                try {
                    const tabStates = this.getTabStates();
                    const normalizedId = (tabId || '').replace(/^#/, '');
                    const simplifiedId = normalizedId.startsWith('tab-')
                        ? normalizedId.substring(4)
                        : normalizedId;

                    // 查找并删除对应的标签页状态
                    for (const [key, state] of Object.entries(tabStates)) {
                        if (state.tabId === normalizedId || state.id === simplifiedId) {
                            delete tabStates[key];
                            break;
                        }
                    }

                    localStorage.setItem('dolphin_tab_states', JSON.stringify(tabStates));
                    Logger.debug(`[Dolphin Tabs] 已删除标签页状态: ${tabId}`);
                } catch (e) {
                    Logger.warn('[Dolphin Tabs] 删除标签页状态失败:', e);
                }
            },

            /**
             * 更新标签页最后活跃时间
             */
            updateTabLastActive: function(tabId) {
                try {
                    const tabStates = this.getTabStates();
                    const normalizedId = (tabId || '').replace(/^#/, '');
                    const simplifiedId = normalizedId.startsWith('tab-')
                        ? normalizedId.substring(4)
                        : normalizedId;

                    for (const [key, state] of Object.entries(tabStates)) {
                        if (state.tabId === normalizedId || state.id === simplifiedId) {
                            state.lastActiveAt = Date.now();
                            break;
                        }
                    }

                    localStorage.setItem('dolphin_tab_states', JSON.stringify(tabStates));
                } catch (e) {
                    Logger.warn('[Dolphin Tabs] 更新标签页活跃时间失败:', e);
                }
            },

            /**
             * 获取所有标签页状态
             */
            getTabStates: function() {
                try {
                    const saved = localStorage.getItem('dolphin_tab_states');
                    return saved ? JSON.parse(saved) : {};
                } catch (e) {
                    Logger.warn('[Dolphin Tabs] 读取标签页状态失败:', e);
                    return {};
                }
            },

            /**
             * 恢复保存的标签页
             */
            restoreTabStates: function() {
                try {
                    const tabStates = this.getTabStates();

                    if (Object.keys(tabStates).length === 0) {
                        Logger.debug('[Dolphin Tabs] 没有找到保存的标签页状态');
                        return null;
                    }

                    Logger.debug(`[Dolphin Tabs] 开始恢复 ${Object.keys(tabStates).length} 个标签页`);

                    // 按创建时间排序
                    const sortedStates = Object.values(tabStates).sort((a, b) => a.createdAt - b.createdAt);

                    // 恢复标签页（但不激活和加载）
                    for (const state of sortedStates) {
                        this.restoreTab(state);
                    }

                    // 返回期望激活的标签ID，由外层在恢复排序/固定后统一激活
                    return this.getPreferredRestoreTargetId();

                } catch (e) {
                    Logger.error('[Dolphin Tabs] 恢复标签页状态失败:', e);
                    return null;
                }
            },

            /**
             * 计算恢复后优先激活的标签ID
             * 优先URL hash，其次localStorage记录
             * @return {string|null}
             */
            getPreferredRestoreTargetId: function() {
                const currentHash = window.location.hash;
                if (currentHash && currentHash.startsWith('#tab-')) {
                    return currentHash;
                }

                const activeTabId = localStorage.getItem('dolphin_active_tab');
                if (!activeTabId) {
                    return null;
                }

                return activeTabId.startsWith('#') ? activeTabId : `#${activeTabId}`;
            },

            /**
             * 按ID激活恢复目标标签
             * @param {string|null} targetTabId
             * @return {boolean}
             */
            activateRestoredTabById: function(targetTabId) {
                if (!targetTabId) {
                    return false;
                }

                const currentTargetTab = document.querySelector(`#pinnedTabs .nav-link[href="${targetTabId}"], #openedTabs .nav-link[href="${targetTabId}"]`);
                if (!currentTargetTab) {
                    return false;
                }

                this.activateTabByLink(currentTargetTab);
                return true;
            },

            /**
             * 重建URL到标签ID索引
             */
            rebuildTabUrlIndex: function() {
                this.tabUrlIndex = new Map();
                const panes = document.querySelectorAll('#tabContent .tab-pane.iframe-container');
                panes.forEach((pane) => {
                    const paneId = pane.id;
                    const iframe = pane.querySelector('.content-iframe');
                    if (!paneId || !iframe) return;
                    const dataSrc = iframe.getAttribute('data-src');
                    if (!dataSrc) return;
                    this.tabUrlIndex.set(dataSrc, `#${paneId}`);
                });
            },

            /**
             * 恢复单个标签页
             */
            restoreTab: function(state) {
                try {
                    // 检查是否已存在
                    const existingTab = document.querySelector(`[data-tab-id="${state.id}"]`);
                    if (existingTab) {
                Logger.debug(`[Dolphin Tabs] 标签页已存在，跳过恢复: ${state.title}`);
                        return;
                    }

                    // 创建标签页结构
                    const navItem = this.createTabNavItem(state, state.tabId);
                    const tabPane = this.createTabPaneForRestore(state, state.tabId);

                    // 添加到DOM
                    const openedTabs = document.getElementById('openedTabs');
                    const tabContent = document.getElementById('tabContent');

                    if (openedTabs && tabContent) {
                        openedTabs.appendChild(navItem);
                        tabContent.appendChild(tabPane);

                        // 绑定事件但不触发激活
                        this.bindNewTabEvents(navItem);
                        if (state.url) {
                            this.tabUrlIndex.set(state.url, `#${state.tabId}`);
                        }

                        Logger.debug(`[Dolphin Tabs] 已恢复标签页: ${state.title}`);
                    }

                } catch (e) {
                    Logger.error('[Dolphin Tabs] 恢复标签页失败:', state.title, e);
                }
            },

            /**
             * 为恢复创建标签页面板（不立即加载iframe）
             */
            createTabPaneForRestore: function(config, tabId) {
                const tabPane = document.createElement('div');
                tabPane.className = 'tab-pane fade iframe-container';
                tabPane.id = tabId;
                tabPane.setAttribute('role', 'tabpanel');
                tabPane.setAttribute('aria-labelledby', tabId);

                // 创建加载指示器
                const loadingText = `正在加载 ${config.title}...`;
                const loadingIndicator = `
                    <div class="loading-indicator d-flex align-items-center justify-content-center" style="height: 400px;">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">加载中...</span>
                        </div>
                        <span class="ms-2">${loadingText}</span>
                    </div>`;

                // 创建iframe（使用data-src，不立即加载）
                const iframe = `
                    <iframe class="content-iframe"
                            data-src="${config.url}"
                            referrerpolicy="strict-origin-when-cross-origin"
                            style="display: none;">
                    </iframe>`;

                tabPane.innerHTML = loadingIndicator + iframe;
                const indicator = tabPane.querySelector('.loading-indicator');
                if (indicator) {
                    indicator.setAttribute('data-loading-text', loadingText);
                }
                return tabPane;
            },

            /**
             * 保存当前活跃标签页
             */
            saveActiveTab: function(tabId) {
                try {
                    localStorage.setItem('dolphin_active_tab', tabId);
                    this.updateTabLastActive(tabId);
                } catch (e) {
                    Logger.warn('[Dolphin Tabs] 保存活跃标签页失败:', e);
                }
            }
        };

        /**
         * 共享的标签关闭逻辑
         *
         * 统一处理标签关闭流程，避免通过模拟点击事件触发，减少时序不确定性。
         *
         * @param {HTMLElement} tabItem - 要关闭的标签 .nav-item 元素
         * @returns {boolean} 是否成功关闭标签
         */
        const performTabClose = function(tabItem) {
            if (!tabItem) {
                Logger.warn('[performTabClose] 未提供有效的 tabItem');
                return false;
            }

            const tabLink = tabItem.querySelector('.nav-link');
            if (!tabLink) {
                Logger.warn('[performTabClose] 未找到 .nav-link 元素');
                return false;
            }

            // 检查是否为永久标签（首页标签）
            if (utils.isPermanentTab(tabItem)) {
                Dolphin.warning('工作台标签无法关闭');
                return false;
            }

            // 使用工具函数检查最小标签数量
            if (!utils.checkMinTabCount()) return false;

            // 清理iframe资源
            const targetId = tabLink.getAttribute('href');
            if (targetId) {
                iframeManager.cleanupTabIframe(targetId);
            }

            // 如果关闭的是当前活跃标签，需要先激活下一个标签
            if (tabLink.classList.contains('active')) {
                tabBatchActions.activateNextTab(tabItem);
            }

            // 移除标签
            tabItem.remove();

            // 更新滚动按钮状态
            setTimeout(() => tabScrollManager.updateButtonState(), 100);

            syncFavoriteActiveState();

            return true;
        };

        // 单个标签关闭处理器（用于关闭按钮点击事件）
        const closeTabHandler = function(event) {
            event.preventDefault();
            event.stopPropagation();

            const tabItem = event.target.closest('.nav-item');
            performTabClose(tabItem);
        };

        const delegatedCloseButtonHandler = function(event) {
            if (!event.target.closest('.btn-close-tab')) return;
            closeTabHandler(event);
        };
        bindGlobalEvent('tabs.closeButton', document, 'click', delegatedCloseButtonHandler);

        // 标签点击处理器
        const tabClickHandler = function(event) {
            if (event.target.closest('.btn-close-tab')) {
                return;
            }
            const navLink = event.target.closest('.nav-link');

            // 检查是否点击了标签链接，且不是关闭按钮
            if (navLink && !event.target.closest('.btn-close-tab')) {
                const parentNavItem = navLink.closest('.nav-item');

                // 检查是否是标签页容器内的标签
                if (parentNavItem &&
                    (parentNavItem.closest('#pinnedTabs') || parentNavItem.closest('#openedTabs'))) {

                    // 彻底阻止 Bootstrap 的标签页行为
                    event.preventDefault();
                    event.stopPropagation();
                    event.stopImmediatePropagation();

                    // 统一入口激活标签
                    if (typeof iframeManager !== 'undefined' && typeof iframeManager.activateTabByLink === 'function') {
                        iframeManager.activateTabByLink(navLink);
                    } else {
                        utils.activateTab(navLink);
                    }

                }
            }
        };

        bindGlobalEvent('tabs.navClick', document, 'click', tabClickHandler);

        // 标签页批量操作管理器
        const tabBatchActions = {
            getAllTabs: function() {
                const pinnedContainer = document.getElementById('pinnedTabs');
                const openedContainer = document.getElementById('openedTabs');
                const pinned = pinnedContainer ? Array.from(pinnedContainer.querySelectorAll('.nav-item')) : [];
                const normal = openedContainer ? Array.from(openedContainer.querySelectorAll('.nav-item')) : [];
                return [...pinned, ...normal];
            },

            getClosableTabs: function(tabs, excludes = []) {
                const excludeSet = new Set(Array.isArray(excludes) ? excludes.filter(Boolean) : [excludes].filter(Boolean));
                return tabs.filter(tab =>
                    !excludeSet.has(tab) &&
                    !utils.isPermanentTab(tab) &&
                    !utils.isPinnedTab(tab)
                );
            },

            performBatchClose: function(tabs, options = {}) {
                const {
                    emptyMessage = null,
                    confirm = false,
                    confirmTitle = '',
                    confirmMessage = '',
                    successLabel = '标签页',
                    showFeedback = true,
                    afterClose = null
                } = options;

                const targetTabs = Array.isArray(tabs) ? tabs : [];
                if (targetTabs.length === 0) {
                    if (emptyMessage) {
                        Dolphin.info(emptyMessage);
                    }
                    return;
                }

                const finalizeClose = () => {
                    const iframeManager = Dolphin.getIframeManager();
                    if (iframeManager) {
                        iframeManager.cleanupMultipleIframes(targetTabs);
                    } else {
                Logger.warn('[tabBatchActions] iframe管理器未初始化');
                    }

                    targetTabs.forEach(tab => tab.remove());

                    if (showFeedback) {
                        this._updateAfterBatchClose(targetTabs.length, successLabel);
                    } else {
                        setTimeout(() => tabScrollManager.updateButtonState(), 100);
                    }

                    if (typeof afterClose === 'function') {
                        afterClose();
                    }

                    syncFavoriteActiveState();
                };

                if (confirm && confirmTitle && confirmMessage) {
                    Dolphin.confirm(confirmTitle, confirmMessage, finalizeClose);
                } else {
                    finalizeClose();
                }
            },

            closeCurrent: function() {
                this.closeDropdown();

                const activeTab = document.querySelector('#openedTabs .nav-link.active, #pinnedTabs .nav-link.active');
                if (!activeTab) return;

                const tabItem = activeTab.closest('.nav-item');
                if (utils.isPermanentTab(tabItem)) {
                    Dolphin.warning('工作台标签无法关闭');
                    return;
                }

                if (!utils.checkMinTabCount()) return;

                this.activateNextTab(tabItem);

                this.performBatchClose([tabItem], {
                    showFeedback: false
                });
            },

            closeOthers: function() {
                this.closeDropdown();

                const activeTab = document.querySelector('#openedTabs .nav-link.active, #pinnedTabs .nav-link.active');
                if (!activeTab) {
                    Logger.error('[closeOthers] 未找到活跃标签');
                    return;
                }

                const activeTabItem = activeTab.closest('.nav-item');
                const tabsToClose = this.getClosableTabs(this.getAllTabs(), activeTabItem);

                this.performBatchClose(tabsToClose, {
                    emptyMessage: '没有其他标签页需要关闭',
                    confirm: true,
                    confirmTitle: `确定要关闭其他 ${tabsToClose.length} 个标签页吗？`,
                    confirmMessage: '将保留当前标签页，关闭所有其他标签页。',
                    successLabel: '其他标签页'
                });
            },

            closeRight: function() {
                this.closeDropdown();

                const activeTab = document.querySelector('#openedTabs .nav-link.active, #pinnedTabs .nav-link.active');
                if (!activeTab) return;

                const activeTabItem = activeTab.closest('.nav-item');
                const allTabs = this.getAllTabs();
                const activeIndex = allTabs.indexOf(activeTabItem);
                const rightTabs = this.getClosableTabs(allTabs.slice(activeIndex + 1));

                this.performBatchClose(rightTabs, {
                    emptyMessage: '没有右侧标签页需要关闭',
                    confirm: rightTabs.length >= 3,
                    confirmTitle: `确定要关闭右侧 ${rightTabs.length} 个标签页吗？`,
                    confirmMessage: '将关闭当前标签页右侧的所有标签页。',
                    successLabel: '右侧标签页'
                });
            },

            _updateAfterBatchClose: function(closedCount, type) {
                if (closedCount > 0) {
                    setTimeout(() => tabScrollManager.updateButtonState(), 100);
                    Dolphin.success(`已关闭 ${closedCount} 个${type}`);
                } else {
                    Dolphin.toast(`没有${type}可关闭`);
                }
            },

            closeAll: function() {
                this.closeDropdown();

                const closableTabs = this.getClosableTabs(this.getAllTabs());
                this.performBatchClose(closableTabs, {
                    emptyMessage: '没有标签页需要关闭',
                    confirm: true,
                    confirmTitle: `确定要关闭 ${closableTabs.length} 个标签页吗？`,
                    confirmMessage: '将保留工作台标签和已固定的标签，关闭所有其他标签页。',
                    successLabel: '标签页',
                    afterClose: () => {
                        this.activateHomeTab();
                    }
                });
            },

            activateHomeTab: function() {
                const homeTab = document.querySelector('.home-tab');
                if (!homeTab) return;

                const homeLink = homeTab.querySelector('.nav-link');
                if (!homeLink) return;

                if (typeof iframeManager !== 'undefined' && typeof iframeManager.activateTabByLink === 'function') {
                    iframeManager.activateTabByLink(homeLink);
                } else {
                    utils.activateTab(homeLink);
                }
            },

            // 激活下一个标签（当前标签即将关闭时）
            activateNextTab: function(currentTabItem) {
                const allTabs = this.getAllTabs();
                const currentIndex = allTabs.indexOf(currentTabItem);
                let nextTab = null;

                if (currentIndex < allTabs.length - 1) {
                    nextTab = allTabs[currentIndex + 1];
                } else if (currentIndex > 0) {
                    nextTab = allTabs[currentIndex - 1];
                }

                if (nextTab) {
                    const nextLink = nextTab.querySelector('.nav-link');
                    if (nextLink) {
                        if (typeof iframeManager !== 'undefined' && typeof iframeManager.activateTabByLink === 'function') {
                            iframeManager.activateTabByLink(nextLink);
                        } else {
                            utils.activateTab(nextLink);
                        }

                        // 防御性验证：确保目标 tab-pane 状态正确
                        const targetId = nextLink.getAttribute('href') || nextLink.getAttribute('data-bs-target');
                        if (targetId) {
                            const targetPane = document.querySelector(targetId);
                            if (targetPane) {
                                // 检测并修复 show 类缺失问题
                                if (targetPane.classList.contains('active') && !targetPane.classList.contains('show')) {
                                    Logger.warn(`[activateNextTab] 检测到 show 类缺失，自动修复: ${targetId}`);
                                    targetPane.classList.add('show');
                                }
                            }
                        }

                    }
                }
            },

            // 关闭下拉菜单并移除焦点
            closeDropdown: function() {
                const dropdownBtn = document.getElementById('tabActionsBtn');
                if (dropdownBtn) {
                    const dropdownMenu = dropdownBtn.nextElementSibling;
                    if (dropdownMenu) {
                        dropdownMenu.classList.remove('show');
                    }
                    dropdownBtn.classList.remove('show');
                    dropdownBtn.setAttribute('aria-expanded', 'false');
                    setTimeout(function() {
                        dropdownBtn.blur();
                    }, 100);
                }
            }
        };

        const tabDropdownAutoCloser = {
            init: function() {
                if (this.initialized) return;
                this.initialized = true;
                this.boundPointerDown = this.handlePointerDown.bind(this);
                this.boundFocusIn = this.handleFocusIn.bind(this);
                this.boundWindowBlur = this.handleWindowBlur.bind(this);

                bindGlobalEvent('tabs.dropdown.pointer', document, 'pointerdown', this.boundPointerDown, true);
                bindGlobalEvent('tabs.dropdown.focusin', document, 'focusin', this.boundFocusIn);
                bindGlobalEvent('tabs.dropdown.windowblur', window, 'blur', this.boundWindowBlur);
            },
            isDropdownOpen: function() {
                const btn = document.getElementById('tabActionsBtn');
                return btn && btn.classList.contains('show');
            },
            handlePointerDown: function(event) {
                if (!this.isDropdownOpen()) return;
                const btn = document.getElementById('tabActionsBtn');
                const menu = btn ? btn.nextElementSibling : null;
                if ((btn && btn.contains(event.target)) || (menu && menu.contains(event.target))) {
                    return;
                }
                tabBatchActions.closeDropdown();
            },
            handleFocusIn: function(event) {
                if (!this.isDropdownOpen()) return;
                if (event.target && event.target.tagName === 'IFRAME') {
                    tabBatchActions.closeDropdown();
                }
            },
            handleWindowBlur: function() {
                if (!this.isDropdownOpen()) return;
                tabBatchActions.closeDropdown();
            }
        };

        const sidebarToggleManager = {
            storageKey: 'dolphin_sidebar_collapsed',
            afterToggle: null,
            init: function() {
                if (this.initialized) return;
                const btn = document.getElementById('sidebarToggleBtn');
                if (!btn) return;
                this.initialized = true;
                const collapsed = this.getStoredState();
                this.applyState(collapsed, false);
                this.boundToggleHandler = (event) => {
                    event.preventDefault();
                    const nextState = !document.body.classList.contains('sidebar-collapsed');
                    this.applyState(nextState, true);
                };
                btn.addEventListener('click', this.boundToggleHandler);
            },
            applyState: function(collapsed, persist = true) {
                if (collapsed) {
                    document.body.classList.add('sidebar-collapsed');
                    document.body.classList.remove('sidebar-expanded');
                } else {
                    document.body.classList.remove('sidebar-collapsed');
                    document.body.classList.add('sidebar-expanded');
                }
                const btn = document.getElementById('sidebarToggleBtn');
                if (btn) {
                    btn.setAttribute('aria-pressed', collapsed ? 'true' : 'false');
                }
                if (typeof this.afterToggle === 'function') {
                    this.afterToggle(collapsed);
                }
                if (persist) {
                    this.storeState(collapsed);
                }
            },
            getStoredState: function() {
                try {
                    return localStorage.getItem(this.storageKey) === '1';
                } catch (e) {
                    Logger.warn('读取侧栏状态失败', e);
                    return false;
                }
            },
            storeState: function(collapsed) {
                try {
                    localStorage.setItem(this.storageKey, collapsed ? '1' : '0');
                } catch (e) {
                    Logger.warn('保存侧栏状态失败', e);
                }
            }
        };

        const menuSearchManager = {
            input: document.getElementById('menuSearchInput'),
            resultsContainer: document.getElementById('menuSearchResults'),
            results: [],
            highlightedIndex: -1,
            maxResults: 8,
            init: function() {
                if (this.initialized) return;
                if (!this.input || !this.resultsContainer) return;
                this.initialized = true;
                this.wrapper = this.resultsContainer.closest('.menu-search-wrapper');
                this.input.addEventListener('input', () => this.handleInput());
                this.input.addEventListener('keydown', (event) => this.handleKeyDown(event));
                this.input.addEventListener('focus', () => {
                    if ((this.input.value || '').trim()) {
                        this.handleInput();
                    }
                });
                this.resultsContainer.addEventListener('mousedown', (event) => this.handleMousedown(event));
                document.addEventListener('click', (event) => {
                    if (this.wrapper && !this.wrapper.contains(event.target)) {
                        this.hideResults();
                    }
                });
                this.clearBtn = document.getElementById('menuSearchClear');
                if (this.clearBtn) {
                    this.clearBtn.addEventListener('pointerdown', (event) => {
                        event.preventDefault();
                        event.stopPropagation();
                        this.clearSearch();
                    });
                    this.updateClearButtonVisibility();
                }
            },
            getNavigationData: function() {
                if (typeof DolphinConfig !== 'undefined' && DolphinConfig && DolphinConfig.navigation) {
                    return DolphinConfig.navigation;
                }

                if (typeof window !== 'undefined' && window.DolphinConfig && window.DolphinConfig.navigation) {
                    return window.DolphinConfig.navigation;
                }

                return null;
            },
            getCurrentAppName: function() {
                const navigationData = this.getNavigationData();
                return String(navigationData?.currentApp?.name || '').toLowerCase();
            },
            normalizeIconToken: function(iconClass) {
                if (!iconClass) {
                    return 'ti-file';
                }

                const classes = String(iconClass).split(/\s+/).filter(Boolean);
                const matched = classes.find(cls => cls.indexOf('ti-') === 0);
                return matched || 'ti-file';
            },
            normalizeRegistryItem: function(item, index = 0) {
                return {
                    id: String(item?.id || `menu-search-${index}`),
                    title: String(item?.title || item?.name || ''),
                    href: String(item?.url || item?.href || ''),
                    iframeUrl: String(item?.iframe_url || item?.iframeUrl || item?.url || item?.href || ''),
                    normalizedPath: String(item?.normalized_path || item?.normalizedPath || ''),
                    iconClass: this.normalizeIconToken(item?.icon || item?.iconClass || 'ti-file'),
                    appName: String(item?.app_name || item?.appName || ''),
                    appTitle: String(item?.app_title || item?.appTitle || ''),
                    isCurrentApp: Boolean(item?.is_current_app ?? item?.isCurrentApp ?? false),
                    isPublic: Boolean(item?.is_public ?? item?.isPublic ?? false),
                    isWorkspace: Boolean(item?.is_workspace ?? item?.isWorkspace ?? false),
                    route: String(item?.route || ''),
                    sortOrder: Number.isFinite(Number(item?.sort_order ?? item?.sortOrder))
                        ? Number(item?.sort_order ?? item?.sortOrder)
                        : index
                };
            },
            buildFallbackRegistry: function() {
                const navigationData = this.getNavigationData();
                const currentAppName = this.getCurrentAppName();
                const currentAppTitle = String(navigationData?.currentApp?.title || '当前应用');

                return iframeManager.menuRegistry.map((item, index) => this.normalizeRegistryItem({
                    id: item?.normalizedPath || `fallback-${index}`,
                    title: item?.title || '',
                    url: item?.href || '',
                    iframe_url: item?.iframeUrl || item?.href || '',
                    normalized_path: item?.normalizedPath || '',
                    icon: item?.iconClass || 'ti-menu',
                    app_name: currentAppName,
                    app_title: currentAppTitle,
                    is_current_app: true,
                    is_workspace: Boolean(item?.isHomeMenu),
                    sort_order: index,
                }, index));
            },
            ensureRegistry: function() {
                const navigationData = this.getNavigationData();
                const searchableMenus = Array.isArray(navigationData?.searchableMenus)
                    ? navigationData.searchableMenus
                    : [];

                if (searchableMenus.length) {
                    return searchableMenus.map((item, index) => this.normalizeRegistryItem(item, index));
                }

                if (!iframeManager.menuRegistry.length) {
                    iframeManager.buildMenuRegistry();
                }

                return this.buildFallbackRegistry();
            },
            isCurrentAppItem: function(item) {
                const currentAppName = this.getCurrentAppName();
                return currentAppName !== '' && String(item?.appName || '').toLowerCase() === currentAppName;
            },
            getSortPriority: function(item) {
                if (this.isCurrentAppItem(item)) {
                    return 0;
                }

                if (item?.isWorkspace || item?.isPublic) {
                    return 1;
                }

                return 2;
            },
            resolveScopeLabel: function(item) {
                if (!item) return '';
                if (item.isWorkspace) return item.appTitle || '工作台';
                if (item.isPublic) return item.appTitle || '公共入口';
                return item.appTitle || (this.isCurrentAppItem(item) ? '当前应用' : '其他应用');
            },
            resolveScopeClass: function(item) {
                if (!item) return '';
                if (item.isWorkspace) return 'is-workspace';
                if (item.isPublic) return 'is-public';
                if (this.isCurrentAppItem(item)) return 'is-current';
                return 'is-external';
            },
            handleInput: function() {
                const keyword = (this.input.value || '').trim().toLowerCase();
                if (!keyword) {
                    this.hideResults();
                    return;
                }
                const registry = this.ensureRegistry();
                const matched = registry.filter(item => {
                    const title = (item.title || '').toLowerCase();
                    const path = (item.normalizedPath || '').toLowerCase();
                    const appTitle = (item.appTitle || '').toLowerCase();
                    return title.includes(keyword) || path.includes(keyword) || appTitle.includes(keyword);
                }).sort((a, b) => {
                    const priorityDiff = this.getSortPriority(a) - this.getSortPriority(b);
                    if (priorityDiff !== 0) {
                        return priorityDiff;
                    }

                    return (a.sortOrder || 0) - (b.sortOrder || 0);
                });
                this.results = matched.slice(0, this.maxResults);
                this.highlightedIndex = this.results.length ? 0 : -1;
                this.renderResults();
            },
            renderResults: function() {
                if (!this.resultsContainer) return;
                this.resultsContainer.innerHTML = '';
                if (!this.results.length) {
                    const empty = document.createElement('div');
                    empty.className = 'dropdown-item text-muted menu-search-empty';
                    empty.textContent = '未找到匹配菜单';
                    this.resultsContainer.appendChild(empty);
                    this.resultsContainer.classList.add('show');
                    return;
                }
                this.results.forEach((item, index) => {
                    const itemEl = document.createElement('a');
                    itemEl.href = '#';
                    itemEl.className = 'dropdown-item menu-search-item';
                    if (index === this.highlightedIndex) {
                        itemEl.classList.add('active');
                    }
                    itemEl.dataset.index = index.toString();
                    const icon = document.createElement('div');
                    icon.className = 'menu-search-icon';
                    icon.innerHTML = `<i class="ti ${item.iconClass || 'ti-menu'}"></i>`;
                    const textWrapper = document.createElement('div');
                    textWrapper.className = 'd-flex flex-column flex-grow-1';
                    const header = document.createElement('div');
                    header.className = 'menu-search-header';
                    const title = document.createElement('div');
                    title.className = 'menu-search-title';
                    title.textContent = item.title || '未命名菜单';
                    const scopeLabel = this.resolveScopeLabel(item);
                    if (scopeLabel) {
                        const badge = document.createElement('span');
                        badge.className = `menu-search-badge ${this.resolveScopeClass(item)}`.trim();
                        badge.textContent = scopeLabel;
                        header.appendChild(title);
                        header.appendChild(badge);
                    } else {
                        header.appendChild(title);
                    }
                    const path = document.createElement('div');
                    path.className = 'menu-search-path';
                    path.textContent = item.normalizedPath || item.href || '';
                    textWrapper.appendChild(header);
                    textWrapper.appendChild(path);
                    itemEl.appendChild(icon);
                    itemEl.appendChild(textWrapper);
                    this.resultsContainer.appendChild(itemEl);
                });
                this.resultsContainer.classList.add('show');
                this.updateClearButtonVisibility();
                this.scrollActiveIntoView();
            },
            handleKeyDown: function(event) {
                if (!this.resultsContainer || !this.resultsContainer.classList.contains('show')) return;
                if (event.key === 'ArrowDown') {
                    event.preventDefault();
                    this.moveHighlight(1);
                } else if (event.key === 'ArrowUp') {
                    event.preventDefault();
                    this.moveHighlight(-1);
                } else if (event.key === 'Enter') {
                    event.preventDefault();
                    this.confirmSelection();
                } else if (event.key === 'Escape') {
                    this.hideResults();
                }
            },
            moveHighlight: function(direction) {
                if (!this.results.length) return;
                this.highlightedIndex += direction;
                if (this.highlightedIndex < 0) this.highlightedIndex = this.results.length - 1;
                if (this.highlightedIndex >= this.results.length) this.highlightedIndex = 0;
                this.renderResults();
            },
            scrollActiveIntoView: function() {
                if (!this.resultsContainer) return;
                const activeItem = this.resultsContainer.querySelector('.menu-search-item.active');
                if (activeItem && typeof activeItem.scrollIntoView === 'function') {
                    activeItem.scrollIntoView({ block: 'nearest' });
                }
            },
            confirmSelection: function(index = null) {
                if (!this.results.length) return;
                const targetIndex = index !== null ? index : (this.highlightedIndex >= 0 ? this.highlightedIndex : 0);
                const item = this.results[targetIndex];
                if (!item) return;
                this.openMenu(item);
            },
            handleMousedown: function(event) {
                const item = event.target.closest('.menu-search-item');
                if (!item) return;
                event.preventDefault();
                const index = parseInt(item.dataset.index || '-1', 10);
                if (index >= 0) {
                    this.confirmSelection(index);
                }
            },
            openMenu: function(item) {
                if (!item) return;

                const sidebarNavigationManager = window.DolphinSidebarNavigationManager;
                if (sidebarNavigationManager && typeof sidebarNavigationManager.openMenuSearchItem === 'function') {
                    const handled = sidebarNavigationManager.openMenuSearchItem(item);
                    if (handled) {
                        this.hideResults();
                        this.input.blur();
                        return;
                    }
                }

                const appMode = (typeof DolphinConfig !== 'undefined' && DolphinConfig && DolphinConfig.tabMode)
                    ? String(DolphinConfig.tabMode).toLowerCase()
                    : 'iframe';
                const targetUrl = item.href || '';
                const targetIframeUrl = item.iframeUrl || targetUrl;

                // 首页菜单不创建新iframe标签，直接激活内置首页标签
                if (item.isWorkspace || item.isHomeMenu || targetUrl === './' || /admin\/index\/index/i.test(targetUrl || '')) {
                    if (appMode === 'iframe') {
                        const homeTabLink = document.querySelector('#pinnedTabs .nav-link[href="#tab-home"], #openedTabs .nav-link[href="#tab-home"]');
                        if (homeTabLink && iframeManager && typeof iframeManager.activateTabByLink === 'function') {
                            iframeManager.activateTabByLink(homeTabLink);
                        }
                    } else {
                        const homeUrl = (targetUrl && targetUrl !== '#') ? targetUrl : '/admin/index/index';
                        window.location.href = homeUrl;
                    }
                    this.hideResults();
                    this.input.blur();
                    return;
                }

                if (appMode !== 'iframe') {
                    if (targetUrl) {
                        window.location.href = targetUrl;
                    }
                    this.hideResults();
                    this.input.blur();
                    return;
                }

                const tabConfig = {
                    id: iframeManager.generateTabId ? iframeManager.generateTabId(targetIframeUrl || targetUrl) : `tab-${Date.now()}`,
                    title: item.title || '新标签页',
                    url: targetIframeUrl || targetUrl,
                    icon: item.iconClass || 'ti-file',
                    realIconHTML: null,
                    closable: true,
                    activate: true
                };
                if (iframeManager && typeof iframeManager.createDynamicTab === 'function') {
                    iframeManager.createDynamicTab(tabConfig);
                }
                this.hideResults();
                this.input.blur();
            },
            clearSearch: function() {
                if (this.input) {
                    this.input.value = '';
                }
                this.hideResults();
                this.input?.focus();
            },
            hideResults: function() {
                if (this.resultsContainer) {
                    this.resultsContainer.classList.remove('show');
                    this.resultsContainer.innerHTML = '';
                }
                this.results = [];
                this.highlightedIndex = -1;
                this.updateClearButtonVisibility();
            },
            updateClearButtonVisibility: function() {
                if (!this.clearBtn) return;
                if (this.input && this.input.value.trim()) {
                    this.clearBtn.classList.remove('d-none');
                } else {
                    this.clearBtn.classList.add('d-none');
                }
            }
        };

        const favoriteManager = {
            storageKey: 'dolphin_favorites',
            items: [],
            container: document.getElementById('favoriteItems'),
            allowedIconTags: ['svg', 'path', 'circle', 'g', 'i', 'span', 'use', 'rect', 'line', 'polyline', 'polygon'],

            sanitizeIconHTML: function(html) {
                if (!html || typeof html !== 'string') return '';
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = html;

                const nodes = [];
                const walker = document.createTreeWalker(tempDiv, NodeFilter.SHOW_ELEMENT, null);
                while (walker.nextNode()) {
                    nodes.push(walker.currentNode);
                }

                nodes.forEach(node => {
                    const tagName = (node.tagName || '').toLowerCase();
                    if (!this.allowedIconTags.includes(tagName)) {
                        node.replaceWith(...Array.from(node.childNodes));
                        return;
                    }

                    Array.from(node.attributes || []).forEach(attr => {
                        const name = attr.name.toLowerCase();
                        if (name.startsWith('on') ||
                            name === 'style' ||
                            name === 'href' ||
                            name === 'xlink:href' ||
                            name === 'src' ||
                            name === 'id') {
                            node.removeAttribute(attr.name);
                        }
                    });

                    node.removeAttribute('width');
                    node.removeAttribute('height');
                });

                const first = tempDiv.firstElementChild;
                return first ? tempDiv.innerHTML.trim() : '';
            },

            validateFavoriteItem: function(item) {
                if (!item || typeof item !== 'object') return false;
                if (!item.id || !item.url || !item.title) return false;
                if (item.iconClass && typeof item.iconClass !== 'string') {
                    return false;
                }

                if (item.realIconHTML) {
                    const sanitized = this.sanitizeIconHTML(item.realIconHTML);
                    if (!sanitized) {
                        item.realIconHTML = '';
                    } else {
                        item.realIconHTML = sanitized;
                    }
                }

                return true;
            },

            init: function() {
                this.loadFavorites();
                this.render();
            },

            loadFavorites: function() {
                try {
                    const saved = localStorage.getItem(this.storageKey);
                    const parsed = saved ? JSON.parse(saved) : [];
                    this.items = Array.isArray(parsed)
                        ? parsed.filter(item => this.validateFavoriteItem(item))
                        : [];
                } catch (e) {
                    Logger.warn('加载收藏数据失败', e);
                    this.items = [];
                }
            },

            saveFavorites: function() {
                try {
                    localStorage.setItem(this.storageKey, JSON.stringify(this.items));
                } catch (e) {
                    Logger.warn('保存收藏数据失败', e);
                }
            },

            render: function() {
                if (!this.container) return;
                this.cleanupContainerTooltips();
                this.container.innerHTML = '';

                this.items.forEach(item => {
                    this.container.appendChild(this.createFavoriteElement(item));
                });

                this.container.appendChild(this.createAddButton());

                if (typeof reinitializeTooltips === 'function') {
                    reinitializeTooltips();
                }

                this.updateActiveState();
            },

            createFavoriteElement: function(item) {
                const pill = document.createElement('div');
                pill.className = 'favorite-item';
                pill.dataset.favoriteId = item.id;
                pill.dataset.favoriteUrl = item.url;

                if (item.realIconHTML) {
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = item.realIconHTML;
                    const iconElement = tempDiv.firstElementChild;
                    if (iconElement) {
                        iconElement.removeAttribute('width');
                        iconElement.removeAttribute('height');
                        pill.appendChild(iconElement);
                    }
                }
                if (!pill.firstChild) {
                    const fallbackIcon = document.createElement('i');
                    fallbackIcon.className = `icon ${item.iconClass || 'ti ti-star'}`;
                    pill.appendChild(fallbackIcon);
                }

                pill.setAttribute('title', item.title);
                pill.setAttribute('data-bs-toggle', 'tooltip');
                pill.setAttribute('data-bs-placement', 'bottom');

                const removeBtn = document.createElement('span');
                removeBtn.className = 'favorite-remove';
                removeBtn.innerHTML = '&times;';
                removeBtn.title = '移除收藏';
                pill.appendChild(removeBtn);

                pill.addEventListener('click', () => this.openFavorite(item));
                removeBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.destroyTooltipInstance(pill);
                    this.removeFavorite(item.id);
                });
                pill.addEventListener('contextmenu', (e) => {
                    e.preventDefault();
                    this.destroyTooltipInstance(pill);
                    this.removeFavorite(item.id);
                });

                return pill;
            },

            createAddButton: function() {
                const addBtn = document.createElement('div');
                addBtn.className = 'favorite-add-btn';
                addBtn.innerHTML = '<i class="ti ti-plus"></i>';
                addBtn.title = '将当前标签添加到收藏';
                addBtn.setAttribute('data-bs-toggle', 'tooltip');
                addBtn.setAttribute('data-bs-placement', 'bottom');
                addBtn.addEventListener('click', () => {
                    this.destroyTooltipInstance(addBtn);
                    const navItem = this.getActiveNavItem();
                    if (!navItem) {
                        Dolphin.info('暂无可收藏的标签');
                        return;
                    }
                    this.addFavoriteFromNav(navItem);
                });
                return addBtn;
            },

            getActiveNavItem: function() {
                const activeLink = document.querySelector('#openedTabs .nav-link.active, #pinnedTabs .nav-link.active');
                return activeLink ? activeLink.closest('.nav-item') : null;
            },

            normalizeUrl: function(url) {
                if (!url) return '';
                try {
                    const parsed = new URL(url, window.location.origin);
                    let path = parsed.pathname;
                    if (path.endsWith('.html')) {
                        path = path.slice(0, -5);
                    }
                    path = path.replace(/\/$/, '');
                    return path + (parsed.search || '');
                } catch (e) {
                    return url.replace(/\/+/g, '/');
                }
            },

            generateFavoriteId: function(url) {
                if (iframeManager && typeof iframeManager.generateTabId === 'function') {
                    return `fav-${iframeManager.generateTabId(url)}`;
                }
                return `fav-${url.replace(/[^a-zA-Z0-9]+/g, '-')}`;
            },

            extractMetaFromNav: function(navItem) {
                if (!navItem) return null;
                const navLink = navItem.querySelector('.nav-link');
                if (!navLink) return null;
                const targetId = navLink.getAttribute('href');
                const tabPane = targetId ? document.querySelector(targetId) : null;
                const iframe = tabPane ? tabPane.querySelector('.content-iframe') : null;
                const rawUrl = iframe ? (iframe.getAttribute('data-src') || iframe.getAttribute('src')) : null;
                const url = this.normalizeUrl(rawUrl);
                if (!url || url === 'about:blank') return null;

                const title = navLink.querySelector('.tab-title')?.textContent.trim() || navLink.textContent.trim();
                let iconClass = 'ti-file';
                let realIconHTML = null;
                const storedIconHTML = navLink.getAttribute('data-original-icon');

                const extractTiClass = (element) => {
                    if (!element) return null;
                    const match = Array.from(element.classList || []).find(cls => cls.startsWith('ti-'));
                    return match || null;
                };

                if (storedIconHTML) {
                    realIconHTML = this.sanitizeIconHTML(storedIconHTML);
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = storedIconHTML;
                    const storedIcon = tempDiv.firstElementChild;
                    const iconCls = extractTiClass(storedIcon);
                    if (iconCls) iconClass = iconCls;
                } else {
                    const iconElement = navLink.querySelector('.icon');
                    if (iconElement) {
                        realIconHTML = this.sanitizeIconHTML(iconElement.outerHTML);
                        const iconCls = extractTiClass(iconElement);
                        if (iconCls) iconClass = iconCls;
                    }
                }

                return {
                    title: title || '未命名',
                    url,
                    iconClass,
                    realIconHTML
                };
            },

            isFavorite: function(url) {
                const normalized = this.normalizeUrl(url);
                return this.items.some(item => item.url === normalized);
            },

            addFavoriteFromNav: function(navItem) {
                if (navItem && utils.isPermanentTab(navItem)) {
                    Dolphin.info('工作台已固定，无需收藏');
                    return false;
                }
                const meta = this.extractMetaFromNav(navItem);
                if (!meta) {
                    Dolphin.warning('无法收藏该标签页');
                    return false;
                }
                return this.addFavorite(meta);
            },

            addFavoriteFromTabId: function(tabId) {
                const navItem = document.querySelector(`#openedTabs [data-tab-id="${tabId}"]`) ||
                                 document.querySelector(`#pinnedTabs [data-tab-id="${tabId}"]`);
                if (!navItem) {
                    Dolphin.warning('未找到对应的标签页');
                    return;
                }
                this.addFavoriteFromNav(navItem);
            },

            addFavorite: function(meta) {
                if (this.isFavorite(meta.url)) {
                    Dolphin.info('该页面已在收藏中');
                    return false;
                }
                const sanitizedIcon = this.sanitizeIconHTML(meta.realIconHTML);
                const favoriteItem = {
                    id: this.generateFavoriteId(meta.url),
                    title: meta.title,
                    url: meta.url,
                    iconClass: meta.iconClass,
                    realIconHTML: sanitizedIcon
                };
                if (!this.validateFavoriteItem(favoriteItem)) {
                    Dolphin.warning('收藏图标包含不受信任的内容，已使用安全图标');
                    favoriteItem.realIconHTML = '';
                }
                this.items.push(favoriteItem);
                this.saveFavorites();
                this.render();
                return true;
            },

            removeFavorite: function(favoriteId) {
                const targetElement = this.container ? this.container.querySelector(`[data-favorite-id="${favoriteId}"]`) : null;
                this.destroyTooltipInstance(targetElement);
                this.items = this.items.filter(item => item.id !== favoriteId);
                this.saveFavorites();
                this.render();
            },

            removeFavoriteByUrl: function(url) {
                const normalized = this.normalizeUrl(url);
                const targetElement = this.container ? this.container.querySelector(`[data-favorite-url="${normalized}"]`) : null;
                this.destroyTooltipInstance(targetElement);
                const beforeLength = this.items.length;
                this.items = this.items.filter(item => item.url !== normalized);
                if (beforeLength !== this.items.length) {
                    this.saveFavorites();
                    this.render();
                }
            },

            openFavorite: function(item) {
                if (!iframeManager || typeof iframeManager.createDynamicTab !== 'function') return;
                iframeManager.createDynamicTab({
                    id: iframeManager.generateTabId ? iframeManager.generateTabId(item.url) : `fav-${Date.now()}`,
                    title: item.title,
                    url: item.url,
                    icon: item.iconClass,
                    realIconHTML: item.realIconHTML,
                    closable: true,
                    activate: true
                });
            },

            updateActiveState: function() {
                if (!this.container) return;
                const activeLink = document.querySelector('#openedTabs .nav-link.active, #pinnedTabs .nav-link.active');
                const targetId = activeLink ? activeLink.getAttribute('href') : null;
                const tabPane = targetId ? document.querySelector(targetId) : null;
                const iframe = tabPane ? tabPane.querySelector('.content-iframe') : null;
                const activeUrl = iframe ? this.normalizeUrl(iframe.getAttribute('data-src') || iframe.getAttribute('src')) : null;

                this.container.querySelectorAll('.favorite-item').forEach(item => {
                    if (activeUrl && item.dataset.favoriteUrl === activeUrl) {
                        item.classList.add('active');
                    } else {
                        item.classList.remove('active');
                    }
                });
            },

            destroyTooltipInstance: function(element) {
                if (!element) return;
                try {
                    const ctor = (typeof window !== 'undefined' && window.bootstrap && window.bootstrap.Tooltip)
                        || (typeof bootstrap !== 'undefined' && bootstrap.Tooltip)
                        || null;

                    if (ctor) {
                        let instance = null;
                        if (typeof ctor.getInstance === 'function') {
                            instance = ctor.getInstance(element) || null;
                        }
                        if (!instance && typeof ctor.getOrCreateInstance === 'function') {
                            instance = ctor.getOrCreateInstance(element) || null;
                        }
                        if (instance) {
                            try {
                                instance.hide();
                            } catch (hideErr) {}
                            try {
                                instance.dispose();
                            } catch (disposeErr) {}
                        }
                    }

                    if (typeof $ !== 'undefined' && $.fn.tooltip) {
                        const $el = $(element);
                        const data = $el.data('bs.tooltip') || $el.data('tooltip');
                        if (data) {
                            try {
                                $el.tooltip('hide');
                            } catch (hideErr) {}
                            try {
                                $el.tooltip('dispose');
                            } catch (disposeErr) {}
                        }
                    }

                    const descId = element.getAttribute('aria-describedby');
                    if (descId) {
                        const tooltipEl = document.getElementById(descId);
                        if (tooltipEl) {
                            tooltipEl.remove();
                        }
                        element.removeAttribute('aria-describedby');
                    }
                } catch (e) {
                    Logger.debug('[FavoriteManager] 销毁tooltip失败', e);
                }
            },

            cleanupContainerTooltips: function() {
                if (!this.container) return;
                Array.from(this.container.children).forEach(child => {
                    this.destroyTooltipInstance(child);
                });
            }
        };
        globalFavoriteManager = favoriteManager;
        window.DolphinFavoriteManager = favoriteManager;

        // 绑定菜单项事件监听器
        const bindMenuEvents = function() {
            const actions = [
                { id: 'tabCloseCurrent', handler: tabBatchActions.closeCurrent, key: 'closeCurrent' },
                { id: 'tabCloseOthers', handler: tabBatchActions.closeOthers, key: 'closeOthers' },
                { id: 'tabCloseRight', handler: tabBatchActions.closeRight, key: 'closeRight' },
                { id: 'tabCloseAll', handler: tabBatchActions.closeAll, key: 'closeAll' }
            ];

            actions.forEach(({ id, handler, key }) => {
                const element = document.getElementById(id);
                if (!element) return;

                bindGlobalEvent(`tabs.menu.${key}`, element, 'click', (event) => {
                    event.preventDefault();
                    handler.call(tabBatchActions);
                });
            });
        };

        // 轻量公共组件：两种模式都初始化
        sidebarToggleManager.init();
        menuSearchManager.init();

        if (hasTabContainer) {
            // 初始化滚动管理器
            tabScrollManager.init();
            tabDropdownAutoCloser.init();

            // 初始化侧栏/收藏组件
            favoriteManager.init();
            const handleFavoriteShown = () => favoriteManager.updateActiveState();
            bindGlobalEvent('tabs.favorites.shown', document, 'shown.bs.tab', handleFavoriteShown);

            const handleFavoriteClickSync = (event) => {
                if (event.target.closest('#openedTabs .nav-link, #pinnedTabs .nav-link')) {
                    setTimeout(() => favoriteManager.updateActiveState(), 50);
                }
            };
            bindGlobalEvent('tabs.favorites.clickSync', document, 'click', handleFavoriteClickSync);
            sidebarToggleManager.afterToggle = () => {
                tabScrollManager.updateButtonState();
                setTimeout(() => tabScrollManager.updateButtonState(), 200);
            };

            // 统一事件绑定
            bindMenuEvents();

            // 初始化固定标签功能
            initPinnedTabs();

            // 初始化iframe管理器
            iframeManager.init();

            // 将iframeManager保存到外部作用域，供getIframeManager方法访问
            globalIframeManager = iframeManager;

            // 为页面加载时的所有标签设置初始时间戳
            const currentTime = Date.now();
            const allNavItems = document.querySelectorAll('#openedTabs .nav-item, #pinnedTabs .nav-item');

            allNavItems.forEach(navItem => {
                if (!navItem.getAttribute('data-last-active')) {
                    const navLink = navItem.querySelector('.nav-link');
                    if (navLink && navLink.classList.contains('active')) {
                        // 活跃标签设置为当前时间
                        navItem.setAttribute('data-last-active', currentTime.toString());
                    } else {
                        // 非活跃标签设置为稍早的时间，避免立即被认为超时
                        // 设置为30秒前，这样不会立即触发1分钟阈值
                        navItem.setAttribute('data-last-active', (currentTime - 30000).toString());
                    }
                }
            });
        }

        // 初始化固定标签功能
        function initPinnedTabs() {
            // 绑定右键菜单
            bindContextMenu();

            // 隐藏空的固定标签区域
            updatePinnedTabsVisibility();
        }

        // 绑定右键菜单
        function bindContextMenu() {
            const contextMenu = document.getElementById('tabContextMenu');
            if (!contextMenu) return;

            // 绑定所有标签的右键事件
            const handleContextMenu = (e) => {
                const navItem = e.target.closest('.nav-item');
                if (!navItem || !navItem.hasAttribute('data-tab-id')) return;

                if (utils.isPermanentTab(navItem)) {
                    return;
                }

                e.preventDefault();

                contextMenu.dataset.tabId = navItem.getAttribute('data-tab-id');

                const isPinned = navItem.closest('#pinnedTabs') !== null;

                const pinAction = contextMenu.querySelector('[data-action="pin"]');
                const unpinAction = contextMenu.querySelector('[data-action="unpin"]');
                const closeCurrentAction = contextMenu.querySelector('[data-action="close-current"]');
                const favoriteAddAction = contextMenu.querySelector('[data-action="favorite-add"]');
                const favoriteRemoveAction = contextMenu.querySelector('[data-action="favorite-remove"]');

                if (isPinned) {
                    pinAction.style.display = 'none';
                    unpinAction.style.display = 'flex';
                    if (closeCurrentAction) closeCurrentAction.style.display = 'flex';
                } else {
                    pinAction.style.display = 'flex';
                    unpinAction.style.display = 'none';
                    if (closeCurrentAction) closeCurrentAction.style.display = 'flex';
                }

                const meta = favoriteManager.extractMetaFromNav(navItem);
                if (meta && meta.url) {
                    contextMenu.dataset.favoriteUrl = meta.url;
                    if (favoriteManager.isFavorite(meta.url)) {
                        if (favoriteAddAction) favoriteAddAction.style.display = 'none';
                        if (favoriteRemoveAction) favoriteRemoveAction.style.display = 'flex';
                    } else {
                        if (favoriteAddAction) favoriteAddAction.style.display = 'flex';
                        if (favoriteRemoveAction) favoriteRemoveAction.style.display = 'none';
                    }
                } else {
                    delete contextMenu.dataset.favoriteUrl;
                    if (favoriteAddAction) favoriteAddAction.style.display = 'none';
                    if (favoriteRemoveAction) favoriteRemoveAction.style.display = 'none';
                }

                contextMenu.style.display = 'block';

                let menuX = e.clientX;
                let menuY = e.clientY;

                const menuRect = contextMenu.getBoundingClientRect();
                const viewportWidth = window.innerWidth;
                const viewportHeight = window.innerHeight;

                if (menuX + menuRect.width > viewportWidth) {
                    menuX = viewportWidth - menuRect.width - 10;
                }

                if (menuY + menuRect.height > viewportHeight) {
                    menuY = viewportHeight - menuRect.height - 10;
                }

                menuX = Math.max(10, menuX);
                menuY = Math.max(10, menuY);

                contextMenu.style.left = menuX + 'px';
                contextMenu.style.top = menuY + 'px';
            };

            bindGlobalEvent('tabs.contextmenu.open', document, 'contextmenu', handleContextMenu);

            const handleMenuClick = (e) => {
                e.preventDefault();
                const action = e.target.closest('[data-action]')?.dataset.action;
                const tabId = contextMenu.dataset.tabId;
                const favoriteUrl = contextMenu.dataset.favoriteUrl;

                if (!action || !tabId) return;

                const activateContextTab = () => {
                    const targetTabItem = document.querySelector(`#openedTabs [data-tab-id="${tabId}"]`) ||
                        document.querySelector(`#pinnedTabs [data-tab-id="${tabId}"]`);
                    const targetTabLink = targetTabItem?.querySelector('.nav-link');
                    if (!targetTabLink) return;
                    if (targetTabLink.classList.contains('active')) return;
                    if (iframeManager && typeof iframeManager.activateTabByLink === 'function') {
                        iframeManager.activateTabByLink(targetTabLink);
                    } else {
                        utils.activateTab(targetTabLink);
                    }
                };

                switch (action) {
                    case 'pin':
                        pinTab(tabId);
                        break;
                    case 'unpin':
                        unpinTab(tabId);
                        break;
                    case 'refresh':
                        if (iframeManager && typeof iframeManager.refreshTab === 'function') {
                            iframeManager.refreshTab(tabId);
                        }
                        break;
                    case 'close-current':
                        closeTabById(tabId);
                        break;
                    case 'close-others':
                        activateContextTab();
                        tabBatchActions.closeOthers();
                        break;
                    case 'close-right':
                        activateContextTab();
                        tabBatchActions.closeRight();
                        break;
                    case 'favorite-add':
                        favoriteManager.addFavoriteFromTabId(tabId);
                        break;
                    case 'favorite-remove':
                        favoriteManager.removeFavoriteByUrl(favoriteUrl);
                        break;
                }

                hideContextMenu();
            };

            bindGlobalEvent('tabs.contextmenu.action', contextMenu, 'click', handleMenuClick);

            const hideIfOutside = (e) => {
                if (!e.target.closest('#tabContextMenu')) {
                    hideContextMenu();
                }
            };
            bindGlobalEvent('tabs.contextmenu.dismissClick', document, 'click', hideIfOutside);
            bindGlobalEvent('tabs.contextmenu.scroll', document, 'scroll', hideContextMenu);

            const hideForGlobalMenu = (e) => {
                if (!e.target.closest('.nav-item')) {
                    hideContextMenu();
                }
            };

            bindGlobalEvent('tabs.contextmenu.dismissGlobal', document, 'contextmenu', hideForGlobalMenu);
            bindGlobalEvent('tabs.contextmenu.windowBlur', window, 'blur', hideContextMenu);
        }

        // 隐藏右键菜单
        function hideContextMenu() {
            const contextMenu = document.getElementById('tabContextMenu');
            if (contextMenu) {
                contextMenu.style.display = 'none';
                delete contextMenu.dataset.tabId;
                delete contextMenu.dataset.favoriteUrl;
            }
        }

        /**
         * 固定标签到固定区域
         *
         * 将一个普通标签移动到固定标签区域，同时：
         * 1. 复制标签DOM结构到固定区域
         * 2. 添加tooltip属性以显示标签名称（因为固定标签只显示图标）
         * 3. 从普通标签区域移除原标签
         * 4. 更新localStorage持久化存储
         * 5. 重新初始化所有tooltip
         *
         * @function pinTab
         * @param {string} tabId - 要固定的标签ID
         */
        function pinTab(tabId) {
            const openedTabsContainer = document.getElementById('openedTabs');
            const normalTab = openedTabsContainer.querySelector(`[data-tab-id="${tabId}"]`);
            const pinnedTabsContainer = document.getElementById('pinnedTabs');
            if (!normalTab || !pinnedTabsContainer) return;

            // 直接移动标签节点，保留已有事件与运行时状态
            const pinnedTab = normalTab;
            const pinnedLink = pinnedTab.querySelector('.nav-link');
            const tabTitle = pinnedLink?.querySelector('.tab-title');

            // 使用工具函数设置tooltip
            if (pinnedLink && tabTitle) {
                utils.setTooltip(pinnedLink, tabTitle.textContent.trim());
            }

            pinnedTabsContainer.appendChild(pinnedTab);

            // 更新状态和重新绑定事件
            savePinnedTabs();
            updatePinnedTabsVisibility();
            reinitializeTooltips();
        }

        /**
         * 取消固定标签
         *
         * 将一个固定标签移回普通标签区域，同时：
         * 1. 销毁原有的tooltip实例
         * 2. 复制标签DOM结构到普通区域
         * 3. 移除tooltip相关属性（因为普通标签不需要tooltip）
         * 4. 从固定标签区域移除原标签
         * 5. 更新localStorage持久化存储
         * 6. 重新初始化所有tooltip
         *
         * @function unpinTab
         * @param {string} tabId - 要取消固定的标签ID
         */
        function unpinTab(tabId) {
            const pinnedTabsContainer = document.getElementById('pinnedTabs');
            const pinnedTab = pinnedTabsContainer?.querySelector(`[data-tab-id="${tabId}"]`);
            if (!pinnedTab) return;

            // 销毁tooltip
            destroyTooltip(pinnedTab);

            // 直接移动标签节点，保留已有事件与运行时状态
            const normalTab = pinnedTab;
            const normalLink = normalTab.querySelector('.nav-link');

            // 使用工具函数移除tooltip属性
            if (normalLink) {
                utils.removeTooltip(normalLink);
            }

            const openedTabsContainer = document.getElementById('openedTabs');
            openedTabsContainer.appendChild(normalTab);

            // 更新状态和重新绑定事件
            savePinnedTabs();
            updatePinnedTabsVisibility();

            // 重新初始化所有tooltip
            reinitializeTooltips();
        }

        // 通过ID关闭标签
        function closeTabById(tabId) {
            const pinnedTab = document.querySelector(`#pinnedTabs [data-tab-id="${tabId}"]`);
            const normalTab = document.querySelector(`#openedTabs [data-tab-id="${tabId}"]`);

            if (pinnedTab) {
                // 处理固定标签的关闭
                const allTabs = document.querySelectorAll('[data-tab-id]');

                // 如果只剩一个标签，不允许关闭
                if (allTabs.length <= 1) {
                    Dolphin.warning('至少需要保留一个标签页');
                    return;
                }

                // 如果关闭的是当前活跃固定标签，需要先激活下一个标签
                const pinnedLink = pinnedTab.querySelector('.nav-link');
                if (pinnedLink && pinnedLink.classList.contains('active')) {
                    tabBatchActions.activateNextTab(pinnedTab);
                }

                // 直接移除固定标签
                pinnedTab.remove();

                // 更新固定标签状态
                savePinnedTabs();
                updatePinnedTabsVisibility();

                // 更新滚动按钮状态
                setTimeout(() => tabScrollManager.updateButtonState(), 100);

            } else if (normalTab) {
                // 处理普通标签的关闭 - 直接调用共享关闭逻辑，避免模拟点击
                performTabClose(normalTab);
            }
        }

        // 保存固定标签到localStorage
        function savePinnedTabs() {
            try {
                const pinnedTabs = document.querySelectorAll('#pinnedTabs .nav-item[data-tab-id]');
                const pinnedTabIds = Array.from(pinnedTabs).map(tab => tab.getAttribute('data-tab-id'));
                localStorage.setItem('dolphin_pinned_tabs', JSON.stringify(pinnedTabIds));
            } catch (e) {
                Logger.warn('无法保存固定标签:', e);
            }
        }

        // 恢复固定标签
        function restorePinnedTabs() {
            try {
                const savedPinned = localStorage.getItem('dolphin_pinned_tabs');
                if (!savedPinned) return;

                const pinnedTabIds = JSON.parse(savedPinned);
                const pinnedTabs = document.getElementById('pinnedTabs');

                pinnedTabIds.forEach(tabId => {
                    const normalTab = document.querySelector(`#openedTabs [data-tab-id="${tabId}"]`);
                    if (normalTab && pinnedTabs) {
                        const pinnedTab = normalTab;

                        // 为恢复的固定标签添加tooltip
                        const pinnedLink = pinnedTab.querySelector('.nav-link');
                        const tabTitle = pinnedLink.querySelector('.tab-title');

                        if (pinnedLink && tabTitle) {
                            const titleText = tabTitle.textContent.trim();
                            pinnedLink.setAttribute('data-bs-toggle', 'tooltip');
                            pinnedLink.setAttribute('data-bs-placement', 'top');
                            pinnedLink.setAttribute('data-bs-original-title', titleText);
                        }

                        pinnedTabs.appendChild(pinnedTab);
                    }
                });

                updatePinnedTabsVisibility();

                // 重新初始化所有tooltip
                reinitializeTooltips();
            } catch (e) {
                Logger.warn('无法恢复固定标签:', e);
            }
        }

        // 更新固定标签区域的显示状态
        function updatePinnedTabsVisibility() {
            const pinnedTabs = document.getElementById('pinnedTabs');
            const separator = document.getElementById('tabsSeparator');

            if (pinnedTabs && separator) {
                const hasPinnedTabs = pinnedTabs.children.length > 0;
                separator.style.display = hasPinnedTabs ? 'block' : 'none';
            }
        }

        // 初始化tooltip（已简化，统一使用reinitializeTooltips）
        function initTooltip(element) {
            // 不再需要，直接调用reinitializeTooltips即可
        }

        // 销毁tooltip
        function destroyTooltip(element) {
            try {
                const tooltipElement = element.querySelector('[data-bs-toggle="tooltip"]');
                if (tooltipElement && typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                    const tooltip = bootstrap.Tooltip.getInstance(tooltipElement);
                    if (tooltip) {
                        tooltip.dispose();
                    }
                }
            } catch (e) {
                Logger.warn('无法销毁tooltip:', e);
            }
        }

        /**
         * 重新初始化所有tooltip
         *
         * 当动态添加或修改含有tooltip的DOM元素时，需要重新初始化tooltip实例
         * 该函数会尝试多种方式来确保tooltip正常工作：
         * 1. 直接使用Bootstrap的Tooltip类
         * 2. 通过window.bootstrap访问Tooltip类
         * 3. 使用jQuery的tooltip方法（备用方案）
         *
         * @function reinitializeTooltips
         * @description 重新扫描页面中所有带有data-bs-toggle="tooltip"属性的元素并初始化tooltip
         */
        function reinitializeTooltips() {
            // 延迟执行以确保DOM更新完成，避免在DOM操作过程中初始化失败
            setTimeout(() => {
                // 查找页面中所有需要tooltip的元素
                const tooltipElements = document.querySelectorAll('[data-bs-toggle="tooltip"]');

                if (tooltipElements.length > 0) {
                    // 方式1: 直接调用Bootstrap Tooltip (推荐方式)
                    // 适用于Tabler框架和标准Bootstrap环境
                    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                        tooltipElements.forEach(element => {
                            try {
                                // 检查元素是否已有tooltip实例，避免重复初始化
                                const existingInstance = bootstrap.Tooltip.getInstance(element);
                                if (!existingInstance) {
                                    new bootstrap.Tooltip(element);
                                }
                            } catch (e) {
                                // 静默处理错误，继续处理下一个元素
                            }
                        });
                    }
                    // 方式2: 通过window.bootstrap访问 (备用方式)
                    // 某些情况下bootstrap对象可能挂载在window上
                    else if (typeof window.bootstrap !== 'undefined' && window.bootstrap.Tooltip) {
                        tooltipElements.forEach(element => {
                            try {
                                const existingInstance = window.bootstrap.Tooltip.getInstance(element);
                                if (!existingInstance) {
                                    new window.bootstrap.Tooltip(element);
                                }
                            } catch (e) {
                                // 静默处理错误
                            }
                        });
                    }
                    // 方式3: jQuery方式 (最后的备用方案)
                    // 当Bootstrap对象不可用时的兼容性方案
                    else if (typeof $ !== 'undefined' && $.fn.tooltip) {
                        try {
                            $(tooltipElements).tooltip();
                        } catch (e) {
                            // 静默处理错误
                        }
                    }
                }
            }, 200); // 延迟200ms确保DOM完全更新，这个时间经过测试比较合适
        }

        // 恢复保存的标签页状态（串行流程：恢复标签 -> 恢复顺序 -> 恢复固定 -> 激活目标）
        const restoreTabsStateInOrder = () => {
            let targetTabId = null;

            if (typeof iframeManager !== 'undefined' && iframeManager.restoreTabStates) {
                targetTabId = iframeManager.restoreTabStates();
            }

            restorePinnedTabs();

            // 等待DOM迁移（固定标签）完成后再激活目标标签，避免引用失效
            requestAnimationFrame(() => {
                requestAnimationFrame(() => {
                    if (typeof iframeManager !== 'undefined' && iframeManager.activateRestoredTabById) {
                        iframeManager.activateRestoredTabById(targetTabId);
                    }
                    tabScrollManager.updateButtonState();
                });
            });
        };

        setTimeout(restoreTabsStateInOrder, 500); // 延迟执行，确保所有初始化完成

        return {
            tabScrollManager,
            iframeManager,
            favoriteManager,
            tabBatchActions,
            sidebarToggleManager,
            menuSearchManager
        };
    }

    /**
     * ajax请求
     * @param url
     * @param type
     * @param data
     * @param options
     * @returns {*}
     * @private
     */
    const _ajax = function (url, type, data, options = {}) {
        if (typeof url === "object") {
            options = url;
            url = undefined;
        }

        options = options || {};

        let _options = {
            url: url,
            type: type || 'get',
            data: data || {},
        }

        // 提取自定义回调
        const success = options.success;
        const error = options.error;
        const complete = options.complete;
        const showDefaultMsg = options.showDefaultMsg !== false; // 默认显示系统消息
        const showLoading = options.showLoading !== false; // 默认显示加载提示

        // 删除自定义回调，避免与 jQuery.ajax 冲突
        delete options.success;
        delete options.error;
        delete options.complete;
        delete options.showDefaultMsg;
        delete options.showLoading;

        if (showLoading) {
            _loading();
        }

        const jqXHR = $.ajax($.extend(_options, options));

        // 添加成功处理
        jqXHR.done(function (res) {
            if (showLoading) {
                _loading('hide');
            }

            // 执行自定义成功回调
            if (typeof success === 'function') {
                const result = success.call(this, res);
                // 如果自定义回调返回 false，则不显示默认消息
                if (result === false) {
                    return;
                }
            }

            // 显示默认消息
            if (showDefaultMsg) {
                if (res.code === 1) {
                    Dolphin.success(res.msg);
                } else {
                    Dolphin.error(res.msg);
                }
            }
        });

        // 添加失败处理
        jqXHR.fail(function(err) {
            if (showLoading) {
                Dolphin.loading('hide');
            }

            // 执行自定义错误回调
            if (typeof error === 'function') {
                const result = error.call(this, err);
                // 如果自定义回调返回 false，则不显示默认错误消息
                if (result === false) {
                    return;
                }
            }

            // 显示默认错误消息
            if (showDefaultMsg) {
                let errorMsg = '请求失败，请稍后重试';
                if (err['responseJSON']) {
                    errorMsg = err['responseJSON'].message || errorMsg;
                } else if (err.responseText) {
                    try {
                        errorMsg = $(err.responseText).find('h1:first').text().trim() || errorMsg;
                    } catch (e) {}
                }
                Dolphin.error($('<div>').text(errorMsg).html());
            }
        });

        // 添加完成处理（无论成功失败都执行）
        jqXHR.always(function() {
            // 执行自定义完成回调
            if (typeof complete === 'function') {
                complete.call(this);
            }
        });

        return jqXHR;
    }

    /**
     * 页面加载提示
     * @param $msg 提示文字,默认"加载中..."
     * @private
     */
    const _loading = function ($msg = '加载中...') {
        let $loadingEl = jQuery('#dp-loading');
        if ($msg === 'hide') {
            if ($loadingEl.length) {
                $loadingEl.fadeOut(250);
            }
        } else {
            if ($loadingEl.length) {
                $loadingEl.fadeIn(250);
            } else {
                jQuery('body').append('<div id="dp-loading"><div class="dp-spinner" role="status"></div><div>' + $msg + '</div></div>');
            }
        }
    }

    /**
     * 页面通知
     * @param $msg 提示信息
     * @param $type 提示类型:'info', 'success', 'warning', 'error'
     * @param $options 参数
     *      int duration 持续显示时间,默认2秒
     *      array position 坐标位置, [x,y],x取值:left | center | right, y取值:top | center | bottom
     *      bool dismissible 是否可关闭
     * @private
     */
    const _notify = function ($msg, $type = 'info', $options = null) {
        let _options = {
            duration: 2000,
            ripple: false,
            position: {x: 'right', y: 'top'},
            dismissible: true,
            types: [
                {
                    type: 'warning',
                    background: '#f76707',
                    icon: {
                        className: 'fa-solid fa-triangle-exclamation',
                        color: '#FFFFFF'
                    }
                },
                {
                    type: 'info',
                    background: '#4299e1',
                    icon: {
                        className: 'fa-solid fa-circle-info',
                        color: '#FFFFFF'
                    }
                }
            ]
        }

        if ($options !== null) {
            $.extend(_options, $options);
            notify = new Notyf(_options);
        } else if (notify === null) {
            notify = new Notyf(_options);
        }

        notify.open({
            type: $type,
            message: $msg
        });
    }

    /**
     * 弹窗提示
     * @param msg 提示内容
     * @param type 提示类型:success,error,warning,info,question
     * @param timer 提示停留时间,单位毫秒
     * @param position 位置:'top', 'top-start', 'top-end', 'center', 'center-start', 'center-end', 'bottom', 'bottom-start', or 'bottom-end'.
     * @private
     */
    const _toast = function (msg, type = 'info', timer = 2000, position = 'top') {
        let _options = $.extend({
            toast: true,
            position: position,
            showConfirmButton: false,
            showCloseButton: true,
            timer: timer,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        }, DolphinOption.toast);

        const Toast = Swal.mixin(_options);
        Toast.fire({
            icon: type,
            title: msg
        });
    }

    /**
     * 确认提示
     * @param msg 提示信息
     * @param text 额外提示内容
     * @param callback 确认回调函数
     * @param options 参数
     * @private
     */
    const _confirm = function (msg, text, callback, options = {}) {
        options = $.extend({
            title: msg || '确定要执行该操作？',
            html: text || '',
            icon: 'question',
            iconColor: '#f59f00',
            showCancelButton: true,
            cancelButtonText: "取消",
            confirmButtonText: "确定",
            confirmButtonColor: '#3085d6'
        }, options);
        Swal.fire(options).then((result) => {
            if (result.isConfirmed) {
                callback();
            }
        });
    }

    /**
     * 加密
     * @param data
     * @param key
     * @param iv
     * @returns {string}
     * @private
     */
    const _encrypt = function (data, key, iv) {
        let text = typeof data == 'string' ? data : JSON.stringify(data);
        text = CryptoJS.enc.Base64.stringify(CryptoJS.enc.Utf8.parse(text)).toString();
        key = CryptoJS.enc.Utf8.parse(key); // 为了避免补位，直接用16位的秘钥
        iv = CryptoJS.enc.Utf8.parse(iv); // 16位初始向量
        let encrypted = CryptoJS.AES.encrypt(text, key, {
            iv: iv,
            mode: CryptoJS.mode.CBC,
            padding: CryptoJS.pad.ZeroPadding
        }).toString();
        return CryptoJS.enc.Base64.stringify(CryptoJS.enc.Utf8.parse(encrypted)).toString();
    }

    /**
     * 解密
     * @param data
     * @param key
     * @param iv
     * @returns {string}
     * @private
     */
    const _decrypt = function (data, key, iv) {
        let encrypted = CryptoJS.enc.Utf8.stringify(CryptoJS.enc.Base64.parse(data)).toString();
        key = CryptoJS.enc.Utf8.parse(key); //为了避免补位，直接用16位的秘钥
        iv = CryptoJS.enc.Utf8.parse(iv); //16位初始向量
        let decrypted = CryptoJS.AES.decrypt(encrypted, key, {
            iv: iv,
            mode: CryptoJS.mode.CBC,
            padding: CryptoJS.pad.ZeroPadding
        }).toString(CryptoJS.enc.Utf8);
        return CryptoJS.enc.Utf8.stringify(CryptoJS.enc.Base64.parse(decrypted)).toString();
    }

    /**
     * 刷新表单令牌
     * @param name
     * @private
     */
    const _refreshToken = function (name) {
        name = name || '__token__';
        $.get('/_token', function (res) {
            $('input[name="' + name + '"]').val(res);
            $('meta[name="csrf-token"]').attr('content', res);
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': res
                }
            });
        }).fail(function () {
            _toast('刷新表单令牌失败', 'error', 3000);
        });
    }

    /**
     * 生成随机字符串
     * @param length 长度
     * @param type 类型:0-小写字母+数字，1-小写字母，2-大写字母，3-数字，4-小写+大写字母，5-小写+大写+数字
     * @returns {string}
     * @private
     */
    const _randStr = (length = 8, type = 0) => {
        const charSets = {
            a: 'abcdefghijklmnopqrstuvwxyz',
            A: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
            n: '0123456789'
        };

        // 使用 Map 定义字符集组合
        const typeMap = new Map([
            [1, 'a'],
            [2, 'A'],
            [3, 'n'],
            [4, 'aA'],
            [5, 'aAn'],
            [6, 'An'],
            [0, 'an']
        ]);

        // 获取对应类型的字符集
        const chars = (typeMap.get(type) || 'an')
            .split('')
            .map(key => charSets[key])
            .join('');

        // 使用 Array.from 生成随机字符串
        return Array.from(
            { length },
            () => chars[Math.floor(Math.random() * chars.length)]
        ).join('');
    };

    /**
     * 加载图片
     * @param url
     * @returns {Promise<never>|Promise<unknown>}
     * @private
     */
    const _loadImage = function (url) {
        // 参数验证
        if (!url || typeof url !== 'string') {
            return Promise.reject(new Error('无效的图片URL'));
        }

        // 处理URL
        const processedUrl = (() => {
            const baseUrl = url.trim();
            // 只有非blob URL才需要添加时间戳
            return baseUrl.startsWith('blob:')
                ? baseUrl
                : `${baseUrl}${baseUrl.includes('?') ? '&' : '?'}t=${Date.now()}`;
        })();

        return new Promise((resolve, reject) => {
            const img = new Image();

            // 必须在设置src之前设置crossOrigin属性
            img.crossOrigin = 'anonymous';

            // 设置src触发加载
            img.src = processedUrl;

            // 绑定事件处理
            img.onload = () => resolve(img);
            img.onerror = () => {
                const error = new Error('图片加载失败');
                Dolphin.error('图片加载失败，请确认图片链接是否有效');
                reject(error);
            };

            // 如果图片已经加载完成，直接返回
            if (img.complete) {
                resolve(img);
            }
        });
    }

    /**
     * 生成缩略图
     * @param imageUrl
     * @param options
     * @returns {Promise<never>|Promise<unknown>}
     * @private
     */
    const _thumbImage = function (imageUrl, options) {
        // 参数验证
        if (!imageUrl || typeof imageUrl !== 'string') {
            return Promise.reject(new Error('无效的图片URL'));
        }

        return new Promise((resolve, reject) => {
            let {
                width = 50,
                height = 50,
                quality = 0.8,
                type = 'image/jpeg'
            } = options;

            // 验证数值参数
            if (width <= 0 || height <= 0 || quality <= 0 || quality > 1) {
                reject('无效参数');
                return;
            }

            try {
                Dolphin.loadImage(imageUrl).then(img => {
                    // 创建canvas
                    const canvas = document.createElement('canvas');
                    const ctx = canvas.getContext('2d');

                    if (!ctx) {
                        reject('canvas初始化失败');
                        return;
                    }

                    // 计算canvas的尺寸
                    if (img.width > img.height) {
                        width = img.width * height / img.height;
                    } else if (img.height > img.width) {
                        height = img.height * width / img.width;
                    }

                    // 设置canvas尺寸
                    canvas.width = width;
                    canvas.height = height;

                    // 优化图片渲染
                    ctx.imageSmoothingEnabled = true;
                    ctx.imageSmoothingQuality = 'high';

                    // 绘制图片
                    ctx.drawImage(img, 0, 0, width, height);
                    // 转换为base64
                    const thumbnail = canvas.toDataURL(type, quality);
                    // 清理canvas
                    ctx.clearRect(0, 0, canvas.width, canvas.height);

                    resolve(thumbnail);
                })
            } catch (error) {
                reject('生成缩略图失败：' + (error.message || '未知错误'));
            }
        })
    }

    /**
     * 检查是否支持 Web Crypto API
     * @returns {boolean}
     * @private
     */
    const _isCryptoAPISupported = function () {
        return typeof crypto !== 'undefined' &&
               typeof crypto.subtle !== 'undefined' &&
               typeof crypto.subtle.digest === 'function';
    }

    /**
     * 检查当前环境是否为安全上下文（HTTPS、localhost等）
     * @returns {boolean}
     * @private
     */
    const _isSecureContext = function () {
        // 检查浏览器是否支持 isSecureContext API
        if (typeof window !== 'undefined' && typeof window.isSecureContext !== 'undefined') {
            return window.isSecureContext;
        }

        // 手动检查安全上下文
        if (typeof location !== 'undefined') {
            const protocol = location.protocol;
            const hostname = location.hostname;

            // HTTPS 总是安全的
            if (protocol === 'https:') {
                return true;
            }

            // localhost 和 127.0.0.1 在 HTTP 下也是安全的
            if (protocol === 'http:' && (hostname === 'localhost' || hostname === '127.0.0.1' || hostname === '::1')) {
                return true;
            }

            // file:// 协议通常也是安全的
            if (protocol === 'file:') {
                return true;
            }
        }

        return false;
    }

    /**
     * 将哈希值填充到40个字符（与SHA-1长度一致）
     * @param {string} hash - 原始哈希值
     * @param {string} fileName - 文件名
     * @param {number} fileSize - 文件大小
     * @param {number} lastModified - 最后修改时间
     * @returns {string} 40字符的哈希值
     * @private
     */
    const _padHashTo40Chars = function (hash, fileName, fileSize, lastModified) {
        // 确保输入哈希是字符串
        hash = String(hash || '');

        // 如果已经是40字符，直接返回
        if (hash.length === 40) {
            return hash;
        }

        // 如果超过40字符，截取前40字符
        if (hash.length > 40) {
            return hash.substring(0, 40).toLowerCase();
        }

        // 生成补充信息用于填充
        const supplements = [
            (fileName || 'unknown').toString(),
            (fileSize || 0).toString(),
            (lastModified || Date.now()).toString()
        ];

        // 创建补充哈希源
        const supplementText = supplements.join('-');
        let supplementHash = '';

        // 使用简单哈希算法生成补充哈希
        for (let i = 0; i < supplementText.length; i++) {
            const char = supplementText.charCodeAt(i);
            supplementHash += (char * 31 + i).toString(16);
        }

        // 组合原始哈希和补充哈希
        let combinedHash = hash + supplementHash;

        // 截取或填充到40字符
        if (combinedHash.length > 40) {
            combinedHash = combinedHash.substring(0, 40);
        } else if (combinedHash.length < 40) {
            // 使用重复模式填充到40字符
            const pattern = 'abcdef0123456789';
            while (combinedHash.length < 40) {
                const patternIndex = combinedHash.length % pattern.length;
                combinedHash += pattern.charAt(patternIndex);
            }
        }

        return combinedHash.toLowerCase();
    }

    /**
     * 使用简单哈希算法（适用于不支持 Web Crypto API 的环境）
     * @param {ArrayBuffer} buffer
     * @returns {string} 20字符的哈希值（后续会填充到40字符）
     * @private
     */
    const _simpleHash = function (buffer) {
        const uint8Array = new Uint8Array(buffer);
        let hash1 = 0;
        let hash2 = 0;
        let hash3 = 0;
        let hash4 = 0;

        // 使用多重哈希算法增加分散性
        for (let i = 0; i < uint8Array.length; i++) {
            const byte = uint8Array[i];

            // 第一个哈希：经典 hashCode 算法
            hash1 = ((hash1 << 5) - hash1) + byte;
            hash1 = hash1 & hash1; // 转换为32位整数

            // 第二个哈希：使用不同的位移
            hash2 = ((hash2 << 3) - hash2) + byte;
            hash2 = hash2 & hash2;

            // 第三个哈希：使用素数
            hash3 = (hash3 * 31 + byte) & 0xffffffff;

            // 第四个哈希：使用另一个素数
            hash4 = (hash4 * 37 + byte) & 0xffffffff;
        }

        // 转换为十六进制字符串
        const hex1 = (hash1 >>> 0).toString(16).padStart(8, '0');
        const hex2 = (hash2 >>> 0).toString(16).padStart(8, '0');
        const hex3 = (hash3 >>> 0).toString(16).padStart(8, '0');
        const hex4 = (hash4 >>> 0).toString(16).padStart(8, '0');

        // 组合前两个和后两个的一半，形成20字符的基础哈希（更接近40字符目标的一半）
        const shortHex3 = hex3.substring(0, 4);
        const shortHex4 = hex4.substring(0, 4);
        return (hex1 + hex2 + shortHex3 + shortHex4).toLowerCase();
    }

    /**
     * 使用 CryptoJS 计算哈希（备用方案）
     * @param {ArrayBuffer} buffer
     * @returns {string} 40字符的SHA-1哈希值
     * @private
     */
    const _cryptoJSHash = function (buffer) {
        if (typeof CryptoJS !== 'undefined' && CryptoJS.SHA1) {
            try {
                const wordArray = CryptoJS.lib.WordArray.create(buffer);
                const hash = CryptoJS.SHA1(wordArray).toString();
                // CryptoJS的SHA1默认返回40字符的十六进制字符串
                return hash.toLowerCase();
            } catch (error) {
                Logger.warn('CryptoJS哈希计算失败:', error);
                return null;
            }
        }
        return null;
    }

    /**
     * 流式计算文件哈希（适用于大文件）
     * @param {File} file - 文件对象
     * @param {number} chunkSize - 分块大小，默认1MB
     * @returns {Promise<string>} 文件哈希值
     * @private
     */
    const _computeFileHashStream = function (file, chunkSize = 1024 * 1024) {
        return new Promise((resolve, reject) => {
            const isSecure = _isSecureContext();

            // 检查是否支持流式哈希计算
            if (!_isCryptoAPISupported() || !isSecure) {
                // 回退到完整文件读取
                resolve(null);
                return;
            }

            let hasher = null;
            let currentChunk = 0;
            const totalChunks = Math.ceil(file.size / chunkSize);

            // 初始化哈希器
            crypto.subtle.digest('SHA-256', new ArrayBuffer(0))
                .then(() => {
                    // 开始分块处理
                    processChunk();
                })
                .catch(error => {
                    Logger.warn('初始化流式哈希失败:', error);
                    resolve(null);
                });

            function processChunk() {
                if (currentChunk >= totalChunks) {
                    // 所有块处理完成，但Web Crypto API不支持流式更新
                    // 这里实际上还是需要读取完整文件
                    resolve(null);
                    return;
                }

                const start = currentChunk * chunkSize;
                const end = Math.min(start + chunkSize, file.size);
                const chunk = file.slice(start, end);

                const reader = new FileReader();
                reader.onload = function(e) {
                    // 由于Web Crypto API限制，暂时回退
                    resolve(null);
                };
                reader.onerror = function() {
                    resolve(null);
                };

                reader.readAsArrayBuffer(chunk);
                currentChunk++;
            }
        });
    }

    /**
     * 采样哈希计算（适用于超大文件）
     * @param {File} file - 文件对象
     * @param {number} sampleSize - 采样大小，默认每个采样点1MB
     * @returns {Promise<string>} 采样哈希值
     * @private
     */
    const _computeSampleHash = function (file, sampleSize = 1024 * 1024) {
        return new Promise(async (resolve, reject) => {
            try {
                const samples = [];
                const fileSize = file.size;

                // 对于小文件，不使用采样
                if (fileSize <= sampleSize * 3) {
                    resolve(null);
                    return;
                }

                // 计算采样点
                const samplePoints = [
                    { start: 0, size: Math.min(sampleSize, fileSize) }, // 文件开头
                    { start: Math.floor(fileSize / 2) - Math.floor(sampleSize / 2), size: sampleSize }, // 文件中间
                    { start: Math.max(0, fileSize - sampleSize), size: Math.min(sampleSize, fileSize) } // 文件末尾
                ];

                // 读取采样数据
                for (const point of samplePoints) {
                    const chunk = file.slice(point.start, point.start + point.size);
                    const buffer = await readChunkAsArrayBuffer(chunk);
                    samples.push(buffer);
                }

                // 合并采样数据
                const totalSize = samples.reduce((sum, buffer) => sum + buffer.byteLength, 0);
                const combinedBuffer = new ArrayBuffer(totalSize);
                const combinedView = new Uint8Array(combinedBuffer);

                let offset = 0;
                for (const buffer of samples) {
                    combinedView.set(new Uint8Array(buffer), offset);
                    offset += buffer.byteLength;
                }

                                 // 计算合并后的哈希
                 const isSecure = _isSecureContext();

                 if (_isCryptoAPISupported() && isSecure) {
                     try {
                         const hashBuffer = await crypto.subtle.digest('SHA-1', combinedBuffer);
                         const hashArray = Array.from(new Uint8Array(hashBuffer));
                         const hash = hashArray.map(b => b.toString(16).padStart(2, '0')).join('');

                         // 返回标准长度的SHA-1哈希（40个字符）
                         resolve(hash);
                     } catch (error) {
                         Logger.warn('采样哈希计算失败:', error);
                         resolve(null);
                     }
                 } else if (typeof CryptoJS !== 'undefined' && CryptoJS.SHA1) {
                     // 使用CryptoJS计算采样哈希
                     try {
                         const wordArray = CryptoJS.lib.WordArray.create(combinedBuffer);
                         const hash = CryptoJS.SHA1(wordArray).toString();
                         resolve(hash);
                     } catch (error) {
                         Logger.warn('CryptoJS采样哈希计算失败:', error);
                         resolve(null);
                     }
                 } else {
                     // 使用简单哈希，但要确保长度一致
                     try {
                         const hash = _simpleHash(combinedBuffer);
                         // 将简单哈希扩展到40位，保持与SHA-1一致的长度
                         const paddedHash = _padHashTo40Chars(hash, file.name, file.size, file.lastModified);
                         resolve(paddedHash);
                     } catch (error) {
                         Logger.warn('简单采样哈希计算失败:', error);
                         resolve(null);
                     }
                 }

            } catch (error) {
                Logger.warn('采样哈希计算出错:', error);
                resolve(null);
            }
        });

        function readChunkAsArrayBuffer(chunk) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload = e => resolve(e.target.result);
                reader.onerror = () => reject(reader.error);
                reader.readAsArrayBuffer(chunk);
            });
        }
    }

    /**
     * 读取文件信息
     * @param file
     * @returns {Promise<unknown>}
     * @private
     */
    const _readFile = function (file) {
        return new Promise(async (resolve, reject) => {
            // 参数验证
            if (!file || !Dolphin.isFile(file)) {
                reject('无效的文件对象');
                return;
            }

            try {
                let fileHash = null;
                let hashMethod = 'unknown';
                const isSecure = _isSecureContext();
                const fileSize = file.size || 0;

                // 大文件阈值：50MB
                const LARGE_FILE_THRESHOLD = 50 * 1024 * 1024;

                // 超大文件阈值：200MB
                const HUGE_FILE_THRESHOLD = 200 * 1024 * 1024;

                // 对于超大文件，优先使用采样哈希（快速处理）
                if (fileSize > HUGE_FILE_THRESHOLD) {
                    Logger.info(`文件 ${file.name} 大小为 ${Dolphin.formatSize(fileSize)}，使用采样哈希计算`);

                    try {
                        fileHash = await _computeSampleHash(file);
                        if (fileHash) {
                            hashMethod = 'SampleHash';
                        }
                    } catch (error) {
                        Logger.warn('采样哈希计算失败，回退到完整文件哈希:', error);
                    }
                }

                // 如果采样哈希失败或不适用，使用完整文件哈希计算
                if (!fileHash) {
                    // 对于大文件，显示提示信息
                    if (fileSize > LARGE_FILE_THRESHOLD) {
                        Logger.info(`文件 ${file.name} 大小为 ${Dolphin.formatSize(fileSize)}，正在计算完整文件哈希，请稍等...`);
                    }

                    // 使用完整文件读取方式
                    const reader = new FileReader();
                    reader.readAsArrayBuffer(file);

                    reader.onload = async function () {
                        try {
                            // 方案1: Web Crypto API（最佳方案，需要安全上下文）
                            if (_isCryptoAPISupported() && isSecure) {
                                try {
                                    const hashBuffer = await crypto.subtle.digest('SHA-1', reader.result);
                                    const hashArray = Array.from(new Uint8Array(hashBuffer));
                                    fileHash = hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
                                    hashMethod = 'WebCrypto-SHA1';
                                } catch (cryptoError) {
                                    Logger.warn('Web Crypto API 在安全上下文中失败，尝试备用方案:', cryptoError.message);
                                    fileHash = null; // 确保继续到下一个方案
                                }
                            }

                            // 方案2: CryptoJS（兼容性好，但需要额外库）
                            if (!fileHash && typeof CryptoJS !== 'undefined' && CryptoJS.SHA1) {
                                try {
                                    fileHash = _cryptoJSHash(reader.result);
                                    if (fileHash) {
                                        hashMethod = 'CryptoJS-SHA1';
                                    }
                                } catch (cryptoJSError) {
                                    Logger.warn('CryptoJS 计算失败，使用简单哈希:', cryptoJSError.message);
                                    fileHash = null;
                                }
                            }

                            // 方案3: 非安全上下文下的 Web Crypto API 尝试
                            if (!fileHash && _isCryptoAPISupported()) {
                                try {
                                    const hashBuffer = await crypto.subtle.digest('SHA-1', reader.result);
                                    const hashArray = Array.from(new Uint8Array(hashBuffer));
                                    fileHash = hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
                                    hashMethod = 'WebCrypto-SHA1-Insecure';
                                } catch (cryptoError) {
                                    Logger.warn('非安全上下文 Web Crypto API 失败，使用简单哈希:', cryptoError.message);
                                    fileHash = null;
                                }
                            }

                            // 方案4: 简单哈希算法（最后的备用方案）
                            if (!fileHash) {
                                try {
                                    const simpleHash = _simpleHash(reader.result);
                                    // 确保简单哈希也是40字符
                                    fileHash = _padHashTo40Chars(simpleHash, file.name, file.size, file.lastModified);
                                    hashMethod = 'SimpleHash';
                                } catch (simpleHashError) {
                                    Logger.error('简单哈希计算也失败:', simpleHashError);
                                    throw new Error('所有哈希计算方法都失败');
                                }
                            }

                            // 完成文件信息构建
                            finishFileInfo();

                        } catch (error) {
                            const errorMessage = '计算文件哈希失败：' + (error.message || '未知错误');
                            Logger.error(errorMessage, error);
                            reject(errorMessage);
                        }
                    };

                    reader.onerror = function () {
                        const errorMessage = '读取文件失败：' + (reader.error?.message || '文件读取错误');
                        Logger.error(errorMessage);
                        reject(errorMessage);
                    };

                    // 设置超时处理（防止大文件长时间无响应）
                    const timeoutDuration = fileSize > LARGE_FILE_THRESHOLD ? 120000 : 30000; // 大文件2分钟，小文件30秒
                    setTimeout(() => {
                        if (reader.readyState === FileReader.LOADING) {
                            reader.abort();
                            reject('文件读取超时');
                        }
                    }, timeoutDuration);

                } else {
                    // 采样哈希成功，直接完成
                    finishFileInfo();
                }

                function finishFileInfo() {
                    try {
                        // 安全性检查：确保哈希不为空且长度正确
                        if (!fileHash || fileHash.length === 0) {
                            throw new Error('计算的文件哈希为空');
                        }

                        // 确保所有哈希值都是40字符（SHA-1标准长度）
                        if (fileHash.length !== 40) {
                            Logger.warn(`哈希长度不标准 (${fileHash.length}字符)，使用方法: ${hashMethod}`);
                            // 如果不是标准长度，进行标准化处理
                            if (hashMethod === 'SimpleHash' || fileHash.length < 40) {
                                fileHash = _padHashTo40Chars(fileHash, file.name, file.size, file.lastModified);
                            } else if (fileHash.length > 40) {
                                // 如果超过40字符，截取前40字符
                                fileHash = fileHash.substring(0, 40);
                            }
                        }

                        // 构建文件信息对象
                        let fileInfo = {
                            'name': file.name || '',
                            'hash': fileHash,
                            'size': file.size || 0,
                            'mine': file.type || '',
                            'ext': '',
                            'hashMethod': hashMethod, // 添加哈希方法标识
                            'isSecureContext': isSecure, // 添加安全上下文标识
                            'isLargeFile': fileSize > LARGE_FILE_THRESHOLD, // 添加大文件标识
                            'isHugeFile': fileSize > HUGE_FILE_THRESHOLD // 添加超大文件标识
                        };

                        // 安全地获取文件扩展名
                        try {
                            if (Dolphin.isFile(file) && file.name) {
                                const nameParts = file.name.split('.');
                                fileInfo.ext = nameParts.length > 1 ? nameParts.pop().toLowerCase() : '';
                            } else if (file.type) {
                                const typeParts = file.type.split('/');
                                fileInfo.ext = typeParts.length > 1 ? typeParts.pop().toLowerCase() : '';
                            }
                        } catch (extError) {
                            Logger.warn('获取文件扩展名失败:', extError.message);
                            fileInfo.ext = '';
                        }

                        resolve(fileInfo);

                    } catch (error) {
                        const errorMessage = '构建文件信息失败：' + (error.message || '未知错误');
                        Logger.error(errorMessage, error);
                        reject(errorMessage);
                    }
                }

            } catch (e) {
                const errorMessage = '初始化文件读取失败：' + (e.message || '未知错误');
                Logger.error(errorMessage, e);
                reject(errorMessage);
            }
        });
    }

    /**
     * 判断是否为文件
     * @param obj
     * @returns {boolean}
     * @private
     */
    const _isFile = function (obj) {
        // 快速null/undefined检查
        if (obj == null) {
            return false;
        }

        // 基本的instanceof检查（适用于大多数情况）
        if (obj instanceof File) {
            return true;
        }

        // 兼容性检查：处理跨框架/iframe的情况
        // 检查对象是否具有File对象的特征
        if (typeof obj === 'object' && obj !== null) {
            // 检查File对象的关键属性和方法
            const hasFileProperties = (
                typeof obj.name === 'string' &&
                typeof obj.size === 'number' &&
                typeof obj.type === 'string' &&
                (typeof obj.lastModified === 'number' || typeof obj.lastModifiedDate !== 'undefined')
            );

            // 检查File对象的关键方法
            const hasFileMethods = (
                typeof obj.slice === 'function' &&
                typeof obj.stream === 'function'
            );

            // 检查构造函数名称（兼容性检查）
            const hasFileConstructor = (
                obj.constructor &&
                obj.constructor.name === 'File'
            );

            // 检查toString方法返回值
            const hasFileToString = (
                typeof obj.toString === 'function' &&
                obj.toString() === '[object File]'
            );

            // 综合判断：如果具有File的主要特征，则认为是File对象
            if (hasFileProperties && (hasFileMethods || hasFileConstructor || hasFileToString)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 保存文件信息
     * @param params
     * @returns {Promise<unknown>}
     * @private
     */
    const _saveFile = function (params) {
        return new Promise((resolve, reject) => {
            params['_ajax'] = 1;
            jQuery.post(DolphinConfig.url.saveFile, params, function (res) {
                resolve(res);
            }).fail(function (res) {
                reject(res)
            });
        });
    }

    /**
     * 检查文件是已上传
     * @param file
     * @param options 可选配置参数
     * @returns {Promise<unknown>}
     * @private
     */
    const _checkFileUpload = async function (file, options = {}) {
        // 参数验证
        if (!file || !Dolphin.isFile(file)) {
            throw new Error('无效的文件对象');
        }

        // 默认配置
        const defaultOptions = {
            from: 'image',  // 可配置的来源标识
            ajax: 1         // Ajax标识
        };

        const config = { ...defaultOptions, ...options };

        try {
            // 读取文件信息
            const fileInfo = await Dolphin.readFile(file);

            if (!fileInfo || !fileInfo.hash) {
                throw new Error('无法获取文件哈希值');
            }

            // 构建请求参数
            const params = {
                hash: fileInfo.hash,
                '_from': config.from,
                '_ajax': config.ajax
            };

            // 发送检查请求
            return new Promise((resolve, reject) => {
                $.get(DolphinConfig.url.checkFile, params, function (res) {
                    // 成功回调
                    resolve({ res, fileInfo });
                }).fail(function (xhr, status, error) {
                    // 失败回调 - 更详细的错误处理
                    let errorMessage = '文件检查失败';

                    if (xhr && xhr.responseJSON && xhr.responseJSON.msg) {
                        errorMessage = xhr.responseJSON.msg;
                    } else if (xhr && xhr.responseText) {
                        try {
                            // 尝试从HTML响应中提取错误信息
                            const $html = $(xhr.responseText);
                            const h1Text = $html.find('h1:first').text().trim();
                            if (h1Text) {
                                errorMessage = h1Text;
                            }
                        } catch (parseError) {
                            // 如果解析HTML失败，使用原始响应文本
                            if (xhr.responseText.length < 200) {
                                errorMessage = xhr.responseText;
                            }
                        }
                    } else if (error) {
                        errorMessage = error;
                    } else if (status) {
                        errorMessage = `请求失败: ${status}`;
                    }

                    // 添加上下文信息
                    const contextError = new Error(errorMessage);
                    contextError.xhr = xhr;
                    contextError.status = status;
                    contextError.originalError = error;
                    contextError.fileInfo = fileInfo;

                    reject(contextError);
                });
            });

        } catch (error) {
            // 包装文件读取错误
            const wrappedError = new Error(`文件检查失败: ${error.message || '未知错误'}`);
            wrappedError.originalError = error;
            wrappedError.file = {
                name: file.name,
                size: file.size,
                type: file.type
            };
            throw wrappedError;
        }
    }

    /**
     * 格式化文件大小
     * @param size
     * @param delimiter
     * @returns {string}
     * @private
     */
    const _formatSize = function (size, delimiter = '') {
        const units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        let i = 0;
        while (size >= 1024 && i < 5) {
            size /= 1024;
            i++;
        }
        return Math.round(size * 100) / 100 + delimiter + units[i];
    }

    /**
     * 上传驱动管理器
     * @param {string} name - 驱动名称.
     * @param {object} [options] - 驱动配置对象. 如果提供，则为注册驱动；否则为获取驱动.
     * @returns {object|undefined}
     * @private
     */
    const _uploader = function (name, options) {
        // 参数验证
        if (!name) {
            Dolphin.error('驱动名称不能为空！');
            return;
        }

        // 注册驱动
        if (options) {
            if (typeof options !== 'object') {
                Dolphin.error(`上传驱动 ${name} 的配置必须是一个对象!`);
                return;
            }
            uploaderManager.register(name, options);
            return;
        }

        // 获取驱动
        const driver = uploaderManager.get(name);
        if (!driver) {
            Dolphin.error(`上传驱动: ${name}, 不存在!`);
            return;
        }
        return driver;
    }

    /**
     * 附件浏览器
     * @param {string|jQuery} target 触发元素
     * @param {Object|Function} options 配置选项或回调函数
     * @param {Function} callback 选择回调函数
     * @private
     */
    const _browser = function (target, options, callback) {
        // 参数标准化和验证
        const config = _normalizeBrowserParams(options, callback);
        const browserId = 'dp-browser-' + _randStr();

        // 创建浏览器实例
        const browserInstance = new DolphinBrowserModal(browserId, config);

        // 绑定触发事件
        $(target).off('click.dolphin-browser').on('click.dolphin-browser', function(e) {
            e.preventDefault();
            browserInstance.show();
        });

        return browserInstance;
    };

    /**
     * 标准化浏览器参数
     * @private
     */
    function _normalizeBrowserParams(options, callback) {
        const defaults = {
            title: '附件浏览器',
            url: DolphinConfig.url.getFiles,
            multiple: false,
            type: '', // image, video, audio, file
            maxSelection: 0, // 0表示无限制
            allowedTypes: [], // 允许的文件类型
            onSelect: null,
            onCancel: null,
            debug: false
        };

        // 处理参数重载
        if (typeof options === 'function') {
            return { ...defaults, onSelect: options };
        }

        if (typeof options === 'object' && options !== null) {
            const config = { ...defaults, ...options };
            if (callback && typeof callback === 'function') {
                config.onSelect = callback;
            }
            return config;
        }

        return { ...defaults, onSelect: callback };
    }

    /**
     * 附件浏览器模态框类
     * @private
     */
    class DolphinBrowserModal {
        constructor(id, config) {
            this.id = id;
            this.config = config;
            this.selectedData = {};
            this.currentPage = 1;
            this.isLoading = false;
            this.isDestroyed = false;
            this.searchKeyword = '';
            this.searchDebounceTimer = null;
            this.searchDebounceDelay = this.config.searchDebounce || 500;

            // DOM元素引用
            this.$modal = null;
            this.$browser = null;
            this.$pagination = null;
            this.$confirmBtn = null;
            this.$searchInput = null;
            this.$searchClearBtn = null;

            this._init();
        }

        /**
         * 初始化浏览器
         * @private
         */
        _init() {
            this._createModal();
            this._bindEvents();
            this._setupAccessibility();
        }

        /**
         * 创建模态框DOM
         * @private
         */
        _createModal() {
            const modalElement = this._createModalElement();
            $('body').append(modalElement);

            // 缓存DOM引用
            this.$modal = $('#' + this.id);
            this.$browser = this.$modal.find('.dp-js-browser');
            this.$pagination = this.$modal.find('.pagination');
            this.$confirmBtn = this.$modal.find('.dp-js-browser-confirm');
            this.$searchArea = this.$modal.find('.dp-browser-search');
            this.$searchInput = this.$modal.find('.dp-js-browser-search');
            this.$searchClearBtn = this.$modal.find('.dp-js-browser-search-clear');
            this.$toggleSearchBtn = this.$modal.find('#toggle-search');
        }

        /**
         * 创建模态框元素
         * @private
         */
        _createModalElement() {
            const modal = document.createElement('div');
            modal.className = 'modal modal-blur fade dp-browser-modal';
            modal.id = this.id;
            modal.setAttribute('tabindex', '-1');
            modal.setAttribute('role', 'dialog');
            modal.setAttribute('aria-labelledby', this.id + '-title');
            modal.setAttribute('aria-hidden', 'true');

            // 安全地设置innerHTML
            modal.innerHTML = this._getModalTemplate();

            return modal;
        }

        /**
         * 获取模态框HTML模板
         * @private
         */
        _getModalTemplate() {
            const titleId = this.id + '-title';
            const title = this._escapeHtml(this.config.title);
            let rowClass = 'row row-cols-4 row-cols-md-6 g-3';
            if (this.config.type === 'file') {
                rowClass = 'row g-2';
            }

            return `
                <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="${titleId}">${title}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="关闭浏览器"></button>
                        </div>
                        <div class="modal-body">
                            <div class="dp-browser-search mb-3">
                                <div class="input-group input-group-flat">
                                    <span class="input-group-text" id="${titleId}-search-label"><i class="ti ti-search"></i></span>
                                    <input type="search" class="form-control dp-js-browser-search" placeholder="输入附件名称进行搜索..." aria-label="搜索附件" aria-describedby="${titleId}-search-label">
                                    <span class="input-group-text">
                                        <a href="javascript:void(0);" class="link-secondary dp-js-browser-search-clear" data-bs-toggle="tooltip" aria-label="清除搜索" data-bs-original-title="清除搜索">
                                            <i class="ti ti-x"></i>
                                        </a>
                                    </span>
                                </div>
                            </div>
                            <div class="${rowClass} dp-js-browser" role="grid" aria-label="附件列表">
                                <div class="empty"><i class="fas fa-ban"></i> 暂无数据</div>
                            </div>
                        </div>
                        <div class="modal-footer pagination">
                            <button type="button" class="btn me-auto" data-bs-dismiss="modal"><i class="ti ti-x"></i> <span class="d-none d-sm-inline">关闭</span></button>
                            <button type="button" class="btn" id="toggle-search"><i class="ti ti-search d-none d-sm-inline" style="margin-right: 5px;"></i><span class="d-none d-sm-inline">搜索</span><i class="ti ti-search d-sm-none"></i></button>
                            <button type="button" class="btn page-prev"><i class="ti ti-chevron-left d-none d-sm-inline" style="margin-right: 5px;"></i><span class="d-none d-sm-inline">上一页</span><i class="ti ti-chevron-left d-sm-none"></i></button>
                            <button type="button" class="btn page-next"><i class="ti ti-chevron-right d-sm-none"></i><span class="d-none d-sm-inline">下一页</span><i class="ti ti-chevron-right d-none d-sm-inline" style="margin-left: 5px;"></i></button>
                            <button type="button" class="btn btn-primary dp-js-browser-confirm">
                                <i class="dp-icon fas fa-check d-none d-sm-inline"></i>
                                <span class="d-none d-sm-inline">选择</span>
                                <i class="fas fa-check d-sm-none"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }

        /**
         * 绑定事件
         * @private
         */
        _bindEvents() {
            // 模态框事件
            this.$modal
                .on('show.bs.modal', () => this._onModalShow())
                .on('shown.bs.modal', () => this._onModalShown())
                .on('hidden.bs.modal', () => this._onModalHidden())
                .on('hide.bs.modal', () => this._onModalHide());

            // 文件选择事件（使用事件委托）
            this.$browser.on('click', '.img-responsive', (e) => this._onFileSelect(e));

            if (this.$searchInput && this.$searchInput.length) {
                this.$searchInput.on('input', (e) => this._onSearchInput(e));
            }

            this.$toggleSearchBtn.on('click', () => this.$searchArea.toggleClass('active'));

            if (this.$searchClearBtn && this.$searchClearBtn.length) {
                this.$searchClearBtn.on('click', () => this._onSearchClear());
            }

            // 确认按钮事件
            this.$confirmBtn.on('click', () => this._onConfirm());

            // 分页事件
            this.$pagination.on('click', '.page-prev:not(.disabled)', () => this._previousPage());
            this.$pagination.on('click', '.page-next:not(.disabled)', () => this._nextPage());

            // 键盘导航支持
            this.$modal.on('keydown', (e) => this._onKeyDown(e));
        }

        /**
         * 设置无障碍访问
         * @private
         */
        _setupAccessibility() {
            // 在模态框显示时处理焦点
            this.$modal.on('shown.bs.modal', () => {
                // 移除aria-hidden并设置aria-modal
                this.$modal.removeAttr('aria-hidden').attr('aria-modal', 'true');

                // 设置焦点到第一个可交互元素
                const firstFocusable = this.$modal.find('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])').first();
                if (firstFocusable.length) {
                    firstFocusable.focus();
                }
            });

            // 焦点陷阱 - 确保焦点不会离开模态框
            this.$modal.on('keydown', (e) => {
                if (e.key === 'Tab') {
                    this._trapFocus(e);
                }
            });
        }

        /**
         * 焦点陷阱实现
         * @private
         */
        _trapFocus(e) {
            const focusableElements = this.$modal.find(
                'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
            ).filter(':visible');

            const firstElement = focusableElements.first();
            const lastElement = focusableElements.last();

            if (e.shiftKey && document.activeElement === firstElement[0]) {
                e.preventDefault();
                lastElement.focus();
            } else if (!e.shiftKey && document.activeElement === lastElement[0]) {
                e.preventDefault();
                firstElement.focus();
            }
        }

        /**
         * 键盘导航处理
         * @private
         */
        _onKeyDown(e) {
            switch (e.key) {
                case 'Escape':
                    this.hide();
                    break;
                case 'Enter':
                    if (e.target.classList.contains('img-responsive')) {
                        this._onFileSelect({ target: e.target });
                    }
                    break;
                case 'ArrowLeft':
                case 'ArrowRight':
                case 'ArrowUp':
                case 'ArrowDown':
                    this._handleArrowNavigation(e);
                    break;
            }
        }

        /**
         * 箭头键导航
         * @private
         */
        _handleArrowNavigation(e) {
            const items = this.$browser.find('.img-responsive');
            const currentIndex = items.index(document.activeElement);

            if (currentIndex === -1) return;

            let nextIndex;
            const itemsPerRow = 6; // 根据CSS类名 row-cols-md-6

            switch (e.key) {
                case 'ArrowLeft':
                    nextIndex = Math.max(0, currentIndex - 1);
                    break;
                case 'ArrowRight':
                    nextIndex = Math.min(items.length - 1, currentIndex + 1);
                    break;
                case 'ArrowUp':
                    nextIndex = Math.max(0, currentIndex - itemsPerRow);
                    break;
                case 'ArrowDown':
                    nextIndex = Math.min(items.length - 1, currentIndex + itemsPerRow);
                    break;
            }

            if (nextIndex !== undefined && items[nextIndex]) {
                e.preventDefault();
                items[nextIndex].focus();
            }
        }

        /**
         * 模态框显示前事件
         * @private
         */
        _onModalShow() {
            this._resetState();
        }

        /**
         * 模态框显示后事件
         * @private
         */
        _onModalShown() {
            this._loadAttachments();
        }

        /**
         * 模态框隐藏前事件
         * @private
         */
        _onModalHide() {
            // 🆕 立即移除aria-modal，但不立即设置aria-hidden
            this.$modal.removeAttr('aria-modal');

            // 🆕 将焦点转移到body，避免焦点留在即将隐藏的元素上
            const activeElement = document.activeElement;
            if (activeElement && this.$modal[0].contains(activeElement)) {
                // 如果当前焦点在模态框内，转移焦点到body
                activeElement.blur();
                document.body.focus();
            }

            if (this.config.onCancel && typeof this.config.onCancel === 'function') {
                this.config.onCancel();
            }
        }

        /**
         * 模态框隐藏后事件
         * @private
         */
        _onModalHidden() {
            // 🆕 延迟设置aria-hidden，确保焦点已经完全转移且Bootstrap动画完成
            setTimeout(() => {
                // 再次检查焦点是否还在模态框内
                const activeElement = document.activeElement;
                if (!activeElement || !this.$modal[0].contains(activeElement)) {
                    // 焦点已经不在模态框内，安全设置 aria-hidden
                    this.$modal.attr('aria-hidden', 'true');
                } else {
                    // 焦点仍在模态框内，强制转移后再设置
                    activeElement.blur();
                    document.body.focus();
                    setTimeout(() => {
                        this.$modal.attr('aria-hidden', 'true');
                    }, 50);
                }
            }, 150); // 延迟150ms确保Bootstrap动画和处理完成
        }

        /**
         * 重置状态
         * @private
         */
        _resetState() {
            this.selectedData = {};
            this.currentPage = 1;
            this.searchKeyword = '';
            if (this.$searchInput && this.$searchInput.length) {
                this.$searchInput.val('');
            }
            if (this.$searchClearBtn && this.$searchClearBtn.length) {
                this.$searchClearBtn.prop('disabled', false);
            }
            this._showEmptyState();
            this._updateConfirmButton();
        }

        /**
         * 显示空状态
         * @private
         */
        _showEmptyState() {
            this.$browser.html('<div class="empty" role="status" aria-live="polite"><i class="fas fa-ban"></i> 暂无数据</div>');
        }

        /**
         * 加载附件数据
         * @private
         */
        async _loadAttachments() {
            if (this.isLoading || this.isDestroyed) return;

            this.clearSelection();

            this.isLoading = true;

            try {
                Dolphin.loading();

                const response = await $.get(this.config.url, {
                    page: this.currentPage,
                    type: this.config.type,
                    keyword: this.searchKeyword
                });

                if (response.code) {
                    this._renderAttachments(response.data);
                    this._updatePagination(response.data);
                } else {
                    throw new Error(response.msg || '加载失败');
                }

            } catch (error) {
                this._handleError('加载附件失败', error);
            } finally {
                this.isLoading = false;
                Dolphin.loading('hide');
            }
        }

        _onSearchInput(e) {
            if (this.isDestroyed) {
                return;
            }

            const value = (e.target.value || '').trim();
            this.searchKeyword = value;
            this.currentPage = 1;

            if (this.$searchClearBtn && this.$searchClearBtn.length) {
                this.$searchClearBtn.prop('disabled', value.length === 0);
            }

            if (this.searchDebounceTimer) {
                clearTimeout(this.searchDebounceTimer);
            }

            this.searchDebounceTimer = setTimeout(() => {
                if (!this.isDestroyed) {
                    this._loadAttachments();
                }
            }, this.searchDebounceDelay);
        }

        _onSearchClear() {
            if (this.isDestroyed) {
                return;
            }

            this.searchKeyword = '';
            this.currentPage = 1;

            if (this.$searchInput && this.$searchInput.length) {
                this.$searchInput.val('');
            }

            if (this.searchDebounceTimer) {
                clearTimeout(this.searchDebounceTimer);
                this.searchDebounceTimer = null;
            }

            this._loadAttachments();
        }

        /**
         * 渲染附件列表
         * @private
         */
        _renderAttachments(data) {
            if (!data.data || !Array.isArray(data.data) || data.data.length === 0) {
                this._showEmptyState();
                return;
            }

            const fragment = document.createDocumentFragment();

            data.data.forEach((item, index) => {
                const col = this._createAttachmentItem(item, index);
                fragment.appendChild(col);
            });

            this.$browser.empty().append(fragment);

            // 设置ARIA属性
            this.$browser.attr('aria-rowcount', Math.ceil(data.data.length / 6));
        }

        /**
         * 创建附件项元素
         * @private
         */
        _createAttachmentItem(item, index) {
            const col = document.createElement('div');
            col.className = 'col';

            const responsive = document.createElement('div');

            if (this.config.type === 'file') {
                col.className = 'col-12';
                responsive.className = 'img-responsive dp-browser-file rounded border';
                responsive.setAttribute('role', 'gridcell');
                responsive.setAttribute('tabindex', '0');
                responsive.setAttribute('aria-label', `附件: ${item.name}`);
                responsive.setAttribute('title', `${item.name}`);
                responsive.setAttribute('data-info', JSON.stringify(item));

                const icon = document.createElement('i');
                icon.className = 'fas fa-file';
                responsive.appendChild(icon);

                const titleSpan = document.createElement('span');
                titleSpan.innerText = item.name + ' (' + Dolphin.formatSize(item.size) +')';
                responsive.appendChild(titleSpan);
            } else {
                responsive.className = 'img-responsive img-responsive-1x1 rounded border';
                responsive.setAttribute('role', 'gridcell');
                responsive.setAttribute('tabindex', '0');
                responsive.setAttribute('aria-label', `附件: ${item.name}`);
                responsive.setAttribute('title', `${item.name}`);
                responsive.setAttribute('data-info', JSON.stringify(item));
                // 安全设置背景图
                if (item.preview) {
                    responsive.style.backgroundImage = `url("${this._escapeUrl(item.preview)}")`;
                }
            }

            col.appendChild(responsive);
            return col;
        }

        /**
         * 更新分页状态
         * @private
         */
        _updatePagination(data) {
            const $prev = this.$pagination.find('.page-prev');
            const $next = this.$pagination.find('.page-next');

            // 重置状态
            $prev.removeClass('disabled').find('a').attr('tabindex', '0').attr('aria-disabled', 'false');
            $next.removeClass('disabled').find('a').attr('tabindex', '0').attr('aria-disabled', 'false');

            // 设置禁用状态
            if (data.current_page <= 1) {
                $prev.addClass('disabled').find('a').attr('tabindex', '-1').attr('aria-disabled', 'true');
            }

            if (data.current_page >= data.last_page) {
                $next.addClass('disabled').find('a').attr('tabindex', '-1').attr('aria-disabled', 'true');
            }
        }

        /**
         * 文件选择处理
         * @private
         */
        _onFileSelect(e) {
            const $item = $(e.currentTarget);
            const itemData = $item.data('info');

            if (this.config.multiple) {
                this._handleMultipleSelection($item, itemData);
            } else {
                this._handleSingleSelection($item, itemData);
            }

            this._updateConfirmButton();
            this._announceSelection(itemData, $item.hasClass('active'));
        }

        /**
         * 多选处理
         * @private
         */
        _handleMultipleSelection($item, itemData) {
            if ($item.hasClass('active')) {
                $item.removeClass('active').attr('aria-selected', 'false');
                delete this.selectedData[itemData.id];
            } else {
                // 检查最大选择数量
                if (this.config.maxSelection > 0 && Object.keys(this.selectedData).length >= this.config.maxSelection) {
                    Dolphin.notify(`最多只能选择 ${this.config.maxSelection} 个文件`, 'warning');
                    return;
                }

                $item.addClass('active').attr('aria-selected', 'true');
                this.selectedData[itemData.id] = itemData;
            }
        }

        /**
         * 单选处理
         * @private
         */
        _handleSingleSelection($item, itemData) {
            // 清除其他选择
            this.$browser.find('.img-responsive').removeClass('active').attr('aria-selected', 'false');

            // 选择当前项
            $item.addClass('active').attr('aria-selected', 'true');
            this.selectedData = itemData;
        }

        /**
         * 宣布选择状态（无障碍）
         * @private
         */
        _announceSelection(itemData, isSelected) {
            const message = isSelected ? `已选择 ${itemData.name}` : `取消选择 ${itemData.name}`;
            this._announceToScreenReader(message);
        }

        /**
         * 向屏幕阅读器宣布消息
         * @private
         */
        _announceToScreenReader(message) {
            const announcement = document.createElement('div');
            announcement.setAttribute('aria-live', 'polite');
            announcement.setAttribute('aria-atomic', 'true');
            announcement.className = 'sr-only';
            announcement.textContent = message;

            document.body.appendChild(announcement);
            setTimeout(() => document.body.removeChild(announcement), 1000);
        }

        /**
         * 更新确认按钮状态
         * @private
         */
        _updateConfirmButton() {
            const hasSelection = this.config.multiple ?
                Object.keys(this.selectedData).length > 0 :
                this.selectedData && this.selectedData.id;

            this.$confirmBtn.prop('disabled', !hasSelection);

            if (hasSelection) {
                const count = this.config.multiple ? Object.keys(this.selectedData).length : 1;
                this.$confirmBtn.attr('aria-label', `确认选择 ${count} 个文件`);
            } else {
                this.$confirmBtn.attr('aria-label', '请先选择文件');
            }
        }

        /**
         * 确认选择
         * @private
         */
        _onConfirm() {
            const hasSelection = this.config.multiple ?
                Object.keys(this.selectedData).length > 0 :
                this.selectedData && this.selectedData.id;

            if (!hasSelection) {
                Dolphin.error('请至少选择一个项目');
                return;
            }

            if (this.config.onSelect && typeof this.config.onSelect === 'function') {
                this.config.onSelect(this.selectedData);
            }

            this.hide();
        }

        /**
         * 上一页
         * @private
         */
        _previousPage() {
            if (this.currentPage > 1 && !this.isLoading) {
                this.currentPage--;
                this._loadAttachments();
            }
        }

        /**
         * 下一页
         * @private
         */
        _nextPage() {
            if (!this.isLoading) {
                this.currentPage++;
                this._loadAttachments();
            }
        }

        /**
         * 错误处理
         * @private
         */
        _handleError(message, error) {
            if (this.config.debug) {
                Logger.error(`[DolphinBrowser] ${message}:`, error);
            }

            const errorMsg = error && error.message ? error.message : '未知错误';
            Dolphin.error(`${message}: ${errorMsg}`);

            this._showEmptyState();
        }

        /**
         * HTML转义
         * @private
         */
        _escapeHtml(str) {
            if (typeof str !== 'string') return '';

            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        /**
         * URL转义
         * @private
         */
        _escapeUrl(url) {
            if (typeof url !== 'string') return '';
            return url.replace(/["']/g, '');
        }

        // 公共API方法

        /**
         * 显示浏览器
         */
        show() {
            if (this.isDestroyed) {
                Logger.warn('[DolphinBrowser] Cannot show destroyed browser instance');
                return;
            }

            this.$modal.modal('show');
        }

        /**
         * 隐藏浏览器
         */
        hide() {
            if (this.isDestroyed) return;
            this.$modal.modal('hide');
        }

        /**
         * 销毁浏览器实例
         */
        destroy() {
            if (this.isDestroyed) return;

            this.isDestroyed = true;

            // 移除事件监听器
            this.$modal.off();
            this.$browser.off();
            this.$confirmBtn.off();
            this.$pagination.off();
            if (this.$searchInput && this.$searchInput.length) {
                this.$searchInput.off();
            }
            if (this.$searchClearBtn && this.$searchClearBtn.length) {
                this.$searchClearBtn.off();
            }

            if (this.searchDebounceTimer) {
                clearTimeout(this.searchDebounceTimer);
                this.searchDebounceTimer = null;
            }

            // 移除DOM
            this.$modal.remove();

            // 清理引用
            this.$modal = null;
            this.$browser = null;
            this.$pagination = null;
            this.$confirmBtn = null;
            this.$searchInput = null;
            this.$searchClearBtn = null;
            this.selectedData = null;
        }

        /**
         * 获取当前选择
         */
        getSelection() {
            return this.selectedData;
        }

        /**
         * 清除选择
         */
        clearSelection() {
            this.selectedData = this.config.multiple ? {} : null;
            this.$browser.find('.img-responsive').removeClass('active').attr('aria-selected', 'false');
            this._updateConfirmButton();
        }
    }

    /**
     * 跳转url
     * @param url
     * @param params
     * @private
     */
    const _jumpUrl = function (url, params) {
        if (params) {
            url += '?' + $.param(params);
        }
        window.location.href = url;
    }

    /**
     * layui弹窗
     * @param url
     * @param title
     * @param options
     * @private
     */
    const _pop = function (url, title, options = {}) {
        let _options = {
            title: title,
            content: url
        };
        $.extend(_options, DolphinConfig.dialog, options);

        const callbacks = ['success', 'yes', 'cancel', 'beforeEnd', 'end', 'moveEnd', 'resizing', 'full', 'min', 'restore'];
        callbacks.forEach(callback => {
            const callbackVal = _options[callback];
            if (!callbackVal) return;

            if (typeof callbackVal === 'string') {
                const globalFunc = window[callbackVal?.trim()];
                _options[callback] = typeof globalFunc === 'function' ? globalFunc : void delete _options[callback];
            }
        });

        layer.open(_options);
    }

    return {
        init: function () {
            _initAjaxPost();
            _initAjaxGet();
            _initAjaxFormSubmit();
            _initPopup();
            _initTabs();
        },
        loading: function ($msg) {
            _loading($msg);
        },
        notify: function ($msg, $type, $options) {
            _notify($msg, $type, $options);
        },
        toast: function (msg, type, timer, position) {
            _toast(msg, type, timer, position);
        },
        info: function (msg, timer = 3000) {
            _toast(msg, 'info', timer);
        },
        warning: function (msg, timer = 3000) {
            _toast(msg, 'warning', timer);
        },
        error: function (msg, timer = 3000) {
            _toast(msg, 'error', timer);
        },
        success: function (msg, timer = 2000) {
            _toast(msg, 'success', timer);
        },
        updateAdminCurrentUserSummary: function (summary) {
            return _updateAdminCurrentUserSummary(summary);
        },
        openAdminMenuItem: function (item) {
            const sidebarNavigationManager = window.DolphinSidebarNavigationManager;

            if (sidebarNavigationManager && typeof sidebarNavigationManager.openMenuSearchItem === 'function') {
                return sidebarNavigationManager.openMenuSearchItem(item);
            }

            return false;
        },
        confirm: function (msg, text, callback, options) {
            _confirm(msg, text, callback, options);
        },
        fail: function (res, timer = 5000) {
            let msg;
            if (res['responseJSON'] !== undefined) {
                msg = res['responseJSON']['msg'];
            } else {
                msg = $(res.responseText).find('h1').text() || '服务器内部错误~';
            }
            _toast(msg, 'error', timer);
        },
        refreshToken: function (name) {
            _refreshToken(name);
        },
        randStr: function (length = 8, type = 0) {
            return _randStr(length, type);
        },
        encrypt: function (data, key, iv) {
            return _encrypt(data, key, iv);
        },
        decrypt: function (data, key, iv) {
            return _decrypt(data, key, iv);
        },
        loadImage: function (url) {
            return _loadImage(url);
        },
        thumbImage: function (url, options) {
            return _thumbImage(url, options);
        },
        readFile: function (file) {
            return _readFile(file);
        },
        isFile: function (obj) {
            return _isFile(obj);
        },
        saveFile: function (params) {
            return _saveFile(params);
        },
        checkFileUpload: function (file, options = {}) {
            return _checkFileUpload(file, options);
        },
        formatSize: function (size, delimiter) {
            return _formatSize(size, delimiter);
        },
        uploader: _uploaderAPI,
        browser: function (ele, options, callback) {
            return _browser(ele, options, callback);
        },
        jumpUrl: function (url, params) {
            _jumpUrl(url, params);
        },
        pop: function (url, title, options = {}) {
            _pop(url, title, options);
        },
        modal: function (options = {}) {
            return _modal(options);
        },
        modalSuccess: function (title, content = '', buttonText = '确定') {
            return _modalAlert(title, content, 'success', 'sm', buttonText);
        },
        modalError: function (title, content = '', buttonText = '知道了') {
            return _modalAlert(title, content, 'danger', 'sm', buttonText);
        },
        modalWarning: function (title, content = '', buttonText = '我知道了') {
            return _modalAlert(title, content, 'warning', 'sm', buttonText);
        },
        modalInfo: function (title, content = '', buttonText = '确定') {
            return _modalAlert(title, content, 'info', 'sm', buttonText);
        },
        modalAlert: function (title, content, status = 'info', size = 'sm', buttonText = '') {
            return _modalAlert(title, content, status, size, buttonText);
        },
        modalConfirm: function (title, content, onConfirm, confirmText = '确认', cancelText = '取消', status = 'warning') {
            return _modalConfirm(title, content, onConfirm, confirmText, cancelText, status);
        },
        modalDetail: function (title, content, size = 'lg', footer = '', scrollable = false) {
            const defaultFooter = '<button type="button" class="btn" data-bs-dismiss="modal">关闭</button>';

            return _modal({
                title: title,
                size: size,
                scrollable: scrollable,
                content: content,
                footer: footer || defaultFooter
            });
        },
        ajax: function (url, type, data, options = {}) {
            return _ajax(url, type, data, options);
        },
        // 获取iframe管理器
        getIframeManager: function() {
            return globalIframeManager;
        },
        // 获取标签管理器
        getTabManager: function() {
            if (globalTabsCore && typeof globalTabsCore.getTabsManager === 'function') {
                return globalTabsCore.getTabsManager();
            }
            return null;
        },
        // 获取收藏管理器
        getFavoriteManager: function() {
            return globalFavoriteManager;
        },
    }
}(jQuery);
window.Dolphin = Dolphin;

// 初始化后暴露管理器到全局作用域（用于调试）
jQuery(function () {
    Dolphin.init();

    const navigationData = (typeof DolphinConfig !== 'undefined' && DolphinConfig && DolphinConfig.navigation)
        ? DolphinConfig.navigation
        : null;
    const mobileAppSwitcherModal = document.getElementById('mobile-app-switcher-modal');

    const sidebarNavigationManager = (() => {
        if (!navigationData || !navigationData.sidebarMenusByApp) {
            return null;
        }

        const appMap = new Map();
        const switcherApps = Array.isArray(navigationData.appSwitcherApps) ? navigationData.appSwitcherApps : [];
        switcherApps.forEach((app) => {
            if (app && app.name) {
                appMap.set(String(app.name), app);
            }
        });

        const escapeHtml = (value) => String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');

        const hasChildren = (node) => Array.isArray(node?.children) && node.children.length > 0;

        const isNodeActive = (node) => {
            if (!node) {
                return false;
            }
            if (node.active) {
                return true;
            }
            return hasChildren(node) && node.children.some((child) => isNodeActive(child));
        };

        const buildSidebarSummaryMedia = (app) => {
            const media = app.image
                ? `<img src="${escapeHtml(app.image)}" alt="${escapeHtml(app.title || '当前应用')}" class="dp-sidebar-app-summary-image" />`
                : `<span class="dp-sidebar-app-summary-icon"><i class="dp-icon ${escapeHtml(app.icon || 'ti ti-apps')}"></i></span>`;

            return media;
        };

        const buildSidebarSummaryMarkup = (app) => {
            if (!app) {
                return `
                    <div class="dp-sidebar-brand-app-empty">
                        <div class="dp-sidebar-brand-app-label">当前应用</div>
                        <div class="dp-sidebar-brand-app-title">暂无可访问应用</div>
                    </div>
                `;
            }

            const media = buildSidebarSummaryMedia(app);

            return `
                <a
                    href="#"
                    class="dp-sidebar-brand-app-link"
                    data-bs-toggle="dropdown"
                    data-bs-auto-close="outside"
                    aria-expanded="false"
                    aria-label="打开应用切换"
                >
                    <div class="dp-sidebar-brand-app-main">
                        <div class="dp-sidebar-brand-app-media">${media}</div>
                        <div class="dp-sidebar-brand-app-title">${escapeHtml(app.title || '未命名应用')}</div>
                        <span class="dp-sidebar-brand-app-action" aria-hidden="true">
                        <i class="dp-icon ti ti-layout-grid"></i>
                        </span>
                    </div>
                </a>
                <div class="dropdown-menu dp-sidebar-brand-app-menu">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title" data-dp-sidebar-app-switcher-title>应用切换</div>
                        </div>
                        <div class="card-body scroll-y p-2" data-dp-sidebar-app-switcher-body style="max-height: 50vh"></div>
                    </div>
                </div>
            `;
        };

        const renderSummary = (app) => {
            document.querySelectorAll('[data-dp-sidebar-app-summary]').forEach((summary) => {
                summary.innerHTML = buildSidebarSummaryMarkup(app);
            });
        };

        const renderDropdownItems = (items, level = 1) => {
            if (!Array.isArray(items) || !items.length) {
                return '';
            }

            return items.map((item, index) => {
                if (!item) {
                    return '';
                }

                const name = escapeHtml(item.name || '');
                const icon = escapeHtml(item.icon || '');
                const active = isNodeActive(item);

                if (hasChildren(item)) {
                    const rawId = String(item.id || `submenu-${level}-${index}-${Date.now()}`);
                    const submenuId = `sidebar-${rawId.replace(/[^a-zA-Z0-9\-_]+/g, '-')}` || `sidebar-submenu-${Date.now()}`;
                    return `
                        <div class="dropend">
                            <a class="dropdown-item dropdown-toggle" href="#${escapeHtml(submenuId)}" data-bs-toggle="dropdown" data-bs-auto-close="false" role="button" aria-expanded="${active ? 'true' : 'false'}">
                                ${icon ? `<i class="dp-icon ${icon}"></i>` : ''}
                                ${name}
                            </a>
                            <div class="dropdown-menu${active ? ' show' : ''}">
                                ${renderDropdownItems(item.children, level + 1)}
                            </div>
                        </div>
                    `;
                }

                const href = escapeHtml(item.url || '#');
                return `
                    <a class="dropdown-item${active ? ' active' : ''}" href="${href}">
                        ${icon ? `<i class="dp-icon ${icon}"></i>` : ''}
                        ${name}
                    </a>
                `;
            }).join('');
        };

        const renderSidebarMenus = (menus) => {
            const container = document.querySelector('[data-dp-sidebar-menu-list]');
            if (!container || !Array.isArray(menus)) {
                return;
            }

            container.innerHTML = menus.map((menu, index) => {
                if (!menu) {
                    return '';
                }

                const name = escapeHtml(menu.name || '');
                const icon = escapeHtml(menu.icon || 'ti ti-point');
                const active = isNodeActive(menu);

                if (hasChildren(menu)) {
                    const rawId = String(menu.id || `menu-${index}-${Date.now()}`);
                    const menuId = `navbar-${rawId.replace(/[^a-zA-Z0-9\-_]+/g, '-')}` || `navbar-menu-${Date.now()}`;
                    return `
                        <li class="nav-item dropdown${active ? ' active' : ''}">
                            <a class="nav-link dropdown-toggle${active ? ' active' : ''}" href="#${escapeHtml(menuId)}" data-bs-toggle="dropdown" data-bs-auto-close="false" role="button" aria-expanded="${active ? 'true' : 'false'}">
                                <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="dp-icon ${icon}"></i></span>
                                <span class="nav-link-title">${name}</span>
                            </a>
                            <div class="dropdown-menu${active ? ' show' : ''}">
                                <div class="dropdown-menu-columns">
                                    <div class="dropdown-menu-column">
                                        ${renderDropdownItems(menu.children, 1)}
                                    </div>
                                </div>
                            </div>
                        </li>
                    `;
                }

                const workspaceAttr = String(menu.code || '') === 'workspace' ? ' data-dp-workspace="1"' : '';
                return `
                    <li class="nav-item${active ? ' active' : ''}">
                        <a class="nav-link${active ? ' active' : ''}" href="${escapeHtml(menu.url || '#')}"${workspaceAttr}>
                            <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="dp-icon ${icon}"></i></span>
                            <span class="nav-link-title">${name}</span>
                        </a>
                    </li>
                `;
            }).join('');
        };

        const markCurrentSwitcherApp = (appName) => {
            document.querySelectorAll('[data-dp-app-switch="1"]').forEach((link) => {
                link.classList.toggle('is-current', String(link.dataset.dpAppName || link.dataset.appName || link.getAttribute('data-dp-app-name') || '') === String(appName));
            });
        };

        const rememberApp = (appName) => {
            const rememberUrl = DolphinConfig?.url?.rememberApp;
            if (!rememberUrl || !appName) {
                return;
            }

            fetch(rememberUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                },
                body: `app=${encodeURIComponent(appName)}`,
                keepalive: true
            }).catch(() => {});
        };

        const syncSearchableMenusCurrentApp = (appName) => {
            const searchableMenus = Array.isArray(navigationData.searchableMenus) ? navigationData.searchableMenus : [];
            searchableMenus.forEach((item) => {
                if (!item || typeof item !== 'object') {
                    return;
                }

                item.is_current_app = appName !== '' && String(item.app_name || '') === String(appName);
            });
        };

        const rebuildSidebarBindings = () => {
            const iframeManager = Dolphin.getIframeManager();
            if (!iframeManager) {
                return;
            }

            iframeManager.sidebarStateInitialized = false;
            iframeManager.activeSidebarDropdownItem = null;
            iframeManager.activeSidebarTopItem = null;
            iframeManager.activeSidebarParentDropdowns = [];
            iframeManager.buildMenuRegistry?.();
            iframeManager.bindSidebarMenuEvents?.();

            const activeTabLink = document.querySelector('#openedTabs .nav-link.active, #pinnedTabs .nav-link.active');
            if (activeTabLink) {
                iframeManager.syncSidebarWithActiveTab?.(activeTabLink);
            }
        };

        const applyCurrentApp = (appName, options = {}) => {
            const app = appMap.get(String(appName));
            if (!app) {
                return false;
            }

            const sidebarMenus = navigationData.sidebarMenusByApp?.[appName];
            renderSummary(app);
            renderSidebarMenus(Array.isArray(sidebarMenus) ? sidebarMenus : []);
            markCurrentSwitcherApp(appName);
            navigationData.currentApp = app;
            syncSearchableMenusCurrentApp(String(app.name || ''));

            if (!options.skipRemember) {
                rememberApp(appName);
            }

            rebuildSidebarBindings();
            return true;
        };

        const resolveAppNameByUrl = (url) => {
            if (!url) {
                return '';
            }

            try {
                const parsed = new URL(url, window.location.origin);
                const segments = parsed.pathname.replace(/^\/+/, '').split('/');
                const appName = segments[0] || '';
                if (navigationData.sidebarMenusByApp?.[appName]) {
                    return appName;
                }
            } catch (error) {
                return '';
            }

            return '';
        };

        const openAppTab = (app) => {
            if (!app || !app.url) {
                return;
            }

            const appMode = String(DolphinConfig?.tabMode || 'iframe').toLowerCase();
            if (appMode !== 'iframe') {
                rememberApp(app.name);
                window.location.href = app.url;
                return;
            }

            applyCurrentApp(app.name);

            const iframeManager = Dolphin.getIframeManager();
            if (!iframeManager || typeof iframeManager.createDynamicTab !== 'function') {
                window.location.href = app.url;
                return;
            }

            iframeManager.createDynamicTab({
                id: iframeManager.generateTabId ? iframeManager.generateTabId(app.url) : `app-${Date.now()}`,
                title: app.title || '应用',
                url: app.url,
                icon: app.icon || 'ti ti-apps',
                realIconHTML: null,
                closable: true,
                activate: true
            });
        };

        const openMenuSearchItem = (item) => {
            if (!item) {
                return false;
            }

            const targetUrl = String(item.url || item.href || '');
            const targetIframeUrl = String(item.iframeUrl || item.iframe_url || targetUrl);
            const targetAppName = String(item.appName || item.app_name || '');
            const targetTitle = String(item.title || '新标签页');
            const targetIcon = String(item.iconClass || item.icon || 'ti-file');
            const appMode = String(DolphinConfig?.tabMode || 'iframe').toLowerCase();
            const isWorkspaceItem = Boolean(item.isWorkspace || item.is_workspace || /admin\/index\/index/i.test(targetUrl));

            if (isWorkspaceItem) {
                if (appMode === 'iframe') {
                    const homeTabLink = document.querySelector('#pinnedTabs .nav-link[href="#tab-home"], #openedTabs .nav-link[href="#tab-home"]');
                    if (homeTabLink) {
                        const iframeManager = Dolphin.getIframeManager();
                        if (iframeManager && typeof iframeManager.activateTabByLink === 'function') {
                            iframeManager.activateTabByLink(homeTabLink);
                            return true;
                        }
                    }
                }

                if (targetUrl) {
                    window.location.href = targetUrl;
                    return true;
                }

                return false;
            }

            if (appMode !== 'iframe') {
                if (targetAppName) {
                    rememberApp(targetAppName);
                }

                if (targetUrl) {
                    window.location.href = targetUrl;
                    return true;
                }

                return false;
            }

            if (targetAppName) {
                applyCurrentApp(targetAppName);
            }

            const iframeManager = Dolphin.getIframeManager();
            if (!iframeManager || typeof iframeManager.createDynamicTab !== 'function') {
                if (targetUrl) {
                    window.location.href = targetUrl;
                    return true;
                }

                return false;
            }

            iframeManager.createDynamicTab({
                id: iframeManager.generateTabId ? iframeManager.generateTabId(targetIframeUrl || targetUrl) : `menu-search-${Date.now()}`,
                title: targetTitle,
                url: targetIframeUrl || targetUrl,
                icon: targetIcon,
                realIconHTML: null,
                closable: true,
                activate: true
            });

            return true;
        };

        const syncCurrentAppByActiveTab = () => {
            const activeTabLink = document.querySelector('#openedTabs .nav-link.active, #pinnedTabs .nav-link.active');
            if (!activeTabLink || activeTabLink.getAttribute('href') === '#tab-home') {
                return;
            }

            const pane = document.querySelector(activeTabLink.getAttribute('href'));
            const iframe = pane ? pane.querySelector('.content-iframe') : null;
            const iframeUrl = iframe ? (iframe.getAttribute('src') || iframe.getAttribute('data-src')) : '';
            const appName = resolveAppNameByUrl(iframeUrl);
            if (appName) {
                applyCurrentApp(appName);
            }
        };

        return {
            applyCurrentApp,
            openAppTab,
            openMenuSearchItem,
            syncCurrentAppByActiveTab,
        };
    })();

    let mobileAppSwitcherFocusTarget = null;
    const resolveMobileAppSwitcherFocusTarget = () => {
        const trigger = document.querySelector('.dp-mobile-app-switcher-trigger');
        if (trigger instanceof HTMLElement && !trigger.disabled) {
            return trigger;
        }

        return document.body instanceof HTMLElement ? document.body : null;
    };

    const blurMobileAppSwitcherActiveElement = () => {
        if (!mobileAppSwitcherModal) {
            return;
        }

        const activeElement = document.activeElement;
        if (activeElement instanceof HTMLElement && mobileAppSwitcherModal.contains(activeElement) && typeof activeElement.blur === 'function') {
            activeElement.blur();
        }
    };

    const scheduleMobileAppSwitcherFocusRestore = () => {
        const focusTarget = mobileAppSwitcherFocusTarget || resolveMobileAppSwitcherFocusTarget();
        window.setTimeout(() => {
            if (!(focusTarget instanceof HTMLElement) || typeof focusTarget.focus !== 'function') {
                return;
            }

            try {
                focusTarget.focus({ preventScroll: true });
            } catch (error) {
                focusTarget.focus();
            }
        }, 0);
    };

    const syncAppSwitcherContent = (target, options = {}) => {
        const sourceCard = document.querySelector('[data-dp-app-switcher-source] .card');
        const targetTitle = target?.querySelector?.('[data-dp-sidebar-app-switcher-title], [data-dp-mobile-app-switcher-title]');
        const targetBody = target?.querySelector?.('[data-dp-sidebar-app-switcher-body], [data-dp-mobile-app-switcher-body]');

        if (!sourceCard || !targetBody) {
            return;
        }

        const sourceTitle = sourceCard.querySelector('.card-title');
        const sourceBody = sourceCard.querySelector('.card-body');

        if (targetTitle && sourceTitle) {
            targetTitle.textContent = sourceTitle.textContent.trim();
        }

        if (options.mode === 'modal') {
            targetBody.innerHTML = sourceBody ? sourceBody.outerHTML : '';
            targetBody.querySelectorAll('[data-dp-app-switch="1"]').forEach((link) => {
                link.setAttribute('data-bs-dismiss', 'modal');
            });
            return;
        }

        targetBody.innerHTML = sourceBody ? sourceBody.innerHTML : '';
    };

    document.querySelectorAll('[data-dp-sidebar-app-summary]').forEach((summary) => {
        summary.addEventListener('show.bs.dropdown', () => {
            syncAppSwitcherContent(summary, { mode: 'dropdown' });
        });
    });

    if (mobileAppSwitcherModal) {
        const syncMobileAppSwitcherContent = () => {
            syncAppSwitcherContent(mobileAppSwitcherModal, { mode: 'modal' });
        };

        mobileAppSwitcherModal.addEventListener('show.bs.modal', syncMobileAppSwitcherContent);
        mobileAppSwitcherModal.addEventListener('show.bs.modal', () => {
            mobileAppSwitcherFocusTarget = document.activeElement instanceof HTMLElement
                ? document.activeElement
                : resolveMobileAppSwitcherFocusTarget();
        });
        mobileAppSwitcherModal.addEventListener('click', (event) => {
            const dismissButton = event.target.closest('[data-bs-dismiss="modal"]');
            const isBackdropClick = event.target === mobileAppSwitcherModal;

            if (!dismissButton && !isBackdropClick) {
                return;
            }

            blurMobileAppSwitcherActiveElement();
            scheduleMobileAppSwitcherFocusRestore();
        });
        mobileAppSwitcherModal.addEventListener('hidden.bs.modal', () => {
            const targetBody = mobileAppSwitcherModal.querySelector('[data-dp-mobile-app-switcher-body]');
            if (targetBody) {
                targetBody.innerHTML = '';
            }
            mobileAppSwitcherFocusTarget = null;
        });
    }

    document.addEventListener('click', (event) => {
        const switcherLink = event.target.closest('[data-dp-app-switch="1"]');
        if (!switcherLink) {
            return;
        }

        const closeSwitcherOverlay = () => {
            const sidebarDropdownMenu = switcherLink.closest('.dp-sidebar-brand-app-menu');
            if (sidebarDropdownMenu && window.bootstrap && typeof window.bootstrap.Dropdown === 'function') {
                const sidebarDropdown = switcherLink.closest('[data-dp-sidebar-app-summary]');
                const sidebarToggle = sidebarDropdown
                    ? sidebarDropdown.querySelector('[data-bs-toggle="dropdown"]')
                    : null;

                if (sidebarToggle) {
                    const dropdownInstance = window.bootstrap.Dropdown.getInstance(sidebarToggle)
                        || window.bootstrap.Dropdown.getOrCreateInstance(sidebarToggle);
                    dropdownInstance.hide();
                    return true;
                }
            }

            if (mobileAppSwitcherModal && switcherLink.closest('#mobile-app-switcher-modal')) {
                if (window.bootstrap && typeof window.bootstrap.Modal === 'function') {
                    blurMobileAppSwitcherActiveElement();
                    scheduleMobileAppSwitcherFocusRestore();

                    const modalInstance = window.bootstrap.Modal.getInstance(mobileAppSwitcherModal)
                        || window.bootstrap.Modal.getOrCreateInstance(mobileAppSwitcherModal);
                    window.setTimeout(() => {
                        modalInstance.hide();
                    }, 0);
                    return true;
                }

                return false;
            }

            return false;
        };

        const app = {
            name: switcherLink.getAttribute('data-dp-app-name') || '',
            url: switcherLink.getAttribute('data-dp-app-url') || '',
            route: switcherLink.getAttribute('data-dp-app-route') || '',
            title: switcherLink.getAttribute('data-dp-app-title') || '',
            icon: switcherLink.getAttribute('data-dp-app-icon') || 'ti ti-apps',
            image: switcherLink.getAttribute('data-dp-app-image') || '',
        };

        if (String(DolphinConfig?.tabMode || 'iframe').toLowerCase() === 'iframe' && sidebarNavigationManager) {
            event.preventDefault();
            const handledByModal = closeSwitcherOverlay();

            if (!handledByModal) {
                sidebarNavigationManager.openAppTab(app);
                return;
            }

            sidebarNavigationManager.openAppTab(app);
        } else if (sidebarNavigationManager && app.name) {
            const handledByModal = closeSwitcherOverlay();

            if (!handledByModal) {
                sidebarNavigationManager.applyCurrentApp(app.name);
                return;
            }

            sidebarNavigationManager.applyCurrentApp(app.name);
        } else {
            closeSwitcherOverlay();
        }
    });

    if (sidebarNavigationManager) {
        document.addEventListener('shown.bs.tab', (event) => {
            const tabLink = event.target?.closest?.('#openedTabs .nav-link, #pinnedTabs .nav-link');
            if (!tabLink) {
                return;
            }

            window.setTimeout(() => {
                sidebarNavigationManager.syncCurrentAppByActiveTab();
            }, 0);
        });

        window.setTimeout(() => {
            sidebarNavigationManager.syncCurrentAppByActiveTab();
        }, 200);
    }

    if (sidebarNavigationManager) {
        window.DolphinSidebarNavigationManager = sidebarNavigationManager;
    }

    // 暴露管理器到window对象，方便调试和外部调用
    if (typeof Dolphin.getIframeManager() !== 'undefined') {
        window.DolphinIframeManager = Dolphin.getIframeManager();
    }
    if (typeof Dolphin.getTabManager() !== 'undefined') {
        window.DolphinTabManager = Dolphin.getTabManager();
    }
    const favoriteManagerInstance = Dolphin.getFavoriteManager();
    if (favoriteManagerInstance) {
        window.DolphinFavoriteManager = favoriteManagerInstance;
    }
});
