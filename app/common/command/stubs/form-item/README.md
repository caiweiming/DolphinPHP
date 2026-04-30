# Form 扩展项模板

此目录用于为 `Form` 渲染器创建新的自定义扩展项提供可复制模板。

使用方式：

1. 复制 `Item.php.stub` 到 `extend/form/<type>/Item.php`
2. 复制 `item.html.stub` 到 `extend/form/<type>/item.html`
3. 复制 `form-item.js.stub` 到 `public/extend/form/<type>/<slug>.js`
4. 复制 `form-item.css.stub` 到 `public/extend/form/<type>/<slug>.css`
5. 将模板中的占位符替换为 `{{type}}`、`{{dir}}`、`{{slug}}`、`{{namespace}}`、`{{type_identifier}}`、`{{label}}`

说明：

- 本目录仅作为命令模板，不参与运行时自动发现
- 模板文件统一使用 `.stub` 后缀，避免被误加载
- 建议类型目录名使用 ASCII slug，例如 `transfer`、`stats/selector`
- 扩展类型按约定自动发现，无需注册到 `config/form.php`
