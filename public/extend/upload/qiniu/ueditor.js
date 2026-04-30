/**
 * 七牛云上传驱动(UEditor编辑器)
 */
$(function () {
    // 检查Dolphin.uploader API是否存在
    if (window.Dolphin && typeof Dolphin.uploader === 'object' && typeof Dolphin.uploader.register === 'function') {
        // 注册七牛云UEditor专用驱动
        Dolphin.uploader.register('qiniu:ueditor', {
            /**
             * 上传前钩子
             * @param {File} file - 文件对象
             * @param {Object} fileItem - 文件项对象
             * @returns {Object|boolean} 返回额外数据或false取消上传
             */
            before(file, fileItem) {
                // 可以在这里添加文件验证逻辑
                return true;
            },

            /**
             * 上传准备钩子 - 获取七牛云上传凭证
             * @param {File} file - 文件对象
             * @param {Object} fileItem - 文件项对象
             * @returns {Promise<Object|boolean>} 返回上传凭证或false取消上传
             */
            async prepare(file, fileItem) {
                try {
                    // 计算文件信息
                    const fileInfo = await Dolphin.readFile(file);

                    // 构建文件路径
                    const uploadDir = fileItem?.uploadDir || '';
                    const key = uploadDir + fileInfo.hash + '.' + fileInfo.ext;

                    // 从后端获取上传Token（同时检查文件是否已存在）
                    const tokenData = {
                        name: file.name,
                        size: file.size,
                        mime: file.type,
                        hash: fileInfo.hash,
                        ext: fileInfo.ext,
                        url: key
                    };

                    const response = await jQuery.post('/_uploader/qiniu/ueditor/getToken.html', { file: tokenData });

                    if (!response || response.code !== 1) {
                        throw new Error('获取上传凭证失败：' + (response?.msg || '未知错误'));
                    }

                    const domain = DolphinConfig?.upload?.['qiniu.ueditor']?.domain || '';

                    // 检查文件是否已存在（后端返回 exists 字段）
                    if (response.data.exists) {
                        console.log('[七牛云] 文件已存在，跳过上传:', key);
                        // 返回已存在文件的信息，标记为跳过上传
                        return {
                            skipUpload: true,
                            url: domain + key,
                            key: key,
                            ext: fileInfo.ext,
                            hash: fileInfo.hash,
                            domain: domain
                        };
                    }

                    // 返回上传所需的额外数据
                    return {
                        token: response.data.token,
                        key: key,
                        ext: fileInfo.ext,
                        hash: fileInfo.hash,
                        domain: domain,
                        directUpload: true,
                    };
                } catch (error) {
                    console.error('七牛云上传准备失败:', error);
                    throw error;
                }
            },

            /**
             * 自定义上传处理（直传到七牛云）
             * @param {File} file - 文件对象
             * @param {Object} fileItem - 文件项对象
             * @param {String} uploadUrl - 上传地址
             * @returns {Promise<Object>} 上传结果
             */
            async upload(file, fileItem, uploadUrl) {
                const { token, key, domain } = fileItem;

                // 构建FormData
                const formData = new FormData();
                formData.append('token', token);
                formData.append('key', key);
                formData.append('file', file);

                // 直传到七牛云
                const response = await fetch(uploadUrl, {
                    method: 'POST',
                    body: formData
                });

                const responseJson = await response.json();

                if (!response.ok || response.status !== 200) {
                    throw new Error('七牛云上传失败：' + (responseJson.error || '未知错误'));
                }

                if (!responseJson.key) {
                    throw new Error('七牛云返回格式错误');
                }

                return {
                    url: domain + key,
                    key: key,
                    name: file.name
                };
            },

            /**
             * 上传成功后的回调
             * @param {File} file - 原生文件对象
             * @param {Object} result - 上传成功后七牛云返回的结果
             * @param {Object} fileItem - DolphinUploader的文件项实例
             * @returns {Promise<any>}
             */
            async success(file, result, fileItem) {
                // 经过responseHandler处理后，这里的result已经是原始的七牛云返回数据 {hash, key}
                if (!result || !result.key) {
                    console.error('七牛云驱动上传成功回调缺少`key`');
                    // 抛出错误，让DolphinUploader捕获并标记为上传失败
                    throw new Error('上传结果无效：缺少文件key');
                }
                // 从Dolphin的全局配置中获取七牛云的域名
                const domain = DolphinConfig?.upload?.['qiniu.ueditor']?.domain;
                if (!domain) {
                    console.error('未在 DolphinConfig.upload.qiniu 中配置域名(domain)');
                    throw new Error('七牛云配置不完整：缺少域名');
                }
                const fileUrl = domain + result.key;

                // 调用Dolphin.saveFile将文件信息保存到服务器
                // Dolphin.saveFile应该返回一个Promise
                let res = await Dolphin.saveFile({
                    'name': file.name,
                    'url': fileUrl,
                    'mime': file.type,
                    'ext': fileItem.ext,
                    'size': file.size,
                    'sha1': fileItem.hash,
                    'driver': 'qiniu',
                });

                if (res.code === 1) {
                    return {
                        url: fileUrl,
                        key: result.key,
                        name: file.name
                    }
                } else {
                    Dolphin.error(res.msg || '保存文件信息失败');
                    throw new Error(res.msg || '保存文件信息失败');
                }
            },

            /**
             * 上传失败钩子
             * @param {File} file - 文件对象
             * @param {Error} error - 错误对象
             * @param {Object} fileItem - 文件项对象
             */
            error(file, error, fileItem) {
                console.error('UEditor七牛云上传失败:', error);
                Dolphin.error(error.message || '上传失败');
            }
        });

        console.log('七牛云UEditor专用驱动已注册: qiniu:ueditor');
    } else {
        console.error('Dolphin uploader API not found, cannot register "qiniu:ueditor" driver.');
    }
});
