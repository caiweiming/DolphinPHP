/**
 * 本地上传驱动(vditor编辑器)
 */
$(function () {
    // 检查 Dolphin.uploader API 是否存在
    if (window.Dolphin && typeof Dolphin.uploader === 'object' && typeof Dolphin.uploader.register === 'function') {
        // 注册vditor的本地上传驱动
        Dolphin.uploader.register('local:vditor', {
            /**
             * 格式化本地驱动返回是数据
             * @param files
             * @param responseText
             * @returns {string}
             */
            format(files, responseText) {
                const response = JSON.parse(responseText);
                let errFiles = [];
                let succMap = {};

                response.data.error.forEach(item => {
                    errFiles.push(item.name)
                })
                response.data.success.forEach(item => {
                    succMap[item.name] = item.url;
                })

                response.data = {
                    "errFiles": errFiles,
                    "succMap": succMap
                }

                return JSON.stringify(response)
            }
        });

    } else {
        console.error('Dolphin uploader API not found, cannot register "local" driver.');
        Dolphin.error('无法注册“local”驱动');
    }
});