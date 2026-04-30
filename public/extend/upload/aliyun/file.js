/**
 * 阿里云上传驱动 (新版)
 * 使用 DolphinUploader v2.x API
 */
$(function () {
    // 检查 Dolphin.uploader API 是否存在且符合新版规范
    if (window.Dolphin && typeof Dolphin.uploader === 'object' && typeof Dolphin.uploader.register === 'function') {
        
        // 注册阿里云驱动
        Dolphin.uploader.register('aliyun:file', {
            /**
             * 在文件哈希计算完成后，实际上传前执行。
             * 此阶段用于获取上传策略等需要文件哈希的操作。
             * @param {File} file - 当前文件对象。
             * @param {DolphinFileUploadItem} fileItem - 文件项实例 (包含hash)。
             * @returns {Promise<object>} 返回一个包含上传策略的对象。
             */
            async prepare(file, fileItem) {
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

                    const policyResponse = await jQuery.post('/_uploader/aliyun/file/getPolicy.html', {file:fileInfo});
                    if (policyResponse && policyResponse.code === 1) {
                        // 返回策略数据，它将自动被添加到上传表单和fileItem.driverData中
                        policyResponse.data.key = key;
                        return policyResponse.data;
                    } else {
                        // 如果获取策略失败，则抛出错误以中止上传
                        const errorMessage = policyResponse.msg || '获取阿里云上传策略失败';
                        throw new Error(errorMessage);
                    }
                } catch (error) {
                    const errorMessage = error.responseJSON?.msg || error.message || '请求上传策略接口失败';
                    throw new Error(errorMessage);
                }
            },

            /**
             * 响应处理器：处理阿里云返回的非标准响应。
             * @param {Object} responseData - 原始响应体，对于阿里云成功上传，这通常是一个空对象。
             * @param {Response} response - Fetch API的原始Response对象。
             * @returns {Object} 返回DolphinUploader能识别的标准格式。
             */
            responseHandler(responseData, response) {
                // 阿里云上传成功时，HTTP状态码通常为 204 No Content，且响应体为空。
                if (response.ok && (response.status === 204 || response.status === 200)) {
                    return {
                        code: 1, // 1 表示成功
                        msg: '上传成功',
                        data: responseData // data 可以是空对象或包含服务器返回的任何信息
                    };
                }

                // 如果不满足成功条件，则返回一个标准的失败格式
                return {
                    code: 0, // 0 表示失败
                    msg: '上传到阿里云失败【' + this.getResponseMessage(responseData) + '】',
                    data: responseData
                };
            },
            
            /**
             * 上传成功后的回调，用于将文件信息保存到服务器。
             * @param {File} file - 原生文件对象。
             * @param {Object} result - 经过responseHandler处理后的结果，通常为空对象。
             * @param {DolphinFileUploadItem} fileItem - 文件项实例，其中 driverData 包含了 before 钩子返回的策略信息。
             * @returns {Promise<any>}
             */
            async success(file, result, fileItem) {
                // 从 driverData 中获取在 before 阶段存入的 key
                const key = fileItem.driverData?.key;
                if (!key) {
                    throw new Error('无法从driverData中获取文件key，保存失败');
                }

                // 从全局配置获取域名
                const domain = DolphinConfig?.upload?.['aliyun.file']?.domain;
                if (!domain) {
                    throw new Error('未在 DolphinConfig.upload.aliyun 中配置域名');
                }
                const fileUrl = domain + key;

                // 调用Dolphin.saveFile将文件信息保存到服务器
                let res = await Dolphin.saveFile({
                    'name': file.name,
                    'url': fileUrl,
                    'mime': file.type,
                    'ext': fileItem.ext,
                    'size': file.size,
                    'sha1': fileItem.hash,
                    'driver': 'aliyun',
                });

                if (res.code === 1) {
                    return res;
                } else {
                    Dolphin.error(res.msg || '保存文件信息失败');
                    throw new Error(res.msg || '保存文件信息失败');
                }
            },
            // 获取阿里云oss返回的信息
            getResponseMessage: function(xml) {
                const match = xml.match(/<Message>([\s\S]*?)<\/Message>/i);
                return match ? match[1] : '';
            }
        });
    } else {
        console.error('Dolphin uploader API not found, cannot register "aliyun" driver.');
    }
});
