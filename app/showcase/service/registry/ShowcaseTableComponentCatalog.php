<?php
declare(strict_types=1);

namespace app\showcase\service\registry;

/**
 * Showcase 表格组件目录
 */
final class ShowcaseTableComponentCatalog
{
    /**
     * @return array<string, array<string, string>>
     */
    public static function groups(): array
    {
        return [
            'basic' => ['title' => '基础展示', 'summary' => '常见文本、图标、图片和时间展示列。'],
            'state' => ['title' => '状态与映射', 'summary' => '状态标签、开关、布尔值和彩色映射等列能力。'],
            'media' => ['title' => '媒体与预览', 'summary' => '图片预览、媒体放大与内容预览能力。'],
            'interactive' => ['title' => '交互与操作', 'summary' => '链接跳转、操作按钮与交互型列能力。'],
            'advanced' => ['title' => '高级能力', 'summary' => '回调渲染、复杂映射和高级定制能力。'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function components(): array
    {
        return [
            'basic.image' => [
                'key' => 'basic.image',
                'renderer' => 'table',
                'group' => 'basic',
                'group_title' => '基础展示',
                'title' => 'image 图片缩略图',
                'summary' => '用于展示单图、多图缩略图和基础图片列样式。',
                'status' => 'available',
                'tags' => ['缩略图', '多图', '占位图'],
                'doc_links' => ['docs/表格/README.md'],
                'source_refs' => [
                    ['path' => 'app/common/render/table/image/Item.php', 'label' => '内置列实现', 'description' => 'image 列的服务端定义。'],
                    ['path' => 'app/common/render/table/image/item.html', 'label' => '列模板', 'description' => 'image 列的前端模板。'],
                ],
                'builder' => 'app\\showcase\\service\\components\\table\\ImageColumnPage',
            ],
            'basic.icon' => [
                'key' => 'basic.icon',
                'renderer' => 'table',
                'group' => 'basic',
                'group_title' => '基础展示',
                'title' => 'icon 图标列',
                'summary' => '用于展示图标类字段、状态图标或小型视觉提示。',
                'status' => 'available',
                'tags' => ['图标', '状态提示', '视觉符号'],
                'doc_links' => ['docs/表格/README.md'],
                'source_refs' => [
                    ['path' => 'app/common/render/table/icon/Item.php', 'label' => '内置列实现', 'description' => 'icon 列的服务端定义。'],
                    ['path' => 'app/common/render/table/icon/item.html', 'label' => '列模板', 'description' => 'icon 列的前端模板。'],
                ],
                'builder' => 'app\\showcase\\service\\components\\table\\IconColumnPage',
            ],
            'basic.datetime' => [
                'key' => 'basic.datetime',
                'renderer' => 'table',
                'group' => 'basic',
                'group_title' => '基础展示',
                'title' => 'datetime 时间列',
                'summary' => '用于格式化日期时间值，并承接快速编辑等时间型能力。',
                'status' => 'available',
                'tags' => ['日期时间', '格式化', '快速编辑'],
                'doc_links' => ['docs/表格/表格快速编辑功能使用指南.md'],
                'source_refs' => [
                    ['path' => 'app/common/render/table/datetime/Item.php', 'label' => '内置列实现', 'description' => 'datetime 列的服务端定义。'],
                    ['path' => 'app/common/render/table/datetime/item.html', 'label' => '列模板', 'description' => 'datetime 列的前端模板。'],
                ],
                'builder' => 'app\\showcase\\service\\components\\table\\DatetimeColumnPage',
            ],
            'state.status' => [
                'key' => 'state.status',
                'renderer' => 'table',
                'group' => 'state',
                'group_title' => '状态与映射',
                'title' => 'status 状态标签',
                'summary' => '用于将状态值映射为带颜色的标签展示。',
                'status' => 'available',
                'tags' => ['状态映射', '颜色标签', '业务状态'],
                'doc_links' => ['docs/表格/README.md'],
                'source_refs' => [
                    ['path' => 'app/common/render/table/status/Item.php', 'label' => '内置列实现', 'description' => 'status 列的服务端定义。'],
                    ['path' => 'app/common/render/table/status/item.html', 'label' => '列模板', 'description' => 'status 列的前端模板。'],
                ],
                'builder' => 'app\\showcase\\service\\components\\table\\StatusColumnPage',
            ],
            'state.switch' => [
                'key' => 'state.switch',
                'renderer' => 'table',
                'group' => 'state',
                'group_title' => '状态与映射',
                'title' => 'switch 开关列',
                'summary' => '用于在表格内展示并切换二元状态值。',
                'status' => 'available',
                'tags' => ['开关', '即时切换', '二元状态'],
                'doc_links' => ['docs/表格/表格事件系统使用指南.md'],
                'source_refs' => [
                    ['path' => 'app/common/render/table/switch/Item.php', 'label' => '内置列实现', 'description' => 'switch 列的服务端定义。'],
                    ['path' => 'app/common/render/table/switch/item.html', 'label' => '列模板', 'description' => 'switch 列的前端模板。'],
                ],
                'builder' => 'app\\showcase\\service\\components\\table\\SwitchColumnPage',
            ],
            'state.yes_no' => [
                'key' => 'state.yes_no',
                'renderer' => 'table',
                'group' => 'state',
                'group_title' => '状态与映射',
                'title' => 'yes_no 是/否列',
                'summary' => '用于将布尔值或枚举值映射为是/否展示。',
                'status' => 'available',
                'tags' => ['布尔映射', '是/否', '轻量状态'],
                'doc_links' => ['docs/表格/README.md'],
                'source_refs' => [
                    ['path' => 'app/common/render/table/yes_no/Item.php', 'label' => '内置列实现', 'description' => 'yes_no 列的服务端定义。'],
                    ['path' => 'app/common/render/table/yes_no/item.html', 'label' => '列模板', 'description' => 'yes_no 列的前端模板。'],
                ],
                'builder' => 'app\\showcase\\service\\components\\table\\YesNoColumnPage',
            ],
            'state.color' => [
                'key' => 'state.color',
                'renderer' => 'table',
                'group' => 'state',
                'group_title' => '状态与映射',
                'title' => 'color 颜色列',
                'summary' => '用于直接展示颜色值，或将状态映射为颜色块。',
                'status' => 'available',
                'tags' => ['颜色块', '色值', '视觉映射'],
                'doc_links' => ['docs/表格/README.md'],
                'source_refs' => [
                    ['path' => 'app/common/render/table/color/Item.php', 'label' => '内置列实现', 'description' => 'color 列的服务端定义。'],
                    ['path' => 'app/common/render/table/color/item.html', 'label' => '列模板', 'description' => 'color 列的前端模板。'],
                ],
                'builder' => 'app\\showcase\\service\\components\\table\\ColorColumnPage',
            ],
            'media.preview' => [
                'key' => 'media.preview',
                'renderer' => 'table',
                'group' => 'media',
                'group_title' => '媒体与预览',
                'title' => 'preview 预览列',
                'summary' => '用于图片、附件或富内容的预览入口展示。',
                'status' => 'available',
                'tags' => ['弹层预览', '图片预览', '附件查看'],
                'doc_links' => ['docs/表格/preview.md'],
                'source_refs' => [
                    ['path' => 'app/common/render/table/preview/Item.php', 'label' => '内置列实现', 'description' => 'preview 列的服务端定义。'],
                    ['path' => 'app/common/render/table/preview/item.html', 'label' => '列模板', 'description' => 'preview 列的前端模板。'],
                ],
                'builder' => 'app\\showcase\\service\\components\\table\\PreviewColumnPage',
            ],
            'interactive.url' => [
                'key' => 'interactive.url',
                'renderer' => 'table',
                'group' => 'interactive',
                'group_title' => '交互与操作',
                'title' => 'url 链接列',
                'summary' => '用于将字段值渲染为可跳转链接或外部地址。',
                'status' => 'available',
                'tags' => ['链接跳转', '外链', '打开方式'],
                'doc_links' => ['docs/表格/README.md'],
                'source_refs' => [
                    ['path' => 'app/common/render/table/url/Item.php', 'label' => '内置列实现', 'description' => 'url 列的服务端定义。'],
                    ['path' => 'app/common/render/table/url/item.html', 'label' => '列模板', 'description' => 'url 列的前端模板。'],
                ],
                'builder' => 'app\\showcase\\service\\components\\table\\UrlColumnPage',
            ],
            'interactive.actions' => [
                'key' => 'interactive.actions',
                'renderer' => 'table',
                'group' => 'interactive',
                'group_title' => '交互与操作',
                'title' => 'actions 操作列',
                'summary' => '用于承载行级按钮、权限控制和常用操作入口。',
                'status' => 'available',
                'tags' => ['操作按钮', '权限控制', '行级动作'],
                'doc_links' => ['docs/表格/添加右侧按钮.md', 'docs/表格/表格工具栏权限控制使用指南.md'],
                'source_refs' => [
                    ['path' => 'app/common/render/table/actions/Item.php', 'label' => '内置列实现', 'description' => 'actions 列的服务端定义。'],
                    ['path' => 'app/common/render/table/actions/item.html', 'label' => '列模板', 'description' => 'actions 列的前端模板。'],
                ],
                'builder' => 'app\\showcase\\service\\components\\table\\ActionsColumnPage',
            ],
            'interactive.select' => [
                'key' => 'interactive.select',
                'renderer' => 'table',
                'group' => 'interactive',
                'group_title' => '交互与操作',
                'title' => 'select 下拉映射列',
                'summary' => '用于在表格中展示下拉映射值或承载快速编辑选择。',
                'status' => 'available',
                'tags' => ['下拉映射', '快速编辑', '枚举选择'],
                'doc_links' => ['docs/表格/表格快速编辑功能使用指南.md'],
                'source_refs' => [
                    ['path' => 'app/common/render/table/select/Item.php', 'label' => '内置列实现', 'description' => 'select 列的服务端定义。'],
                    ['path' => 'app/common/render/table/select/item.html', 'label' => '列模板', 'description' => 'select 列的前端模板。'],
                ],
                'builder' => 'app\\showcase\\service\\components\\table\\SelectColumnPage',
            ],
            'advanced.callback' => [
                'key' => 'advanced.callback',
                'renderer' => 'table',
                'group' => 'advanced',
                'group_title' => '高级能力',
                'title' => 'callback 回调列',
                'summary' => '用于通过回调函数或自定义逻辑生成复杂展示结果。',
                'status' => 'available',
                'tags' => ['回调渲染', '复杂逻辑', '自定义输出'],
                'doc_links' => ['docs/表格/自定义扩展列开发指南.md', 'docs/表格/表格列组件开发契约.md'],
                'source_refs' => [
                    ['path' => 'app/common/render/table/callback/Item.php', 'label' => '内置列实现', 'description' => 'callback 列的服务端定义。'],
                ],
                'builder' => 'app\\showcase\\service\\components\\table\\CallbackColumnPage',
            ],
        ];
    }
}
