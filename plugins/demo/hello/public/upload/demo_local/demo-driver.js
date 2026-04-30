/**
 * demo/hello 示例上传驱动前端适配脚本
 *
 * 目的：
 * - 向 Dolphin.uploader 注册插件驱动，避免前端回退到“驱动未注册”警告
 * - 保持默认上传流程不变，仅用于演示插件驱动资源与前端注册链路
 */
$(function () {
    const driverNames = ['demo_local:image', 'demo_local'];

    const driverHooks = {
        /**
         * 上传前钩子
         * 这里返回一个演示标记，便于在调试时确认驱动确实被调用。
         * @param {File} file
         * @param {Object} fileItem
         * @returns {Object}
         */
        before(file, fileItem) {
            return {
                plugin_driver: 'demo_local',
                plugin_component: fileItem?.componentType || 'image',
            };
        },

        /**
         * 上传成功后直接透传结果，保持默认流程。
         * @param {File} file
         * @param {Object} result
         * @returns {Object}
         */
        success(file, result) {
            return result;
        },

        /**
         * 上传失败时保留日志，方便人工验证。
         * @param {File} file
         * @param {Error} error
         * @returns {void}
         */
        error(file, error) {
            console.error('[demo_local] 上传失败:', error);
        },
    };

    const registerDriver = () => {
        if (!(window.Dolphin && typeof Dolphin.uploader === 'object' && typeof Dolphin.uploader.register === 'function')) {
            return false;
        }

        driverNames.forEach((name) => {
            if (!Dolphin.uploader.has(name)) {
                Dolphin.uploader.register(name, driverHooks);
            }
        });

        window.DolphinPluginDemoUpload = {
            ready: true,
            registered: driverNames,
        };

        return true;
    };

    if (registerDriver()) {
        return;
    }

    let retryCount = 0;
    const maxRetries = 20;
    const timer = window.setInterval(() => {
        retryCount += 1;
        if (registerDriver() || retryCount >= maxRetries) {
            window.clearInterval(timer);
            if (retryCount >= maxRetries && !window.DolphinPluginDemoUpload) {
                window.DolphinPluginDemoUpload = {
                    ready: false,
                    registered: [],
                    error: 'Dolphin.uploader.register is unavailable',
                };
                console.error('[demo_local] Dolphin.uploader.register 不可用，驱动未完成前端注册');
            }
        }
    }, 100);
});
