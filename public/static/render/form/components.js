/**
 * UploadComponentBase - 上传组件基类
 *
 * 为 ImageUpload 和 FileUpload 提供公共功能，消除重复代码
 *
 * @author DolphinPHP
 * @version 2.1.0
 */
(function () {
    'use strict';

    /**
     * @typedef {Object} ValidateResult
     * @property {boolean} valid - 验证是否通过
     * @property {string[]} errors - 错误信息数组
     */

    /**
     * 上传组件基类
     *
     * 提供文件上传组件的通用功能：
     * - DOM 缓存管理
     * - 驱动适配器集成
     * - 值的获取和设置
     * - 清空和删除功能
     * - 验证功能
     * - 资源清理
     *
     * @abstract
     * @extends {BaseComponent}
     */
    class UploadComponentBase extends DpForm['BaseComponent'] {
        /**
         * 构造函数
         * @param {...*} args - 传递给父类的参数
         */
        constructor(...args) {
            super(...args);

            /** @type {Map<string, Element|null>} 缓存DOM查询结果 */
            this.domCache = new Map();
            /** @type {Array<Function>} 资源清理队列 */
            this.cleanupQueue = [];
            /** @type {Array<Function>} 事件监听器清理函数 */
            this.eventCleanups = [];
        }

        /**
         * 获取默认配置
         * 子类可以覆盖此方法提供特定配置
         *
         * @returns {Object} 默认配置
         */
        getDefaultConfig() {
            return {
                // 基础配置
                maxFileSize: 10 * 1024 * 1024, // 10MB
                allowedTypes: ['*'],
                multiple: true,
                autoUpload: false,

                // 自定义UI模式
                inputOnly: true,

                // 分片配置
                chunked: false,
                chunkSize: 1024 * 1024, // 1MB
                resumable: true,

                // 并发配置
                concurrent: 3,
                retries: 3,
                retryDelay: 1000,

                // 接口配置
                endpoints: {
                    upload: '/admin/api/upload',
                    check: '/admin/api/checkFile',
                    save: '/admin/api/saveFile',
                    chunk: '/admin/api/uploadChunk',
                    merge: '/admin/api/mergeChunks',
                    getChunks: '/admin/api/getUploadedChunks'
                },

                // UI配置
                showPreview: true,
                dragAndDrop: true,
            };
        }

        /**
         * 获取上传类型（抽象方法，子类必须实现）
         *
         * @abstract
         * @returns {'image'|'file'} 上传类型
         * @throws {Error} 子类未实现时抛出错误
         */
        getUploadType() {
            throw new Error('getUploadType() must be implemented by subclass');
        }

        /**
         * 获取项目选择器（抽象方法，子类必须实现）
         *
         * @abstract
         * @returns {string} CSS选择器
         * @throws {Error} 子类未实现时抛出错误
         */
        getItemSelector() {
            throw new Error('getItemSelector() must be implemented by subclass');
        }

        /**
         * 初始化DOM元素（抽象方法，子类必须实现）
         *
         * @abstract
         * @throws {Error} 子类未实现时抛出错误
         */
        initDOMElements() {
            throw new Error('initDOMElements() must be implemented by subclass');
        }

        /**
         * 绑定上传器事件（抽象方法，子类必须实现）
         *
         * @abstract
         * @throws {Error} 子类未实现时抛出错误
         */
        bindUploaderEvents() {
            throw new Error('bindUploaderEvents() must be implemented by subclass');
        }

        /**
         * 绑定DOM事件（抽象方法，子类可选实现）
         */
        bindDOMEvents() {
            // 子类可以覆盖此方法
        }

        /**
         * 缓存DOM元素查询
         *
         * @param {string} selector - CSS选择器
         * @returns {Element|null} DOM元素
         */
        getCachedElement(selector) {
            if (!this.domCache.has(selector)) {
                const element = this.element[0].querySelector(selector);
                this.domCache.set(selector, element);
            }
            return this.domCache.get(selector);
        }

        /**
         * 安全地处理ID
         * 只允许字母、数字、下划线、连字符
         *
         * @param {string} id - 原始ID
         * @returns {string} 安全的ID
         */
        sanitizeId(id) {
            return String(id).replace(/[^a-zA-Z0-9_-]/g, '');
        }

        /**
         * 获取当前已有的文件数量
         *
         * @returns {number} 当前文件数量
         */
        getCurrentCount() {
            try {
                return this.element[0].querySelectorAll(this.getItemSelector()).length;
            } catch (error) {
                console.warn('获取当前文件数量失败:', error);
                return 0;
            }
        }

        /**
         * 组件初始化
         *
         * @returns {Promise<void>}
         * @throws {Error} 当缺少依赖库、DOM元素未找到或驱动初始化失败时抛出
         */
        async onInit() {
            try {
                // 检查依赖
                if (!window.DolphinUploader) {
                    throw new Error('DolphinUploader is required. Please include dolphin-uploader.js');
                }

                // 初始化DOM元素（子类实现）
                this.initDOMElements();

                // 验证必需的DOM元素
                if (!this.target) {
                    throw new Error('Upload target element not found');
                }

                // 上传目录
                const dir = $(this.target).data('dir');

                // 合并元素上的配置
                const elementOptions = $(this.target).data('options') || {};
                const finalOptions = {...this.config.get(), ...elementOptions};

                // 安全地初始化驱动
                const driverName = $(this.target).data('driver') || 'local';
                const uploadType = this.getUploadType();

                // 先尝试智能查找驱动（支持命名空间）
                const driverInfo = Dolphin.uploader.find(driverName, uploadType);
                const actualDriver = driverInfo ? driverInfo.driver : (Dolphin.uploader.get(driverName) || {});

                // 将驱动的 responseHandler 添加到配置中
                if (typeof actualDriver.responseHandler === 'function') {
                    finalOptions.responseHandler = actualDriver.responseHandler.bind(actualDriver);
                }

                // 创建上传器实例
                this.instance = new DolphinUploader(this.element[0], finalOptions);

                // 通过Dolphin.uploader.create API集成驱动
                this.driverAdapter = Dolphin.uploader.create(this.instance, driverName, uploadType);

                // 记录驱动信息
                if (!this.driverAdapter) {
                    console.warn(`[${this.constructor.name}] 驱动 "${driverName}" 创建失败，将使用默认行为`);
                }

                // 初始化完成事件
                this.element[0].addEventListener('uploader:init', () => {
                    if (this.instance.options.multiple) {
                        const itemsContainer = this.element[0].querySelector(this.getItemsSelector());
                        if (itemsContainer) {
                            this.sortable = new Sortable(itemsContainer, this.getSortableOptions());
                        }
                    }
                });

                // 绑定上传器事件（子类实现）
                this.bindUploaderEvents();

                // 绑定DOM事件（子类可选实现）
                this.bindDOMEvents();

                // 初始化时更新清空按钮显示状态
                this.updateClearButtonVisibility();

            } catch (error) {
                // 特殊处理驱动相关错误
                if (error.message && error.message.includes('driver')) {
                    console.error(`[${this.constructor.name}] 驱动错误:`, error);
                    this.handleError('驱动初始化失败', error);

                    // 提供降级方案
                    if (window.Dolphin && typeof Dolphin.error === 'function') {
                        Dolphin.error('上传功能可能受限，请检查驱动配置');
                    }
                } else {
                    this.handleError('组件初始化失败', error);
                }
                throw error;
            }
        }

        /**
         * 获取items容器选择器（子类可覆盖）
         *
         * @returns {string} CSS选择器
         */
        getItemsSelector() {
            // 子类可以覆盖此方法
            return this.getItemSelector().replace('-item', '-items');
        }

        /**
         * 获取Sortable配置（子类可覆盖）
         *
         * @returns {Object} Sortable配置
         */
        getSortableOptions() {
            return {
                animation: 150,
                draggable: this.getItemSelector()
            };
        }

        /**
         * 获取组件值
         *
         * @returns {string|string[]} 单文件返回字符串，多文件返回数组
         */
        getValue() {
            try {
                const values = [];
                const items = this.element[0].querySelectorAll(`${this.getItemSelector()} input[type="hidden"]`);

                items.forEach(input => {
                    const value = input.value;
                    if (value) {
                        values.push(value);
                    }
                });

                if (this.instance && this.instance.options.multiple) {
                    return values;
                } else {
                    return values.length > 0 ? values[0] : null;
                }

            } catch (error) {
                console.warn('获取组件值失败:', error);
                return this.instance && this.instance.options.multiple ? [] : null;
            }
        }

        /**
         * 设置组件值
         *
         * @param {string|string[]|Object|Object[]} value - 值（支持字符串、数组、对象）
         * @returns {Promise<UploadComponentBase>} this（支持链式调用）
         */
        async setValue(value) {
            try {
                // 清空现有文件
                this.clearAllFiles();

                if (!value) {
                    return this;
                }

                // 处理不同类型的值
                const values = Array.isArray(value) ? value : [value];

                // 异步设置每个值
                for (const item of values) {
                    await this.setValueItem(item);
                }

                this.emit('change', this.getValue());

            } catch (error) {
                this.handleError('设置组件值失败', error);
            }

            return this;
        }

        /**
         * 设置单个值项（抽象方法，子类必须实现）
         *
         * @abstract
         * @param {string|Object} item - 值项
         * @returns {Promise<void>}
         */
        async setValueItem(item) {
            throw new Error('setValueItem() must be implemented by subclass');
        }

        /**
         * 生成唯一ID
         *
         * @returns {string} 唯一ID
         */
        generateUniqueId() {
            return `file_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
        }

        /**
         * 清空所有文件（公共方法）
         *
         * @returns {UploadComponentBase} this（支持链式调用）
         */
        clear() {
            this.clearAllFiles();
            return this;
        }

        /**
         * 清空所有文件
         * @returns {void}
         */
        clearAllFiles() {
            try {
                // 获取所有删除按钮
                const deleteButtons = this.element[0].querySelectorAll(this.getDeleteButtonSelector());

                if (deleteButtons.length === 0) {
                    Dolphin.notify('没有可清空的文件', 'info');
                    return;
                }

                // 记录删除的数量
                let deletedCount = 0;

                // 遍历所有删除按钮，模拟点击删除
                deleteButtons.forEach((deleteBtn) => {
                    try {
                        const $deleteBtn = $(deleteBtn);
                        const fileId = $deleteBtn.data('id');
                        const $item = $deleteBtn.closest(this.getItemSelector());

                        // 移除DOM元素
                        $item.find('[data-bs-toggle="tooltip"]').tooltip('hide');
                        $item.remove();

                        // 从上传器中移除文件
                        if (this.instance && typeof this.instance.removeFile === 'function' && fileId) {
                            this.instance.cancelUpload(fileId);
                            this.instance.removeFile(fileId);
                        }

                        deletedCount++;
                    } catch (error) {
                        console.error('删除单个文件失败:', error);
                    }
                });

                // 清空上传器（确保清理所有状态）
                if (this.instance && typeof this.instance.clear === 'function') {
                    this.instance.clear();
                }

                // 显示添加按钮
                if (this.btnAdd) $(this.btnAdd).show();
                if (this.btnBrowser) $(this.btnBrowser).show();

                // 触发变化事件
                this.emit('change', this.getValue());

                // 更新清空按钮显示状态
                this.updateClearButtonVisibility();

                // 显示成功消息
                if (deletedCount > 0) {
                    Dolphin.notify(`成功清空 ${deletedCount} 个文件`, 'success');
                }
            } catch (error) {
                console.error('清空所有文件失败:', error);
                Dolphin.error('清空操作失败');
            }
        }

        /**
         * 获取删除按钮选择器（子类可覆盖）
         *
         * @abstract
         * @returns {string} CSS选择器
         * @throws {Error} 子类未实现时抛出错误
         */
        getDeleteButtonSelector() {
            // 子类可以覆盖此方法
            throw new Error('getDeleteButtonSelector() must be implemented by subclass');
        }

        /**
         * 更新清空按钮的显示状态
         * @returns {void}
         */
        updateClearButtonVisibility() {
            if (!this.btnClear) return;

            const fileItems = this.element[0].querySelectorAll(this.getItemSelector());
            const hasFiles = fileItems.length > 0;

            if (this.instance && this.instance.options.multiple) {
                // 多文件模式：有文件时显示清空按钮
                if (hasFiles) {
                    $(this.btnClear).show();
                } else {
                    $(this.btnClear).hide();
                }
            } else {
                // 单文件模式：一般隐藏清空按钮（用户可以直接点击删除）
                $(this.btnClear).hide();
            }
        }

        /**
         * 验证组件
         *
         * @returns {ValidateResult} 验证结果 {valid: boolean, errors: string[]}
         */
        validate() {
            const errors = [];

            try {
                const value = this.getValue();
                const config = this.config.get();

                // 必填验证
                if (config.required && this.isEmpty(value)) {
                    errors.push('请至少上传一个文件');
                }

                // 数量验证
                if (Array.isArray(value)) {
                    const minFiles = config.minFiles || 0;
                    const maxFiles = config.maxFiles || Infinity;

                    if (value.length < minFiles) {
                        errors.push(`至少需要上传 ${minFiles} 个文件`);
                    }

                    if (maxFiles !== Infinity && value.length > maxFiles) {
                        errors.push(`最多只能上传 ${maxFiles} 个文件`);
                    }
                }

                // 上传状态验证
                if (this.instance) {
                    const uploadErrors = this.validateUploadStatus();
                    errors.push(...uploadErrors);
                }

            } catch (error) {
                errors.push('验证过程中发生错误');
                console.error('验证失败:', error);
            }

            return {
                valid: errors.length === 0,
                errors
            };
        }

        /**
         * 验证上传状态
         *
         * @returns {string[]} 错误信息数组
         */
        validateUploadStatus() {
            const errors = [];

            try {
                const files = this.instance.getFiles();
                const errorFiles = files.filter(file => file.status === 'error');
                const uploadingFiles = files.filter(file => file.status === 'uploading');
                const queuedFiles = files.filter(file => file.status === 'queued' || file.status === 'pending');

                if (errorFiles.length > 0) {
                    errors.push(`有 ${errorFiles.length} 个文件上传失败，请重试或移除`);
                }

                if (uploadingFiles.length > 0) {
                    errors.push(`还有 ${uploadingFiles.length} 个文件正在上传中，请等待完成`);
                }

                if (queuedFiles.length > 0) {
                    errors.push(`还有 ${queuedFiles.length} 个文件等待上传，请等待完成`);
                }

            } catch (error) {
                console.warn('验证上传状态失败:', error);
            }

            return errors;
        }

        /**
         * 判断值是否为空
         *
         * @param {*} value - 值
         * @returns {boolean} 是否为空
         */
        isEmpty(value) {
            return value === null || value === undefined || value === '' ||
                (Array.isArray(value) && value.length === 0);
        }

        /**
         * 处理错误
         *
         * @param {string} message - 错误消息
         * @param {Error} error - 错误对象
         * @returns {void}
         */
        handleError(message, error) {
            console.error(`${this.constructor.name} - ${message}:`, error);

            // 触发错误事件
            this.emit('error', error);

            // 显示用户友好的错误信息
            if (window.Dolphin && typeof Dolphin.error === 'function') {
                Dolphin.error(message);
            }
        }

        /**
         * 销毁组件
         *
         * @returns {Promise<void>}
         * @throws {Error} 当销毁过程中发生错误时抛出
         */
        async onDestroy() {
            try {
                // 清理事件监听器
                if (this.element) {
                    this.element.off('click', this.getDeleteButtonSelector());
                    this.element.off('click', '.dp-form-upload-button-retry');
                    this.element.off('click', `${this.getItemSelector()}-clear`);
                }

                // 执行清理队列
                this.cleanupQueue.forEach(cleanup => {
                    try {
                        cleanup();
                    } catch (error) {
                        console.warn('清理操作失败:', error);
                    }
                });

                // 清理事件监听器
                this.eventCleanups.forEach(cleanup => {
                    try {
                        cleanup();
                    } catch (error) {
                        console.warn('清理事件监听器失败:', error);
                    }
                });

                // 销毁Sortable实例
                if (this.sortable && typeof this.sortable.destroy === 'function') {
                    this.sortable.destroy();
                    this.sortable = null;
                }

                // 销毁上传器实例
                if (this.instance && typeof this.instance.destroy === 'function') {
                    this.instance.destroy();
                }

                // 销毁驱动适配器
                if (this.driverAdapter && typeof this.driverAdapter.destroy === 'function') {
                    this.driverAdapter.destroy();
                }

                this.instance = null;
                this.driverAdapter = null;

                // 清理DOM缓存
                if (this.domCache) {
                    this.domCache.clear();
                    this.domCache = null;
                }

                // 清理DOM元素引用
                this.target = null;
                this.btnAdd = null;
                this.btnBrowser = null;
                this.btnClear = null;
                this.items = null;

                // 清理其他引用
                this.cleanupQueue = null;
                this.eventCleanups = null;

            } catch (error) {
                console.warn(`${this.constructor.name} destroy failed:`, error);
            }

            // 调用父类销毁方法
            if (super.onDestroy) {
                await super.onDestroy();
            }
        }
    }

    // 导出到全局命名空间
    if (typeof window.DpForm !== 'undefined') {
        window.DpForm.UploadComponentBase = UploadComponentBase;
    } else {
        console.warn('DpForm not found. UploadComponentBase requires DpForm to be loaded first.');
    }

})();

(function () {
    'use strict';

    // ===========================================
    // TextareaMax Component
    // ===========================================
    /**
     * TextareaMax 组件
     * 提供字符计数功能的文本域组件
     *
     * @class TextareaMaxComponent
     * @extends {BaseComponent}
     */
    class TextareaMaxComponent extends DpForm['BaseComponent'] {
        /**
         * 组件初始化
         * @returns {Promise<void>}
         */
        async onInit() {
            // 获取原生DOM元素
            const element = this.element[0] || this.element;

            // 查找 textarea 元素
            let textarea = element.querySelector('textarea');
            if (!textarea && element.tagName === 'TEXTAREA') {
                textarea = element;
            }

            // 查找 .curr 元素
            const curr = element.querySelector('.curr');

            if (!textarea || !curr) {
                console.warn('TextareaMaxComponent: textarea or .curr element not found');
                return;
            }

            // 输入法状态
            let composing = false;

            // 更新字符数的函数
            const updateCount = () => {
                curr.textContent = textarea.value.length;
            };

            // 监听输入法开始
            const handleCompositionStart = () => {
                composing = true;
            };

            // 监听输入法结束
            const handleCompositionEnd = () => {
                composing = false;
                updateCount();
            };

            // 监听输入事件
            const handleInput = () => {
                if (!composing) updateCount();
            };

            // 绑定事件监听器
            textarea.addEventListener('compositionstart', handleCompositionStart);
            textarea.addEventListener('compositionend', handleCompositionEnd);
            textarea.addEventListener('input', handleInput);

            // 初始化显示
            updateCount();

            // 保存引用以便清理
            this.textarea = textarea;
            this.curr = curr;
            this.composing = composing;
            this.eventHandlers = {handleCompositionStart, handleCompositionEnd, handleInput};
        }

        /**
         * 获取组件值
         * @returns {string} 文本域的值
         */
        getValue() {
            return this.textarea ? this.textarea.value : '';
        }

        /**
         * 设置组件值
         * @param {string} value - 要设置的值
         * @returns {TextareaMaxComponent} this（支持链式调用）
         */
        setValue(value) {
            if (this.textarea) {
                this.textarea.value = value;
                // 更新计数显示
                if (this.curr) {
                    this.curr.textContent = value.length;
                }
            }
            return this;
        }

        /**
         * 组件销毁时清理事件监听器
         * @returns {Promise<void>}
         */
        async onDestroy() {
            if (this.textarea && this.eventHandlers) {
                this.textarea.removeEventListener('compositionstart', this.eventHandlers.handleCompositionStart);
                this.textarea.removeEventListener('compositionend', this.eventHandlers.handleCompositionEnd);
                this.textarea.removeEventListener('input', this.eventHandlers.handleInput);
            }

            // 清空引用
            this.textarea = null;
            this.curr = null;
            this.eventHandlers = null;
        }
    }

    // ===========================================
    // Select2 Component
    // ===========================================
    /**
     * Select2 组件
     * 基于 jQuery Select2 的下拉选择组件，支持搜索、Ajax 加载、多选等功能
     *
     * @class Select2Component
     * @extends {BaseComponent}
     */
    class Select2Component extends DpForm['BaseComponent'] {
        /**
         * 组件初始化
         * @returns {Promise<void>}
         * @throws {Error} 当依赖库缺失或配置错误时抛出异常
         */
        async onInit() {
            try {
                // 检查依赖
                this.validateDependencies();

                // 初始化配置
                this.initializeConfig();

                // 设置AJAX配置
                this.setupAjaxConfig();

                // 加载默认值
                await this.loadDefaultValues();

                // 初始化Select2
                this.initializeSelect2();

                // 绑定事件
                this.bindEvents();

            } catch (error) {
                this.handleError('组件初始化失败', error);
                throw error;
            }
        }

        /**
         * 验证依赖库
         * @private
         * @throws {Error} 当 jQuery 或 Select2 库未加载时抛出异常
         */
        validateDependencies() {
            if (!window.jQuery || !window.$.fn.select2) {
                throw new Error('需要加载jquery和Select2库');
            }
        }

        /**
         * 初始化配置
         * @private
         * @throws {Error} 当 select 元素未找到时抛出异常
         */
        initializeConfig() {
            const $this = $(this.element);
            this.$select = $this.find('select');

            if (this.$select.length === 0) {
                throw new Error('Select element not found');
            }

            // 解析配置参数
            this.config = {
                multiple: this.$select.attr('multiple') || false,
                width: this.$select.data('width') || '100%',
                url: this.$select.data('ajax-url') || '',
                delay: parseInt(this.$select.data('ajax-delay')) || 500,
                token: this.$select.data('ajax-token') || '',
                value: this.$select.data('ajax-value') || '',
                rows: parseInt(this.$select.data('ajax-rows')) || 15,
                customOptions: this.$select.data('options') || {}
            };

            // 基础Select2选项
            this.options = {
                width: this.config.width,
                language: "zh-CN",
                theme: "bootstrap-5",
                selectionCssClass: 'select2--small',
                dropdownCssClass: 'select2--small',
                placeholder: this.$select.attr('placeholder') || '请选择...'
            };
        }

        /**
         * 设置 AJAX 配置
         * @private
         */
        setupAjaxConfig() {
            if (!this.config.url) return;

            const self = this;
            this.options.ajax = {
                url: this.config.url,
                dataType: 'json',
                delay: this.config.delay,
                cache: true,
                data: function (params) {
                    return {
                        keyword: params.term || '',
                        type: params._type || '',
                        page: params.page || 1,
                        token: self.config.token
                    };
                },
                processResults: function (data, params) {
                    params.page = params.page || 1;

                    // 数据验证和处理
                    const results = Array.isArray(data.data) ? data.data : [];
                    const total = parseInt(data.total) || 0;

                    return {
                        results: results,
                        pagination: {
                            more: (params.page * self.config.rows) < total
                        }
                    };
                },
                transport: function (params, success, failure) {
                    // 使用jQuery.ajax进行请求，增强错误处理
                    const request = $.ajax(params);

                    request.done(function (data) {
                        // 检查业务逻辑错误
                        if (data.code === 0) {
                            const errorMsg = data.msg || data.message || '请求失败';
                            self.handleError('数据请求失败', new Error(errorMsg));
                            failure({message: errorMsg});
                            return;
                        }
                        success(data);
                    });

                    request.fail(function (xhr) {
                        const errorMsg = self.parseErrorMessage(xhr);
                        self.handleError('网络请求失败', new Error(errorMsg));
                        failure({message: errorMsg});
                    });

                    return request;
                }
            };
        }

        /**
         * 加载默认值
         * @private
         * @returns {Promise<void>}
         */
        async loadDefaultValues() {
            if (!this.config.url || !this.config.value) return;

            try {
                const data = await this.fetchDefaultValues();
                if (data && data.code && Array.isArray(data.data)) {
                    this.appendOptions(data.data);
                    this.setDefaultValue();
                }
            } catch (error) {
                this.handleError('加载默认值失败', error);
                // 不抛出错误，允许组件继续初始化
            }
        }

        /**
         * 获取默认值
         * @private
         * @returns {Promise<Object>} 包含默认值的响应对象
         */
        fetchDefaultValues() {
            return new Promise((resolve, reject) => {
                $.get(this.config.url, {
                    type: 'default',
                    token: this.config.token,
                    default: this.config.value
                })
                    .done(resolve)
                    .fail((xhr) => {
                        const errorMsg = this.parseErrorMessage(xhr);
                        reject(new Error(errorMsg));
                    });
            });
        }

        /**
         * 追加选项
         * @private
         * @param {Array<Object>} optionsData - 选项数据数组
         */
        appendOptions(optionsData) {
            optionsData.forEach(item => {
                if (item.id !== undefined && item.text !== undefined) {
                    const option = new Option(item.text, item.id, false, false);
                    this.$select.append(option);
                }
            });
        }

        /**
         * 设置默认值
         * @private
         */
        setDefaultValue() {
            let selectedValue = this.config.value;

            if (this.config.multiple) {
                selectedValue = selectedValue.toString().split(',');
            }

            this.$select.val(selectedValue).trigger('change');
        }

        /**
         * 初始化 Select2 实例
         * @private
         */
        initializeSelect2() {
            // 合并自定义选项
            const finalOptions = $.extend(true, {}, this.options, this.config.customOptions);

            // 初始化Select2
            this.instance = this.$select.select2(finalOptions);

            // 触发初始化完成事件
            this.emit('initialized', this.instance);
        }

        /**
         * 绑定事件
         * @private
         */
        bindEvents() {
            const self = this;

            // 记录点击X按钮前的下拉菜单状态
            this.$select.on('select2:unselecting', function (e) {
                self._wasOpenBeforeUnselect = $(this).select2('isOpen');
                self._isUnselecting = true;
            });

            // 阻止因点击X按钮导致的下拉菜单状态改变
            this.$select.on('select2:closing', function (e) {
                // 如果是因为点击X按钮触发的关闭
                if (self._isUnselecting && self._wasOpenBeforeUnselect) {
                    // 阻止关闭，保持打开状态
                    e.preventDefault();

                    // 清除标记（因为阻止了closing，close事件不会触发）
                    self._isUnselecting = false;
                    self._wasOpenBeforeUnselect = undefined;
                }
            });

            this.$select.on('select2:opening', function (e) {
                // 如果是因为点击X按钮触发的打开
                if (self._isUnselecting && !self._wasOpenBeforeUnselect) {
                    // 阻止打开，保持关闭状态
                    e.preventDefault();

                    // 清除标记（因为阻止了opening，open事件不会触发）
                    self._isUnselecting = false;
                    self._wasOpenBeforeUnselect = undefined;
                }
            });

            // 标准事件转发
            this.$select.on('select2:select', function (e) {
                self.emit('select', e.params.data);
            });

            this.$select.on('select2:unselect', function (e) {
                self.emit('unselect', e.params.data);
            });

            this.$select.on('select2:open', function () {
                self.emit('open');

                // 在下拉菜单打开后清除标记
                if (self._isUnselecting) {
                    self._isUnselecting = false;
                    self._wasOpenBeforeUnselect = undefined;
                }
            });

            this.$select.on('select2:close', function () {
                self.emit('close');

                // 在下拉菜单关闭后清除标记
                if (self._isUnselecting) {
                    self._isUnselecting = false;
                    self._wasOpenBeforeUnselect = undefined;
                }
            });

            this.$select.on('change', function () {
                self.emit('change', self.getValue());
            });
        }

        /**
         * 处理取消选择事件
         * @private
         * @param {Event} e - 事件对象
         * @param {jQuery} $select - Select2 元素
         */
        handleUnselecting(e, $select) {
            const $searchField = $select.parent().find('.select2-search__field');
            const isOpen = $select.select2('isOpen');

            e.preventDefault();

            // 使用箭头函数保持this上下文
            setTimeout(() => {
                const targetValue = e.params.args.data.id;
                $select.find(`[value="${targetValue}"]`).prop('selected', false);
                $select.trigger('change');

                // 如果下拉菜单之前是打开的，则保持打开状态
                if (isOpen) {
                    $select.select2('open');
                    $searchField.val('').trigger('focus');
                }
            }, 0);
        }

        /**
         * 获取组件值
         * @returns {string|Array<string>|null} 单选返回字符串，多选返回数组
         */
        getValue() {
            if (!this.$select) return null;

            const value = this.$select.val();
            return this.config.multiple ? (Array.isArray(value) ? value : []) : value;
        }

        /**
         * 设置组件值
         * @param {string|Array<string>} value - 要设置的值
         * @returns {Select2Component} this（支持链式调用）
         */
        setValue(value) {
            if (!this.$select) return this;

            try {
                this.$select.val(value).trigger('change');
                this.emit('setValue', value);
            } catch (error) {
                this.handleError('设置值失败', error);
            }

            return this;
        }

        /**
         * 刷新 Select2 实例
         * @returns {Select2Component} this（支持链式调用）
         */
        refresh() {
            if (this.instance) {
                this.$select.select2('destroy').select2(this.options);
            }
            return this;
        }

        /**
         * 解析错误消息
         * @private
         * @param {jqXHR} xhr - jQuery XHR 对象
         * @returns {string} 解析后的错误消息
         */
        parseErrorMessage(xhr) {
            let errorMessage = '服务器响应错误';

            try {
                if (xhr['responseJSON']) {
                    errorMessage = xhr['responseJSON'].msg || xhr['responseJSON'].message || errorMessage;
                } else if (xhr.responseText) {
                    // 尝试从HTML响应中提取错误信息
                    const $response = $(xhr.responseText);
                    const h1Text = $response.find('h1').text();
                    errorMessage = h1Text || '服务器内部错误';
                }
            } catch (e) {
                console.warn('Failed to parse error message:', e);
            }

            return errorMessage;
        }

        /**
         * 处理错误
         * @private
         * @param {string} context - 错误上下文
         * @param {Error} error - 错误对象
         */
        handleError(context, error) {
            const fullMessage = `${context}: ${error.message || error}`;

            Dolphin.error(fullMessage);

            // 触发错误事件
            this.emit('error', {context, error, message: fullMessage});
        }

        /**
         * 销毁组件
         * @returns {Promise<void>}
         */
        async onDestroy() {
            try {
                // 移除事件监听
                if (this.$select) {
                    this.$select.off('select2:unselecting select2:closing select2:opening select2:select select2:unselect select2:open select2:close change');

                    // 销毁Select2实例
                    if (this.instance) {
                        this.$select.select2('destroy');
                    }
                }

                // 清理引用
                this.instance = null;
                this.$select = null;
                this.config = null;
                this.options = null;
                this._wasOpenBeforeUnselect = null;
                this._isUnselecting = null;

            } catch (error) {
                console.warn('Error during Select2Component destruction:', error);
            }
        }
    }

    // ===========================================
    // Password Component
    // ===========================================
    /**
     * Password 组件
     * 提供密码输入、显示/隐藏密码、密码强度检测功能
     *
     * @class PasswordComponent
     * @extends {BaseComponent}
     */
    class PasswordComponent extends DpForm['BaseComponent'] {
        /**
         * 组件初始化
         * @returns {Promise<void>}
         */
        async onInit() {
            let $password = $(this.element);
            let $link = $password.find('.dp-icon-link');
            let $icon = $password.find('.dp-icon');
            let $input = $password.find('input');

            // 显示/隐藏密码功能
            $link.click(function () {
                $link.toggleClass('link-secondary');
                if ($icon.hasClass('fa-eye-slash')) {
                    $icon.removeClass('fa-eye-slash').addClass('fa-eye');
                    $input.attr('type', 'text');
                } else {
                    $icon.removeClass('fa-eye').addClass('fa-eye-slash');
                    $input.attr('type', 'password');
                }
            });

            // 密码强度检测功能
            if ($password.find('.password-strength-wrapper')) {
                this.initPasswordStrength($password, $input);
            }
        }

        /**
         * 初始化密码强度检测
         * @private
         * @param {jQuery} $password - 密码组件容器
         * @param {jQuery} $input - 密码输入框
         */
        initPasswordStrength($password, $input) {
            const $wrapper = $password.find('.password-strength-wrapper');
            const $progress = $password.find('.password-strength-progress');
            const $level = $password.find('.password-strength-level');

            // 监听输入事件
            $input.on('input', () => {
                const password = $input.val();
                const strength = this.calculatePasswordStrength(password);

                // 显示/隐藏强度提示
                if (password.length > 0) {
                    $wrapper.show();
                } else {
                    $wrapper.hide();
                    return;
                }

                // 更新进度条
                $progress.css('width', strength.percentage + '%');
                $progress.attr('aria-valuenow', strength.percentage);

                // 更新进度条颜色
                $progress.removeClass('bg-danger bg-warning bg-success');
                $progress.addClass('bg-' + strength.color);

                // 更新文字
                $level.text(strength.text);
                $level.removeClass('text-danger text-warning text-success');
                $level.addClass('text-' + strength.color);
            });

            // 触发一次计算（如果有初始值）
            if ($input.val()) {
                $input.trigger('input');
            }
        }

        /**
         * 计算密码强度（固定标准）
         * @private
         * @param {string} password - 密码
         * @returns {{level: string, text: string, color: string, percentage: number}} 强度信息
         */
        calculatePasswordStrength(password) {
            if (!password) {
                return {
                    level: 'none',
                    text: '未设置',
                    color: 'secondary',
                    percentage: 0
                };
            }

            let score = 0;
            const length = password.length;

            // 固定长度评分标准
            if (length >= 12) {
                score += 30;  // 12位以上：优秀
            } else if (length >= 8) {
                score += 25;  // 8-11位：良好
            } else if (length >= 6) {
                score += 15;  // 6-7位：及格
            } else {
                score += 5;   // 6位以下：很弱
            }

            // 包含小写字母
            if (/[a-z]/.test(password)) score += 15;

            // 包含大写字母
            if (/[A-Z]/.test(password)) score += 15;

            // 包含数字
            if (/\d/.test(password)) score += 15;

            // 包含特殊字符
            if (/[^a-zA-Z0-9]/.test(password)) score += 20;

            // 超长密码额外加分
            if (length >= 16) {
                score += 10;
            } else if (length >= 14) {
                score += 5;
            }

            // 根据得分返回强度等级
            if (score >= 80) {
                return {
                    level: 'strong',
                    text: '强',
                    color: 'success',
                    percentage: 100
                };
            } else if (score >= 50) {
                return {
                    level: 'medium',
                    text: '中等',
                    color: 'warning',
                    percentage: 60
                };
            } else {
                return {
                    level: 'weak',
                    text: '弱',
                    color: 'danger',
                    percentage: 30
                };
            }
        }

        /**
         * 获取组件值
         * @returns {string} 密码值
         */
        getValue() {
            return this.element.find('input').val();
        }

        /**
         * 设置组件值
         * @param {string} value - 要设置的值
         */
        setValue(value) {
            this.element.find('input').val(value).trigger('input');
        }

        /**
         * 组件销毁时清理事件监听器
         * @returns {Promise<void>}
         */
        async onDestroy() {
            // 清理事件监听器
            const $input = this.element.find('input');
            $input.off('input');
            this.element.find('.dp-icon-link').off('click');
        }
    }

    // ===========================================
    // Tags Component
    // ===========================================
    /**
     * Tags 组件
     * 基于 Tagify 的标签输入组件，支持拖拽排序
     *
     * @class TagsComponent
     * @extends {BaseComponent}
     */
    class TagsComponent extends DpForm['BaseComponent'] {
        /**
         * 组件初始化
         * @returns {Promise<void>}
         */
        async onInit() {
            let $this = $(this.element);
            let $tags = $this.find('input');
            let options = {
                originalInputValueFormat: valuesArr => valuesArr.map(item => item.value).join(','),
            }
            $.extend(options, $tags.data('options'));

            this.instance = new Tagify($tags[0], options);

            if (!$tags.prop('readonly')) {
                new DragSort(this.instance.DOM.scope, {
                    selector: '.' + this.instance.settings.classNames.tag,
                    callbacks: {
                        dragEnd: () => {
                            this.instance.updateValueByDOMTags()
                        }
                    }
                });
            }
        }

        /**
         * 获取组件值
         * @returns {string} 以逗号分隔的标签字符串
         */
        getValue() {
            if (!this.instance) return '';
            const tags = this.instance.value;
            return tags.map(tag => tag.value).join(',');
        }

        /**
         * 设置组件值
         * @param {string|Array<string>} value - 字符串或数组
         */
        setValue(value) {
            if (!this.instance) return;
            if (typeof value === 'string') {
                this.instance.removeAllTags();
                if (value) {
                    this.instance.addTags(value.split(','));
                }
            } else if (Array.isArray(value)) {
                this.instance.removeAllTags();
                this.instance.addTags(value);
            }
        }

        /**
         * 组件销毁
         * @returns {Promise<void>}
         */
        async onDestroy() {
            if (this.instance && typeof this.instance.destroy === 'function') {
                this.instance.destroy();
            }
        }
    }

    // ===========================================
    // ColorPicker Component
    // ===========================================
    /**
     * ColorPicker 组件
     * 基于 Coloris 的颜色选择器组件
     *
     * @class ColorPickerComponent
     * @extends {BaseComponent}
     */
    class ColorPickerComponent extends DpForm['BaseComponent'] {
        /**
         * 组件初始化
         * @returns {Promise<void>}
         */
        async onInit() {
            let $this = $(this.element);
            let $colorIs = $this.find('input');
            const id = $colorIs.attr('id');
            let dataOptions = $colorIs.data('options') || {};

            Coloris({el: $colorIs[0]});
            Coloris.setInstance('#' + id, dataOptions);
            this.instance = $colorIs[0];
        }

        /**
         * 获取组件值
         * @returns {string} 颜色值
         */
        getValue() {
            if (!this.instance) return '';
            return this.instance.value;
        }

        /**
         * 设置组件值
         * @param {string} value - 颜色值
         */
        setValue(value) {
            if (!this.instance) return;
            this.instance.value = value || '';
            // 触发change事件以更新Coloris
            $(this.instance).trigger('input');
        }

        /**
         * 组件销毁
         * @returns {Promise<void>}
         */
        async onDestroy() {
            // Coloris自动清理，无需额外操作
        }
    }

    // ===========================================
    // DatetimePicker Component
    // ===========================================
    /**
     * DatetimePicker 组件
     * 基于 AirDatepicker 的日期时间选择器组件
     *
     * @class DatetimePickerComponent
     * @extends {BaseComponent}
     */
    class DatetimePickerComponent extends DpForm['BaseComponent'] {
        /**
         * 组件初始化
         * @returns {Promise<void>}
         * @throws {Error} 当元素或配置错误时抛出异常
         */
        async onInit() {
            try {
                // 验证依赖和元素
                this.validateElements();

                // 初始化配置
                this.initializeConfig();

                // 处理按钮配置
                this.processButtons();

                // 处理回调函数
                this.processCallbacks();

                // 处理功能属性
                this.processFunctionProps();

                // 处理导航标题
                this.processNavTitles();

                // 创建日期选择器实例
                this.createDatepicker();

            } catch (error) {
                this.handleError('日期选择器组件初始化失败', error);
                throw error;
            }
        }

        validateElements() {
            this.$container = $(this.element);
            this.$input = this.$container.find('input');

            if (this.$input.length === 0) {
                throw new Error('未找到日期输入框');
            }
        }

        initializeConfig() {
            // 获取配置参数
            const dataOptions = this.$input.data('options') || {};

            // 基础配置
            this.config = {
                locale: window['airDatePickerLocale'] ?? {},
                buttons: ['today', 'clear'],
                multipleDatesSeparator: ',',
                ...dataOptions
            };
        }

        processButtons() {
            if (!this.config.buttons) return;

            if (typeof this.config.buttons === 'object' && Array.isArray(this.config.buttons)) {
                this.config.buttons = this.config.buttons.map(button => this.processButton(button));
            } else if (typeof this.config.buttons === 'string' && typeof window[this.config.buttons] === "object") {
                this.config.buttons = window[this.config.buttons];
            }
        }

        processButton(button) {
            // 处理对象类型的按钮
            if (typeof button === "object") {
                return this.processObjectButton(button);
            }

            // 处理字符串类型的按钮
            return this.processStringButton(button);
        }

        processObjectButton(button) {
            // 已有有效的onClick函数
            if (typeof button.onClick === 'function') {
                return button;
            }

            // 尝试从window对象获取onClick函数
            if (typeof button.onClick === 'string' && typeof window[button.onClick] === "function") {
                return {
                    ...button,
                    onClick: window[button.onClick]
                };
            }

            // 创建默认按钮配置
            return {
                content: button.content ?? '未知按钮',
                className: button.className ?? '',
                onClick: () => {
                    console.warn('未定义按钮点击事件:', button);
                    alert('未定义按钮点击事件');
                }
            };
        }

        processStringButton(buttonName) {
            // 处理内联模式下的特殊按钮
            if (this.config.inline === true) {
                if (buttonName === 'today') {
                    return this.createTodayButton();
                }
                if (buttonName === 'clear') {
                    return this.createClearButton();
                }
            }

            // 从window对象获取按钮配置
            return typeof window[buttonName] === "object" ? window[buttonName] : buttonName;
        }

        createTodayButton() {
            return {
                content: '今天',
                onClick: (dp) => {
                    this.preventDefaultEvent();
                    dp.selectDate(new Date());
                }
            };
        }

        createClearButton() {
            return {
                content: '清除',
                onClick: (dp) => {
                    this.preventDefaultEvent();
                    dp.clear();
                }
            };
        }

        preventDefaultEvent() {
            const event = window.event;
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }
        }

        processCallbacks() {
            const callbacks = [
                'onSelect',
                'onBeforeSelect',
                'onChangeViewDate',
                'onChangeView',
                'onRenderCell',
                'onShow',
                'onHide',
                'onClickDayName',
                'onFocus'
            ];

            callbacks.forEach(callbackName => {
                this.processCallback(callbackName);
            });
        }

        processCallback(callbackName) {
            const callbackValue = this.config[callbackName];

            if (!callbackValue) {
                this.config[callbackName] = '';
                return;
            }

            if (typeof callbackValue === 'function') {
                // 已经是函数，保持不变
                return;
            }

            if (typeof callbackValue === 'string' && typeof window[callbackValue] === 'function') {
                this.config[callbackName] = window[callbackValue];
                return;
            }

            // 无效的回调函数
            console.warn(`无效的回调函数: ${callbackName}`, callbackValue);
            this.config[callbackName] = '';
        }

        processFunctionProps() {
            const functionProps = [
                'position',
                'dateFormat',
                'altFieldDateFormat'
            ];

            functionProps.forEach(propName => {
                this.processFunctionProp(propName);
            });
        }

        processFunctionProp(propName) {
            const propValue = this.config[propName];

            if (typeof propValue === 'string' && typeof window[propValue] === 'function') {
                this.config[propName] = window[propValue];
            }
        }

        processNavTitles() {
            if (!this.config.navTitles || typeof this.config.navTitles !== 'object') {
                return;
            }

            for (const key in this.config.navTitles) {
                if (!this.config.navTitles.hasOwnProperty(key)) continue;

                const value = this.config.navTitles[key];

                if (typeof value === 'string' && typeof window[value] === 'function') {
                    this.config.navTitles[key] = window[value];
                }
            }
        }

        createDatepicker() {
            if (!this.$input || this.$input.length === 0) {
                throw new Error('无法创建日期选择器：输入框元素无效');
            }

            try {
                this.instance = new AirDatepicker(this.$input[0], this.config);

                // 触发初始化完成事件
                this.emit('initialized', this.instance);
            } catch (error) {
                throw new Error(`AirDatepicker初始化失败: ${error.message}`);
            }
        }

        /**
         * 获取组件值
         * @returns {string} 日期字符串
         */
        getValue() {
            return this.$input ? this.$input.val() : '';
        }

        /**
         * 设置组件值
         * @param {string|Array<string>|Date} value - 要设置的日期值
         * @returns {DatetimePickerComponent} this（支持链式调用）
         */
        setValue(value) {
            if (!this.instance) return this;

            try {
                if (Array.isArray(value)) {
                    // 多选日期
                    this.instance.selectDate(value);
                } else if (value) {
                    // 单个日期
                    this.instance.selectDate(new Date(value));
                } else {
                    // 清空日期
                    this.instance.clear();
                }

                this.emit('setValue', value);
            } catch (error) {
                this.handleError('设置日期值失败', error);
            }

            return this;
        }

        /**
         * 显示日期选择器
         * @returns {DatetimePickerComponent} this（支持链式调用）
         */
        show() {
            if (this.instance) {
                this.instance.show();
            }
            return this;
        }

        /**
         * 隐藏日期选择器
         * @returns {DatetimePickerComponent} this（支持链式调用）
         */
        hide() {
            if (this.instance) {
                this.instance.hide();
            }
            return this;
        }

        /**
         * 清空选中的日期
         * @returns {DatetimePickerComponent} this（支持链式调用）
         */
        clear() {
            if (this.instance) {
                this.instance.clear();
            }
            return this;
        }

        destroy() {
            if (this.instance) {
                this.instance.destroy();
                this.instance = null;
            }
            return this;
        }

        // 获取选中的日期
        getSelectedDates() {
            return this.instance ? this.instance.selectedDates : [];
        }

        // 设置日期范围
        setDateRange(startDate, endDate) {
            if (!this.instance) return this;

            try {
                this.instance.selectDate([new Date(startDate), new Date(endDate)]);
                this.emit('dateRangeSet', {startDate, endDate});
            } catch (error) {
                this.handleError('设置日期范围失败', error);
            }

            return this;
        }

        // 验证日期
        validate() {
            const value = this.getValue();
            const required = this.$input.prop('required');

            const errors = [];

            // 必填验证
            if (required && !value) {
                errors.push('日期不能为空');
            }

            // 日期格式验证
            if (value && !this.isValidDate(value)) {
                errors.push('日期格式无效');
            }

            const isValid = errors.length === 0;
            const result = {valid: isValid, errors};

            this.emit('validate', result);
            return result;
        }

        // 简单的日期验证
        isValidDate(dateString) {
            if (!dateString) return false;

            const date = new Date(dateString);
            return date instanceof Date && !isNaN(date);
        }

        // 工具方法
        handleError(context, error) {
            const fullMessage = `${context}: ${error.message || error}`;

            Dolphin.error(fullMessage);

            // 触发错误事件
            this.emit('error', {context, error, message: fullMessage});
        }

        /**
         * 组件销毁
         * @returns {Promise<void>}
         */
        async onDestroy() {
            try {
                // 销毁AirDatepicker实例
                if (this.instance) {
                    this.instance.destroy();
                }

                // 清理引用
                this.instance = null;
                this.$input = null;
                this.$container = null;
                this.config = null;

            } catch (error) {
                console.warn('Error during DatetimePickerComponent destruction:', error);
            }
        }
    }

    // ===========================================
    // ImageUpload Component
    // ===========================================
    /**
     * ImageUpload 组件
     * 图片上传组件，支持多文件上传、预览、拖拽排序等功能
     *
     * @class ImageUploadComponent
     * @extends {UploadComponentBase}
     */
    class ImageUploadComponent extends DpForm['UploadComponentBase'] {
        /**
         * 获取上传类型
         * @returns {string} 上传类型
         */
        getUploadType() {
            return 'image';
        }

        /**
         * 获取项目选择器
         * @returns {string} CSS选择器
         */
        getItemSelector() {
            return '.dp-form-upload-image-item';
        }

        /**
         * 获取删除按钮选择器
         * @returns {string} CSS选择器
         */
        getDeleteButtonSelector() {
            return '.dp-form-upload-button-delete';
        }

        /**
         * 获取默认配置
         * @returns {Object} 默认配置
         */
        getDefaultConfig() {
            const baseConfig = super.getDefaultConfig();
            return {
                ...baseConfig,
                allowedTypes: ['image/*'],
                customUI: true
            };
        }

        /**
         * 获取Sortable配置
         * @returns {Object} Sortable配置
         */
        getSortableOptions() {
            return {
                animation: 150,
                draggable: '.dp-form-upload-image-item',
                onEnd: () => {
                    if (typeof refreshFsLightbox === 'function') {
                        refreshFsLightbox();
                    }
                }
            };
        }

        /**
         * 初始化DOM元素
         */
        initDOMElements() {
            this.target = this.getCachedElement('.dp-form-upload-image');
            this.btnAdd = this.getCachedElement('.dp-form-upload-image-item-add');
            this.btnBrowser = this.getCachedElement('.dp-form-upload-image-item-select');
            this.btnClear = this.getCachedElement('.dp-form-upload-image-item-clear');
            this.items = this.getCachedElement('.dp-form-upload-image-items');
        }

        /**
         * 绑定上传器事件
         */
        bindUploaderEvents() {
            const dir = $(this.target).data('dir');

            // 文件添加事件
            this.element[0].addEventListener('uploader:fileAdded', (e) => {
                const [fileItem, fileData] = e.detail;

                // 设置上传目录，用于在驱动中拼接文件路径
                fileItem.dir = dir;

                // 文件已被添加，需要减去当前文件进行检查
                const currentCount = this.getCurrentCount();
                const maxFiles = this.instance.options.maxFiles;

                if (maxFiles && maxFiles !== 0 && maxFiles !== Infinity && currentCount >= maxFiles) {
                    // 超限时自动移除文件
                    this.instance.removeFile(fileData.id);
                    Dolphin.error(`最多只能上传 ${maxFiles} 张图片`);
                    return;
                }

                this.showPreview(fileData.id, fileData.preview.url);
                this.instance.upload();
            });

            // 文件上传前事件
            this.element[0].addEventListener('uploader:beforeUpload', (e) => {
                const [fileItem] = e.detail;
                this.setStatus(fileItem.id, 'uploading');
            });

            // 文件上传成功事件
            this.element[0].addEventListener('uploader:uploadSuccess', (e) => {
                const [fileItem, result] = e.detail;
                this.setStatus(fileItem.id, '', result.data.success[0].id);
                Dolphin.notify('上传成功', 'success');
                this.emit('change', this.getValue());
            });

            // 文件上传失败事件
            this.element[0].addEventListener('uploader:uploadError', (e) => {
                const [fileItem, data] = e.detail;
                this.setStatus(fileItem.id, 'error');
                Dolphin.error(data.message);
            });

            // 上传进度
            this.element[0].addEventListener('uploader:uploadProgress', (e) => {
                const [, progressData] = e.detail;
                const $progress = $(this.target).find('#dp-form-upload-image-item-' + progressData.id).find('.progress-bar');
                $progress.css('width', progressData.progress + '%');
            });
        }

        /**
         * 绑定DOM事件
         */
        bindDOMEvents() {
            // 删除文件事件委托
            this.element.on('click', '.dp-form-upload-button-delete', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.removeFile(e);
            });

            // 重试上传事件委托
            this.element.on('click', '.dp-form-upload-button-retry', (e) => {
                e.preventDefault();
                e.stopPropagation();

                const $retryBtn = $(e.currentTarget);
                const fileId = $retryBtn.data('id');

                if (fileId && this.instance) {
                    this.instance.retryUpload(fileId);
                    Dolphin.notify('开始重试上传', 'info');
                }
            });

            // 清空事件委托
            this.element.on('click', '.dp-form-upload-image-item-clear', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.clearAllFiles();
            });

            // 图片浏览器
            Dolphin.browser(this.btnBrowser, {
                type: 'image',
                title: '选择图片',
                multiple: this.instance.options.multiple
            }, (data) => {
                const currentCount = this.getCurrentCount();
                const maxFiles = this.instance.options.maxFiles;
                const newImageCount = this.instance.options.multiple ? Object.keys(data).length : 1;

                if (maxFiles && maxFiles !== 0 && maxFiles !== Infinity && currentCount + newImageCount > maxFiles) {
                    Dolphin.error(`最多只能上传 ${maxFiles} 张图片`);
                    return;
                }

                // 插入预览图片
                if (this.instance.options.multiple) {
                    Object.keys(data).forEach((key) => {
                        let id = Dolphin.randStr();
                        this.showPreview(id, data[key]['url']).then(() => {
                            this.setStatus(id, '', data[key]['id']);
                        });
                    });
                } else {
                    let id = Dolphin.randStr();
                    this.showPreview(id, data['url']).then(() => {
                        this.setStatus(id, '', data['id']);
                    });
                }
            });
        }

        /**
         * 显示预览
         * @param {string} id 文件ID
         * @param {string} imageUrl 图片URL
         * @returns {Promise<void>}
         */
        showPreview(id, imageUrl) {
            return new Promise((resolve, reject) => {
                try {
                    // 参数验证
                    if (!id || !imageUrl) {
                        throw new Error('ID和图片URL不能为空');
                    }

                    // 安全地处理参数
                    const safeId = this.sanitizeId(id);
                    const safeImageUrl = this.sanitizeUrl(imageUrl);

                    // 创建预览元素
                    const previewElement = this.createPreviewElement(safeId, safeImageUrl);

                    // 安全地插入DOM
                    const $btnItem = $(this.btnAdd || this.btnBrowser);
                    $btnItem.before(previewElement);

                    // 刷新lightbox
                    this.refreshLightbox();

                    // 更新UI状态
                    if (!this.instance.options.multiple) {
                        if (this.btnAdd) $(this.btnAdd).hide();
                        if (this.btnBrowser) $(this.btnBrowser).hide();
                    }

                    // 初始化tooltip
                    const $item = $('#dp-form-upload-image-item-' + safeId);
                    $item.find('[data-bs-toggle="tooltip"]').tooltip();

                    // 更新清空按钮显示状态
                    this.updateClearButtonVisibility();

                    resolve();

                } catch (error) {
                    this.handleError('显示预览失败', error);
                    reject(error);
                }
            });
        }

        /**
         * 安全地处理URL
         * @param {string} url 原始URL
         * @returns {string} 安全的URL
         */
        sanitizeUrl(url) {
            try {
                // 基本的URL验证
                const urlObj = new URL(url, window.location.origin);

                // 只允许http/https/data/blob协议
                if (!['http:', 'https:', 'data:', 'blob:'].includes(urlObj.protocol)) {
                    throw new Error('不支持的URL协议');
                }

                return urlObj.href;
            } catch (error) {
                console.warn('URL验证失败:', error);
                return '#'; // 返回安全的默认值
            }
        }

        /**
         * 创建预览元素
         * @param {string} safeId 安全的ID
         * @param {string} safeImageUrl 安全的图片URL
         * @returns {HTMLElement} 预览元素
         */
        createPreviewElement(safeId, safeImageUrl) {
            const container = document.createElement('div');
            container.className = 'dp-form-upload-image-item checking';
            container.id = `dp-form-upload-image-item-${safeId}`;

            // 创建隐藏输入框
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = this.target.dataset.name || '';
            hiddenInput.value = '';

            // 创建链接元素
            const link = document.createElement('a');
            link.setAttribute('data-fslightbox', `dp-gallery-${this.target.dataset.id || ''}`);
            link.href = safeImageUrl;

            // 创建图片容器
            const imgContainer = document.createElement('div');
            imgContainer.className = 'img-responsive img-responsive-1x1 rounded border';
            imgContainer.style.backgroundImage = `url("${safeImageUrl}")`;

            // 创建进度条
            const progressContainer = document.createElement('div');
            progressContainer.className = 'progress';

            const progressBar = document.createElement('div');
            progressBar.className = 'progress-bar';
            progressBar.style.width = '0';
            progressBar.setAttribute('role', 'progressbar');

            progressContainer.appendChild(progressBar);
            imgContainer.appendChild(progressContainer);
            link.appendChild(imgContainer);

            // 创建删除按钮
            const deleteBtn = document.createElement('span');
            deleteBtn.className = 'dp-form-upload-button-delete';
            deleteBtn.setAttribute('data-id', safeId);
            deleteBtn.setAttribute('data-bs-toggle', 'tooltip');
            deleteBtn.setAttribute('data-bs-placement', 'top');
            deleteBtn.setAttribute('title', '删除');
            deleteBtn.innerHTML = '<i class="fas fa-times-circle"></i>';

            // 创建重试按钮
            const retryBtn = document.createElement('div');
            retryBtn.className = 'dp-form-upload-button-retry';
            retryBtn.setAttribute('data-id', safeId);
            retryBtn.setAttribute('data-bs-toggle', 'tooltip');
            retryBtn.setAttribute('data-bs-placement', 'top');
            retryBtn.setAttribute('title', '重新上传');
            retryBtn.innerHTML = '<i class="fas fa-redo"></i>';

            // 创建加载指示器
            const loadingIndicator = document.createElement('div');
            loadingIndicator.className = 'dp-form-upload-image-loading';
            loadingIndicator.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i>';

            // 组装元素
            container.appendChild(hiddenInput);
            container.appendChild(link);
            container.appendChild(deleteBtn);
            container.appendChild(retryBtn);
            container.appendChild(loadingIndicator);

            return container;
        }

        /**
         * 刷新lightbox
         */
        refreshLightbox() {
            if (typeof refreshFsLightbox === 'function') {
                refreshFsLightbox();
            }
        }


        /**
         * 设置状态
         * @param {string} id - 文件ID
         * @param {string} status - 状态
         * @param {string} value - 值
         */
        setStatus(id, status = '', value = '') {
            $(this.target).find('#dp-form-upload-image-item-' + id)
                .removeClass()
                .addClass('dp-form-upload-image-item')
                .addClass(status)
                .find('input[type=hidden]').val(value);
        }

        /**
         * 设置单个值项
         * @param {string|Object} item 值项
         */
        async setValueItem(item) {
            try {
                let imageUrl, imageId;

                if (typeof item === 'string') {
                    // 假设是图片URL
                    imageUrl = item;
                    imageId = item;
                } else if (typeof item === 'object' && item !== null) {
                    imageUrl = item.url || item.path || item.src;
                    imageId = item.id || item.value;
                } else {
                    throw new Error('不支持的值类型');
                }

                if (!imageUrl) {
                    throw new Error('无效的图片URL');
                }

                // 生成唯一ID
                const id = this.generateUniqueId();

                // 显示预览
                await this.showPreview(id, imageUrl);

                // 设置值
                this.setStatus(id, '', imageId);

            } catch (error) {
                console.warn('设置值项失败:', error);
            }
        }

        /**
         * 删除文件（扩展基类方法以处理图片特定逻辑）
         * @param {Event} e - 点击事件对象
         */
        removeFile(e) {
            try {
                const $deleteBtn = $(e.currentTarget);
                const fileId = $deleteBtn.data('id');
                const $item = $deleteBtn.closest('.dp-form-upload-image-item');

                // 移除DOM元素
                $item.find('[data-bs-toggle="tooltip"]').tooltip('hide');
                $item.remove();

                // 从上传器中移除文件
                if (this.instance && typeof this.instance.removeFile === 'function' && fileId) {
                    this.instance.cancelUpload(fileId);
                    this.instance.removeFile(fileId);
                }

                // 如果是单文件模式，重新显示添加按钮
                if (!this.instance.options.multiple) {
                    if (this.btnAdd) $(this.btnAdd).show();
                    if (this.btnBrowser) $(this.btnBrowser).show();
                }

                // 触发变化事件
                this.emit('change', this.getValue());

                // 更新清空按钮显示状态
                this.updateClearButtonVisibility();

                Dolphin.notify('文件删除成功', 'success');

            } catch (error) {
                console.error('删除文件失败:', error);
                Dolphin.error('删除文件失败');
            }
        }
    }

    // ===========================================
    // Vditor Component
    // ===========================================
    /**
     * Vditor 组件
     * 基于 Vditor 的 Markdown 编辑器组件，支持多驱动上传
     *
     * @class VditorComponent
     * @extends {BaseComponent}
     */
    class VditorComponent extends DpForm['BaseComponent'] {
        /**
         * 构造函数
         * @param {...any} args - 构造参数
         */
        constructor(...args) {
            super(...args);

            // 驱动适配器实例
            this.driverAdapter = null;
            // 清理函数队列
            this.cleanupQueue = [];
        }

        /**
         * 组件初始化
         * @returns {Promise<void>}
         * @throws {Error} 当初始化失败时抛出异常
         */
        async onInit() {
            try {
                const $this = $(this.element).find('.dp-form-vditor');
                const id = $this.attr('id');
                const $options = $this.data('options');
                const driverName = $this.data('driver') || 'local';
                const $textarea = $(this.element).find('textarea');

                // 验证必需元素
                if (!$this.length || !id) {
                    throw new Error('Vditor container or ID not found');
                }

                // 获取上传目录配置
                const uploadDir = $this.data('dir') || '';

                let options = {
                    height: 360,
                    placeholder: '请输入内容',
                    input: (value) => {
                        $textarea.val(value);
                        this.emit('change', value);
                    },
                    after: () => {
                        this.instance.setValue($textarea.val());
                        this.emit('ready', this.instance);
                    },
                    cache: {
                        enable: false
                    },
                    cdn: '/static/libs/vditor',
                }

                // 合并用户配置
                $.extend(options, $options);

                // 处理回调函数
                this._processCallbacks(options);

                // 处理自定义工具栏
                this._processToolbar(options);

                // 配置多驱动上传支持
                await this._setupMultiDriverUpload(options, driverName, uploadDir);

                // 创建Vditor实例
                this.instance = new Vditor(id, options);

                // 保存相关引用
                this.$container = $this;
                this.$textarea = $textarea;
                this.driverName = driverName;
                this.uploadDir = uploadDir;

                this.emit('initialized', this.instance);
            } catch (error) {
                this.handleError('Vditor组件初始化失败', error);
                throw error;
            }
        }

        /**
         * 手动插入文件到编辑器
         * @param succMap
         * @private
         */
        _insertUploadedFiles(succMap) {
            if (!this.instance) {
                console.warn('Vditor实例不存在，无法插入文件');
                return;
            }

            let insertText = '';

            // 为每个成功上传的文件生成Markdown
            for (const [fileName, fileUrl] of Object.entries(succMap)) {
                const fileExt = fileName.split('.').pop().toLowerCase();

                if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'].includes(fileExt)) {
                    // 图片文件
                    insertText += `![${fileName}](${fileUrl})\n`;
                } else if (['mp4', 'webm', 'ogg'].includes(fileExt)) {
                    // 视频文件
                    insertText += `<video controls>\n  <source src="${fileUrl}" type="video/${fileExt}">\n
            + 您的浏览器不支持视频播放。\n</video>\n\n`;
                } else if (['mp3', 'wav', 'flac', 'aac'].includes(fileExt)) {
                    // 音频文件
                    insertText += `<audio controls>\n  <source src="${fileUrl}" type="audio/${fileExt}">\n
            + 您的浏览器不支持音频播放。\n</audio>\n\n`;
                } else {
                    // 其他文件类型
                    insertText += `[${fileName}](${fileUrl})\n`;
                }
            }

            // 插入到编辑器当前光标位置
            if (insertText) {
                try {
                    this.instance.insertValue(insertText);
                } catch (error) {
                    console.error('插入文件到编辑器失败:', error);
                }
            }
        }

        /**
         * 配置多驱动上传支持
         */
        async _setupMultiDriverUpload(options, driverName, uploadDir) {
            try {
                // 先尝试智能查找驱动（支持命名空间）
                const driverInfo = Dolphin.uploader ? Dolphin.uploader.find(driverName, 'vditor') : null;
                const actualDriver = driverInfo ? driverInfo.driver : null;

                if (actualDriver) {
                    // 获取容器元素，注意这里$container可能还没初始化
                    const containerElement = this.element ? this.element[0] : document.createElement('div');

                    // 创建适配器实例
                    this.driverAdapter = Dolphin.uploader.create({
                        element: containerElement,
                        options: { extraData: {} },
                        handleUploadError: (fileItem, error) => {
                            console.error('Vditor上传失败:', error);
                        }
                    }, driverName, 'vditor');

                    // 让驱动接管Vditor的upload处理方法
                    this._setupDriverMethods(actualDriver, options, uploadDir);
                } else {
                    console.warn(`[VditorComponent] 驱动 "${driverName}" 未找到，将使用回退方案`);

                    // 设置默认上传配置
                    if (!options.upload) {
                        options.upload = {};
                    }
                    if (!options.upload.url) {
                        options.upload.url = DolphinConfig?.url?.upload || '/admin/api/upload';
                    }
                }

                this.emit('uploadConfigured', { driver: driverName, hasAdapter: !!this.driverAdapter });

            } catch (error) {
                console.warn('配置多驱动上传失败:', error);
            }
        }

        /**
         * 让驱动接管Vditor的upload处理方法
         * @param {Object} driver 驱动对象
         * @param {Object} options Vditor选项
         * @param {string} uploadDir 上传目录
         */
        _setupDriverMethods(driver, options, uploadDir) {
            // 1. 检查并设置handler方法
            if (typeof driver.handler === 'function') {
                // 保存原有的format方法（如果有）
                const originalHandler = options.upload.handler;

                // 绑定上下文并设置handler
                options.upload.handler = async (files) => {
                    try {
                        // 调用驱动的handler，保持驱动的this上下文，传递组件上下文
                        return await driver.handler.call(driver, files, {
                            uploadDir: uploadDir,
                            options: options.upload,
                            vditor: this.instance,
                            driverAdapter: this.driverAdapter,
                            component: this,
                            originalHandler: originalHandler,
                            insertFiles: (succMap) => this._insertUploadedFiles(succMap)
                        });
                    } catch (error) {
                        return `上传失败: ${error.message}`;
                    }
                };
            }

            // 2. 检查并设置format方法
            if (typeof driver.format === 'function') {
                // 保存原有的format方法（如果有）
                const originalFormat = options.upload.format;

                options.upload.format = (files, responseText) => {
                    try {
                        // 调用驱动的format方法
                        return driver.format.call(driver, files, responseText, {
                            uploadDir: uploadDir,
                            vditor: this.instance,
                            driverAdapter: this.driverAdapter,
                            component: this,
                            originalFormat: originalFormat
                        });
                    } catch (error) {
                        // 回退到原有format方法或默认处理
                        if (originalFormat) {
                            return originalFormat(files, responseText);
                        } else {
                            return this._processUploadFormat(files, responseText);
                        }
                    }
                };
            }

            // 3. 检查并设置file方法
            if (typeof driver.file === 'function') {
                options.upload.file = async (files) => {
                    try {
                        // 调用驱动的file方法
                        return await driver.file.call(driver, files, {
                            uploadDir: uploadDir,
                            vditor: this.instance,
                            driverAdapter: this.driverAdapter,
                            component: this
                        });
                    } catch (error) {
                        // 返回原始文件数组
                        return files;
                    }
                };
            }

            // 4. 检查并设置success方法
            if (typeof driver.success === 'function') {
                // 保存原有的success方法（如果有）
                const originalSuccess = options.upload.success;

                options.upload.success = (editorElement, responseText) => {
                    try {
                        // 调用驱动的success方法
                        return driver.success.call(driver, editorElement, responseText, {
                            uploadDir: uploadDir,
                            vditor: this.instance,
                            driverAdapter: this.driverAdapter,
                            component: this,
                            originalSuccess: originalSuccess,
                            insertFiles: (succMap) => this._insertUploadedFiles(succMap)
                        });
                    } catch (error) {
                        // 回退到原有success方法（如果有）
                        if (originalSuccess) {
                            return originalSuccess(editorElement, responseText);
                        }
                    }
                };
            }

            // 5. 检查并设置error方法
            if (typeof driver.error === 'function') {
                // 保存原有的error方法（如果有）
                const originalError = options.upload.error;

                options.upload.error = (responseText) => {
                    try {
                        // 调用驱动的error方法
                        return driver.error.call(driver, responseText, {
                            uploadDir: uploadDir,
                            vditor: this.instance,
                            driverAdapter: this.driverAdapter,
                            component: this,
                            originalError: originalError
                        });
                    } catch (error) {
                        // 回退到原有error方法或默认处理
                        if (originalError) {
                            return originalError(responseText);
                        } else {
                            console.error('上传错误:', responseText);
                        }
                    }
                };
            }

            // 6. 检查并设置validate方法
            if (typeof driver.validate === 'function') {
                // 保存原有的validate方法（如果有）
                const originalValidate = options.upload.validate;

                options.upload.validate = (files) => {
                    try {
                        // 调用驱动的validate方法
                        return driver.validate.call(driver, files, {
                            uploadDir: uploadDir,
                            vditor: this.instance,
                            driverAdapter: this.driverAdapter,
                            component: this,
                            originalValidate: originalValidate
                        });
                    } catch (error) {
                        // 回退到原有validate方法（如果有）
                        if (originalValidate) {
                            return originalValidate(files);
                        }
                        // 默认验证通过
                        return true;
                    }
                };
            }

            // 7. 检查并设置cancel方法
            if (typeof driver.cancel === 'function') {
                // 保存原有的cancel方法（如果有）
                const originalCancel = options.upload.cancel;

                options.upload.cancel = (files) => {
                    try {
                        // 调用驱动的cancel方法
                        return driver.cancel.call(driver, files, {
                            uploadDir: uploadDir,
                            vditor: this.instance,
                            driverAdapter: this.driverAdapter,
                            component: this,
                            originalCancel: originalCancel
                        });
                    } catch (error) {
                        // 回退到原有cancel方法（如果有）
                        if (originalCancel) {
                            return originalCancel();
                        }
                    }
                };
            }

            // 8. 检查并设置setHeaders方法
            if (typeof driver.setHeaders === 'function') {
                // 保存原有的setHeaders方法（如果有）
                const originalSetHeaders = options.upload.setHeaders;

                options.upload.setHeaders = () => {
                    try {
                        // 调用驱动的setHeaders方法
                        return driver.setHeaders.call(driver, {
                            uploadDir: uploadDir,
                            vditor: this.instance,
                            driverAdapter: this.driverAdapter,
                            component: this,
                            originalSetHeaders: originalSetHeaders
                        });
                    } catch (error) {
                        // 回退到原有setHeaders方法或返回空对象
                        if (originalSetHeaders) {
                            return originalSetHeaders();
                        } else {
                            return {};
                        }
                    }
                };
            }

            // 9. 检查并设置renderLinkDest方法
            if (typeof driver.renderLinkDest === 'function') {
                // 保存原有的renderLinkDest方法（如果有）
                const originalRenderLinkDest = options.upload.renderLinkDest;

                options.upload.renderLinkDest = (value) => {
                    try {
                        // 调用驱动的renderLinkDest方法
                        return driver.renderLinkDest.call(driver, value, {
                            uploadDir: uploadDir,
                            vditor: this.instance,
                            driverAdapter: this.driverAdapter,
                            component: this,
                            originalRenderLinkDest: originalRenderLinkDest
                        });
                    } catch (error) {
                        // 回退到原有方法或返回原值
                        if (originalRenderLinkDest) {
                            return originalRenderLinkDest(value);
                        } else {
                            return value;
                        }
                    }
                };
            }

            // 10. 检查并设置linkToImgFormat方法
            if (typeof driver.linkToImgFormat === 'function') {
                // 保存原有的linkToImgFormat方法（如果有）
                const originalLinkToImgFormat = options.upload.linkToImgFormat;

                options.upload.linkToImgFormat = (url) => {
                    try {
                        // 调用驱动的linkToImgFormat方法
                        return driver.linkToImgFormat.call(driver, url, {
                            uploadDir: uploadDir,
                            vditor: this.instance,
                            driverAdapter: this.driverAdapter,
                            component: this,
                            originalLinkToImgFormat: originalLinkToImgFormat
                        });
                    } catch (error) {
                        // 回退到原有方法或默认处理
                        if (originalLinkToImgFormat) {
                            return originalLinkToImgFormat(url);
                        } else {
                            // 默认格式化：返回Vditor期望的格式
                            return JSON.stringify({
                                msg: '',
                                code: 0,
                                data: {
                                    originalURL: url,
                                    url: url
                                }
                            });
                        }
                    }
                };
            }

            // 11. 检查并设置linkToImgCallback方法
            if (typeof driver.linkToImgCallback === 'function') {
                // 保存原有的linkToImgCallback方法（如果有）
                const originalLinkToImgCallback = options.upload.linkToImgCallback;

                options.upload.linkToImgCallback = (responseText) => {
                    try {
                        // 调用驱动的linkToImgCallback方法
                        return driver.linkToImgCallback.call(driver, responseText, {
                            uploadDir: uploadDir,
                            vditor: this.instance,
                            driverAdapter: this.driverAdapter,
                            component: this,
                            originalLinkToImgCallback: originalLinkToImgCallback
                        });
                    } catch (error) {
                        // 回退到原有方法（如果有）
                        if (originalLinkToImgCallback) {
                            return originalLinkToImgCallback(responseText);
                        }
                    }
                };
            }
        }

        /**
         * 处理回调函数引用
         */
        _processCallbacks(options) {
            const callbacks = ['input', 'focus', 'blur', 'keydown', 'esc', 'ctrlEnter', 'select', 'unSelect'];
            callbacks.forEach(callback => {
                if (options[callback] && typeof options[callback] === 'string') {
                    if (typeof window[options[callback]] === 'function') {
                        options[callback] = window[options[callback]];
                    } else {
                        delete options[callback];
                    }
                }
            });
        }

        /**
         * 对服务端返回的数据进行转换
         * @param files
         * @param responseText
         * @returns {string}
         * @private
         */
        _processUploadFormat(files, responseText) {
            const response = JSON.parse(responseText);
            let errFiles = [];
            let succMap = {};

            response.data.error.forEach(item => {
                errFiles.push(item.name)
            })
            response.data.success.forEach(item => {
                succMap[item.name] = item.url;
            })

            response.data = {
                "errFiles": errFiles,
                "succMap": succMap
            }

            return JSON.stringify(response)
        }

        /**
         * 处理自定义工具栏
         */
        _processToolbar(options) {
            if (options.toolbar && options.toolbar.length > 0) {
                options.toolbar.forEach(item => {
                    if (item['click'] && typeof window[item['click']] === 'function') {
                        item['click'] = window[item['click']];
                    } else {
                        delete item['click'];
                    }
                })
            }

            // 处理所见即所得模式的自定义工具栏
            if (!options.customWysiwygToolbar) {
                options.customWysiwygToolbar = (toolbar) => {
                    // 返回默认的工具栏配置，或根据当前配置进行自定义
                    if (options.toolbar && options.toolbar.length > 0) {
                        return options.toolbar;
                    }
                    return toolbar; // 返回默认工具栏
                };
            } else if (typeof options.customWysiwygToolbar === 'string' && typeof window[options.customWysiwygToolbar] === 'function') {
                // 如果是字符串引用，转换为实际函数
                options.customWysiwygToolbar = window[options.customWysiwygToolbar];
            }
        }

        /**
         * 获取组件值
         * @returns {string} Markdown 内容
         */
        getValue() {
            return this.instance ? this.instance.getValue() : (this.$textarea ? this.$textarea.val() : '');
        }

        /**
         * 设置组件值
         * @param {string} value - Markdown 内容
         * @returns {VditorComponent} this（支持链式调用）
         */
        setValue(value) {
            if (this.instance) {
                this.instance.setValue(value);
            }
            if (this.$textarea) {
                this.$textarea.val(value);
            }
            this.emit('setValue', value);
            return this;
        }

        /**
         * 错误处理
         * @private
         * @param {string} message - 错误消息
         * @param {Error} error - 错误对象
         */
        handleError(message, error) {
            this.emit('error', error);

            if (window.Dolphin && typeof window.Dolphin.error === 'function') {
                Dolphin.error(message);
            }
        }

        /**
         * 销毁组件
         * @returns {Promise<void>}
         */
        async onDestroy() {
            try {
                // 销毁Vditor实例
                if (this.instance && typeof this.instance.destroy === 'function') {
                    this.instance.destroy();
                }

                // 销毁驱动适配器
                if (this.driverAdapter && typeof this.driverAdapter.destroy === 'function') {
                    this.driverAdapter.destroy();
                }

                // 执行清理队列
                this.cleanupQueue.forEach(cleanup => {
                    try {
                        cleanup();
                    } catch (error) {
                        console.warn('清理操作失败:', error);
                    }
                });

                // 清理引用
                this.instance = null;
                this.driverAdapter = null;
                this.$container = null;
                this.$textarea = null;
                this.driverName = null;
                this.uploadDir = null;
                this.cleanupQueue = null;

            } catch (error) {
                console.warn('VditorComponent destroy failed:', error);
            }

            // 调用父类销毁方法
            if (super.onDestroy) {
                await super.onDestroy();
            }
        }
    }

    // ===========================================
    // FileUpload Component
    // ===========================================
    /**
     * FileUpload 组件
     * 文件上传组件，支持多文件上传、预览、拖拽排序等功能
     *
     * @class FileUploadComponent
     * @extends {UploadComponentBase}
     */
    class FileUploadComponent extends DpForm['UploadComponentBase'] {
        /**
         * 获取上传类型
         * @returns {string} 上传类型
         */
        getUploadType() {
            return 'file';
        }

        /**
         * 获取项目选择器
         * @returns {string} CSS选择器
         */
        getItemSelector() {
            return '.dp-form-upload-file-item';
        }

        /**
         * 获取删除按钮选择器
         * @returns {string} CSS选择器
         */
        getDeleteButtonSelector() {
            return '.dp-form-upload-file-delete';
        }

        /**
         * 获取默认配置
         * @returns {Object} 默认配置
         */
        getDefaultConfig() {
            const baseConfig = super.getDefaultConfig();
            return {
                ...baseConfig,
                allowedTypes: ['*'],
                customUI: true
            };
        }

        /**
         * 获取Sortable配置
         * @returns {Object} Sortable配置
         */
        getSortableOptions() {
            return {
                animation: 150,
                handle: '.dp-form-upload-file-item-handle',
                draggable: '.dp-form-upload-file-item'
            };
        }

        /**
         * 初始化DOM元素
         */
        initDOMElements() {
            this.target = this.getCachedElement('.dp-form-upload-file');
            this.btnAdd = this.getCachedElement('.dp-form-upload-file-item-add');
            this.btnBrowser = this.getCachedElement('.dp-form-upload-file-item-select');
            this.btnClear = this.getCachedElement('.dp-form-upload-file-item-clear');
            this.items = this.getCachedElement('.dp-form-upload-file-items');
        }

        /**
         * 绑定上传器事件
         */
        bindUploaderEvents() {
            const dir = $(this.target).data('dir');

            // 文件添加事件
            this.element[0].addEventListener('uploader:fileAdded', (e) => {
                const [fileItem, fileData] = e.detail;

                // 设置上传目录，用于在驱动中拼接文件路径
                fileItem.dir = dir;

                // 文件已被添加，需要减去当前文件进行检查
                const currentCount = this.getCurrentCount();
                const maxFiles = this.instance.options.maxFiles;

                if (maxFiles && maxFiles !== 0 && maxFiles !== Infinity && currentCount >= maxFiles) {
                    // 超限时自动移除文件
                    this.instance.removeFile(fileData.id);
                    Dolphin.error(`最多只能上传 ${maxFiles} 个文件`);
                    return;
                }

                this.showPreview(fileData);
                this.instance.upload();
            });

            // 文件上传前事件
            this.element[0].addEventListener('uploader:beforeUpload', (e) => {
                const [fileItem] = e.detail;
                this.setStatus(fileItem.id, 'uploading', '', '上传中...');
            });

            // 上传进度
            this.element[0].addEventListener('uploader:uploadProgress', (e) => {
                const [fileItem, progressData] = e.detail;
                const $progress = $(this.target).find('#dp-form-upload-file-item-' + progressData.id).find('.progress-bar');
                $progress.css('width', progressData.progress + '%');
                this.setStatus(fileItem.id, 'uploading', '', parseInt(progressData.progress) + '%');
            });

            // 文件上传成功事件
            this.element[0].addEventListener('uploader:uploadSuccess', (e) => {
                const [fileItem, result] = e.detail;
                // 驱动逻辑已移交适配器处理，这里只关心UI和状态
                this.setStatus(fileItem.id, 'success', result.data.success[0].id, '上传成功');
                this.setDownloadUrl(fileItem.id, result.data.success[0]);
                // 触发change事件，通知表单值已更新
                this.emit('change', this.getValue());
            });

            // 文件上传失败事件
            this.element[0].addEventListener('uploader:uploadError', (e) => {
                const [fileItem, data] = e.detail;
                this.setStatus(fileItem.id, 'error', '', '上传失败', data.message);
            });
        }

        /**
         * 绑定DOM事件
         */
        bindDOMEvents() {
            // 删除文件事件委托
            this.element.on('click', '.dp-form-upload-file-delete', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.removeFile(e);
            });

            // 清空事件委托
            this.element.on('click', '.dp-form-upload-file-item-clear', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.clearAllFiles();
            });

            // 文件浏览器
            Dolphin.browser(this.btnBrowser, {
                title: '选择文件',
                type: 'file',
                multiple: this.instance.options.multiple
            }, (data) => {
                const currentCount = this.getCurrentCount();
                const maxFiles = this.instance.options.maxFiles;
                const newFileCount = this.instance.options.multiple ? Object.keys(data).length : 1;

                if (maxFiles && maxFiles !== 0 && maxFiles !== Infinity && currentCount + newFileCount > maxFiles) {
                    Dolphin.error(`最多只能上传 ${maxFiles} 个文件`);
                    return;
                }

                // 插入预览文件
                if (this.instance.options.multiple) {
                    Object.keys(data).forEach((key) => {
                        data[key]['formattedSize'] = this.instance.formatSize(data[key]['size']);
                        this.showPreview(data[key]).then(() => {
                            this.setStatus(data[key]['id'], '', data[key]['id']);
                        });
                    });
                } else {
                    data['formattedSize'] = this.instance.formatSize(data['size']);
                    this.showPreview(data).then(() => {
                        this.setStatus(data['id'], '', data['id']);
                    });
                }
            });
        }

        /**
         * 显示预览
         * @param {object} fileData 文件数据
         * @returns {Promise<void>}
         */
        showPreview(fileData) {
            return new Promise((resolve) => {
                // 参数验证
                if (!fileData.id) {
                    throw new Error('ID不能为空');
                }

                // 安全地处理参数
                const safeId = this.sanitizeId(fileData.id);

                // 创建预览元素
                const previewElement = this.createPreviewElement(safeId, fileData);

                // 安全地插入DOM
                this.items.append(previewElement)

                // 更新UI状态
                if (!this.instance.options.multiple) {
                    if (this.btnAdd) $(this.btnAdd).hide();
                    if (this.btnBrowser) $(this.btnBrowser).hide();
                }

                // 初始化tooltip
                const $item = $('#dp-form-upload-file-item-' + safeId);
                $item.find('[data-bs-toggle="tooltip"]').tooltip();

                // 更新清空按钮显示状态
                this.updateClearButtonVisibility();

                resolve();
            });
        }

        /**
         * 创建预览元素
         * @param {string} safeId 安全的ID
         * @param {object} fileData 文件数据
         * @returns {HTMLElement} 预览元素
         */
        createPreviewElement(safeId, fileData) {
            const container = document.createElement('div');
            container.className = 'dp-form-upload-file-item';
            container.id = `dp-form-upload-file-item-${safeId}`;

            // 创建隐藏输入框
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = this.target.dataset.name || '';
            hiddenInput.value = '';

            // 创建进度条
            const progressContainer = document.createElement('div');
            progressContainer.className = 'progress bg-transparent';

            // 创建进度条
            const progressBar = document.createElement('div');
            progressBar.className = 'progress-bar progress-bar-striped progress-bar-animated bg-green-lt';
            progressBar.style.width = '0';
            progressBar.setAttribute('role', 'progressbar');

            // 创建文件信息容器
            const fileInfoDiv = document.createElement('div');
            fileInfoDiv.className = 'dp-form-upload-file-info';

            // 创建文件名span
            const fileNameSpan = document.createElement('span');
            fileNameSpan.className = 'file-name';

            // 创建文件图标
            const fileIcon = document.createElement('i');
            fileIcon.className = 'dp-icon ti ti-file';

            // 拖拽图标
            const dragIcon = document.createElement('i');
            dragIcon.className = 'dp-form-upload-file-item-handle ti ti-grip-vertical';

            // 创建文件名文本
            const fileNameText = document.createTextNode(' ' + fileData.name + ' (' + fileData.formattedSize + ')');

            // 创建上传提示span
            const tipsSpan = document.createElement('span');
            tipsSpan.className = 'tips';

            // 创建tips中的空i标签
            const tipsIcon = document.createElement('i');
            tipsSpan.appendChild(tipsIcon);

            // 创建删除按钮
            const deleteBtn = document.createElement('i');
            deleteBtn.className = 'dp-form-upload-file-delete ti ti-trash-x';
            deleteBtn.title = '删除';
            deleteBtn.setAttribute('data-id', safeId);
            deleteBtn.setAttribute('data-bs-toggle', 'tooltip');

            // 创建下载按钮
            const downloadBtn = document.createElement('a');
            if (fileData.url) {
                downloadBtn.href = fileData.url;
                downloadBtn.setAttribute('download', fileData.name);
            }

            // 创建删除按钮图标
            const downloadBtnIcon = document.createElement('i');
            downloadBtnIcon.className = 'dp-form-upload-file-download ti ti-square-rounded-arrow-down';
            downloadBtnIcon.title = '下载';
            downloadBtnIcon.setAttribute('data-bs-toggle', 'tooltip');
            downloadBtnIcon.setAttribute('data-bs-placement', 'top');

            // 组装元素
            if (this.instance.options.multiple) {
                fileNameSpan.appendChild(dragIcon);
            }
            fileNameSpan.appendChild(fileIcon);
            fileNameSpan.appendChild(fileNameText);
            downloadBtn.appendChild(downloadBtnIcon);
            fileInfoDiv.appendChild(fileNameSpan);
            fileInfoDiv.appendChild(deleteBtn);
            fileInfoDiv.appendChild(downloadBtn);
            fileInfoDiv.appendChild(tipsSpan);
            progressContainer.appendChild(progressBar);
            container.appendChild(hiddenInput);
            container.appendChild(progressContainer);
            container.appendChild(fileInfoDiv);

            return container;
        }

        /**
         * 设置上传状态
         * @param id
         * @param status
         * @param value
         * @param tips
         * @param errMsg
         */
        setStatus(id, status = '', value = '', tips = '', errMsg = '') {
            const $fileItem = $(this.target).find('#dp-form-upload-file-item-' + id);
            $fileItem
                .removeClass()
                .addClass('dp-form-upload-file-item')
                .addClass(status)
                .find('input[type=hidden]').val(value);
            if (errMsg !== '') {
                tips = '<i class="ti ti-alert-circle" data-bs-toggle="tooltip" data-bs-placement="top" title="错误：'+errMsg+'"></i>' + tips;
            }
            $fileItem.find('.tips').html(tips);
            $fileItem.find('.tips i').tooltip();
        }

        /**
         * 设置下载链接
         * @param id
         * @param fileInfo
         */
        setDownloadUrl(id, fileInfo) {
            $(this.target).find('#dp-form-upload-file-item-' + id).find('.dp-form-upload-file-info a').attr('href', fileInfo.url).attr('download', fileInfo.name);
        }

        /**
         * 设置单个值项
         * @param {string|Object} item 值项
         */
        async setValueItem(item) {
            try {
                let fileUrl, fileId, fileName, fileSize;

                if (typeof item === 'string') {
                    // 假设是文件URL
                    fileUrl = item;
                    fileId = item;
                    fileName = item.split('/').pop();
                    fileSize = 0;
                } else if (typeof item === 'object' && item !== null) {
                    fileUrl = item.url || item.path || item.src;
                    fileId = item.id || item.value;
                    fileName = item.name || fileUrl.split('/').pop();
                    fileSize = item.size || 0;
                } else {
                    throw new Error('不支持的值类型');
                }

                if (!fileUrl) {
                    throw new Error('无效的文件URL');
                }

                // 生成唯一ID
                const id = this.generateUniqueId();

                // 格式化文件大小
                const formattedSize = this.instance ? this.instance.formatSize(fileSize) : '0 B';

                // 显示预览
                await this.showPreview({
                    id: id,
                    name: fileName,
                    url: fileUrl,
                    formattedSize: formattedSize
                });

                // 设置值
                this.setStatus(id, '', fileId);

            } catch (error) {
                console.warn('设置值项失败:', error);
            }
        }

        /**
         * 删除文件
         * @param {Event} e - 点击事件对象
         */
        removeFile(e) {
            try {
                const $deleteBtn = $(e.currentTarget);
                const fileId = $deleteBtn.data('id');
                const $item = $deleteBtn.closest('.dp-form-upload-file-item');

                // 移除DOM元素
                $item.find('[data-bs-toggle="tooltip"]').tooltip('hide');
                $item.remove();

                // 从上传器中移除文件
                if (this.instance && typeof this.instance.removeFile === 'function' && fileId) {
                    this.instance.cancelUpload(fileId);
                    this.instance.removeFile(fileId);
                }

                // 如果是单文件模式，重新显示添加按钮
                if (!this.instance.options.multiple) {
                    if (this.btnAdd) $(this.btnAdd).show();
                    if (this.btnBrowser) $(this.btnBrowser).show();
                }

                // 触发变化事件
                this.emit('change', this.getValue());

                // 更新清空按钮显示状态
                this.updateClearButtonVisibility();

                Dolphin.notify('文件删除成功', 'success');

            } catch (error) {
                console.error('删除文件失败:', error);
                Dolphin.error('删除文件失败');
            }
        }
    }

    // ===========================================
    // Cropper Component
    // ===========================================
    /**
     * Cropper 组件
     * 图片裁剪组件，基于 Cropper.js，支持多种裁剪模式和上传驱动
     *
     * @class CropperComponent
     * @extends {BaseComponent}
     */
    class CropperComponent extends DpForm['BaseComponent'] {
        /**
         * 构造函数
         * @param {...any} args - 构造参数
         */
        constructor(...args) {
            super(...args);

            // DOM元素缓存
            this.domCache = new Map();
            // 事件监听器清理函数队列
            this.eventCleanups = [];
            // 对象URL管理
            this.uploadedImageURL = null;
            // XMLHttpRequest实例
            this.xhr = null;
        }

        /**
         * 缓存DOM元素查询
         * @private
         * @param {string} selector - CSS选择器
         * @returns {jQuery} jQuery对象
         */
        _getCachedElement(selector) {
            if (!this.domCache.has(selector)) {
                const element = $(this.element).find(selector);
                this.domCache.set(selector, element);
            }
            return this.domCache.get(selector);
        }

        /**
         * 更新按钮状态
         * @private
         * @param {string} action - 操作类型 ('crop', 'url', 'all')
         * @param {boolean} isLoading - 是否为加载状态
         */
        _updateButtonState(action, isLoading = false) {
            const $btnCrop = this._getCachedElement('.dp-form-cropper-crop');
            const $btnCropIcon = $btnCrop.find('.dp-icon');

            if (action === 'crop' || action === 'all') {
                if (isLoading) {
                    $btnCrop.prop('disabled', true);
                    $btnCropIcon.removeClass('fas fa-cut').addClass('fas fa-spinner fa-spin');
                } else {
                    $btnCrop.prop('disabled', false);
                    $btnCropIcon.removeClass('fas fa-spinner fa-spin').addClass('fas fa-cut');
                }
            }

            if (action === 'url' || action === 'all') {
                const $btnAdd = this._getCachedElement('.dp-form-cropper-add');
                if (isLoading) {
                    $btnAdd.addClass('disabled').html('<span class="spinner-border spinner-border-sm me-2" role="status"></span> 加载中');
                } else {
                    $btnAdd.removeClass('disabled').html('<i class="dp-icon fas fa-check"></i> 确定');
                }
            }
        }

        /**
         * 重置所有按钮状态
         * @private
         */
        _resetButtonState() {
            this._updateButtonState('all', false);
        }

        /**
         * 显示加载状态
         * @private
         * @param {string} action - 操作类型
         */
        _showLoadingState(action = 'crop') {
            this._updateButtonState(action, true);
        }

        /**
         * 隐藏加载状态
         * @private
         * @param {string} action - 操作类型
         */
        _hideLoadingState(action = 'crop') {
            this._updateButtonState(action, false);
        }

        /**
         * 统一错误处理
         * @private
         * @param {Error|string} error - 错误对象或错误消息
         * @param {string} context - 错误上下文
         */
        _handleError(error, context = 'unknown') {
            // 记录详细错误信息
            console.error(`CropperComponent[${context}]:`, error);

            // 显示用户友好的错误信息
            const userMessage = this._getUserErrorMessage(error, context);
            if (window.Dolphin && typeof Dolphin.error === 'function') {
                Dolphin.error(userMessage);
            }

            // 触发错误事件
            this.emit('error', { error, context, userMessage });

            // 重置UI状态
            this._resetButtonState();
        }

        /**
         * 获取用户友好的错误信息
         * @private
         * @param {Error|string} error - 错误对象或错误消息
         * @param {string} context - 错误上下文
         * @returns {string} 用户友好的错误信息
         */
        _getUserErrorMessage(error, context) {
            const messages = {
                'network': '网络连接失败，请检查网络连接后重试',
                'validation': '文件格式不正确，请选择有效的图片文件',
                'upload': '图片上传失败，请重试',
                'crop': '图片裁剪失败，请重新操作',
                'url': '图片链接无效或加载失败',
                'init': '组件初始化失败',
                'default': '操作失败，请重试'
            };

            // 根据错误类型和上下文返回相应的用户友好信息
            return messages[context] || messages.default;
        }

        /**
         * 组件初始化
         * @returns {Promise<void>}
         */
        async onInit() {
            const $this = $(this.element);
            const $cropper = this._getCachedElement('.dp-form-cropper');
            const $btnUpload = this._getCachedElement('.dp-form-cropper-upload');
            const $btnUrl = this._getCachedElement('.dp-form-cropper-url');
            const $btnSelect = this._getCachedElement('.dp-form-cropper-select');
            const $btnDelete = this._getCachedElement('.dp-form-cropper-delete');
            const $inputImage = $btnUpload.find('input');
            const $btnCrop = this._getCachedElement('.dp-form-cropper-crop');
            const $btnCropIcon = $btnCrop.find('.dp-icon');
            const $inputUrl = this._getCachedElement('.dp-form-cropper-url-input');
            const $gallery = this._getCachedElement('.dp-form-cropper-gallery');
            const $modalUpload = this._getCachedElement('.modal-upload');
            const $modalUrl = this._getCachedElement('.modal-url');
            const $cropperPreview = this._getCachedElement('.dp-form-cropper-preview');
            const $cropperImage = this._getCachedElement('.dp-form-cropper-image');
            const $inputCropper = $cropperImage.find('input');
            const image = document.getElementById($cropper.data('id'));
            let options = $cropper.data('options');
            const that = this;

            if (!options.url) {
                options.url = DolphinConfig?.url?.upload;
            }

            const _loadImage = function(url) {
                // 获取选中的图片URL
                Dolphin.loadImage(url).then(img => {
                    // 创建Canvas
                    const canvas = document.createElement('canvas');
                    const ctx = canvas.getContext('2d');

                    // 设置Canvas尺寸为图片尺寸
                    canvas.width = img.naturalWidth;
                    canvas.height = img.naturalHeight;

                    // 将图片绘制到Canvas
                    ctx.drawImage(img, 0, 0);

                    // 转换为Base64
                    image.src = canvas.toDataURL('image/png');
                    $modalUrl.modal('hide');
                    that._hideLoadingState('url');
                    $inputUrl.val('');
                    $modalUpload.modal('show');
                }).catch((error) => {
                    that._handleError(error || new Error('图片加载失败'), 'url');
                })
            }

            $modalUpload.on('hide.bs.modal', function () {
                document.activeElement.blur();
            });

            // 网络图片模态框隐藏时清空输入框
            $modalUrl.on('hide.bs.modal', function () {
                $inputUrl.val('');
                that._hideLoadingState('url');
                document.activeElement.blur();
            });

            $modalUpload.on('hidden.bs.modal', () => {
                $cropperPreview.hide();
                $cropperImage.show();
                $inputImage.val('');
                this.xhr !== null && typeof this.xhr.abort === 'function' && this.xhr.abort();
                $cropper.find('cropper-canvas').remove();
                $cropper.find('img').attr('src', '');

                // 清理临时URL资源
                if (that.uploadedImageURL) {
                    URL.revokeObjectURL(that.uploadedImageURL);
                    that.uploadedImageURL = null;
                }

                // 重置按钮状态
                that._resetButtonState();

                this.instance = null;
            });

            $modalUpload.on('shown.bs.modal', () => {
                const CropperConstructor = Cropper.default ?? Cropper;
                if (!CropperConstructor || typeof CropperConstructor !== 'function') {
                    console.error('Cropper 构造函数未找到，请检查 Cropper.js 库是否正确加载');
                    return;
                }

                // 初始化
                this.instance = new CropperConstructor(image);

                // 画布
                const cropperCanvas = this.instance.getCropperCanvas();
                // 设置cropperCanvas参数
                if (options['canvas']) {
                    $.each(options['canvas'], function (name, value) {
                        cropperCanvas[name] = value;
                    });
                }

                // 图片
                const cropperImage = this.instance.getCropperImage();
                cropperImage.$ready(image => {
                    // 设置cropperImage参数
                    if (options['image']) {
                        $.each(options['image'], function (name, value) {
                            cropperImage[name] = value;
                        });
                    }

                    // 设置shade参数
                    if (options['shade']) {
                        const cropperShade = this.instance.container.querySelector('cropper-shade');
                        $.each(options['shade'], function (name, value) {
                            cropperShade[name] = value;
                        });
                    }

                    // 设置selection参数
                    if (options['selection']) {
                        const cropperSelection = this.instance.getCropperSelection();
                        $.each(options['selection'], function (name, value) {
                            cropperSelection[name] = value;
                        });
                    }

                    // 设置handle参数
                    if (options['handle']) {
                        const cropperHandle = this.instance.container.querySelector('cropper-handle');
                        $.each(options['handle'], function (name, value) {
                            cropperHandle[name] = value;
                        });
                    }

                    // 设置grid参数
                    if (options['grid']) {
                        const cropperGrid = this.instance.container.querySelector('cropper-grid');
                        $.each(options['grid'], function (name, value) {
                            cropperGrid[name] = value;
                        });
                    }

                    // 设置crosshair参数
                    if (options['crosshair']) {
                        const cropperCrosshair = this.instance.container.querySelector('cropper-crosshair');
                        $.each(options['crosshair'], function (name, value) {
                            cropperCrosshair[name] = value;
                        });
                    }

                    // 设置viewer参数
                    if (options['viewer']) {
                        const cropperViewer = this.instance.container.querySelector('cropper-viewer');
                        $.each(options['viewer'], function (name, value) {
                            cropperViewer[name] = value;
                        });
                    }

                    $this.find('.dp-form-cropper-buttons .btn').click(function () {
                        const method = $(this).data('method');
                        let params = String($(this).data('params'))
                            .split(',')
                            .filter(item => item.trim() !== '')
                            .map(value => {
                                // 如果是数字字符串，转为数字
                                if (typeof value === 'string' && !isNaN(value) && value.trim() !== '') {
                                    return Number(value);
                                }
                                // 保持原样
                                return value;
                            });
                        cropperImage['$' + method](...params)
                    });
                });

                Dolphin.loading('hide');
            });

            // 删除图片按钮点击事件
            $this.on('click', '.dp-form-cropper-delete', function(e) {
                e.preventDefault();

                if ($inputCropper.val() !== '') {
                    // 清空隐藏输入框的值
                    $inputCropper.val('');

                    // 清空图片预览
                    $cropperImage.find('img').attr('src', '');
                    $gallery.attr('href', 'javascript:void(0);');

                    Dolphin.notify('图片删除成功', 'success');
                }
            });

            // 网络图片按钮点击事件
            $this.on('click', '.dp-form-cropper-url', function(e) {
                e.preventDefault();
                // 显示网络图片输入模态框
                $modalUrl.modal('show');
            });

            // 网络图片模态框显示完成时设置焦点
            $modalUrl.on('shown.bs.modal', function () {
                // 清空输入框并获得焦点，使用较长的延迟确保模态框完全显示
                setTimeout(() => {
                    $inputUrl.val('').focus();
                }, 100);
            });

            // 网络图片确定按钮点击事件
            $this.on('click', '.dp-form-cropper-add', function(e) {
                e.preventDefault();
                const imageUrl = $inputUrl.val().trim();

                if (!imageUrl) {
                    that._handleError(new Error('请输入正确的图片链接'), 'validation');
                    return;
                }

                // 基本URL格式验证
                if (!imageUrl.match(/^https?:\/\/.+/)) {
                    that._handleError(new Error('请输入有效的图片链接（http://或https://）'), 'validation');
                    return;
                }

                that._showLoadingState('url');
                _loadImage(imageUrl)
            });

            // 浏览图片按钮点击事件
            Dolphin.browser($this.find('.dp-form-cropper-select'), {
                type: 'image',
                title: '选择图片',
                multiple: false
            }, (data) => {
                _loadImage(data.url)
            });

            // 立即绑定文件上传change事件，确保在用户首次交互前完成绑定
            $inputImage.on('change', function () {
                const files = this.files;
                let file;

                if (files && files.length) {
                    file = files[0];

                    if (/^image\/\w+/.test(file.type)) {
                        if (that.uploadedImageURL) {
                            URL.revokeObjectURL(that.uploadedImageURL);
                        }

                        image.src = that.uploadedImageURL = URL.createObjectURL(file);

                        if (that.instance) {
                            that.instance = null;
                        }

                        Dolphin.loading();
                        $modalUpload.modal('show');
                        $(this).val('');
                    } else {
                        that._handleError(new Error('请选择一张图片'), 'validation');
                    }
                }
            });

            $btnCrop.click(() => {
                // 显示加载状态
                that._showLoadingState('crop');

                const selection = that.instance.getCropperSelection();
                const canvas = selection.$toCanvas();

                canvas.then(res => {
                    res.toBlob(function(blob) {
                        // 上传裁剪后的图片
                        that.uploadCroppedImage(blob, $inputCropper.attr('name'), options.url)
                            .then(response => {
                                // 上传成功
                                if (response.code === 1 && response.data.success.length > 0) {
                                    const fileInfo = response.data.success[0];

                                    // 更新预览图片
                                    $cropperImage.find('img').attr('src', fileInfo.url);
                                    $gallery.attr('href', fileInfo.url);

                                    // 更新隐藏输入框的值
                                    $inputCropper.val(fileInfo.id);

                                    // 刷新lightbox
                                    if (typeof refreshFsLightbox === 'function') {
                                        refreshFsLightbox();
                                    }

                                    // 关闭模态框
                                    $modalUpload.modal('hide');

                                    Dolphin.notify('图片裁剪并上传成功', 'success');
                                } else {
                                    that._handleError(new Error(response.msg || '未知错误'), 'upload');
                                }
                            })
                            .catch(error => {
                                that._handleError(error, 'upload');
                            })
                            .finally(() => {
                                // 恢复裁剪按钮状态
                                that._hideLoadingState('crop');
                            });
                    }, 'image/png', 0.9); // 使用PNG格式，配合后端处理
                }).catch(error => {
                    that._handleError(error, 'crop');
                });
            })

            setTimeout(() => {
                $btnUpload.removeClass('disabled');
                $btnUrl.removeClass('disabled');
                $btnSelect.removeClass('disabled');
                $btnDelete.removeClass('disabled');
            }, 100)
        }

        /**
         * 上传裁剪后的图片
         * @param {Blob} blob - 图片blob数据
         * @param {string} fieldName - 字段名
         * @param {string} url - 上传地址
         * @returns {Promise<Object>} 上传结果
         */
        uploadCroppedImage(blob, fieldName, url) {
            // 获取当前组件的驱动配置
            const $this = $(this.element);
            const $cropper = $this.find('.dp-form-cropper');
            const driver = $cropper.data('driver') || 'local';
            const dir = $cropper.data('dir') || '';

            // 检查是否有第三方上传驱动支持
            if (driver !== 'local' && window.Dolphin && window.Dolphin.uploader) {
                const driverInfo = Dolphin.uploader.find(driver, 'cropper');
                const actualDriver = driverInfo ? driverInfo.driver : null;

                if (actualDriver && typeof actualDriver.uploadCroppedImage === 'function') {
                    // 使用第三方驱动的上传方法
                    return actualDriver.uploadCroppedImage.call(actualDriver, blob, fieldName, url, dir);
                }
            }

            // 回退到本地上传
            return this._uploadToLocal(blob, url);
        }

        /**
         * 本地上传实现
         * @private
         * @param {Blob} blob - 图片blob数据
         * @param {string} url - 上传地址
         * @returns {Promise<Object>} 上传结果
         */
        _uploadToLocal(blob, url) {
            return new Promise((resolve, reject) => {
                // 创建FormData对象
                const formData = new FormData();

                // 生成文件名
                const timestamp = new Date().getTime();
                const fileName = `${timestamp}.png`;

                // 添加文件数据
                formData.append('file', blob, fileName);

                // 添加必要的参数
                formData.append('_from', 'cropper');
                formData.append('_name', 'file');
                formData.append('_ajax', '1');

                // 创建XMLHttpRequest对象
                const xhr = new XMLHttpRequest();

                // 设置上传监听
                xhr.upload.onprogress = (event) => {
                    if (event.lengthComputable) {
                        const percentComplete = (event.loaded / event.total) * 100;
                        console.log('上传进度:', percentComplete.toFixed(2) + '%');
                    }
                };

                // 设置完成监听
                xhr.onload = () => {
                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            resolve(response);
                        } catch (error) {
                            reject(new Error('响应解析失败: ' + error.message));
                        }
                    } else {
                        reject(new Error('HTTP错误: ' + xhr.status));
                    }
                };

                // 设置错误监听
                xhr.onerror = () => {
                    reject(new Error('网络错误'));
                };

                // 设置超时监听
                xhr.ontimeout = () => {
                    reject(new Error('上传超时'));
                };

                // 配置请求
                xhr.open('POST', url, true);
                xhr.timeout = 30000; // 30秒超时

                // 发送请求
                xhr.send(formData);
            });
        }

        /**
         * 获取组件值
         * @returns {string} 已裁剪图片的URL（如果有）
         */
        getValue() {
            const $input = this._getCachedElement('.dp-form-cropper-input');
            return $input && $input.length > 0 ? $input.val() : '';
        }

        /**
         * 设置组件值
         * @param {string} value - 图片URL
         * @returns {CropperComponent} this（支持链式调用）
         */
        setValue(value) {
            if (!value) return this;

            try {
                const $input = this._getCachedElement('.dp-form-cropper-input');
                const $preview = this._getCachedElement('.dp-form-cropper-preview');

                if ($input && $input.length > 0) {
                    $input.val(value);
                }

                // 更新预览图片
                if ($preview && $preview.length > 0) {
                    const $img = $preview.find('img');
                    if ($img.length > 0) {
                        $img.attr('src', value);
                        $preview.show();
                    }
                }

                this.emit('change', value);
            } catch (error) {
                this._handleError(error, 'setValue');
            }

            return this;
        }

        /**
         * 销毁组件
         * @returns {Promise<void>}
         */
        async onDestroy() {
            try {
                // 清理事件监听器
                this.eventCleanups.forEach(cleanup => {
                    try {
                        cleanup();
                    } catch (error) {
                        console.warn('清理事件监听器失败:', error);
                    }
                });

                // 终止进行中的请求
                if (this.xhr && typeof this.xhr.abort === 'function') {
                    this.xhr.abort();
                }

                // 释放对象URL
                if (this.uploadedImageURL) {
                    URL.revokeObjectURL(this.uploadedImageURL);
                    this.uploadedImageURL = null;
                }

                // 销毁Cropper实例
                if (this.instance) {
                    try {
                        this.instance.destroy();
                    } catch (error) {
                        console.warn('销毁Cropper实例失败:', error);
                    }
                    this.instance = null;
                }

                // 清理DOM缓存
                if (this.domCache) {
                    this.domCache.clear();
                    this.domCache = null;
                }

                // 清理其他引用
                this.eventCleanups = null;
                this.xhr = null;

            } catch (error) {
                console.warn('CropperComponent销毁过程中发生错误:', error);
            }

            // 调用父类销毁方法
            if (super.onDestroy) {
                await super.onDestroy();
            }
        }
    }

    // ===========================================
    // QMap Component
    // ===========================================
    /**
     * QMap 组件
     * 基于腾讯地图 JS API GL 的选点组件
     *
     * @class QMapComponent
     * @extends {BaseComponent}
     */
    class QMapComponent extends DpForm['BaseComponent'] {
        constructor(...args) {
            super(...args);
            this.map = null;
            this.marker = null;
            this.options = {};
            this.key = '';
            this.$root = null;
            this.$valueInput = null;
            this.$addressInput = null;
            this.$suggest = null;
            this.$searchInput = null;
            this.searchTimer = null;
            this.geocodeTimer = null;
            this.lastGeocodeKey = '';
            this.lastGeocodeAddress = '';
        }

        async onInit() {
            const $root = $(this.element).find('.dp-form-qmap');
            if (!$root.length) {
                throw new Error('QMap container not found');
            }

            this.$root = $root;
            this.$valueInput = $(this.element).find('.dp-qmap-value');
            this.$addressInput = $(this.element).find('.dp-qmap-address-input');
            this.$suggest = $(this.element).find('.dp-qmap-suggest');
            this.$searchInput = $(this.element).find('.dp-qmap-search');

            const rawOptions = $root.data('options');
            this.options = this._parseOptions(rawOptions);
            this.readonly = Boolean($root.data('readonly'));
            this.key = this.options.key || '';

            if (!this.key) {
                console.error('[QMap] 未配置腾讯地图 key');
                if (window.Dolphin && typeof Dolphin.error === 'function') {
                    Dolphin.error('腾讯地图 Key 未配置');
                }
                return;
            }

            await this._loadMap();
            await this._initMap();
            this._bindEvents();
        }

        _parseOptions(rawOptions) {
            if (!rawOptions) {
                return {};
            }
            if (typeof rawOptions === 'string') {
                try {
                    return JSON.parse(rawOptions);
                } catch (error) {
                    console.warn('[QMap] 解析 options 失败', error);
                    return {};
                }
            }
            return rawOptions;
        }

        _parseLngLat(value) {
            if (!value || typeof value !== 'string') {
                return null;
            }
            const parts = value.split(',');
            if (parts.length !== 2) {
                return null;
            }
            const lng = parseFloat(parts[0]);
            const lat = parseFloat(parts[1]);
            if (Number.isNaN(lng) || Number.isNaN(lat)) {
                return null;
            }
            return { lng, lat };
        }

        async _resolveCenterFromAddress(address) {
            if (!address) {
                return null;
            }
            try {
                const data = await this._jsonp('https://apis.map.qq.com/ws/geocoder/v1/', {
                    address: address,
                    key: this.key,
                    output: 'jsonp'
                });
                if (data && data.status === 0 && data.result && data.result.location) {
                    return {
                        lng: data.result.location.lng,
                        lat: data.result.location.lat,
                        address: data.result.address || address
                    };
                }
                if (this._handleApiError(data, '地址解析失败')) {
                    return null;
                }
            } catch (error) {
                console.warn('[QMap] 地址解析失败', error);
            }
            return null;
        }

        _loadMap() {
            if (window.TMap && window.TMap.Map) {
                return Promise.resolve();
            }

            if (!QMapComponent._loader) {
                const key = encodeURIComponent(this.key);
                QMapComponent._loader = new Promise((resolve, reject) => {
                    const script = document.createElement('script');
                    script.src = `https://map.qq.com/api/gljs?v=1.exp&key=${key}`;
                    script.onload = () => resolve();
                    script.onerror = () => reject(new Error('腾讯地图脚本加载失败'));
                    document.head.appendChild(script);
                });
            }

            return QMapComponent._loader;
        }

        async _initMap() {
            const mapContainer = this.$root.find('.dp-qmap-canvas')[0];
            const zoom = Number(this.options.zoom) || 15;
            const value = this.$valueInput.val();
            const centerValue = this._parseLngLat(value) || this._parseLngLat(this.options.center);
            let center = centerValue || { lng: 116.3975, lat: 39.9087 };

            if (!centerValue) {
                const address = this.options.address || this.$addressInput.val();
                if (address) {
                    const resolved = await this._resolveCenterFromAddress(address);
                    if (resolved) {
                        center = { lng: resolved.lng, lat: resolved.lat };
                        this._setValue(resolved.lng, resolved.lat);
                        this._setAddress(resolved.address || address);
                    } else {
                        this._setAddress(address);
                    }
                }
            }

            const centerLatLng = new TMap.LatLng(center.lat, center.lng);

            this.map = new TMap.Map(mapContainer, {
                center: centerLatLng,
                zoom: zoom
            });

            const canEdit = !this.readonly;
            this.marker = new TMap.MultiMarker({
                map: this.map,
                draggable: canEdit,
                styles: {
                    marker: new TMap.MarkerStyle({
                        width: 25,
                        height: 35,
                        anchor: { x: 13, y: 35 }
                    })
                },
                geometries: [{
                    id: 'marker',
                    styleId: 'marker',
                    position: centerLatLng,
                    draggable: canEdit
                }]
            });

            if (centerValue) {
                this._setValue(center.lng, center.lat);
                this._reverseGeocode(center.lat, center.lng);
            }
        }

        _handleApiError(data, fallbackMessage = '') {
            if (!data || data.status === 0) {
                return false;
            }
            const message = data.message || fallbackMessage || '腾讯地图服务返回错误';
            if (window.Dolphin && typeof Dolphin.error === 'function') {
                Dolphin.error(message);
            } else {
                console.warn('[QMap] 请求失败:', message);
            }
            return true;
        }

        _bindEvents() {
            if (this.readonly) {
                return;
            }
            const $searchInput = this.$searchInput || this.$root.find('.dp-qmap-search');
            const $clearBtn = this.$root.find('.dp-qmap-clear');

            this.map.on('click', (evt) => {
                if (!evt || !evt.latLng) {
                    return;
                }
                this._setPosition(evt.latLng);
            });

            if (this.marker && typeof this.marker.on === 'function') {
                this.marker.on('dragend', (evt) => {
                    const position = evt.geometry?.position || evt.latLng;
                    if (position) {
                        this._setPosition(position);
                    }
                });
            }

            $searchInput.on('input', () => {
                if (this.searchTimer) {
                    clearTimeout(this.searchTimer);
                }
                this.searchTimer = setTimeout(() => {
                    const keyword = ($searchInput.val() || '').trim();
                    if (!keyword) {
                        this._renderSuggest([]);
                        return;
                    }
                    if (keyword.length < 2) {
                        this._renderSuggest([]);
                        return;
                    }
                    this._searchSuggest(keyword);
                }, 600);
            });

            $clearBtn.on('click', () => {
                $searchInput.val('');
                this._renderSuggest([]);
            });
        }

        _setPosition(position) {
            const lng = typeof position.getLng === 'function' ? position.getLng() : position.lng;
            const lat = typeof position.getLat === 'function' ? position.getLat() : position.lat;
            const latLng = new TMap.LatLng(lat, lng);

            if (this.marker) {
                if (typeof this.marker.updateGeometries === 'function') {
                    this.marker.updateGeometries([{ id: 'marker', position: latLng, draggable: true }]);
                } else if (typeof this.marker.setGeometries === 'function') {
                    this.marker.setGeometries([{ id: 'marker', position: latLng, draggable: true }]);
                }
            }

            if (this.map) {
                this.map.setCenter(latLng);
            }

            this._setValue(lng, lat);
            this._reverseGeocode(lat, lng);
        }

        _setValue(lng, lat) {
            const value = `${lng.toFixed(6)},${lat.toFixed(6)}`;
            this.$valueInput.val(value).trigger('change');
        }

        _setAddress(address) {
            const value = address || '';
            if (this.$addressInput.length) {
                this.$addressInput.val(value).trigger('change');
            }
            if (this.$searchInput && this.$searchInput.length) {
                this.$searchInput.val(value);
            }
        }

        _buildGeocoderParams(lat, lng) {
            const params = Object.assign({}, this.options.geocoderParams || {});
            params.location = params.location || `${lat},${lng}`;
            params.key = params.key || this.key;
            if (params.get_poi === undefined) {
                params.get_poi = this.options.get_poi ?? 0;
            }
            if (params.output === undefined) {
                params.output = 'jsonp';
            }
            return params;
        }

        _buildSuggestParams(keyword) {
            const params = Object.assign({}, this.options.suggestParams || {});
            params.keyword = params.keyword || keyword;
            if (this.options.region && params.region === undefined) {
                params.region = this.options.region;
            }
            params.key = params.key || this.key;
            if (params.output === undefined) {
                params.output = 'jsonp';
            }
            return params;
        }

        async _reverseGeocode(lat, lng) {
            const roundedLat = Number(lat).toFixed(6);
            const roundedLng = Number(lng).toFixed(6);
            const key = `${roundedLng},${roundedLat}`;

            if (this.lastGeocodeKey === key && this.lastGeocodeAddress) {
                this._setAddress(this.lastGeocodeAddress);
                return;
            }

            if (this.geocodeTimer) {
                clearTimeout(this.geocodeTimer);
            }

            this.geocodeTimer = setTimeout(async () => {
                try {
                    const data = await this._jsonp('https://apis.map.qq.com/ws/geocoder/v1/', this._buildGeocoderParams(roundedLat, roundedLng));

                    if (data && data.status === 0) {
                        const addressMode = this.options.address_mode || 'poi';
                        const poiName = data.result?.formatted_addresses?.recommend
                            || data.result?.address_reference?.landmark_l2?.title
                            || data.result?.address_reference?.town?.title
                            || '';
                        const fullAddress = data.result?.formatted_addresses?.standard_address
                            || data.result?.address
                            || '';
                        let address = '';
                        if (addressMode === 'full') {
                            address = fullAddress || poiName;
                        } else if (addressMode === 'both') {
                            if (poiName && fullAddress) {
                                address = `${poiName}（${fullAddress}）`;
                            } else {
                                address = poiName || fullAddress || '';
                            }
                        } else {
                            address = poiName || fullAddress || data.result?.address || '';
                        }
                        this.lastGeocodeKey = key;
                        this.lastGeocodeAddress = address;
                        this._setAddress(address);
                    }
                    this._handleApiError(data, '逆地址解析失败');
                } catch (error) {
                    console.warn('[QMap] 逆地址解析失败', error);
                }
            }, 700);
        }

        async _searchSuggest(keyword) {
            try {
                const data = await this._jsonp('https://apis.map.qq.com/ws/place/v1/suggestion/', this._buildSuggestParams(keyword));

                if (data && data.status === 0 && Array.isArray(data.data)) {
                    this._renderSuggest(data.data);
                } else {
                    this._renderSuggest([]);
                    this._handleApiError(data, '地址搜索失败');
                }
            } catch (error) {
                console.warn('[QMap] 地址搜索失败', error);
                this._renderSuggest([]);
            }
        }

        _renderSuggest(items) {
            if (!this.$suggest.length) {
                return;
            }
            if (!items || items.length === 0) {
                this.$suggest.hide().empty();
                return;
            }

            const html = items.map((item) => {
                const title = item.title || item.address || '';
                const address = item.address || '';
                const data = {
                    lat: item.location?.lat,
                    lng: item.location?.lng,
                    address: address || title
                };
                return `<button type="button" class="list-group-item list-group-item-action dp-qmap-item" data-lat="${data.lat}" data-lng="${data.lng}" data-address="${data.address}">
${title}<div class="small text-muted">${address}</div></button>`;
            }).join('');

            this.$suggest.html(html).show();

            this.$suggest.find('.dp-qmap-item').on('click', (e) => {
                const $target = $(e.currentTarget);
                const lat = parseFloat($target.data('lat'));
                const lng = parseFloat($target.data('lng'));
                const address = $target.data('address') || '';
                if (!Number.isNaN(lat) && !Number.isNaN(lng)) {
                    this._setPosition({ lat, lng });
                    if (address) {
                        this._setAddress(address);
                    }
                    this._handleApiError(data, '逆地址解析失败');
                }
                this._renderSuggest([]);
            });
        }

        _jsonp(url, params) {
            return new Promise((resolve, reject) => {
                const callbackName = `qmap_jsonp_${Date.now()}_${Math.random().toString(36).slice(2)}`;
                const query = new URLSearchParams(params || {});
                query.set('callback', callbackName);

                const script = document.createElement('script');
                script.src = `${url}?${query.toString()}`;

                window[callbackName] = (data) => {
                    resolve(data);
                    delete window[callbackName];
                    script.remove();
                };

                script.onerror = () => {
                    reject(new Error('JSONP请求失败'));
                    delete window[callbackName];
                    script.remove();
                };

                document.head.appendChild(script);
            });
        }
    }


    // ===========================================
    // BMap Component
    // ===========================================
    /**
     * BMap 组件
     * 基于百度地图 JavaScript API 的选点组件
     *
     * @class BMapComponent
     * @extends {BaseComponent}
     */
    class BMapComponent extends DpForm['BaseComponent'] {
        constructor(...args) {
            super(...args);
            this.map = null;
            this.marker = null;
            this.api = null;
            this.options = {};
            this.ak = '';
            this.$root = null;
            this.$valueInput = null;
            this.$addressInput = null;
            this.$suggest = null;
            this.$searchInput = null;
            this.searchTimer = null;
            this.geocodeTimer = null;
            this.lastGeocodeKey = '';
            this.lastGeocodeAddress = '';
            this.localSearch = null;
        }

        async onInit() {
            const $root = $(this.element).find('.dp-form-bmap');
            if (!$root.length) {
                throw new Error('BMap container not found');
            }

            this.$root = $root;
            this.$valueInput = $(this.element).find('.dp-bmap-value');
            this.$addressInput = $(this.element).find('.dp-bmap-address-input');
            this.$suggest = $(this.element).find('.dp-bmap-suggest');
            this.$searchInput = $(this.element).find('.dp-bmap-search');

            const rawOptions = $root.data('options');
            this.options = this._parseOptions(rawOptions);
            this.readonly = Boolean($root.data('readonly'));
            this.ak = this.options.ak || '';

            if (!this.ak) {
                console.error('[BMap] 未配置百度地图 ak');
                if (window.Dolphin && typeof Dolphin.error === 'function') {
                    Dolphin.error('百度地图 AK 未配置');
                }
                return;
            }

            await this._loadMap();
            await this._initMap();
            this._bindEvents();
        }

        _parseOptions(rawOptions) {
            if (!rawOptions) {
                return {};
            }
            if (typeof rawOptions === 'string') {
                try {
                    return JSON.parse(rawOptions);
                } catch (error) {
                    console.warn('[BMap] 解析 options 失败', error);
                    return {};
                }
            }
            return rawOptions;
        }

        _parseLngLat(value) {
            if (!value || typeof value !== 'string') {
                return null;
            }
            const parts = value.split(',');
            if (parts.length !== 2) {
                return null;
            }
            const lng = parseFloat(parts[0]);
            const lat = parseFloat(parts[1]);
            if (Number.isNaN(lng) || Number.isNaN(lat)) {
                return null;
            }
            return { lng, lat };
        }

        _getApiType() {
            return (this.options.api_type || '').toLowerCase();
        }

        _getApiVersion() {
            return this.options.api_version || (this._getApiType() === 'webgl' ? '1.0' : '3.0');
        }

        _getMapApi() {
            if (this._getApiType() === 'webgl' && window.BMapGL) {
                return window.BMapGL;
            }
            return window.BMap;
        }

        _loadMap() {
            const apiType = this._getApiType();
            if ((apiType === 'webgl' && window.BMapGL) || (apiType !== 'webgl' && window.BMap)) {
                this.api = this._getMapApi();
                return Promise.resolve();
            }

            if (!BMapComponent._loader) {
                const ak = encodeURIComponent(this.ak);
                const version = encodeURIComponent(this._getApiVersion());
                const typeParam = apiType === 'webgl' ? '&type=webgl' : '';
                const callbackName = `dp_bmap_init_${Date.now()}_${Math.random().toString(36).slice(2)}`;
                BMapComponent._loader = new Promise((resolve, reject) => {
                    let settled = false;
                    const script = document.createElement('script');
                    const cleanup = () => {
                        if (window[callbackName]) {
                            delete window[callbackName];
                        }
                    };
                    const waitForApi = (maxAttempts) => {
                        let attempts = 0;
                        const timer = setInterval(() => {
                            if (settled) {
                                clearInterval(timer);
                                return;
                            }
                            const currentApi = this._getMapApi();
                            if (currentApi && currentApi.Point) {
                                this.api = currentApi;
                                settled = true;
                                cleanup();
                                clearInterval(timer);
                                resolve();
                                return;
                            }
                            attempts += 1;
                            if (attempts >= maxAttempts) {
                                clearInterval(timer);
                                if (!settled) {
                                    settled = true;
                                    cleanup();
                                    reject(new Error('百度地图 API 未加载'));
                                }
                            }
                        }, 100);
                    };

                    window[callbackName] = () => {
                        const api = this._getMapApi();
                        if (api && api.Point && !settled) {
                            this.api = api;
                            settled = true;
                            cleanup();
                            resolve();
                            return;
                        }
                        waitForApi(80);
                    };

                    script.src = `https://api.map.baidu.com/api?v=${version}&ak=${ak}${typeParam}&callback=${callbackName}`;
                    script.onerror = () => {
                        if (!settled) {
                            settled = true;
                            cleanup();
                            reject(new Error('百度地图脚本加载失败'));
                        }
                    };

                    document.head.appendChild(script);

                    setTimeout(() => {
                        const api = this._getMapApi();
                        if (!settled && (!api || !api.Point)) {
                            settled = true;
                            cleanup();
                            reject(new Error('百度地图 API 未加载'));
                        }
                    }, 8000);
                });
                BMapComponent._loader.catch(() => {
                    BMapComponent._loader = null;
                });
            }

            return BMapComponent._loader;
        }

        async _resolveCenterFromAddress(address) {
            if (!address) {
                return null;
            }

            const api = this.api || this._getMapApi();
            if (api && typeof api.Geocoder === 'function') {
                return new Promise((resolve) => {
                    const geocoder = new api.Geocoder();
                    const city = this.options.city || this.options.region || '';
                    geocoder.getPoint(address, (point) => {
                        if (point) {
                            resolve({ lng: point.lng, lat: point.lat, address: address });
                        } else {
                            resolve(null);
                        }
                    }, city || undefined);
                });
            }

            try {
                const data = await this._jsonp('https://api.map.baidu.com/geocoding/v3/', this._buildGeocodeParams(address));
                if (data && data.status === 0 && data.result && data.result.location) {
                    return {
                        lng: data.result.location.lng,
                        lat: data.result.location.lat,
                        address: data.result.formatted_address || address
                    };
                }
            } catch (error) {
                console.warn('[BMap] 地址解析失败', error);
            }
            return null;
        }

        _buildGeocodeParams(address) {
            const params = Object.assign({}, this.options.geocodeParams || {});
            params.address = params.address || address;
            params.ak = params.ak || this.ak;
            if (params.output === undefined) {
                params.output = 'jsonp';
            }
            return params;
        }

        _buildReverseParams(lat, lng) {
            const params = Object.assign({}, this.options.geocoderParams || {});
            params.location = params.location || `${lat},${lng}`;
            params.ak = params.ak || this.ak;
            if (params.output === undefined) {
                params.output = 'jsonp';
            }
            return params;
        }

        _buildSuggestParams(keyword) {
            const params = Object.assign({}, this.options.suggestParams || {});
            params.query = params.query || keyword;
            if (this.options.region && params.region === undefined) {
                params.region = this.options.region;
            }
            params.ak = params.ak || this.ak;
            if (params.output === undefined) {
                params.output = 'jsonp';
            }
            return params;
        }

        async _initMap() {
            const mapContainer = this.$root.find('.dp-bmap-canvas')[0];
            const zoom = Number(this.options.zoom) || 15;
            const value = this.$valueInput.val();
            const centerValue = this._parseLngLat(value) || this._parseLngLat(this.options.center);
            let center = centerValue || { lng: 116.404, lat: 39.915 };

            if (!centerValue) {
                const address = this.options.address || this.$addressInput.val();
                if (address) {
                    const resolved = await this._resolveCenterFromAddress(address);
                    if (resolved) {
                        center = { lng: resolved.lng, lat: resolved.lat };
                        this._setValue(resolved.lng, resolved.lat);
                        this._setAddress(resolved.address || address);
                    } else {
                        this._setAddress(address);
                    }
                }
            }

            const api = this._getMapApi();
            if (!api || !api.Point) {
                console.error('[BMap] 百度地图 API 未正确加载');
                if (window.Dolphin && typeof Dolphin.error === 'function') {
                    Dolphin.error('百度地图 API 未正确加载');
                }
                return;
            }
            this.api = api;
            const point = api === window.BMapGL
                ? new api.Point(center.lng, center.lat)
                : new api.Point(center.lng, center.lat);

            const mapOptions = Object.assign({ enableMapClick: false }, this.options.mapOptions || {});
            this.map = new api.Map(mapContainer, mapOptions);
            this.map.centerAndZoom(point, zoom);

            this.marker = this._createMarker(point, api);
            if (!this.readonly && typeof this.marker.enableDragging === 'function') {
                this.marker.enableDragging();
            }
            this.map.addOverlay(this.marker);

            if (centerValue) {
                this._setValue(center.lng, center.lat);
                this._reverseGeocode(center.lat, center.lng);
            }
        }

        _createMarker(point, api) {
            const options = {};
            const sizeOpt = this.options.markerSize || null;
            let iconUrl = this.options.markerIcon || '';
            if (!iconUrl) {
                // Use a larger default marker icon
                iconUrl = 'https://api.map.baidu.com/images/marker_red.png';
            }
            if (api && typeof api.Icon === 'function' && typeof api.Size === 'function') {
                let width = 32;
                let height = 40;
                if (Array.isArray(sizeOpt) && sizeOpt.length >= 2) {
                    width = Number(sizeOpt[0]) || width;
                    height = Number(sizeOpt[1]) || height;
                } else if (sizeOpt && typeof sizeOpt === 'object') {
                    width = Number(sizeOpt.width || sizeOpt.w) || width;
                    height = Number(sizeOpt.height || sizeOpt.h) || height;
                }
                let anchorX = Math.round(width / 2);
                let anchorY = height;
                const anchorOpt = this.options.markerAnchor || null;
                if (Array.isArray(anchorOpt) && anchorOpt.length >= 2) {
                    anchorX = Number(anchorOpt[0]) || anchorX;
                    anchorY = Number(anchorOpt[1]) || anchorY;
                } else if (anchorOpt && typeof anchorOpt === 'object') {
                    anchorX = Number(anchorOpt.x) || anchorX;
                    anchorY = Number(anchorOpt.y) || anchorY;
                }
                const icon = new api.Icon(iconUrl, new api.Size(width, height), {
                    anchor: new api.Size(anchorX, anchorY)
                });
                options.icon = icon;
            }

            return new api.Marker(point, options);
        }

        _bindEvents() {
            if (this.readonly) {
                return;
            }
            const $searchInput = this.$searchInput || this.$root.find('.dp-bmap-search');
            const $clearBtn = this.$root.find('.dp-bmap-clear');

            if (this.map) {
                this.map.addEventListener('click', (evt) => {
                    if (!evt || !evt.point) {
                        return;
                    }
                    this._setPosition(evt.point);
                });
            }

            if (this.marker) {
                this.marker.addEventListener('dragend', (evt) => {
                    const point = evt && evt.point ? evt.point : null;
                    if (point) {
                        this._setPosition(point);
                    }
                });
            }

            $searchInput.on('input', () => {
                if (this.searchTimer) {
                    clearTimeout(this.searchTimer);
                }
                this.searchTimer = setTimeout(() => {
                    const keyword = ($searchInput.val() || '').trim();
                    if (!keyword) {
                        this._renderSuggest([]);
                        return;
                    }
                    if (keyword.length < 2) {
                        this._renderSuggest([]);
                        return;
                    }
                    this._searchSuggest(keyword);
                }, 600);
            });

            $clearBtn.on('click', () => {
                $searchInput.val('');
                this._renderSuggest([]);
            });
        }

        _setPosition(pointOrLngLat) {
            let lng = null;
            let lat = null;
            if (pointOrLngLat && typeof pointOrLngLat.lng === 'number' && typeof pointOrLngLat.lat === 'number') {
                lng = pointOrLngLat.lng;
                lat = pointOrLngLat.lat;
            }
            if (lng === null || lat === null) {
                return;
            }

            const api = this.api || this._getMapApi();
            const point = new api.Point(lng, lat);
            if (this.marker) {
                if (typeof this.marker.setPosition === 'function') {
                    this.marker.setPosition(point);
                }
            }
            if (this.map && typeof this.map.setCenter === 'function') {
                this.map.setCenter(point);
            }

            this._setValue(lng, lat);
            this._reverseGeocode(lat, lng);
        }

        _setValue(lng, lat) {
            const value = `${lng.toFixed(6)},${lat.toFixed(6)}`;
            this.$valueInput.val(value).trigger('change');
        }

        _setAddress(address) {
            const value = address || '';
            if (this.$addressInput.length) {
                this.$addressInput.val(value).trigger('change');
            }
            if (this.$searchInput && this.$searchInput.length) {
                this.$searchInput.val(value);
            }
        }

        async _reverseGeocode(lat, lng) {
            const roundedLat = Number(lat).toFixed(6);
            const roundedLng = Number(lng).toFixed(6);
            const key = `${roundedLng},${roundedLat}`;

            if (this.lastGeocodeKey === key && this.lastGeocodeAddress) {
                this._setAddress(this.lastGeocodeAddress);
                return;
            }

            if (this.geocodeTimer) {
                clearTimeout(this.geocodeTimer);
            }

            const api = this.api || this._getMapApi();
            this.geocodeTimer = setTimeout(async () => {
                // Prefer JS API Geocoder to avoid JSONP/CORS issues
                if (api && typeof api.Geocoder === 'function' && api.Point) {
                    const geocoder = new api.Geocoder();
                    const point = new api.Point(roundedLng, roundedLat);
                    geocoder.getLocation(point, (res) => {
                        if (!res) {
                            return;
                        }
                        const addressMode = this.options.address_mode || 'poi';
                        const fullAddress = res.address || '';
                        const poiName = (res.surroundingPois && res.surroundingPois.length > 0)
                            ? res.surroundingPois[0].title
                            : (res.business || '');
                        let address = '';
                        if (addressMode === 'full') {
                            address = fullAddress || poiName;
                        } else if (addressMode === 'both') {
                            if (poiName && fullAddress) {
                                address = `${poiName}（${fullAddress}）`;
                            } else {
                                address = poiName || fullAddress || '';
                            }
                        } else {
                            address = poiName || fullAddress || '';
                        }
                        this.lastGeocodeKey = key;
                        this.lastGeocodeAddress = address;
                        this._setAddress(address);
                    });
                    return;
                }

                try {
                    const data = await this._jsonp('https://api.map.baidu.com/reverse_geocoding/v3/', this._buildReverseParams(roundedLat, roundedLng));
                    if (data && data.status === 0 && data.result) {
                        const addressMode = this.options.address_mode || 'poi';
                        const poiName = data.result.sematic_description || '';
                        const fullAddress = data.result.formatted_address || '';
                        let address = '';
                        if (addressMode === 'full') {
                            address = fullAddress || poiName;
                        } else if (addressMode === 'both') {
                            if (poiName && fullAddress) {
                                address = `${poiName}（${fullAddress}）`;
                            } else {
                                address = poiName || fullAddress || '';
                            }
                        } else {
                            address = poiName || fullAddress || '';
                        }
                        this.lastGeocodeKey = key;
                        this.lastGeocodeAddress = address;
                        this._setAddress(address);
                    }
                    this._handleApiError(data, '逆地址解析失败');
                } catch (error) {
                    console.warn('[BMap] 逆地址解析失败', error);
                }
            }, 500);
        }

        async _searchSuggest(keyword) {
            const api = this.api || this._getMapApi();
            if (api && typeof api.LocalSearch === 'function' && this.map) {
                if (!this.localSearch) {
                    this.localSearch = new api.LocalSearch(this.map, {
                        onSearchComplete: (results) => {
                            if (!results || typeof results.getCurrentNumPois !== 'function') {
                                this._renderSuggest([]);
                                return;
                            }
                            const count = results.getCurrentNumPois();
                            const items = [];
                            for (let i = 0; i < count; i++) {
                                const poi = results.getPoi(i);
                                if (poi && poi.point) {
                                    items.push({
                                        name: poi.title || '',
                                        address: poi.address || '',
                                        location: { lng: poi.point.lng, lat: poi.point.lat },
                                        city: poi.city || ''
                                    });
                                }
                            }
                            this._renderSuggest(items);
                        }
                    });
                }
                this.localSearch.search(keyword);
                return;
            }

            try {
                const data = await this._jsonp('https://api.map.baidu.com/place/v2/suggestion/', this._buildSuggestParams(keyword));
                if (data && data.status === 0 && Array.isArray(data.result)) {
                    this._renderSuggest(data.result);
                } else {
                    this._renderSuggest([]);
                }
            } catch (error) {
                console.warn('[BMap] 地址搜索失败', error);
                this._renderSuggest([]);
            }
        }

        _renderSuggest(items) {
            if (!this.$suggest.length) {
                return;
            }
            if (!items || items.length === 0) {
                this.$suggest.hide().empty();
                return;
            }

            const html = items.map((item) => {
                const title = item.name || item.address || '';
                const address = item.address || '';
                const data = {
                    lat: item.location?.lat,
                    lng: item.location?.lng,
                    address: address || title,
                    city: item.city || ''
                };
                return `<button type="button" class="list-group-item list-group-item-action dp-bmap-item" data-lat="${data.lat}" data-lng="${data.lng}" data-address="${data.address}" data-city="${data.city}">
${title}<div class="small text-muted">${address}</div></button>`;
            }).join('');

            this.$suggest.html(html).show();

            this.$suggest.find('.dp-bmap-item').on('click', async (e) => {
                const $target = $(e.currentTarget);
                const lat = parseFloat($target.data('lat'));
                const lng = parseFloat($target.data('lng'));
                const address = $target.data('address') || '';
                if (!Number.isNaN(lat) && !Number.isNaN(lng)) {
                    this._setPosition({ lat, lng });
                    if (address) {
                        this._setAddress(address);
                    }
                    this._handleApiError(data, '逆地址解析失败');
                } else if (address) {
                    const resolved = await this._resolveCenterFromAddress(address);
                    if (resolved) {
                        this._setPosition({ lat: resolved.lat, lng: resolved.lng });
                        this._setAddress(resolved.address || address);
                    }
                }
                this._renderSuggest([]);
            });
        }

        _jsonp(url, params) {
            return new Promise((resolve, reject) => {
                const callbackName = `bmap_jsonp_${Date.now()}_${Math.random().toString(36).slice(2)}`;
                const query = new URLSearchParams(params || {});
                query.set('callback', callbackName);

                const script = document.createElement('script');
                script.src = `${url}?${query.toString()}`;

                window[callbackName] = (data) => {
                    resolve(data);
                    delete window[callbackName];
                    script.remove();
                };

                script.onerror = () => {
                    reject(new Error('JSONP请求失败'));
                    delete window[callbackName];
                    script.remove();
                };

                document.head.appendChild(script);
            });
        }
    }




    // ===========================================
    // Icon Picker Component
    // ===========================================
    /**
     * 图标选择器组件
     *
     * @class IconPickerComponent
     * @extends {BaseComponent}
     */
    class IconPickerComponent extends DpForm['BaseComponent'] {
        constructor(...args) {
            super(...args);
            this.$root = null;
            this.$input = null;
            this.$preview = null;
            this.$picker = null;
            this.$clear = null;
            this.$modal = null;
            this.$librariesJson = null;
            this.$focusProxy = null;
            this.$fallbackBackdrop = null;
            this._modalInstance = null;
            this.options = {};
            this.libraries = [];
            this.libraryMap = new Map();
            this.renderStates = new Map();
            this.statusStates = new Map();
            this.defaultIcon = 'fa fa-icons';
            this._renderVersion = 0;
            this._searchKeyword = '';
            this._filterTimer = null;
            this._isComposing = false;
        }

        onInit() {
            const $root = $(this.element).find('.dp-form-icon');
            if (!$root.length) {
                throw new Error('Icon container not found');
            }
            this.$root = $root;
            this.$input = $root.find('.dp-icon-input');
            this.$preview = $root.find('.dp-icon-preview i');
            this.$picker = $root.find('.dp-icon-picker');
            this.$clear = $root.find('.dp-icon-clear');
            this.$modal = $(this.element).find('.dp-icon-modal');
            this.$librariesJson = $(this.element).find('.dp-icon-libraries-json');
            this.$focusProxy = $('<button type="button" class="dp-icon-focus-proxy" tabindex="-1"></button>');
            $(this.element).append(this.$focusProxy);

            this._prepareModal();

            const rawOptions = $root.data('options');
            this.options = this._parseOptions(rawOptions);
            this.libraries = this._parseLibraries(this.$librariesJson.text());
            this.libraryMap = new Map(this.libraries.map((library) => [library.id, library]));
            this.defaultIcon = this.options.defaultIcon || this.options.default_icon || this.defaultIcon;

            this._setPreview(this.$input.val());
            this._bindEvents();
        }

        _parseOptions(rawOptions) {
            if (!rawOptions) {
                return {};
            }
            if (typeof rawOptions === 'string') {
                try {
                    return JSON.parse(rawOptions);
                } catch (error) {
                    console.warn('[IconPicker] 解析 options 失败', error);
                    return {};
                }
            }
            return rawOptions;
        }

        _parseLibraries(rawLibraries) {
            if (!rawLibraries) {
                return [];
            }

            try {
                const libraries = JSON.parse(rawLibraries);
                if (!Array.isArray(libraries)) {
                    return [];
                }

                return libraries
                    .map((library) => ({
                        id: String(library.id || '').trim(),
                        label: String(library.label || library.id || '').trim(),
                        icons: Array.isArray(library.icons)
                            ? library.icons
                                .map((icon) => String(icon || '').trim())
                                .filter(Boolean)
                            : [],
                        searchIndex: null
                    }))
                    .filter((library) => library.id && library.icons.length);
            } catch (error) {
                console.warn('[IconPicker] 解析图标库失败', error);
                return [];
            }
        }

        _bindEvents() {
            this.$input.on('input', () => {
                this._setPreview(this.$input.val());
            });

            this.$picker.on('click', () => {
                this._openModal();
            });

            this.$clear.on('click', () => {
                this.$input.val('').trigger('change');
                this._setPreview('');
            });

            // 使用 mousedown 而不是 click，在按钮获得焦点之前就转移焦点
            this.$modal.on('mousedown', '[data-bs-dismiss="modal"], .btn-close', (e) => {
                e.preventDefault();
            });

            this.$modal.on('click', '[data-bs-dismiss="modal"], .btn-close', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this._closeModal();
            });

            this.$modal.on('click', '.dp-icon-item', (e) => {
                const $target = $(e.currentTarget);
                const value = $target.data('value') || '';
                this._applyIcon(value);
                this._closeModal();
            });

            this.$modal.on('compositionstart', '.dp-icon-filter', () => {
                this._isComposing = true;
            });

            this.$modal.on('compositionend', '.dp-icon-filter', (e) => {
                this._isComposing = false;
                this._scheduleFilter($(e.currentTarget).val() || '');
            });

            this.$modal.on('input', '.dp-icon-filter', (e) => {
                if (this._isComposing) {
                    return;
                }
                this._scheduleFilter($(e.currentTarget).val() || '');
            });

            this.$modal.on('click', '.dp-icon-search-clear', (e) => {
                e.preventDefault();
                const $filter = this.$modal.find('.dp-icon-filter');
                $filter.val('');
                this._scheduleFilter('');
                $filter.trigger('focus');
            });

            this.$modal.on('shown.bs.modal', () => {
                const activeLibId = this._getActiveLibraryId();
                if (activeLibId && !this._searchKeyword) {
                    this._renderDefaultLibrary(activeLibId);
                }
            });

            this.$modal.on('shown.bs.tab', '[data-bs-toggle="tab"]', (e) => {
                if (this._searchKeyword) {
                    return;
                }

                const targetSelector = $(e.target).attr('data-bs-target') || $(e.target).attr('href');
                const $targetPane = targetSelector ? this.$modal.find(targetSelector) : $();
                const libId = String($targetPane.data('libId') || '');
                if (libId) {
                    this._renderDefaultLibrary(libId);
                }
            });

            this.$modal.on('hide.bs.modal', () => {
                this._cancelPendingRender();
                // 设置 inert 防止关闭动画期间用户误操作
                const modalEl = this.$modal[0];
                modalEl && modalEl.setAttribute('inert', '');
            });

            this.$modal.on('hidden.bs.modal', () => {
                const el = this.$modal[0];
                el && el.removeAttribute('inert');

                const $filter = this.$modal.find('.dp-icon-filter');
                $filter.val('');

                requestAnimationFrame(() => {
                    this._restoreDefaultView(false);
                });
            });
        }

        _prepareModal() {
            if (!this.$modal || !this.$modal.length) {
                return;
            }

            if (!this.$modal.parent().is('body')) {
                $('body').append(this.$modal);
            }

            // 用原生捕获阶段监听 hide.bs.modal，确保在 Bootstrap 设置 aria-hidden 之前转移焦点
            this.$modal[0].addEventListener('hide.bs.modal', () => {
                const modalEl = this.$modal[0];
                if (modalEl && document.activeElement && modalEl.contains(document.activeElement)) {
                    document.activeElement.blur();
                }
                if (this.$focusProxy && this.$focusProxy.length) {
                    this.$focusProxy[0].focus();
                }
            }, true);
        }

        _getActiveLibraryId() {
            const $activeLink = this.$modal.find('.nav-tabs .nav-link.active').first();
            const targetSelector = $activeLink.attr('data-bs-target') || $activeLink.attr('href');
            const $activePane = targetSelector
                ? this.$modal.find(targetSelector)
                : this.$modal.find('.tab-pane').first();
            return String($activePane.data('libId') || '');
        }

        _getPane(libId) {
            return this.$modal.find(`.tab-pane[data-lib-id="${libId}"]`).first();
        }

        _getLibrary(libId) {
            const library = this.libraryMap.get(libId);
            if (!library) {
                return null;
            }

            if (!Array.isArray(library.searchIndex)) {
                library.searchIndex = library.icons.map((icon) => icon.toLowerCase());
            }

            return library;
        }

        _setPreview(value) {
            const val = (value || '').trim();
            if (!val) {
                this.$preview.attr('class', this.defaultIcon || '');
                return;
            }
            this.$preview.attr('class', val);
        }

        _applyIcon(value) {
            this.$input.val(value).trigger('change');
            this._setPreview(value);
        }

        _openModal() {
            const modalEl = this.$modal[0];
            modalEl && modalEl.removeAttribute('inert');

            if (window.bootstrap && typeof window.bootstrap.Modal === 'function') {
                if (!this._modalInstance) {
                    if (typeof window.bootstrap.Modal.getOrCreateInstance === 'function') {
                        this._modalInstance = window.bootstrap.Modal.getOrCreateInstance(modalEl, {
                            focus: false,
                            backdrop: true
                        });
                    } else {
                        this._modalInstance = new window.bootstrap.Modal(modalEl, {
                            focus: false,
                            backdrop: true
                        });
                    }
                }
                this._modalInstance.show();
            } else {
                this._ensureFallbackBackdrop();
                $('body').addClass('modal-open');
                this.$modal.addClass('show').css('display', 'block');
                this.$modal.triggerHandler('shown.bs.modal');
            }
        }

        _closeModal() {
            if (this._modalInstance) {
                this._modalInstance.hide();
            } else {
                this.$modal.triggerHandler('hide.bs.modal');
                this._removeFallbackBackdrop();
                $('body').removeClass('modal-open');
                this.$modal.removeClass('show').css('display', 'none');
                this.$modal.triggerHandler('hidden.bs.modal');
            }
        }

        _ensureFallbackBackdrop() {
            if (this.$fallbackBackdrop && this.$fallbackBackdrop.length) {
                return;
            }

            this.$fallbackBackdrop = $('<div class="modal-backdrop fade show dp-icon-modal-backdrop"></div>');
            this.$fallbackBackdrop.on('click', () => {
                this._closeModal();
            });
            $('body').append(this.$fallbackBackdrop);
        }

        _removeFallbackBackdrop() {
            if (!this.$fallbackBackdrop || !this.$fallbackBackdrop.length) {
                return;
            }

            this.$fallbackBackdrop.off('click');
            this.$fallbackBackdrop.remove();
            this.$fallbackBackdrop = null;
        }

        _scheduleFilter(keyword) {
            if (this._filterTimer) {
                clearTimeout(this._filterTimer);
            }

            this._filterTimer = setTimeout(() => {
                this._filterList(keyword);
            }, 120);
        }

        _cancelPendingRender() {
            this._renderVersion += 1;
        }

        _filterList(keyword) {
            const normalizedKeyword = String(keyword || '').trim().toLowerCase();
            const displayKeyword = String(keyword || '').trim();

            if (!normalizedKeyword) {
                this._restoreDefaultView(true);
                return;
            }

            this._searchKeyword = normalizedKeyword;
            this.$modal.find('.nav-tabs').hide();
            this.$modal.find('.tab-content').addClass('dp-icon-filtering');

            const plans = this.libraries.map((library) => ({
                libId: library.id,
                icons: this._matchIcons(library.id, normalizedKeyword),
                mode: 'search',
                keyword: normalizedKeyword,
                emptyText: `未找到包含 "${displayKeyword}" 的图标`
            }));

            this._renderPlans(plans);
        }

        _restoreDefaultView(renderActive = true) {
            this._searchKeyword = '';
            this._cancelPendingRender();

            this.$modal.find('.nav-tabs').show();
            this.$modal.find('.tab-content').removeClass('dp-icon-filtering');
            this.$modal.find('.tab-pane').each((_, pane) => {
                this._clearPane($(pane), '等待加载...');
            });
            this.renderStates.clear();

            if (!renderActive) {
                return;
            }

            const activeLibId = this._getActiveLibraryId();
            if (activeLibId) {
                this._renderDefaultLibrary(activeLibId, true);
            }
        }

        _renderDefaultLibrary(libId, force = false) {
            const library = this._getLibrary(libId);
            if (!library) {
                return;
            }

            const state = this.renderStates.get(libId);
            if (!force && state && state.mode === 'default') {
                return;
            }

            this._renderPlans([{
                libId,
                icons: library.icons,
                mode: 'default',
                keyword: '',
                emptyText: '暂无可用图标'
            }]);
        }

        _renderPlans(plans) {
            const token = ++this._renderVersion;
            const tasks = [];

            plans.forEach((plan) => {
                const $pane = this._getPane(plan.libId);
                if (!$pane.length) {
                    return;
                }

                this._clearPane($pane, plan.icons.length ? '加载中...' : '');
                if (!plan.icons.length) {
                    this._toggleNoResultForLib($pane, true, plan.emptyText);
                    this.renderStates.set(plan.libId, {
                        mode: plan.mode,
                        keyword: plan.keyword
                    });
                    return;
                }

                tasks.push({
                    plan,
                    $pane,
                    listEl: $pane.find('.dp-icon-list')[0],
                    cursor: 0
                });
            });

            if (!tasks.length) {
                return;
            }

            const step = () => {
                if (token !== this._renderVersion) {
                    return;
                }

                let frameBudget = tasks.length === 1 ? 240 : 160;

                while (tasks.length && frameBudget > 0) {
                    const task = tasks[0];
                    const chunkSize = task.plan.mode === 'default' ? 120 : 80;
                    const remaining = task.plan.icons.length - task.cursor;
                    const take = Math.min(chunkSize, frameBudget, remaining);
                    const slice = task.plan.icons.slice(task.cursor, task.cursor + take);

                    if (slice.length) {
                        task.listEl.insertAdjacentHTML('beforeend', this._buildButtonsHtml(slice));
                        task.cursor += slice.length;
                        frameBudget -= slice.length;
                    }

                    if (task.cursor >= task.plan.icons.length) {
                        this._setPaneStatus(task.$pane, '');
                        this.renderStates.set(task.plan.libId, {
                            mode: task.plan.mode,
                            keyword: task.plan.keyword
                        });
                        tasks.shift();
                    }
                }

                if (tasks.length) {
                    requestAnimationFrame(step);
                }
            };

            requestAnimationFrame(step);
        }

        _matchIcons(libId, keyword) {
            const library = this._getLibrary(libId);
            if (!library) {
                return [];
            }

            const matches = [];
            for (let i = 0; i < library.searchIndex.length; i++) {
                if (library.searchIndex[i].includes(keyword)) {
                    matches.push(library.icons[i]);
                }
            }

            return matches;
        }

        _clearPane($pane, statusText = '') {
            const $list = $pane.find('.dp-icon-list');
            $list.empty();
            $pane.find('.dp-icon-no-result').remove();
            this._setPaneStatus($pane, statusText);
        }

        _getPaneStatusKey($pane) {
            return String($pane.data('libId') || $pane.attr('id') || '');
        }

        _getPaneStatusState($pane) {
            const key = this._getPaneStatusKey($pane);
            if (!this.statusStates.has(key)) {
                this.statusStates.set(key, {
                    showTimer: null,
                    hideTimer: null,
                    shownAt: 0,
                    visible: false
                });
            }

            return this.statusStates.get(key);
        }

        _clearStatusTimer(state, timerName) {
            if (!state || !state[timerName]) {
                return;
            }

            clearTimeout(state[timerName]);
            state[timerName] = null;
        }

        _setPaneStatus($pane, text = '') {
            let $status = $pane.find('.dp-icon-render-status');
            if (!$status.length) {
                $status = $('<div class="dp-icon-render-status text-muted"></div>');
                $pane.find('.dp-icon-pane-body').append($status);
            }

            const state = this._getPaneStatusState($pane);
            const showDelay = 120;
            const minVisible = 220;

            this._clearStatusTimer(state, 'showTimer');
            this._clearStatusTimer(state, 'hideTimer');

            if (!text) {
                if (!state.visible) {
                    $status.removeClass('is-visible').html('');
                    return;
                }

                const elapsed = Date.now() - state.shownAt;
                const hide = () => {
                    $status.removeClass('is-visible').html('');
                    state.visible = false;
                    state.shownAt = 0;
                    state.hideTimer = null;
                };

                if (elapsed >= minVisible) {
                    hide();
                } else {
                    state.hideTimer = setTimeout(hide, minVisible - elapsed);
                }
                return;
            }

            const show = () => {
                $status
                    .addClass('is-visible')
                    .html(text);
                state.visible = true;
                state.shownAt = Date.now();
                state.showTimer = null;
            };

            if (state.visible) {
                show();
                return;
            }

            state.showTimer = setTimeout(show, showDelay);
        }

        _buildButtonsHtml(icons) {
            let html = '';
            for (let i = 0; i < icons.length; i++) {
                const value = this._escapeHtml(icons[i]);
                html += `<button type="button" class="dp-icon-item" data-value="${value}" title="${value}"><i class="${value}"></i></button>`;
            }
            return html;
        }

        _toggleNoResultForLib($pane, show, message = '') {
            let $noResult = $pane.find('.dp-icon-no-result');

            if (show) {
                if (!$noResult.length) {
                    $noResult = $('<div class="dp-icon-no-result"></div>');
                    $pane.find('.dp-icon-list').after($noResult);
                }
                $noResult.html(`<p class="text-muted mb-0">${this._escapeHtml(message)}</p>`).show();
            } else {
                $noResult.remove();
            }
        }

        _escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        async onDestroy() {
            try {
                this._cancelPendingRender();
                if (this._filterTimer) {
                    clearTimeout(this._filterTimer);
                }
                this._removeFallbackBackdrop();
                $('body').removeClass('modal-open');

                if (this._modalInstance && typeof this._modalInstance.dispose === 'function') {
                    this._modalInstance.dispose();
                }

                if (this.$modal && this.$modal.length) {
                    this.$modal.off();
                    this.$modal.remove();
                }

                if (this.$focusProxy && this.$focusProxy.length) {
                    this.$focusProxy.remove();
                }

                this.$modal = null;
                this.$librariesJson = null;
                this.$focusProxy = null;
                this._modalInstance = null;
                this.libraryMap.clear();
                this.renderStates.clear();
                this.statusStates.forEach((state) => {
                    this._clearStatusTimer(state, 'showTimer');
                    this._clearStatusTimer(state, 'hideTimer');
                });
                this.statusStates.clear();
            } catch (error) {
                console.warn('IconPickerComponent destroy failed:', error);
            }

            if (super.onDestroy) {
                await super.onDestroy();
            }
        }
    }

    // ===========================================
    // Linkage Component
    // ===========================================
    /**
     * 多级联动组件
     *
     * @class LinkageComponent
     * @extends {BaseComponent}
     */
    class LinkageComponent extends DpForm['BaseComponent'] {
        constructor(...args) {
            super(...args);
            this.$root = null;
            this.$selects = null;
            this.options = {};
            this.url = '';
            this.levels = [];
            this.values = {};
            this.multiple = false;
            this._loadingCount = 0;
            this._isInitializing = false;
        }

        async onInit() {
            const $root = $(this.element).find('.dp-form-linkage');
            if (!$root.length) {
                throw new Error('Linkage container not found');
            }
            this.$root = $root;
            this.$selects = $root.find('select[data-level-key]');

            const rawOptions = $root.data('options');
            this.options = this._parseOptions(rawOptions);
            this.url = this.options.url || '';
            this.levels = Array.isArray(this.options.levels) ? this.options.levels : [];
            this.values = this.options.values || {};
            this.multiple = !!this.options.multiple;

            this._isInitializing = true;
            this._showLoading();
            try {
                this._bindEvents();
                this._initLastSelectMultiple();
                await this._applyInitialValues();
            } finally {
                this._isInitializing = false;
                this._hideLoading();
            }
        }

        _parseOptions(rawOptions) {
            if (!rawOptions) {
                return {};
            }
            if (typeof rawOptions === 'string') {
                try {
                    return JSON.parse(rawOptions);
                } catch (error) {
                    console.warn('[Linkage] 解析 options 失败', error);
                    return {};
                }
            }
            return rawOptions;
        }

        _bindEvents() {
            this.$selects.on('change', (e) => {
                const $select = $(e.currentTarget);
                this._handleChange($select);
            });
        }

        _handleChange($select) {
            const index = this.$selects.index($select);
            if (index < 0) {
                return;
            }

            this._resetAfter(index);

            if (index >= this.$selects.length - 1) {
                return;
            }

            const value = ($select.val() || '').toString();
            if (!value) {
                return;
            }

            this._loadNext(index);
        }

        _resetAfter(index) {
            for (let i = index + 1; i < this.$selects.length; i++) {
                this._resetSelect(this.$selects.eq(i));
            }
        }

        _resetSelect($select) {
            const placeholder = $select.data('placeholder') || this.options.emptyText || '请选择';
            $select.empty();
            if (!$select.prop('multiple')) {
                $select.append(`<option value="">${placeholder}</option>`);
            }
        }

        _initLastSelectMultiple() {
            if (!this.multiple || !this.$selects || !this.$selects.length) {
                return;
            }

            const $last = this.$selects.last();
            if (!$last.length || !$last.prop('multiple')) {
                return;
            }

            if (!$.fn || typeof $.fn.select2 !== 'function') {
                return;
            }

            $last.select2({
                width: '100%',
                theme: 'bootstrap-5',
                language: 'zh-CN',
                placeholder: $last.data('placeholder') || this.options.emptyText || '请选择',
                closeOnSelect: false,
                selectionCssClass: 'select2--small dp-linkage-select2-multiple',
            });
        }

        _getParamsUpTo(index) {
            const params = {};
            for (let i = 0; i <= index; i++) {
                const $select = this.$selects.eq(i);
                const key = $select.data('level-key');
                const value = ($select.val() || '').toString();
                if (!key) {
                    continue;
                }
                if (!value) {
                    if (i === index) {
                        return null;
                    }
                    continue;
                }
                params[key] = value;
            }
            return params;
        }

        _getLevelConfig(levelIndex) {
            if (!Array.isArray(this.levels) || levelIndex < 0 || levelIndex >= this.levels.length) {
                return {};
            }
            const level = this.levels[levelIndex];
            return level && typeof level === 'object' ? level : {};
        }

        _resolveRequestUrl(levelIndex) {
            const levelConfig = this._getLevelConfig(levelIndex);
            const levelUrl = typeof levelConfig.url === 'string' ? levelConfig.url.trim() : '';
            return levelUrl || this.url || '';
        }

        _buildLevelParams(index, levelIndex) {
            const params = this._getParamsUpTo(index);
            if (!params) {
                return null;
            }

            const levelConfig = this._getLevelConfig(levelIndex);
            let requestParams = { ...params };

            const requestKeys = Array.isArray(levelConfig.request_keys) ? levelConfig.request_keys : [];
            if (requestKeys.length > 0) {
                const keySet = new Set(requestKeys.map((key) => String(key)));
                requestParams = Object.keys(requestParams).reduce((carry, key) => {
                    if (keySet.has(key)) {
                        carry[key] = requestParams[key];
                    }
                    return carry;
                }, {});
            }

            if (levelConfig.params && typeof levelConfig.params === 'object' && !Array.isArray(levelConfig.params)) {
                requestParams = {
                    ...requestParams,
                    ...levelConfig.params,
                };
            }

            if (levelConfig.request_fields && typeof levelConfig.request_fields === 'object' && !Array.isArray(levelConfig.request_fields)) {
                Object.keys(levelConfig.request_fields).forEach((requestKey) => {
                    const fieldName = levelConfig.request_fields[requestKey];
                    if (typeof fieldName !== 'string' || fieldName === '') {
                        return;
                    }
                    const fieldValue = this._getFormFieldValue(fieldName);
                    if (this._isEmptyParamValue(fieldValue)) {
                        return;
                    }
                    requestParams[requestKey] = fieldValue;
                });
            }

            return requestParams;
        }

        _getFormFieldValue(fieldName) {
            const $form = this.element.closest('form');
            const $container = $form.length ? $form : $(document);
            const escapedName = this._escapeSelectorValue(fieldName);
            const selectors = [
                `[name=\"${escapedName}\"]`,
                `[name=\"${escapedName}[]\"]`,
                `[name^=\"${escapedName}[\"]`,
            ];
            const $fields = $container.find(selectors.join(',')).filter(':input');

            if (!$fields.length) {
                return null;
            }

            const $radios = $fields.filter('[type="radio"]');
            if ($radios.length) {
                const $checked = $radios.filter(':checked');
                return $checked.length ? ($checked.val() ?? '') : '';
            }

            const $checkboxes = $fields.filter('[type="checkbox"]');
            if ($checkboxes.length) {
                const checkedValues = [];
                $checkboxes.filter(':checked').each((_, checkbox) => {
                    checkedValues.push($(checkbox).val());
                });
                if ($checkboxes.length === 1 && !String($checkboxes.first().attr('name') || '').endsWith('[]')) {
                    return checkedValues.length ? checkedValues[0] : '';
                }
                return checkedValues;
            }

            const $first = $fields.first();
            if ($first.is('select[multiple]')) {
                return $first.val() || [];
            }

            if ($fields.length > 1) {
                return $fields.map((_, field) => $(field).val()).get();
            }

            return $first.val();
        }

        _isEmptyParamValue(value) {
            if (value === null || value === undefined || value === '') {
                return true;
            }
            if (Array.isArray(value) && value.length === 0) {
                return true;
            }
            return false;
        }

        _escapeSelectorValue(value) {
            if (window.CSS && typeof window.CSS.escape === 'function') {
                return window.CSS.escape(String(value));
            }
            return String(value).replace(/["\\]/g, '\\$&');
        }

        _extractOptions(data) {
            if (Array.isArray(data)) {
                return data;
            }

            if (data && typeof data === 'object') {
                if (Object.prototype.hasOwnProperty.call(data, 'code') && Number(data.code) === 0) {
                    const message = data.msg || data.message || '联动数据加载失败';
                    this._showError(message);
                    return [];
                }

                if (Array.isArray(data.data)) {
                    return data.data;
                }

                return Object.keys(data).map((key) => ({
                    key: key,
                    value: data[key]
                }));
            }
            return [];
        }

        _showError(message) {
            const msg = message || '联动数据加载失败';
            if (window.Dolphin && typeof window.Dolphin.error === 'function') {
                window.Dolphin.error(msg);
                return;
            }
            console.warn('[Linkage] ' + msg);
        }

        _showLoading() {
            if (!window.Dolphin || typeof window.Dolphin.loading !== 'function') {
                return;
            }
            this._loadingCount += 1;
            if (this._loadingCount === 1) {
                window.Dolphin.loading();
            }
        }

        _hideLoading() {
            if (!window.Dolphin || typeof window.Dolphin.loading !== 'function') {
                return;
            }
            this._loadingCount = Math.max(0, this._loadingCount - 1);
            if (this._loadingCount === 0) {
                window.Dolphin.loading('hide');
            }
        }

        _normalizeOptions(list) {
            const result = [];
            if (!Array.isArray(list)) {
                return result;
            }
            list.forEach((item) => {
                if (item && typeof item === 'object') {
                    if ('key' in item && 'value' in item) {
                        result.push({
                            key: String(item.key),
                            value: item.value
                        });
                        return;
                    }
                }
            });
            return result;
        }

        _fillSelect($select, list) {
            this._resetSelect($select);
            const options = this._normalizeOptions(list);
            options.forEach((item) => {
                $select.append(`<option value="${this._escapeHtml(item.key)}">${item.value ?? ''}</option>`);
            });
            if ($select.prop('multiple') && $select.hasClass('select2-hidden-accessible')) {
                $select.trigger('change.select2');
            }
        }

        _setSelectValue($select, value) {
            if (!$select || !$select.length || value === null || value === undefined || value === '') {
                return;
            }

            if ($select.prop('multiple')) {
                if (Array.isArray(value)) {
                    $select.val(value.map((item) => String(item)));
                    return;
                }
                $select.val([String(value)]);
                return;
            }

            if (Array.isArray(value)) {
                $select.val(value.length ? String(value[0]) : '');
                return;
            }

            $select.val(String(value));
        }

        _loadNext(index, preselectValue = null) {
            const nextLevelIndex = index + 1;
            const requestUrl = this._resolveRequestUrl(nextLevelIndex);
            if (!requestUrl) {
                return Promise.resolve();
            }

            const params = this._buildLevelParams(index, nextLevelIndex);
            if (!params) {
                return Promise.resolve();
            }

            const $next = this.$selects.eq(nextLevelIndex);
            const shouldHandleLoading = !this._isInitializing;
            if (shouldHandleLoading) {
                this._showLoading();
            }
            return $.get(requestUrl, params)
                .done((resp) => {
                    const list = this._extractOptions(resp);
                    this._fillSelect($next, list);
                    if (preselectValue !== null && preselectValue !== undefined && preselectValue !== '') {
                        this._setSelectValue($next, preselectValue);
                    }
                })
                .fail((xhr) => {
                    const message = (xhr && xhr.responseJSON && (xhr.responseJSON.msg || xhr.responseJSON.message))
                        || '联动数据请求失败';
                    this._showError(message);
                    this._resetSelect($next);
                })
                .always(() => {
                    if (shouldHandleLoading) {
                        this._hideLoading();
                    }
                });
        }

        async _applyInitialValues() {
            if (!this.values || typeof this.values !== 'object') {
                return;
            }

            if (this.$selects.length === 0) {
                return;
            }

            if (this.$selects.length === 1) {
                const key = this.$selects.eq(0).data('level-key');
                const value = this.values[key];
                if (value !== undefined && value !== null && value !== '') {
                    this.$selects.eq(0).val(String(value));
                }
                return;
            }

            for (let i = 0; i < this.$selects.length - 1; i++) {
                const $select = this.$selects.eq(i);
                const key = $select.data('level-key');
                const value = this.values[key];

                if (value === undefined || value === null || value === '') {
                    break;
                }

                this._setSelectValue($select, value);

                const nextKey = this.$selects.eq(i + 1).data('level-key');
                const nextValue = nextKey ? this.values[nextKey] : null;
                await this._loadNext(i, nextValue);
            }
        }

        _escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    }



    // ===========================================
    // Linkages Component
    // ===========================================
    /**
     * 快速联动组件
     *
     * @class LinkagesComponent
     * @extends {BaseComponent}
     */
    class LinkagesComponent extends DpForm['BaseComponent'] {
        constructor(...args) {
            super(...args);
            this.$root = null;
            this.$selects = null;
            this.options = {};
            this.url = '';
            this.token = '';
            this.values = {};
            this.multiple = false;
            this._loadingCount = 0;
            this._isInitializing = false;
        }

        async onInit() {
            const $root = $(this.element).find('.dp-form-linkages');
            if (!$root.length) {
                throw new Error('Linkages container not found');
            }
            this.$root = $root;
            this.$selects = $root.find('select[data-level-key]');

            this.options = this._parseOptions($root.data('options'));
            this.url = this.options.url || '';
            this.token = this.options.token || '';
            this.values = this.options.values || {};
            this.multiple = !!this.options.multiple;

            this._isInitializing = true;
            this._showLoading();
            try {
                this._bindEvents();
                this._initLastSelectMultiple();
                await this._loadLevel(0);
                await this._applyInitialValues();
            } finally {
                this._isInitializing = false;
                this._hideLoading();
            }
        }

        _parseOptions(rawOptions) {
            if (!rawOptions) {
                return {};
            }
            if (typeof rawOptions === 'string') {
                try {
                    return JSON.parse(rawOptions);
                } catch (error) {
                    console.warn('[Linkages] 解析 options 失败', error);
                    return {};
                }
            }
            return rawOptions;
        }

        _bindEvents() {
            this.$selects.on('change', (e) => {
                const $select = $(e.currentTarget);
                this._handleChange($select);
            });
        }

        _handleChange($select) {
            const index = this.$selects.index($select);
            if (index < 0) {
                return;
            }

            this._resetAfter(index);

            if (index >= this.$selects.length - 1) {
                return;
            }

            const value = ($select.val() || '').toString();
            if (!value) {
                return;
            }

            this._loadLevel(index + 1, value);
        }

        _resetAfter(index) {
            for (let i = index + 1; i < this.$selects.length; i++) {
                this._resetSelect(this.$selects.eq(i));
            }
        }

        _resetSelect($select) {
            const placeholder = $select.data('placeholder') || this.options.emptyText || '请选择';
            $select.empty();
            if (!$select.prop('multiple')) {
                $select.append(`<option value="">${placeholder}</option>`);
            }
        }

        _initLastSelectMultiple() {
            if (!this.multiple || !this.$selects || !this.$selects.length) {
                return;
            }

            const $last = this.$selects.last();
            if (!$last.length || !$last.prop('multiple')) {
                return;
            }

            if (!$.fn || typeof $.fn.select2 !== 'function') {
                return;
            }

            $last.select2({
                width: '100%',
                theme: 'bootstrap-5',
                language: 'zh-CN',
                placeholder: $last.data('placeholder') || this.options.emptyText || '请选择',
                closeOnSelect: false,
                selectionCssClass: 'select2--small dp-linkage-select2-multiple',
            });
        }

        _normalizeOptions(list) {
            if (!Array.isArray(list)) {
                return [];
            }

            const result = [];
            list.forEach((item) => {
                if (item && typeof item === 'object' && Object.prototype.hasOwnProperty.call(item, 'key') && Object.prototype.hasOwnProperty.call(item, 'value')) {
                    result.push({
                        key: String(item.key),
                        value: item.value
                    });
                }
            });
            return result;
        }

        _extractOptions(resp) {
            if (Array.isArray(resp)) {
                return this._normalizeOptions(resp);
            }

            if (resp && typeof resp === 'object') {
                if (Object.prototype.hasOwnProperty.call(resp, 'code') && Number(resp.code) === 0) {
                    const message = resp.msg || resp.message || '联动数据加载失败';
                    this._showError(message);
                    return [];
                }
                if (Array.isArray(resp.data)) {
                    return this._normalizeOptions(resp.data);
                }
            }

            return [];
        }

        _fillSelect($select, list) {
            this._resetSelect($select);
            list.forEach((item) => {
                $select.append(`<option value="${this._escapeHtml(item.key)}">${item.value ?? ''}</option>`);
            });
            if ($select.prop('multiple') && $select.hasClass('select2-hidden-accessible')) {
                $select.trigger('change.select2');
            }
        }

        _setSelectValue($select, value) {
            if (!$select || !$select.length || value === null || value === undefined || value === '') {
                return;
            }

            if ($select.prop('multiple')) {
                if (Array.isArray(value)) {
                    $select.val(value.map((item) => String(item)));
                    return;
                }
                $select.val([String(value)]);
                return;
            }

            if (Array.isArray(value)) {
                $select.val(value.length ? String(value[0]) : '');
                return;
            }

            $select.val(String(value));
        }

        _showError(message) {
            const msg = message || '联动数据加载失败';
            if (window.Dolphin && typeof window.Dolphin.error === 'function') {
                window.Dolphin.error(msg);
                return;
            }
            console.warn('[Linkages] ' + msg);
        }

        _showLoading() {
            if (!window.Dolphin || typeof window.Dolphin.loading !== 'function') {
                return;
            }
            this._loadingCount += 1;
            if (this._loadingCount === 1) {
                window.Dolphin.loading();
            }
        }

        _hideLoading() {
            if (!window.Dolphin || typeof window.Dolphin.loading !== 'function') {
                return;
            }
            this._loadingCount = Math.max(0, this._loadingCount - 1);
            if (this._loadingCount === 0) {
                window.Dolphin.loading('hide');
            }
        }

        _loadLevel(levelIndex, parentValue = null, preselectValue = null) {
            if (!this.url || !this.token) {
                return Promise.resolve();
            }

            const $select = this.$selects.eq(levelIndex);
            if (!$select.length) {
                return Promise.resolve();
            }

            const params = {
                token: this.token,
                level: levelIndex + 1,
            };
            if (parentValue !== null && parentValue !== undefined && parentValue !== '') {
                params.parent = parentValue;
            }

            const shouldHandleLoading = !this._isInitializing;
            if (shouldHandleLoading) {
                this._showLoading();
            }
            return $.get(this.url, params)
                .done((resp) => {
                    const list = this._extractOptions(resp);
                    this._fillSelect($select, list);
                    if (preselectValue !== null && preselectValue !== undefined && preselectValue !== '') {
                        this._setSelectValue($select, preselectValue);
                    }
                })
                .fail((xhr) => {
                    const message = (xhr && xhr.responseJSON && (xhr.responseJSON.msg || xhr.responseJSON.message))
                        || '联动数据请求失败';
                    this._showError(message);
                    this._resetSelect($select);
                })
                .always(() => {
                    if (shouldHandleLoading) {
                        this._hideLoading();
                    }
                });
        }

        async _applyInitialValues() {
            if (!this.values || typeof this.values !== 'object' || this.$selects.length === 0) {
                return;
            }

            for (let i = 0; i < this.$selects.length; i++) {
                const $select = this.$selects.eq(i);
                const key = $select.data('level-key');
                const value = this.values[key];

                if (value === undefined || value === null || value === '') {
                    break;
                }

                this._setSelectValue($select, value);

                if (i >= this.$selects.length - 1) {
                    break;
                }

                await this._loadLevel(i + 1, value, this.values[this.$selects.eq(i + 1).data('level-key')]);
            }
        }

        _escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    }

    // ===========================================
    // AMap Component
    // ===========================================
    /**
     * AMap 组件
     * 基于高德地图 JS API 的选点组件
     *
     * @class AMapComponent
     * @extends {BaseComponent}
     */
    class AMapComponent extends DpForm['BaseComponent'] {
        constructor(...args) {
            super(...args);
            this.map = null;
            this.marker = null;
            this.options = {};
            this.key = '';
            this.readonly = false;
            this.$root = null;
            this.$valueInput = null;
            this.$addressInput = null;
            this.$searchInput = null;
            this.$suggest = null;
            this.searchTimer = null;
            this.geocodeTimer = null;
            this.lastGeocodeKey = '';
            this.lastGeocodeAddress = '';
            this.autoComplete = null;
            this.geocoder = null;
        }

        async onInit() {
            const $root = $(this.element).find('.dp-form-amap');
            if (!$root.length) {
                throw new Error('AMap container not found');
            }

            this.$root = $root;
            this.$valueInput = $(this.element).find('.dp-amap-value');
            this.$addressInput = $(this.element).find('.dp-amap-address-input');
            this.$searchInput = $(this.element).find('.dp-amap-search');
            this.$suggest = $(this.element).find('.dp-amap-suggest');

            const rawOptions = $root.data('options');
            this.options = this._parseOptions(rawOptions);
            this.readonly = Boolean($root.data('readonly'));
            this.key = this.options.key || '';

            if (!this.key) {
                console.error('[AMap] 未配置高德地图 key');
                if (window.Dolphin && typeof Dolphin.error === 'function') {
                    Dolphin.error('高德地图 Key 未配置');
                }
                return;
            }

            await this._loadMap();
            await this._initMap();
            this._bindEvents();
        }

        _parseOptions(rawOptions) {
            if (!rawOptions) {
                return {};
            }
            if (typeof rawOptions === 'string') {
                try {
                    return JSON.parse(rawOptions);
                } catch (error) {
                    console.warn('[AMap] 解析 options 失败', error);
                    return {};
                }
            }
            return rawOptions;
        }

        _parseLngLat(value) {
            if (!value || typeof value !== 'string') {
                return null;
            }
            const parts = value.split(',');
            if (parts.length !== 2) {
                return null;
            }
            const lng = parseFloat(parts[0]);
            const lat = parseFloat(parts[1]);
            if (Number.isNaN(lng) || Number.isNaN(lat)) {
                return null;
            }
            return { lng, lat };
        }

        _loadMap() {
            if (window.AMap && window.AMap.Map) {
                return Promise.resolve();
            }

            if (!AMapComponent._loader) {
                const key = encodeURIComponent(this.key);
                const plugins = Array.isArray(this.options.plugins) && this.options.plugins.length
                    ? this.options.plugins
                    : ['AMap.Geocoder', 'AMap.AutoComplete'];
                const pluginParam = encodeURIComponent(plugins.join(','));
                const securityJsCode = this.options.securityJsCode || this.options.security_js_code || '';
                const serviceHost = this.options.serviceHost || this.options.service_host || '';
                if (securityJsCode || serviceHost) {
                    window._AMapSecurityConfig = window._AMapSecurityConfig || {};
                    if (securityJsCode) {
                        window._AMapSecurityConfig.securityJsCode = securityJsCode;
                    }
                    if (serviceHost) {
                        window._AMapSecurityConfig.serviceHost = serviceHost;
                    }
                }
                AMapComponent._loader = new Promise((resolve, reject) => {
                    const script = document.createElement('script');
                    script.src = `https://webapi.amap.com/maps?v=2.0&key=${key}&plugin=${pluginParam}`;
                    script.onload = () => {
                        if (window.AMap && window.AMap.Map) {
                            resolve();
                        } else {
                            reject(new Error('高德地图 API 未加载'));
                        }
                    };
                    script.onerror = () => reject(new Error('高德地图脚本加载失败'));
                    document.head.appendChild(script);
                });
            }

            return AMapComponent._loader;
        }

        async _resolveCenterFromAddress(address) {
            if (!address) {
                return null;
            }
            const geocoder = await this._ensureGeocoder();
            if (!geocoder) {
                return null;
            }
            return await new Promise((resolve) => {
                geocoder.getLocation(address, (status, result) => {
                    if (status === 'complete' && result && result.geocodes && result.geocodes.length) {
                        const location = result.geocodes[0].location;
                        resolve({ lng: location.lng, lat: location.lat, address: result.geocodes[0].formattedAddress || address });
                    } else {
                        resolve(null);
                    }
                });
            });
        }

        _ensureGeocoder() {
            if (this.geocoder) {
                return Promise.resolve(this.geocoder);
            }
            if (window.AMap && typeof window.AMap.Geocoder === 'function') {
                this.geocoder = new window.AMap.Geocoder({ city: (this.options.city || this.options.region || undefined) });
                return Promise.resolve(this.geocoder);
            }
            if (window.AMap && typeof window.AMap.plugin === 'function') {
                return new Promise((resolve) => {
                    window.AMap.plugin('AMap.Geocoder', () => {
                        this.geocoder = new window.AMap.Geocoder({ city: (this.options.city || this.options.region || undefined) });
                        resolve(this.geocoder);
                    });
                });
            }
            return Promise.resolve(null);
        }

        _getAutoComplete() {
            if (this.autoComplete) {
                return this.autoComplete;
            }
            if (window.AMap && typeof window.AMap.AutoComplete === 'function') {
                const options = Object.assign({}, this.options.autocompleteOptions || {});
                if (this.options.city && options.city === undefined) {
                    options.city = this.options.city;
                }
                if (this.options.region && options.city === undefined) {
                    options.city = this.options.region;
                }
                this.autoComplete = new window.AMap.AutoComplete(options);
            }
            return this.autoComplete;
        }

        async _initMap() {
            const mapContainer = this.$root.find('.dp-amap-canvas')[0];
            const zoom = Number(this.options.zoom) || 15;
            const value = this.$valueInput.val();
            const centerValue = this._parseLngLat(value) || this._parseLngLat(this.options.center);
            let center = centerValue || { lng: 116.3975, lat: 39.9087 };

            if (!centerValue) {
                const address = this.options.address || this.$addressInput.val();
                if (address) {
                    const resolved = await this._resolveCenterFromAddress(address);
                    if (resolved) {
                        center = { lng: resolved.lng, lat: resolved.lat };
                        this._setValue(resolved.lng, resolved.lat);
                        this._setAddress(resolved.address || address);
                    } else {
                        this._setAddress(address);
                    }
                }
            }

            const mapOptions = Object.assign({ zoom: zoom }, this.options.mapOptions || {});
            this.map = new window.AMap.Map(mapContainer, Object.assign({ center: [center.lng, center.lat] }, mapOptions));

            this.marker = this._createMarker(center);
            this.map.add(this.marker);

            if (centerValue) {
                this._setValue(center.lng, center.lat);
                this._reverseGeocode(center.lat, center.lng);
            }
        }

        _createMarker(center) {
            const options = {};
            const sizeOpt = this.options.markerSize || null;
            let iconUrl = this.options.markerIcon || '';
            if (iconUrl) {
                let width = 32;
                let height = 40;
                if (Array.isArray(sizeOpt) && sizeOpt.length >= 2) {
                    width = Number(sizeOpt[0]) || width;
                    height = Number(sizeOpt[1]) || height;
                } else if (sizeOpt && typeof sizeOpt === 'object') {
                    width = Number(sizeOpt.width || sizeOpt.w) || width;
                    height = Number(sizeOpt.height || sizeOpt.h) || height;
                }
                options.icon = new window.AMap.Icon({
                    size: new window.AMap.Size(width, height),
                    image: iconUrl,
                    imageSize: new window.AMap.Size(width, height)
                });
                const anchorOpt = this.options.markerAnchor || null;
                if (anchorOpt && typeof anchorOpt === 'object' && anchorOpt.x !== undefined && anchorOpt.y !== undefined) {
                    options.anchor = new window.AMap.Pixel(anchorOpt.x, anchorOpt.y);
                }
            }

            return new window.AMap.Marker(Object.assign({
                position: [center.lng, center.lat],
                draggable: !this.readonly
            }, options));
        }

        _bindEvents() {
            if (this.readonly) {
                return;
            }

            const $searchInput = this.$searchInput || this.$root.find('.dp-amap-search');
            const $clearBtn = this.$root.find('.dp-amap-clear');

            if (this.map) {
                this.map.on('click', (evt) => {
                    if (!evt || !evt.lnglat) {
                        return;
                    }
                    this._setPosition({ lng: evt.lnglat.lng, lat: evt.lnglat.lat });
                });
            }

            if (this.marker) {
                this.marker.on('dragend', (evt) => {
                    const lnglat = evt && evt.lnglat ? evt.lnglat : null;
                    if (lnglat) {
                        this._setPosition({ lng: lnglat.lng, lat: lnglat.lat });
                    }
                });
            }

            $searchInput.on('input', () => {
                if (this.searchTimer) {
                    clearTimeout(this.searchTimer);
                }
                this.searchTimer = setTimeout(() => {
                    const keyword = ($searchInput.val() || '').trim();
                    if (!keyword) {
                        this._renderSuggest([]);
                        return;
                    }
                    if (keyword.length < 2) {
                        this._renderSuggest([]);
                        return;
                    }
                    this._searchSuggest(keyword);
                }, 500);
            });

            $clearBtn.on('click', () => {
                $searchInput.val('');
                this._renderSuggest([]);
            });
        }

        _setPosition(point) {
            if (!point || typeof point.lng !== 'number' || typeof point.lat !== 'number') {
                return;
            }
            if (this.marker) {
                this.marker.setPosition([point.lng, point.lat]);
            }
            if (this.map) {
                this.map.setCenter([point.lng, point.lat]);
            }
            this._setValue(point.lng, point.lat);
            this._reverseGeocode(point.lat, point.lng);
        }

        _setValue(lng, lat) {
            const value = `${lng.toFixed(6)},${lat.toFixed(6)}`;
            this.$valueInput.val(value).trigger('change');
        }

        _setAddress(address) {
            const value = address || '';
            if (this.$addressInput.length) {
                this.$addressInput.val(value).trigger('change');
            }
            if (this.$searchInput && this.$searchInput.length) {
                this.$searchInput.val(value);
            }
        }

        async _reverseGeocode(lat, lng) {
            const roundedLat = Number(lat).toFixed(6);
            const roundedLng = Number(lng).toFixed(6);
            const key = `${roundedLng},${roundedLat}`;

            if (this.lastGeocodeKey === key && this.lastGeocodeAddress) {
                this._setAddress(this.lastGeocodeAddress);
                return;
            }

            if (this.geocodeTimer) {
                clearTimeout(this.geocodeTimer);
            }

            const geocoder = await this._ensureGeocoder();
            if (!geocoder) {
                console.warn('[AMap] Geocoder 不可用');
                return;
            }

            this.geocodeTimer = setTimeout(() => {
                const lnglat = window.AMap && typeof window.AMap.LngLat === 'function'
                    ? new window.AMap.LngLat(Number(roundedLng), Number(roundedLat))
                    : [Number(roundedLng), Number(roundedLat)];
                geocoder.getAddress(lnglat, async (status, result) => {
                    if (status === 'complete' && result && result.regeocode) {
                        const addressMode = this.options.address_mode || 'poi';
                        const fullAddress = result.regeocode.formattedAddress || '';
                        const poiName = (result.regeocode.pois && result.regeocode.pois.length)
                            ? result.regeocode.pois[0].name
                            : '';
                        let address = '';
                        if (addressMode === 'full') {
                            address = fullAddress || poiName;
                        } else if (addressMode === 'both') {
                            if (poiName && fullAddress) {
                                address = `${poiName}（${fullAddress}）`;
                            } else {
                                address = poiName || fullAddress || '';
                            }
                        } else {
                            address = poiName || fullAddress || '';
                        }
                        this.lastGeocodeKey = key;
                        this.lastGeocodeAddress = address;
                        this._setAddress(address);
                        return;
                    }

                    const fallbackAddress = await this._reverseGeocodeHttp(roundedLat, roundedLng);
                    if (fallbackAddress) {
                        this.lastGeocodeKey = key;
                        this.lastGeocodeAddress = fallbackAddress;
                        this._setAddress(fallbackAddress);
                    }
                });
            }, 500);
        }

        async _searchSuggest(keyword) {
            const autoComplete = this._getAutoComplete();
            if (!autoComplete) {
                return;
            }
            autoComplete.search(keyword, (status, result) => {
                if (status === 'complete' && result && Array.isArray(result.tips)) {
                    const items = result.tips.map((item) => ({
                        name: item.name || '',
                        address: item.address || '',
                        location: item.location ? { lng: item.location.lng, lat: item.location.lat } : null
                    }));
                    this._renderSuggest(items);
                } else {
                    this._renderSuggest([]);
                }
            });
        }

        async _reverseGeocodeHttp(lat, lng) {
            try {
                const params = Object.assign({}, this.options.regeoParams || {});
                params.location = params.location || `${lng},${lat}`;
                params.key = params.key || this.options.web_key || this.key;
                if (params.radius === undefined) {
                    params.radius = 1000;
                }
                if (params.extensions === undefined) {
                    params.extensions = 'all';
                }
                params.output = 'jsonp';
                const data = await this._jsonp('https://restapi.amap.com/v3/geocode/regeo', params);
                if (data && data.status === '1' && data.regeocode) {
                    const addressMode = this.options.address_mode || 'poi';
                    const fullAddress = data.regeocode.formatted_address || '';
                    const poiName = (data.regeocode.pois && data.regeocode.pois.length)
                        ? data.regeocode.pois[0].name
                        : '';
                    let address = '';
                    if (addressMode === 'full') {
                        address = fullAddress || poiName;
                    } else if (addressMode === 'both') {
                        if (poiName && fullAddress) {
                            address = `${poiName}（${fullAddress}）`;
                        } else {
                            address = poiName || fullAddress || '';
                        }
                    } else {
                        address = poiName || fullAddress || '';
                    }
                    return address;
                }
                if (data && data.status && data.status !== '1') {
                    console.warn('[AMap] 逆地址解析失败:', data.info || data.infocode || 'unknown');
                }
            } catch (error) {
                console.warn('[AMap] 逆地址解析失败', error);
            }
            return '';
        }

        _jsonp(url, params) {
            return new Promise((resolve, reject) => {
                const callbackName = `amap_jsonp_${Date.now()}_${Math.random().toString(36).slice(2)}`;
                const query = new URLSearchParams(params || {});
                query.set('callback', callbackName);

                const script = document.createElement('script');
                script.src = `${url}?${query.toString()}`;

                window[callbackName] = (data) => {
                    resolve(data);
                    delete window[callbackName];
                    script.remove();
                };

                script.onerror = () => {
                    reject(new Error('JSONP请求失败'));
                    delete window[callbackName];
                    script.remove();
                };

                document.head.appendChild(script);
            });
        }

        _renderSuggest(items) {
            if (!this.$suggest.length) {
                return;
            }
            if (!items || items.length === 0) {
                this.$suggest.hide().empty();
                return;
            }

            const html = items.map((item) => {
                const title = item.name || item.address || '';
                const address = item.address || '';
                const data = {
                    lat: item.location ? item.location.lat : '',
                    lng: item.location ? item.location.lng : '',
                    address: address || title
                };
                return `<button type="button" class="list-group-item list-group-item-action dp-amap-item" data-lat="${data.lat}" data-lng="${data.lng}" data-address="${data.address}">
${title}<div class="small text-muted">${address}</div></button>`;
            }).join('');

            this.$suggest.html(html).show();

            this.$suggest.find('.dp-amap-item').on('click', async (e) => {
                const $target = $(e.currentTarget);
                const lat = parseFloat($target.data('lat'));
                const lng = parseFloat($target.data('lng'));
                const address = $target.data('address') || '';
                if (!Number.isNaN(lat) && !Number.isNaN(lng)) {
                    this._setPosition({ lat, lng });
                    if (address) {
                        this._setAddress(address);
                    }
                } else if (address) {
                    const resolved = await this._resolveCenterFromAddress(address);
                    if (resolved) {
                        this._setPosition({ lat: resolved.lat, lng: resolved.lng });
                        this._setAddress(resolved.address || address);
                    }
                }
                this._renderSuggest([]);
            });
        }
    }


    // ===========================================
    // UEditor Component
    // ===========================================
    /**
     * UEditor 组件
     * 基于 UEditor Plus 的富文本编辑器组件，支持多驱动上传
     *
     * @class UEditorComponent
     * @extends {BaseComponent}
     */
    class UEditorComponent extends DpForm['BaseComponent'] {
        /**
         * 构造函数
         * @param {...any} args - 构造参数
         */
        constructor(...args) {
            super(...args);

            // 编辑器实例
            this.instance = null;
            // 上传目录配置
            this.uploadDir = '';
            // 上传地址
            this.uploadUrl = '';
            // 驱动适配器实例
            this.driverAdapter = null;
            // 清理函数队列
            this.cleanupQueue = [];
            // 正在进行的上传请求
            this.activeUploads = new Map();
        }

        /**
         * 组件初始化
         * @returns {Promise<void>}
         * @throws {Error} 当初始化失败时抛出异常
         */
        async onInit() {
            try {
                const $this = $(this.element).find('.dp-form-ueditor');
                const id = $this.attr('id');
                const $options = $this.data('options');
                const uploadDir = $this.data('dir') || '';
                const driverName = $this.data('driver') || 'local';
                const $textarea = $(this.element).find('textarea');

                // 验证必需元素
                if (!$this.length || !id) {
                    throw new Error('UEditor container or ID not found');
                }

                if (!window.UE) {
                    throw new Error('UEditor library not loaded');
                }

                // 解析配置选项
                let options = {};
                if ($options) {
                    try {
                        options = typeof $options === 'string' ? JSON.parse($options) : $options;
                    } catch (error) {
                        console.warn('Failed to parse UEditor options:', error);
                    }
                }

                // 保存上传目录配置
                this.uploadDir = uploadDir;
                this.uploadUrl = options.url || '';

                // 配置多驱动上传支持
                await this._setupMultiDriverUpload(driverName, uploadDir);

                // 合并默认配置，启用自定义上传服务
                const editorConfig = Object.assign({
                    initialFrameHeight: 400,
                    initialFrameWidth: '100%',
                    autoHeightEnabled: false,
                    serverUrl: '',
                    shortcutMenu: ["bold","italic","underline","strikethrough","fontborder","forecolor","backcolor","imagenone","imageleft","imagecenter","imageright","insertimage","formula"],
                    uploadServiceEnable: true,
                    uploadServiceUpload: this.handleUpload.bind(this)
                }, options);

                // 创建编辑器实例
                this.instance = UE.getEditor(id, editorConfig);

                // 等待编辑器准备就绪
                await new Promise((resolve, reject) => {
                    const timeout = setTimeout(() => {
                        reject(new Error('UEditor initialization timeout'));
                    }, 10000);

                    this.instance.ready(() => {
                        clearTimeout(timeout);
                        resolve();
                    });
                });

                // 设置初始值
                const initialValue = $textarea.val();
                if (initialValue) {
                    this.instance.setContent(initialValue);
                }

                // 监听内容变化
                this.instance.addListener('contentChange', () => {
                    const content = this.instance.getContent();
                    $textarea.val(content);
                    this.emit('change', content);
                });

                console.log('UEditor initialized successfully:', id);

            } catch (error) {
                console.error('UEditor initialization failed:', error);
                throw error;
            }
        }

        /**
         * 配置多驱动上传支持
         * @param {string} driverName - 驱动名称
         * @param {string} uploadDir - 上传目录
         * @private
         */
        async _setupMultiDriverUpload(driverName, uploadDir) {
            try {
                // 检查 Dolphin.uploader 是否可用
                if (!window.Dolphin || !Dolphin.uploader) {
                    console.warn('Dolphin.uploader not available, falling back to default upload');
                    return;
                }

                // 获取容器元素
                const containerElement = $(this.element).find('.dp-form-ueditor')[0];
                if (!containerElement) {
                    console.warn('UEditor container element not found');
                    return;
                }

                // 创建适配器实例
                this.driverAdapter = Dolphin.uploader.create({
                    element: containerElement,
                    options: {
                        extraData: {
                            dir: uploadDir
                        }
                    },
                    handleUploadError: (fileItem, error) => {
                        console.error('UEditor上传失败:', error);
                    }
                }, driverName, 'ueditor');

                if (this.driverAdapter) {
                    console.log(`UEditor multi-driver upload configured: ${driverName}`);
                } else {
                    Dolphin.error(`驱动 "${driverName}" 未找到, 将退回本地上传模式`);
                    console.warn(`Driver "${driverName}" not found, falling back to default upload`);
                }

            } catch (error) {
                Dolphin.error('[UEDITOR]多驱动上传初始化失败');
                console.error('Failed to setup multi-driver upload:', error);
                // 不抛出错误，允许降级到默认上传
            }
        }

        /**
         * 处理文件上传
         * @param {string} type - 上传类型 (image/video/audio/attachment)
         * @param {File|Blob} file - 文件对象（可能是包装对象）
         * @param {Object} callback - 回调函数对象
         * @param {Object} option - 上传配置
         */
        async handleUpload(type, file, callback, option) {
            try {
                // 提取真正的 File 或 Blob 对象
                let actualFile = this._extractFile(file);

                // 验证文件对象
                if (!actualFile) {
                    this._handleError('无效的文件对象', callback);
                    return;
                }

                // 如果是 Blob 对象（如涂鸦），转换为 File 对象
                if (actualFile instanceof Blob && !(actualFile instanceof File)) {
                    actualFile = this._blobToFile(actualFile, type);
                }

                // 验证文件类型和大小
                const validationError = this._validateFile(actualFile, type);
                if (validationError) {
                    this._handleError(validationError, callback);
                    return;
                }

                // 生成上传ID用于跟踪
                const uploadId = this._generateUploadId();

                // 如果配置了驱动适配器，使用驱动系统上传
                if (this.driverAdapter && this.driverAdapter.driver) {
                    await this._uploadWithDriver(actualFile, callback, type, uploadId);
                    return;
                }

                // 默认上传逻辑（降级方案）
                await this._uploadDefault(actualFile, callback, uploadId);

            } catch (error) {
                this._handleError(error.message || '上传失败', callback, error);
            }
        }

        /**
         * 将 Blob 对象转换为 File 对象
         * @param {Blob} blob - Blob 对象
         * @param {string} type - 上传类型
         * @returns {File} File 对象
         * @private
         */
        _blobToFile(blob, type) {
            // 根据上传类型生成文件名
            const timestamp = Date.now();
            let fileName = '';
            let fileType = blob.type || 'image/png';

            switch (type) {
                case 'scrawl':
                    fileName = `scrawl_${timestamp}.png`;
                    fileType = 'image/png';
                    break;
                case 'image':
                    // 根据 MIME 类型确定扩展名
                    const ext = fileType.split('/')[1] || 'png';
                    fileName = `image_${timestamp}.${ext}`;
                    break;
                case 'video':
                    const videoExt = fileType.split('/')[1] || 'mp4';
                    fileName = `video_${timestamp}.${videoExt}`;
                    break;
                case 'attachment':
                    fileName = `file_${timestamp}.bin`;
                    break;
                default:
                    fileName = `upload_${timestamp}.bin`;
            }

            // 创建 File 对象
            return new File([blob], fileName, { type: fileType });
        }

        /**
         * 提取真正的 File 或 Blob 对象
         * @param {*} file - 可能被包装的文件对象
         * @returns {File|Blob|null} 真正的 File/Blob 对象或 null
         * @private
         */
        _extractFile(file) {
            // 如果已经是 File 或 Blob 对象，直接返回
            if (file instanceof File || file instanceof Blob) {
                return file;
            }

            // 尝试从嵌套结构中提取
            let current = file;
            let depth = 0;
            const maxDepth = 10; // 防止无限循环

            while (current && depth < maxDepth) {
                // 检查是否为 File 或 Blob 对象
                if (current instanceof File || current instanceof Blob) {
                    return current;
                }

                // 尝试常见的嵌套路径
                if (current.file) {
                    current = current.file;
                } else if (current.source) {
                    current = current.source;
                } else {
                    break;
                }

                depth++;
            }

            // 最后验证提取出的对象（File 或 Blob 都有 size 属性）
            if (current && typeof current.size === 'number') {
                return current;
            }

            return null;
        }

        /**
         * 验证文件类型和大小
         * @param {File|Blob} file - 文件对象
         * @param {string} type - 上传类型
         * @returns {string|null} 错误信息或 null
         * @private
         */
        _validateFile(file, type) {
            // 文件大小限制（默认 10MB）
            const maxSize = 10 * 1024 * 1024;
            if (file.size > maxSize) {
                return `文件大小不能超过 ${this._formatFileSize(maxSize)}`;
            }

            // 根据类型验证文件格式
            const allowedTypes = {
                'image': ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/bmp', 'image/webp'],
                'scrawl': ['image/png', 'image/jpeg', 'image/jpg'],
                'video': ['video/mp4', 'video/webm', 'video/ogg', 'video/avi', 'video/mov'],
                'audio': ['audio/mp3', 'audio/mpeg', 'audio/wav', 'audio/ogg', 'audio/aac'],
                'attachment': ['*'] // 附件允许所有类型
            };

            const allowed = allowedTypes[type] || ['*'];

            // 如果允许所有类型，跳过验证
            if (allowed.includes('*')) {
                return null;
            }

            // 如果文件没有 type 属性或为空（某些 Blob 对象），根据上传类型判断
            if (!file.type) {
                // 涂鸦类型默认允许
                if (type === 'scrawl') {
                    return null;
                }
                console.warn('[UEditor] 文件缺少 MIME 类型，跳过类型验证');
                return null;
            }

            // 验证 MIME 类型
            if (!allowed.includes(file.type)) {
                const typeNames = {
                    'image': '图片',
                    'scrawl': '图片',
                    'video': '视频',
                    'audio': '音频'
                };
                return `只支持上传 ${typeNames[type] || '指定类型的'} 文件`;
            }

            return null;
        }

        /**
         * 格式化文件大小
         * @param {number} bytes - 字节数
         * @returns {string} 格式化后的大小
         * @private
         */
        _formatFileSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(2) + ' KB';
            return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
        }

        /**
         * 生成上传ID
         * @returns {string} 唯一的上传ID
         * @private
         */
        _generateUploadId() {
            return `upload_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
        }

        /**
         * 统一错误处理
         * @param {string} message - 错误消息
         * @param {Object} callback - UEditor 回调对象
         * @param {Error} [error] - 原始错误对象
         * @private
         */
        _handleError(message, callback, error = null) {
            // 记录详细错误
            if (error) {
                console.error('[UEditor] Upload error:', error);
            } else {
                console.error('[UEditor] Upload error:', message);
            }

            // 显示用户友好的错误信息
            if (window.Dolphin && typeof Dolphin.error === 'function') {
                Dolphin.error(message);
            }

            // 触发错误事件
            this.emit('uploadError', { message, error });

            // 调用 UEditor 回调
            if (callback && typeof callback.error === 'function') {
                callback.error(message);
            }
        }

        /**
         * 使用驱动系统上传文件
         * @param {File|Blob} file - 文件对象
         * @param {Object} callback - UEditor 回调对象
         * @param {string} type - 上传类型
         * @param {string} uploadId - 上传ID
         * @private
         */
        async _uploadWithDriver(file, callback, type, uploadId) {
            const driver = this.driverAdapter.driver;
            const fileItem = {
                uploadDir: this.uploadDir
            };

            // 用于取消上传的 AbortController
            const abortController = new AbortController();
            this.activeUploads.set(uploadId, abortController);

            try {
                // 1. 调用 before 钩子
                if (driver.before) {
                    const beforeResult = await driver.before(file, fileItem);
                    if (beforeResult === false) {
                        this._handleError('上传被取消', callback);
                        return;
                    }
                }

                // 2. 调用 prepare 钩子
                if (driver.prepare) {
                    const prepareResult = await driver.prepare(file, fileItem);
                    if (prepareResult === false) {
                        this._handleError('上传准备失败', callback);
                        return;
                    }
                    // 将 prepare 返回的数据合并到 fileItem
                    if (prepareResult && typeof prepareResult === 'object') {
                        Object.assign(fileItem, prepareResult);
                    }

                    // 如果存在prepareResult.skipUpload，则表示跳过上传
                    if (prepareResult && prepareResult.skipUpload) {
                        callback.success({
                            state: 'SUCCESS',
                            url: prepareResult.url || prepareResult.path || ''
                        });
                        return;
                    }
                }

                let result;

                // 3. 如果驱动有自定义 upload 方法，使用它（如七牛云直传）
                if (driver.upload) {
                    result = await driver.upload(file, fileItem, this.uploadUrl);
                    // 如果驱动返回 undefined，表示使用默认上传流程
                    if (result === undefined) {
                        result = await this._uploadToServer(file, fileItem, callback, abortController.signal);
                    }
                } else {
                    // 4. 否则使用默认上传逻辑
                    result = await this._uploadToServer(file, fileItem, callback, abortController.signal);
                }

                // 5. 调用 success 钩子
                if (driver.success) {
                    result = await driver.success(file, result, fileItem);
                }

                // 6. 返回成功结果给 UEditor
                callback.success({
                    state: 'SUCCESS',
                    url: result.url || result.path || ''
                });

            } catch (error) {
                // 如果是取消操作，不显示错误
                if (error.name === 'AbortError') {
                    console.log('[UEditor] Upload cancelled:', uploadId);
                    return;
                }

                this._handleError(error.message || '上传失败', callback, error);

                // 调用 error 钩子
                if (driver.error) {
                    driver.error(file, error, fileItem);
                }
            } finally {
                // 清理上传记录
                this.activeUploads.delete(uploadId);
            }
        }

        /**
         * 上传文件到服务器（支持进度回调）
         * @param {File} file - 文件对象
         * @param {Object} fileItem - 文件项对象
         * @param {Object} callback - UEditor 回调对象
         * @param {AbortSignal} [signal] - 取消信号
         * @returns {Promise<Object>} 上传结果
         * @private
         */
        async _uploadToServer(file, fileItem, callback, signal = null) {
            return new Promise((resolve, reject) => {
                const formData = new FormData();
                formData.append('file', file);
                formData.append('dir', fileItem.uploadDir || '');

                const xhr = new XMLHttpRequest();

                // 如果提供了取消信号，监听取消事件
                if (signal) {
                    signal.addEventListener('abort', () => {
                        xhr.abort();
                    });
                }

                // 监听上传进度
                xhr.upload.addEventListener('progress', (e) => {
                    if (e.lengthComputable && callback && typeof callback.progress === 'function') {
                        const progress = e.loaded / e.total;
                        callback.progress(progress);
                    }
                });

                // 监听上传完成
                xhr.addEventListener('load', () => {
                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.code === 1 && response.data && response.data.success && response.data.success.length > 0) {
                                const fileData = response.data.success[0];
                                resolve({
                                    url: fileData.url || fileData.path || '',
                                    name: fileData.name || file.name
                                });
                            } else {
                                const errorMsg = response.data?.error?.[0]?.msg || response.msg || '上传失败';
                                reject(new Error(errorMsg));
                            }
                        } catch (error) {
                            reject(new Error('解析响应失败'));
                        }
                    } else {
                        reject(new Error(`上传失败：HTTP ${xhr.status}`));
                    }
                });

                // 监听上传错误
                xhr.addEventListener('error', () => {
                    reject(new Error('网络错误'));
                });

                // 监听上传中止
                xhr.addEventListener('abort', () => {
                    const abortError = new Error('上传已取消');
                    abortError.name = 'AbortError';
                    reject(abortError);
                });

                // 发送请求
                xhr.open('POST', '/admin/api/upload.html?_ajax=1');
                xhr.send(formData);
            });
        }

        /**
         * 默认上传逻辑（不使用驱动）
         * @param {Blob} file - 文件对象
         * @param {Object} callback - UEditor 回调对象
         * @param {string} uploadId - 上传ID
         * @private
         */
        async _uploadDefault(file, callback, uploadId) {
            const formData = new FormData();
            formData.append('file', file);
            formData.append('dir', this.uploadDir || '');

            const xhr = new XMLHttpRequest();

            // 用于取消上传的 AbortController
            const abortController = new AbortController();
            this.activeUploads.set(uploadId, abortController);

            // 监听取消信号
            abortController.signal.addEventListener('abort', () => {
                xhr.abort();
            });

            // 监听上传进度
            xhr.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable && callback && typeof callback.progress === 'function') {
                    const progress = e.loaded / e.total;
                    callback.progress(progress);
                }
            });

            // 监听上传完成
            xhr.addEventListener('load', () => {
                this.activeUploads.delete(uploadId);

                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.code === 1 && response.data && response.data.success && response.data.success.length > 0) {
                            const fileData = response.data.success[0];
                            callback.success({
                                state: 'SUCCESS',
                                url: fileData.url || fileData.path || ''
                            });
                        } else {
                            const errorMsg = response.data?.error?.[0]?.msg || response.msg || '上传失败';
                            this._handleError(errorMsg, callback);
                        }
                    } catch (error) {
                        this._handleError('解析响应失败', callback, error);
                    }
                } else {
                    this._handleError(`上传失败：HTTP ${xhr.status}`, callback);
                }
            });

            // 监听上传错误
            xhr.addEventListener('error', () => {
                this.activeUploads.delete(uploadId);
                this._handleError('网络错误', callback);
            });

            // 监听上传中止
            xhr.addEventListener('abort', () => {
                this.activeUploads.delete(uploadId);
                console.log('[UEditor] Upload cancelled:', uploadId);
            });

            // 发送请求
            xhr.open('POST', '/admin/api/upload.html?_ajax=1');
            xhr.send(formData);
        }

        /**
         * 获取编辑器内容
         * @returns {string} 编辑器HTML内容
         */
        getValue() {
            if (!this.instance) {
                return '';
            }

            try {
                return this.instance.getContent() || '';
            } catch (error) {
                console.error('Failed to get UEditor content:', error);
                return '';
            }
        }

        /**
         * 设置编辑器内容
         * @param {string} value - 要设置的HTML内容
         * @param {boolean} append - 是否追加内容（默认false为替换）
         * @returns {UEditorComponent} this（支持链式调用）
         */
        setValue(value, append = false) {
            if (!this.instance) {
                return this;
            }

            try {
                this.instance.setContent(value || '', append);
                this.emit('change', value);
            } catch (error) {
                console.error('Failed to set UEditor content:', error);
            }

            return this;
        }

        /**
         * 销毁组件
         * @returns {Promise<void>}
         */
        async onDestroy() {
            try {
                // 取消所有正在进行的上传
                if (this.activeUploads && this.activeUploads.size > 0) {
                    console.log(`[UEditor] Cancelling ${this.activeUploads.size} active uploads`);
                    this.activeUploads.forEach((abortController, uploadId) => {
                        try {
                            abortController.abort();
                        } catch (error) {
                            console.warn(`取消上传失败 (${uploadId}):`, error);
                        }
                    });
                    this.activeUploads.clear();
                }

                // 执行清理队列
                if (this.cleanupQueue && this.cleanupQueue.length > 0) {
                    this.cleanupQueue.forEach(cleanup => {
                        try {
                            cleanup();
                        } catch (error) {
                            console.warn('清理函数执行失败:', error);
                        }
                    });
                }

                // 销毁驱动适配器
                if (this.driverAdapter && typeof this.driverAdapter.destroy === 'function') {
                    try {
                        this.driverAdapter.destroy();
                    } catch (error) {
                        console.warn('销毁驱动适配器失败:', error);
                    }
                    this.driverAdapter = null;
                }

                // 销毁编辑器实例
                if (this.instance) {
                    try {
                        this.instance.destroy();
                    } catch (error) {
                        console.warn('销毁UEditor实例失败:', error);
                    }
                    this.instance = null;
                }

                // 清理引用
                this.cleanupQueue = null;
                this.activeUploads = null;
                this.driverName = null;
                this.uploadDir = null;

            } catch (error) {
                console.warn('UEditorComponent销毁过程中发生错误:', error);
            }

            // 调用父类销毁方法
            if (super.onDestroy) {
                await super.onDestroy();
            }
        }
    }

    // 自动注册组件
    if (typeof DpForm !== 'undefined') {
        DpForm.setComponentMap({
            'textarea-max': TextareaMaxComponent,
            'select2': Select2Component,
            'password': PasswordComponent,
            'tags': TagsComponent,
            'icon': IconPickerComponent,
            'linkage': LinkageComponent,
            'linkages': LinkagesComponent,
            'color-picker': ColorPickerComponent,
            'datetime-picker': DatetimePickerComponent,
            'image-upload': ImageUploadComponent,
            'file-upload': FileUploadComponent,
            'vditor': VditorComponent,
            'ueditor': UEditorComponent,
            'qmap': QMapComponent,
            'bmap': BMapComponent,
            'amap': AMapComponent,
            'cropper': CropperComponent,
        });
    }
})();
