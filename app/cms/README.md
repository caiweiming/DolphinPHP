# CMS 最小完整示例应用说明

`cms` 不是生产级内容系统，而是 DolphinPHP 当前仓库内用于演示“一个应用应该怎样组织”的最小完整示例。

它覆盖了一个标准应用最常见的几个面：

- 应用元数据与运行时配置
- 安装、升级、卸载 SQL
- 生命周期钩子
- 后台 CRUD
- 前台入口、列表、详情
- 空状态与最小展示字段整理

如果你准备开发一个新应用，优先把这里当作结构参考，而不是把其中的业务文案直接复制到正式项目。

## 推荐阅读顺序

1. `app.json`
2. `app.php`
3. `database/install.sql`
4. `database/upgrade.sql`
5. `database/uninstall.sql`
6. `service/Hook.php`
7. `controller/admin/Category.php`
8. `controller/admin/Article.php`
9. `service/CmsContentService.php`
10. `controller/Index.php`、`controller/Article.php`
11. `view/`

## 核心文件说明

### 1. 应用元数据

- `app.json`
  - 提供静态分发元数据
  - 用于后台识别、导入、打包和版本比较

- `app.php`
  - 提供运行时配置
  - 当前示例不再显式声明生命周期钩子
  - 默认按约定解析 `app\<应用名>\service\Hook`

### 2. 数据库脚本

- `database/install.sql`
  - 初始化 `cms` 自己的演示业务表
  - 写入生命周期演示记录所需的数据

- `database/upgrade.sql`
  - 演示从旧版 `cms` 升级到当前最小示例应用时，如何补齐表结构和演示数据

- `database/uninstall.sql`
  - 删除 `cms` 自己创建的业务表和演示表
  - 用于配合后台“卸载将删除应用数据”的提示

### 3. 生命周期钩子

- `service/Hook.php`
  - 演示 `install`、`upgrade`、`uninstall`、`enable`、`disable` 钩子
  - 负责写入和清理 `runtime/cms/` 下的演示 JSON 文件
  - 用来说明“SQL 做结构，PHP 做运行态收尾”这类分工

### 4. 后台入口

- `controller/admin/Category.php`
  - 分类管理示例
  - 包含列表、新增、编辑、删除、状态切换
  - 演示删除前的关联文章保护

- `controller/admin/Article.php`
  - 文章管理示例
  - 包含列表、搜索、分类展示、状态展示、新增、编辑、删除
  - 演示后台表格与表单的最小闭环

- `controller/admin/Demo.php`
  - 生命周期演示页
  - 只读展示 `install.sql` / `upgrade.sql` 写入的演示记录

### 5. 前台入口

- `controller/Index.php`
  - 前台首页入口
  - 演示分类导航和文章摘要列表

- `controller/Article.php`
  - 前台文章列表、分类筛选、详情页入口
  - 演示上一篇 / 下一篇和浏览量展示

- `service/CmsContentService.php`
  - 统一整理前台文章排序、展示字段、上一篇 / 下一篇逻辑
  - 演示把展示层默认值和格式化放在服务层而不是散落在模板里

## 你可以重点观察什么

- 一个应用如何通过 `app.json` 和 `app.php` 同时满足“分发”和“运行时接入”
- 一个应用如何把 `install.sql`、`upgrade.sql`、`uninstall.sql` 和生命周期钩子配合起来
- 一个应用如何同时包含后台管理与前台展示，但仍保持目录职责清晰
- 一个最小示例如何处理空状态、默认展示字段和卸载清理边界

## 不建议直接照抄的部分

- 页面文案、分类名、文章标题等演示内容
- `cms` 里的默认说明文字和空状态提示
- 任何只为演示而保留的 JSON 运行时文件

更合适的做法是参考它的目录结构、分层方式和生命周期边界，再替换成你自己的业务模型与页面内容。
