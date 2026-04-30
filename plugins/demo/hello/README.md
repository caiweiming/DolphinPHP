# demo/hello

这是一个用于演示 DolphinPHP 插件机制的最小示例插件，主要用于验证插件从安装、启用到运行时接入的完整链路。

## 插件用途

- 提供一个独立的插件页面和一个健康检查接口
- 演示插件如何注册表单项、表格列、图表类型、上传驱动
- 演示插件如何挂载命令、中间件、组件处理器等能力骨架
- 演示插件如何用 `topbar.tools.fragment` 在后台顶部工具栏复现一个完整通知铃铛，并为壳层直接注入 JS/CSS 资源
- 演示插件静态资源、语言包、路由、权限声明如何接入框架

## 启用后可验证内容

- 访问插件首页：`/plugin/demo/hello`
- 健康检查接口：`/plugin/demo/hello/ping`
- 后台顶部通知铃铛：启用插件后，在后台顶部工具栏出现“示例通知”图标，完整按钮与下拉结构都由插件 `fragment` 模板提供
- 表单组件接口：`/_form/demo_hello/options`
- 命令行示例：`php think make:dp-plugin` 生成插件骨架后可参考本插件结构

## 目录说明

- `plugin.json`：插件元数据
- `src/Plugin.php`：插件生命周期入口
- `src/ServiceProvider.php`：服务注册入口
- `routes.php`：插件路由
- `permissions.php`：插件权限声明
- `src/Form`：表单项示例
- `src/Table`：表格列示例
- `src/Chart`：图表类型示例
- `src/Upload`：上传驱动示例
- `src/Command`：命令示例
- `src/Middleware`：中间件示例
- `src/Component`：组件处理器示例

## 使用说明

1. 作为 Composer 插件包发布时，使用根目录下的 `composer.json`，并保持 `name = demo/hello`、`type = dolphinphp-plugin`。
2. 宿主项目接入 Composer 安装器后，可通过 `composer require demo/hello` 将本插件安装到 `plugins/demo/hello/`。
3. Composer 落盘后，仍需在插件管理页面或命令行先安装插件，再启用插件。
4. 启用成功后，系统会同步插件权限并发布 `public/` 下的静态资源。
5. 如果修改了 `public/hello.js` 或 `public/hello.css`，可执行 `php think plugin:publish demo/hello` 重新发布静态资源，或在后台插件管理页点击“发布”按钮。
6. 如需查看插件页面或接口，可直接访问上面的示例路由。
7. 如需开发自己的插件，可以此插件目录结构为参考模板。

## 注意事项

- 如果启用时报静态资源目录无写权限，需要确认 `public/plugins` 对 Web 进程可写。
- 开发环境默认优先使用软链接；生产环境如果不希望使用软链接，可将 `plugin.asset.symlink` 设为 `false`，改为复制模式。
- 如果插件提供后台入口，建议补充 `README.md`，明确说明用途、配置项和访问方式。
- Composer 分发模式 v1 只支持安装到默认目录 `plugins/<vendor>/<name>/`，不支持直接留在 `vendor/` 中原位运行。
