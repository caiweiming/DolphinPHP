// 自定义js
function aa3(value){
    // console.log(DpForm.getComponent('dp-form-avatar'))
    console.log(value)
    // console.log(DpForm.getComponent('dp-form-avatar'))
}
var aa2 = function(dp) {
    console.log(111)
    let date = new Date('2021-07-26');
    dp.selectDate(date);
    dp.setViewDate(date);
}
function xx(event, vditor){
    console.log(event)
    console.log(vditor)
}

function myExports(obj) {
    // 当前示例配置项
    var options = obj.config;
    // 当前示例表格对象
    const table = DolphinTable[options.id];
    // 获得数据并清除临时字段
    var data = table.clearCacheKey(obj.data);

    console.log(data)

    // 弹出面板
    obj.openPanel({
        list: [ // 列表
            '<li data-type="csv">导出 CSV 文件</li>',
            '<li data-type="xlsx">导出 XLSX 文件</li>'
        ].join(''),
        done: function (panel, list) { // 操作列表
            list.on('click', function () {
                var type = $(this).data('type')
                if (type === 'csv') {
                    // 调用内置导出方法
                    table.exportFile(options.id, null, type);
                } else if (type === 'xlsx') {
                    console.log('export xlsx')
                    // 自助处理导出 - 如借助 sheetjs 库或服务端导出
                    // …
                }
            });
        }
    });
}

function myExports3(table, tableId, obj, self) {
    console.log(table)
    console.log(tableId)
    console.log(obj)
    console.log(self)
}

function myExports2(obj) {
    console.log(obj)
    // 当前示例配置项
    var options = obj.config;
    // 当前示例表格对象
    const table = DolphinTable[options.id];
    console.log(table)
    // 获得数据并清除临时字段
    // var data = table.clearCacheKey(obj.data);
    table.expandAll(options.id, true)
    // console.log(data)

    // 弹出面板
    obj.openPanel({
        list: [ // 列表
            '<li data-type="csv">导出 CSV 文件</li>',
            '<li data-type="xlsx">导出 XLSX 文件</li>'
        ].join(''),
        done: function (panel, list) { // 操作列表
            list.on('click', function () {
                var type = $(this).data('type')
                if (type === 'csv') {
                    // 调用内置导出方法
                    table.exportFile(options.id, null, type);
                } else if (type === 'xlsx') {
                    console.log('export xlsx')
                    // 自助处理导出 - 如借助 sheetjs 库或服务端导出
                    // …
                }
            });
        }
    });
}