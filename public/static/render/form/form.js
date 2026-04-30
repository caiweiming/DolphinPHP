/**
 * DolphinForm - 现代化表单渲染器 v2.0
 * 
 * 特性：
 * - 事件驱动架构
 * - 插件系统（支持卸载）
 * - 统一的组件生命周期
 * - 配置继承和覆盖
 * - 内存管理和清理
 * - 链式调用支持
 * - 并发安全
 * - 错误处理完善
 * - 性能优化
 */

// 依赖检查
if (typeof window.$ === 'undefined' && typeof window.jQuery === 'undefined') {
    throw new Error('DolphinForm requires jQuery library');
}

// 安全的jQuery引用
const $ = window.$ || window.jQuery;

// 检查浏览器兼容性
const SUPPORTS_WEAK_REF = typeof WeakRef !== 'undefined';

/**
 * @typedef {Object} CacheObject
 * @property {function(string): *} get - 获取缓存值
 * @property {function(string, *): void} set - 设置缓存值
 * @property {function(string): boolean} has - 检查键是否存在
 * @property {function(string): boolean} delete - 删除缓存项
 * @property {function(): void} clear - 清空缓存
 * @property {function(): number} size - 获取缓存大小
 */

/**
 * @typedef {Object} WeakRefObject
 * @property {function(): Object|null} deref - 获取弱引用的对象
 * @property {boolean} [_isWeakRef] - 是否为真正的WeakRef（降级方案标记）
 */

/**
 * 工具函数集合
 * @namespace Utils
 */
const Utils = {
    /**
     * 生成唯一ID
     * @param {string} [prefix='dp'] - ID前缀
     * @returns {string} 唯一ID
     */
    generateId(prefix = 'dp') {
        return `${prefix}-${Math.random().toString(36).substring(2, 11)}`;
    },

    /**
     * 检查对象是否可序列化
     * @param {*} obj - 要检查的对象
     * @returns {boolean} 是否可序列化
     */
    isSerializable(obj) {
        if (obj === null || typeof obj !== 'object') return true;
        if (obj instanceof Date || obj instanceof RegExp || obj instanceof Function) return false;
        if (obj instanceof Element || (window.jQuery && obj instanceof window.jQuery)) return false;
        
        try {
            JSON.stringify(obj);
            return true;
        } catch {
            return false;
        }
    },

    /**
     * 参数验证（增强版）
     * @param {*} value - 要验证的值
     * @param {string} name - 参数名称
     * @param {string|string[]} type - 期望的类型
     * @param {boolean} [required=true] - 是否必需
     * @returns {boolean} 验证是否通过
     * @throws {TypeError} 参数类型不匹配时抛出
     */
    validateParam(value, name, type, required = true) {
        if (required && (value === undefined || value === null)) {
            throw new TypeError(`Parameter '${name}' is required`);
        }
        
        if (value === undefined || value === null) {
            return true; // 非必需参数，允许为空
        }
        
        // 支持复合类型验证
        if (Array.isArray(type)) {
            // 支持多种类型：validateParam(value, 'param', ['string', 'number'])
            const actualType = typeof value;
            if (!type.includes(actualType)) {
                throw new TypeError(`Parameter '${name}' must be one of types [${type.join(', ')}], got '${actualType}'`);
            }
        } else if (type === 'array') {
            // 特殊处理数组类型
            if (!Array.isArray(value)) {
                throw new TypeError(`Parameter '${name}' must be an array, got '${typeof value}'`);
            }
        } else if (typeof value !== type) {
            throw new TypeError(`Parameter '${name}' must be of type '${type}', got '${typeof value}'`);
        }
        
        return true;
    },

    /**
     * 防抖函数
     * @param {Function} func - 要防抖的函数
     * @param {number} wait - 等待时间（毫秒）
     * @returns {Function} 防抖后的函数
     */
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    /**
     * 深度克隆（优化版，支持循环引用检测）
     * @param {*} obj - 要克隆的对象
     * @param {WeakMap} [visited] - 用于检测循环引用的WeakMap
     * @returns {*} 克隆后的对象
     */
    deepClone(obj, visited = new WeakMap()) {
        if (obj === null || typeof obj !== 'object') return obj;
        
        // 检查循环引用
        if (visited.has(obj)) {
            return visited.get(obj);
        }
        
        if (obj instanceof Date) return new Date(obj);
        if (obj instanceof RegExp) return new RegExp(obj);
        
        if (obj instanceof Array) {
            const clonedArray = [];
            visited.set(obj, clonedArray);
            obj.forEach((item, index) => {
                clonedArray[index] = Utils.deepClone(item, visited);
            });
            return clonedArray;
        }
        
        // 对于普通对象，先尝试快速序列化
        if (Utils.isSerializable(obj)) {
            try {
                return JSON.parse(JSON.stringify(obj));
            } catch {
                // 降级到递归克隆
            }
        }
        
        const cloned = {};
        visited.set(obj, cloned);
        
        for (const key in obj) {
            if (obj.hasOwnProperty(key)) {
                cloned[key] = Utils.deepClone(obj[key], visited);
            }
        }
        return cloned;
    },


    /**
     * 创建简单的LRU缓存
     * @param {number} [maxSize=100] - 最大缓存大小
     * @returns {CacheObject} 缓存对象
     */
    createCache(maxSize = 100) {
        const cache = new Map();
        
        return {
            get(key) {
                const item = cache.get(key);
                if (item) {
                    // 更新访问时间
                    cache.delete(key);
                    cache.set(key, item);
                    return item.value;
                }
                return undefined;
            },
            
            set(key, value) {
                if (cache.has(key)) {
                    cache.delete(key);
                } else if (cache.size >= maxSize) {
                    // 删除最旧的项
                    const firstKey = cache.keys().next().value;
                    cache.delete(firstKey);
                }
                cache.set(key, { value, timestamp: Date.now() });
            },
            
            has(key) {
                return cache.has(key);
            },
            
            delete(key) {
                return cache.delete(key);
            },
            
            clear() {
                cache.clear();
            },
            
            size() {
                return cache.size;
            }
        };
    },

    /**
     * 安全的WeakRef包装
     * @param {Object} obj - 要包装的对象
     * @returns {WeakRef<Object>|WeakRefObject} WeakRef对象或降级方案
     */
    createWeakRef(obj) {
        if (SUPPORTS_WEAK_REF && obj) {
            return new WeakRef(obj);
        }
        // 降级方案：使用普通引用但标记为弱引用
        return {
            deref: () => obj,
            _isWeakRef: false
        };
    },




    /**
     * 检查是否为空值
     * @param {*} value - 要检查的值
     * @returns {boolean} 是否为空
     */
    isEmpty(value) {
        if (value == null) return true;
        if (typeof value === 'string') return value.trim() === '';
        if (Array.isArray(value)) return value.length === 0;
        if (typeof value === 'object') return Object.keys(value).length === 0;
        return false;
    },

};

/**
 * @typedef {Object} EmitResult
 * @property {boolean} success - 是否成功执行所有监听器
 * @property {Array<{error: Error, event: string, index: number}>} errors - 错误列表
 */

/**
 * 事件管理器
 * @class EventManager
 */
class EventManager {
    constructor() {
        /** @type {Map<string, Array<{callback: Function, once: boolean}>>} */
        this.events = new Map();
        /** @type {boolean} */
        this._destroyed = false;
    }

    /**
     * @private
     * @throws {Error} 当管理器已销毁时抛出
     */
    _checkDestroyed() {
        if (this._destroyed) {
            throw new Error('EventManager has been destroyed');
        }
    }

    /**
     * 注册事件监听器
     * @param {string} event - 事件名称
     * @param {Function} callback - 回调函数
     * @param {boolean} [once=false] - 是否只执行一次
     * @returns {Function} 移除监听器的函数
     * @throws {TypeError} 当参数类型不正确时抛出
     */
    on(event, callback, once = false) {
        this._checkDestroyed();
        Utils.validateParam(event, 'event', 'string');
        Utils.validateParam(callback, 'callback', 'function');

        if (!this.events.has(event)) {
            this.events.set(event, []);
        }

        const listeners = this.events.get(event);
        const listenerObj = {
            callback,
            once
        };

        listeners.push(listenerObj);

        // 返回移除函数
        return () => this.off(event, callback);
    }

