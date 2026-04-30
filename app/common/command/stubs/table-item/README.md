# Table 列组件模板

此目录用于为 `Table` 渲染器创建新的自定义扩展列提供可复制模板。

使用方式：

1. 复制 `Item.php.stub` 到 `extend/table/<type>/Item.php`
2. 复制 `item.html.stub` 到 `extend/table/<type>/item.html`
3. 将模板中的占位符替换为 `{{type}}`、`{{type_key}}`、`{{type_identifier}}`、`{{namespace}}`、`{{label}}`

说明：

- 本目录仅作为命令模板，不参与运行时自动发现
- 模板文件统一使用 `.stub` 后缀，避免被误加载
- 建议类型目录名使用 ASCII slug，例如 `badge`、`stats/badge`
- 扩展类型按约定自动发现，无需注册到 `config/table.php`
