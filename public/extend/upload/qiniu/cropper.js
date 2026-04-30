/**
 * 七牛云上传驱动（图片裁剪器专用）
 */
$(function () {
    // 检查Dolphin.uploader API是否存在
    if (window.Dolphin && typeof Dolphin.uploader === 'object' && typeof Dolphin.uploader.register === 'function') {
        // 注册七牛云裁剪器专用驱动
        Dolphin.uploader.register('qiniu:cropper', {

            /**
             * 上传裁剪后的图片到七牛云
             * @param {Blob} blob 图片blob数据
             * @param {string} fieldName 字段名
             * @param {string} url 七牛上传地址
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

                    // 获取七牛云上传策略
                    const tokenData = await this._getQiniuToken({
                        name: fileName,
                        size: blob.size,
                        mime: 'image/png',
                        hash: fileItem.hash,
                        ext: 'png',
                        url: fileKey
                    });

                    // 上传到七牛云
                    const uploadResult = await this._uploadToQiniu(blob, tokenData, fileKey, url);

                    // 保存文件信息到服务器
                    return await this._saveFileInfo(fileName, uploadResult, fileItem, blob);

                } catch (error) {
                    console.error('七牛云裁剪图片上传失败:', error);
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
             * 获取七牛云上传策略
             * @param {Object} fileInfo 文件信息
             * @returns {Promise<Object>}
             * @private
             */
            async _getQiniuToken(fileInfo) {
                const response = await jQuery.post('/_uploader/qiniu/cropper/getToken.html', {
                    file: fileInfo
                });

                if (!response || response.code !== 1) {
                    throw new Error('获取上传token失败：' + (response.msg || '未知错误'));
                }

                return response.data;
            },

            /**
             * 上传文件到七牛云
             * @param {Blob} blob
             * @param {Object} tokenData
             * @param {string} fileKey
             * @param {string} url
             * @returns {Promise<Object>}
             * @private
             */
            async _uploadToQiniu(blob, tokenData, fileKey, url) {
                // 构建FormData
                const formData = new FormData();
                formData.append('token', tokenData.token);
                formData.append('key', fileKey);
                formData.append('file', blob, 'cropped.png');

                // 上传到七牛云
                const response = await fetch(url, {
                    method: 'POST',
                    body: formData
                });

                const responseData = await response.json();

                // 如果HTTP状态码是200，且响应体中包含key和hash，则我们认为上传成功
                if (response.ok && responseData.key && responseData.hash) {
                    return responseData;
                }

                throw new Error(responseData.error || '上传到七牛云失败');
            },

            /**
             * 保存文件信息到服务器
             * @param {string} fileName
             * @param {Object} uploadResult
             * @param {Object} fileItem
             * @param {Blob} blob
             * @returns {Promise<Object>}
             * @private
             */
            async _saveFileInfo(fileName, uploadResult, fileItem, blob) {
                // 从Dolphin的全局配置中获取七牛云的域名
                const domain = DolphinConfig?.upload?.['qiniu.cropper']?.domain;
                if (!domain) {
                    console.error('未在 DolphinConfig.upload.qiniu 中配置域名(domain)');
                    throw new Error('七牛云配置不完整：缺少域名');
                }

                const fileUrl = domain + uploadResult.key;

                // 调用Dolphin.saveFile保存文件信息
                const saveResponse = await Dolphin.saveFile({
                    name: fileName,
                    url: fileUrl,
                    mime: 'image/png',
                    ext: 'png',
                    size: blob.size,
                    sha1: fileItem.hash,
                    _dir: 'images',
                    driver: 'qiniu',
                });

                if (saveResponse.code !== 1) {
                    throw new Error(saveResponse.msg || '保存文件信息失败');
                }

                return saveResponse;
            }
        });

        console.log('七牛云裁剪器专用驱动已注册: qiniu:cropper');
    } else {
        console.error('Dolphin uploader API not found, cannot register "qiniu:cropper" driver.');
    }
});