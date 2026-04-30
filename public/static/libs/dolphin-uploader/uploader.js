/**
 * DolphinUploader - Dolphin文件上传组件
 *
 * 特性：
 * - 基于现代Web API (Fetch, File API, Crypto API)
 * - 支持分片上传和断点续传
 * - 拖拽上传支持
 * - 并发控制和队列管理
 * - 实时进度显示
 * - 错误处理和重试机制
 * - 兼容DolphinPHP现有接口
 * - 集成Dolphin驱动系统
 * - 智能预览资源管理，确保上传失败时仍可预览
 * - 支持手动和自动预览资源清理
 */

/*
 * [js-sha1]{@link https://github.com/emn178/js-sha1}
 * 引入此库是为了正确计算流式哈希
 * @version 0.7.0
 * @author Chen, Yi-Cyuan [emn178@gmail.com]
 * @copyright Chen, Yi-Cyuan 2014-2024
 * @license MIT
 */
/* ===== SHA1 Library (js-sha1 v0.7.0 by Chen, Yi-Cyuan) ===== */
!function(){"use strict";function t(t){t?(l[0]=l[16]=l[1]=l[2]=l[3]=l[4]=l[5]=l[6]=l[7]=l[8]=l[9]=l[10]=l[11]=l[12]=l[13]=l[14]=l[15]=0,this.blocks=l):this.blocks=[0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0],this.h0=1732584193,this.h1=4023233417,this.h2=2562383102,this.h3=271733878,this.h4=3285377520,this.block=this.start=this.bytes=this.hBytes=0,this.finalized=this.hashed=!1,this.first=!0}function r(r,e){var i,h=v(r);if(r=h[0],h[1]){var s,n=[],o=r.length,a=0;for(i=0;i<o;++i)(s=r.charCodeAt(i))<128?n[a++]=s:s<2048?(n[a++]=192|s>>>6,n[a++]=128|63&s):s<55296||s>=57344?(n[a++]=224|s>>>12,n[a++]=128|s>>>6&63,n[a++]=128|63&s):(s=65536+((1023&s)<<10|1023&r.charCodeAt(++i)),n[a++]=240|s>>>18,n[a++]=128|s>>>12&63,n[a++]=128|s>>>6&63,n[a++]=128|63&s);r=n}r.length>64&&(r=new t(!0).update(r).array());var f=[],u=[];for(i=0;i<64;++i){var c=r[i]||0;f[i]=92^c,u[i]=54^c}t.call(this,e),this.update(u),this.oKeyPad=f,this.inner=!0,this.sharedMemory=e}var e="input is invalid type",i="object"==typeof window,h=i?window:{};h.JS_SHA1_NO_WINDOW&&(i=!1);var s=!i&&"object"==typeof self,n=!h.JS_SHA1_NO_NODE_JS&&"object"==typeof process&&process.versions&&process.versions.node;n?h=global:s&&(h=self);var o=!h.JS_SHA1_NO_COMMON_JS&&"object"==typeof module&&module.exports,a="function"==typeof define&&define.amd,f=!h.JS_SHA1_NO_ARRAY_BUFFER&&"undefined"!=typeof ArrayBuffer,u="0123456789abcdef".split(""),c=[-2147483648,8388608,32768,128],y=[24,16,8,0],p=["hex","array","digest","arrayBuffer"],l=[],d=Array.isArray;!h.JS_SHA1_NO_NODE_JS&&d||(d=function(t){return"[object Array]"===Object.prototype.toString.call(t)});var b=ArrayBuffer.isView;!f||!h.JS_SHA1_NO_ARRAY_BUFFER_IS_VIEW&&b||(b=function(t){return"object"==typeof t&&t.buffer&&t.buffer.constructor===ArrayBuffer});var v=function(t){var r=typeof t;if("string"===r)return[t,!0];if("object"!==r||null===t)throw new Error(e);if(f&&t.constructor===ArrayBuffer)return[new Uint8Array(t),!1];if(!d(t)&&!b(t))throw new Error(e);return[t,!1]},_=function(r){return function(e){return new t(!0).update(e)[r]()}},A=function(t){var r,i=require("crypto"),s=require("buffer").Buffer;r=s.from&&!h.JS_SHA1_NO_BUFFER_FROM?s.from:function(t){return new s(t)};return function(h){if("string"==typeof h)return i.createHash("sha1").update(h,"utf8").digest("hex");if(null===h||void 0===h)throw new Error(e);return h.constructor===ArrayBuffer&&(h=new Uint8Array(h)),d(h)||b(h)||h.constructor===s?i.createHash("sha1").update(r(h)).digest("hex"):t(h)}},w=function(t){return function(e,i){return new r(e,!0).update(i)[t]()}};t.prototype.update=function(t){if(this.finalized)throw new Error("finalize already called");var r=v(t);t=r[0];for(var e,i,h=r[1],s=0,n=t.length||0,o=this.blocks;s<n;){if(this.hashed&&(this.hashed=!1,o[0]=this.block,this.block=o[16]=o[1]=o[2]=o[3]=o[4]=o[5]=o[6]=o[7]=o[8]=o[9]=o[10]=o[11]=o[12]=o[13]=o[14]=o[15]=0),h)for(i=this.start;s<n&&i<64;++s)(e=t.charCodeAt(s))<128?o[i>>>2]|=e<<y[3&i++]:e<2048?(o[i>>>2]|=(192|e>>>6)<<y[3&i++],o[i>>>2]|=(128|63&e)<<y[3&i++]):e<55296||e>=57344?(o[i>>>2]|=(224|e>>>12)<<y[3&i++],o[i>>>2]|=(128|e>>>6&63)<<y[3&i++],o[i>>>2]|=(128|63&e)<<y[3&i++]):(e=65536+((1023&e)<<10|1023&t.charCodeAt(++s)),o[i>>>2]|=(240|e>>>18)<<y[3&i++],o[i>>>2]|=(128|e>>>12&63)<<y[3&i++],o[i>>>2]|=(128|e>>>6&63)<<y[3&i++],o[i>>>2]|=(128|63&e)<<y[3&i++]);else for(i=this.start;s<n&&i<64;++s)o[i>>>2]|=t[s]<<y[3&i++];this.lastByteIndex=i,this.bytes+=i-this.start,i>=64?(this.block=o[16],this.start=i-64,this.hash(),this.hashed=!0):this.start=i}return this.bytes>4294967295&&(this.hBytes+=this.bytes/4294967296<<0,this.bytes=this.bytes%4294967296),this},t.prototype.finalize=function(){if(!this.finalized){this.finalized=!0;var t=this.blocks,r=this.lastByteIndex;t[16]=this.block,t[r>>>2]|=c[3&r],this.block=t[16],r>=56&&(this.hashed||this.hash(),t[0]=this.block,t[16]=t[1]=t[2]=t[3]=t[4]=t[5]=t[6]=t[7]=t[8]=t[9]=t[10]=t[11]=t[12]=t[13]=t[14]=t[15]=0),t[14]=this.hBytes<<3|this.bytes>>>29,t[15]=this.bytes<<3,this.hash()}},t.prototype.hash=function(){var t,r,e=this.h0,i=this.h1,h=this.h2,s=this.h3,n=this.h4,o=this.blocks;for(t=16;t<80;++t)r=o[t-3]^o[t-8]^o[t-14]^o[t-16],o[t]=r<<1|r>>>31;for(t=0;t<20;t+=5)e=(r=(i=(r=(h=(r=(s=(r=(n=(r=e<<5|e>>>27)+(i&h|~i&s)+n+1518500249+o[t]<<0)<<5|n>>>27)+(e&(i=i<<30|i>>>2)|~e&h)+s+1518500249+o[t+1]<<0)<<5|s>>>27)+(n&(e=e<<30|e>>>2)|~n&i)+h+1518500249+o[t+2]<<0)<<5|h>>>27)+(s&(n=n<<30|n>>>2)|~s&e)+i+1518500249+o[t+3]<<0)<<5|i>>>27)+(h&(s=s<<30|s>>>2)|~h&n)+e+1518500249+o[t+4]<<0,h=h<<30|h>>>2;for(;t<40;t+=5)e=(r=(i=(r=(h=(r=(s=(r=(n=(r=e<<5|e>>>27)+(i^h^s)+n+1859775393+o[t]<<0)<<5|n>>>27)+(e^(i=i<<30|i>>>2)^h)+s+1859775393+o[t+1]<<0)<<5|s>>>27)+(n^(e=e<<30|e>>>2)^i)+h+1859775393+o[t+2]<<0)<<5|h>>>27)+(s^(n=n<<30|n>>>2)^e)+i+1859775393+o[t+3]<<0)<<5|i>>>27)+(h^(s=s<<30|s>>>2)^n)+e+1859775393+o[t+4]<<0,h=h<<30|h>>>2;for(;t<60;t+=5)e=(r=(i=(r=(h=(r=(s=(r=(n=(r=e<<5|e>>>27)+(i&h|i&s|h&s)+n-1894007588+o[t]<<0)<<5|n>>>27)+(e&(i=i<<30|i>>>2)|e&h|i&h)+s-1894007588+o[t+1]<<0)<<5|s>>>27)+(n&(e=e<<30|e>>>2)|n&i|e&i)+h-1894007588+o[t+2]<<0)<<5|h>>>27)+(s&(n=n<<30|n>>>2)|s&e|n&e)+i-1894007588+o[t+3]<<0)<<5|i>>>27)+(h&(s=s<<30|s>>>2)|h&n|s&n)+e-1894007588+o[t+4]<<0,h=h<<30|h>>>2;for(;t<80;t+=5)e=(r=(i=(r=(h=(r=(s=(r=(n=(r=e<<5|e>>>27)+(i^h^s)+n-899497514+o[t]<<0)<<5|n>>>27)+(e^(i=i<<30|i>>>2)^h)+s-899497514+o[t+1]<<0)<<5|s>>>27)+(n^(e=e<<30|e>>>2)^i)+h-899497514+o[t+2]<<0)<<5|h>>>27)+(s^(n=n<<30|n>>>2)^e)+i-899497514+o[t+3]<<0)<<5|i>>>27)+(h^(s=s<<30|s>>>2)^n)+e-899497514+o[t+4]<<0,h=h<<30|h>>>2;this.h0=this.h0+e<<0,this.h1=this.h1+i<<0,this.h2=this.h2+h<<0,this.h3=this.h3+s<<0,this.h4=this.h4+n<<0},t.prototype.hex=function(){this.finalize();var t=this.h0,r=this.h1,e=this.h2,i=this.h3,h=this.h4;return u[t>>>28&15]+u[t>>>24&15]+u[t>>>20&15]+u[t>>>16&15]+u[t>>>12&15]+u[t>>>8&15]+u[t>>>4&15]+u[15&t]+u[r>>>28&15]+u[r>>>24&15]+u[r>>>20&15]+u[r>>>16&15]+u[r>>>12&15]+u[r>>>8&15]+u[r>>>4&15]+u[15&r]+u[e>>>28&15]+u[e>>>24&15]+u[e>>>20&15]+u[e>>>16&15]+u[e>>>12&15]+u[e>>>8&15]+u[e>>>4&15]+u[15&e]+u[i>>>28&15]+u[i>>>24&15]+u[i>>>20&15]+u[i>>>16&15]+u[i>>>12&15]+u[i>>>8&15]+u[i>>>4&15]+u[15&i]+u[h>>>28&15]+u[h>>>24&15]+u[h>>>20&15]+u[h>>>16&15]+u[h>>>12&15]+u[h>>>8&15]+u[h>>>4&15]+u[15&h]},t.prototype.toString=t.prototype.hex,t.prototype.digest=function(){this.finalize();var t=this.h0,r=this.h1,e=this.h2,i=this.h3,h=this.h4;return[t>>>24&255,t>>>16&255,t>>>8&255,255&t,r>>>24&255,r>>>16&255,r>>>8&255,255&r,e>>>24&255,e>>>16&255,e>>>8&255,255&e,i>>>24&255,i>>>16&255,i>>>8&255,255&i,h>>>24&255,h>>>16&255,h>>>8&255,255&h]},t.prototype.array=t.prototype.digest,t.prototype.arrayBuffer=function(){this.finalize();var t=new ArrayBuffer(20),r=new DataView(t);return r.setUint32(0,this.h0),r.setUint32(4,this.h1),r.setUint32(8,this.h2),r.setUint32(12,this.h3),r.setUint32(16,this.h4),t},(r.prototype=new t).finalize=function(){if(t.prototype.finalize.call(this),this.inner){this.inner=!1;var r=this.array();t.call(this,this.sharedMemory),this.update(this.oKeyPad),this.update(r),t.prototype.finalize.call(this)}};var S=function(){var r=_("hex");n&&(r=A(r)),r.create=function(){return new t},r.update=function(t){return r.create().update(t)};for(var e=0;e<p.length;++e){var i=p[e];r[i]=_(i)}return r}();S.sha1=S,S.sha1.hmac=function(){var t=w("hex");t.create=function(t){return new r(t)},t.update=function(r,e){return t.create(r).update(e)};for(var e=0;e<p.length;++e){var i=p[e];t[i]=w(i)}return t}(),o?module.exports=S:(h.sha1=S,a&&define(function(){return S}))}();
/* ===== End SHA1 Library ===== */

/**
 * DolphinUploader 常量定义
 * 集中管理所有魔法数字和配置常量，提升代码可读性和维护性
 */
const UPLOADER_CONSTANTS = {
    // 文件约束
    FILE_CONSTRAINTS: {
        MAX_NAME_LENGTH: 255                     // 文件名最大长度
    },
    
    // 性能阈值
    PERFORMANCE_THRESHOLDS: {
        DEFAULT_CONCURRENT: 3,                   // 默认并发数
        DEFAULT_RETRIES: 3,                      // 默认重试次数
        DEFAULT_RETRY_DELAY: 1000,               // 默认重试延迟 (毫秒)
        BASIC_CONCURRENT: 2,                     // 基础预设并发数
        SECURE_CONCURRENT: 1,                    // 安全预设并发数
        PERFORMANCE_CONCURRENT: 4,               // 性能预设并发数
        HASH_SAMPLE_SIZE: 64 * 1024,             // 哈希采样大小 (64KB)
        PREVIEW_CLEANUP_DELAY: 5000              // 预览清理延迟 (毫秒)
    },
    
    // 文件大小单位
    SIZE_UNITS: {
        BYTES_PER_KB: 1024,
        BYTES_PER_MB: 1024 * 1024,
        BYTES_PER_GB: 1024 * 1024 * 1024,
        BYTES_PER_TB: 1024 * 1024 * 1024 * 1024
    },
    
    // 默认配置值
    DEFAULT_VALUES: {
        MAX_FILES_BASIC: 10,                     // 基础预设最大文件数
        MAX_FILES_SECURE: 3,                     // 安全预设最大文件数
        MAX_FILES_ENTERPRISE: 20,                // 企业预设最大文件数
        MAX_FILES_PERFORMANCE: 50,               // 性能预设最大文件数
        SECURE_RETRY_DELAY: 2000,                // 安全预设重试延迟
        PERFORMANCE_RETRY_DELAY: 500,            // 性能预设重试延迟
        SECURE_RETRIES: 5,                       // 安全预设重试次数
        PERFORMANCE_RETRIES: 2                   // 性能预设重试次数
    },
    
    // HTTP响应码
    HTTP_CODES: {
        SUCCESS: 1,                              // 成功响应码
        ERROR: 0                                 // 错误响应码
    }
};

const UPLOADER_EVENTS = {
    INIT: 'uploader:init', // 上传器初始化完成时触发
    FILE_ADDED: 'uploader:fileAdded', // 单个文件被添加时触发（主要用于自定义UI模式）
    FILES_ADDED: 'uploader:filesAdded', // 一批文件被处理并添加到列表后触发
    BEFORE_BATCH_UPLOAD: 'uploader:beforeBatchUpload', // 在手动批量上传开始前触发，可通过 e.preventDefault() 阻止
    BATCH_UPLOAD_START: 'uploader:batchUploadStart', // 批量上传开始时触发
    BATCH_UPLOAD_CANCELLED: 'uploader:batchUploadCancelled', // 当批量上传被 `BEFORE_BATCH_UPLOAD` 事件阻止时触发
    BEFORE_UPLOAD: 'uploader:beforeUpload', // 在单个文件哈希计算之前触发，可通过 e.preventDefault() 阻止
    PREPARE_UPLOAD: 'uploader:prepareUpload', // 在秒传检查失败后、实际上传开始前触发，可通过 e.preventDefault() 阻止
    UPLOAD_START: 'uploader:uploadStart', // 单个文件的实际上传请求开始时触发
    UPLOAD_PROGRESS: 'uploader:uploadProgress', // 文件上传过程中，进度更新时触发
    POST_PROCESS: 'uploader:postProcess', // HTTP请求成功后，在驱动的 `success` 钩子执行前触发的内部事件
    UPLOAD_SUCCESS: 'uploader:uploadSuccess', // 单个文件成功上传并被驱动处理后触发
    UPLOAD_ERROR: 'uploader:uploadError', // 文件上传过程中发生错误时触发
    UPLOAD_CANCELLED: 'uploader:uploadCancelled', // 文件上传被取消时触发
    STATS_UPDATED: 'uploader:statsUpdated', // 上传统计信息更新时触发
    FILE_REMOVED: 'uploader:fileRemoved', // 文件从列表中被移除时触发
    ALL_PAUSED: 'uploader:allPaused', // 所有上传任务被暂停时触发
    ALL_RESUMED: 'uploader:allResumed', // 所有被暂停的上传任务被恢复时触发
    HEALTH_STATUS: 'uploader:healthStatus', // 当健康状态检查执行后触发
    MAINTENANCE_PERFORMED: 'uploader:maintenancePerformed', // 当维护任务执行完成后触发
    DESTROY: 'uploader:destroy' // 上传器实例被销毁时触发
};

/**
 * 文件安全检查器
 * 专门处理文件安全相关的验证逻辑，包括文件头检查、危险文件检测等
 * @class
 * @private
 */
class SecurityChecker {
    constructor(options) {
        this.options = options;
    }

    /**
     * 检查文件名是否合法
     * @param {string} fileName - 文件名
     * @returns {boolean} 是否合法
     */
    isValidFileName(fileName) {
        if (!fileName || typeof fileName !== 'string') {
            return false;
        }

        // 基本长度检查
        if (fileName.length === 0 || fileName.length > UPLOADER_CONSTANTS.FILE_CONSTRAINTS.MAX_NAME_LENGTH) {
            return false;
        }

        // 检查危险字符（扩展版本）
        const dangerousChars = /[<>:"/\\|?*\x00-\x1f\x7f-\x9f]/;
        if (dangerousChars.test(fileName)) {
            return false;
        }

        // 强化的路径遍历攻击检查
        if (this._containsPathTraversal(fileName)) {
            return false;
        }

        // 检查Windows保留文件名
        if (this._isWindowsReservedName(fileName)) {
            return false;
        }

        // 检查文件名是否以点或空格开头/结尾
        if (fileName.startsWith('.') || fileName.startsWith(' ') || 
            fileName.endsWith('.') || fileName.endsWith(' ')) {
            return false;
        }

        // 检查Unicode规范化攻击
        return !this._containsSuspiciousUnicode(fileName);
    }

    /**
     * 检查路径遍历攻击（增强版）
     * @param {string} fileName - 文件名
     * @returns {boolean} 是否包含路径遍历
     * @private
     */
    _containsPathTraversal(fileName) {
        // 标准化文件名以检测各种编码形式的路径遍历
        const normalized = decodeURIComponent(fileName).toLowerCase();
        
        // 检查各种路径遍历模式
        const pathTraversalPatterns = [
            /\.\./,                    // 标准 ..
            /%2e%2e/,                  // URL编码 ..
            /%252e%252e/,              // 双重URL编码 ..
            /\.%2e/,                   // 混合编码 .%2e
            /%2e\./,                   // 混合编码 %2e.
            /\.\\\./,                  // Windows路径 .\.
            /\.%5c\./,                 // URL编码反斜杠 .%5c.
            /\x2e\x2e/,                // 十六进制编码
            /\u002e\u002e/             // Unicode编码
        ];

        return pathTraversalPatterns.some(pattern => pattern.test(normalized));
    }

    /**
     * 检查Windows保留文件名
     * @param {string} fileName - 文件名
     * @returns {boolean} 是否为保留名
     * @private
     */
    _isWindowsReservedName(fileName) {
        const baseName = fileName.split('.')[0].toUpperCase();
        const reservedNames = [
            'CON', 'PRN', 'AUX', 'NUL',
            'COM1', 'COM2', 'COM3', 'COM4', 'COM5', 'COM6', 'COM7', 'COM8', 'COM9',
            'LPT1', 'LPT2', 'LPT3', 'LPT4', 'LPT5', 'LPT6', 'LPT7', 'LPT8', 'LPT9'
        ];
        return reservedNames.includes(baseName);
    }

    /**
     * 检查可疑的Unicode字符
     * @param {string} fileName - 文件名
     * @returns {boolean} 是否包含可疑Unicode
     * @private
     */
    _containsSuspiciousUnicode(fileName) {
        // 检查可能用于混淆的Unicode字符
        const suspiciousPatterns = [
            /[\u200B-\u200F\u202A-\u202E\u2060-\u206F]/,  // 零宽字符和方向控制字符
            /\uFEFF/,                                     // 字节顺序标记
            /[\u0001-\u001F\u007F-\u009F]/,                // 控制字符
            /[\uE000-\uF8FF]/,                             // 私用区域
            /[\uFFFE\uFFFF]/                               // 非字符
        ];

        return suspiciousPatterns.some(pattern => pattern.test(fileName));
    }
}

/**
 * 文件验证器
 * 负责处理文件类型验证、大小检查、类型匹配等验证逻辑
 * @class
 * @private
 */
class FileValidator {
    constructor(securityChecker) {
        this.securityChecker = securityChecker;
    }

    /**
     * 验证单个文件的合法性（大小、类型、文件名等）
     * @param {File} file - 待验证的文件对象
     * @param {Object} options - 验证配置选项
     * @returns {Promise<{valid: boolean, error?: string}>} 验证结果
     */
    async validateFile(file, options) {
        // 基础验证
        if (options.maxFileSize !== 0 && options.maxFileSize !== Infinity && file.size > options.maxFileSize) {
            return {
                valid: false,
                error: `文件大小超过限制 (${this._formatSize(options.maxFileSize)})`
            };
        }

        if (file.size === 0) {
            return {
                valid: false,
                error: '文件为空'
            };
        }

        if (!this.isValidFileType(file, options)) {
            return {
                valid: false,
                error: '文件类型不支持'
            };
        }

        // 文件名安全检查
        if (!this.securityChecker.isValidFileName(file.name)) {
            return {
                valid: false,
                error: '文件名包含非法字符'
            };
        }

        return { valid: true };
    }

    /**
     * 根据配置的 allowedTypes 检查文件类型是否合法（增强版）
     * @param {File} file - 文件对象
     * @param {Object} options - 配置选项
     * @returns {boolean} 文件类型是否有效
     */
    isValidFileType(file, options) {
        const allowedTypes = options.allowedTypes;
        if (allowedTypes.includes('*') || allowedTypes.includes('*/*')) {
            return true;
        }

        const fileName = file.name || '';
        const fileMimeType = file.type || '';
        const fileExtension = fileName.split('.').pop()?.toLowerCase() || '';

        // 首先检查基本的类型匹配
        const basicTypeCheck = allowedTypes.some(type => {
            const lowerType = type.toLowerCase();

            // 检查MIME类型 (e.g., 'image/jpeg', 'image/*')
            if (lowerType.includes('/')) {
                if (lowerType.endsWith('/*')) {
                    // 通配符MIME类型 (e.g., 'image/*')
                    const mainType = lowerType.split('/')[0];
                    return fileMimeType.startsWith(mainType);
                } else {
                    // 特定MIME类型 (e.g., 'image/jpeg')
                    return fileMimeType === lowerType;
                }
            }
            
            // 检查文件后缀 (e.g., 'png', 'jpeg')
            return fileExtension === lowerType;
        });

        if (!basicTypeCheck) {
            return false;
        }

        // 进行扩展名与MIME类型的交叉验证
        if (options.strictTypeValidation !== false) {
            return this._validateTypeCrossReference(fileExtension, fileMimeType);
        }

        return true;
    }

    /**
     * 验证文件扩展名与MIME类型是否匹配
     * @param {string} extension - 文件扩展名
     * @param {string} mimeType - MIME类型
     * @returns {boolean} 是否匹配
     * @private
     */
    _validateTypeCrossReference(extension, mimeType) {
        // 如果没有MIME类型或扩展名，跳过交叉验证
        if (!extension || !mimeType) {
            return true;
        }

        const extensionToMimeMap = this._getExtensionMimeMapping();
        const expectedMimeTypes = extensionToMimeMap[extension];

        if (expectedMimeTypes) {
            const isMatched = expectedMimeTypes.includes(mimeType);
            if (!isMatched) {
                console.debug(`类型不匹配: 扩展名.${extension}期望MIME类型${expectedMimeTypes.join('/')}, 但实际为${mimeType}`);
                return false;
            }
        }

        return true;
    }

