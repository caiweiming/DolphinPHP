# DolphinPHP

DophinPHP（海豚PHP）是一个基于 ThinkPHP 8 的 后台开发脚手架，提供管理后台、多应用组织、权限体系、插件系统，借助内置的表单、表格、图表三大渲染器，可以快速的搭建后台功能。

## 运行环境

- PHP `>= 8.2.0`
- Composer `>= 2.5`
- MySQL / MariaDB

建议启用的 PHP 扩展：

- `openssl`
- `mbstring`
- `pdo`
- `curl`
- `fileinfo`
- `dom`
- `libxml`

## 快速开始

### 1. 部署框架

新建站点，将站点目录指向 `public` 目录。

### 2. 设置伪静态

```text
location / {
	if (!-e $request_filename){
		rewrite  ^(.*)$  /index.php?s=$1  last;   break;
	}
}
```

### 3. 具备可写权限

- `runtime/`
- `public/plugins/`
- 其他上传、缓存、日志目录

### 3. 执行安装向导

项目首次访问时，如果尚未安装，系统会自动将 `/` 或 `/admin` 引导到安装向导：

安装向导会完成以下工作：

- 环境检测
- 数据库连接校验
- 写入数据库配置
- 导入初始 SQL
- 创建超级管理员
- 生成安装锁文件 `config/install.lock`

安装完成后：

- 后台入口：`/admin`
- 前台入口：`/`

## 目录结构

```text
.
├── app/           应用代码
├── config/        全局配置
├── database/      Seeder 等数据库辅助文件
├── extend/        项目级扩展
├── frontend/      前端资源或构建相关内容
├── plugins/       插件目录
├── public/        Web 根目录与静态资源
├── route/         路由定义
├── runtime/       运行时目录
└── think          ThinkPHP 命令入口
```

应用层重点目录：

- `app/admin`：后台管理、权限、系统设置、插件与应用管理
- `app/common`：公共抽象、服务、渲染器、插件与应用基础设施
- `app/install`：安装向导与安装状态管理

## 常用命令

### 启动与缓存

```bash
php think run
php think clear
php think optimize:config
php think optimize:route
php think optimize:schema
```

### 权限与队列

```bash
php think permission:sync
php think queue:listen
```

### 插件相关

```bash
php think plugin:list
php think plugin:install <vendor>/<name>
php think plugin:enable <vendor>/<name>
php think plugin:disable <vendor>/<name>
php think plugin:upgrade <vendor>/<name>
php think plugin:publish <vendor>/<name>
php think plugin:uninstall <vendor>/<name>
```

### 脚手架命令

```bash
php think make:dp-plugin
php think make:dp-form-item
php think make:dp-table-item
php think make:dp-chart-type
php think make:dp-chart-map
```

## 测试版反馈

测试版期间，建议统一通过 GitHub Discussions 提交反馈：

- `Bug 反馈`：提交异常、报错、兼容性问题
- `功能建议`：提交新需求、交互建议、流程优化建议
- `使用咨询`：提交安装、配置、插件和权限使用问题

如果反馈的是故障问题，请尽量附带版本号、运行环境、复现步骤和错误日志。

## 开发说明

### 代码组织

- 业务控制器尽量保持轻量，复杂逻辑下沉到 `app/*/service` 或 `app/common/service`
- 公共能力优先沉淀在 `app/function.php`
- 项目级扩展放在 `extend/`
- 插件扩展放在 `plugins/<vendor>/<name>/`

## 授权说明

`DolphinPHP` 当前版本采用“公开源码可访问，但受产品许可协议约束”的分发模式。

- 自然人用户可在非商业前提下免费使用、学习、修改和二次开发
- 商业使用需按部署域名取得授权
- 未经书面授权，不得改名后再次发布、出售或作为自有框架独立分发
- 第三方组件与资源的版权和许可，以其各自附带声明为准

更多细节请参阅：

- [LICENSE.txt](LICENSE.txt)
