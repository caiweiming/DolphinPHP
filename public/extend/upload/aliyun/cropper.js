/**
 * 阿里云上传驱动（图片裁剪器专用）
 */
$(function () {
    // 检查Dolphin.uploader API是否存在
    if (window.Dolphin && typeof Dolphin.uploader === 'object' && typeof Dolphin.uploader.register === 'function') {
        // 注册阿里云裁剪器专用驱动
        Dolphin.uploader.register('aliyun:cropper', {

            /**
             * 上传裁剪后的图片到阿里云OSS
             * @param {Blob} blob 图片blob数据
             * @param {string} fieldName 字段名
             * @param {string} url 阿里云上传地址
             * @param {string} dir 上传目录
             * @returns {Promise}
             */
            async uploadCroppedImage(blob, fieldName, url, dir) {
                try {
                    // 计算文件信息
                    const fileItem = await this._calculateFileInfo(blob);

                    // 生成文件名和路径
                    const fileName = `${fileItem.hash}.png`;
                    const fileKey = (dir || '') + `${fileItem.hash}.png`;

                    // 获取阿里云上传策略
                    const policyData = await this._getAliyunPolicy({
                        name: fileName,
                        size: blob.size,
                        mime: 'image/png',
                        hash: fileItem.hash,
                        ext: 'png',
                        url: fileKey
                    });

                    // 上传到阿里云OSS
                    const uploadResult = await this._uploadToAliyun(blob, policyData, fileKey, url);

                    // 保存文件信息到服务器
                    return await this._saveFileInfo(fileName, uploadResult, fileItem, blob, fileKey);

                } catch (error) {
                    console.error('阿里云裁剪图片上传失败:', error);
                    throw new Error(error.message);
                }
            },

            /**
             * 计算文件信息（使用 Dolphin.readFile）
             * @param {Blob} blob
             * @returns {Promise<{hash: string, ext: string}>}
             * @private
             */
            async _calculateFileInfo(blob) {
                // 创建临时File对象以使用Dolphin.readFile
                const file = new File([blob], 'cropped.png', { type: 'image/png' });
                return await Dolphin.readFile(file);
            },

            /**
             * 获取阿里云上传策略
             * @param {Object} fileInfo 文件信息
             * @returns {Promise<Object>}
             * @private
             */
            async _getAliyunPolicy(fileInfo) {
                const response = await jQuery.post('/_uploader/aliyun/cropper/getPolicy.html', {
                    file: fileInfo
                });

                if (!response || response.code !== 1) {
                    throw new Error('获取上传策略失败：' + (response.msg || '未知错误'));
                }

                return response.data;
            },

            /**
             * 上传文件到阿里云OSS
             * @param {Blob} blob
             * @param {Object} policyData
             * @param {string} fileKey
             * @param {string} url
             * @returns {Promise<Object>}
             * @private
             */
            async _uploadToAliyun(blob, policyData, fileKey, url) {
                // 构建FormData
                const formData = new FormData();
                formData.append('OSSAccessKeyId', policyData.OSSAccessKeyId);
                formData.append('policy', policyData.policy);
                formData.append('Signature', policyData.Signature);
                formData.append('key', fileKey);
                formData.append('file', blob, 'cropped.png');

                // 上传到阿里云OSS
                const response = await fetch(url, {
                    method: 'POST',
                    body: formData
                });

                // 阿里云OSS上传成功时，HTTP状态码通常为 204 No Content
                if (response.ok && (response.status === 204 || response.status === 200)) {
                    return {
                        key: fileKey,
                        success: true
                    };
                }

                // 如果上传失败，尝试解析错误信息
                let errorMessage = '上传到阿里云OSS失败';
                try {
                    const responseText = await response.text();
                    const errorMatch = responseText.match(/<Message>([\s\S]*?)<\/Message>/i);
                    if (errorMatch) {
                        errorMessage += '：' + errorMatch[1];
                    }
                } catch (e) {
                    // 忽略解析错误
                }

                throw new Error(errorMessage);
            },

            /**
             * 保存文件信息到服务器
             * @param {string} fileName
             * @param {Object} uploadResult
             * @param {Object} fileItem
             * @param {Blob} blob
             * @param {string} fileKey
             * @returns {Promise<Object>}
             * @private
             */
            async _saveFileInfo(fileName, uploadResult, fileItem, blob, fileKey) {
                // 从Dolphin的全局配置中获取阿里云的域名
                const domain = DolphinConfig?.upload?.['aliyun.cropper']?.domain;
                if (!domain) {
                    console.error('未在 DolphinConfig.upload.aliyun 中配置域名(domain)');
                    throw new Error('阿里云配置不完整：缺少域名');
                }

                const fileUrl = domain + fileKey;

                // 调用Dolphin.saveFile保存文件信息
                const saveResponse = await Dolphin.saveFile({
                    name: fileName,
                    url: fileUrl,
                    mime: 'image/png',
                    ext: 'png',
                    size: blob.size,
                    sha1: fileItem.hash,
                    driver: 'aliyun',
                });

                if (saveResponse.code !== 1) {
                    throw new Error(saveResponse.msg || '保存文件信息失败');
                }

                return saveResponse;
            }
        });

        console.log('阿里云裁剪器专用驱动已注册: aliyun:cropper');
    } else {
        console.error('Dolphin uploader API not found, cannot register "aliyun:cropper" driver.');
    }
});