    /**
     * 获取文件扩展名到MIME类型的映射
     * @returns {Object} 映射表
     * @private
     */
    _getExtensionMimeMapping() {
        return {
            // 图片格式
            'jpg': ['image/jpeg'],
            'jpeg': ['image/jpeg'],
            'png': ['image/png'],
            'gif': ['image/gif'],
            'webp': ['image/webp'],
            'bmp': ['image/bmp'],
            'tiff': ['image/tiff'],
            'tif': ['image/tiff'],
            'svg': ['image/svg+xml'],
            'ico': ['image/x-icon', 'image/vnd.microsoft.icon'],

            // 文档格式
            'pdf': ['application/pdf'],
            'doc': ['application/msword'],
            'docx': ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'xls': ['application/vnd.ms-excel'],
            'xlsx': ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            'ppt': ['application/vnd.ms-powerpoint'],
            'pptx': ['application/vnd.openxmlformats-officedocument.presentationml.presentation'],
            'rtf': ['application/rtf'],
            'txt': ['text/plain'],
            'csv': ['text/csv', 'application/csv'],

            // 压缩格式
            'zip': ['application/zip'],
            'rar': ['application/x-rar-compressed'],
            '7z': ['application/x-7z-compressed'],
            'tar': ['application/x-tar'],
            'gz': ['application/gzip'],

            // 音频格式
            'mp3': ['audio/mpeg', 'audio/mp3'],
            'wav': ['audio/wav', 'audio/wave'],
            'ogg': ['audio/ogg'],
            'flac': ['audio/flac'],
            'aac': ['audio/aac'],
            'm4a': ['audio/mp4'],

            // 视频格式
            'mp4': ['video/mp4'],
            'avi': ['video/x-msvideo'],
            'mov': ['video/quicktime'],
            'wmv': ['video/x-ms-wmv'],
            'flv': ['video/x-flv'],
            'webm': ['video/webm'],
            'mkv': ['video/x-matroska'],

            // Web格式
            'html': ['text/html'],
            'htm': ['text/html'],
            'xml': ['text/xml', 'application/xml'],
            'json': ['application/json'],
            'js': ['application/javascript', 'text/javascript'],
            'css': ['text/css'],

            // 字体格式
            'ttf': ['font/ttf'],
            'otf': ['font/otf'],
            'woff': ['font/woff'],
            'woff2': ['font/woff2'],
            'eot': ['application/vnd.ms-fontobject']
        };
    }

    /**
     * 格式化文件大小 (e.g., 1024 -> "1 KB")
     * @param {number} bytes - 字节数
     * @returns {string} 格式化后的大小字符串
     * @private
     */
    _formatSize(bytes) {
        if (bytes === 0) return '0 B';
        const k = UPLOADER_CONSTANTS.SIZE_UNITS.BYTES_PER_KB;
        const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
}

/**
 * 哈希计算器
 * 负责处理文件哈希计算，支持多种策略（快速、准确、混合模式）
 * @class
 * @private
 */
class HashCalculator {
    constructor(options) {
        this.options = options;
    }

    /**
     * 根据策略准备文件哈希
     * @param file - 文件对象
     * @param mode - 计算模式：'fast', 'accurate', 'hybrid'
     * @param updateStatus - 状态更新回调
     * @returns {Promise<{finalHash: null, preliminaryHash: null}>} 哈希结果
     */
    async prepareHashes(file, mode, updateStatus) {
        const statusMap = {
            fast: '计算采样哈希...',
            accurate: '计算完整哈希...',
            hybrid: '计算哈希...'
        };
        
        if (updateStatus) {
            updateStatus(statusMap[mode]);
        }
        
        const hashes = await this.calculateFileHashSafely(file, mode);
        
        // 在 'fast' 模式下，将采样哈希作为最终哈希
        if (mode === 'fast') {
            return {
                preliminaryHash: hashes.preliminaryHash,
                finalHash: hashes.preliminaryHash
            };
        }
        
        return hashes;
    }

    /**
     * 安全地计算文件哈希
     * @param file - 文件对象
     * @param mode - 计算模式
     * @returns {Promise<{finalHash: null, preliminaryHash: null}>} 哈希结果
     */
    async calculateFileHashSafely(file, mode) {
        try {
            const result = {
                preliminaryHash: null,
                finalHash: null
            };

            // 根据模式计算不同的哈希
            switch (mode) {
                case 'fast':
                    result.preliminaryHash = await this._calculateSampleHash(file);
                    break;
                case 'accurate':
                    result.finalHash = await this._calculateFullHash(file);
                    break;
                case 'hybrid':
                    result.preliminaryHash = await this._calculateSampleHash(file);
                    result.finalHash = await this._calculateFullHash(file);
                    break;
            }

            return result;
        } catch (error) {
            console.warn('哈希计算失败:', error);
            return {
                preliminaryHash: null,
                finalHash: null
            };
        }
    }

    /**
     * 计算采样哈希（快速模式）
     * @param {File} file - 文件对象
     * @returns {Promise<string>} 采样哈希值
     * @private
     */
    async _calculateSampleHash(file) {
        const sampleSize = Math.min(UPLOADER_CONSTANTS.PERFORMANCE_THRESHOLDS.HASH_SAMPLE_SIZE, file.size);
        const chunks = [];
        
        // 文件开头
        chunks.push(file.slice(0, sampleSize / 4));
        
        // 文件中间
        if (file.size > sampleSize / 2) {
            const midStart = Math.floor(file.size / 2) - sampleSize / 8;
            chunks.push(file.slice(midStart, midStart + sampleSize / 4));
        }
        
        // 文件末尾
        if (file.size > sampleSize / 4) {
            chunks.push(file.slice(-sampleSize / 4));
        }

        const sampleBlob = new Blob(chunks);
        return await this._calculateSHA1(sampleBlob);
    }

    /**
     * 计算完整文件哈希
     * @param {File} file - 文件对象
     * @returns {Promise<string>} 完整哈希值
     * @private
     */
    async _calculateFullHash(file) {
        return await this._calculateSHA1(file);
    }

    /**
     * 计算SHA1哈希
     * @param {Blob} blob - 要计算的数据
     * @returns {Promise<string>} SHA1哈希值
     * @private
     */
    async _calculateSHA1(blob) {
        const buffer = await blob.arrayBuffer();
        const hashBuffer = await crypto.subtle.digest('SHA-1', buffer);
        const hashArray = Array.from(new Uint8Array(hashBuffer));
        return hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
    }
}

/**
 * 分片上传器
 * 负责处理大文件分片上传逻辑，包括断点续传和并发控制
 * @class
 * @private
 */
class ChunkUploader {
    constructor(uploader) {
        this.uploader = uploader;
        this.options = uploader.options;
    }

    /**
     * 执行分片上传的完整流程
     * @param {Object} fileItem - 文件项
     * @param {AbortSignal} signal - 取消信号
     * @returns {Promise<Object>} 上传结果
     */
    async uploadChunked(fileItem, signal) {
        const file = fileItem.file;
        const chunkSize = this.options.chunkSize;
        fileItem.totalChunks = Math.ceil(file.size / chunkSize);
    
        // 获取已上传的分片（断点续传）
        fileItem.uploadedChunks = await this._getUploadedChunks(fileItem, signal);
    
        // 上传剩余分片
        await this._uploadRemainingChunks(fileItem, signal);
    
        // 合并分片
        return await this._mergeChunks(fileItem, signal);
    }

    /**
     * 获取已上传的分片列表
     * @param {Object} fileItem - 文件项
     * @param {AbortSignal} signal - 取消信号
     * @returns {Promise<Array>} 已上传分片列表
     * @private
     */
    async _getUploadedChunks(fileItem, signal) {
        try {
            const queryParams = new URLSearchParams();
            if (fileItem.hash) {
                queryParams.set('hash', fileItem.hash);
            }
            queryParams.set('filename', fileItem.file.name);
            queryParams.set('total_chunks', fileItem.totalChunks.toString());

            const response = await fetch(`${this.options.endpoints.chunk}?${queryParams}`, {
                method: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                signal: signal
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();
            return data.code === UPLOADER_CONSTANTS.HTTP_CODES.SUCCESS ? (data.uploaded_chunks || []) : [];
        } catch (error) {
            if (error.name !== 'AbortError') {
                console.warn('获取已上传分片列表失败:', error);
            }
            return [];
        }
    }

    /**
     * 上传剩余的分片
     * @param {Object} fileItem - 文件项
     * @param {AbortSignal} signal - 取消信号
     * @returns {Promise<void>}
     * @private
     */
    async _uploadRemainingChunks(fileItem, signal) {
        const totalChunks = fileItem.totalChunks;
        const uploadedChunks = fileItem.uploadedChunks || [];
        
        const chunks = [];
        for (let i = 0; i < totalChunks; i++) {
            if (!uploadedChunks.includes(i)) {
                chunks.push(i);
            }
        }

        fileItem.remainingChunks = chunks.length;
        
        // 并发上传分片
        const concurrency = this.options.chunkConcurrency || 3;
        await this._uploadChunksWithConcurrency(fileItem, chunks, signal, concurrency);
    }

    /**
     * 并发上传分片
     * @param {Object} fileItem - 文件项
     * @param {Array} chunks - 待上传分片索引数组
     * @param {AbortSignal} signal - 取消信号
     * @param {number} concurrency - 并发数
     * @returns {Promise<void>}
     * @private
     */
    async _uploadChunksWithConcurrency(fileItem, chunks, signal, concurrency) {
        let index = 0;

        const uploadNext = async () => {
            while (index < chunks.length) {
                const chunkIndex = chunks[index++];
                await this._uploadSingleChunk(fileItem, chunkIndex, signal);
                fileItem.remainingChunks--;
                
                // 更新进度
                const progress = Math.round(((fileItem.totalChunks - fileItem.remainingChunks) / fileItem.totalChunks) * 100);
                this.uploader.updateProgress(fileItem, { percent: progress });
            }
        };

        // 创建并发工作器
        const workers = Array.from({ length: Math.min(concurrency, chunks.length) }, () => uploadNext());
        await Promise.all(workers);
    }

    /**
     * 上传单个分片
     * @param {Object} fileItem - 文件项
     * @param {number} chunkIndex - 分片索引
     * @param {AbortSignal} signal - 取消信号
     * @returns {Promise<void>}
     * @private
     */
    async _uploadSingleChunk(fileItem, chunkIndex, signal) {
        const file = fileItem.file;
        const chunkSize = this.options.chunkSize;
        const start = chunkIndex * chunkSize;
        const end = Math.min(start + chunkSize, file.size);
        const chunk = file.slice(start, end);

        const formData = new FormData();
        formData.append('file', chunk);
        formData.append('chunk_index', chunkIndex.toString());
        formData.append('total_chunks', fileItem.totalChunks.toString());
        formData.append('filename', file.name);
        
        if (fileItem.hash) {
            formData.append('hash', fileItem.hash);
        }

        // 添加额外数据
        const extraDataParams = await this.uploader.processExtraData(file, fileItem);
        this.uploader.safeAppendToFormData(formData, extraDataParams, 'extraData');

        const response = await fetch(this.options.endpoints.chunk, {
            method: 'POST',
            body: formData,
            signal: signal,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });

        if (!response.ok) {
            throw new Error(`分片 ${chunkIndex} 上传失败: ${response.status} ${response.statusText}`);
        }

        const result = await response.json();
        if (result.code !== UPLOADER_CONSTANTS.HTTP_CODES.SUCCESS) {
            throw new Error(result.msg || `分片 ${chunkIndex} 上传失败`);
        }
    }

    /**
     * 合并分片
     * @param {Object} fileItem - 文件项
     * @param {AbortSignal} signal - 取消信号
     * @returns {Promise<Object>} 合并结果
     * @private
     */
    async _mergeChunks(fileItem, signal) {
        const mergeData = {
            filename: fileItem.file.name,
            total_chunks: fileItem.totalChunks
        };
        
        if (fileItem.hash) {
            mergeData.hash = fileItem.hash;
        }

        // 添加额外数据
        const extraDataParams = await this.uploader.processExtraData(fileItem.file, fileItem);
        Object.assign(mergeData, extraDataParams);

        const response = await fetch(this.options.endpoints.merge, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(mergeData),
            signal: signal
        });

        if (!response.ok) {
            throw new Error(`合并失败: ${response.status} ${response.statusText}`);
        }

        const result = await response.json();
        if (result.code !== UPLOADER_CONSTANTS.HTTP_CODES.SUCCESS) {
            throw new Error(result.msg || '合并失败');
        }

        return result;
    }
}

/**
 * 上传管理器
 * 负责协调整个上传流程，包括队列管理、上传策略选择和流程控制
 * @class
 * @private
 */
class UploadManager {
    constructor(uploader, hashCalculator, chunkUploader) {
        this.uploader = uploader;
        this.options = uploader.options;
        this.hashCalculator = hashCalculator;
        this.chunkUploader = chunkUploader;
    }

    /**
     * 处理上传队列
     * @returns {Promise<void>}
     */
    async processQueue() {
        // 使用锁机制避免竞态条件
        if (this.uploader.processingQueue || this.uploader.destroyed) return;
        this.uploader.processingQueue = true;

        try {
            while (this.uploader.uploadQueue.length > 0 && 
                   (this.options.concurrent === 0 || this.uploader.activeUploads < this.options.concurrent) && 
                   !this.uploader.destroyed) {
                const fileItem = this.uploader.uploadQueue.shift();
                if (fileItem && fileItem.status !== 'cancelled') {
                    this.uploader.activeUploads++;

                    // 使用异步处理避免递归调用栈溢出
                    this.uploadFile(fileItem)
                        .finally(() => {
                            this.uploader.activeUploads--;
                            // 延迟处理下一个，避免栈溢出
                            setTimeout(() => {
                                this.processQueue().catch(error => {
                                    console.error('队列处理失败:', error);
                                });
                            }, 0);
                        });
                }
            }
        } finally {
            this.uploader.processingQueue = false;
        }
    }

    /**
     * 核心上传方法，管理单个文件的完整上传周期
     * @param {Object} fileItem - 文件项
     * @returns {Promise<void>}
     */
    async uploadFile(fileItem) {
        if (this.uploader.destroyed || fileItem.status === 'cancelled') {
            return;
        }

        const abortController = new AbortController();
        this.uploader.abortControllers.set(fileItem.id, abortController);

        try {
            // 预上传验证和准备
            await this._preUploadValidation(fileItem);
            
            // 哈希计算（如果需要）
            await this._calculateHashIfNeeded(fileItem);

            // 秒传检查
            const skipUpload = await this._checkQuickUpload(fileItem, abortController.signal);
            if (skipUpload) {
                return;
            }

            // 执行实际上传
            await this._performActualUpload(fileItem, abortController.signal);

        } catch (error) {
            await this._handleUploadError(fileItem, error);
        } finally {
            await this._postUploadCleanup(fileItem);
        }
    }

    /**
     * 上传前验证
     * @param {Object} fileItem - 文件项
     * @returns {Promise<void>}
     * @private
     */
    async _preUploadValidation(fileItem) {
        // 轻量级验证事件
        if (this.uploader.trigger(UPLOADER_EVENTS.BEFORE_UPLOAD, fileItem, fileItem.file).defaultPrevented) {
            throw new Error('上传已被 beforeUpload 事件阻止');
        }

        this.uploader.trigger(UPLOADER_EVENTS.UPLOAD_START, fileItem, fileItem.file);
        fileItem.status = 'uploading';
        fileItem.startTime = Date.now();
        this.uploader.updateFileStatus(fileItem, '准备中...');
    }

    /**
     * 根据配置计算文件哈希
     * @param {Object} fileItem - 文件项
     * @returns {Promise<void>}
     * @private
     */
    async _calculateHashIfNeeded(fileItem) {
        if (!this.options.quickUpload) {
            return;
        }

        const strategy = this.options.checkStrategy;
        const hashes = await this.hashCalculator.prepareHashes(
            fileItem.file, 
            strategy, 
            (status) => this.uploader.updateFileStatus(fileItem, status)
        );
        
        fileItem.preliminaryHash = hashes.preliminaryHash;
        fileItem.hash = hashes.finalHash;
    }

    /**
     * 检查秒传可能性
     * @param {Object} fileItem - 文件项
     * @param {AbortSignal} signal - 取消信号
     * @returns {Promise<boolean>} 是否跳过上传
     * @private
     */
    async _checkQuickUpload(fileItem, signal) {
        if (!this.options.quickUpload) {
            return false;
        }

        let checkResult = false;
        const strategy = this.options.checkStrategy;

        switch (strategy) {
            case 'fast':
            case 'accurate':
                checkResult = await this._checkFileExists(fileItem, signal);
                break;
            case 'hybrid':
                // 混合模式：先检查采样哈希，再检查完整哈希
                if (fileItem.preliminaryHash && await this._checkFileExists(fileItem, signal, true)) {
                    checkResult = true;
                } else if (fileItem.hash) {
                    checkResult = await this._checkFileExists(fileItem, signal, false);
                }
                break;
        }

        return checkResult;
    }