    /**
     * 注册一次性事件监听器
     * @param {string} event - 事件名称
     * @param {Function} callback - 回调函数
     * @returns {Function} 移除监听器的函数
     */
    once(event, callback) {
        return this.on(event, callback, true);
    }

    /**
     * 移除事件监听器
     * @param {string} event - 事件名称
     * @param {Function} [callback] - 回调函数（不传则移除该事件的所有监听器）
     * @returns {EventManager} this（支持链式调用）
     */
    off(event, callback) {
        this._checkDestroyed();

        if (!this.events.has(event)) return this;

        const listeners = this.events.get(event);
        if (callback) {
            const index = listeners.findIndex(listener => listener.callback === callback);
            if (index !== -1) {
                listeners.splice(index, 1);
            }

            // 如果没有监听器了，删除事件
            if (listeners.length === 0) {
                this.events.delete(event);
            }
        } else {
            this.events.delete(event);
        }

        return this;
    }

    /**
     * 触发事件
     * @param {string} event - 事件名称
     * @param {...*} args - 传递给监听器的参数
     * @returns {EmitResult} 执行结果
     */
    emit(event, ...args) {
        this._checkDestroyed();

        if (!this.events.has(event)) return { success: true, errors: [] };

        const listeners = this.events.get(event);
        const toRemove = [];
        const errors = [];

        listeners.forEach((listener, index) => {
            try {
                listener.callback(...args);
                if (listener.once) {
                    toRemove.push(index);
                }
            } catch (error) {
                errors.push({
                    error,
                    event,
                    index
                });
                console.error(`Error in event listener for '${event}':`, error);
            }
        });

        // 移除一次性监听器（逆序移除）
        toRemove.reverse().forEach(index => listeners.splice(index, 1));

        return { success: errors.length === 0, errors };
    }

    /**
     * 清空所有事件监听器
     * @returns {EventManager} this（支持链式调用）
     */
    clear() {
        this.events.clear();
        return this;
    }

    /**
     * 销毁事件管理器
     * @returns {void}
     */
    destroy() {
        this.clear();
        this._destroyed = true;
    }

    /**
     * 获取指定事件的监听器数量
     * @param {string} event - 事件名称
     * @returns {number} 监听器数量
     */
    getListenerCount(event) {
        if (!this.events.has(event)) return 0;
        return this.events.get(event).length;
    }
}

/**
 * 配置管理器
 * @class ConfigManager
 */
class ConfigManager {
    /**
     * 构造函数
     * @param {Object} [defaults={}] - 默认配置对象
     */
    constructor(defaults = {}) {
        /** @type {Object} */
        this.defaults = Utils.deepClone(defaults);
        /** @type {Object} */
        this.config = Utils.deepClone(defaults);
    }

    /**
     * 设置配置项
     * @param {string} key - 配置键名
     * @param {*} value - 配置值
     * @returns {ConfigManager} this（支持链式调用）
     * @throws {TypeError} 当key不是字符串时抛出
     */
    set(key, value) {
        Utils.validateParam(key, 'key', 'string');
        this.config[key] = value;
        return this;
    }

    /**
     * 获取配置项
     * @param {string} [key] - 配置键名（不传则返回所有配置）
     * @param {*} [defaultValue=undefined] - 默认值
     * @returns {*} 配置值或所有配置对象
     * @throws {TypeError} 当key不是字符串时抛出
     */
    get(key, defaultValue = undefined) {
        if (!key) return { ...this.config };

        Utils.validateParam(key, 'key', 'string');
        const value = this.config[key];
        return value !== undefined ? value : defaultValue;
    }

    /**
     * 合并配置
     * @param {Object} config - 要合并的配置对象
     * @returns {ConfigManager} this（支持链式调用）
     * @throws {TypeError} 当config不是对象时抛出
     */
    merge(config) {
        Utils.validateParam(config, 'config', 'object');
        Object.assign(this.config, config);
        return this;
    }

    /**
     * 重置为默认配置
     * @returns {ConfigManager} this（支持链式调用）
     */
    reset() {
        this.config = Utils.deepClone(this.defaults);
        return this;
    }

    /**
     * 销毁配置管理器
     * @returns {void}
     */
    destroy() {
        this.config = null;
        this.defaults = null;
    }
}

/**
 * 基础组件类（优化版）
 * @class BaseComponent
 */
class BaseComponent {
    /**
     * 构造函数
     * @param {HTMLElement|jQuery|string} element - DOM元素、jQuery对象或选择器
     * @param {Object} [options={}] - 组件选项
     * @param {FormManager} [form] - 表单管理器实例
     * @throws {Error} 当element为空或无效时抛出
     */
    constructor(element, options = {}, form) {
        // 参数验证
        if (!element) {
            throw new Error('Element is required for component');
        }

        /** @type {jQuery} */
        this.element = ($ && element instanceof $) ? element : $(element);
        if (this.element.length === 0) {
            throw new Error('Element not found or invalid');
        }

        // 安全的WeakRef处理
        /** @type {WeakRef<FormManager>|WeakRefObject|null} */
        this._formRef = form ? Utils.createWeakRef(form) : null;
        /** @type {string} */
        this.id = this.element.attr('id') || Utils.generateId('dp-component');
        /** @type {EventManager} */
        this.events = new EventManager();
        /** @type {ConfigManager} */
        this.config = new ConfigManager(this.getDefaultConfig());
        this.config.merge(options);
        /** @type {string} */
        this.state = 'uninitialized';
        /** @type {*} */
        this.instance = null;
        /** @type {boolean} */
        this._destroyed = false;
        /** @type {boolean} */
        this._initLock = false;
        /** @type {string} */
        this._domEventNamespace = `.dp-component-${this.id}`;

        // 确保元素有ID
        if (!this.element.attr('id')) {
            this.element.attr('id', this.id);
        }

        // 标记元素
        this.element.data('dp-component', this);
    }

    /**
     * 获取表单管理器实例
     * @returns {FormManager|null}
     */
    get form() {
        return this._formRef?.deref() || null;
    }

    /**
     * 获取默认配置（子类可覆盖）
     * @returns {Object} 默认配置对象
     */
    getDefaultConfig() {
        return {};
    }

    /**
     * 初始化组件
     * @returns {Promise<BaseComponent>} this（支持链式调用）
     * @throws {Error} 当组件已销毁、正在初始化或已初始化时抛出
     */
    async init() {
        if (this._destroyed) {
            throw new Error(`Component ${this.id} has been destroyed`);
        }

        if (this._initLock) {
            throw new Error(`Component ${this.id} is already being initialized`);
        }

        if (this.state !== 'uninitialized') {
            throw new Error(`Component ${this.id} is already initialized (state: ${this.state})`);
        }

        this._initLock = true;
        this.state = 'initializing';

        try {
            this.emit('beforeInit');
            await this.onInit();
            this.state = 'initialized';
            this.emit('init');
            return this;
        } catch (error) {
            this.state = 'error';
            this.emit('error', error);
            throw error;
        } finally {
            this._initLock = false;
        }
    }

    /**
     * 组件初始化钩子（子类实现）
     * @returns {Promise<void>}
     */
    async onInit() {
        // 子类实现具体的初始化逻辑
    }

    /**
     * 销毁组件
     * @returns {Promise<BaseComponent>} this（支持链式调用）
     * @throws {Error} 当销毁过程中发生错误时抛出
     */
    async destroy() {
        if (this._destroyed) return this;

        this._destroyed = true;
        this.state = 'destroying';

        try {
            this.emit('beforeDestroy');
            await this.onDestroy();

            // 清理事件监听器
            this.events.destroy();

            // 清理DOM事件（使用命名空间）
            if (this.element && this.element.off) {
                this.element.off(this._domEventNamespace);
            }

            // 清理数据
            if (this.element && this.element.removeData) {
                this.element.removeData('dp-component');
            }

            // 破除引用
            this._formRef = null;
            this.element = null;
            this.config.destroy();
            this.config = null;
            this.instance = null;

            this.state = 'destroyed';

            return this;
        } catch (error) {
            console.error(`Error destroying component ${this.id}:`, error);
            throw error;
        }
    }

    /**
     * 组件销毁钩子（子类实现）
     * @returns {Promise<void>}
     */
    async onDestroy() {
        // 子类实现具体的销毁逻辑
    }

