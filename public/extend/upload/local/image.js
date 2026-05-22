/**
 * 本地上传驱动
 * 
 * 使用新的 `Dolphin.uploader.register` API 进行注册。
 * 这个驱动的核心功能是在文件上传前，动态添加额外的请求参数。
 */
$(function () {
    // 检查 Dolphin.uploader API 是否存在
    if (window.Dolphin && typeof Dolphin.uploader === 'object' && typeof Dolphin.uploader.register === 'function') {
        
        Dolphin.uploader.register('local:image', {
            /**
             * 在文件开始上传前执行。
             * 这个钩子函数允许我们动态地修改上传参数。
             *
             * @param {File} file - 当前正在处理的文件对象。
             * @param {DolphinFileUploadItem} fileItem - DolphinUploader内部的文件项实例，包含文件的状态和元数据。
             * @returns {object} 返回一个对象，该对象中的键值对将被合并到上传请求的表单数据中。
             */
            async before(file, fileItem) {
                // 返回需要附加到上传请求的额外参数。
                // 例如，可以指定文件保存的分类、来源等信息。
                // return {
                //     category: 'default', // 文件分类
                //     source: 'web_upload' // 文件来源
                // };
            },

            /**
             * 文件上传成功后执行。
             * @param {File} file - 当前文件对象。
             * @param {object} result - 服务器返回的原始响应数据。
             * @param {DolphinFileUploadItem} fileItem - 文件项实例。
             */
            async success(file, result, fileItem) {
                // 可以在这里根据服务器返回的结果执行进一步操作，
                // 例如更新UI、显示成功消息等。
            },

            /**
             * 文件上传失败后执行。
             * @param {File} file - 当前文件对象。
             * @param {Error} error - 描述失败原因的错误对象。
             * @param {DolphinFileUploadItem} fileItem - 文件项实例。
             */
            async error(file, error, fileItem) {
                console.error(`[Local Driver] 文件 ${file.name} 上传失败:`, error.message);
                // 可以在这里向监控系统发送错误日志。
            }
        });

    } else {
        console.error('Dolphin uploader API not found, cannot register "local" driver.');
        Dolphin.error('无法注册“local”驱动');
    }
});
