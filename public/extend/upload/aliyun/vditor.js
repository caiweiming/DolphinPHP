/**
 * 阿里云上传驱动 (vditor编辑器)
 */
$(function () {
    // 检查 Dolphin.uploader API 是否存在
    if (window.Dolphin && typeof Dolphin.uploader === 'object' && typeof Dolphin.uploader.register === 'function') {
        // 注册阿里云驱动
        Dolphin.uploader.register('aliyun:vditor', {
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

                // 要保存的文件路径
                const key = (uploadDir || '') + fileItem.hash + '.' + fileItem.ext;
                // 从后端获取上传策略（Policy）
                const fileInfo = {
                    name: file.name,
                    size: file.size,
                    mime: file.type,
                    hash: fileItem.hash,
                    ext: fileItem.ext,
                    url: key
                };

                // 获取token
                const policyResponse = await jQuery.post('/_uploader/aliyun/vditor/getPolicy.html', {file:fileInfo});
                if (!policyResponse || policyResponse.code !== 1) {
                    throw new Error('获取上传token失败：' + policyResponse.msg);
                }

                const domain = DolphinConfig?.upload?.['aliyun.vditor']?.domain;

                // 构建FormData
                const formData = new FormData();
                formData.append('OSSAccessKeyId', policyResponse.data.OSSAccessKeyId);
                formData.append('Signature', policyResponse.data.Signature);
                formData.append('policy', policyResponse.data.policy);
                formData.append('success_action_status', policyResponse.data.success_action_status);
                formData.append('key', key);
                formData.append('file', file);

                // 上传
                const response = await fetch(url, {
                    method: 'POST',
                    body: formData
                });

                if (response.ok && (response.status === 204 || response.status === 200)) {
                    return {
                        key: key,
                        url: domain + key
                    };
                } else {
                    const responseText = await response.clone().text();
                    throw new Error('阿里云上传失败：' + this.getResponseMessage(responseText));
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