    /**
     * 注册事件监听器
     * @param {string} event - 事件名称
     * @param {Function} callback - 回调函数
     * @param {boolean} [once=false] - 是否只执行一次
     * @returns {Function} 移除监听器的函数
     * @throws {Error} 当组件已销毁时抛出
     */
    on(event, callback, once = false) {
        this._checkDestroyed();
        return this.events.on(event, callback, once);
    }

    /**
     * 注册一次性事件监听器
     * @param {string} event - 事件名称
     * @param {Function} callback - 回调函数
     * @returns {Function} 移除监听器的函数
     */
    once(event, callback) {
        return this.on(event, callback, true);
    }

    /**
     * 移除事件监听器
     * @param {string} event - 事件名称
     * @param {Function} [callback] - 回调函数（不传则移除该事件的所有监听器）
     * @returns {BaseComponent} this（支持链式调用）
     * @throws {Error} 当组件已销毁时抛出
     */
    off(event, callback) {
        this._checkDestroyed();
        this.events.off(event, callback);
        return this;
    }

    /**
     * 触发事件
     * @param {string} event - 事件名称
     * @param {...*} args - 传递给监听器的参数
     * @returns {EmitResult} 执行结果
     */
    emit(event, ...args) {
        if (this._destroyed) return { success: false, errors: [] };

        const result = this.events.emit(event, ...args);

        // 同时向表单实例发送事件（如果表单还存在）
        const form = this.form;
        if (form && !form._destroyed) {
            form.emit(`component.${event}`, this, ...args);
        }

        return result;
    }

    /**
     * 获取组件实例
     * @returns {*} 组件实例（如Select2、Vditor等）
     */
    getInstance() {
        return this.instance;
    }

    /**
     * 设置组件值（子类必须实现）
     * @abstract
     * @param {*} value - 要设置的值
     * @returns {BaseComponent} this（支持链式调用）
     * @throws {Error} 子类未实现时抛出
     */
    setValue(value) {
        this._checkDestroyed();
        throw new Error('setValue method must be implemented by subclass');
    }

    /**
     * 获取组件值（子类必须实现）
     * @abstract
     * @returns {*} 组件值
     * @throws {Error} 子类未实现时抛出
     */
    getValue() {
        this._checkDestroyed();
        throw new Error('getValue method must be implemented by subclass');
    }

    /**
     * 验证组件（子类可覆盖）
     * @returns {{valid: boolean, errors: string[]}} 验证结果
     * @throws {Error} 当组件已销毁时抛出
     */
    validate() {
        this._checkDestroyed();
        // 默认验证通过，子类可以重写
        return { valid: true, errors: [] };
    }

    /**
     * 获取缓存的jQuery元素
     * @param {string} selector - jQuery选择器
     * @returns {jQuery} jQuery元素对象
     */
    getCachedElement(selector) {
        const cacheKey = `_cached_${selector}`;
        if (!this[cacheKey]) {
            this[cacheKey] = this.element.find(selector);
        }
        return this[cacheKey];
    }

    /**
     * 统一错误处理
     * @param {string} context - 错误上下文
     * @param {Error} error - 错误对象
     */
    handleError(context, error) {
        const userMessage = this.getUserErrorMessage(context, error);
        console.error(`[${this.id}] ${context}:`, error);

        if (window.Dolphin && typeof window.Dolphin.error === 'function') {
            window.Dolphin.error(userMessage);
        }

        this.emit('error', { context, error, userMessage });
    }

    /**
     * 获取用户友好的错误消息
     * @param {string} context - 错误上下文
     * @param {Error} error - 错误对象
     * @returns {string} 用户友好的错误消息
     */
    getUserErrorMessage(context, error) {
        const messages = {
            'init': '初始化失败',
            'destroy': '销毁失败',
            'setValue': '设置值失败',
            'getValue': '获取值失败',
            'upload': '上传失败',
            'download': '下载失败',
            'delete': '删除失败'
        };

        return messages[context] || '操作失败';
    }

    /**
     * 检查组件是否已销毁
     * @private
     * @throws {Error} 当组件已销毁时抛出
     */
    _checkDestroyed() {
        if (this._destroyed) {
            throw new Error(`Component ${this.id} has been destroyed`);
        }
    }
}

/**
 * 组件注册表（优化版）
 * @class ComponentRegistry
 */
class ComponentRegistry {
    /**
     * 构造函数
     */
    constructor() {
        /** @type {Map<string, Function>} */
        this.components = new Map();
        /** @type {Map<string, Object>} */
        this.plugins = new Map();
        /** @type {boolean} */
        this._destroyed = false;
    }

    /**
     * 注册组件类
     * @param {string} name - 组件名称
     * @param {Function} componentClass - 组件类（构造函数）
     * @returns {ComponentRegistry} this（支持链式调用）
     * @throws {TypeError} 当name不是字符串或componentClass不是函数时抛出
     * @throws {Error} 当注册表已销毁时抛出
     */
    register(name, componentClass) {
        this._checkDestroyed();
        Utils.validateParam(name, 'name', 'string');

        if (typeof componentClass !== 'function') {
            throw new TypeError('Component must be a constructor function or class');
        }

        // 检查是否继承自 BaseComponent
        if (componentClass.prototype && !(componentClass.prototype instanceof BaseComponent) && componentClass !== BaseComponent) {
            console.warn(`Component '${name}' does not extend BaseComponent`);
        }

        this.components.set(name, componentClass);
        return this;
    }

    /**
     * 获取组件类
     * @param {string} name - 组件名称
     * @returns {Function|undefined} 组件类
     * @throws {TypeError} 当name不是字符串时抛出
     * @throws {Error} 当注册表已销毁时抛出
     */
    get(name) {
        this._checkDestroyed();
        Utils.validateParam(name, 'name', 'string');
        return this.components.get(name);
    }

    /**
     * 检查组件是否已注册
     * @param {string} name - 组件名称
     * @returns {boolean} 是否已注册
     * @throws {TypeError} 当name不是字符串时抛出
     * @throws {Error} 当注册表已销毁时抛出
     */
    has(name) {
        this._checkDestroyed();
        Utils.validateParam(name, 'name', 'string');
        return this.components.has(name);
    }

    /**
     * 取消注册组件
     * @param {string} name - 组件名称
     * @returns {boolean} 是否成功删除
     * @throws {TypeError} 当name不是字符串时抛出
     * @throws {Error} 当注册表已销毁时抛出
     */
    unregister(name) {
        this._checkDestroyed();
        Utils.validateParam(name, 'name', 'string');
        return this.components.delete(name);
    }

    /**
     * 注册插件
     * @param {string} name - 插件名称
     * @param {Object} plugin - 插件对象
     * @returns {ComponentRegistry} this（支持链式调用）
     * @throws {TypeError} 当参数类型不正确时抛出
     * @throws {Error} 当注册表已销毁时抛出
     */
    registerPlugin(name, plugin) {
        this._checkDestroyed();
        Utils.validateParam(name, 'name', 'string');
        Utils.validateParam(plugin, 'plugin', 'object');

        this.plugins.set(name, plugin);
        return this;
    }

    /**
     * 获取插件
     * @param {string} name - 插件名称
     * @returns {Object|undefined} 插件对象
     * @throws {Error} 当注册表已销毁时抛出
     */
    getPlugin(name) {
        this._checkDestroyed();
        return this.plugins.get(name);
    }

    /**
     * 取消注册插件
     * @param {string} name - 插件名称
     * @returns {boolean} 是否成功删除
     * @throws {Error} 当注册表已销毁时抛出
     */
    unregisterPlugin(name) {
        this._checkDestroyed();
        return this.plugins.delete(name);
    }

    /**
     * 获取所有已注册的组件名称
     * @returns {string[]} 组件名称数组
     * @throws {Error} 当注册表已销毁时抛出
     */
    getComponentNames() {
        this._checkDestroyed();
        return Array.from(this.components.keys());
    }

    /**
     * 获取所有已注册的插件名称
     * @returns {string[]} 插件名称数组
     * @throws {Error} 当注册表已销毁时抛出
     */
    getPluginNames() {
        this._checkDestroyed();
        return Array.from(this.plugins.keys());
    }

    /**
     * 清空所有注册的组件和插件
     * @returns {ComponentRegistry} this（支持链式调用）
     */
    clear() {
        this.components.clear();
        this.plugins.clear();
        return this;
    }

    /**
     * 销毁注册表
     * @returns {void}
     */
    destroy() {
        this.clear();
        this._destroyed = true;
    }

