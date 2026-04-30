/**
 * 阿里云上传驱动(UEditor编辑器)
 */
$(function () {
    // 检查 Dolphin.uploader API 是否存在
    if (window.Dolphin && typeof Dolphin.uploader === 'object' && typeof Dolphin.uploader.register === 'function') {
        // 注册阿里云驱动
        Dolphin.uploader.register('aliyun:ueditor', {
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
             * 上传准备钩子 - 获取阿里云上传凭证
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

                    // 从后端获取上传策略（Policy）（同时检查文件是否已存在）
                    const policyData = {
                        name: file.name,
                        size: file.size,
                        mime: file.type,
                        hash: fileInfo.hash,
                        ext: fileInfo.ext,
                        url: key
                    };

                    const response = await jQuery.post('/_uploader/aliyun/ueditor/getPolicy.html', { file: policyData });

                    if (!response || response.code !== 1) {
                        throw new Error('获取上传凭证失败：' + (response?.msg || '未知错误'));
                    }

                    const domain = DolphinConfig?.upload?.['aliyun.ueditor']?.domain || '';

                    // 检查文件是否已存在（后端返回 exists 字段）
                    if (response.data.exists) {
                        console.log('[阿里云] 文件已存在，跳过上传:', key);
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
                        policy: response.data,
                        key: key,
                        ext: fileInfo.ext,
                        hash: fileInfo.hash,
                        domain: domain,
                        directUpload: true,
                    };
                } catch (error) {
                    console.error('阿里云上传准备失败:', error);
                    throw error;
                }
            },

            /**
             * 自定义上传处理（直传到阿里云OSS）
             * @param {File} file - 文件对象
             * @param {Object} fileItem - 文件项对象
             * @param {String} uploadUrl - 上传地址
             * @returns {Promise<Object>} 上传结果
             */
            async upload(file, fileItem, uploadUrl) {
                const { policy, key, domain} = fileItem;

                // 构建FormData
                const formData = new FormData();
                formData.append('OSSAccessKeyId', policy.OSSAccessKeyId);
                formData.append('Signature', policy.Signature);
                formData.append('policy', policy.policy);
                formData.append('success_action_status', policy.success_action_status || '200');
                formData.append('key', key);
                formData.append('file', file);

                // 直传到阿里云OSS
                const response = await fetch(uploadUrl, {
                    method: 'POST',
                    body: formData
                });

                if (response.ok && (response.status === 200 || response.status === 204)) {
                    return {
                        url: domain + key,
                        key: key,
                        name: file.name
                    };
                } else {
                    const responseText = await response.text();
                    const errorMsg = this.getResponseMessage(responseText) || '上传失败';
                    throw new Error('阿里云上传失败：' + errorMsg);
                }
            },

            /**
             * 上传成功后的回调
             * @param {File} file - 原生文件对象
             * @param {Object} result - 上传成功后阿里云返回的结果
             * @param {Object} fileItem - DolphinUploader的文件项实例
             * @returns {Promise<any>}
             */
            async success(file, result, fileItem) {
                // 经过responseHandler处理后，这里的result已经是原始的阿里云返回数据 {hash, key}
                if (!result || !result.key) {
                    console.error('阿里云驱动上传成功回调缺少`key`');
                    // 抛出错误，让DolphinUploader捕获并标记为上传失败
                    throw new Error('上传结果无效：缺少文件key');
                }
                // 从Dolphin的全局配置中获取阿里云的域名
                const domain = DolphinConfig?.upload?.['aliyun.ueditor']?.domain;
                if (!domain) {
                    console.error('未在 DolphinConfig.upload.aliyun 中配置域名(domain)');
                    throw new Error('阿里云配置不完整：缺少域名');
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
                console.error('UEditor阿里云上传失败:', error);
                Dolphin.error(error.message || '上传失败');
            },

            /**
             * 获取阿里云OSS返回的错误信息
             * @param {string} xml - XML响应文本
             * @returns {string} 错误信息
             */
            getResponseMessage(xml) {
                const match = xml.match(/<Message>([\s\S]*?)<\/Message>/i);
                return match ? match[1] : '';
            }
        });
    } else {
        console.error('Dolphin uploader API not found, cannot register "aliyun:ueditor" driver.');
    }
});
