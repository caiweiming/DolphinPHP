/**
 * 七牛云上传驱动(Vditor编辑器)
 */
$(function () {
    // 检查Dolphin.uploader API是否存在
    if (window.Dolphin && typeof Dolphin.uploader === 'object' && typeof Dolphin.uploader.register === 'function') {
        // 注册七牛云Vditor专用驱动，使用命名空间格式
        Dolphin.uploader.register('qiniu:vditor', {
            // 接管vditor的handler方法
            async handler(files, context) {
                const results = {
                    errFiles: [],
                    succMap: {}
                };

                // 逐个上传文件
                for (let i = 0; i < files.length; i++) {
                    const file = files[i];
                    try {
                        let result = await this._uploadSingleFile(file, context.uploadDir, context.options.url);
                        results.succMap[file.name] = result.url;
                    } catch (error) {
                        Dolphin.error(error.message);
                        results.errFiles.push(file.name);
                    }
                }

                // 返回结果
                const successCount = Object.keys(results.succMap).length;

                if (successCount > 0) {
                    // 有文件上传成功，手动插入到编辑器
                    context.insertFiles(results.succMap);
                    // 成功时返回null
                    return null;
                } else {
                    // 全部失败，返回错误信息
                    return `上传失败: ${results.errFiles.join(', ')}`;
                }
            },

            /**
             * 上传文件
             * @param file
             * @param uploadDir
             * @param url
             * @returns {Promise<{key, url: string}>}
             * @private
             */
            async _uploadSingleFile(file, uploadDir, url) {
                // 计算文件信息
                const fileItem = await Dolphin.readFile(file);
                const fileKey = (uploadDir || '') + fileItem.hash + '.' + fileItem.ext;

                // 从后端获取上传策略（Policy）
                const fileInfo = {
                    name: file.name,
                    size: file.size,
                    mime: file.type,
                    hash: fileItem.hash,
                    ext: fileItem.ext,
                    url: fileKey
                };

                // 获取token
                const tokenRes = await jQuery.post('/_uploader/qiniu/vditor/getToken.html', {file:fileInfo});
                if (!tokenRes || tokenRes.code !== 1) {
                    throw new Error('获取上传token失败：' + tokenRes.msg);
                }

                // 构建FormData
                const formData = new FormData();
                formData.append('token', tokenRes.data.token);
                formData.append('key', fileKey);
                formData.append('file', file);

                // 上传
                const response = await fetch(url, {
                    method: 'POST',
                    body: formData
                });

                const responseJson = await response.json();

                if (!response.ok || response.status !== 200) {
                    throw new Error('七牛云上传失败：' + responseJson.error);
                }

                if (!responseJson.key) {
                    throw new Error('七牛云返回格式错误');
                }

                const domain = DolphinConfig?.upload?.['qiniu.vditor']?.domain;

                return {
                    key: fileKey,
                    url: domain + fileKey
                };
            }
        });
        
        console.log('七牛云Vditor专用驱动已注册: qiniu:vditor');
    } else {
        console.error('Dolphin uploader API not found, cannot register "qiniu:vditor" driver.');
    }
});