    /**
     * 检查注册表是否已销毁
     * @private
     * @throws {Error} 当注册表已销毁时抛出
     */
    _checkDestroyed() {
        if (this._destroyed) {
            throw new Error('ComponentRegistry has been destroyed');
        }
    }
}

/**
 * 主表单管理器（优化版）
 * @class FormManager
 */
class FormManager {
    /**
     * 构造函数
     * @param {Object} [options={}] - 配置选项
     */
    constructor(options = {}) {
        /** @type {EventManager} */
        this.events = new EventManager();
        /** @type {ConfigManager} */
        this.config = new ConfigManager(this.getDefaultConfig());
        this.config.merge(options);
        /** @type {Map<string, BaseComponent>} */
        this.components = new Map();
        /** @type {ComponentRegistry} */
        this.registry = new ComponentRegistry();
        /** @type {Map<string, Function>} 存储插件卸载函数 */
        this.plugins = new Map();
        /** @type {string} */
        this.state = 'uninitialized';
        /** @type {boolean} */
        this._destroyed = false;
        /** @type {boolean} */
        this._initLock = false;

        // 注册默认组件类型
        this.registerDefaultComponents();
    }

    /**
     * 获取默认配置
     * @returns {Object} 默认配置对象
     */
    getDefaultConfig() {
        return {
            autoInit: true,
            autoScan: true,
            selector: '.dp-form',
            componentSelector: '[data-component]',
            errorHandler: 'console', // 'console', 'notify', 'throw', function
            debug: false,
            maxComponents: 1000, // 防止内存泄漏
            componentTimeout: 30000, // 组件初始化超时时间
            gracefulErrorHandling: true // 优雅错误处理
        };
    }

    /**
     * 注册默认组件类型（可由子类覆盖）
     * @returns {void}
     */
    registerDefaultComponents() {
        // 这里会注册所有内置组件
        // 实际的组件实现会在后续的插件中提供
    }

    /**
     * 设置组件映射
     * @param {Object} componentMap - 组件映射对象（key为组件名，value为组件类）
     * @returns {FormManager} this（支持链式调用）
     * @throws {TypeError} 当componentMap不是对象时抛出
     * @throws {Error} 当管理器已销毁时抛出
     */
    setComponentMap(componentMap) {
        this._checkDestroyed();
        Utils.validateParam(componentMap, 'componentMap', 'object');
        this._componentMap = componentMap;
        return this;
    }

    /**
     * 获取组件映射
     * @returns {Object} 组件映射对象（优先使用实例映射，其次使用全局映射）
     */
    getComponentMap() {
        // 优先使用实例的组件映射，然后是全局组件映射
        return this._componentMap || window.DpFormComponentMap || {};
    }

    /**
     * 添加组件到映射
     * @param {string} name - 组件名称
     * @param {Function} componentClass - 组件类
     * @returns {FormManager} this（支持链式调用）
     * @throws {TypeError} 当name不是字符串时抛出
     * @throws {Error} 当管理器已销毁时抛出
     */
    addToComponentMap(name, componentClass) {
        this._checkDestroyed();
        Utils.validateParam(name, 'name', 'string');

        if (!this._componentMap) {
            this._componentMap = {};
        }

        this._componentMap[name] = componentClass;
        return this;
    }

    /**
     * 从映射中移除组件
     * @param {string} name - 组件名称
     * @returns {FormManager} this（支持链式调用）
     * @throws {Error} 当管理器已销毁时抛出
     */
    removeFromComponentMap(name) {
        this._checkDestroyed();

        if (this._componentMap && this._componentMap[name]) {
            delete this._componentMap[name];
        }

        return this;
    }

    /**
     * 注册组件类（支持三种方式）
     * @param {string|string[]|Object} name - 组件名称、名称数组或组件映射对象
     * @param {Function} [componentClass] - 组件类（仅在name为字符串时使用）
     * @returns {FormManager} this（支持链式调用）
     * @throws {TypeError} 当name类型不正确或为null/undefined时抛出
     * @throws {Error} 当管理器已销毁时抛出
     * @example
     * // 单个注册（自动从组件映射查找）
     * form.register('select2');
     *
     * // 单个注册（显式指定类）
     * form.register('myComponent', MyComponentClass);
     *
     * // 数组批量注册
     * form.register(['select2', 'vditor', 'cropper']);
     *
     * // 对象批量注册
     * form.register({
     *   select2: Select2Component,
     *   vditor: VditorComponent
     * });
     */
    register(name, componentClass) {
        this._checkDestroyed();

        // 参数验证
        if (name === null || name === undefined) {
            throw new TypeError('Parameter "name" cannot be null or undefined');
        }

        // 支持多种注册方式
        if (Array.isArray(name)) {
            return this._registerArray(name);
        }

        if (typeof name === 'object' && name !== null && !Array.isArray(name)) {
            // 对象批量注册：register({textareaMax: TextareaMaxComponent, select2: Select2Component})
            return this._registerObject(name);
        }

        if (typeof name !== 'string') {
            throw new TypeError(`Parameter "name" must be string, array, or object, got '${typeof name}'`);
        }

        // 单个组件注册
        return this._registerSingle(name, componentClass);
    }

    /**
     * 单个组件注册（私有方法）
     * @private
     * @param {string} name - 组件名称
     * @param {Function} [componentClass] - 组件类（不传则从组件映射中查找）
     * @returns {FormManager} this（支持链式调用）
     * @throws {TypeError} 当name不是字符串时抛出
     */
    _registerSingle(name, componentClass) {
        Utils.validateParam(name, 'name', 'string');

        // 如果没有传入组件类，尝试从组件映射中查找
        if (componentClass === undefined) {
            const componentMap = this.getComponentMap();
            componentClass = componentMap[name];

            if (!componentClass) {
                console.warn(`Component '${name}' not found in component map. Available components: ${Object.keys(componentMap).join(', ')}`);
                return this;
            }

            if (this.config.get('debug')) {
                console.log(`Auto-resolved component '${name}' from component map`);
            }
        }

        this.registry.register(name, componentClass);

        if (this.config.get('debug')) {
            console.log(`Registered component: ${name}`);
        }

        return this;
    }

    /**
     * 数组批量注册（私有方法）
     * @private
     * @param {string[]} names - 组件名称数组
     * @returns {FormManager} this（支持链式调用）
     * @throws {TypeError} 当names不是数组时抛出
     */
    _registerArray(names) {
        Utils.validateParam(names, 'names', 'array');

        if (names.length === 0) {
            console.warn('Empty array provided for batch registration');
            return this;
        }

        const results = {
            successful: [],
            failed: []
        };

        names.forEach((name, index) => {
            try {
                if (name === null || name === undefined) {
                    throw new TypeError(`Array element at index ${index} is null or undefined`);
                }

                if (typeof name !== 'string') {
                    throw new TypeError(`Array element at index ${index} must be string, got '${typeof name}'`);
                }

                if (name.trim() === '') {
                    throw new TypeError(`Array element at index ${index} is empty string`);
                }

                this._registerSingle(name.trim());
                results.successful.push(name);
            } catch (error) {
                results.failed.push({ name, index, error: error.message });
                console.error(`Failed to register component '${name}' at index ${index}:`, error);
            }
        });

        if (this.config.get('debug')) {
            console.log(`Batch registration completed. Successful: ${results.successful.length}, Failed: ${results.failed.length}`);
        }

        this.emit('batchRegister', results);
        return this;
    }

    /**
     * 对象批量注册（私有方法）
     * @private
     * @param {Object.<string, Function>} componentMap - 组件映射对象（key为组件名，value为组件类）
     * @returns {FormManager} this（支持链式调用）
     * @throws {TypeError} 当componentMap不是对象时抛出
     */
    _registerObject(componentMap) {
        Utils.validateParam(componentMap, 'componentMap', 'object');

        const keys = Object.keys(componentMap);
        if (keys.length === 0) {
            console.warn('Empty object provided for batch registration');
            return this;
        }

        const results = {
            successful: [],
            failed: []
        };

        keys.forEach(name => {
            try {
                if (typeof name !== 'string' || name.trim() === '') {
                    throw new TypeError(`Component name must be non-empty string, got '${name}'`);
                }

                const componentClass = componentMap[name];
                if (componentClass === null || componentClass === undefined) {
                    throw new TypeError(`Component class for '${name}' is null or undefined`);
                }

                this._registerSingle(name.trim(), componentClass);
                results.successful.push(name);
            } catch (error) {
                results.failed.push({ name, error: error.message });
                console.error(`Failed to register component '${name}':`, error);
            }
        });

        if (this.config.get('debug')) {
            console.log(`Object registration completed. Successful: ${results.successful.length}, Failed: ${results.failed.length}`);
        }

        this.emit('batchRegister', results);
        return this;
    }

