/**
 * 七牛云上传驱动
 */
$(function () {
    // 检查Dolphin.uploader API是否存在
    if (window.Dolphin && typeof Dolphin.uploader === 'object' && typeof Dolphin.uploader.register === 'function') {
        // 注册七牛云驱动
        Dolphin.uploader.register('qiniu:file', {
            /**
             * 在秒传检查失败后、实际上传开始前执行。
             * 这个钩子函数允许我们动态地修改上传参数，仅在确定需要实际上传时才会被调用。
             *
             * @param {File} file - 当前正在处理的文件对象。
             * @param {DolphinFileUploadItem} fileItem - DolphinUploader内部的文件项实例，包含文件的状态和元数据。
             * @returns {object} 返回一个对象，该对象中的键值对将被合并到上传请求的表单数据中。
             */
            async prepare(file, fileItem) {
                // 返回需要附加到上传请求的额外参数。
                try {
                    // 要保存的文件路径
                    const key = (fileItem.dir || '') + fileItem.hash + '.' + fileItem.ext;
                    // 从后端获取上传策略（Policy）
                    const fileInfo = {
                        name: file.name,
                        size: file.size,
                        mime: file.type,
                        hash: fileItem.hash,
                        ext: fileItem.ext,
                        url: key
                    };

                    // 从后端获取上传票据（Token）
                    const response = await jQuery.post('/_uploader/qiniu/file/getToken.html', {file:fileInfo});

                    if (response && response.code === 1) {
                        // 返回策略数据，它将自动被添加到上传表单和fileItem.driverData中
                        response.data.key =  key;
                        return response.data;
                    } else {
                        // 如果获取策略失败，则抛出错误以中止上传
                        const errorMessage = response.msg || '获取七牛云上传策略失败';
                        throw new Error(errorMessage);
                    }
                } catch (error) {
                    const errorMessage = error.responseJSON?.msg || error.message || '请求上传策略接口失败';
                    throw new Error(errorMessage);
                }
            },

            /**
             * 响应处理器：将七牛云返回的非标准JSON适配成DolphinUploader的标准格式
             * @param {Object} responseData - 七牛云返回的原始响应体，例如 {hash: "...", key: "..."}
             * @param {Response} response - Fetch API的原始Response对象
             * @returns {Object} - 返回DolphinUploader能识别的标准格式
             */
            responseHandler(responseData, response) {
                // 如果HTTP状态码是200，且响应体中包含key和hash，则我们认为上传成功
                if (response.ok && responseData.key && responseData.hash) {
                    return {
                        code: 1, // 1 表示成功
                        msg: '上传成功',
                        data: responseData // 将原始数据包装在data字段中
                    };
                }

                return {
                    code: 0, // 0 表示失败
                    msg: responseData.error || '上传到七牛云失败',
                    data: responseData
                };
            },
            
            /**
             * 上传成功后的回调
             * @param {File} file - 原生文件对象
             * @param {Object} result - 上传成功后七牛云返回的结果
             * @param {DolphinFileUploadItem} fileItem - DolphinUploader的文件项实例
             * @returns {Promise<any>}
             */
            async success(file, result, fileItem) {
                // 经过responseHandler处理后，这里的result已经是原始的七牛云返回数据 {hash, key}
                if (!result || !result.data || !result.data.key) {
                    console.error('七牛云驱动上传成功回调缺少`key`');
                    // 抛出错误，让DolphinUploader捕获并标记为上传失败
                    throw new Error('上传结果无效：缺少文件key');
                }
                // 从Dolphin的全局配置中获取七牛云的域名
                const domain = DolphinConfig?.upload?.['qiniu.file']?.domain;
                if (!domain) {
                    console.error('未在 DolphinConfig.upload.qiniu 中配置域名(domain)');
                    throw new Error('七牛云配置不完整：缺少域名');
                }
                const fileUrl = domain + result.data.key;

                // 调用Dolphin.saveFile将文件信息保存到服务器
                // Dolphin.saveFile应该返回一个Promise
                let res =  await Dolphin.saveFile({
                    'name': file.name,
                    'url': fileUrl,
                    'mime': file.type,
                    'ext': fileItem.ext,
                    'size': file.size,
                    'sha1': fileItem.hash,
                    'driver': 'qiniu',
                });

                if (res.code === 1) {
                    return res;
                } else {
                    Dolphin.error(res.msg || '保存文件信息失败');
                    throw new Error(res.msg || '保存文件信息失败');
                }
            },
        });
    } else {
        console.error('Dolphin uploader API not found, cannot register "qiniu" driver.');
    }
});