    /**
     * 检查文件是否已在服务器上存在（用于秒传）
     * @param {Object} fileItem - 文件项
     * @param {AbortSignal} signal - 取消信号
     * @param {boolean} usePreliminary - 是否使用采样哈希
     * @returns {Promise<boolean>} 文件是否存在
     * @private
     */
    async _checkFileExists(fileItem, signal, usePreliminary = false) {
        try {
            const hashToCheck = usePreliminary ? fileItem.preliminaryHash : fileItem.hash;

            if (!hashToCheck) {
                return false;
            }
            
            this.uploader.updateFileStatus(fileItem, usePreliminary ? '检查采样哈希...' : '检查完整哈希...');
            const response = await fetch(`${this.options.endpoints.check}?hash=${encodeURIComponent(hashToCheck)}`, {
                method: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                signal: signal
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();

            if (data.code === UPLOADER_CONSTANTS.HTTP_CODES.SUCCESS) {
                // 文件已存在 (秒传成功)，直接调用最终的成功处理器
                await this.uploader.handleUploadSuccess(fileItem, data);
                return true;
            }
        } catch (error) {
            if (error.name !== 'AbortError') {
                console.warn('检查文件是否存在失败:', error);
            }
        }

        return false;
    }

    /**
     * 执行实际上传
     * @param {Object} fileItem - 文件项
     * @param {AbortSignal} signal - 取消信号
     * @returns {Promise<void>}
     * @private
     */
    async _performActualUpload(fileItem, signal) {
        // 准备上传事件
        await this.uploader.triggerAsync(UPLOADER_EVENTS.PREPARE_UPLOAD, fileItem, fileItem.file);

        // 选择上传方式
        this.uploader.updateFileStatus(fileItem, '上传中...');
        let result;
        
        if (this.options.chunked && fileItem.file.size > this.options.chunkSize) {
            result = await this.chunkUploader.uploadChunked(fileItem, signal);
        } else {
            result = await this.uploadDirect(fileItem, signal);
        }

        await this.uploader._onHttpSuccess(fileItem, result);
    }

    /**
     * 执行直接上传
     * @param {Object} fileItem - 文件项
     * @param {AbortSignal} signal - 取消信号
     * @returns {Promise<Object>} 上传结果
     */
    async uploadDirect(fileItem, signal) {
        const formData = new FormData();
        const extraDataParams = await this.uploader.processExtraData(fileItem.file, fileItem);

        // 将哈希值添加到表单数据中（仅在启用秒传检查时）
        if (this.options.quickUpload) {
            if (fileItem.hash) {
                formData.append('hash', fileItem.hash);
            }
            if (this.options.checkStrategy !== 'accurate' && fileItem.preliminaryHash) {
                formData.append('preliminary_hash', fileItem.preliminaryHash);
            }
        }

        // 先添加额外参数
        this.uploader.safeAppendToFormData(formData, extraDataParams, 'extraData');
        // 最后添加文件
        formData.append('file', fileItem.file);

        const response = await this.uploader.fetchWithProgress(
            this.options.endpoints.upload,
            {
                method: 'POST',
                body: formData,
                signal: signal
            },
            (progress) => this.uploader.updateProgress(fileItem, progress)
        );

        let rawResult;
        const contentType = response.getResponseHeader("content-type");
        if (contentType && contentType.includes("application/json")) {
            rawResult = await response.json();
        } else if (response.responseText) {
            try {
                rawResult = JSON.parse(response.responseText);
            } catch (parseError) {
                rawResult = response.responseText;
            }
        } else {
            // 对于无内容响应（如阿里云OSS返回的204 No Content），创建一个占位符
            rawResult = {};
        }

        let result = rawResult;
        // 检查并调用 responseHandler
        if (typeof this.options.responseHandler === 'function') {
            try {
                const handlerResult = this.options.responseHandler(rawResult, response);
                result = handlerResult instanceof Promise ? await handlerResult : handlerResult;
            } catch (handlerError) {
                console.error('responseHandler 执行失败:', handlerError);
                throw handlerError;
            }
        }

        const isSuccess = result && result.code === UPLOADER_CONSTANTS.HTTP_CODES.SUCCESS;
        if (!isSuccess) {
            const errorMessage =
                result?.data?.error?.[0]?.msg ||
                result?.msg ||
                (response && !response.ok ? `上传失败: ${response.status} ${response.statusText}` : '上传失败');
            throw new Error(errorMessage);
        }

        return result;
    }

    /**
     * 处理上传错误
     * @param {Object} fileItem - 文件项
     * @param {Error} error - 错误对象
     * @returns {Promise<void>}
     * @private
     */
    async _handleUploadError(fileItem, error) {
        if (fileItem && fileItem.__driverHandledError) {
            delete fileItem.__driverHandledError;
            return;
        }
        if (error.name === 'AbortError' || error.message.includes('上传已被') || error.message.includes('已取消')) {
            fileItem.status = 'cancelled';
            this.uploader.updateFileStatus(fileItem, '已取消');
            this.uploader.trigger(UPLOADER_EVENTS.UPLOAD_CANCELLED, fileItem);
        } else {
            await this.uploader.handleUploadError(fileItem, error);
        }
    }

    /**
     * 上传后清理
     * @param {Object} fileItem - 文件项
     * @returns {Promise<void>}
     * @private
     */
    async _postUploadCleanup(fileItem) {
        this.uploader.abortControllers.delete(fileItem.id);
    }
}

/**
 * 配置管理内部类
 * 负责处理预设配置、配置继承、参数验证等配置相关逻辑
 * @class
 * @private
 */
class ConfigurationManager {
    constructor() {
        this.baseConfigs = {};
        this.presets = this._getPresets();
        this.validationRules = this._getValidationRules();
    }

    /**
     * 处理预设配置，支持继承和组合
     * @param {Object} options - 用户配置选项
     * @returns {Object} 处理后的配置对象
     */
    processPresetConfig(options) {
        // 如果没有预设，直接返回原配置
        if (!options.preset) {
            return options;
        }

        // 支持多个预设配置组合：preset: ['basic', 'secure'] 或 preset: 'basic'
        const presetNames = Array.isArray(options.preset) ? options.preset : [options.preset];
        
        // 使用配置管理器处理预设配置
        const processedOptions = { preset: presetNames };
        const resolvedConfig = this._configManager.processPresetConfig(processedOptions);

        // 合并用户配置（优先级最高）
        const { preset: _, ...userOptions } = options;
        const mergedConfig = { ...resolvedConfig, ...userOptions };

        // 解析字符串格式的文件大小配置项
        const sizeConfigKeys = ['maxFileSize', 'chunkSize', 'largeFileThreshold', 'previewSizeLimit'];
        sizeConfigKeys.forEach(key => {
            if (mergedConfig[key] !== undefined) {
                try {
                    mergedConfig[key] = this._configManager.parseFileSize(mergedConfig[key]);
                } catch (error) {
                    console.warn(`Failed to parse ${key}: ${error.message}`);
                }
            }
        });

        return mergedConfig;
    }


    /**
     * 解析文件大小字符串
     * @param {string|number} size - 文件大小，可以是数字（字节）或（支持单位：B, KB, MB, GB）
     * @returns {number} 解析后的字节数
     * @throws {Error} 当格式无效时抛出错误
     */
    parseFileSize(size) {
        // 如果已经是数字（包括0和Infinity），直接返回
        if (typeof size === 'number') {
            return size;
        }

        // 如果不是字符串，返回原值
        if (typeof size !== 'string') {
            return size;
        }

        // 去除首尾空格并转为小写
        const sizeStr = size.trim().toLowerCase();

        // 如果是空字符串，返回0
        if (sizeStr === '') {
            return 0;
        }

        // 如果是纯数字字符串，直接解析
        if (/^\d+$/.test(sizeStr)) {
            return parseInt(sizeStr, 10);
        }

        // 解析带单位的字符串
        const match = sizeStr.match(/^(\d+(?:\.\d+)?)\s*([a-z]+)$/);
        if (!match) {
            throw new Error(`Invalid file size format: "${size}". Expected formats: "10MB", "1.5GB", "500KB", etc.`);
        }

        const value = parseFloat(match[1]);
        const unit = match[2];

        // 验证数值有效性
        if (isNaN(value) || value < 0) {
            throw new Error(`Invalid file size value: "${match[1]}". Size must be a positive number.`);
        }

        // 单位转换表
        const unitMultipliers = {
            'b': 1, 'byte': 1, 'bytes': 1,
            'k': UPLOADER_CONSTANTS.SIZE_UNITS.BYTES_PER_KB, 'kb': UPLOADER_CONSTANTS.SIZE_UNITS.BYTES_PER_KB, 'kib': UPLOADER_CONSTANTS.SIZE_UNITS.BYTES_PER_KB, 'kilobyte': UPLOADER_CONSTANTS.SIZE_UNITS.BYTES_PER_KB, 'kilobytes': UPLOADER_CONSTANTS.SIZE_UNITS.BYTES_PER_KB,
            'm': UPLOADER_CONSTANTS.SIZE_UNITS.BYTES_PER_MB, 'mb': UPLOADER_CONSTANTS.SIZE_UNITS.BYTES_PER_MB, 'mib': UPLOADER_CONSTANTS.SIZE_UNITS.BYTES_PER_MB, 'megabyte': UPLOADER_CONSTANTS.SIZE_UNITS.BYTES_PER_MB, 'megabytes': UPLOADER_CONSTANTS.SIZE_UNITS.BYTES_PER_MB,
            'g': UPLOADER_CONSTANTS.SIZE_UNITS.BYTES_PER_GB, 'gb': UPLOADER_CONSTANTS.SIZE_UNITS.BYTES_PER_GB, 'gib': UPLOADER_CONSTANTS.SIZE_UNITS.BYTES_PER_GB, 'gigabyte': UPLOADER_CONSTANTS.SIZE_UNITS.BYTES_PER_GB, 'gigabytes': UPLOADER_CONSTANTS.SIZE_UNITS.BYTES_PER_GB,
            't': UPLOADER_CONSTANTS.SIZE_UNITS.BYTES_PER_TB, 'tb': UPLOADER_CONSTANTS.SIZE_UNITS.BYTES_PER_TB, 'tib': UPLOADER_CONSTANTS.SIZE_UNITS.BYTES_PER_TB, 'terabyte': UPLOADER_CONSTANTS.SIZE_UNITS.BYTES_PER_TB, 'terabytes': UPLOADER_CONSTANTS.SIZE_UNITS.BYTES_PER_TB
        };

        const multiplier = unitMultipliers[unit];
        if (multiplier === undefined) {
            const supportedUnits = Object.keys(unitMultipliers).filter(u => u.length <= 3).join(', ');
            throw new Error(`Unsupported file size unit: "${unit}". Supported units: ${supportedUnits}`);
        }

        const result = value * multiplier;

        // 检查结果是否在安全范围内
        if (!Number.isFinite(result) || result > Number.MAX_SAFE_INTEGER) {
            throw new Error(`File size too large: "${size}". Maximum supported size is ${Number.MAX_SAFE_INTEGER} bytes.`);
        }

        return Math.floor(result);
    }

    /**
     * 验证配置选项
     * @param {Object} options - 配置选项
     * @throws {Error} 当配置无效时抛出错误
     */
    validateOptions(options) {
        const rules = this.validationRules;

        // 支持 allowedTypes 传入以逗号分隔的字符串，统一拆分成数组
        if (typeof options.allowedTypes === 'string') {
            options.allowedTypes = options.allowedTypes
                .split(',')
                .map(item => item.trim())
                .filter(item => item.length > 0);
        }

        // 按类型分组验证配置
        Object.keys(rules).forEach(key => {
            const rule = rules[key];
            const value = options[key];
            this._validateConfigItem(key, value, rule);
        });

        // 特殊验证：allowedTypes处理
        if (options.allowedTypes) {
            options.allowedTypes = this._normalizeAllowedTypes(options.allowedTypes);
        }

        // 验证配置之间的关系
        this._validateConfigRelationships(options);
    }

    /**
     * 获取默认配置选项
     * @returns {Object} 默认配置对象
     */
    getDefaultOptions() {
        return {
            // 基础配置
            maxFileSize: '10MB',
            allowedTypes: ['image/*'],
            multiple: false,
            autoUpload: true,

            // 分片配置
            chunked: false,
            chunkSize: '1MB',
            resumable: true,

            // 并发配置
            concurrent: UPLOADER_CONSTANTS.PERFORMANCE_THRESHOLDS.DEFAULT_CONCURRENT,
            retries: UPLOADER_CONSTANTS.PERFORMANCE_THRESHOLDS.DEFAULT_RETRIES,
            retryDelay: UPLOADER_CONSTANTS.PERFORMANCE_THRESHOLDS.DEFAULT_RETRY_DELAY,

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

            // 自定义UI事件配置
            clickToSelect: true,
            keyboardSupport: true,
            inputOnly: false,

            // 自定义参数配置
            extraData: null,
            abortOnExtraDataError: true,

            // 秒传检查策略
            quickUpload: true,
            checkStrategy: 'accurate',

            // 预览和内存管理配置
            clearPreviewOnSuccess: false,
            clearPreviewOnCancel: false,
            previewCleanupDelay: UPLOADER_CONSTANTS.PERFORMANCE_THRESHOLDS.PREVIEW_CLEANUP_DELAY,
            enablePreviewPersistence: true
        };
    }


    /**
     * 获取预设配置
     * @returns {Object} 预设配置对象
     * @private
     */
    _getPresets() {
        return {
            'basic': this._getBasicPreset(),
            'secure': this._getSecurePreset(),
            'performance': this._getPerformancePreset(),
            'enterprise': this._getEnterprisePreset(),
            'debug': this._getDebugPreset(),
            'custom': this._getCustomPreset()
        };
    }

    /**
     * 基础配置预设 - 适合90%的使用场景
     * @returns {Object} 基础预设配置
     * @private
     */
    _getBasicPreset() {
        return {
            multiple: true,
            autoUpload: true,
            dragAndDrop: true,
            showPreview: true,
            maxFileSize: '10MB',
            maxFiles: UPLOADER_CONSTANTS.DEFAULT_VALUES.MAX_FILES_BASIC,
            allowedTypes: ['*'],
            chunked: true,
            chunkSize: '2MB',
            retries: UPLOADER_CONSTANTS.PERFORMANCE_THRESHOLDS.DEFAULT_RETRIES,
            retryDelay: UPLOADER_CONSTANTS.PERFORMANCE_THRESHOLDS.DEFAULT_RETRY_DELAY,
            concurrent: UPLOADER_CONSTANTS.PERFORMANCE_THRESHOLDS.BASIC_CONCURRENT
        };
    }

    /**
     * 安全优先预设 - 严格的文件验证和安全检查
     * @returns {Object} 安全预设配置
     * @private
     */
    _getSecurePreset() {
        return {
            multiple: false,
            autoUpload: false,
            dragAndDrop: false,
            showPreview: true,
            maxFileSize: '5MB',
            maxFiles: UPLOADER_CONSTANTS.DEFAULT_VALUES.MAX_FILES_SECURE,
            allowedTypes: ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'],
            chunked: true,
            chunkSize: '1MB',
            retries: UPLOADER_CONSTANTS.DEFAULT_VALUES.SECURE_RETRIES,
            retryDelay: UPLOADER_CONSTANTS.DEFAULT_VALUES.SECURE_RETRY_DELAY,
            concurrent: UPLOADER_CONSTANTS.PERFORMANCE_THRESHOLDS.SECURE_CONCURRENT,
            validateContent: true,
            dangerousFileDetection: true,
            xssProtection: true,
            safeModeEnabled: true
        };
    }

    /**
     * 性能优先预设 - 大文件、高并发、快速上传
     * @returns {Object} 性能预设配置
     * @private
     */
    _getPerformancePreset() {
        return {
            multiple: true,
            autoUpload: true,
            dragAndDrop: true,
            showPreview: false,
            maxFileSize: 0, // 无限制
            maxFiles: UPLOADER_CONSTANTS.DEFAULT_VALUES.MAX_FILES_PERFORMANCE,
            allowedTypes: ['*'],
            chunked: true,
            chunkSize: '5MB',
            retries: UPLOADER_CONSTANTS.DEFAULT_VALUES.PERFORMANCE_RETRIES,
            retryDelay: UPLOADER_CONSTANTS.DEFAULT_VALUES.PERFORMANCE_RETRY_DELAY,
            concurrent: UPLOADER_CONSTANTS.PERFORMANCE_THRESHOLDS.PERFORMANCE_CONCURRENT,
            memoryOptimized: true,
            quickUpload: true,
            checkStrategy: 'fast'
        };
    }

    /**
     * 企业级预设 - 安全与性能的平衡配置
     * @returns {Object} 企业级预设配置
     * @private
     */
    _getEnterprisePreset() {
        return {
            multiple: true,
            autoUpload: true,
            dragAndDrop: true,
            showPreview: true,
            maxFileSize: '100MB',
            maxFiles: UPLOADER_CONSTANTS.DEFAULT_VALUES.MAX_FILES_ENTERPRISE,
            allowedTypes: ['image/*', 'application/pdf', 'application/msword', 'text/*', 'application/zip'],
            chunked: true,
            chunkSize: '5MB',
            retries: 5,
            retryDelay: 1500,
            concurrent: UPLOADER_CONSTANTS.PERFORMANCE_THRESHOLDS.BASIC_CONCURRENT,
            validateContent: true,
            dangerousFileDetection: true,
            xssProtection: true,
            memoryOptimized: true,
            quickUpload: true,
            checkStrategy: 'hybrid'
        };
    }

    /**
     * 调试模式预设 - 开发和调试时使用
     * @returns {Object} 调试预设配置
     * @private
     */
    _getDebugPreset() {
        return {
            multiple: true,
            autoUpload: false,
            dragAndDrop: true,
            showPreview: true,
            maxFileSize: '50MB',
            maxFiles: 5,
            allowedTypes: ['*'],
            chunked: true,
            chunkSize: '1MB',
            retries: 1,
            retryDelay: 0,
            concurrent: 1,
            debug: true,
            validateContent: false,
            quickUpload: false
        };
    }

    /**
     * 自定义基础预设 - 提供自定义配置的起点
     * @returns {Object} 自定义预设配置
     * @private
     */
    _getCustomPreset() {
        return {
            multiple: true,
            autoUpload: true,
            dragAndDrop: true,
            showPreview: true,
            maxFileSize: '20MB',
            maxFiles: 0, // 无限制
            allowedTypes: ['*'],
            chunked: true,
            chunkSize: '2MB',
            retries: UPLOADER_CONSTANTS.PERFORMANCE_THRESHOLDS.DEFAULT_RETRIES,
            retryDelay: UPLOADER_CONSTANTS.PERFORMANCE_THRESHOLDS.DEFAULT_RETRY_DELAY,
            concurrent: UPLOADER_CONSTANTS.PERFORMANCE_THRESHOLDS.BASIC_CONCURRENT
        };
    }

    /**
     * 获取配置验证规则
     * @returns {Object} 验证规则对象
     * @private
     */
    _getValidationRules() {
        return {
            // 文件大小相关配置
            maxFileSize: { type: 'size', required: true, min: 0, allowInfinity: true },
            chunkSize: { type: 'size', required: false, min: 1024, default: 1024 * 1024 },
            largeFileThreshold: { type: 'size', required: false, min: 0, allowInfinity: true },
            previewSizeLimit: { type: 'size', required: false, min: 0, allowInfinity: true },

            // 数值配置
            maxFiles: { type: 'integer', required: false, min: 0, default: 0 },
            minFiles: { type: 'integer', required: false, min: 0, default: 0 },
            concurrent: { type: 'integer', required: false, min: 0, default: 3 },
            retries: { type: 'integer', required: false, min: 0, default: 3 },
            retryDelay: { type: 'integer', required: false, min: 0, default: 1000 },

            // 布尔配置
            multiple: { type: 'boolean', required: false, default: true },
            autoUpload: { type: 'boolean', required: false, default: true },
            chunked: { type: 'boolean', required: false, default: false },
            resumable: { type: 'boolean', required: false, default: true },
            showPreview: { type: 'boolean', required: false, default: true },
            dragAndDrop: { type: 'boolean', required: false, default: true },
            clickToSelect: { type: 'boolean', required: false, default: true },
            keyboardSupport: { type: 'boolean', required: false, default: true },
            inputOnly: { type: 'boolean', required: false, default: false },
            abortOnExtraDataError: { type: 'boolean', required: false, default: true },
            quickUpload: { type: 'boolean', required: false, default: true },
            clearPreviewOnSuccess: { type: 'boolean', required: false, default: false },
            clearPreviewOnCancel: { type: 'boolean', required: false, default: false },
            enablePreviewPersistence: { type: 'boolean', required: false, default: true },
            validateContent: { type: 'boolean', required: false, default: false },
            dangerousFileDetection: { type: 'boolean', required: false, default: false },
            xssProtection: { type: 'boolean', required: false, default: false },
            safeModeEnabled: { type: 'boolean', required: false, default: false },
            memoryOptimized: { type: 'boolean', required: false, default: false },

            // 枚举配置
            checkStrategy: { type: 'enum', required: false, enum: ['fast', 'accurate', 'hybrid'], default: 'accurate' },

            // 字符串配置
            preset: { type: 'string', required: false },

            // 数组配置
            allowedTypes: { type: 'array', required: true, elementType: 'string', minLength: 1 },

            // 对象配置
            endpoints: { type: 'object', required: true, schema: {
                upload: { type: 'string', required: true },
                check: { type: 'string', required: false },
                save: { type: 'string', required: false },
                chunk: { type: 'string', required: false },
                merge: { type: 'string', required: false },
                getChunks: { type: 'string', required: false }
            }}
        };
    }

    /**
     * 验证单个配置项
     * @param {string} key - 配置项键名
     * @param {any} value - 配置项值
     * @param {Object} rule - 验证规则
     * @private
     */
    _validateConfigItem(key, value, rule) {
        // 处理默认值
        if (value === undefined && rule.default !== undefined) {
            value = rule.default;
        }

        // 检查必需项
        if (rule.required && value === undefined) {
            throw new Error(`Configuration validation failed: '${key}' is required.`);
        }

        // 如果值为undefined且不是必需项，直接返回
        if (value === undefined) {
            return value;
        }

        // 根据类型进行验证
        switch (rule.type) {
            case 'size':
                return this._validateSizeConfig(key, value, rule);
            case 'integer':
                return this._validateIntegerConfig(key, value, rule);
            case 'boolean':
                return this._validateBooleanConfig(key, value);
            case 'enum':
                return this._validateEnumConfig(key, value, rule);
            case 'array':
                return this._validateArrayConfig(key, value, rule);
            case 'object':
                return this._validateObjectConfig(key, value, rule);
            case 'string':
                return this._validateStringConfig(key, value, rule);
            default:
                console.warn(`Unknown validation rule type: ${rule.type} for key: ${key}`);
                return value;
        }
    }

    /**
     * 验证大小配置
     * @param {string} key - 配置项键名
     * @param {any} value - 配置项值
     * @param {Object} rule - 验证规则
     * @private
     */
    _validateSizeConfig(key, value, rule) {
        try {
            const parsed = this.parseFileSize(value);
            if (rule.min !== undefined && parsed < rule.min) {
                throw new Error(`Configuration validation failed for '${key}': value must be at least ${rule.min} bytes.`);
            }
            if (!rule.allowInfinity && parsed === Infinity) {
                throw new Error(`Configuration validation failed for '${key}': infinite values are not allowed.`);
            }
            return parsed;
        } catch (error) {
            throw new Error(`Configuration validation failed for '${key}': ${error.message}`);
        }
    }

    /**
     * 验证整数配置
     * @param {string} key - 配置项键名
     * @param {any} value - 配置项值
     * @param {Object} rule - 验证规则
     * @private
     */
    _validateIntegerConfig(key, value, rule) {
        if (!Number.isInteger(value) || value < 0) {
            throw new Error(`Configuration validation failed for '${key}': value must be a non-negative integer.`);
        }
        if (rule.min !== undefined && value < rule.min) {
            throw new Error(`Configuration validation failed for '${key}': value must be at least ${rule.min}.`);
        }
        if (rule.max !== undefined && value > rule.max) {
            throw new Error(`Configuration validation failed for '${key}': value must be at most ${rule.max}.`);
        }
        return value;
    }

    /**
     * 验证布尔配置
     * @param {string} key - 配置项键名
     * @param {any} value - 配置项值
     * @private
     */
    _validateBooleanConfig(key, value) {
        if (typeof value !== 'boolean') {
            throw new Error(`Configuration validation failed for '${key}': value must be a boolean.`);
        }
        return value;
    }

    /**
     * 验证枚举配置
     * @param {string} key - 配置项键名
     * @param {any} value - 配置项值
     * @param {Object} rule - 验证规则
     * @private
     */
    _validateEnumConfig(key, value, rule) {
        if (!rule.enum.includes(value)) {
            throw new Error(`Configuration validation failed for '${key}': value must be one of [${rule.enum.join(', ')}].`);
        }
        return value;
    }

    /**
     * 验证数组配置
     * @param {string} key - 配置项键名
     * @param {any} value - 配置项值
     * @param {Object} rule - 验证规则
     * @private
     */
    _validateArrayConfig(key, value, rule) {
        // 支持逗号分隔的字符串
        if (typeof value === 'string') {
            value = value.split(',').map(item => item.trim()).filter(item => item.length > 0);
        }

        if (!Array.isArray(value)) {
            throw new Error(`Configuration validation failed for '${key}': value must be an array.`);
        }

        if (rule.minLength !== undefined && value.length < rule.minLength) {
            throw new Error(`Configuration validation failed for '${key}': array must have at least ${rule.minLength} elements.`);
        }

        // 验证数组元素类型
        if (rule.elementType) {
            value.forEach((item, index) => {
                if (rule.elementType === 'string' && typeof item !== 'string') {
                    throw new Error(`Configuration validation failed for '${key}[${index}]': element must be a string.`);
                }
            });
        }

        return value;
    }

    /**
     * 验证对象配置
     * @param {string} key - 配置项键名
     * @param {any} value - 配置项值
     * @param {Object} rule - 验证规则
     * @private
     */
    _validateObjectConfig(key, value, rule) {
        if (typeof value !== 'object' || value === null || Array.isArray(value)) {
            throw new Error(`Configuration validation failed for '${key}': value must be an object.`);
        }

        // 验证对象schema
        if (rule.schema) {
            Object.keys(rule.schema).forEach(subKey => {
                const subRule = rule.schema[subKey];
                if (subKey in value) {
                    if (subRule.type === 'string' && typeof value[subKey] !== 'string') {
                        throw new Error(`Configuration validation failed for '${key}.${subKey}': value must be a string.`);
                    }
                } else if (subRule.required) {
                    throw new Error(`Configuration validation failed for '${key}.${subKey}': property is required.`);
                }
            });
        }

        return value;
    }

    /**
     * 验证字符串配置
     * @param {string} key - 配置项键名
     * @param {any} value - 配置项值
     * @param {Object} rule - 验证规则
     * @private
     */
    _validateStringConfig(key, value, rule) {
        if (typeof value !== 'string') {
            throw new Error(`Configuration validation failed for '${key}': value must be a string.`);
        }
        if (rule.minLength !== undefined && value.length < rule.minLength) {
            throw new Error(`Configuration validation failed for '${key}': string must be at least ${rule.minLength} characters long.`);
        }
        return value;
    }

    /**
     * 规范化allowedTypes配置
     * @param {Array} allowedTypes - 允许的文件类型数组
     * @returns {Array} 规范化后的数组
     * @private
     */
    _normalizeAllowedTypes(allowedTypes) {
        const source = Array.isArray(allowedTypes) ? allowedTypes : [allowedTypes];

        // 先打平数组并拆分逗号分隔的字符串
        const flattened = [];
        source.forEach(type => {
            if (Array.isArray(type)) {
                flattened.push(...type);
            } else if (typeof type === 'string' && type.includes(',')) {
                flattened.push(...type.split(','));
            } else {
                flattened.push(type);
            }
        });

        const processed = flattened
            .map(type => (typeof type === 'string' ? type.trim() : ''))
            .filter(type => type && typeof type === 'string')
            .map(type => type.replace(/\s+/g, '').toLowerCase());

        const result = [];
        const seen = new Set();

        processed.forEach(type => {
            if (type === '*' || type === '*/*') {
                const wildcard = type === '*' ? '*' : '*/*';
                if (!seen.has(wildcard)) {
                    seen.add(wildcard);
                    result.push(wildcard);
                }
                return;
            }

            if (type.includes('/')) {
                if (!seen.has(type)) {
                    seen.add(type);
                    result.push(type);
                }
                return;
            }

            let extension = type.replace(/^\*+/, '');
            if (extension.startsWith('.')) {
                extension = extension.slice(1);
            }
            if (!extension) {
                return;
            }

            if (!seen.has(extension)) {
                seen.add(extension);
                result.push(extension);
            }
        });

        return result;
    }

    /**
     * 验证配置项之间的关系
     * @param {Object} options - 配置选项
     * @private
     */
    _validateConfigRelationships(options) {
        // 文件数量关系验证
        if (options.minFiles !== undefined && options.maxFiles !== undefined && options.minFiles > options.maxFiles) {
            throw new Error('minFiles cannot be greater than maxFiles');
        }

        // 多文件选择一致性检查
        if (!options.multiple && options.maxFiles > 1) {
            console.warn('maxFiles is greater than 1 but multiple is false. Setting maxFiles to 1.');
            options.maxFiles = 1;
        }

        // 分片配置一致性检查
        if (options.chunked && options.chunkSize >= options.maxFileSize && options.maxFileSize > 0) {
            console.warn('chunkSize >= maxFileSize. Chunking may not be effective.');
        }

        // 并发和重试关系检查
        if (options.concurrent === 0 && options.retries > 5) {
            console.warn('Unlimited concurrency with high retry count may cause performance issues');
        }

        // 预览和内存优化关系检查
        if (options.showPreview && options.memoryOptimized && !options.previewSizeLimit) {
            console.warn('Consider setting previewSizeLimit when both showPreview and memoryOptimized are enabled');
        }

        // 安全配置一致性检查
        if (options.validateContent && options.allowedTypes.includes('*')) {
            console.warn('Content validation may not be effective when all file types are allowed');
        }
    }
}

/**
 * 事件管理内部类
 * 负责事件触发、节流、异步处理等事件相关逻辑
 * @class
 * @private
 */
class EventEmitter {
    constructor(element, options, uploader = null) {
        this.element = element;
        this.options = options;
        this.uploader = uploader;
        
        // 事件节流控制
        this.throttle = {
            enabled: options.enableEventThrottle !== false,
            rules: {
                [UPLOADER_EVENTS.UPLOAD_PROGRESS]: options.progressEventInterval || 50,
                [UPLOADER_EVENTS.STATS_UPDATED]: options.statsEventInterval || 100,
                'queueChanged': options.queueEventInterval || 100,
            },
            lastTriggerTimes: new Map(),
            pendingEvents: new Map(),
            pendingTimer: null
        };
    }

    /**
     * 触发事件（支持节流）
     * @param {string} eventName - 事件名
     * @param {...*} args - 事件参数
     * @returns {CustomEvent} 事件对象
     */
    trigger(eventName, ...args) {
        // 使用uploader的ThrottleManager（如果可用）
        if (this.uploader && this.uploader._throttleManager) {
            let actualEvent = null;
            
            const wasTriggered = this.uploader._throttleManager.throttle(
                'events',
                eventName,
                (context) => {
                    actualEvent = this._triggerDirect(context.eventName, context.args);
                },
                { eventName, args }
            );
            
            // 如果事件被立即触发，返回实际事件对象
            if (wasTriggered && actualEvent) {
                return actualEvent;
            }
            
            // 如果事件被节流延迟，返回一个占位事件对象（保持兼容性）
            return new CustomEvent(eventName, { 
                detail: args, 
                bubbles: false, 
                cancelable: false 
            });
        }
        
        // 后备：检查是否需要事件节流（保持兼容性）
        if (this.throttle.enabled && this.throttle.rules[eventName]) {
            return this._triggerWithThrottle(eventName, args);
        }
        
        // 直接触发事件（无节流）
        return this._triggerDirect(eventName, args);
    }

    /**
     * 异步触发事件
     * @param {string} eventName - 事件名
     * @param {...*} args - 事件参数
     * @returns {Promise<void>}
     */
    async triggerAsync(eventName, ...args) {
        const event = new CustomEvent(eventName, {
            detail: args,
            bubbles: true,
            cancelable: true
        });

        event._isAsync = true;
        event._asyncPromises = [];

        if (this.element && typeof this.element.dispatchEvent === 'function') {
            this.element.dispatchEvent(event);
        } else {
            console.warn('Cannot dispatch async event: element unavailable', { eventName });
            return;
        }

        if (event._asyncPromises.length > 0) {
            await Promise.all(event._asyncPromises);
        }

        if (event.defaultPrevented) {
            throw new Error(`事件 ${eventName} 被阻止`);
        }

        const callbackName = this.eventNameToCallbackName(eventName);
        if (typeof this.options[callbackName] === 'function') {
            try {
                await this.options[callbackName](...args);
            } catch (error) {
                console.error(`异步回调函数 ${callbackName} 执行失败:`, error);
                throw error;
            }
        }
    }

    /**
     * 带节流的事件触发
     * @param {string} eventName - 事件名
     * @param {Array} args - 事件参数
     * @returns {CustomEvent} 事件对象
     * @private
     */
    _triggerWithThrottle(eventName, args) {
        const now = Date.now();
        const throttleInterval = this.throttle.rules[eventName];
        const lastTriggerTime = this.throttle.lastTriggerTimes.get(eventName) || 0;
        const timeSinceLastTrigger = now - lastTriggerTime;
        
        const isImportant = this._isImportantEvent(eventName, args);
        
        if (isImportant || timeSinceLastTrigger >= throttleInterval) {
            this.throttle.lastTriggerTimes.set(eventName, now);
            return this._triggerDirect(eventName, args);
        } else {
            this.throttle.pendingEvents.set(eventName, { args, timestamp: now });
            
            if (!this.throttle.pendingTimer) {
                const remainingTime = throttleInterval - timeSinceLastTrigger;
                this.throttle.pendingTimer = setTimeout(() => {
                    this._flushPendingEvents();
                }, Math.max(remainingTime, 0));
            }
            
            return new CustomEvent(eventName, { detail: args, bubbles: false, cancelable: false });
        }
    }

    /**
     * 直接触发事件
     * @param {string} eventName - 事件名
     * @param {Array} args - 事件参数
     * @returns {CustomEvent} 事件对象
     * @private
     */
    _triggerDirect(eventName, args) {
        const event = new CustomEvent(eventName, {
            detail: args,
            bubbles: true,
            cancelable: true
        });

        if (this.element && typeof this.element.dispatchEvent === 'function') {
            this.element.dispatchEvent(event);
        } else {
            console.warn('Cannot dispatch event: element unavailable', { eventName });
        }

        const callbackName = this.eventNameToCallbackName(eventName);
        if (typeof this.options[callbackName] === 'function') {
            try {
                this.options[callbackName](...args);
            } catch (error) {
                console.error(`Error in '${callbackName}' callback:`, error);
            }
        }
        return event;
    }

    /**
     * 判断是否是重要事件（不受节流限制）
     * @param {string} eventName - 事件名
     * @param {Array} args - 事件参数
     * @returns {boolean}
     * @private
     */
    _isImportantEvent(eventName, args) {
        if (eventName.includes('error') || eventName.includes('success') || eventName.includes('complete')) {
            return true;
        }
        
        if (eventName === UPLOADER_EVENTS.UPLOAD_PROGRESS && args[1] && args[1].progress >= 100) {
            return true;
        }
        
        if (eventName === 'fileStatusChanged' && args[0]) {
            const status = args[0].status;
            return status === 'success' || status === 'error' || status === 'cancelled';
        }
        
        return false;
    }

    /**
     * 刷新所有待处理的事件
     * @private
     */
    _flushPendingEvents() {
        const now = Date.now();
        
        this.throttle.pendingEvents.forEach(({ args }, eventName) => {
            this.throttle.lastTriggerTimes.set(eventName, now);
            this._triggerDirect(eventName, args);
        });
        
        this.throttle.pendingEvents.clear();
        this.throttle.pendingTimer = null;
    }

    /**
     * 将事件名转换为回调函数名
     * @param {string} eventName - 事件名
     * @returns {string} 回调函数名
     */
    eventNameToCallbackName(eventName) {
        return 'on' + eventName.charAt(0).toUpperCase() + eventName.slice(1);
    }

    /**
     * 销毁事件管理器
     */
    destroy() {
        if (this.throttle.pendingTimer) {
            clearTimeout(this.throttle.pendingTimer);
            this.throttle.pendingTimer = null;
        }
        this.throttle.pendingEvents.clear();
        this.throttle.lastTriggerTimes.clear();
    }
}

/**
 * 内存管理内部类
 * 负责内存监控、清理和内存相关的优化策略
 * @class
 * @private
 */
class MemoryManager {
    constructor(uploader, options) {
        this.uploader = uploader;
        this.options = options;
        
        this.monitor = {
            enabled: options.enableMemoryMonitor !== false,
            interval: options.memoryMonitorInterval || 5000,
            maxMemoryUsage: options.maxMemoryUsage || 1024 * 1024 * 1024,
            warningThreshold: options.memoryWarningThreshold || 0.8,
            criticalThreshold: options.memoryCriticalThreshold || 0.9,
            
            currentUsage: 0,
            peakUsage: 0,
            averageUsage: 0,
            usageHistory: [],
            warningCount: 0,
            criticalCount: 0,
            lastCleanupTime: 0,
            
            totalAllocations: 0,
            totalDeallocations: 0,
            forcedCleanups: 0,
            
            monitorTimer: null
        };
        
        if (this.monitor.enabled) {
            this.startMonitoring();
        }
    }

    /**
     * 开始内存监控
     */
    startMonitoring() {
        if (this.monitor.monitorTimer) {
            clearInterval(this.monitor.monitorTimer);
        }

        this.monitor.monitorTimer = setInterval(() => {
            this.performCheck();
        }, this.monitor.interval);

        this._debug('内存监控已启动');
    }

    /**
     * 停止内存监控
     */
    stopMonitoring() {
        if (this.monitor.monitorTimer) {
            clearInterval(this.monitor.monitorTimer);
            this.monitor.monitorTimer = null;
        }
        this._debug('内存监控已停止');
    }

    /**
     * 执行内存检查
     * @private
     */
    performCheck() {
        const memoryInfo = this.getMemoryInfo();
        const monitor = this.monitor;

        monitor.currentUsage = memoryInfo.used;
        monitor.peakUsage = Math.max(monitor.peakUsage, memoryInfo.used);
        
        monitor.usageHistory.push({
            timestamp: Date.now(),
            used: memoryInfo.used,
            total: memoryInfo.total,
            percentage: memoryInfo.percentage
        });
        
        if (monitor.usageHistory.length > 100) {
            monitor.usageHistory.shift();
        }

        if (monitor.usageHistory.length > 0) {
            const totalUsage = monitor.usageHistory.reduce((sum, record) => sum + record.used, 0);
            monitor.averageUsage = totalUsage / monitor.usageHistory.length;
        }

        this._checkThresholds(memoryInfo);

        this.uploader.trigger('memoryStatus', {
            current: memoryInfo,
            monitor: monitor,
            recommendations: this.getRecommendations(memoryInfo)
        });
    }

    /**
     * 获取内存信息
     * @returns {Object} 内存信息
     */
    getMemoryInfo() {
        const info = {
            used: 0,
            total: 0,
            percentage: 0,
            available: 0,
            supported: false
        };

        if (typeof performance !== 'undefined' && performance.memory) {
            info.used = performance.memory.usedJSHeapSize;
            info.total = performance.memory.totalJSHeapSize;
            info.available = performance.memory.jsHeapSizeLimit;
            info.percentage = info.total > 0 ? (info.used / info.total) * 100 : 0;
            info.supported = true;
        }

        return info;
    }

    /**
     * 检查内存阈值
     * @param {Object} memoryInfo - 内存信息
     * @private
     */
    _checkThresholds(memoryInfo) {
        const monitor = this.monitor;
        const usageRatio = memoryInfo.available > 0 ? memoryInfo.used / memoryInfo.available : 0;

        if (usageRatio >= monitor.criticalThreshold) {
            monitor.criticalCount++;
            this._debug(`🚨 内存使用严重警告: ${Math.round(usageRatio * 100)}%`);
            
            this.performEmergencyCleanup();
            
            this.uploader.trigger('memoryCritical', {
                usage: memoryInfo,
                ratio: usageRatio,
                action: 'emergency_cleanup'
            });
            
        } else if (usageRatio >= monitor.warningThreshold) {
            monitor.warningCount++;
            this._debug(`⚠️ 内存使用警告: ${Math.round(usageRatio * 100)}%`);
            
            this.performGentleCleanup();
            
            this.uploader.trigger('memoryWarning', {
                usage: memoryInfo,
                ratio: usageRatio,
                action: 'gentle_cleanup'
            });
        }
    }

    /**
     * 执行紧急内存清理
     */
    performEmergencyCleanup() {
        const cleanupStartTime = Date.now();
        this._debug('🚨 执行紧急内存清理...');
        
        this.uploader._clearAllObjectUrls();
        
        this.uploader.files.forEach(fileItem => {
            if (fileItem.status === 'completed' || fileItem.status === 'error') {
                if (fileItem.cachedChunks) {
                    fileItem.cachedChunks.clear();
                    fileItem.cachedChunks = null;
                }
                fileItem.tempData = null;
            }
        });
        
        this._attemptGarbageCollection();
        this._cleanupEventReferences();
        
        this.monitor.forcedCleanups++;
        this.monitor.lastCleanupTime = cleanupStartTime;
        
        const cleanupTime = Date.now() - cleanupStartTime;
        this._debug(`紧急清理完成，耗时: ${cleanupTime}ms`);
    }

    /**
     * 执行温和的内存清理
     */
    performGentleCleanup() {
        this._debug('执行温和内存清理...');
        
        this.uploader._cleanupExpiredUrls();
        
        this.uploader.files.forEach(fileItem => {
            if (fileItem.status === 'completed' && 
                Date.now() - fileItem.completedTime > 300000) {
                fileItem.tempData = null;
            }
        });
        
        this._attemptGarbageCollection();
    }

    /**
     * 获取内存使用建议
     * @param {Object} memoryInfo - 内存信息
     * @returns {Array} 建议列表
     */
    getRecommendations(memoryInfo) {
        const recommendations = [];
        const usageRatio = memoryInfo.available > 0 ? memoryInfo.used / memoryInfo.available : 0;
        
        if (usageRatio > 0.8) {
            recommendations.push('考虑减少同时上传的文件数量');
            recommendations.push('清理已完成的文件');
            recommendations.push('使用分片上传处理大文件');
        }
        
        if (this.uploader.files.size > 50) {
            recommendations.push('文件队列较长，考虑分批处理');
        }
        
        if (this.monitor.peakUsage > memoryInfo.available * 0.9) {
            recommendations.push('检测到内存峰值过高，建议重启应用');
        }
        
        return recommendations;
    }

    /**
     * 获取内存监控报告
     * @returns {Object} 内存报告
     */
    getReport() {
        const currentInfo = this.getMemoryInfo();
        
        return {
            current: currentInfo,
            peak: {
                usage: this.monitor.peakUsage,
                formatted: Math.round(this.monitor.peakUsage / 1024 / 1024) + 'MB'
            },
            average: {
                usage: this.monitor.averageUsage,
                formatted: Math.round(this.monitor.averageUsage / 1024 / 1024) + 'MB'
            },
            statistics: {
                warningCount: this.monitor.warningCount,
                criticalCount: this.monitor.criticalCount,
                forcedCleanups: this.monitor.forcedCleanups,
                lastCleanupTime: this.monitor.lastCleanupTime
            },
            history: this.monitor.usageHistory.slice(-10),
            recommendations: this.getRecommendations(currentInfo),
            objectUrls: {
                count: this.uploader.objectUrls ? this.uploader.objectUrls.size : 0,
                estimatedMemory: (this.uploader.objectUrls ? this.uploader.objectUrls.size : 0) * 1024
            }
        };
    }

    /**
     * 清理事件引用
     * @private
     */
    _cleanupEventReferences() {
        this.uploader.files.forEach((fileItem, fileId) => {
            if (fileItem.status === 'completed' || fileItem.status === 'error') {
                if (this.uploader.events) {
                    Object.keys(this.uploader.events).forEach(eventName => {
                        if (this.uploader.events[eventName]) {
                            this.uploader.events[eventName] = this.uploader.events[eventName].filter(handler => {
                                return !handler._fileId || handler._fileId !== fileId;
                            });
                        }
                    });
                }
            }
        });
    }

    /**
     * 尝试垃圾回收
     * @private
     */
    _attemptGarbageCollection() {
        if (typeof window !== 'undefined' && window.gc) {
            try {
                window.gc();
                this._debug('手动垃圾回收已触发');
            } catch (error) {
                this._debug('手动垃圾回收失败:', error);
            }
        }
    }

    /**
     * 调试输出
     * @param {...*} args - 调试参数
     * @private
     */
    _debug(...args) {
        if (this.uploader._debug) {
            this.uploader._debug('[MemoryManager]', ...args);
        }
    }

    /**
     * 销毁内存管理器
     */
    destroy() {
        this.stopMonitoring();
        this.monitor.usageHistory = [];
    }
}

/**
 * 调试管理内部类
 * 负责日志系统、调试输出和错误跟踪
 * @class
 * @private
 */
class DebugManager {
    constructor(uploader, options) {
        this.uploader = uploader;
        this.options = options;
        
        // 日志级别定义
        this.LOG_LEVELS = {
            ERROR: 0,
            WARN: 1, 
            INFO: 2,
            DEBUG: 3,
            TRACE: 4
        };

        this.logLevel = this._getLogLevel();
        
        this.logCollector = {
            logs: [],
            maxLogs: options.maxLogs || 1000,
            startTime: Date.now()
        };

        this._createLogMethods();
        
        if (options.debug && options.debugPanel) {
            this._initDebugPanel();
        }
    }

    /**
     * 获取日志级别
     * @private
     */
    _getLogLevel() {
        if (typeof this.options.debug === 'string') {
            const level = this.options.debug.toUpperCase();
            return this.LOG_LEVELS[level] !== undefined ? this.LOG_LEVELS[level] : this.LOG_LEVELS.INFO;
        }
        return this.options.debug ? this.LOG_LEVELS.DEBUG : this.LOG_LEVELS.WARN;
    }

    /**
     * 创建分级日志方法
     * @private
     */
    _createLogMethods() {
        this.logError = (message, error, context = {}) => {
            this._log('ERROR', message, { error, ...context });
        };

        this.logWarn = (message, context = {}) => {
            this._log('WARN', message, context);
        };

        this.logInfo = (message, context = {}) => {
            this._log('INFO', message, context);
        };

        this.logDebug = (message, context = {}) => {
            this._log('DEBUG', message, context);
        };

        this.logTrace = (message, context = {}) => {
            this._log('TRACE', message, context);
        };

        // 兼容性：保留原有的_debug方法
        this._debug = (message, ...args) => {
            this.logDebug(message, { args });
        };
    }

    /**
     * 统一日志处理方法
     * @param {string} level - 日志级别
     * @param {string} message - 日志消息
     * @param {Object} context - 上下文信息
     * @private
     */
    _log(level, message, context = {}) {
        const levelValue = this.LOG_LEVELS[level];
        
        if (levelValue > this.logLevel) {
            return;
        }

        const logEntry = {
            timestamp: Date.now(),
            level,
            message,
            context,
            instanceId: this.uploader.instanceId || 'default',
            relativeTime: Date.now() - this.logCollector.startTime
        };

        this._addToLogCollector(logEntry);
        this._outputToConsole(logEntry);

        if (this.debugPanel) {
            this.debugPanel.addLogEntry(logEntry);
        }
    }

    /**
     * 添加日志到收集器
     * @param {Object} logEntry - 日志条目
     * @private
     */
    _addToLogCollector(logEntry) {
        this.logCollector.logs.push(logEntry);
        
        if (this.logCollector.logs.length > this.logCollector.maxLogs) {
            this.logCollector.logs.shift();
        }
    }

    /**
     * 输出到控制台
     * @param {Object} logEntry - 日志条目
     * @private
     */
    _outputToConsole(logEntry) {
        const { level, message, context, relativeTime } = logEntry;
        const prefix = `[DolphinUploader][${level}][+${relativeTime}ms]`;

        const consoleMethod = this._getConsoleMethod(level);
        
        if (Object.keys(context).length > 0) {
            consoleMethod(`${prefix} ${message}`, context);
        } else {
            consoleMethod(`${prefix} ${message}`);
        }
    }

    /**
     * 获取控制台方法
     * @param {string} level - 日志级别
     * @private
     */
    _getConsoleMethod(level) {
        switch (level) {
            case 'ERROR': return console.error;
            case 'WARN': return console.warn;
            case 'INFO': return console.info;
            case 'DEBUG': return console.debug;
            case 'TRACE': return console.trace;
            default: return console.log;
        }
    }

    /**
     * 初始化调试面板
     * @private
     */
    _initDebugPanel() {
        // 调试面板初始化逻辑（如果需要）
        this.debugPanel = null; // 简化实现
    }

    /**
     * 获取日志报告
     * @param {number} limit - 限制日志数量
     * @returns {Object} 日志报告
     */
    getLogReport(limit = 100) {
        const logs = this.logCollector.logs.slice(-limit);
        const levelCounts = {};

        logs.forEach(log => {
            levelCounts[log.level] = (levelCounts[log.level] || 0) + 1;
        });

        return {
            timestamp: Date.now(),
            totalLogs: this.logCollector.logs.length,
            levelCounts,
            logs,
            logLevel: Object.keys(this.LOG_LEVELS).find(key => this.LOG_LEVELS[key] === this.logLevel),
            startTime: this.logCollector.startTime
        };
    }

    /**
     * 销毁调试管理器
     */
    destroy() {
        this.logCollector.logs = [];
        if (this.debugPanel) {
            this.debugPanel = null;
        }
    }
}

/**
 * 性能监控内部类
 * 负责性能计时、指标收集和内存快照
 * @class
 * @private
 */
class PerformanceMonitor {
    constructor(uploader, options) {
        this.uploader = uploader;
        this.options = options;
        
        this.enabled = options.debug || options.performanceMonitoring;
        this.metrics = new Map();
        this.timers = new Map();
        this.counters = new Map();
        this.memorySnapshots = [];
    }

    /**
     * 开始性能计时
     * @param {string} name - 计时器名称
     * @param {Object} context - 上下文信息
     */
    startTimer(name, context = {}) {
        if (!this.enabled) return;

        const timer = {
            name,
            startTime: performance.now(),
            startMemory: this._getCurrentMemoryUsage(),
            context
        };

        this.timers.set(name, timer);
        this._debug(`计时器启动: ${name}`, context);
    }

    /**
     * 结束性能计时
     * @param {string} name - 计时器名称
     * @param {Object} additionalContext - 额外上下文信息
     * @returns {Object} 性能指标
     */
    endTimer(name, additionalContext = {}) {
        if (!this.enabled) return null;

        const timer = this.timers.get(name);
        if (!timer) {
            this._debug(`计时器不存在: ${name}`);
            return null;
        }

        const endTime = performance.now();
        const endMemory = this._getCurrentMemoryUsage();
        const duration = endTime - timer.startTime;
        const memoryDelta = endMemory - timer.startMemory;

        const metrics = {
            name,
            duration,
            memoryDelta,
            startTime: timer.startTime,
            endTime,
            context: { ...timer.context, ...additionalContext }
        };

        this._saveMetric(metrics);
        this.timers.delete(name);

        this._debug(`计时器完成: ${name}`, {
            duration: `${duration.toFixed(2)}ms`,
            memoryDelta: `${(memoryDelta / 1024 / 1024).toFixed(2)}MB`
        });

        return metrics;
    }

    /**
     * 增加计数器
     * @param {string} name - 计数器名称
     * @param {number} value - 增加的值
     * @param {Object} context - 上下文信息
     */
    incrementCounter(name, value = 1, context = {}) {
        if (!this.enabled) return;

        const current = this.counters.get(name) || 0;
        const newValue = current + value;
        
        this.counters.set(name, newValue);
        this._debug(`计数器更新: ${name} = ${newValue}`, context);
    }

    /**
     * 记录自定义指标
     * @param {string} name - 指标名称
     * @param {*} value - 指标值
     * @param {Object} context - 上下文信息
     */
    recordMetric(name, value, context = {}) {
        if (!this.enabled) return;

        const metric = {
            name,
            value,
            timestamp: Date.now(),
            context
        };

        this._saveMetric(metric);
        this._debug(`自定义指标: ${name}`, { value, ...context });
    }

    /**
     * 拍摄内存快照
     * @param {string} label - 快照标签
     */
    takeMemorySnapshot(label = 'snapshot') {
        if (!this.enabled) return;

        const snapshot = {
            label,
            timestamp: Date.now(),
            memory: this._getDetailedMemoryInfo(),
            objectUrls: this.uploader.objectUrls ? this.uploader.objectUrls.size : 0,
            files: this.uploader.files ? this.uploader.files.size : 0,
            uploadQueue: this.uploader.uploadQueue ? this.uploader.uploadQueue.length : 0
        };

        this.memorySnapshots.push(snapshot);

        const maxSnapshots = 50;
        if (this.memorySnapshots.length > maxSnapshots) {
            this.memorySnapshots.shift();
        }

        this._debug(`内存快照: ${label}`, snapshot);
    }

    /**
     * 获取性能报告
     * @returns {Object} 性能报告
     */
    getReport() {
        if (!this.enabled) {
            return { error: 'Performance monitoring is disabled' };
        }

        const report = {
            timestamp: Date.now(),
            uptime: Date.now() - (this.uploader._debugManager ? this.uploader._debugManager.logCollector.startTime : Date.now()),
            metrics: {},
            counters: Object.fromEntries(this.counters),
            memorySnapshots: this.memorySnapshots.slice(-10),
            activeTimers: Array.from(this.timers.keys())
        };

        for (const [name, metrics] of this.metrics) {
            if (metrics.length === 0) continue;

            const durations = metrics.map(m => m.duration).filter(d => d !== undefined);
            const values = metrics.map(m => m.value).filter(v => v !== undefined);

            report.metrics[name] = {
                count: metrics.length,
                latest: metrics[metrics.length - 1],
                ...(durations.length > 0 && {
                    duration: {
                        min: Math.min(...durations),
                        max: Math.max(...durations),
                        avg: durations.reduce((a, b) => a + b, 0) / durations.length,
                        total: durations.reduce((a, b) => a + b, 0)
                    }
                }),
                ...(values.length > 0 && {
                    values: {
                        min: Math.min(...values),
                        max: Math.max(...values),
                        avg: values.reduce((a, b) => a + b, 0) / values.length,
                        latest: values[values.length - 1]
                    }
                })
            };
        }

        return report;
    }

    /**
     * 保存性能指标
     * @param {Object} metric - 性能指标
     * @private
     */
    _saveMetric(metric) {
        if (!this.metrics.has(metric.name)) {
            this.metrics.set(metric.name, []);
        }

        const metrics = this.metrics.get(metric.name);
        metrics.push(metric);

        const maxMetrics = 100;
        if (metrics.length > maxMetrics) {
            metrics.shift();
        }
    }

    /**
     * 获取当前内存使用量
     * @returns {number} 内存使用量
     * @private
     */
    _getCurrentMemoryUsage() {
        if (typeof performance !== 'undefined' && performance.memory) {
            return performance.memory.usedJSHeapSize;
        }
        return 0;
    }

    /**
     * 获取详细内存信息
     * @returns {Object} 内存信息
     * @private
     */
    _getDetailedMemoryInfo() {
        const basic = this._getCurrentMemoryUsage();
        
        const detailed = {
            estimated: basic,
            performance: null,
            gc: null
        };

        if (performance.memory) {
            detailed.performance = {
                used: performance.memory.usedJSHeapSize,
                total: performance.memory.totalJSHeapSize,
                limit: performance.memory.jsHeapSizeLimit
            };
        }

        if (window.performance && window.performance.measureUserAgentSpecificMemory) {
            detailed.gc = 'measureUserAgentSpecificMemory available';
        }

        return detailed;
    }

    /**
     * 调试输出
     * @param {...*} args - 调试参数
     * @private
     */
    _debug(...args) {
        if (this.uploader._debugManager) {
            this.uploader._debugManager.logDebug('[PerformanceMonitor]', { args });
        }
    }

    /**
     * 销毁性能监控器
     */
    destroy() {
        this.timers.clear();
        this.counters.clear();
        this.metrics.clear();
        this.memorySnapshots = [];
    }
}

/**
 * 统一节流管理器
 * 合并原有的事件节流、进度节流和统计缓存功能
 */
class ThrottleManager {
    constructor(uploader, options) {
        this.uploader = uploader;
        this.options = options;

        // 统一节流规则配置
        this.throttleRules = {
            // 事件节流规则
            events: {
                'progress': options.progressUpdateInterval || 100,
                'stats': options.statsCacheExpiry || 50,
                'upload': 200,
                'error': 500,
                'success': 100,
                'queue': 150
            },
            // 进度更新节流
            progress: {
                enabled: options.enableProgressThrottle !== false,
                interval: options.progressUpdateInterval || 100
            },
            // 统计缓存
            stats: {
                enabled: options.enableStatsCache !== false,
                expiry: options.statsCacheExpiry || 50
            }
        };

        // 统一的节流状态
        this.throttleState = {
            lastTriggerTimes: new Map(),
            pendingActions: new Map(),
            timers: new Map(),
            cache: new Map()
        };
    }

    /**
     * 通用节流方法
     * @param {string} type - 节流类型 ('event', 'progress', 'stats')
     * @param {string} key - 节流键
     * @param {Function} action - 要执行的动作
     * @param {Object} context - 上下文数据
     * @returns {boolean} 是否立即执行
     */
    throttle(type, key, action, context = {}) {
        const rule = this.throttleRules[type];
        if (!rule || (rule.enabled === false)) {
            // 节流已禁用，直接执行
            action(context);
            return true;
        }

        if (type === 'events') {
            const eventRules = rule.events || null;
            // 只有显式定义了的事件才应用节流，否则立即执行，避免丢失关键事件
            if (!eventRules || !Object.prototype.hasOwnProperty.call(eventRules, key)) {
                action(context);
                return true;
            }
        }

        const throttleKey = `${type}:${key}`;
        const now = Date.now();
        const interval = rule.interval || rule.expiry || rule.events?.[key] || 100;

        const lastTriggerTime = this.throttleState.lastTriggerTimes.get(throttleKey) || 0;
        const timeSinceLastTrigger = now - lastTriggerTime;

        // 检查是否是重要操作（立即执行）
        const isImportant = this._isImportantAction(type, key, context);

        if (isImportant || timeSinceLastTrigger >= interval) {
            // 立即执行
            this.throttleState.lastTriggerTimes.set(throttleKey, now);
            action(context);
            return true;
        } else {
            // 节流延迟执行
            this._scheduleAction(throttleKey, action, context, interval - timeSinceLastTrigger);
            return false;
        }
    }

    /**
     * 带缓存的节流（用于统计等昂贵计算）
     * @param {string} key - 缓存键
     * @param {Function} calculator - 计算函数
     * @param {Object} context - 上下文
     * @returns {*} 计算结果
     */
    throttleWithCache(key, calculator, context = {}) {
        const cacheKey = `cache:${key}`;
        const now = Date.now();
        const expiry = this.throttleRules.stats.expiry;

        // 检查缓存
        const cached = this.throttleState.cache.get(cacheKey);
        if (cached && (now - cached.timestamp) < expiry && !context.forceRefresh) {
            return cached.data;
        }

        // 计算新值
        const result = calculator(context);
        
        // 更新缓存
        this.throttleState.cache.set(cacheKey, {
            data: result,
            timestamp: now
        });

        return result;
    }

    /**
     * 调度延迟执行的动作
     * @param {string} throttleKey - 节流键
     * @param {Function} action - 动作
     * @param {Object} context - 上下文
     * @param {number} delay - 延迟时间
     * @private
     */
    _scheduleAction(throttleKey, action, context, delay) {
        // 取消之前的定时器
        const existingTimer = this.throttleState.timers.get(throttleKey);
        if (existingTimer) {
            clearTimeout(existingTimer);
        }

        // 更新待处理的动作（使用最新的）
        this.throttleState.pendingActions.set(throttleKey, { action, context });

        // 设置新的定时器
        const timer = setTimeout(() => {
            const pending = this.throttleState.pendingActions.get(throttleKey);
            if (pending) {
                this.throttleState.lastTriggerTimes.set(throttleKey, Date.now());
                pending.action(pending.context);
                this.throttleState.pendingActions.delete(throttleKey);
            }
            this.throttleState.timers.delete(throttleKey);
        }, Math.max(delay, 0));

        this.throttleState.timers.set(throttleKey, timer);
    }

    /**
     * 判断是否是重要动作（不受节流限制）
     * @param {string} type - 节流类型
     * @param {string} key - 节流键
     * @param {Object} context - 上下文
     * @returns {boolean}
     * @private
     */
    _isImportantAction(type, key, context) {
        if (type === 'progress') {
            // 进度达到100%或状态为完成/错误时是重要更新
            return context.progress >= 100 || 
                   context.status === 'success' || 
                   context.status === 'error';
        }
        
        if (type === 'event' || type === 'events') {
            // 某些事件总是重要的
            const importantEvents = ['error', 'destroy', 'init'];
            return importantEvents.includes(key);
        }

        return false;
    }

    /**
     * 立即刷新所有待处理的动作
     */
    flush() {
        const now = Date.now();
        
        this.throttleState.pendingActions.forEach(({ action, context }, throttleKey) => {
            this.throttleState.lastTriggerTimes.set(throttleKey, now);
            action(context);
        });

        this._clearAll();
    }

    /**
     * 清理所有节流状态
     * @private
     */
    _clearAll() {
        // 清理所有定时器
        this.throttleState.timers.forEach(timer => clearTimeout(timer));
        
        // 清理所有状态
        this.throttleState.lastTriggerTimes.clear();
        this.throttleState.pendingActions.clear();
        this.throttleState.timers.clear();
    }

    /**
     * 销毁节流管理器
     */
    destroy() {
        this._clearAll();
        this.throttleState.cache.clear();
        this.uploader = null;
        this.options = null;
    }
}

/**
 * DolphinUploader 主类
 * @class
 */
class DolphinUploader {
    /**
     * @param {HTMLElement|string} element - 挂载上传器的DOM元素或其CSS选择器。
     * @param {object} [options={}] - 配置选项。
     */
    constructor(element, options = {}) {
        // 记录初始化开始时间
        this._initStartTime = performance.now();
        
        // 核心初始化（关键路径）
        this._validateElement(element);
        this._configManager = new ConfigurationManager();
        this._processOptions(options);
        this._initializeState();
        
        // 立即初始化关键子系统
        this._initializeCriticalSubsystems();
        
        // 基础初始化
        this._initBasicUI();
        this._bindCriticalEvents();
        
        // 异步初始化非关键功能
        this._scheduleNonCriticalInitialization();
        
        // 触发初始化完成事件
        this._finalizeInitialization();
    }

    // =====================================
    // 初始化相关方法
    // =====================================

    /**
     * 验证和设置DOM元素
     * @param {HTMLElement|string} element - 目标元素或选择器
     * @private
     */
    _validateElement(element) {
        // 参数验证
        if (!element) {
            throw new Error('Upload element is required');
        }

        // 更强的元素处理逻辑
        if (element instanceof HTMLElement) {
            this.element = element;
        } else if (typeof element === 'string') {
            this.element = document.querySelector(element);
        } else {
            // 处理可能的 jQuery 对象或其他包装器
            console.warn('Unexpected element type:', typeof element, element);
            throw new Error(`Invalid element type: expected HTMLElement or string, got ${typeof element}`);
        }
        
        if (!this.element) {
            throw new Error('Upload element not found');
        }
        
        // 验证是否为有效的DOM元素
        if (typeof this.element.dispatchEvent !== 'function') {
            throw new Error('Element does not support dispatchEvent (not a valid DOM element)');
        }
    }

    /**
     * 处理配置选项
     * @param {Object} options - 用户提供的配置选项
     * @private
     */
    _processOptions(options) {
        // 快速配置处理（仅关键配置）
        const defaultOptions = this._configManager.getDefaultOptions();
        
        // 简化配置合并，推迟复杂处理
        this.options = {
            ...defaultOptions,
            ...options,
            // 确保关键配置存在（需要在初始化期间立即使用的配置）
            debug: options.debug !== undefined ? options.debug : defaultOptions.debug,
            multiple: options.multiple !== undefined ? options.multiple : defaultOptions.multiple,
            maxFiles: options.maxFiles !== undefined ? options.maxFiles : defaultOptions.maxFiles,
            accept: options.accept !== undefined ? options.accept : defaultOptions.accept,
            allowedTypes: options.allowedTypes !== undefined ? options.allowedTypes : defaultOptions.allowedTypes,
            enableProgressThrottle: options.enableProgressThrottle !== undefined ? options.enableProgressThrottle : defaultOptions.enableProgressThrottle,
            enableStatsCache: options.enableStatsCache !== undefined ? options.enableStatsCache : defaultOptions.enableStatsCache
        };

        this.options.allowedTypes = this._resolveAllowedTypes(this.options.allowedTypes);
        this._normalizeSizeOptions(this.options);

        // 延迟完整的配置处理和验证
        this._pendingOptionsProcessing = {
            userOptions: options,
            defaultOptions
        };
    }

    /**
     * 完成配置处理（延迟执行）
     * @private
     */
    _completeOptionsProcessing() {
        if (!this._pendingOptionsProcessing) return;
        
        const { userOptions, defaultOptions } = this._pendingOptionsProcessing;
        const existingOptions = this.options || {};

        // 完整的预设配置处理
        const processedOptions = this._configManager.processPresetConfig(userOptions);

        // 深度合并配置，同时保留初始化阶段挂载的自定义钩子（如 responseHandler）
        const finalEndpoints = {
            ...defaultOptions.endpoints,
            ...(existingOptions.endpoints || {}),
            ...(processedOptions.endpoints || {})
        };

        this.options = {
            ...defaultOptions,
            ...existingOptions,
            ...processedOptions,
            endpoints: finalEndpoints
        };

        this.options.allowedTypes = this._resolveAllowedTypes(this.options.allowedTypes);
        this._normalizeSizeOptions(this.options);

        // 配置验证（非关键路径）
        try {
            this._configManager.validateOptions(this.options);
        } catch (error) {
            console.warn('Configuration validation warning:', error.message);
        }
        
        this._pendingOptionsProcessing = null;
    }


    /**
     * 初始化组件状态
     * @private
     */
    _initializeState() {
        // --- 状态管理 ---
        /**
         * 存储所有文件项的Map，键为文件ID
         * @type {Map<string, DolphinFileUploadItem>}
         */
        this.files = new Map();

        /**
         * 待上传文件队列
         * @type {DolphinFileUploadItem[]}
         */
        this.uploadQueue = [];

        /**
         * 当前活动的上传任务数
         * @type {number}
         */
        this.activeUploads = 0;

        /**
         * 实例是否已被销毁
         * @type {boolean}
         */
        this.destroyed = false;

        /**
         * 存储每个文件上传请求的AbortController，用于实现取消功能
         * @type {Map<string, AbortController>}
         */
        this.abortControllers = new Map();

        /**
         * 队列处理锁，防止 processQueue 方法并发执行
         * @type {boolean}
         */
        this.processingQueue = false;

        /**
         * 存储事件监听器的清理函数，用于在destroy时移除监听
         * @type {Function[]}
         */
        this.eventCleanups = [];

        /**
         * 存储已创建的ObjectURL，用于预览后及时释放，防止内存泄漏
         * 使用Map建立文件与URL的映射关系，便于管理和及时清理
         * @type {Map<string, {url: string, file: File, createdAt: number}>}
         */
        this.objectUrls = new Map();
        
        /**
         * ObjectURL自动清理定时器
         * @type {number|null}
         */
        this.urlCleanupTimer = null;
    }

    /**
     * 初始化关键子系统（立即需要的功能）
     * @private
     */
    _initializeCriticalSubsystems() {
        // 只初始化核心必需的模块
        this._securityChecker = new SecurityChecker(this.options);
        this._fileValidator = new FileValidator(this._securityChecker);
        this._throttleManager = new ThrottleManager(this, this.options);
    }

    /**
     * 初始化非关键子系统（延迟加载）
     * @private
     */
    _initializeNonCriticalSubsystems() {
        // 完成配置处理
        this._completeOptionsProcessing();
        
        // 初始化调试和性能监控（非关键路径）
        this._debugManager = new DebugManager(this, this.options);
        this._performanceMonitor = new PerformanceMonitor(this, this.options);
        
        // 初始化上传相关模块
        this._hashCalculator = new HashCalculator(this.options);
        this._chunkUploader = new ChunkUploader(this);
        this._uploadManager = new UploadManager(this, this._hashCalculator, this._chunkUploader);
        
        // 初始化事件和内存管理
        this._eventEmitter = new EventEmitter(this.element, this.options, this);
        this._memoryManager = new MemoryManager(this, this.options);
    }

    /**
     * 调度非关键功能的异步初始化
     * @private
     */
    _scheduleNonCriticalInitialization() {
        // 使用 setTimeout 推迟非关键初始化，让关键路径先完成
        setTimeout(() => {
            if (!this.destroyed) {
                this._initializeNonCriticalSubsystems();
                this._initAdvancedUI();
                this._bindAdvancedEvents();
                
                // 触发延迟初始化完成事件
                this.trigger('nonCriticalInitialized', {
                    instance: this,
                    totalInitTime: performance.now() - this._initStartTime
                });
            }
        }, 0);
    }

    /**
     * 初始化基础UI（仅必需的UI元素）
     * @private
     */
    _initBasicUI() {
        // 只初始化必需的UI元素
        if (!this.element) {
            throw new Error('Element reference lost during initialization');
        }
        
        // 处理文件输入元素
        if (this.element.tagName === 'INPUT' && this.element.type === 'file') {
            this.fileInput = this.element;
        } else {
            this.fileInput = this.element.querySelector('input[type="file"]');
            if (!this.fileInput) {
                this.fileInput = this.createHiddenFileInput();
                this.element.appendChild(this.fileInput);
            }
        }
        
        // 确保文件输入元素的关键属性正确设置
        this._updateFileInputAttributes();
    }

    /**
     * 更新文件输入元素的关键属性
     * @private
     */
    _updateFileInputAttributes() {
        if (!this.fileInput) return;
        
        // 设置multiple属性
        if (this.options.multiple) {
            this.fileInput.setAttribute('multiple', '');
        } else {
            this.fileInput.removeAttribute('multiple');
        }
        
        // 设置accept属性
        const acceptTypes = this.getAcceptTypes();
        if (acceptTypes) {
            this.fileInput.setAttribute('accept', acceptTypes);
        }
    }

    /**
     * 初始化高级UI功能（延迟加载）
     * @private
     */
    _initAdvancedUI() {
        // 延迟初始化的UI功能
        this.uploadArea = this.element;
        
        // 其他高级UI功能...
        if (this.options.debug) {
            console.log('Advanced UI initialized');
        }
    }

    /**
     * 绑定关键事件（立即需要的事件）
     * @private
     */
    _bindCriticalEvents() {
        // 只绑定核心必需的事件
        this.handleFileChange = this.handleFileChange.bind(this);
        this.fileInput.addEventListener('change', this.handleFileChange);
        
        // 添加到清理队列
        this.eventCleanups.push(() => {
            if (this.fileInput) {
                this.fileInput.removeEventListener('change', this.handleFileChange);
            }
        });
    }

    /**
     * 绑定高级事件（延迟加载）
     * @private
     */
    _bindAdvancedEvents() {
        // 延迟绑定的事件
        this.handleAreaClick = this.handleAreaClick.bind(this);
        this.handleKeydown = this.handleKeydown.bind(this);
        this.handlePaste = this.handlePaste.bind(this);
        this.preventDefaults = this.preventDefaults.bind(this);

        // 绑定自定义UI事件
        this.bindCustomUIEvents();

        // 全局粘贴支持
        const pasteCleanup = this.addGlobalEventListener(document, 'paste', this.handlePaste);
        this.eventCleanups.push(pasteCleanup);
    }

    /**
     * 完成初始化并触发初始化事件
     * @private
     */
    _finalizeInitialization() {
        this.initTime = Date.now();
        const criticalInitTime = performance.now() - this._initStartTime;
        
        // 立即触发关键初始化完成事件
        setTimeout(() => {
            if (!this.destroyed) {
                this.trigger(UPLOADER_EVENTS.INIT, {
                    instance: this,
                    initTime: this.initTime,
                    criticalInitTime: criticalInitTime,
                    timestamp: Date.now()
                });
            }
        }, 0);
    }

    /**
     * 获取可用的预设配置列表
     * @returns {Object} 核心预设配置
     */
    static getAvailablePresets() {
        return {
            basic: '基础配置 - 适合90%的使用场景',
            secure: '安全优先 - 严格的文件验证和安全检查',
            performance: '性能优先 - 大文件、高并发、快速上传',
            enterprise: '企业级 - 安全与性能的平衡配置',
            debug: '调试模式 - 开发和调试时使用',
            custom: '自定义基础 - 提供自定义配置的起点'
        };
    }

    /**
     * 获取当前实例的配置信息
     * @param {boolean} includeDefaults - 是否包含默认值
     * @returns {Object} 配置信息对象
     */
    getConfigInfo(includeDefaults = false) {
        const result = {
            current: { ...this.options },
            validation: {
                rules: this._configManager.validationRules,
                lastValidated: true
            }
        };

        if (includeDefaults) {
            result.defaults = this._configManager.getDefaultOptions();
            result.differences = this._compareWithDefaults();
        }

        return result;
    }

    /**
     * 比较当前配置与默认配置的差异
     * @returns {Object} 差异对象
     * @private
     */
    _compareWithDefaults() {
        const defaults = this._configManager.getDefaultOptions();
        const current = this.options;
        const differences = {};

        for (const [key, defaultValue] of Object.entries(defaults)) {
            if (JSON.stringify(current[key]) !== JSON.stringify(defaultValue)) {
                differences[key] = {
                    default: defaultValue,
                    current: current[key]
                };
            }
        }

        return differences;
    }


    /**
     * 验证配置对象（静态方法）
     * @param {Object} config - 要验证的配置对象
     * @returns {Object} 验证结果
     */
    static validateConfig(config) {
        try {
            const tempElement = document.createElement('div');
            const instance = new DolphinUploader(tempElement, config);
            instance.destroy();
            
            return {
                valid: true,
                errors: [],
                warnings: []
            };
        } catch (error) {
            return {
                valid: false,
                errors: [error.message],
                warnings: []
            };
        }
    }

    /**
     * 调试输出（兼容性保留）
     * @param {...*} args - 调试参数
     */
    _debug(...args) {
        if (this._debugManager) {
            return this._debugManager._debug(...args);
        }
        // 后备：直接输出到控制台
        if (this.options.debug) {
            console.log('[DolphinUploader]', ...args);
        }
    }

    /**
     * 开始性能计时
     * @deprecated 性能监控方法，建议在开发调试时使用
     * @param {string} name - 计时器名称
     * @param {Object} context - 上下文信息
     */
    startTimer(name, context = {}) {
        if (this._performanceMonitor) {
            return this._performanceMonitor.startTimer(name, context);
        }
        return null;
    }

    /**
     * 结束性能计时
     * @deprecated 性能监控方法，建议在开发调试时使用
     * @param {string} name - 计时器名称
     * @param {Object} additionalContext - 额外上下文信息
     * @returns {Object} 性能指标
     */
    endTimer(name, additionalContext = {}) {
        if (this._performanceMonitor) {
            return this._performanceMonitor.endTimer(name, additionalContext);
        }
        return null;
    }

    /**
     * 增加计数器
     * @deprecated 性能监控方法，建议在开发调试时使用
     * @param {string} name - 计数器名称
     * @param {number} value - 增加的值
     * @param {Object} context - 上下文信息
     */
    incrementCounter(name, value = 1, context = {}) {
        if (this._performanceMonitor) {
            return this._performanceMonitor.incrementCounter(name, value, context);
        }
    }

    /**
     * 记录自定义指标
     * @deprecated 性能监控方法，建议在开发调试时使用
     * @param {string} name - 指标名称
     * @param {*} value - 指标值
     * @param {Object} context - 上下文信息
     */
    recordMetric(name, value, context = {}) {
        if (this._performanceMonitor) {
            return this._performanceMonitor.recordMetric(name, value, context);
        }
    }

    /**
     * 拍摄内存快照
     * @deprecated 性能监控方法，建议在开发调试时使用
     * @param {string} label - 快照标签
     */
    takeMemorySnapshot(label = 'snapshot') {
        if (this._performanceMonitor) {
            return this._performanceMonitor.takeMemorySnapshot(label);
        }
    }

    /**
     * 获取性能报告
     * @deprecated 性能监控方法，建议在开发调试时使用
     * @returns {Object} 性能报告
     */
    getPerformanceReport() {
        if (this._performanceMonitor) {
            return this._performanceMonitor.getReport();
        }
        return { error: 'Performance monitor not initialized' };
    }

    /**
     * 获取日志报告
     * @param {number} limit - 限制日志数量
     * @returns {Object} 日志报告
     */
    getLogReport(limit = 100) {
        if (this._debugManager) {
            return this._debugManager.getLogReport(limit);
        }
        return { error: 'Debug manager not initialized' };
    }

    // =====================================
    // API响应标准化
    // =====================================

    /**
     * 创建标准化的API响应
     * @param success - 操作是否成功
     * @param data - 响应数据
     * @param message - 响应消息
     * @param meta - 元数据
     * @returns {{data: null, success, message: null, timestamp: number}} 标准化的响应对象
     * @private
     */
    _createApiResponse(success, data = null, message = null, meta = {}) {
        const response = {
            success,
            data,
            message,
            timestamp: Date.now(),
            ...meta
        };

        // 清理null值
        Object.keys(response).forEach(key => {
            if (response[key] === null) {
                delete response[key];
            }
        });

        return response;
    }

    /**
     * 创建成功响应
     * @param data - 响应数据
     * @param message - 成功消息
     * @param meta - 元数据
     * @returns {{data: null, success, message: null, timestamp: number}} 成功响应
     * @private
     */
    _success(data = null, message = null, meta = {}) {
        return this._createApiResponse(true, data, message, meta);
    }

    /**
     * 创建错误响应
     * @param message - 错误消息
     * @param code - 错误代码
     * @param details - 错误详情
     * @returns {{data: null, success, message: null, timestamp: number}} 错误响应
     * @private
     */
    _error(message, code = null, details = null) {
        const meta = {};
        if (code) meta.code = code;
        if (details) meta.details = details;
        
        return this._createApiResponse(false, null, message, meta);
    }

    /**
     * 参数验证辅助方法
     * @param {*} value - 要验证的值
     * @param {string} type - 期望的类型
     * @param {string} name - 参数名称
     * @returns {Object|null} 验证失败时返回错误响应，成功时返回null
     * @private
     */
    _validateParam(value, type, name) {
        if (type === 'file' && !(value instanceof File)) {
            return this._error(`参数 ${name} 必须是File对象`, 'INVALID_PARAM_TYPE');
        }
        if (type === 'string' && typeof value !== 'string') {
            return this._error(`参数 ${name} 必须是字符串`, 'INVALID_PARAM_TYPE');
        }
        if (type === 'number' && typeof value !== 'number') {
            return this._error(`参数 ${name} 必须是数字`, 'INVALID_PARAM_TYPE');
        }
        if (type === 'boolean' && typeof value !== 'boolean') {
            return this._error(`参数 ${name} 必须是布尔值`, 'INVALID_PARAM_TYPE');
        }
        if (type === 'array' && !Array.isArray(value)) {
            return this._error(`参数 ${name} 必须是数组`, 'INVALID_PARAM_TYPE');
        }
        if (type === 'object' && (typeof value !== 'object' || value === null)) {
            return this._error(`参数 ${name} 必须是对象`, 'INVALID_PARAM_TYPE');
        }
        return null;
    }

    /**
     * 批量操作包装器
     * @param {Array} operations - 操作列表
     * @returns {Promise<Object>} 批量操作结果
     */
    async batch(operations) {
        if (this.destroyed) {
            return this._error('上传器实例已销毁', 'INSTANCE_DESTROYED');
        }

        const validation = this._validateParam(operations, 'array', 'operations');
        if (validation) {
            return validation;
        }

        const results = [];
        let successCount = 0;
        let errorCount = 0;

        for (const operation of operations) {
            try {
                const result = await operation();
                results.push(result);
                if (result && result.success) {
                    successCount++;
                } else {
                    errorCount++;
                }
            } catch (error) {
                const errorResult = this._error(
                    `批量操作中的操作失败: ${error.message}`,
                    'BATCH_OPERATION_FAILED',
                    { error: error.message }
                );
                results.push(errorResult);
                errorCount++;
            }
        }

        return this._success(
            { results },
            `批量操作完成: ${successCount} 个成功, ${errorCount} 个失败`,
            { 
                total: operations.length,
                successCount,
                errorCount,
                successRate: successCount / operations.length 
            }
        );
    }

    /**
     * 初始化上传器实例，创建UI并绑定事件。
     * @private
     */
    init() {
        // 记录初始化时间
        this.initTime = Date.now();

        this.initCustomUI();
        this.bindEvents();

        // 打印初始化信息
        if (this.options.debug) {
            console.log('DolphinUploader initialized in custom UI mode');
        }

        // 异步触发初始化完成事件，确保外部代码有机会添加事件监听器
        if (!this.destroyed) {
            setTimeout(() => {
                if (!this.destroyed) {
                    this.trigger(UPLOADER_EVENTS.INIT, {
                        mode: 'custom',
                        element: this.element,
                        fileInput: this.fileInput,
                        config: { ...this.options },
                        initTime: this.initTime,
                        timestamp: Date.now()
                    });
                }
            }, 0);
        }
    }

    /**
     * 在自定义UI模式下，初始化并配置现有的或自动创建的 input[type=file] 元素。
     * @private
     */
    initCustomUI() {
        // 防御性检查：确保element在初始化过程中没有被清空
        if (!this.element) {
            throw new Error('Element reference lost during initialization');
        }
        
        // 对于自定义UI模式，element本身就是input[type=file]或者是包含input的容器
        if (this.element.tagName === 'INPUT' && this.element.type === 'file') {
            this.fileInput = this.element;
            this.uploadArea = this.element;
        } else {
            // 查找container中的file input
            this.fileInput = this.element.querySelector('input[type="file"]');
            if (!this.fileInput) {
                // 自定义UI模式下如果没有input元素，自动创建一个隐藏的文件输入元素
                this.fileInput = this.createHiddenFileInput();
                this.element.appendChild(this.fileInput);

                if (this.options.debug) {
                    console.log('Auto-created hidden file input for custom UI mode');
                }
            } else {
                // 找到了已存在的input元素，更新其属性以匹配配置
                if (this.options.debug) {
                    console.log('Found existing file input for custom UI mode');
                }
            }
            this.uploadArea = this.element;
        }

        // 验证fileInput是否可用
        if (!this.fileInput || this.fileInput.disabled) {
            throw new Error('File input element is not available or disabled');
        }

        // 为自定义UI设置必要的属性
        if (this.options.multiple && !this.fileInput.hasAttribute('multiple')) {
            this.fileInput.setAttribute('multiple', '');
        }

        // 如果不支持多文件但设置了multiple属性，移除它
        if (!this.options.multiple && this.fileInput.hasAttribute('multiple')) {
            this.fileInput.removeAttribute('multiple');
        }

        const acceptTypes = this.getAcceptTypes();
        if (acceptTypes && !this.fileInput.hasAttribute('accept')) {
            this.fileInput.setAttribute('accept', acceptTypes);
        }

        // 确保文件输入支持所需的功能
        if (this.options.dragAndDrop && this.uploadArea.tagName === 'INPUT') {
            // 对于input元素，拖拽功能可能受限
            console.warn('Drag and drop may not work properly on input elements');
        }

        // fileList 和 uploadStats 在自定义UI模式下为null，用户需要自己处理UI
        this.fileList = null;
        this.uploadStats = null;

        if (this.options.debug) {
            console.log('DolphinUploader initialized in custom UI mode', {
                element: this.element.tagName,
                fileInput: this.fileInput.tagName,
                multiple: this.options.multiple,
                dragAndDrop: this.options.dragAndDrop
            });
        }
    }

    /**
     * 创建一个隐藏的 input[type=file] 元素，用于自定义UI模式下没有提供输入框的场景。
     * @returns {HTMLInputElement} 创建的文件输入元素。
     * @private
     */
    createHiddenFileInput() {
        // 创建隐藏的文件输入元素
        const fileInput = document.createElement('input');
        fileInput.type = 'file';
        fileInput.style.display = 'none';

        // 设置基本属性
        if (this.options.multiple) {
            fileInput.setAttribute('multiple', '');
        }

        // 设置accept属性
        const acceptTypes = this.getAcceptTypes();
        if (acceptTypes) {
            fileInput.setAttribute('accept', acceptTypes);
        }

        // 为了调试和识别，添加一个类名
        fileInput.className = 'dolphin-auto-created-input';

        // 添加标识属性，表明这是自动创建的
        fileInput.setAttribute('data-auto-created', 'true');

        return fileInput;
    }

    /**
     * 转义HTML字符串，防止XSS攻击。
     * @param {string} text - 需要转义的文本
     * @returns {string} 转义后的安全文本
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * 统一绑定所有必要的事件监听器。
     * @private
     */
    bindEvents() {
        // 绑定方法到this上下文
        this.handleAreaClick = this.handleAreaClick.bind(this);
        this.handleFileChange = this.handleFileChange.bind(this);
        this.handleKeydown = this.handleKeydown.bind(this);
        this.handlePaste = this.handlePaste.bind(this);
        this.preventDefaults = this.preventDefaults.bind(this);

        // 必须绑定的事件：文件选择
        this.fileInput.addEventListener('change', this.handleFileChange);

        // 自定义UI模式下的事件绑定
        this.bindCustomUIEvents();

        // 全局粘贴支持 - 使用清理队列管理
        const pasteCleanup = this.addGlobalEventListener(document, 'paste', this.handlePaste);
        this.eventCleanups.push(pasteCleanup);
    }

    /**
     * 在自定义UI模式下绑定事件。
     * @private
     */
    bindCustomUIEvents() {
        // 自定义UI模式下，只绑定必要的事件
        // 根据配置决定事件绑定的目标元素
        const eventTarget = this.getEventTarget();

        // 如果element不是input本身，可以选择性地绑定点击事件
        if (this.element !== this.fileInput) {
            // 可选：点击选择文件支持
            if (this.options.clickToSelect !== false) {
                eventTarget.addEventListener('click', this.handleAreaClick);
            }

            // 可选：键盘支持
            if (this.options.keyboardSupport !== false) {
                eventTarget.addEventListener('keydown', this.handleKeydown);
            }

            // 可选：拖拽支持
            if (this.options.dragAndDrop) {
                this.bindDragEvents(eventTarget);
            }
        }

        if (this.options.debug) {
            console.log('Custom UI events bound to:', eventTarget.tagName, {
                inputOnly: this.options.inputOnly,
                clickToSelect: this.options.clickToSelect,
                keyboardSupport: this.options.keyboardSupport,
                dragAndDrop: this.options.dragAndDrop
            });
        }
    }

    /**
     * 根据配置获取事件应该绑定到的目标DOM元素。
     * @returns {HTMLElement} 事件目标元素。
     * @private
     */
    getEventTarget() {
        // 在自定义UI模式下，如果配置了inputOnly，则将事件绑定到input元素
        if (this.options.inputOnly) {
            return this.fileInput;
        }

        // 否则使用默认的uploadArea（通常是容器元素）
        return this.uploadArea;
    }


    /**
     * 添加一个全局事件监听器，并返回一个用于清理该监听器的函数。
     * @param {EventTarget} element - 监听事件的元素 (如 document, window)。
     * @param {string} event - 事件名称。
     * @param {Function} handler - 事件处理函数。
     * @param {object} [options] - addEventListener的选项。
     * @returns {Function} 用于移除事件监听的清理函数。
     * @private
     */
    addGlobalEventListener(element, event, handler, options) {
        element.addEventListener(event, handler, options);
        return () => element.removeEventListener(event, handler, options);
    }

    /**
     * 处理上传区域的点击事件，触发文件选择框。
     * @param {MouseEvent} e - 点击事件对象。
     * @private
     */
    handleAreaClick(e) {
        if (e.target !== this.fileInput) {
            this.fileInput.click();
        }
    }

    /**
     * 处理文件输入框的 change 事件。
     * @param {Event} e - change事件对象。
     * @private
     */
    async handleFileChange(e) {
        try {
            await this.handleFiles(Array.from(e.target.files));
        } catch (error) {
            console.error('文件处理失败:', error);
            this.showError('文件处理失败: ' + error.message);
        } finally {
            e.target.value = ''; // 清空input，允许重复选择同一文件
        }
    }

    /**
     * 处理键盘事件，支持通过回车或空格键触发文件选择。
     * @param {KeyboardEvent} e - 键盘事件对象。
     * @private
     */
    handleKeydown(e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            this.fileInput.click();
        }
    }

    /**
     * 绑定拖拽上传相关的事件。
     * @param {HTMLElement} [targetElement=null] - 拖拽事件的目标元素。
     * @private
     */
    bindDragEvents(targetElement = null) {
        // 使用传入的目标元素，如果没有则使用uploadArea
        const dragTarget = targetElement || this.uploadArea;

        // 检查拖拽目标是否可用
        if (!dragTarget) {
            console.warn('Drag target element not available for drag events');
            return;
        }

        const dragEvents = ['dragenter', 'dragover', 'dragleave', 'drop'];

        // 本地拖拽事件
        dragEvents.forEach(eventName => {
            dragTarget.addEventListener(eventName, this.preventDefaults);
        });

        // 全局拖拽事件 - 使用清理队列
        dragEvents.forEach(eventName => {
            const cleanup = this.addGlobalEventListener(document.body, eventName, this.preventDefaults);
            this.eventCleanups.push(cleanup);
        });

        // 拖拽状态事件（只对非input元素添加视觉效果）
        const canAddVisualEffects = dragTarget.tagName !== 'INPUT';

        if (canAddVisualEffects) {
            ['dragenter', 'dragover'].forEach(eventName => {
                dragTarget.addEventListener(eventName, () => {
                    dragTarget.classList.add('drag-over');
                });
            });

            ['dragleave', 'drop'].forEach(eventName => {
                dragTarget.addEventListener(eventName, () => {
                    dragTarget.classList.remove('drag-over');
                });
            });
        }

        dragTarget.addEventListener('drop', async (e) => {
            const files = Array.from(e.dataTransfer.files);
            try {
                await this.handleFiles(files);
            } catch (error) {
                console.error('拖拽文件处理失败:', error);
                this.showError('拖拽文件处理失败: ' + error.message);
            }
        });

        if (this.options.debug) {
            console.log('Drag events bound to:', dragTarget.tagName, dragTarget);
        }
    }

    /**
     * 阻止事件的默认行为和冒泡。
     * @param {Event} e - 事件对象。
     * @private
     */
    preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    /**
     * 处理粘贴事件，允许从剪贴板粘贴文件。
     * @param {ClipboardEvent} e - 粘贴事件对象。
     * @private
     */
    async handlePaste(e) {
        // 检查焦点是否在上传组件内
        if (!this.element.contains(document.activeElement)) {
            return;
        }

        const items = Array.from(e.clipboardData.items);
        const files = items
            .filter(item => item.kind === 'file')
            .map(item => item.getAsFile())
            .filter(file => file);

        if (files.length > 0) {
            e.preventDefault();
            try {
                await this.handleFiles(files);
            } catch (error) {
                console.error('粘贴文件处理失败:', error);
                this.showError('粘贴文件处理失败: ' + error.message);
            }
        }
    }

    /**
     * 处理文件选择或拖拽的入口方法。
     * 负责文件过滤、验证，并启动上传流程。
     * @param {File[]} files - 用户选择或拖拽的文件列表。
     * @private
     */
    async handleFiles(files) {
        if (this.destroyed) return;

        // 预处理文件数组
        const fileArray = this._preprocessFiles(files);
        if (!fileArray) return;

        // 验证文件限制
        if (!this._validateFileLimits(fileArray)) return;

        // 验证并分类文件
        const { validFiles, invalidFiles } = await this._validateAndClassifyFiles(fileArray);

        // 处理验证错误
        this._handleFileValidationErrors(invalidFiles);

        if (validFiles.length === 0) {
            this.trigger('noValidFiles', invalidFiles);
            return;
        }

        // 创建和添加文件项
        const addedFiles = this._createAndAddFileItems(validFiles);

        // 启动上传流程
        this._initiateUploadProcess(addedFiles);

        // 触发事件
        if (addedFiles.length > 0) {
            this.trigger(UPLOADER_EVENTS.FILES_ADDED, validFiles, addedFiles);
        }
    }

    // =====================================
    // 文件处理相关方法
    // =====================================
    /**
     * 预处理文件列表
     * @param {FileList|File[]|null} files - 原始文件列表
     * @returns {File[]|null} 处理后的文件数组或null（如果无效）
     * @private
     */
    _preprocessFiles(files) {
        // 参数验证
        if (!files || files.length === 0) {
            return null;
        }

        // 确保files是数组
        return Array.isArray(files) ? files : Array.from(files);
    }

    /**
     * 验证文件数量限制
     * @param {File[]} fileArray - 文件数组
     * @returns {boolean} 是否通过验证
     * @private
     */
    _validateFileLimits(fileArray) {
        // 多文件支持检查
        if (!this.options.multiple && fileArray.length > 1) {
            this.showError('当前配置不支持多文件上传');
            return false;
        }

        // DOM项目数限制，防止性能问题
        const maxDOMItems = this.options.maxDomItems || 100;
        if (this.files.size + fileArray.length > maxDOMItems) {
            this.showError(`最多只能同时处理 ${maxDOMItems} 个文件`);
            return false;
        }

        // 最大文件数检查
        if (this.options.maxFiles && this.options.maxFiles !== 0 && this.options.maxFiles !== Infinity && this.files.size + fileArray.length > this.options.maxFiles) {
            this.showError(`最多只能上传 ${this.options.maxFiles} 个文件`);
            return false;
        }

        return true;
    }

    /**
     * 验证并分类文件
     * @param {File[]} fileArray - 文件数组
     * @returns {Promise<{validFiles: File[], invalidFiles: Array}>} 验证结果
     * @private
     */
    async _validateAndClassifyFiles(fileArray) {
        const validFiles = [];
        const invalidFiles = [];

        for (const file of fileArray) {
            // 基本类型检查
            if (!file || !(file instanceof File)) {
                invalidFiles.push({ file: file, error: '无效的文件对象' });
                continue;
            }

            try {
                const validation = await this.validateFile(file);
                if (validation.valid) {
                    validFiles.push(file);
                } else {
                    invalidFiles.push({ file: file, error: validation.error });
                }
            } catch (error) {
                invalidFiles.push({
                    file: file,
                    error: `验证失败: ${error.message}`
                });
            }
        }

        return { validFiles, invalidFiles };
    }

    /**
     * 处理文件验证错误
     * @param {Array} invalidFiles - 验证失败的文件列表
     * @private
     */
    _handleFileValidationErrors(invalidFiles) {
        invalidFiles.forEach(({ file, error }) => {
            const fileName = file && file.name ? this.escapeHtml(file.name) : '未知文件';
            this.showError(`文件 ${fileName}: ${error}`);
        });
    }

    /**
     * 创建并添加文件项
     * @param {File[]} validFiles - 验证通过的文件数组
     * @returns {Array} 添加的文件项列表
     * @private
     */
    _createAndAddFileItems(validFiles) {
        this.updateStats();
        const addedFiles = [];

        for (const file of validFiles) {
            const fileId = this.generateFileId();

            // 检查是否已存在相同文件
            if (this.files.has(fileId)) {
                this.showError(`文件 ${this.escapeHtml(file.name)} 已存在`);
                continue;
            }

            const fileItem = new DolphinFileUploadItem(file, fileId, this.options);
            this.files.set(fileId, fileItem);
            this.renderFileItem(fileItem);
            addedFiles.push(fileItem);

            if (this.options.autoUpload) {
                this.queueUpload(fileItem);
            }
        }

        return addedFiles;
    }

    /**
     * 启动上传流程
     * @param {Array} addedFiles - 已添加的文件项列表
     * @private
     */
    _initiateUploadProcess(addedFiles) {
        if (this.options.autoUpload && addedFiles.length > 0) {
            this.processQueue().catch(error => {
                console.error('自动上传队列处理失败:', error);
            });
        }
    }

    /**
     * 验证单个文件的合法性（大小、类型、文件名等）
     * 现在委托给内部FileValidator处理
     * @param {File} file - 待验证的文件对象
     * @returns {Promise<{valid: boolean, error?: string}>} 验证结果
     * @private
     */
    async validateFile(file) {
        return await this._fileValidator.validateFile(file, this.options);
    }

    /**
     * 检查文件名是否合法
     * 现在委托给内部SecurityChecker处理
     * @param {string} fileName - 文件名
     * @returns {boolean} 是否合法
     * @private
     */
    isValidFileName(fileName) {
        return this._securityChecker.isValidFileName(fileName);
    }


    /**
     * 检查文件类型是否合法
     * 现在委托给内部FileValidator处理
     * @param {File} file - 文件对象
     * @returns {boolean} 文件类型是否有效
     * @private
     */
    isValidFileType(file) {
        return this._fileValidator.isValidFileType(file, this.options);
    }

    /**
     * 为文件生成一个唯一的客户端ID。
     * @returns {string} 生成的唯一ID。
     * @private
     */
    generateFileId() {
        // 生成纯随机的唯一ID，不包含文件信息
        if (typeof crypto !== 'undefined' && crypto.randomUUID) {
            // 使用现代浏览器的 crypto.randomUUID() API
            return crypto.randomUUID();
        } else {
            // 回退方案：生成随机字符串
            const timestamp = Date.now().toString(36);
            const randomPart = Math.random().toString(36).substring(2, 15);
            const extraRandom = Math.random().toString(36).substring(2, 15);
            return `${timestamp}-${randomPart}-${extraRandom}`;
        }
    }

    /**
     * 渲染单个文件项，在自定义UI模式下触发事件通知。
     * @param {DolphinFileUploadItem} fileItem - 文件项。
     * @returns {null} 自定义UI模式下返回null。
     * @private
     */
    renderFileItem(fileItem) {
        // 自定义UI模式：不渲染DOM，而是触发事件让用户处理
        this.trigger(UPLOADER_EVENTS.FILE_ADDED, fileItem, {
            id: fileItem.id,
            file: fileItem.file,
            name: fileItem.file.name,
            size: fileItem.file.size,
            type: fileItem.file.type,
            status: fileItem.status,
            formattedSize: this.formatSize(fileItem.file.size),
            preview: this.options.showPreview ? this.generatePreviewData(fileItem.file) : null
        });
        return null; // 在自定义UI模式下不返回DOM元素
    }

    /**
     * 为自定义UI模式生成预览数据。
     * @param {File} file - 文件对象。
     * @returns {{type: string, url?: string, alt?: string, revoke?: Function, icon?: string}} 预览数据对象。
     * @private
     */
    generatePreviewData(file) {
        // 为自定义UI提供预览数据
        const previewLimit = this.options.previewSizeLimit || 5 * 1024 * 1024;
        if (file.type.startsWith('image/') && (previewLimit === 0 || file.size <= previewLimit)) {
            try {
                const url = URL.createObjectURL(file);
                const fileId = file.name + '_' + file.size + '_' + Date.now();
                
                // 记录URL以便后续清理，包含创建时间和文件信息
                this.objectUrls.set(fileId, {
                    url: url,
                    file: file,
                    createdAt: Date.now()
                });

                // 启动定时清理
                this._scheduleUrlCleanup();

                return {
                    type: 'image',
                    url: url,
                    alt: file.name,
                    // 提供清理方法
                    revoke: () => {
                        this._revokeObjectURL(fileId);
                    },
                    // 返回fileId供外部管理
                    fileId: fileId
                };
            } catch (error) {
                return {
                    type: 'icon',
                    icon: this.getFileIconForCustomUI(file)
                };
            }
        }
        return {
            type: 'icon',
            icon: this.getFileIconForCustomUI(file)
        };
    }

    /**
     * 为自定义UI模式获取文件的图标（emoji）。
     * @param {File} file - 文件对象。
     * @returns {string} 代表文件类型的emoji图标。
     * @private
     */
    getFileIconForCustomUI(file) {
        const iconMap = {
            'image': '🖼️',
            'video': '🎥',
            'audio': '🎵',
            'application/pdf': '📄',
            'application/msword': '📝',
            'application/vnd.ms-excel': '📊',
            'application/vnd.ms-powerpoint': '📽️',
            'application/zip': '📦',
            'text': '📄'
        };

        const type = file.type.split('/')[0];
        return iconMap[file.type] || iconMap[type] || '📄';
    }


    /**
     * 检查文件是否可以安全地生成预览。
     * @param {File} file - 文件对象。
     * @returns {boolean} 是否可以生成预览。
     * @private
     */
    canPreviewSafely(file) {
        const maxPreviewSize = this.options.previewSizeLimit || 5 * 1024 * 1024; // 预览大小限制
        return file.type.startsWith('image/') && (maxPreviewSize === 0 || file.size <= maxPreviewSize);
    }

    /**
     * 安全地释放单个ObjectURL
     * @param {string} fileId - 文件ID
     * @private
     */
    _revokeObjectURL(fileId) {
        const urlInfo = this.objectUrls.get(fileId);
        if (urlInfo) {
            try {
                URL.revokeObjectURL(urlInfo.url);
                this.objectUrls.delete(fileId);
                
                if (this.options.debug) {
                    console.log(`ObjectURL已释放: ${fileId}`);
                }
            } catch (error) {
                console.warn(`释放ObjectURL失败 [${fileId}]:`, error);
                // 即使释放失败也要从Map中删除
                this.objectUrls.delete(fileId);
            }
        }
    }

    /**
     * 调度ObjectURL自动清理任务
     * @private
     */
    _scheduleUrlCleanup() {
        // 避免重复调度
        if (this.urlCleanupTimer) {
            return;
        }

        this.urlCleanupTimer = setTimeout(() => {
            this._cleanupExpiredUrls();
            this.urlCleanupTimer = null;
            
            // 如果还有URL，继续调度清理
            if (this.objectUrls.size > 0) {
                this._scheduleUrlCleanup();
            }
        }, 30000); // 30秒后清理一次
    }

    /**
     * 清理过期的ObjectURL
     * @private
     */
    _cleanupExpiredUrls() {
        const now = Date.now();
        const maxAge = 5 * 60 * 1000; // 5分钟过期时间
        
        let cleanedCount = 0;
        
        for (const [fileId, urlInfo] of this.objectUrls.entries()) {
            if (now - urlInfo.createdAt > maxAge) {
                this._revokeObjectURL(fileId);
                cleanedCount++;
            }
        }
        
        if (cleanedCount > 0 && this.options.debug) {
            console.log(`自动清理了 ${cleanedCount} 个过期的ObjectURL`);
        }
    }

    /**
     * 立即清理所有ObjectURL
     * @private
     */
    _clearAllObjectUrls() {
        const urlCount = this.objectUrls.size;
        
        this.objectUrls.forEach((urlInfo, fileId) => {
            try {
                URL.revokeObjectURL(urlInfo.url);
            } catch (error) {
                console.warn(`清理ObjectURL失败 [${fileId}]:`, error);
            }
        });
        
        this.objectUrls.clear();
        
        // 清理定时器
        if (this.urlCleanupTimer) {
            clearTimeout(this.urlCleanupTimer);
            this.urlCleanupTimer = null;
        }
        
        if (urlCount > 0 && this.options.debug) {
            console.log(`清理了所有 ${urlCount} 个ObjectURL`);
        }
    }

    /**
     * 清理与特定文件相关的ObjectURL
     * @param {File} file - 文件对象
     * @private
     */
    _clearFileObjectUrls(file) {
        const filesToRemove = [];
        
        this.objectUrls.forEach((urlInfo, fileId) => {
            if (urlInfo.file === file) {
                filesToRemove.push(fileId);
            }
        });
        
        filesToRemove.forEach(fileId => {
            this._revokeObjectURL(fileId);
        });
        
        if (filesToRemove.length > 0 && this.options.debug) {
            console.log(`清理了文件 ${file.name} 相关的 ${filesToRemove.length} 个ObjectURL`);
        }
    }

    /**
     * 将一个文件项加入上传队列。
     * @param {DolphinFileUploadItem} fileItem - 文件项。
     * @private
     */
    queueUpload(fileItem) {
        fileItem.status = 'queued';
        this.uploadQueue.push(fileItem);
        this.updateFileStatus(fileItem, '排队中...');
    }

    /**
     * 处理上传队列，根据并发数限制来启动上传任务。
     * @private
     */
    async processQueue() {
        return await this._uploadManager.processQueue();
    }

    /**
     * 核心上传方法，管理单个文件的完整上传周期
     * 现在委托给UploadManager处理
     * @param {DolphinFileUploadItem} fileItem - 要上传的文件项
     * @private
     */
    async uploadFile(fileItem) {
        return await this._uploadManager.uploadFile(fileItem);
    }

    /**
     * 处理上传错误
     * @param fileItem - 文件项
     * @param error - 错误对象
     * @returns {Promise<void>}
     * @private
     */
    async _handleUploadError(fileItem, error) {
        if (fileItem && fileItem.__driverHandledError) {
            delete fileItem.__driverHandledError;
            return;
        }
        if (error.name === 'AbortError' || error.message.includes('上传已被') || error.message.includes('已取消')) {
            fileItem.status = 'cancelled';
            this.updateFileStatus(fileItem, '已取消');
            this.trigger(UPLOADER_EVENTS.UPLOAD_CANCELLED, fileItem);
        } else {
            await this.handleUploadError(fileItem, error);
        }
    }

    /**
     * 根据指定的策略（mode）为文件准备哈希值。
     * @param {DolphinFileUploadItem} fileItem - 文件项。
     * @param {string} mode - 哈希计算模式 ('fast', 'accurate', 'hybrid')。
     * @private
     */
    async prepareHashes(fileItem, mode) {
        const hashes = await this._hashCalculator.prepareHashes(
            fileItem.file, 
            mode, 
            (status) => this.updateFileStatus(fileItem, status)
        );
        
        fileItem.preliminaryHash = hashes.preliminaryHash;
        fileItem.hash = hashes.finalHash;
    }

    /**
     * 检查文件是否已在服务器上存在（用于秒传）。
     * @param {DolphinFileUploadItem} fileItem - 文件项。
     * @param {AbortSignal} signal - 用于中止请求的信号。
     * @param {boolean} [usePreliminary=false] - 是否使用采样哈希进行检查。
     * @returns {Promise<boolean>} 文件是否存在。
     * @private
     */
    async checkFileExists(fileItem, signal, usePreliminary = false) {
        try {
            const hashToCheck = usePreliminary ? fileItem.preliminaryHash : fileItem.hash;

            if (!hashToCheck) {
                return false;
            }
            this.updateFileStatus(fileItem, usePreliminary ? '检查采样哈希...' : '检查完整哈希...');
            const response = await fetch(`${this.options.endpoints.check}?hash=${encodeURIComponent(hashToCheck)}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                signal: signal
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();

            if (data.code === UPLOADER_CONSTANTS.HTTP_CODES.SUCCESS) {
                // 文件已存在 (秒传成功)，直接调用最终的成功处理器
                await this.handleUploadSuccess(fileItem, data);
                return true;
            }
        } catch (error) {
            if (error.name !== 'AbortError') {
                console.warn('检查文件是否存在失败:', error);
            }
        }

        return false;
    }

    /**
     * 执行直接（非分片）上传。
     * @param {DolphinFileUploadItem} fileItem - 文件项。
     * @param {AbortSignal} signal - 用于中止请求的信号。
     * @returns {Promise<object>} 上传成功后的服务器响应。
     * @private
     */
    async uploadDirect(fileItem, signal) {
        return await this._uploadManager.uploadDirect(fileItem, signal);
    }

    /**
     * 执行分片上传的完整流程
     * 现在委托给ChunkUploader处理
     * @param {DolphinFileUploadItem} fileItem - 文件项
     * @param {AbortSignal} signal - 用于中止请求的信号
     * @returns {Promise<object>} 服务器合并成功后返回的结果
     * @private
     */
    async uploadChunked(fileItem, signal) {
        return await this._chunkUploader.uploadChunked(fileItem, signal);
    }

    /**
     * 上传单个分片。
     * @param {DolphinFileUploadItem} fileItem - 文件项。
     * @param {Blob} chunk - 分片数据。
     * @param {number} index - 分片索引。
     * @param {number} total - 总分片数。
     * @param {AbortSignal} signal - 用于中止请求的信号。
     * @returns {Promise<object>} 分片上传成功的结果。
     * @private
     */
    async uploadChunk(fileItem, chunk, index, total, signal) {
        const formData = new FormData();
        formData.append('chunk', chunk);
        formData.append('chunkIndex', index);
        formData.append('totalChunks', total);
        formData.append('fileHash', fileItem.hash);
        formData.append('fileName', fileItem.file.name);

        // 🆕 处理extraData参数（分片上传也支持自定义参数）
        const extraDataParams = await this.processExtraData(fileItem.file, fileItem);
        this.safeAppendToFormData(formData, extraDataParams, 'extraData');

        let retryCount = 0;
        while (retryCount < this.options.retries) {
            try {
                const response = await fetch(this.options.endpoints.chunk, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    signal: signal
                });

                if (!response.ok) {
                    throw new Error(`分片上传失败: ${response.status}`);
                }

                const result = await response.json();
                if (result.code !== UPLOADER_CONSTANTS.HTTP_CODES.SUCCESS) {
                    throw new Error(result.msg || '分片上传失败');
                }

                return result;

            } catch (error) {
                retryCount++;
                if (retryCount >= this.options.retries) {
                    throw error;
                }

                // 等待后重试
                await new Promise(resolve => setTimeout(resolve, this.options.retryDelay * retryCount));
            }
        }
    }

    /**
     * 请求合并服务器上的所有分片。
     * @param {DolphinFileUploadItem} fileItem - 文件项。
     * @param {AbortSignal} signal - 用于中止请求的信号。
     * @returns {Promise<object>} 合并成功的结果。
     * @throws {Error} 如果合并失败，抛出包含服务器响应的错误。
     * @private
     */
    async mergeChunks(fileItem, signal) {
        const response = await fetch(this.options.endpoints.merge, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                fileHash: fileItem.hash,
                fileName: fileItem.file.name,
                totalChunks: fileItem.totalChunks,
                extraData: await this.processExtraData(fileItem.file, fileItem)
            }),
            signal: signal
        });

        if (!response.ok) {
            let errorData;
            try {
                errorData = await response.json();
            } catch (e) {
                const err = new Error(`分片合并失败: ${response.status} ${response.statusText}`);
                err.response = null; // No JSON body
                throw err;
            }
    
            const err = new Error(errorData.msg || `分片合并失败: ${response.status}`);
            err.response = errorData; // Attach parsed JSON body
            throw err;
        }
    
        const result = await response.json();
        // 也检查业务逻辑错误
        if (result.code !== UPLOADER_CONSTANTS.HTTP_CODES.SUCCESS) {
            const err = new Error(result.msg || '分片合并失败');
            err.response = result;
            throw err;
        }
    
        return result;
    }

    /**
     * 带 "自愈" 功能的分片合并方法。
     * 它会尝试合并分片，如果后端返回分片缺失的特定错误，
     * 它会自动重新上传缺失的分片，然后再次尝试合并。
     * @param {DolphinFileUploadItem} fileItem - 文件项。
     * @param {AbortSignal} signal - 用于中止请求的信号。
     * @param {number} [maxRetries=3] - 最大重试次数。
     * @returns {Promise<object>} 最终合并成功的结果。
     * @private
     */
    async mergeChunksWithRetry(fileItem, signal, maxRetries = 3) {
        let attempt = 0;
        while (attempt < maxRetries) {
            attempt++;
            try {
                return await this.mergeChunks(fileItem, signal);
            } catch (error) {
                const isMissingChunkError = error.response &&
                    error.response.code === 409 &&
                    Array.isArray(error.response.data?.missing_chunks) &&
                    error.response.data.missing_chunks.length > 0;
    
                if (isMissingChunkError) {
                    const missingChunks = error.response.data.missing_chunks;
                    
                    if (attempt >= maxRetries) {
                        throw new Error(`分片合并失败，缺失的分片无法修复: ${missingChunks.join(', ')}`);
                    }
    
                    console.warn(`检测到缺失分片: ${missingChunks.join(', ')}。第 ${attempt} 次尝试重新上传...`);
                    this.updateFileStatus(fileItem, `修复分片: ${missingChunks.join(', ')}...`);
    
                    await this.reuploadMissingChunks(fileItem, missingChunks, signal);
                } else {
                    throw error;
                }
            }
        }
        throw new Error('分片合并失败，已达到最大重试次数');
    }

    /**
     * 重新上传指定索引的缺失分片。
     * @param {DolphinFileUploadItem} fileItem - 文件项。
     * @param {number[]} missingChunks - 缺失分片的索引数组。
     * @param {AbortSignal} signal - 用于中止请求的信号。
     * @private
     */
    async reuploadMissingChunks(fileItem, missingChunks, signal) {
        const uploadPromises = [];
        const file = fileItem.file;
        const chunkSize = this.options.chunkSize;
        const totalChunks = fileItem.totalChunks;
    
        for (const chunkIndex of missingChunks) {
            if (signal && signal.aborted) {
                throw new Error('上传已取消');
            }
    
            const start = chunkIndex * chunkSize;
            const end = Math.min(start + chunkSize, file.size);
            const chunk = file.slice(start, end);
    
            uploadPromises.push(this.uploadChunk(fileItem, chunk, chunkIndex, totalChunks, signal));
        }
    
        await Promise.all(uploadPromises);
    }

    /**
     * 执行紧急内存清理
     */
    performEmergencyCleanup() {
        return this._memoryManager.performEmergencyCleanup();
    }

    /**
     * 执行温和的内存清理
     */
    performGentleCleanup() {
        return this._memoryManager.performGentleCleanup();
    }



    /**
     * 获取内存监控报告
     * @deprecated 内存监控方法，建议使用getHealthStatus()获取整体状态
     * @returns {Object} 内存报告
     */
    getMemoryReport() {
        return this._memoryManager.getReport();
    }

    /**
     * 使用 XMLHttpRequest 实现的带进度回调的fetch-like方法。
     * @param {string} url - 请求URL。
     * @param {object} options - 请求选项，类似fetch。
     * @param {Function} onProgress - 进度回调函数。
     * @returns {Promise<XMLHttpRequest>} 完成后的XHR对象。
     * @private
     */
    async fetchWithProgress(url, options, onProgress) {
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();

            // 处理AbortController信号
            if (options.signal) {
                options.signal.addEventListener('abort', () => {
                    xhr.abort();
                    const error = new Error('Upload cancelled');
                    error.name = 'AbortError';
                    reject(error);
                });
            }

            xhr.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable) {
                    const progress = (e.loaded / e.total) * 100;
                    onProgress(progress);
                }
            });

            xhr.addEventListener('load', () => {
                const ok = xhr.status >= 200 && xhr.status < 300;

                xhr.ok = ok;
                xhr.json = async () => {
                    const text = xhr.responseText;
                    if (!text) {
                        return {};
                    }
                    try {
                        return JSON.parse(text);
                    } catch (error) {
                        return {};
                    }
                };
                xhr.text = async () => xhr.responseText || '';

                resolve(xhr);
            });

            xhr.addEventListener('error', () => {
                reject(new Error('Network error'));
            });

            xhr.addEventListener('abort', () => {
                const error = new Error('Upload cancelled');
                error.name = 'AbortError';
                reject(error);
            });

            xhr.addEventListener('timeout', () => {
                reject(new Error('Request timeout'));
            });

            xhr.open(options.method || 'GET', url);

            if (options.headers) {
                Object.keys(options.headers).forEach(key => {
                    xhr.setRequestHeader(key, options.headers[key]);
                });
            }

            xhr.send(options.body);
        });
    }

    /**
     * 更新文件项的上传进度，并更新UI。
     * @param {DolphinFileUploadItem} fileItem - 文件项。
     * @param {number} progress - 进度百分比 (0-100)。
     * @private
     */
    updateProgress(fileItem, progress) {
        if (!fileItem) return;
        
        // 更新内部进度状态（无节流）
        fileItem.progress = progress;
        
        // 使用统一的节流管理器（如果可用）
        if (this._throttleManager) {
            this._throttleManager.throttle(
                'progress', 
                fileItem.id, 
                (context) => this._doProgressUpdate(context.fileItem, context.progress, Date.now()),
                { 
                    fileItem, 
                    progress, 
                    status: fileItem.status 
                }
            );
        } else {
            // 后备：直接更新
            this._doProgressUpdate(fileItem, progress, Date.now());
        }
    }

    /**
     * 执行实际的进度更新操作
     * @param {DolphinFileUploadItem} fileItem - 文件项
     * @param {number} progress - 进度百分比
     * @param {number} now - 当前时间戳
     * @private 
     */
    _doProgressUpdate(fileItem, progress, now) {
        // 计算上传速度和剩余时间
        let speedInfo = null;
        if (fileItem.startTime && progress > 0) {
            const elapsed = (now - fileItem.startTime) / 1000; // 秒
            const uploadedBytes = (progress / 100) * fileItem.file.size;
            const speed = uploadedBytes / elapsed; // bytes/sec
            const speedText = this.formatSpeed(speed);

            speedInfo = {
                speed: speed,
                speedText: speedText,
                uploadedBytes: uploadedBytes
            };

            // 估计剩余时间
            if (progress < 100 && speed > 0) {
                const remainingBytes = fileItem.file.size - uploadedBytes;
                const remainingTime = remainingBytes / speed;
                speedInfo.remainingTime = remainingTime;
                speedInfo.remainingTimeText = this.formatTime(remainingTime);
            }
        }

        // 触发进度事件
        this.trigger(UPLOADER_EVENTS.UPLOAD_PROGRESS, fileItem, {
            id: fileItem.id,
            progress: progress,
            progressText: `${Math.round(progress)}%`,
            speedInfo: speedInfo
        });

        // 触发统计数据更新（使用统一节流系统）
        this._scheduleStatsUpdate();
    }


    /**
     * 更新文件项的状态文本，并更新UI。
     * @param {DolphinFileUploadItem} fileItem - 文件项。
     * @param {string} status - 状态文本。
     * @param {object} [additionalData={}] - 额外数据。
     * @private
     */
    updateFileStatus(fileItem, status, additionalData = {}) {
        if (!fileItem) {
            console.warn('updateFileStatus: fileItem is null or undefined');
            return;
        }
        
        // 检查状态是否发生变化
        const statusChanged = fileItem.status !== status;
        if (statusChanged) {
            // 状态改变时触发统计更新
            this._scheduleStatsUpdate();
        }

        // 安全地获取状态文本
        const statusText = typeof status === 'string' ? status : String(status || '');

        // 自定义UI模式：触发状态事件
        this.trigger('fileStatusChanged', fileItem, {
            id: fileItem.id,
            status: fileItem.status,
            statusText: statusText,
            fileName: fileItem.file ? fileItem.file.name : '',
            ...additionalData
        });
    }

    /**
     * 检查实例是否已销毁
     * @private
     */
    _checkDestroyed() {
        if (this.destroyed) {
            throw new Error('Uploader instance has been destroyed');
        }
    }

    /**
     * HTTP上传成功后的内部处理函数。
     * @param {DolphinFileUploadItem} fileItem - 文件项。
     * @param {object} result - 服务器返回的初步结果。
     * @private
     */
    async _onHttpSuccess(fileItem, result) {
        // 更新状态为"处理中"，等待驱动的success钩子回调
        fileItem.status = 'processing';
        this.updateFileStatus(fileItem, '处理中...');
    
        // 触发内部事件，交由UploadDriverAdapter处理
        this.trigger(UPLOADER_EVENTS.POST_PROCESS, fileItem, result);
    }

    /**
     * 统一处理上传结束（成功或失败）的私有方法
     * @param {DolphinFileUploadItem} fileItem - 文件项
     * @param {string} status - 最终状态 ('success' 或 'error')
     * @param {object} data - 成功时的结果或失败时的错误对象
     * @private
     */
    _finalizeUpload(fileItem, status, data) {
        fileItem.status = status;
        fileItem.completeTime = Date.now();
    
        if (status === 'success') {
            fileItem.result = data;
            if (fileItem.element) {
                fileItem.element.classList.remove('upload-error');
                fileItem.element.classList.add('upload-success');
                this.updateFileStatus(fileItem, '上传成功');

                // 将"取消"按钮变为"移除"按钮
                const cancelBtn = fileItem.element.querySelector('.btn-cancel');
                if (cancelBtn) {
                    // 克隆按钮以移除旧的事件监听器
                    const removeBtn = cancelBtn.cloneNode(true);
                    removeBtn.title = '移除';
                    removeBtn.innerHTML = `
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                            <line x1="10" y1="11" x2="10" y2="17"></line>
                            <line x1="14" y1="11" x2="14" y2="17"></line>
                        </svg>
                    `;
                    // 绑定新的点击事件以移除文件
                    removeBtn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        this.removeFile(fileItem.id);
                    });
                    // 替换旧按钮并显示
                    cancelBtn.parentNode.replaceChild(removeBtn, cancelBtn);
                    removeBtn.style.display = 'inline-flex';
                }
            }
            this.trigger(UPLOADER_EVENTS.UPLOAD_SUCCESS, fileItem, data);
        } else { // 'error'
            fileItem.error = data;
            if (fileItem.element) {
                fileItem.element.classList.remove('upload-success');
                fileItem.element.classList.add('upload-error');
                this.updateFileStatus(fileItem, data.message || '上传失败');
                const retryBtn = fileItem.element.querySelector('.btn-retry');
                if (retryBtn) retryBtn.style.display = 'inline-flex';
            }
            // this.showError(`文件 ${fileItem.file.name} 上传失败: ${data.message}`);
            this.trigger(UPLOADER_EVENTS.UPLOAD_ERROR, fileItem, data);
        }
    
        this.updateStats();
    }

    /**
     * 最终处理上传成功的回调。
     * @param {DolphinFileUploadItem} fileItem - 文件项。
     * @param {object} driverResult - 驱动 `success` 钩子返回的结果。
     */
    async handleUploadSuccess(fileItem, driverResult) {
        this._finalizeUpload(fileItem, 'success', driverResult);
    }

    /**
     * 处理上传失败的回调。
     * @param {DolphinFileUploadItem} fileItem - 文件项。
     * @param {Error} error - 错误对象。
     */
    async handleUploadError(fileItem, error) {
        // 终极备用哈希方案
        if (error.message.includes('哈希计算失败')) {
            fileItem.hash = `fallback-${fileItem.file.name}-${fileItem.file.size}-${fileItem.file.lastModified}-${Date.now()}`;
        }

        this._finalizeUpload(fileItem, 'error', error);
    }

    /**
     * 重试一个失败的上传任务。
     * @param {string} fileId - 文件ID。
     */
    async retryUpload(fileId) {
        const fileItem = this.files.get(fileId);
        if (!fileItem) return;

        // 重置状态
        fileItem.status = 'pending';
        fileItem.error = null;

        if (fileItem.element) {
            fileItem.element.classList.remove('upload-error');
            const retryBtn = fileItem.element.querySelector('.btn-retry');
            if (retryBtn) {
                retryBtn.style.display = 'none';
            }
        }

        // 重新加入队列
        this.queueUpload(fileItem);
        this.processQueue().catch(error => {
            console.error('重试队列处理失败:', error);
        });
    }

    /**
     * 取消一个上传任务。
     * - 如果任务未开始，则直接从列表移除。
     * - 如果任务正在进行，则中止请求并标记为已取消。
     * @param {string} fileId - 文件ID。
     */
    cancelUpload(fileId) {
        const fileItem = this.files.get(fileId);
        if (!fileItem) return;

        // 新增逻辑：如果文件还未开始上传（处于等待或排队状态），则直接将其移除
        if (['pending', 'queued'].includes(fileItem.status)) {
            this.removeFile(fileId);
            return;
        }

        // 保留原有逻辑：对于正在上传或已出错的文件，中止其操作并标记为"已取消"

        // 如果文件正在上传，则中止请求
        const abortController = this.abortControllers.get(fileId);
        if (abortController) {
            abortController.abort();
            // AbortController的abort事件会触发uploadFile中的catch块，
            // 它会将状态设置为 'cancelled' 并更新UI。
            return;
        }

        // 如果不在上传中（例如，上传出错后），直接设置状态
        fileItem.status = 'cancelled';

        // 从上传队列中移除（如果存在）
        const queueIndex = this.uploadQueue.indexOf(fileItem);
        if (queueIndex > -1) {
            this.uploadQueue.splice(queueIndex, 1);
        }

        // 更新UI状态
        if (fileItem.element) {
            this.updateFileStatus(fileItem, '已取消');
            fileItem.element.classList.add('upload-cancelled');
            
            // 如果是从错误状态取消的，需要隐藏重试按钮
            const retryBtn = fileItem.element.querySelector('.btn-retry');
            if (retryBtn) {
                retryBtn.style.display = 'none';
            }
        }

        // 触发取消事件并更新统计信息
        this.trigger(UPLOADER_EVENTS.UPLOAD_CANCELLED, fileItem);
        this.updateStats();
    }

    /**
     * 更新并（可选地）渲染统计信息。
     * @private
     */
    /**
     * 调度统计数据更新（使用统一节流系统）
     * @private
     */
    _scheduleStatsUpdate() {
        this._throttleManager.throttle(
            'stats',
            'update',
            () => this._doStatsUpdate(),
            {}
        );
    }

    /**
     * 执行实际的统计更新（使用ThrottleManager缓存）
     * @private
     */
    _doStatsUpdate() {
        // 使用ThrottleManager的缓存功能
        const stats = this._throttleManager.throttleWithCache(
            'stats',
            () => this._calculateStats(),
            {}
        );
        
        // 触发统计更新事件
        this.trigger(UPLOADER_EVENTS.STATS_UPDATED, stats);
    }

    /**
     * 计算文件统计信息
     * @returns {Object} 统计数据
     * @private
     */
    _calculateStats() {
        const stats = {
            total: this.files.size,
            success: 0,
            error: 0,
            uploading: 0,
            pending: 0,
            cancelled: 0
        };

        this.files.forEach(fileItem => {
            switch (fileItem.status) {
                case 'success':
                    stats.success++;
                    break;
                case 'error':
                    stats.error++;
                    break;
                case 'uploading':
                    stats.uploading++;
                    break;
                case 'cancelled':
                    stats.cancelled++;
                    break;
                default:
                    stats.pending++;
            }
        });

        return stats;
    }

    updateStats() {
        // 标记缓存为脏数据并调度更新
        this._scheduleStatsUpdate();
    }

    /**
     * 格式化文件大小 (e.g., 1024 -> "1 KB")。
     * @param {number} bytes - 字节数。
     * @returns {string} 格式化后的大小字符串。
     * @private
     */
    formatSize(bytes) {
        if (bytes === 0) return '0 B';
        const k = UPLOADER_CONSTANTS.SIZE_UNITS.BYTES_PER_KB;
        const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    /**
     * 格式化上传速度 (e.g., 1024 -> "1 KB/s")。
     * @param {number} bytesPerSecond - 每秒字节数。
     * @returns {string} 格式化后的速度字符串。
     * @private
     */
    formatSpeed(bytesPerSecond) {
        return this.formatSize(bytesPerSecond) + '/s';
    }

    /**
     * 格式化时间 (e.g., 125 -> "2分钟")。
     * @param {number} seconds - 秒数。
     * @returns {string} 格式化后的时间字符串。
     * @private
     */
    formatTime(seconds) {
        if (seconds < 60) {
            return `${Math.round(seconds)}秒`;
        } else if (seconds < 3600) {
            return `${Math.round(seconds / 60)}分钟`;
        } else {
            return `${Math.round(seconds / 3600)}小时`;
        }
    }

    _normalizeSizeOptions(options) {
        if (!options || !this._configManager) {
            return;
        }

        const sizeKeys = ['maxFileSize', 'chunkSize', 'largeFileThreshold', 'previewSizeLimit'];

        sizeKeys.forEach(key => {
            if (options[key] !== undefined && options[key] !== null) {
                try {
                    options[key] = this._configManager.parseFileSize(options[key]);
                } catch (error) {
                    console.warn(`Invalid ${key} value: ${options[key]}.`, error);
                }
            }
        });
    }

    /**
     * 将允许的类型配置规范化为数组
     * @param {string|Array|undefined} rawAllowedTypes - 原始配置
     * @returns {Array<string>} 规范化后的数组
     * @private
     */
    _resolveAllowedTypes(rawAllowedTypes = this.options?.allowedTypes) {
        const normalizer = (value) => {
            if (this._configManager && typeof this._configManager._normalizeAllowedTypes === 'function') {
                return this._configManager._normalizeAllowedTypes(value ?? []);
            }

            if (Array.isArray(value)) {
                return value;
            }

            if (typeof value === 'string') {
                const normalized = value
                    .split(',')
                    .map(item => item.trim())
                    .filter(item => item.length > 0)
                    .map(item => item.replace(/\s+/g, '').toLowerCase())
                    .map(item => {
                        let cleaned = item.replace(/^\*+/, '');
                        if (cleaned.startsWith('.')) {
                            cleaned = cleaned.slice(1);
                        }
                        return cleaned;
                    })
                    .filter(item => item.length > 0);

                const unique = [];
                const seen = new Set();
                normalized.forEach(item => {
                    if (!seen.has(item)) {
                        seen.add(item);
                        unique.push(item);
                    }
                });
                return unique;
            }

            return [];
        };

        const normalized = normalizer(rawAllowedTypes);
        return Array.isArray(normalized) ? normalized : [];
    }

    /**
     * 获取用于UI提示的文件类型字符串。
     * @returns {string} 提示文本。
     * @private
     */
    getTypeHint() {
        const allowedTypes = this._resolveAllowedTypes();

        if (allowedTypes.includes('*')) {
            return '所有文件类型';
        }

        const typeNames = {
            'image/*': '图片',
            'video/*': '视频',
            'audio/*': '音频',
            'text/*': '文本文件',
            'application/pdf': 'PDF',
            'application/msword': 'Word文档',
            'application/vnd.ms-excel': 'Excel表格'
        };

        const hints = allowedTypes.map(type =>
            typeNames[type] || type.replace('*', '所有类型')
        );

        return hints.join(', ');
    }

    /**
     * 根据 allowedTypes 生成用于 input[accept] 属性的字符串。
     * @returns {string} accept属性值。
     * @private
     */
    getAcceptTypes() {
        const allowedTypes = this._resolveAllowedTypes();

        if (allowedTypes.includes('*') || allowedTypes.includes('*/*')) {
            return '*/*';
        }

        return allowedTypes.map(type => {
            // 如果不包含'/'，则认为是文件后缀，需要加上点
            if (!type.includes('/')) {
                return '.' + type.replace(/^\./, '');
            }
            return type;
        }).join(',');
    }

    /**
     * 显示一个全局错误提示。
     * @param {string} message - 错误消息。
     * @private
     */
    showError(message) {
        if (window.Dolphin && typeof window.Dolphin.error === 'function') {
            window.Dolphin.error(message);
        } else {
            console.error(message);
        }
    }

    /**
     * 触发一个自定义事件，并调用对应的 onEventName 回调。
     * @param {string} eventName - 事件名称。
     * @param {...any} args - 传递给事件和回调的参数。
     * @returns {CustomEvent} 派发的事件对象。
     */
    trigger(eventName, ...args) {
        if (this._eventEmitter) {
            return this._eventEmitter.trigger(eventName, ...args);
        }
        
        // 后备：直接创建事件对象（关键初始化阶段）
        const event = new CustomEvent(eventName, {
            detail: args,
            bubbles: true,
            cancelable: true
        });
        
        if (this.element && typeof this.element.dispatchEvent === 'function') {
            this.element.dispatchEvent(event);
        }
        
        return event;
    }

    /**
     * 异步触发事件，等待所有事件监听器完成
     * @param {string} eventName - 事件名
     * @param {...*} args - 事件参数
     * @returns {Promise<void>}
     */
    async triggerAsync(eventName, ...args) {
        return await this._eventEmitter.triggerAsync(eventName, ...args);
    }

    /**
     * 将事件名（如 'uploader:filesAdded'）转换为回调函数名（如 'onFilesAdded'）
     * @param {string} eventName - The event name.
     * @returns {string} The callback function name.
     */
    eventNameToCallbackName(eventName) {
        return this._eventEmitter.eventNameToCallbackName(eventName);
    }

    /**
     * 开始上传所有处于待定状态的文件。
     */
    upload() {
        this._checkDestroyed();
        
        // 🆕 触发批量上传前事件（可阻止上传）
        const pendingFiles = Array.from(this.files.values()).filter(fileItem => fileItem.status === 'pending');
        
        if (pendingFiles.length === 0) {
            console.warn('没有待上传的文件');
            return;
        }
        
        const beforeBatchUploadEvent = new CustomEvent(UPLOADER_EVENTS.BEFORE_BATCH_UPLOAD, {
            detail: [pendingFiles, pendingFiles.map(item => item.file)],
            bubbles: true,
            cancelable: true
        });
        
        this.element.dispatchEvent(beforeBatchUploadEvent);
        
        // 如果事件被阻止，则不继续上传
        if (beforeBatchUploadEvent.defaultPrevented) {
            this.trigger(UPLOADER_EVENTS.BATCH_UPLOAD_CANCELLED, pendingFiles);
            return;
        }
        
        // 🆕 触发批量上传开始事件（不可阻止）
        this.trigger(UPLOADER_EVENTS.BATCH_UPLOAD_START, pendingFiles, pendingFiles.map(item => item.file));
        
        this.files.forEach(fileItem => {
            if (fileItem.status === 'pending') {
                this.queueUpload(fileItem);
            }
        });
        this.processQueue().catch(error => {
            console.error('恢复上传队列处理失败:', error);
        });
    }

    /**
     * 清空所有文件和状态，并重置UI。
     */
    clear() {
        this._checkDestroyed();

        // 取消所有正在进行的上传
        this.abortControllers.forEach(controller => {
            controller.abort();
        });
        this.abortControllers.clear();

        // 清理ObjectURL，防止内存泄漏
        this._clearAllObjectUrls();

        this.files.clear();
        this.uploadQueue = [];
        this.updateStats();
    }

    /**
     * 获取所有文件项的数组。
     * @returns {DolphinFileUploadItem[]}
     */
    getFiles() {
        this._checkDestroyed();
        return Array.from(this.files.values());
    }

    /**
     * 获取所有上传成功的文件项。
     * @returns {DolphinFileUploadItem[]}
     */
    getSuccessfulFiles() {
        this._checkDestroyed();
        return this.getFiles().filter(item => item.status === 'success');
    }

    /**
     * 获取所有上传成功文件的服务器返回结果。
     * @returns {object[]}
     */
    getResults() {
        this._checkDestroyed();
        return this.getSuccessfulFiles().map(item => item.result);
    }

    /**
     * 获取指定状态的文件。
     * @param {string} status - 文件状态：pending/queued/uploading/success/error/cancelled。
     * @returns {DolphinFileUploadItem[]} 文件项数组。
     */
    getFilesByStatus(status) {
        this._checkDestroyed();
        return this.getFiles().filter(item => item.status === status);
    }

    /**
     * 根据ID获取文件项。
     * @param {string} fileId - 文件ID。
     * @returns {DolphinFileUploadItem|null} 文件项或null。
     */
    getFileById(fileId) {
        this._checkDestroyed();
        return this.files.get(fileId) || null;
    }

    /**
     * 检查是否有正在上传的文件
     * @returns {boolean}
     */
    isUploading() {
        try {
            this._checkDestroyed();
            return this.getFilesByStatus('uploading').length > 0;
        } catch {
            return false;
        }
    }

    /**
     * 检查是否所有文件都已上传完成
     * @returns {boolean}
     */
    isAllCompleted() {
        try {
            this._checkDestroyed();
            const files = this.getFiles();
            if (files.length === 0) return true;

            return files.every(file =>
                file.status === 'success' ||
                file.status === 'error' ||
                file.status === 'cancelled'
            );
        } catch {
            return true;
        }
    }

    /**
     * 获取上传进度统计
     * @returns {Object} 包含总体进度信息
     */
    getOverallProgress() {
        this._checkDestroyed();
        const files = this.getFiles();

        if (files.length === 0) {
            return { progress: 0, completed: 0, total: 0 };
        }

        const completed = files.filter(f => f.status === 'success').length;
        const total = files.length;
        const progress = (completed / total) * 100;

        return { progress, completed, total };
    }

    /**
     * 移除指定文件
     * @param {string} fileId - 文件ID
     * @returns {Object} 标准化的API响应
     */
    removeFile(fileId) {
        // 检查实例状态
        if (this.destroyed) {
            return this._error('上传器实例已销毁', 'INSTANCE_DESTROYED');
        }

        // 参数验证
        const validation = this._validateParam(fileId, 'string', 'fileId');
        if (validation) {
            return validation;
        }

        const fileItem = this.files.get(fileId);
        if (!fileItem) {
            return this._error('文件不存在', 'FILE_NOT_FOUND', { fileId });
        }

        try {
            const fileName = fileItem.file.name;
            const fileSize = fileItem.file.size;
            const fileStatus = fileItem.status;

            // 如果正在上传，先取消
            if (fileItem.status === 'uploading') {
                this.cancelUpload(fileId);
            }

            // 从队列中移除
            const queueIndex = this.uploadQueue.indexOf(fileItem);
            if (queueIndex > -1) {
                this.uploadQueue.splice(queueIndex, 1);
            }

            // 清理该文件相关的ObjectURL
            this._clearFileObjectUrls(fileItem.file);

            // 从文件列表中移除
            this.files.delete(fileId);

            // 如果有DOM元素，移除它
            if (fileItem.element && fileItem.element.parentNode) {
                fileItem.element.parentNode.removeChild(fileItem.element);
            }

            // 更新统计
            this.updateStats();

            // 触发文件移除事件
            this.trigger(UPLOADER_EVENTS.FILE_REMOVED, fileItem);

            return this._success(
                { fileId, fileName, fileSize },
                '文件移除成功',
                { 
                    previousStatus: fileStatus,
                    remainingFiles: this.files.size 
                }
            );
        } catch (error) {
            return this._error(
                '文件移除失败',
                'REMOVE_FILE_FAILED',
                { fileId, error: error.message }
            );
        }
    }

    /**
     * 添加单个文件
     * @param {File} file - 文件对象
     * @returns {Promise<Object>} 标准化的API响应
     */
    async addFile(file) {
        // 检查实例状态
        if (this.destroyed) {
            return this._error('上传器实例已销毁', 'INSTANCE_DESTROYED');
        }

        // 参数验证
        const validation = this._validateParam(file, 'file', 'file');
        if (validation) {
            return validation;
        }

        try {
            await this.handleFiles([file]);
            return this._success(
                { fileId: file.name + '_' + Date.now() }, 
                '文件添加成功',
                { fileName: file.name, fileSize: file.size }
            );
        } catch (error) {
            console.error('添加文件失败:', error);
            return this._error(
                '文件添加失败', 
                'ADD_FILE_FAILED',
                { fileName: file.name, error: error.message }
            );
        }
    }

    /**
     * 暂停所有上传
     */
    pauseAll() {
        this._checkDestroyed();

        // 取消当前上传
        this.abortControllers.forEach(controller => {
            controller.abort();
        });
        this.abortControllers.clear();

        // 将上传中的文件状态改为暂停
        this.files.forEach(fileItem => {
            if (fileItem.status === 'uploading') {
                fileItem.status = 'pending';
                this.updateFileStatus(fileItem, '已暂停');
            }
        });

        // 清空队列
        this.uploadQueue = [];
        this.activeUploads = 0;

        this.updateStats();
        this.trigger(UPLOADER_EVENTS.ALL_PAUSED);
    }

    /**
     * 恢复所有暂停的上传
     */
    resumeAll() {
        this._checkDestroyed();

        const pendingFiles = this.getFilesByStatus('pending');
        pendingFiles.forEach(fileItem => {
            this.queueUpload(fileItem);
        });

        if (pendingFiles.length > 0) {
            this.processQueue().catch(error => {
                console.error('恢复上传队列处理失败:', error);
            });
            this.trigger(UPLOADER_EVENTS.ALL_RESUMED);
        }
    }

    /**
     * 手动清理指定文件的预览资源
     * @deprecated 低使用频率方法，建议使用内部预览清理机制
     * @param {string} fileId - 文件ID
     * @returns {boolean} 是否成功清理
     */
    clearFilePreview(fileId) {
        this._checkDestroyed();

        const fileItem = this.files.get(fileId);
        if (!fileItem) {
            console.warn('DolphinUploader: 文件不存在:', fileId);
            return false;
        }

        try {
            this._clearFileObjectUrls(fileItem.file);

            if (this.options.debug) {
                console.log(`手动清理文件预览: ${fileItem.file.name}`);
            }

            return true;
        } catch (error) {
            console.warn(`清理文件预览失败 [${fileId}]:`, error);
            return false;
        }
    }

    /**
     * 手动清理所有预览资源
     * @deprecated 低使用频率方法，建议使用destroy()方法进行完整清理
     * @returns {Object} 标准化的API响应
     */
    clearAllPreviews() {
        // 检查实例状态
        if (this.destroyed) {
            return this._error('上传器实例已销毁', 'INSTANCE_DESTROYED');
        }
        
        try {
            const urlCount = this.objectUrls.size;
            this._clearAllObjectUrls();
            
            if (this.options.debug) {
                console.log(`手动清理了所有预览资源，共 ${urlCount} 个`);
            }
            
            return this._success(
                { clearedCount: urlCount },
                `成功清理了 ${urlCount} 个预览资源`,
                { 
                    operation: 'clearAllPreviews',
                    remainingUrls: this.objectUrls.size 
                }
            );
        } catch (error) {
            return this._error(
                '清理预览资源失败',
                'CLEAR_PREVIEWS_FAILED',
                { error: error.message }
            );
        }
    }

    /**
     * 获取指定文件的预览信息
     * @deprecated 低使用频率方法，建议通过事件获取预览信息
     * @param {string} fileId - 文件ID
     * @returns {Object|null} 预览信息或null
     */
    getFilePreview(fileId) {
        this._checkDestroyed();
        
        const fileItem = this.files.get(fileId);
        if (!fileItem) {
            return null;
        }
        
        // 查找与此文件相关的ObjectURL
        for (const [urlId, urlInfo] of this.objectUrls.entries()) {
            if (urlInfo.file === fileItem.file) {
                return {
                    fileId: urlId,
                    url: urlInfo.url,
                    createdAt: urlInfo.createdAt,
                    file: {
                        name: urlInfo.file.name,
                        size: urlInfo.file.size,
                        type: urlInfo.file.type
                    }
                };
            }
        }
        
        return null;
    }

    /**
     * 获取所有预览资源的统计信息
     * @deprecated 低使用频率方法，建议使用getHealthStatus()获取整体状态
     * @returns {Object} 预览资源统计
     */
    getPreviewStats() {
        this._checkDestroyed();
        
        const now = Date.now();
        let totalSize = 0;
        const previews = [];
        
        this.objectUrls.forEach((urlInfo, fileId) => {
            totalSize += urlInfo.file.size;
            previews.push({
                fileId,
                fileName: urlInfo.file.name,
                fileSize: urlInfo.file.size,
                createdAt: urlInfo.createdAt,
                age: now - urlInfo.createdAt
            });
        });
        
        return {
            count: this.objectUrls.size,
            totalSize,
            averageAge: previews.length > 0 ? 
                previews.reduce((sum, p) => sum + p.age, 0) / previews.length : 0,
            previews: previews.sort((a, b) => b.age - a.age) // 按年龄排序
        };
    }

    /**
     * 获取实例健康状态和性能信息
     * @deprecated 系统监控方法，建议在运维监控时使用
     * @returns {Object} 健康状态报告
     */
    getHealthStatus() {
        if (this.destroyed) {
            return {
                healthy: false,
                reason: 'Instance destroyed',
                timestamp: Date.now()
            };
        }

        try {
            this.updateStats();
            const memoryInfo = {
                fileCount: this.files.size,
                queueLength: this.uploadQueue.length,
                activeUploads: this.activeUploads,
                objectUrls: this.objectUrls.size,
                eventCleanups: this.eventCleanups.length,
                abortControllers: this.abortControllers.size
            };

            // 检查潜在问题
            const warnings = [];
            const errors = [];

            // 内存检查
            if (memoryInfo.objectUrls > 50) {
                warnings.push('Large number of object URLs may cause memory issues');
            }

            if (memoryInfo.fileCount > 100) {
                warnings.push('Large number of files may impact performance');
            }

            // 队列检查
            if (memoryInfo.queueLength > 0 && memoryInfo.activeUploads === 0) {
                warnings.push('Files in queue but no active uploads');
            }

            // DOM检查
            if (!this.fileInput) {
                errors.push('Custom UI mode requires file input element');
            }

            // 配置检查
            try {
                this._configManager.validateOptions(this.options);
            } catch (error) {
                errors.push(`Configuration error: ${error.message}`);
            }

            const healthy = errors.length === 0;

            return {
                healthy,
                timestamp: Date.now(),
                mode: 'custom',
                stats,
                memory: memoryInfo,
                warnings,
                errors,
                uptime: Date.now() - (this.initTime || Date.now())
            };

        } catch (error) {
            return {
                healthy: false,
                reason: `Health check failed: ${error.message}`,
                timestamp: Date.now()
            };
        }
    }

    /**
     * 执行内存和性能清理
     * @deprecated 系统维护方法，已有自动清理机制，通常无需手动调用
     * @returns {Object} 清理结果
     */
    performMaintenance() {
        if (this.destroyed) {
            return { success: false, reason: 'Instance destroyed' };
        }

        try {
            let cleaned = 0;

            // 清理已完成的文件（可选）
            const completedFiles = this.getFilesByStatus('success').concat(
                this.getFilesByStatus('error'),
                this.getFilesByStatus('cancelled')
            );

            // 清理超过一定时间的已完成文件
            const maxAge = this.options.completedFileMaxAge || 5 * 60 * 1000; // 5分钟
            const now = Date.now();

            completedFiles.forEach(fileItem => {
                if (fileItem.completeTime && (now - fileItem.completeTime) > maxAge) {
                    this.removeFile(fileItem.id);
                    cleaned++;
                }
            });

            // 清理孤立的ObjectURL
            this.objectUrls.forEach((urlInfo, fileId) => {
                try {
                    // 尝试创建一个图片来测试URL是否有效
                    const img = new Image();
                    img.onerror = () => {
                        this._revokeObjectURL(fileId);
                    };
                    img.src = urlInfo.url;
                } catch (error) {
                    this._revokeObjectURL(fileId);
                }
            });

            // 强制垃圾回收提示（如果浏览器支持）
            if (window.gc && typeof window.gc === 'function') {
                window.gc();
            }

            return {
                success: true,
                cleanedFiles: cleaned,
                timestamp: Date.now()
            };

        } catch (error) {
            return {
                success: false,
                reason: `Maintenance failed: ${error.message}`,
                timestamp: Date.now()
            };
        }
    }


    /**
     * 处理 `extraData` 配置项，可以是对象或返回对象的(异步)函数。
     * @param {File} file - 文件对象。
     * @param {DolphinFileUploadItem} fileItem - 文件项。
     * @returns {Promise<object>} 处理后的额外数据。
     * @private
     */
    async processExtraData(file, fileItem) {
        const extraData = this.options.extraData;

        if (DolphinOption.debug) {
            console.log(`[DolphinUploader] processExtraData called, extraData type: ${typeof extraData}`, extraData);
        }

        // 如果没有设置extraData，返回空对象
        if (!extraData) {
            return {};
        }

        try {
            let result = {};

            // 如果extraData是函数
            if (typeof extraData === 'function') {
                // 调用函数，传入文件对象和文件项
                const functionResult = await extraData(file, fileItem, this);

                // 验证函数返回值
                if (functionResult && typeof functionResult === 'object') {
                    result = functionResult;
                } else if (functionResult !== null && functionResult !== undefined) {
                    console.warn('extraData函数必须返回对象，当前返回:', typeof functionResult);
                }
            }
            // 如果extraData是对象
            else if (typeof extraData === 'object') {
                result = { ...extraData }; // 浅拷贝，避免修改原对象
            }
            // 其他类型不支持
            else {
                console.warn('extraData必须是对象或函数，当前类型:', typeof extraData);
            }

            if (DolphinOption.debug) {
                console.log(`[DolphinUploader] processExtraData result:`, result);
            }
            return result;

        } catch (error) {
            console.error('处理extraData时发生错误:', error);
            
            // 根据配置决定是否中止上传
            if (this.options.abortOnExtraDataError) {
                throw new Error(`获取额外参数失败: ${error.message}`);
            }

            // 当不中止上传时，提供明确的警告信息给开发者
            console.warn(
                `'extraData' 函数执行失败，但 'abortOnExtraDataError' 配置为 false。` +
                `上传将继续，但不会包含额外参数。错误详情:`,
                error
            );

            return {}; // 出错时返回空对象，不影响上传流程
        }
    }

    /**
     * 安全地将数据对象附加到 FormData 中。
     * @param {FormData} formData - FormData 实例。
     * @param {object} data - 要附加的数据。
     * @param {string} [source='unknown'] - 数据来源标识，用于调试。
     * @private
     */
    safeAppendToFormData(formData, data, source = 'unknown') {
        if (!data || typeof data !== 'object') {
            return;
        }

        Object.keys(data).forEach(key => {
            try {
                // 验证键名安全性，防止原型污染和注入攻击
                if (typeof key !== 'string' || key.length === 0 || key.length > 100) {
                    console.warn(`来自${source}的参数键名无效:`, key);
                    return;
                }

                // 过滤危险字符
                if (/[<>"/\\|?*\x00-\x1f]/.test(key)) {
                    console.warn(`来自${source}的参数键名包含危险字符:`, key);
                    return;
                }

                // 验证键名不是原型链属性
                if (key === '__proto__' || key === 'constructor' || key === 'prototype') {
                    console.warn(`来自${source}的参数键名为保留字:`, key);
                    return;
                }

                const value = data[key];

                // 验证值的类型和安全性
                if (value === null || value === undefined) {
                    // 允许null和undefined，转换为空字符串
                    formData.append(key, '');
                } else if (typeof value === 'string' || typeof value === 'number' || typeof value === 'boolean') {
                    // 允许基本类型
                    formData.append(key, String(value));
                } else if (value instanceof File || value instanceof Blob) {
                    // 允许文件类型
                    formData.append(key, value);
                } else if (typeof value === 'object') {
                    // 对象类型序列化为JSON字符串
                    try {
                        const jsonValue = JSON.stringify(value);
                        // 限制JSON字符串长度，防止过大的数据
                        if (jsonValue.length <= 10000) { // 10KB限制
                            formData.append(key, jsonValue);
                        } else {
                            console.warn(`来自${source}的参数值过大(>${10000}字符):`, key);
                        }
                    } catch (jsonError) {
                        console.warn(`来自${source}的参数值无法序列化:`, key, jsonError);
                    }
                } else {
                    console.warn(`来自${source}的参数值类型不支持:`, key, typeof value);
                }

            } catch (error) {
                console.warn(`添加${source}参数时发生错误:`, key, error);
            }
        });
    }

    /**
     * 将 ArrayBuffer 转换为十六进制字符串。
     * @param {ArrayBuffer} buffer - ArrayBuffer。
     * @returns {string} 十六进制字符串。
     * @private
     */
    bufferToHex(buffer) {
        return Array.from(new Uint8Array(buffer))
            .map(b => b.toString(16).padStart(2, '0'))
            .join('');
    }

    // =====================================
    // API别名和便利方法
    // =====================================

    /**
     * 添加文件的别名方法
     * @param {File} file - 文件对象
     * @returns {Promise<Object>} 标准化的API响应
     */
    add(file) {
        return this.addFile(file);
    }

    // remove方法已合并到removeFile中，保持向后兼容
    remove(fileId) {
        return this.removeFile(fileId);
    }

    // clear方法已合并到主类，用于清空所有文件和状态

    /**
     * 获取预览信息的别名方法
     * @param {string} fileId - 文件ID
     * @returns {Object|null} 预览信息
     */
    preview(fileId) {
        return this.getFilePreview(fileId);
    }

    /**
     * 获取统计信息的别名方法
     * @returns {Object} 预览统计信息
     */
    stats() {
        return this.getPreviewStats();
    }

    /**
     * 链式添加多个文件
     * @param {File[]} files - 文件数组
     * @returns {Promise<Object>} 批量操作结果
     */
    async addFiles(files) {
        const operations = files.map(file => () => this.addFile(file));
        return this.batch(operations);
    }

    /**
     * 链式移除多个文件
     * @param {string[]} fileIds - 文件ID数组
     * @returns {Promise<Object>} 批量操作结果
     */
    async removeFiles(fileIds) {
        const operations = fileIds.map(fileId => () => this.removeFile(fileId));
        return this.batch(operations);
    }

    /**
     * 获取所有文件的ID列表
     * @returns {Object} 标准化的API响应
     */
    getFileIds() {
        if (this.destroyed) {
            return this._error('上传器实例已销毁', 'INSTANCE_DESTROYED');
        }

        const fileIds = Array.from(this.files.keys());
        return this._success(
            { fileIds },
            `获取到 ${fileIds.length} 个文件ID`,
            { count: fileIds.length }
        );
    }

    /**
     * 获取所有文件的基本信息
     * @returns {Object} 标准化的API响应
     */
    getFilesInfo() {
        if (this.destroyed) {
            return this._error('上传器实例已销毁', 'INSTANCE_DESTROYED');
        }

        const filesInfo = Array.from(this.files.entries()).map(([id, fileItem]) => ({
            id,
            name: fileItem.file.name,
            size: fileItem.file.size,
            type: fileItem.file.type,
            status: fileItem.status,
            progress: fileItem.progress || 0
        }));

        return this._success(
            { files: filesInfo },
            `获取到 ${filesInfo.length} 个文件信息`,
            { count: filesInfo.length }
        );
    }

    /**
     * 检查文件是否存在
     * @param {string} fileId - 文件ID
     * @returns {Object} 标准化的API响应
     */
    hasFile(fileId) {
        if (this.destroyed) {
            return this._error('上传器实例已销毁', 'INSTANCE_DESTROYED');
        }

        const validation = this._validateParam(fileId, 'string', 'fileId');
        if (validation) {
            return validation;
        }

        const exists = this.files.has(fileId);
        return this._success(
            { exists, fileId },
            exists ? '文件存在' : '文件不存在'
        );
    }

    /**
     * 销毁上传器实例，清理所有资源
     */
    destroy() {
        if (this.destroyed) return;

        // 标记为已销毁
        this.destroyed = true;

        try {
            // 销毁内部模块（按依赖顺序）
            if (this._debugManager) {
                this._debugManager.destroy();
                this._debugManager = null;
            }

            if (this._performanceMonitor) {
                this._performanceMonitor.destroy();
                this._performanceMonitor = null;
            }

            if (this._throttleManager) {
                this._throttleManager.destroy();
                this._throttleManager = null;
            }

            if (this._memoryManager) {
                this._memoryManager.destroy();
                this._memoryManager = null;
            }

            if (this._eventEmitter) {
                this._eventEmitter.destroy();
                this._eventEmitter = null;
            }

            if (this._uploadManager) {
                this._uploadManager.destroy();
                this._uploadManager = null;
            }

            if (this._chunkUploader) {
                this._chunkUploader.destroy();
                this._chunkUploader = null;
            }

            if (this._hashCalculator) {
                this._hashCalculator.destroy();
                this._hashCalculator = null;
            }

            if (this._fileValidator) {
                this._fileValidator.destroy();
                this._fileValidator = null;
            }

            if (this._securityChecker) {
                this._securityChecker.destroy();
                this._securityChecker = null;
            }

            if (this._configManager) {
                this._configManager.destroy();
                this._configManager = null;
            }

            // 清理其他资源
            if (this.files) {
                this.files.clear();
            }

            if (this.uploadQueue) {
                this.uploadQueue.length = 0;
            }

            if (this.objectUrls) {
                this.objectUrls.forEach((urlInfo) => {
                    if (urlInfo.url) {
                        URL.revokeObjectURL(urlInfo.url);
                    }
                });
                this.objectUrls.clear();
            }

            if (this.abortControllers) {
                this.abortControllers.forEach(controller => {
                    controller.abort('Instance destroyed');
                });
                this.abortControllers.clear();
            }

            // 清理事件监听器
            if (this.eventCleanups && Array.isArray(this.eventCleanups)) {
                this.eventCleanups.forEach(cleanup => {
                    if (typeof cleanup === 'function') {
                        cleanup();
                    }
                });
                this.eventCleanups.length = 0;
            }

            // 触发销毁事件
            if (this.element && typeof this.element.dispatchEvent === 'function') {
                this.element.dispatchEvent(new CustomEvent('uploader:destroy', {
                    detail: { instance: this }
                }));
            }

            // 清理引用
            this.element = null;
            this.options = null;

        } catch (error) {
            console.error('销毁上传器实例时发生错误:', error);
        }
    }
}

/**
 * DolphinUploader 文件项的数据模型类
 * @class
 */
class DolphinFileUploadItem {
    /**
     * @param {File} file - 原始File对象。
     * @param {string} id - 唯一文件ID。
     * @param {object} options - 上传器配置。
     */
    constructor(file, id, options) {
        /** @type {File} */
        this.file = file;
        /** @type {string} */
        this.id = id;
        /** @type {object} */
        this.options = options;
        /** @type {string} */
        this.status = 'pending'; // pending, queued, uploading, success, error, cancelled
        /** @type {number} */
        this.progress = 0;
        /** @type {HTMLElement|null} */
        this.element = null;
        /** @type {object|null} */
        this.result = null;
        /** @type {Error|null} */
        this.error = null;
        /** @type {number|null} */
        this.startTime = null;
        /** @type {string|null} 最终的、精确的完整文件哈希 */
        this.hash = null;
        /** @type {string|null} 临时的、用于快速检查的采样哈希 */
        this.preliminaryHash = null;
        /** @type {object} 用于存储驱动在各阶段需要传递的数据 */
        this.driverData = {};
        /** @type {string} 文件后缀名 */
        this.ext = file.name.includes('.') ? file.name.split('.').pop().toLowerCase() : '';
        /** @type {number} 文件总分片数 */
        this.totalChunks = 0;
        /** @type {number} 已成功上传的分片数 */
        this.uploadedChunks = 0;
    }
}

// 导出到全局
window.DolphinUploader = DolphinUploader;
window.DolphinFileUploadItem = DolphinFileUploadItem;