    /**
     * 取消注册组件
     * @param {string} name - 组件名称
     * @returns {FormManager} this（支持链式调用）
     * @throws {TypeError} 当name不是字符串时抛出
     * @throws {Error} 当管理器已销毁时抛出
     */
    unregister(name) {
        this._checkDestroyed();
        this.registry.unregister(name);
        return this;
    }

    /**
     * 使用插件
     * @param {Function|Object} plugin - 插件函数或包含install方法的对象
     * @param {Object} [options={}] - 插件选项
     * @returns {FormManager} this（支持链式调用）
     * @throws {TypeError} 当plugin类型不正确时抛出
     * @throws {Error} 当管理器已销毁或插件安装失败时抛出
     */
    use(plugin, options = {}) {
        this._checkDestroyed();
        Utils.validateParam(plugin, 'plugin', ['function', 'object']);
        
        let uninstallFn = null;
        
        try {
            if (typeof plugin === 'function') {
                uninstallFn = plugin(this, options);
            } else if (plugin && typeof plugin.install === 'function') {
                uninstallFn = plugin.install(this, options);
            } else {
                throw new TypeError('Plugin must be a function or have an install method');
            }
            
            // 存储卸载函数
            const pluginName = plugin.name || plugin.pluginName || Utils.generateId('plugin');
            if (typeof uninstallFn === 'function') {
                this.plugins.set(pluginName, uninstallFn);
            }
            
            this.emit('pluginInstalled', { plugin, pluginName, options });
        } catch (error) {
            this.handleError(error);
            throw error;
        }
        
        return this;
    }

    /**
     * 卸载插件
     * @param {string} pluginName - 插件名称
     * @returns {FormManager} this（支持链式调用）
     * @throws {TypeError} 当pluginName不是字符串时抛出
     * @throws {Error} 当管理器已销毁或卸载过程中发生错误时抛出
     */
    unuse(pluginName) {
        this._checkDestroyed();
        Utils.validateParam(pluginName, 'pluginName', 'string');

        const uninstallFn = this.plugins.get(pluginName);
        if (uninstallFn) {
            try {
                uninstallFn();
                this.plugins.delete(pluginName);
                this.emit('pluginUninstalled', { pluginName });
            } catch (error) {
                this.handleError(error);
                throw error;
            }
        }

        return this;
    }

    /**
     * 初始化表单管理器
     * @param {string|jQuery} [selector] - 要扫描的容器选择器（不传则使用配置的autoScan）
     * @returns {Promise<FormManager>} this（支持链式调用）
     * @throws {Error} 当管理器已销毁、正在初始化或初始化失败时抛出
     */
    async init(selector) {
        if (this._destroyed) {
            throw new Error('FormManager has been destroyed');
        }

        if (this._initLock) {
            throw new Error('FormManager is already being initialized');
        }

        if (this.state === 'initialized') return this;

        this._initLock = true;
        this.state = 'initializing';
        
        try {
            this.emit('beforeInit');
            
            if (selector || this.config.get('autoScan')) {
                await this.scan(selector);
            }

            // 初始化字段联动规则（不依赖 data-component，纯表单字段也可工作）
            this.initConditionalRules(selector);
            
            this.state = 'initialized';
            this.emit('init');
            return this;
        } catch (error) {
            this.state = 'error';
            this.handleError(error);
            throw error;
        } finally {
            this._initLock = false;
        }
    }

    /**
     * 扫描并创建组件
     * @param {string|jQuery} [selector] - 要扫描的容器选择器（不传则扫描整个文档）
     * @returns {Promise<FormManager>} this（支持链式调用）
     * @throws {Error} 当管理器已销毁或组件数量超限时抛出
     */
    async scan(selector) {
        this._checkDestroyed();

        const container = selector ? $(selector) : $(document);
        const componentElements = container.find(this.config.get('componentSelector'));
        
        if (componentElements.length === 0) {
            return this;
        }

        // 检查组件数量限制
        const maxComponents = this.config.get('maxComponents');
        if (this.components.size + componentElements.length > maxComponents) {
            throw new Error(`Component count would exceed limit of ${maxComponents}`);
        }

        const initPromises = [];
        const gracefulErrorHandling = this.config.get('gracefulErrorHandling');
        
        componentElements.each((index, element) => {
            const $element = $(element);
            const componentType = $element.data('component');
            
            if (!componentType) {
                console.warn('Element has data-component attribute but no component type specified');
                return;
            }
            
            const componentOptions = $element.data('options') || {};

            if (this.registry.has(componentType)) {
                const promise = this.createComponent(componentType, element, componentOptions)
                    .catch(error => {
                        if (gracefulErrorHandling) {
                            console.error(`Failed to create component '${componentType}':`, error);
                            return null; // 返回null表示失败但不影响其他组件
                        } else {
                            throw error;
                        }
                    });
                
                initPromises.push(promise);
            } else {
                const errorMsg = `Unknown component type: ${componentType}`;
                if (gracefulErrorHandling) {
                    console.error(errorMsg);
                } else {
                    throw new Error(errorMsg);
                }
            }
        });

        // 等待所有组件初始化完成
        const results = await Promise.allSettled(initPromises);
        
        // 统计结果
        const successful = results.filter(r => r.status === 'fulfilled' && r.value).length;
        const failed = results.length - successful;
        
        this.emit('scanCompleted', { total: results.length, successful, failed });
        
        return this;
    }

    /**
     * 初始化字段联动规则
     * @param {string|jQuery} [selector]
     * @returns {FormManager}
     */
    initConditionalRules(selector) {
        this._checkDestroyed();

        const container = selector ? $(selector) : $(document);
        const $forms = container.is('form.dp-form') ? container : container.find('form.dp-form');

        if ($forms.length === 0) {
            return this;
        }

        $forms.each((_, formElement) => {
            const $form = $(formElement);
            this._initConditionalRulesForForm($form);
        });

        return this;
    }

    /**
     * 初始化单个表单联动规则
     * @param {jQuery} $form
     * @private
     */
    _initConditionalRulesForForm($form) {
        const rules = this._parseWhenRules($form);
        if (!Array.isArray(rules) || rules.length === 0) {
            return;
        }

        // 已绑定过时，仅重算一次
        if ($form.data('dpWhenInitialized')) {
            this._evaluateWhenRules($form, rules);
            return;
        }

        const dependencies = new Set();
        rules.forEach(rule => {
            (rule.conditions || []).forEach(condition => {
                const field = this._normalizeFieldName(condition.field);
                if (field) dependencies.add(field);
            });
        });

        const selectors = [];
        dependencies.forEach(field => {
            const escapedField = this._escapeAttrValue(field);
            const escapedArrayField = this._escapeAttrValue(`${field}[]`);
            selectors.push(`[name="${escapedField}"]`);
            selectors.push(`[name="${escapedArrayField}"]`);
        });

        const evaluate = Utils.debounce(() => this._evaluateWhenRules($form, rules), 10);

        if (selectors.length > 0) {
            $form.on(`change.dp-when input.dp-when`, selectors.join(','), evaluate);
        }

        $form.data('dpWhenInitialized', true);
        $form.data('dpWhenRules', rules);

        // 首次渲染立即计算，确保首屏状态一致
        this._evaluateWhenRules($form, rules);
    }

    /**
     * 解析表单联动规则
     * @param {jQuery} $form
     * @returns {Array}
     * @private
     */
    _parseWhenRules($form) {
        const encoded = $form.attr('data-when-rules');
        if (!encoded) return [];

        try {
            const json = window.atob(String(encoded));
            const rules = JSON.parse(json);
            return Array.isArray(rules) ? rules : [];
        } catch (error) {
            console.error('Failed to parse form when rules:', error);
            return [];
        }
    }

    /**
     * 执行规则计算
     * @param {jQuery} $form
     * @param {Array} rules
     * @private
     */
    _evaluateWhenRules($form, rules) {
        rules.forEach(rule => {
            try {
                const matched = this._evaluateWhenRule($form, rule);
                const actions = matched ? (rule.actions || []) : (rule.elseActions || []);
                this._applyWhenActions($form, rule, actions);
            } catch (error) {
                console.error('Failed to evaluate when rule:', error, rule);
            }
        });
    }

