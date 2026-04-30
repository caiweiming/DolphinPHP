# Chart 扩展模板

此目录用于为 `Chart` 渲染器创建新的自定义图表类型提供可复制模板。

使用方式：

1. 复制 `Type.php.stub` 到 `extend/chart/<type>/Type.php`
2. 复制 `chart-type.js.stub` 到 `public/extend/chart/<type>/<slug>.js`
3. 复制 `chart-type.css.stub` 到 `public/extend/chart/<type>/<slug>.css`
4. 将模板中的占位符替换为 `{{type}}`、`{{namespace}}`、`{{dir}}`、`{{slug}}`、`{{label}}`

说明：

- 本目录仅作为模板，不参与运行时自动发现
- 模板文件统一使用 `.stub` 后缀，避免被误加载
- 建议类型目录名使用 ASCII slug，例如 `bubble`、`geo_map`
