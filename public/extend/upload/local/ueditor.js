/**
 * 本地上传驱动(UEditor编辑器)
 */
$(function () {
    // 检查 Dolphin.uploader API 是否存在
    if (window.Dolphin && typeof Dolphin.uploader === 'object' && typeof Dolphin.uploader.register === 'function') {
        // 注册ueditor的本地上传驱动
        Dolphin.uploader.register('local:ueditor', {
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
             * 上传准备钩子 - 检查文件是否已存在
             * @param {File} file - 文件对象
             * @param {Object} fileItem - 文件项对象
             * @returns {Promise<Object|boolean>} 返回准备数据或false取消上传
             */
            async prepare(file, fileItem) {
                try {
                    // 计算文件信息
                    const fileInfo = await Dolphin.readFile(file);

                    // 构建文件路径
                    const uploadDir = fileItem.driverData?.uploadDir || '';
                    const path = uploadDir + fileInfo.hash + '.' + fileInfo.ext;

                    // 检查文件是否已存在
                    const checkUrl = DolphinConfig?.url?.checkFile;
                    if (!checkUrl) {
                        console.warn('[本地上传] 未配置 checkFile URL，跳过文件检查');
                        return {
                            path: path,
                            hash: fileInfo.hash,
                            ext: fileInfo.ext
                        };
                    }

                    const response = await jQuery.post(checkUrl, {
                        hash: fileInfo.hash,
                        _from: 'ueditor'
                    });

                    if (response && response.code === 1 && response.data && response.data.success && response.data.success.length > 0) {
                        const existFile = response.data.success[0];
                        console.log('[本地上传] 文件已存在，跳过上传:', existFile.url);
                        // 返回已存在文件的信息，标记为跳过上传
                        return {
                            skipUpload: true,
                            url: existFile.url,
                            path: existFile.url,
                            id: existFile.id,
                            name: existFile.name || file.name
                        };
                    }

                    // 文件不存在，返回上传所需的额外数据
                    return {
                        path: path,
                        hash: fileInfo.hash,
                        ext: fileInfo.ext
                    };
                } catch (error) {
                    console.warn('本地上传准备失败，继续上传:', error);
                    // 即使检查失败，也继续上传
                    return true;
                }
            },

            /**
             * 自定义上传处理
             * @param {File} file - 文件对象
             * @param {Object} fileItem - 文件项对象
             * @returns {Promise<Object>|undefined} 如果返回对象则使用该结果，否则使用默认上传
             */
            async upload(file, fileItem) {
                // 如果文件已存在，直接返回结果
                if (fileItem.driverData && fileItem.driverData.skipUpload) {
                    console.log('[本地上传] 使用已存在的文件');
                    return {
                        url: fileItem.driverData.url,
                        path: fileItem.driverData.path,
                        name: file.name
                    };
                }
                // 返回 undefined 表示使用默认上传流程
                return undefined;
            },

            /**
             * 上传成功钩子
             * @param {File} file - 文件对象
             * @param {Object} result - 服务器返回结果
             * @param {Object} fileItem - 文件项对象
             * @returns {Object} 处理后的结果
             */
            success(file, result, fileItem) {
                // 确保返回的数据包含 url 字段
                if (result && result.url) {
                    return result;
                }

                // 如果服务器返回的是其他格式，进行转换
                if (result && result.path) {
                    return {
                        url: result.path,
                        name: result.name || file.name
                    };
                }

                return result;
            },

            /**
             * 上传失败钩子
             * @param {File} file - 文件对象
             * @param {Error} error - 错误对象
             * @param {Object} fileItem - 文件项对象
             */
            error(file, error, fileItem) {
                console.error('UEditor本地上传失败:', error);
            }
        });

    } else {
        console.error('Dolphin uploader API not found, cannot register "local:ueditor" driver.');
    }
});