    /**
     * 执行单条规则
     * @param {jQuery} $form
     * @param {Object} rule
     * @returns {boolean}
     * @private
     */
    _evaluateWhenRule($form, rule) {
        const conditions = Array.isArray(rule.conditions) ? rule.conditions : [];
        if (conditions.length === 0) return false;

        const logic = String(rule.logic || 'and').toLowerCase();
        if (logic === 'or') {
            return conditions.some(condition => this._evaluateWhenCondition($form, condition));
        }

        return conditions.every(condition => this._evaluateWhenCondition($form, condition));
    }

    /**
     * 执行单条条件
     * @param {jQuery} $form
     * @param {Object} condition
     * @returns {boolean}
     * @private
     */
    _evaluateWhenCondition($form, condition) {
        const operator = String(condition.operator || 'eq').toLowerCase();
        const actualValue = this._getFieldValue($form, condition.field);
        const expectedValue = condition.value;

        switch (operator) {
            case 'eq':
                return this._compareEqual(actualValue, expectedValue);
            case 'neq':
                return !this._compareEqual(actualValue, expectedValue);
            case 'in':
                return this._compareIn(actualValue, expectedValue);
            case 'notin':
                return !this._compareIn(actualValue, expectedValue);
            case 'empty':
                return Utils.isEmpty(actualValue);
            case 'notempty':
                return !Utils.isEmpty(actualValue);
            case 'gt':
                return Number(actualValue) > Number(expectedValue);
            case 'egt':
                return Number(actualValue) >= Number(expectedValue);
            case 'lt':
                return Number(actualValue) < Number(expectedValue);
            case 'elt':
                return Number(actualValue) <= Number(expectedValue);
            default:
                return false;
        }
    }

    /**
     * 读取字段值
     * @param {jQuery} $form
     * @param {string} fieldName
     * @returns {*}
     * @private
     */
    _getFieldValue($form, fieldName) {
        const field = this._normalizeFieldName(fieldName);
        if (!field) return null;

        const $elements = this._findFieldElements($form, field);
        if ($elements.length === 0) return null;

        const $first = $elements.first();
        const tagName = ($first.prop('tagName') || '').toLowerCase();
        const type = String($first.attr('type') || '').toLowerCase();

        if (type === 'radio') {
            return $elements.filter(':checked').val() ?? '';
        }

        if (type === 'checkbox') {
            const checked = $elements.filter(':checked');
            if ($elements.length === 1) {
                return checked.length > 0 ? (checked.val() ?? true) : '';
            }
            return checked.map((_, el) => $(el).val()).get();
        }

        if (tagName === 'select' && $first.prop('multiple')) {
            return $first.val() || [];
        }

        if ($elements.length > 1) {
            return $elements.map((_, el) => $(el).val()).get();
        }

        return $first.val();
    }

    /**
     * 执行动作
     * @param {jQuery} $form
     * @param {Object} rule
     * @param {string[]} actions
     * @private
     */
    _applyWhenActions($form, rule, actions) {
        if (!Array.isArray(actions) || actions.length === 0) return;

        const $wrapper = this._findRuleTarget($form, rule);
        if (!$wrapper || $wrapper.length === 0) return;

        actions.forEach(action => {
            switch (String(action).toLowerCase()) {
                case 'show':
                    $wrapper.show();
                    break;
                case 'hide':
                    $wrapper.hide();
                    break;
                case 'enable':
                    this._setTargetDisabled($wrapper, false);
                    break;
                case 'disable':
                    this._setTargetDisabled($wrapper, true);
                    break;
                case 'require':
                    this._setTargetRequired($wrapper, true);
                    break;
                case 'unrequire':
                    this._setTargetRequired($wrapper, false);
                    break;
                case 'clear':
                    this._clearTargetValue($wrapper);
                    break;
                default:
                    break;
            }
        });
    }

    /**
     * 查找规则目标容器
     * @param {jQuery} $form
     * @param {Object} rule
     * @returns {jQuery}
     * @private
     */
    _findRuleTarget($form, rule) {
        const formId = String($form.attr('id') || '');
        const targetId = String(rule.targetId || '').trim();

        if (targetId) {
            const wrapperId = formId && !targetId.startsWith(`${formId}-`) ? `${formId}-${targetId}` : targetId;
            const $byId = $form.find(`#${this._escapeSelector(wrapperId)}`);
            if ($byId.length > 0) return $byId.first();
        }

        const target = this._normalizeFieldName(rule.target);
        if (!target) return $();

        const $fieldElements = this._findFieldElements($form, target);
        if ($fieldElements.length > 0) {
            return $fieldElements.first().closest('.mb-3');
        }

        return $();
    }

    /**
     * 设置目标禁用状态
     * @param {jQuery} $wrapper
     * @param {boolean} disabled
     * @private
     */
    _setTargetDisabled($wrapper, disabled) {
        $wrapper.find(':input').not('[type="hidden"]').prop('disabled', disabled);
    }

    /**
     * 设置目标必填状态
     * @param {jQuery} $wrapper
     * @param {boolean} required
     * @private
     */
    _setTargetRequired($wrapper, required) {
        $wrapper.find(':input').not('[type="hidden"]').prop('required', required);
        $wrapper.find('label.form-label').toggleClass('required', required);
    }

    /**
     * 清空目标值
     * @param {jQuery} $wrapper
     * @private
     */
    _clearTargetValue($wrapper) {
        $wrapper.find(':input,select,textarea').each((_, element) => {
            const $element = $(element);
            const tagName = ($element.prop('tagName') || '').toLowerCase();
            const type = String($element.attr('type') || '').toLowerCase();

            if (['button', 'submit', 'reset'].includes(type)) {
                return;
            }

            if (type === 'radio' || type === 'checkbox') {
                $element.prop('checked', false).trigger('change');
                return;
            }

            if (tagName === 'select') {
                if ($element.prop('multiple')) {
                    $element.val([]).trigger('change');
                } else {
                    $element.val('').trigger('change');
                }
                return;
            }

            $element.val('').trigger('input').trigger('change');
        });
    }

    /**
     * 读取字段元素集合
     * @param {jQuery} $form
     * @param {string} field
     * @returns {jQuery}
     * @private
     */
    _findFieldElements($form, field) {
        const escapedField = this._escapeAttrValue(field);
        const escapedArrayField = this._escapeAttrValue(`${field}[]`);
        return $form.find(`[name="${escapedField}"], [name="${escapedArrayField}"]`);
    }

    /**
     * 归一化字段名（去除末尾 []）
     * @param {string} field
     * @returns {string}
     * @private
     */
    _normalizeFieldName(field) {
        return String(field || '').trim().replace(/\[\]$/, '');
    }

    /**
     * 转义属性值
     * @param {string} value
     * @returns {string}
     * @private
     */
    _escapeAttrValue(value) {
        return String(value).replace(/\\/g, '\\\\').replace(/"/g, '\\"');
    }

    /**
     * 转义选择器
     * @param {string} value
     * @returns {string}
     * @private
     */
    _escapeSelector(value) {
        if (window.CSS && typeof window.CSS.escape === 'function') {
            return window.CSS.escape(String(value));
        }
        return String(value).replace(/([ #;?%&,.+*~\':"!^$[\]()=>|\/@])/g, '\\$1');
    }

    /**
     * 值比较（等于）
     * @param {*} actual
     * @param {*} expected
     * @returns {boolean}
     * @private
     */
    _compareEqual(actual, expected) {
        if (Array.isArray(actual)) {
            return actual.some(item => String(item) === String(expected));
        }
        return String(actual ?? '') === String(expected ?? '');
    }

    /**
     * 值比较（in）
     * @param {*} actual
     * @param {*} expected
     * @returns {boolean}
     * @private
     */
    _compareIn(actual, expected) {
        const expectedList = Array.isArray(expected) ? expected : [expected];
        const normalizedExpected = expectedList.map(item => String(item));

        if (Array.isArray(actual)) {
            return actual.some(item => normalizedExpected.includes(String(item)));
        }

        return normalizedExpected.includes(String(actual ?? ''));
    }

    /**
     * 创建组件实例
     * @param {string} type - 组件类型
     * @param {HTMLElement|jQuery|string} element - DOM元素、jQuery对象或选择器
     * @param {Object} [options={}] - 组件选项
     * @returns {Promise<BaseComponent>} 创建的组件实例
     * @throws {Error} 当管理器已销毁、组件类型未注册或初始化失败时抛出
     * @throws {Error} 当初始化超时时抛出（超时时间由componentTimeout配置）
     */
    async createComponent(type, element, options = {}) {
        this._checkDestroyed();

        const ComponentClass = this.registry.get(type);
        if (!ComponentClass) {
            throw new Error(`Unknown component type: ${type}`);
        }

        const component = new ComponentClass(element, options, this);

        try {
            // 设置超时
            const timeout = this.config.get('componentTimeout');
            const initPromise = component.init();

            if (timeout > 0) {
                const timeoutPromise = new Promise((_, reject) => {
                    setTimeout(() => reject(new Error(`Component initialization timeout after ${timeout}ms`)), timeout);
                });

                await Promise.race([initPromise, timeoutPromise]);
            } else {
                await initPromise;
            }

            this.components.set(component.id, component);
            this.emit('componentCreated', component);
            return component;
        } catch (error) {
            // 确保清理失败的组件
            try {
                await component.destroy();
            } catch (destroyError) {
                console.error('Error destroying failed component:', destroyError);
            }

            this.handleError(error);
            throw error;
        }
    }

    /**
     * 根据ID获取组件
     * @param {string} id - 组件ID
     * @returns {BaseComponent|undefined} 组件实例（未找到返回undefined）
     * @throws {TypeError} 当id不是字符串时抛出
     * @throws {Error} 当管理器已销毁时抛出
     */
    getComponent(id) {
        this._checkDestroyed();
        Utils.validateParam(id, 'id', 'string');
        return this.components.get(id);
    }

    /**
     * 查找组件
     * @param {Function} predicate - 断言函数（component => boolean）
     * @returns {BaseComponent|undefined} 第一个匹配的组件实例（未找到返回undefined）
     * @throws {TypeError} 当predicate不是函数时抛出
     * @throws {Error} 当管理器已销毁时抛出
     */
    findComponent(predicate) {
        this._checkDestroyed();
        Utils.validateParam(predicate, 'predicate', 'function');
        return Array.from(this.components.values()).find(predicate);
    }

    /**
     * 根据类型获取组件
     * @param {string} type - 组件类型（模糊匹配类名）
     * @returns {BaseComponent[]} 匹配的组件实例数组
     * @throws {TypeError} 当type不是字符串时抛出
     * @throws {Error} 当管理器已销毁时抛出
     */
    getComponentsByType(type) {
        this._checkDestroyed();
        Utils.validateParam(type, 'type', 'string');
        return Array.from(this.components.values()).filter(component =>
            component.constructor.name.toLowerCase().includes(type.toLowerCase())
        );
    }

    /**
     * 根据name属性获取组件
     * @param {string} name - 组件的name属性值
     * @returns {BaseComponent|undefined} 组件实例（未找到返回undefined）
     * @throws {TypeError} 当name不是字符串时抛出
     * @throws {Error} 当管理器已销毁时抛出
     */
    getComponentByName(name) {
        this._checkDestroyed();
        Utils.validateParam(name, 'name', 'string');
        return Array.from(this.components.values()).find(component =>
            component.element && component.element.attr('data-name') === name
        );
    }

    /**
     * 获取所有组件
     * @returns {BaseComponent[]} 所有组件实例数组
     * @throws {Error} 当管理器已销毁时抛出
     */
    getAllComponents() {
        this._checkDestroyed();
        return Array.from(this.components.values());
    }

    /**
     * 销毁单个组件
     * @param {string} id - 组件ID
     * @returns {Promise<FormManager>} this（支持链式调用）
     * @throws {TypeError} 当id不是字符串时抛出
     * @throws {Error} 当管理器已销毁或组件销毁失败时抛出
     */
    async destroyComponent(id) {
        this._checkDestroyed();
        Utils.validateParam(id, 'id', 'string');

        const component = this.components.get(id);
        if (component) {
            try {
                await component.destroy();
                this.components.delete(id);
                this.emit('componentDestroyed', component);
            } catch (error) {
                this.handleError(error);
                throw error;
            }
        }
        return this;
    }

    /**
     * 销毁表单管理器
     * @returns {Promise<FormManager>} this（支持链式调用）
     * @throws {Error} 当销毁过程中发生错误时抛出
     */
    async destroy() {
        if (this._destroyed) return this;

        this._destroyed = true;
        this.state = 'destroying';
        
        try {
            this.emit('beforeDestroy');
            
            // 卸载所有插件
            for (const [pluginName, uninstallFn] of this.plugins) {
                try {
                    uninstallFn();
                } catch (error) {
                    console.error(`Error uninstalling plugin '${pluginName}':`, error);
                }
            }
            this.plugins.clear();
            
            // 销毁所有组件
            const destroyPromises = Array.from(this.components.values()).map(component => 
                component.destroy().catch(error => {
                    console.error(`Error destroying component ${component.id}:`, error);
                })
            );
            
            await Promise.allSettled(destroyPromises);
            this.components.clear();

            // 清理联动规则事件
            $('.dp-form').off('.dp-when').removeData('dpWhenInitialized').removeData('dpWhenRules');
            
            // 清理管理器
            this.events.destroy();
            this.registry.destroy();
            this.config.destroy();
            
            this.state = 'destroyed';
            
            return this;
        } catch (error) {
            console.error('Error during FormManager destruction:', error);
            throw error;
        }
    }

    /**
     * 注册事件监听器
     * @param {string} event - 事件名称
     * @param {Function} callback - 回调函数
     * @param {boolean} [once=false] - 是否只执行一次
     * @returns {Function} 移除监听器的函数
     * @throws {TypeError} 当event不是字符串或callback不是函数时抛出
     * @throws {Error} 当管理器已销毁时抛出
     */
    on(event, callback, once = false) {
        this._checkDestroyed();
        return this.events.on(event, callback, once);
    }

    /**
     * 注册一次性事件监听器
     * @param {string} event - 事件名称
     * @param {Function} callback - 回调函数
     * @returns {Function} 移除监听器的函数
     */
    once(event, callback) {
        return this.on(event, callback, true);
    }

    /**
     * 移除事件监听器
     * @param {string} event - 事件名称
     * @param {Function} [callback] - 回调函数（不传则移除该事件的所有监听器）
     * @returns {FormManager} this（支持链式调用）
     * @throws {Error} 当管理器已销毁时抛出
     */
    off(event, callback) {
        this._checkDestroyed();
        this.events.off(event, callback);
        return this;
    }

    /**
     * 触发事件
     * @param {string} event - 事件名称
     * @param {...*} args - 传递给监听器的参数
     * @returns {EmitResult} 执行结果 {success: boolean, errors: Array}
     */
    emit(event, ...args) {
        if (this._destroyed) return { success: false, errors: [] };
        return this.events.emit(event, ...args);
    }

    /**
     * 错误处理
     * @param {Error} error - 错误对象
     * @returns {void}
     */
    handleError(error) {
        const errorHandler = this.config.get('errorHandler');
        
        try {
            switch (errorHandler) {
                case 'console':
                    console.error('DolphinForm Error:', error);
                    break;
                case 'notify':
                    if (window.Dolphin && typeof window.Dolphin.error === 'function') {
                        window.Dolphin.error(error.message || String(error));
                    } else {
                        console.error('DolphinForm Error:', error);
                    }
                    break;
                case 'throw':
                    throw error;
                default:
                    if (typeof errorHandler === 'function') {
                        errorHandler(error);
                    } else {
                        console.error('DolphinForm Error:', error);
                    }
            }
        } catch (handlerError) {
            console.error('Error in error handler:', handlerError);
            console.error('Original error:', error);
        }
        
        // 发送错误事件
        this.emit('error', error);
    }

    /**
     * 验证所有组件
     * @returns {{valid: boolean, errors: Array, results: Array}} 验证结果
     * @throws {Error} 当管理器已销毁时抛出
     * @property {boolean} valid - 是否全部验证通过
     * @property {Array<{id: string, name: string, valid: boolean, errors: string[]}>} errors - 验证失败的组件列表
     * @property {Array<{id: string, name: string, valid: boolean, errors: string[]}>} results - 所有组件验证结果
     */
    validate() {
        this._checkDestroyed();

        const results = [];
        const errors = [];

        for (const component of this.components.values()) {
            try {
                const result = component.validate();
                const componentResult = {
                    id: component.id,
                    name: component.element?.attr('name') || component.id,
                    ...result
                };

                results.push(componentResult);

                if (!result.valid) {
                    errors.push(componentResult);
                }
            } catch (error) {
                const errorResult = {
                    id: component.id,
                    name: component.element?.attr('name') || component.id,
                    valid: false,
                    errors: [`Validation error: ${error.message}`]
                };

                results.push(errorResult);
                errors.push(errorResult);
            }
        }

        const valid = errors.length === 0;

        return { valid, errors, results };
    }

    /**
     * 获取所有组件值
     * @returns {Object.<string, *>} 组件值对象（key为data-name或组件id，value为组件值）
     * @throws {Error} 当管理器已销毁时抛出
     *
     * @description
     * 返回值的 key 规则：
     * 1. 优先使用 data-name 属性（推荐）
     * 2. 如果没有 data-name，使用组件 ID（向后兼容）
     *
     * @example
     * // 推荐：组件使用 data-name
     * // HTML: <div data-component="textarea-max" data-name="description">
     * const values = DpForm.getValues();
     * // 返回: { description: 'some text' }
     *
     * @example
     * // 向后兼容：没有 data-name 的组件
     * // HTML: <div data-component="textarea-max" id="dp-form-textarea">
     * const values = DpForm.getValues();
     * // 返回: { 'dp-form-textarea': 'some text' }
     */
    getValues() {
        this._checkDestroyed();

        const values = {};

        for (const component of this.components.values()) {
            try {
                if (typeof component.getValue === 'function') {
                    const name = component.element?.attr('data-name') || component.id;
                    values[name] = component.getValue();
                }
            } catch (error) {
                console.error(`Error getting value from component ${component.id}:`, error);
            }
        }

        return values;
    }

    /**
     * 设置所有组件值
     * @param {Object.<string, *>} values - 值对象（key为组件data-name或id，value为要设置的值）
     * @returns {FormManager} this（支持链式调用）
     * @throws {TypeError} 当values不是对象时抛出
     * @throws {Error} 当管理器已销毁时抛出
     *
     * @description
     * 支持两种标识符查找方式：
     * 1. data-name 属性（通过 getComponentByName 查找）
     * 2. 组件 ID（当 getComponentByName 找不到时，通过 getComponent 查找）
     *
     * @example
     * // 推荐方式：使用 data-name
     * // HTML: <div data-component="textarea-max" data-name="description">
     * DpForm.setValues({ description: 'Hello World' });
     *
     * @example
     * // 向后兼容：使用组件ID（从 getValues() 返回的数据）
     * const values = DpForm.getValues(); // { 'dp-form-textarea': 'old value' }
     * values['dp-form-textarea'] = 'new value';
     * DpForm.setValues(values); // ✅ 也能工作
     */
    setValues(values) {
        this._checkDestroyed();
        Utils.validateParam(values, 'values', 'object');

        const errors = [];

        Object.keys(values).forEach(nameOrId => {
            try {
                // 先尝试按 data-name 查找
                let component = this.getComponentByName(nameOrId);

                // 如果找不到，再尝试按 ID 查找（向后兼容）
                if (!component) {
                    component = this.getComponent(nameOrId);
                }

                if (component && typeof component.setValue === 'function') {
                    component.setValue(values[nameOrId]);
                }
            } catch (error) {
                errors.push({ name: nameOrId, error: error.message });
                console.error(`Error setting value for '${nameOrId}':`, error);
            }
        });

        if (errors.length > 0) {
            this.emit('setValuesErrors', errors);
        }

        return this;
    }

    /**
     * 获取统计信息
     * @returns {{componentCount: number, registeredTypes: number, pluginCount: number, state: string, eventListeners: number}} 统计信息对象
     */
    getStats() {
        return {
            componentCount: this.components.size,
            registeredTypes: this.registry.getComponentNames().length,
            pluginCount: this.plugins.size,
            state: this.state,
            eventListeners: this.events.events.size
        };
    }

    /**
     * 检查是否已销毁
     * @returns {boolean} 是否已销毁
     */
    isDestroyed() {
        return this._destroyed;
    }

    /**
     * 私有检查方法
     * @private
     * @throws {Error} 当管理器已销毁时抛出
     */
    _checkDestroyed() {
        if (this._destroyed) {
            throw new Error('FormManager has been destroyed');
        }
    }
}

/**
 * 全局实例管理器（简化版）
 * @class GlobalManager
 */
class GlobalManager {
    /**
     * 构造函数
     */
    constructor() {
        /** @type {FormManager|null} */
        this.instance = null;
        /** @type {boolean} */
        this._destroyed = false;
    }

    /**
     * 注册表单管理器实例
     * @param {FormManager} instance - 表单管理器实例
     * @returns {GlobalManager} this（支持链式调用）
     * @throws {TypeError} 当instance不是FormManager实例时抛出
     * @throws {Error} 当管理器已销毁时抛出
     */
    register(instance) {
        this._checkDestroyed();

        if (!(instance instanceof FormManager)) {
            throw new TypeError('Instance must be a FormManager');
        }

        this.instance = instance;
        return this;
    }

    /**
     * 获取表单管理器实例
     * @returns {FormManager|null} 表单管理器实例（未注册返回null）
     * @throws {Error} 当管理器已销毁时抛出
     */
    get() {
        this._checkDestroyed();
        return this.instance;
    }

    /**
     * 销毁全局管理器
     * @returns {void}
     */
    destroy() {
        this.instance = null;
        this._destroyed = true;
    }

    /**
     * 检查管理器是否已销毁
     * @private
     * @throws {Error} 当管理器已销毁时抛出
     */
    _checkDestroyed() {
        if (this._destroyed) {
            throw new Error('GlobalManager has been destroyed');
        }
    }
}

// 创建全局实例
const globalManager = new GlobalManager();

// 创建默认的表单实例
const DpForm = new FormManager();

// 注册到全局管理器
globalManager.register(DpForm);

// 将默认实例设为全局访问对象
window.DpForm = DpForm;

// 扩展默认实例，添加额外的API方法
Object.assign(window.DpForm, {
    // 创建新实例
    create: (options) => new FormManager(options),

    // 获取默认实例（返回自己）
    getInstance: () => DpForm,
    
    // 全局组件映射管理
    setComponentMap: (componentMap) => {
        window.DpFormComponentMap = componentMap;
        return DpForm;
    },
    
    getComponentMap: () => {
        return window.DpFormComponentMap || {};
    },
    
    addToComponentMap: (name, componentClass) => {
        if (!window.DpFormComponentMap) {
            window.DpFormComponentMap = {};
        }
        window.DpFormComponentMap[name] = componentClass;
        return DpForm;
    },
    
    removeFromComponentMap: (name) => {
        if (window.DpFormComponentMap && window.DpFormComponentMap[name]) {
            delete window.DpFormComponentMap[name];
        }
        return DpForm;
    },
    
    // 导出核心类供扩展使用
    BaseComponent,
    ConfigManager,
    EventManager,
    ComponentRegistry,
    FormManager,
    Utils,
    
    // 版本信息
    version: '2.0.0'
});

// 自动初始化
const autoInit = Utils.debounce(() => {
    if (DpForm.config.get('autoInit') && !DpForm.isDestroyed()) {
        DpForm.init().catch(error => {
            console.error('Auto-initialization failed:', error);
        });
    }
}, 10);

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', autoInit);
} else {
    // DOM已经加载完成
    setTimeout(autoInit, 0);
}

// 页面卸载时清理
window.addEventListener('beforeunload', () => {
    try {
        globalManager.destroy();
    } catch (error) {
        console.error('Error during cleanup:', error);
    }
});

// 开发模式下的调试支持
if (DpForm.config.get('debug')) {
    window.DpFormDebug = {
        globalManager,
        instance: () => globalManager.get(),
        stats: () => DpForm.getStats(),
        components: () => DpForm.getAllComponents(),
        events: () => DpForm.events.events
    };
    
    console.log('DolphinForm v2.0 initialized in debug mode');
}

jQuery(document).ready(function() {
    // 关闭弹窗按钮
    $('#dp-form-close-pop').click(function () {
        // 获取窗口索引
        const index = parent.layer.getFrameIndex(window.name);
        parent.layer.close(index);
    });
});
