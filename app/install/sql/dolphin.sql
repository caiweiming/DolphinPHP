/*
 Navicat Premium Dump SQL

 Source Server         : localhost
 Source Server Type    : MySQL
 Source Server Version : 50739 (5.7.39)
 Source Host           : localhost:3306
 Source Schema         : dolphin

 Target Server Type    : MySQL
 Target Server Version : 50739 (5.7.39)
 File Encoding         : 65001

 Date: 27/04/2026 17:29:47
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for dp_admin_app
-- ----------------------------
DROP TABLE IF EXISTS `dp_admin_app`;
CREATE TABLE `dp_admin_app` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `name` varchar(60) NOT NULL DEFAULT '' COMMENT '应用标识',
  `title` varchar(100) NOT NULL DEFAULT '' COMMENT '应用名称',
  `description` varchar(500) NOT NULL DEFAULT '' COMMENT '应用描述',
  `icon` varchar(120) NOT NULL DEFAULT '' COMMENT '图标',
  `version` varchar(32) NOT NULL DEFAULT '' COMMENT '版本',
  `installed_version` varchar(32) NOT NULL DEFAULT '' COMMENT '最近一次成功安装/升级版本',
  `author` varchar(120) NOT NULL DEFAULT '' COMMENT '作者',
  `provider` varchar(191) NOT NULL DEFAULT '' COMMENT '服务提供者/设置提供者',
  `settings` longtext COMMENT '应用声明型配置值(JSON)',
  `sort` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '状态:1启用,0禁用',
  `is_system` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否系统内置应用',
  `show_in_config` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否在 Config 页面显示',
  `lifecycle_status` varchar(32) NOT NULL DEFAULT 'installed' COMMENT '生命周期状态: imported/installed',
  `distribution_protocol_version` varchar(16) NOT NULL DEFAULT '' COMMENT '应用分发协议版本',
  `distribution_meta` mediumtext COMMENT '应用分发静态元数据(JSON)',
  `last_operation` varchar(32) NOT NULL DEFAULT '' COMMENT '最近一次生命周期操作',
  `last_error` varchar(500) NOT NULL DEFAULT '' COMMENT '最近一次生命周期错误',
  `install_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '安装时间',
  `enable_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '启用时间',
  `disable_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '禁用时间',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_name` (`name`),
  KEY `idx_sort` (`sort`),
  KEY `idx_status` (`status`),
  KEY `idx_is_system` (`is_system`),
  KEY `idx_config_visibility` (`is_system`,`show_in_config`,`status`),
  KEY `idx_lifecycle_status` (`lifecycle_status`,`status`),
  KEY `idx_last_operation` (`last_operation`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COMMENT='后台应用注册表';

-- ----------------------------
-- Records of dp_admin_app
-- ----------------------------
BEGIN;
INSERT INTO `dp_admin_app` (`id`, `name`, `title`, `description`, `icon`, `version`, `installed_version`, `author`, `provider`, `settings`, `sort`, `status`, `is_system`, `show_in_config`, `lifecycle_status`, `distribution_protocol_version`, `distribution_meta`, `last_operation`, `last_error`, `install_time`, `enable_time`, `disable_time`, `create_time`, `update_time`) VALUES (1, 'index', '前台应用', '默认前台应用', 'ti ti-home', '1.0.0', '1.0.0', '广东卓锐软件', '', '', 0, 1, 1, 0, 'installed', '1.0', '{\"name\":\"index\",\"title\":\"前台应用\",\"description\":\"默认前台应用\",\"version\":\"1.0.0\",\"app_api_version\":\"1.0\",\"author\":\"广东卓锐软件\",\"dependencies\":[],\"require\":{\"php\":\">=8.2.0\"}}', '', '', 0, 0, 0, 1774576629, 1775016251);
INSERT INTO `dp_admin_app` (`id`, `name`, `title`, `description`, `icon`, `version`, `installed_version`, `author`, `provider`, `settings`, `sort`, `status`, `is_system`, `show_in_config`, `lifecycle_status`, `distribution_protocol_version`, `distribution_meta`, `last_operation`, `last_error`, `install_time`, `enable_time`, `disable_time`, `create_time`, `update_time`) VALUES (2, 'admin', '系统管理', '系统后台管理应用', 'ti ti-settings', '1.0.0', '1.0.0', '广东卓锐软件', '', '', 1, 1, 1, 1, 'installed', '1.0', '{\"name\":\"admin\",\"title\":\"系统管理\",\"description\":\"系统后台管理应用\",\"version\":\"1.0.0\",\"app_api_version\":\"1.0\",\"author\":\"广东卓锐软件\",\"dependencies\":[],\"require\":{\"php\":\">=8.2.0\"}}', '', '', 0, 0, 0, 1774576629, 1775016251);
INSERT INTO `dp_admin_app` (`id`, `name`, `title`, `description`, `icon`, `version`, `installed_version`, `author`, `provider`, `settings`, `sort`, `status`, `is_system`, `show_in_config`, `lifecycle_status`, `distribution_protocol_version`, `distribution_meta`, `last_operation`, `last_error`, `install_time`, `enable_time`, `disable_time`, `create_time`, `update_time`) VALUES (3, 'install', '安装向导', 'DolphinPHP 首次安装向导应用', 'ti ti-settings-cog', '1.0.0', '1.0.0', '广东卓锐软件', '', '', 9999, 0, 1, 0, 'installed', '1.0', '{\"name\":\"install\",\"title\":\"安装向导\",\"description\":\"DolphinPHP 首次安装向导应用\",\"version\":\"1.0.0\",\"app_api_version\":\"1.0\",\"author\":\"广东卓锐软件\",\"dependencies\":[],\"require\":{\"php\":\">=8.2.0\"}}', '', '', 0, 0, 0, 1774576629, 1775016251);
COMMIT;

-- ----------------------------
-- Table structure for dp_admin_app_log
-- ----------------------------
DROP TABLE IF EXISTS `dp_admin_app_log`;
CREATE TABLE `dp_admin_app_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `app_name` varchar(60) NOT NULL DEFAULT '' COMMENT '应用标识',
  `operation` varchar(32) NOT NULL DEFAULT '' COMMENT '操作类型',
  `status` varchar(16) NOT NULL DEFAULT '' COMMENT '执行状态',
  `message` varchar(500) NOT NULL DEFAULT '' COMMENT '消息',
  `context_json` longtext COMMENT '上下文',
  `operator_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '操作人ID',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_app_name` (`app_name`),
  KEY `idx_operation` (`operation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='后台应用生命周期日志表';

-- ----------------------------
-- Records of dp_admin_app_log
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for dp_admin_config
-- ----------------------------
DROP TABLE IF EXISTS `dp_admin_config`;
CREATE TABLE `dp_admin_config` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `app` varchar(60) NOT NULL DEFAULT 'admin' COMMENT '所属应用',
  `group` varchar(60) NOT NULL DEFAULT '' COMMENT '配置分组',
  `title` varchar(100) NOT NULL DEFAULT '' COMMENT '配置名称',
  `key` varchar(120) NOT NULL DEFAULT '' COMMENT '配置键名',
  `type` varchar(32) NOT NULL DEFAULT 'text' COMMENT '表单项类型',
  `value` longtext COMMENT '当前值',
  `default_value` longtext COMMENT '默认值',
  `options` longtext COMMENT '扩展参数(JSON)',
  `rules` text COMMENT '校验规则',
  `remark` varchar(500) NOT NULL DEFAULT '' COMMENT '备注',
  `sort` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '状态:1启用,0禁用',
  `is_system` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否系统内置',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_app_key` (`app`,`key`),
  KEY `idx_sort` (`sort`),
  KEY `idx_is_system` (`is_system`),
  KEY `idx_app_group_status` (`app`,`group`,`status`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COMMENT='后台动态配置表';

-- ----------------------------
-- Records of dp_admin_config
-- ----------------------------
BEGIN;
INSERT INTO `dp_admin_config` (`id`, `app`, `group`, `title`, `key`, `type`, `value`, `default_value`, `options`, `rules`, `remark`, `sort`, `status`, `is_system`, `create_time`, `update_time`) VALUES (1, 'admin', '站点设置', '后台站点名称', 'site.admin_name', 'text', 'DolphinPHP', 'DolphinPHP', '', 'require|max:100', '用于后台页面标题、登录页标题和品牌文案展示。', 10, 1, 1, 1774488030, 1774488030);
INSERT INTO `dp_admin_config` (`id`, `app`, `group`, `title`, `key`, `type`, `value`, `default_value`, `options`, `rules`, `remark`, `sort`, `status`, `is_system`, `create_time`, `update_time`) VALUES (6, 'admin', '站点设置', '站点标语', 'site.slogan', 'text', 'DolphinPHP 后台管理开发框架', 'DolphinPHP 后台管理开发框架', '', 'max:150', '用于前台首页 Banner、副标题和 SEO 辅助文案展示。', 20, 1, 1, 1777521600, 1777521600);
INSERT INTO `dp_admin_config` (`id`, `app`, `group`, `title`, `key`, `type`, `value`, `default_value`, `options`, `rules`, `remark`, `sort`, `status`, `is_system`, `create_time`, `update_time`) VALUES (7, 'admin', '站点设置', '站点描述', 'site.description', 'textarea', 'DolphinPHP 是基于 ThinkPHP 8.0 的后台管理开发脚手架。', 'DolphinPHP 是基于 ThinkPHP 8.0 的后台管理开发脚手架。', '', 'max:500', '用于前台页面描述、SEO 描述和关于站点简介展示。', 30, 1, 1, 1777521600, 1777521600);
INSERT INTO `dp_admin_config` (`id`, `app`, `group`, `title`, `key`, `type`, `value`, `default_value`, `options`, `rules`, `remark`, `sort`, `status`, `is_system`, `create_time`, `update_time`) VALUES (8, 'admin', '站点设置', '站点LOGO', 'site.logo', 'image', '', '', '{\"driver\":\"local\",\"dir\":\"site\"}', '', '用于上传站点 Logo，供前台页头、分享卡片等位置调用。', 40, 1, 1, 1777521600, 1777521600);
INSERT INTO `dp_admin_config` (`id`, `app`, `group`, `title`, `key`, `type`, `value`, `default_value`, `options`, `rules`, `remark`, `sort`, `status`, `is_system`, `create_time`, `update_time`) VALUES (9, 'admin', '站点设置', '站点关键词', 'site.keywords', 'text', 'DolphinPHP,ThinkPHP,管理后台', 'DolphinPHP,ThinkPHP,管理后台', '', 'max:255', '用于前台页面关键词和 SEO keywords 标签展示，建议使用英文逗号分隔。', 50, 1, 1, 1777521600, 1777521600);
INSERT INTO `dp_admin_config` (`id`, `app`, `group`, `title`, `key`, `type`, `value`, `default_value`, `options`, `rules`, `remark`, `sort`, `status`, `is_system`, `create_time`, `update_time`) VALUES (10, 'admin', '站点设置', '版权信息', 'site.copyright', 'textarea', '© 2016-2026 DolphinPHP. All Rights Reserved.', '© 2016-2026 DolphinPHP. All Rights Reserved.', '', 'max:255', '用于前台页脚版权信息展示，支持中英文文案。', 60, 1, 1, 1777521600, 1777521600);
INSERT INTO `dp_admin_config` (`id`, `app`, `group`, `title`, `key`, `type`, `value`, `default_value`, `options`, `rules`, `remark`, `sort`, `status`, `is_system`, `create_time`, `update_time`) VALUES (11, 'admin', '站点设置', '备案信息', 'site.icp', 'text', '', '', '', 'max:100', '用于前台页脚备案号展示，例如：粤ICP备12345678号。', 70, 1, 1, 1777521600, 1777521600);
INSERT INTO `dp_admin_config` (`id`, `app`, `group`, `title`, `key`, `type`, `value`, `default_value`, `options`, `rules`, `remark`, `sort`, `status`, `is_system`, `create_time`, `update_time`) VALUES (2, 'admin', '登录设置', '登录页欢迎标题', 'login.welcome_title', 'text', '登录您的帐户', '登录您的帐户', '', 'require|max:100', '显示在后台登录页表单卡片顶部。', 20, 1, 1, 1774488030, 1774488030);
INSERT INTO `dp_admin_config` (`id`, `app`, `group`, `title`, `key`, `type`, `value`, `default_value`, `options`, `rules`, `remark`, `sort`, `status`, `is_system`, `create_time`, `update_time`) VALUES (3, 'admin', '登录设置', '登录验证码', 'login.enable_captcha', 'switch', '1', '1', '', '', '控制后台登录页是否显示并校验验证码，关闭后登录时不再要求输入验证码。', 30, 1, 1, 1774490554, 1774490554);
INSERT INTO `dp_admin_config` (`id`, `app`, `group`, `title`, `key`, `type`, `value`, `default_value`, `options`, `rules`, `remark`, `sort`, `status`, `is_system`, `create_time`, `update_time`) VALUES (4, 'admin', '上传设置', '附件默认上传驱动', 'upload.attachment_default_driver', 'select', 'local', 'local', '{\"options\":{\"local\":\"本地存储\",\"aliyun\":\"阿里云 OSS\",\"qiniu\":\"七牛云存储\"}}', 'require', '仅作用于附件管理上传弹窗，不影响其他业务表单的上传驱动默认值。', 30, 1, 1, 1774490554, 1774512941);
COMMIT;

-- ----------------------------
-- Table structure for dp_admin_data_permission
-- ----------------------------
DROP TABLE IF EXISTS `dp_admin_data_permission`;
CREATE TABLE `dp_admin_data_permission` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `role_id` int(11) unsigned NOT NULL COMMENT '角色ID',
  `resource_type` varchar(50) NOT NULL COMMENT '资源类型:order,user,product等',
  `department_ids` varchar(500) DEFAULT NULL COMMENT '部门ID列表,逗号分隔',
  `create_time` bigint(20) unsigned DEFAULT NULL COMMENT '创建时间戳',
  `update_time` bigint(20) unsigned DEFAULT NULL COMMENT '更新时间戳',
  PRIMARY KEY (`id`),
  KEY `idx_role_resource` (`role_id`,`resource_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='自定义数据权限表';

-- ----------------------------
-- Records of dp_admin_data_permission
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for dp_admin_department
-- ----------------------------
DROP TABLE IF EXISTS `dp_admin_department`;
CREATE TABLE `dp_admin_department` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '部门ID',
  `parent_id` int(11) unsigned DEFAULT '0' COMMENT '父级部门ID',
  `name` varchar(100) NOT NULL COMMENT '部门名称',
  `code` varchar(50) DEFAULT NULL COMMENT '部门编码',
  `level` int(11) unsigned DEFAULT '1' COMMENT '层级:1一级,2二级...',
  `path` varchar(500) DEFAULT NULL COMMENT '层级路径:1,2,5',
  `leader_id` int(11) unsigned DEFAULT NULL COMMENT '部门负责人ID',
  `sort` int(11) DEFAULT '0' COMMENT '排序',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态:1正常,0禁用',
  `remark` varchar(500) DEFAULT NULL COMMENT '备注',
  `create_time` bigint(20) unsigned DEFAULT NULL COMMENT '创建时间戳',
  `update_time` bigint(20) unsigned DEFAULT NULL COMMENT '更新时间戳',
  `delete_time` bigint(20) unsigned DEFAULT NULL COMMENT '软删除时间戳',
  PRIMARY KEY (`id`),
  KEY `idx_parent` (`parent_id`),
  KEY `idx_path` (`path`(255))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='部门表';

-- ----------------------------
-- Records of dp_admin_department
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for dp_admin_file
-- ----------------------------
DROP TABLE IF EXISTS `dp_admin_file`;
CREATE TABLE `dp_admin_file` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '文件名',
  `url` varchar(255) NOT NULL DEFAULT '' COMMENT '文件链接',
  `thumb` varchar(255) NOT NULL DEFAULT '' COMMENT '缩略图路径',
  `mime` varchar(128) NOT NULL DEFAULT '' COMMENT '文件mime类型',
  `ext` char(8) NOT NULL DEFAULT '' COMMENT '文件类型',
  `size` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '文件大小',
  `sha1` char(40) NOT NULL DEFAULT '' COMMENT 'sha1 散列值',
  `driver` varchar(16) NOT NULL DEFAULT 'local' COMMENT '上传驱动',
  `status` tinyint(2) unsigned NOT NULL DEFAULT '1' COMMENT '状态',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '上传时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `sha1` (`sha1`) USING BTREE,
  KEY `status` (`status`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='附件表';

-- ----------------------------
-- Records of dp_admin_file
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for dp_admin_icon
-- ----------------------------
DROP TABLE IF EXISTS `dp_admin_icon`;
CREATE TABLE `dp_admin_icon` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `lib_id` varchar(64) NOT NULL DEFAULT '' COMMENT '图标库标识',
  `label` varchar(100) NOT NULL DEFAULT '' COMMENT '图标库名称',
  `source_type` varchar(32) NOT NULL DEFAULT 'iconfont' COMMENT '来源类型',
  `source_url` varchar(500) NOT NULL DEFAULT '' COMMENT '源 CSS 链接',
  `prefix` varchar(64) NOT NULL DEFAULT '' COMMENT '图标类前缀',
  `base_class` varchar(64) NOT NULL DEFAULT '' COMMENT '基础类名',
  `autoload` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否自动加载 CSS',
  `html` longtext NOT NULL COMMENT '图标按钮 HTML',
  `icon_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '图标数量',
  `sort` int(11) NOT NULL DEFAULT '100' COMMENT '排序值',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '状态',
  `sync_status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '最近同步状态',
  `last_sync_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '最近同步时间',
  `last_error` varchar(500) NOT NULL DEFAULT '' COMMENT '最近错误信息',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_lib_id` (`lib_id`),
  KEY `idx_source_type` (`source_type`),
  KEY `idx_status` (`status`),
  KEY `idx_sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='后台在线图标库表';

-- ----------------------------
-- Records of dp_admin_icon
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for dp_admin_log
-- ----------------------------
DROP TABLE IF EXISTS `dp_admin_log`;
CREATE TABLE `dp_admin_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `url` varchar(255) NOT NULL DEFAULT '' COMMENT '操作url',
  `type` varchar(255) NOT NULL DEFAULT '' COMMENT '操作类别',
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT '操作标题',
  `content` longtext NOT NULL COMMENT '操作内容',
  `ip` varchar(64) NOT NULL DEFAULT '' COMMENT 'IP地址',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `status` int(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`) USING BTREE,
  KEY `url` (`url`) USING BTREE,
  KEY `title` (`type`) USING BTREE,
  KEY `status` (`status`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COMMENT='系统日志表';

-- ----------------------------
-- Records of dp_admin_log
-- ----------------------------
BEGIN;
INSERT INTO `dp_admin_log` (`id`, `uid`, `url`, `type`, `title`, `content`, `ip`, `create_time`, `status`) VALUES (1, 0, '/admin', '安全事件', '会话超时', '{\"security_event\":\"会话超时\",\"timestamp\":1777272460,\"request_method\":\"GET\",\"request_url\":\"/admin\",\"user_agent\":\"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36\",\"ip_address\":\"::1\",\"user_id\":1,\"login_time\":1777253917,\"timeout_sec\":7200}', '::1', 1777272460, 2);
INSERT INTO `dp_admin_log` (`id`, `uid`, `url`, `type`, `title`, `content`, `ip`, `create_time`, `status`) VALUES (2, 0, '/admin/Login/index.html', '安全事件', '密码验证失败', '{\"security_event\":\"密码验证失败\",\"timestamp\":1777272465,\"request_method\":\"POST\",\"request_url\":\"/admin/Login/index.html\",\"user_agent\":\"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36\",\"ip_address\":\"::1\",\"username\":\"admin\",\"user_id\":1}', '::1', 1777272465, 3);
INSERT INTO `dp_admin_log` (`id`, `uid`, `url`, `type`, `title`, `content`, `ip`, `create_time`, `status`) VALUES (3, 0, '/admin/Login/index.html', '安全事件', '用户登录成功', '{\"security_event\":\"用户登录成功\",\"timestamp\":1777272467,\"request_method\":\"POST\",\"request_url\":\"/admin/Login/index.html\",\"user_agent\":\"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36\",\"ip_address\":\"::1\",\"username\":\"admin\",\"user_id\":1,\"auto_login\":false,\"last_login_ip\":\"::1\",\"current_login_ip\":\"::1\",\"login_time\":\"2026-04-27 14:47:47\",\"user_status\":1,\"source\":\"web_admin\"}', '::1', 1777272467, 1);
INSERT INTO `dp_admin_log` (`id`, `uid`, `url`, `type`, `title`, `content`, `ip`, `create_time`, `status`) VALUES (4, 0, '/admin/permission/sync.html?_t=9422f45cbb918d3b1b62dcdf9745ae1e&_pop=1', '用户操作', '同步权限', '{\"action_data\":{\"app\":\"exam\",\"incremental\":\"1\",\"added\":13,\"skipped\":0,\"failed\":0,\"details\":[\"[添加] 规则包管理 (rule.package)\",\"[添加] 新增 (exam.rule.rule_package.create)\",\"[添加] 评分表模板管理 (rule.score_sheet_template)\",\"[添加] 考试项目管理 (rule.exam_item)\",\"[添加] 规则版本管理 (rule.version)\",\"[添加] 新增 (exam.rule.rule_version.create)\",\"[添加] 查看 (exam.rule.rule_version.view)\",\"[添加] 项目规则管理 (rule.item_rule)\",\"[添加] 评分单元管理 (rule.item_component)\",\"[添加] 考试方案管理 (exam.plan)\",\"[添加] 新增 (exam.exam.exam_plan.create)\",\"[添加] 考生管理 (exam.candidate)\",\"[添加] 考试方案项目管理 (exam.plan_item)\"]}}', '::1', 1777272488, 1);
INSERT INTO `dp_admin_log` (`id`, `uid`, `url`, `type`, `title`, `content`, `ip`, `create_time`, `status`) VALUES (5, 0, '/admin/logout/index.html', '用户操作', '用户登出', '{\"action_data\":{\"user_id\":1,\"username\":\"admin\",\"ip\":\"::1\",\"user_agent\":\"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36\",\"logout_time\":\"2026-04-27 14:55:58\"}}', '::1', 1777272958, 1);
INSERT INTO `dp_admin_log` (`id`, `uid`, `url`, `type`, `title`, `content`, `ip`, `create_time`, `status`) VALUES (6, 0, '/admin/logout/index.html', '用户操作', '用户登出', '{\"action_data\":{\"user_id\":1,\"username\":\"admin\",\"ip\":\"::1\",\"user_agent\":\"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36\",\"logout_time\":\"2026-04-27 14:55:58\"}}', '::1', 1777272958, 1);
INSERT INTO `dp_admin_log` (`id`, `uid`, `url`, `type`, `title`, `content`, `ip`, `create_time`, `status`) VALUES (7, 0, '/admin/Login/index.html', '安全事件', '用户登录成功', '{\"security_event\":\"用户登录成功\",\"timestamp\":1777272963,\"request_method\":\"POST\",\"request_url\":\"/admin/Login/index.html\",\"user_agent\":\"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36\",\"ip_address\":\"::1\",\"username\":\"admin\",\"user_id\":1,\"auto_login\":false,\"last_login_ip\":\"::1\",\"current_login_ip\":\"::1\",\"login_time\":\"2026-04-27 14:56:03\",\"user_status\":1,\"source\":\"web_admin\"}', '::1', 1777272963, 1);
INSERT INTO `dp_admin_log` (`id`, `uid`, `url`, `type`, `title`, `content`, `ip`, `create_time`, `status`) VALUES (8, 0, '/admin/permission/sync.html?_t=0642d66a7fbfca32e3b840eb26f0afee&_pop=1', '用户操作', '同步权限', '{\"action_data\":{\"app\":\"exam\",\"incremental\":\"1\",\"added\":0,\"skipped\":13,\"failed\":0,\"details\":[\"[跳过] 规则包管理 (rule.package)\",\"[跳过] 新增 (exam.rule.rule_package.create)\",\"[跳过] 评分表模板管理 (rule.score_sheet_template)\",\"[跳过] 考试项目管理 (rule.exam_item)\",\"[跳过] 规则版本管理 (rule.version)\",\"[跳过] 新增 (exam.rule.rule_version.create)\",\"[跳过] 查看 (exam.rule.rule_version.view)\",\"[跳过] 项目规则管理 (rule.item_rule)\",\"[跳过] 评分单元管理 (rule.item_component)\",\"[跳过] 考试方案管理 (exam.plan)\",\"[跳过] 新增 (exam.exam.exam_plan.create)\",\"[跳过] 考生管理 (exam.candidate)\",\"[跳过] 考试方案项目管理 (exam.plan_item)\"]}}', '::1', 1777272986, 1);
COMMIT;

-- ----------------------------
-- Table structure for dp_admin_permission
-- ----------------------------
DROP TABLE IF EXISTS `dp_admin_permission`;
CREATE TABLE `dp_admin_permission` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '权限ID',
  `parent_id` int(11) unsigned DEFAULT '0' COMMENT '父级权限ID',
  `name` varchar(100) NOT NULL COMMENT '权限名称',
  `code` varchar(100) NOT NULL COMMENT '权限标识',
  `type` enum('menu','button','api') DEFAULT 'menu' COMMENT '权限类型:menu菜单,button按钮,api接口',
  `route` varchar(200) DEFAULT NULL COMMENT '路由地址',
  `method` varchar(20) DEFAULT NULL COMMENT '请求方法:GET,POST等',
  `icon` varchar(100) DEFAULT NULL COMMENT '图标',
  `component` varchar(200) DEFAULT NULL COMMENT '组件路径',
  `level` int(11) unsigned DEFAULT '1' COMMENT '层级',
  `path` varchar(500) DEFAULT NULL COMMENT '层级路径',
  `sort` int(11) DEFAULT '0' COMMENT '排序',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态:1显示,0隐藏',
  `visible` tinyint(1) DEFAULT '1' COMMENT '是否在菜单显示:1是,0否',
  `cache` tinyint(1) DEFAULT '0' COMMENT '是否缓存:1是,0否',
  `remark` varchar(500) DEFAULT NULL COMMENT '备注',
  `create_time` bigint(20) unsigned DEFAULT NULL COMMENT '创建时间戳',
  `update_time` bigint(20) unsigned DEFAULT NULL COMMENT '更新时间戳',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `idx_parent` (`parent_id`),
  KEY `idx_type` (`type`),
  KEY `idx_route` (`route`),
  KEY `idx_perm_type_status` (`type`,`status`)
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COMMENT='权限表';

-- ----------------------------
-- Records of dp_admin_permission
-- ----------------------------
BEGIN;
INSERT INTO `dp_admin_permission` (`id`, `parent_id`, `name`, `code`, `type`, `route`, `method`, `icon`, `component`, `level`, `path`, `sort`, `status`, `visible`, `cache`, `remark`, `create_time`, `update_time`) VALUES (1, 0, '系统管理', 'system', 'menu', NULL, NULL, 'ti ti-settings', NULL, 1, '', 1, 1, 1, 0, '系统后台管理应用', 1776940648, 1776940648);
INSERT INTO `dp_admin_permission` (`id`, `parent_id`, `name`, `code`, `type`, `route`, `method`, `icon`, `component`, `level`, `path`, `sort`, `status`, `visible`, `cache`, `remark`, `create_time`, `update_time`) VALUES (2, 1, '图标管理', 'admin.icon', 'menu', 'admin/icon/index', NULL, 'ti ti-icons', NULL, 2, ',', 80, 1, 1, 0, '自动生成', 1776940648, 1776940648);
INSERT INTO `dp_admin_permission` (`id`, `parent_id`, `name`, `code`, `type`, `route`, `method`, `icon`, `component`, `level`, `path`, `sort`, `status`, `visible`, `cache`, `remark`, `create_time`, `update_time`) VALUES (3, 2, '新增', 'admin.icon.create', 'button', 'admin/icon/create', NULL, NULL, NULL, 3, ',,', 0, 1, 1, 0, '自动生成', 1776940648, 1776940648);
INSERT INTO `dp_admin_permission` (`id`, `parent_id`, `name`, `code`, `type`, `route`, `method`, `icon`, `component`, `level`, `path`, `sort`, `status`, `visible`, `cache`, `remark`, `create_time`, `update_time`) VALUES (4, 2, '编辑', 'admin.icon.edit', 'button', 'admin/icon/edit', NULL, NULL, NULL, 3, ',,', 0, 1, 1, 0, '自动生成', 1776940648, 1776940648);
INSERT INTO `dp_admin_permission` (`id`, `parent_id`, `name`, `code`, `type`, `route`, `method`, `icon`, `component`, `level`, `path`, `sort`, `status`, `visible`, `cache`, `remark`, `create_time`, `update_time`) VALUES (5, 1, '应用管理', 'admin.app', 'menu', 'admin/app/index', NULL, 'ti ti-components', NULL, 2, ',', 70, 1, 1, 0, '自动生成', 1776940648, 1776940648);
INSERT INTO `dp_admin_permission` (`id`, `parent_id`, `name`, `code`, `type`, `route`, `method`, `icon`, `component`, `level`, `path`, `sort`, `status`, `visible`, `cache`, `remark`, `create_time`, `update_time`) VALUES (6, 5, '导入', 'admin.app.import', 'button', 'admin/app/import', NULL, NULL, NULL, 3, ',,', 0, 1, 1, 0, '自动生成', 1776940648, 1776940648);
INSERT INTO `dp_admin_permission` (`id`, `parent_id`, `name`, `code`, `type`, `route`, `method`, `icon`, `component`, `level`, `path`, `sort`, `status`, `visible`, `cache`, `remark`, `create_time`, `update_time`) VALUES (7, 5, '启用', 'admin.app.enable', 'button', 'admin/app/enable', NULL, NULL, NULL, 3, ',,', 0, 1, 1, 0, '自动生成', 1776940648, 1776940648);
INSERT INTO `dp_admin_permission` (`id`, `parent_id`, `name`, `code`, `type`, `route`, `method`, `icon`, `component`, `level`, `path`, `sort`, `status`, `visible`, `cache`, `remark`, `create_time`, `update_time`) VALUES (8, 5, '禁用', 'admin.app.disable', 'button', 'admin/app/disable', NULL, NULL, NULL, 3, ',,', 0, 1, 1, 0, '自动生成', 1776940648, 1776940648);
INSERT INTO `dp_admin_permission` (`id`, `parent_id`, `name`, `code`, `type`, `route`, `method`, `icon`, `component`, `level`, `path`, `sort`, `status`, `visible`, `cache`, `remark`, `create_time`, `update_time`) VALUES (9, 5, '编辑', 'admin.app.edit', 'button', 'admin/app/edit', NULL, NULL, NULL, 3, ',,', 0, 1, 1, 0, '自动生成', 1776940648, 1776940648);
INSERT INTO `dp_admin_permission` (`id`, `parent_id`, `name`, `code`, `type`, `route`, `method`, `icon`, `component`, `level`, `path`, `sort`, `status`, `visible`, `cache`, `remark`, `create_time`, `update_time`) VALUES (10, 1, '附件管理', 'admin.file', 'menu', 'admin/file/index', NULL, 'ti ti-files', NULL, 2, ',', 90, 1, 1, 0, '自动生成', 1776940648, 1776940648);
INSERT INTO `dp_admin_permission` (`id`, `parent_id`, `name`, `code`, `type`, `route`, `method`, `icon`, `component`, `level`, `path`, `sort`, `status`, `visible`, `cache`, `remark`, `create_time`, `update_time`) VALUES (11, 10, '新增', 'admin.file.create', 'button', 'admin/file/create', NULL, NULL, NULL, 3, ',,', 0, 1, 1, 0, '自动生成', 1776940648, 1776940648);
INSERT INTO `dp_admin_permission` (`id`, `parent_id`, `name`, `code`, `type`, `route`, `method`, `icon`, `component`, `level`, `path`, `sort`, `status`, `visible`, `cache`, `remark`, `create_time`, `update_time`) VALUES (12, 1, '插件管理', 'admin.plugin', 'menu', 'admin/plugin/index', NULL, 'ti ti-plug', NULL, 2, ',', 100, 1, 1, 0, '自动生成', 1776940648, 1776940648);
INSERT INTO `dp_admin_permission` (`id`, `parent_id`, `name`, `code`, `type`, `route`, `method`, `icon`, `component`, `level`, `path`, `sort`, `status`, `visible`, `cache`, `remark`, `create_time`, `update_time`) VALUES (13, 12, '详情', 'admin.plugin.detail', 'button', 'admin/plugin/detail', NULL, NULL, NULL, 3, ',,', 0, 1, 1, 0, '自动生成', 1776940648, 1776940648);
INSERT INTO `dp_admin_permission` (`id`, `parent_id`, `name`, `code`, `type`, `route`, `method`, `icon`, `component`, `level`, `path`, `sort`, `status`, `visible`, `cache`, `remark`, `create_time`, `update_time`) VALUES (14, 12, '导入', 'admin.plugin.import', 'button', 'admin/plugin/import', NULL, NULL, NULL, 3, ',,', 0, 1, 1, 0, '自动生成', 1776940648, 1776940648);
INSERT INTO `dp_admin_permission` (`id`, `parent_id`, `name`, `code`, `type`, `route`, `method`, `icon`, `component`, `level`, `path`, `sort`, `status`, `visible`, `cache`, `remark`, `create_time`, `update_time`) VALUES (15, 12, '启用', 'admin.plugin.enable', 'button', 'admin/plugin/enable', NULL, NULL, NULL, 3, ',,', 0, 1, 1, 0, '自动生成', 1776940648, 1776940648);
INSERT INTO `dp_admin_permission` (`id`, `parent_id`, `name`, `code`, `type`, `route`, `method`, `icon`, `component`, `level`, `path`, `sort`, `status`, `visible`, `cache`, `remark`, `create_time`, `update_time`) VALUES (16, 12, '禁用', 'admin.plugin.disable', 'button', 'admin/plugin/disable', NULL, NULL, NULL, 3, ',,', 0, 1, 1, 0, '自动生成', 1776940648, 1776940648);
INSERT INTO `dp_admin_permission` (`id`, `parent_id`, `name`, `code`, `type`, `route`, `method`, `icon`, `component`, `level`, `path`, `sort`, `status`, `visible`, `cache`, `remark`, `create_time`, `update_time`) VALUES (17, 1, '用户管理', 'admin.user', 'menu', 'admin/user/index', NULL, 'ti ti-users', NULL, 2, ',', 30, 1, 1, 0, '自动生成', 1776940648, 1776940648);
INSERT INTO `dp_admin_permission` (`id`, `parent_id`, `name`, `code`, `type`, `route`, `method`, `icon`, `component`, `level`, `path`, `sort`, `status`, `visible`, `cache`, `remark`, `create_time`, `update_time`) VALUES (18, 17, '新增', 'admin.user.create', 'button', 'admin/user/create', NULL, NULL, NULL, 3, ',,', 0, 1, 1, 0, '自动生成', 1776940648, 1776940648);
INSERT INTO `dp_admin_permission` (`id`, `parent_id`, `name`, `code`, `type`, `route`, `method`, `icon`, `component`, `level`, `path`, `sort`, `status`, `visible`, `cache`, `remark`, `create_time`, `update_time`) VALUES (19, 17, '编辑', 'admin.user.edit', 'button', 'admin/user/edit', NULL, NULL, NULL, 3, ',,', 0, 1, 1, 0, '自动生成', 1776940648, 1776940648);
INSERT INTO `dp_admin_permission` (`id`, `parent_id`, `name`, `code`, `type`, `route`, `method`, `icon`, `component`, `level`, `path`, `sort`, `status`, `visible`, `cache`, `remark`, `create_time`, `update_time`) VALUES (20, 1, '角色管理', 'admin.role', 'menu', 'admin/role/index', NULL, 'ti ti-user-check', NULL, 2, ',', 50, 1, 1, 0, '自动生成', 1776940649, 1776940649);
INSERT INTO `dp_admin_permission` (`id`, `parent_id`, `name`, `code`, `type`, `route`, `method`, `icon`, `component`, `level`, `path`, `sort`, `status`, `visible`, `cache`, `remark`, `create_time`, `update_time`) VALUES (21, 20, '新增', 'admin.role.create', 'button', 'admin/role/create', NULL, NULL, NULL, 3, ',,', 0, 1, 1, 0, '自动生成', 1776940649, 1776940649);
INSERT INTO `dp_admin_permission` (`id`, `parent_id`, `name`, `code`, `type`, `route`, `method`, `icon`, `component`, `level`, `path`, `sort`, `status`, `visible`, `cache`, `remark`, `create_time`, `update_time`) VALUES (22, 20, '编辑', 'admin.role.edit', 'button', 'admin/role/edit', NULL, NULL, NULL, 3, ',,', 0, 1, 1, 0, '自动生成', 1776940649, 1776940649);
INSERT INTO `dp_admin_permission` (`id`, `parent_id`, `name`, `code`, `type`, `route`, `method`, `icon`, `component`, `level`, `path`, `sort`, `status`, `visible`, `cache`, `remark`, `create_time`, `update_time`) VALUES (23, 20, '删除', 'admin.role.delete', 'button', 'admin/role/delete', NULL, NULL, NULL, 3, ',,', 0, 1, 1, 0, '自动生成', 1776940649, 1776940649);
INSERT INTO `dp_admin_permission` (`id`, `parent_id`, `name`, `code`, `type`, `route`, `method`, `icon`, `component`, `level`, `path`, `sort`, `status`, `visible`, `cache`, `remark`, `create_time`, `update_time`) VALUES (24, 1, '配置管理', 'admin.config', 'menu', 'admin/config/index', NULL, 'ti ti-tool', NULL, 2, ',', 20, 1, 1, 0, '自动生成', 1776940649, 1776940649);
INSERT INTO `dp_admin_permission` (`id`, `parent_id`, `name`, `code`, `type`, `route`, `method`, `icon`, `component`, `level`, `path`, `sort`, `status`, `visible`, `cache`, `remark`, `create_time`, `update_time`) VALUES (25, 24, '新增', 'admin.config.create', 'button', 'admin/config/create', NULL, NULL, NULL, 3, ',,', 0, 1, 1, 0, '自动生成', 1776940649, 1776940649);
INSERT INTO `dp_admin_permission` (`id`, `parent_id`, `name`, `code`, `type`, `route`, `method`, `icon`, `component`, `level`, `path`, `sort`, `status`, `visible`, `cache`, `remark`, `create_time`, `update_time`) VALUES (26, 24, '编辑', 'admin.config.edit', 'button', 'admin/config/edit', NULL, NULL, NULL, 3, ',,', 0, 1, 1, 0, '自动生成', 1776940649, 1776940649);
INSERT INTO `dp_admin_permission` (`id`, `parent_id`, `name`, `code`, `type`, `route`, `method`, `icon`, `component`, `level`, `path`, `sort`, `status`, `visible`, `cache`, `remark`, `create_time`, `update_time`) VALUES (27, 1, '系统设置', 'admin.system', 'menu', 'admin/system/index', NULL, 'ti ti-settings', NULL, 2, ',', 10, 1, 1, 0, '自动生成', 1776940649, 1776940649);
INSERT INTO `dp_admin_permission` (`id`, `parent_id`, `name`, `code`, `type`, `route`, `method`, `icon`, `component`, `level`, `path`, `sort`, `status`, `visible`, `cache`, `remark`, `create_time`, `update_time`) VALUES (28, 1, '部门管理', 'admin.department', 'menu', 'admin/department/index', NULL, 'ti ti-building', NULL, 2, ',', 40, 1, 1, 0, '自动生成', 1776940649, 1776940649);
INSERT INTO `dp_admin_permission` (`id`, `parent_id`, `name`, `code`, `type`, `route`, `method`, `icon`, `component`, `level`, `path`, `sort`, `status`, `visible`, `cache`, `remark`, `create_time`, `update_time`) VALUES (29, 28, '新增', 'admin.department.create', 'button', 'admin/department/create', NULL, NULL, NULL, 3, ',,', 0, 1, 1, 0, '自动生成', 1776940649, 1776940649);
INSERT INTO `dp_admin_permission` (`id`, `parent_id`, `name`, `code`, `type`, `route`, `method`, `icon`, `component`, `level`, `path`, `sort`, `status`, `visible`, `cache`, `remark`, `create_time`, `update_time`) VALUES (30, 28, '编辑', 'admin.department.edit', 'button', 'admin/department/edit', NULL, NULL, NULL, 3, ',,', 0, 1, 1, 0, '自动生成', 1776940649, 1776940649);
INSERT INTO `dp_admin_permission` (`id`, `parent_id`, `name`, `code`, `type`, `route`, `method`, `icon`, `component`, `level`, `path`, `sort`, `status`, `visible`, `cache`, `remark`, `create_time`, `update_time`) VALUES (31, 1, '系统日志', 'admin.log', 'menu', 'admin/log/index', NULL, 'ti ti-file-text', NULL, 2, ',', 120, 1, 1, 0, '自动生成', 1776940649, 1776940649);
INSERT INTO `dp_admin_permission` (`id`, `parent_id`, `name`, `code`, `type`, `route`, `method`, `icon`, `component`, `level`, `path`, `sort`, `status`, `visible`, `cache`, `remark`, `create_time`, `update_time`) VALUES (32, 31, '详情', 'admin.log.detail', 'button', 'admin/log/detail', NULL, NULL, NULL, 3, ',,', 0, 1, 1, 0, '自动生成', 1776940649, 1776940649);
INSERT INTO `dp_admin_permission` (`id`, `parent_id`, `name`, `code`, `type`, `route`, `method`, `icon`, `component`, `level`, `path`, `sort`, `status`, `visible`, `cache`, `remark`, `create_time`, `update_time`) VALUES (33, 1, '上传', 'admin.api.upload', 'api', 'admin/api/upload', NULL, NULL, NULL, 2, ',', 0, 1, 1, 0, '自动生成', 1776940649, 1776940649);
INSERT INTO `dp_admin_permission` (`id`, `parent_id`, `name`, `code`, `type`, `route`, `method`, `icon`, `component`, `level`, `path`, `sort`, `status`, `visible`, `cache`, `remark`, `create_time`, `update_time`) VALUES (34, 1, '权限管理', 'admin.permission', 'menu', 'admin/permission/index', NULL, 'ti ti-lock', NULL, 2, ',', 60, 1, 1, 0, '自动生成', 1776940649, 1776940649);
INSERT INTO `dp_admin_permission` (`id`, `parent_id`, `name`, `code`, `type`, `route`, `method`, `icon`, `component`, `level`, `path`, `sort`, `status`, `visible`, `cache`, `remark`, `create_time`, `update_time`) VALUES (35, 34, '新增', 'admin.permission.create', 'button', 'admin/permission/create', NULL, NULL, NULL, 3, ',,', 0, 1, 1, 0, '自动生成', 1776940649, 1776940649);
INSERT INTO `dp_admin_permission` (`id`, `parent_id`, `name`, `code`, `type`, `route`, `method`, `icon`, `component`, `level`, `path`, `sort`, `status`, `visible`, `cache`, `remark`, `create_time`, `update_time`) VALUES (36, 34, '编辑', 'admin.permission.edit', 'button', 'admin/permission/edit', NULL, NULL, NULL, 3, ',,', 0, 1, 1, 0, '自动生成', 1776940649, 1776940649);
INSERT INTO `dp_admin_permission` (`id`, `parent_id`, `name`, `code`, `type`, `route`, `method`, `icon`, `component`, `level`, `path`, `sort`, `status`, `visible`, `cache`, `remark`, `create_time`, `update_time`) VALUES (37, 34, '删除', 'admin.permission.delete', 'button', 'admin/permission/delete', NULL, NULL, NULL, 3, ',,', 0, 1, 1, 0, '自动生成', 1776940649, 1776940649);
INSERT INTO `dp_admin_permission` (`id`, `parent_id`, `name`, `code`, `type`, `route`, `method`, `icon`, `component`, `level`, `path`, `sort`, `status`, `visible`, `cache`, `remark`, `create_time`, `update_time`) VALUES (38, 34, '同步权限', 'admin.permission.sync', 'button', 'admin/permission/sync', NULL, 'ti ti-key', NULL, 3, ',,', 0, 1, 1, 0, '自动生成', 1776940649, 1776940649);
COMMIT;

-- ----------------------------
-- Table structure for dp_admin_plugin
-- ----------------------------
DROP TABLE IF EXISTS `dp_admin_plugin`;
CREATE TABLE `dp_admin_plugin` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `name` varchar(120) NOT NULL DEFAULT '' COMMENT '插件唯一标识',
  `title` varchar(120) NOT NULL DEFAULT '' COMMENT '插件标题',
  `version` varchar(32) NOT NULL DEFAULT '' COMMENT '插件版本',
  `plugin_api_version` varchar(16) NOT NULL DEFAULT '' COMMENT '插件协议版本',
  `status` varchar(32) NOT NULL DEFAULT 'discovered' COMMENT '插件状态',
  `installed` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否已安装',
  `enabled` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否已启用',
  `path` varchar(255) NOT NULL DEFAULT '' COMMENT '插件路径',
  `provider` varchar(255) NOT NULL DEFAULT '' COMMENT '服务提供者类名',
  `main_class` varchar(255) NOT NULL DEFAULT '' COMMENT '主类名',
  `dependencies_json` text COMMENT '依赖信息',
  `runtime_config_json` text COMMENT '运行时配置',
  `last_error` varchar(500) NOT NULL DEFAULT '' COMMENT '最近错误',
  `install_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '安装时间',
  `enable_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '启用时间',
  `disable_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '禁用时间',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_name` (`name`),
  KEY `idx_status` (`status`),
  KEY `idx_enabled` (`enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='后台插件主表';

-- ----------------------------
-- Records of dp_admin_plugin
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for dp_admin_plugin_log
-- ----------------------------
DROP TABLE IF EXISTS `dp_admin_plugin_log`;
CREATE TABLE `dp_admin_plugin_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `plugin_name` varchar(120) NOT NULL DEFAULT '' COMMENT '插件标识',
  `operation` varchar(32) NOT NULL DEFAULT '' COMMENT '操作类型',
  `status` varchar(16) NOT NULL DEFAULT '' COMMENT '执行状态',
  `message` varchar(500) NOT NULL DEFAULT '' COMMENT '消息',
  `context_json` longtext COMMENT '上下文',
  `operator_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '操作人ID',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_plugin_name` (`plugin_name`),
  KEY `idx_operation` (`operation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='后台插件操作日志表';

-- ----------------------------
-- Records of dp_admin_plugin_log
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for dp_admin_role
-- ----------------------------
DROP TABLE IF EXISTS `dp_admin_role`;
CREATE TABLE `dp_admin_role` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '角色ID',
  `name` varchar(50) NOT NULL COMMENT '角色名称',
  `code` varchar(50) NOT NULL COMMENT '角色标识',
  `data_scope` tinyint(1) unsigned DEFAULT '1' COMMENT '数据范围:1全部,2本部门,3本部门及下级,4仅本人,5自定义',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态:1正常,0禁用',
  `is_super_role` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否超级管理员角色',
  `is_system` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否系统内置角色',
  `remark` varchar(500) DEFAULT NULL COMMENT '备注',
  `sort` int(11) DEFAULT '0' COMMENT '排序',
  `create_time` bigint(20) unsigned DEFAULT NULL COMMENT '创建时间戳',
  `update_time` bigint(20) unsigned DEFAULT NULL COMMENT '更新时间戳',
  `delete_time` bigint(20) unsigned DEFAULT NULL COMMENT '软删除时间戳',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=616 DEFAULT CHARSET=utf8mb4 COMMENT='角色表';

-- ----------------------------
-- Records of dp_admin_role
-- ----------------------------
BEGIN;
INSERT INTO `dp_admin_role` (`id`, `name`, `code`, `data_scope`, `status`, `is_super_role`, `is_system`, `remark`, `sort`, `create_time`, `update_time`, `delete_time`) VALUES (1, '超级管理员', 'ADMIN', 1, 1, 1, 1, '系统内置角色', 1, 1764561585, 1764645020, NULL);
COMMIT;

-- ----------------------------
-- Table structure for dp_admin_role_permission
-- ----------------------------
DROP TABLE IF EXISTS `dp_admin_role_permission`;
CREATE TABLE `dp_admin_role_permission` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `role_id` int(11) unsigned NOT NULL COMMENT '角色ID',
  `permission_id` int(11) unsigned NOT NULL COMMENT '权限ID',
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_permission` (`role_id`,`permission_id`),
  KEY `idx_role` (`role_id`),
  KEY `idx_permission` (`permission_id`),
  KEY `idx_role_perm_cover` (`role_id`,`permission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='角色权限关联表';

-- ----------------------------
-- Records of dp_admin_role_permission
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for dp_admin_user
-- ----------------------------
DROP TABLE IF EXISTS `dp_admin_user`;
CREATE TABLE `dp_admin_user` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '用户id',
  `username` varchar(50) NOT NULL DEFAULT '' COMMENT '用户名',
  `nickname` varchar(64) NOT NULL DEFAULT '' COMMENT '昵称',
  `password` varchar(96) NOT NULL DEFAULT '' COMMENT '密码',
  `email` varchar(128) NOT NULL DEFAULT '' COMMENT '邮箱',
  `email_verify` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否验证邮箱',
  `mobile` varchar(16) NOT NULL DEFAULT '' COMMENT '手机号码',
  `phone_verify` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否验证手机号码',
  `department_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '部门id',
  `avatar` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户头像id',
  `role` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '角色id',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '状态:1正常,0禁用',
  `is_super_admin` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否超级管理员',
  `login_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '登录次数',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `delete_time` int(11) unsigned DEFAULT NULL COMMENT '软删除时间',
  `reg_ip` varchar(40) NOT NULL DEFAULT '' COMMENT '注册ip',
  `last_login_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '最后登录时间',
  `last_login_ip` varchar(50) NOT NULL DEFAULT '' COMMENT '最后登录ip',
  `sort` int(11) NOT NULL DEFAULT '100' COMMENT '排序',
  `next_login_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '下次允许登录时间',
  `session_id` varchar(32) NOT NULL DEFAULT '' COMMENT '用于单账号登录',
  `remember_selector` varchar(32) NOT NULL DEFAULT '' COMMENT 'remember-me 选择器',
  `remember_token_hash` varchar(64) NOT NULL DEFAULT '' COMMENT 'remember-me 令牌哈希',
  `remember_expires_at` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'remember-me 过期时间',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`) USING BTREE,
  KEY `role` (`role`) USING BTREE,
  KEY `idx_status` (`status`) USING BTREE,
  KEY `email` (`email`) USING BTREE,
  KEY `idx_department` (`department_id`) USING BTREE,
  KEY `idx_user_dept_status` (`department_id`,`status`)
) ENGINE=InnoDB AUTO_INCREMENT=613 DEFAULT CHARSET=utf8mb4 COMMENT='管理员表';

-- ----------------------------
-- Records of dp_admin_user
-- ----------------------------
BEGIN;
INSERT INTO `dp_admin_user` (`id`, `username`, `nickname`, `password`, `email`, `email_verify`, `mobile`, `phone_verify`, `department_id`, `avatar`, `role`, `status`, `is_super_admin`, `login_count`, `create_time`, `update_time`, `delete_time`, `reg_ip`, `last_login_time`, `last_login_ip`, `sort`, `next_login_time`, `session_id`, `remember_selector`, `remember_token_hash`, `remember_expires_at`, `remark`) VALUES (1, 'admin', '超级管理员23', '$2y$10$rMHXE2ECj2.2a/TrMSJ.KOwXyqMpTLWP2WaxFMOqTZ.ZQvcXC4y2y', '', 0, '13609030141', 0, 0, 0, 1, 1, 1, 0, 1595216929, 1776940594, NULL, '', 1777272962, '::1', 100, 0, '', '', '', 0, '行政村');
COMMIT;

-- ----------------------------
-- Table structure for dp_admin_user_role
-- ----------------------------
DROP TABLE IF EXISTS `dp_admin_user_role`;
CREATE TABLE `dp_admin_user_role` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL COMMENT '用户ID',
  `role_id` int(11) unsigned NOT NULL COMMENT '角色ID',
  `create_time` bigint(20) unsigned DEFAULT NULL COMMENT '创建时间戳',
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_role` (`user_id`,`role_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_role` (`role_id`)
) ENGINE=InnoDB AUTO_INCREMENT=608 DEFAULT CHARSET=utf8mb4 COMMENT='用户角色关联表';

-- ----------------------------
-- Records of dp_admin_user_role
-- ----------------------------
BEGIN;
INSERT INTO `dp_admin_user_role` (`id`, `user_id`, `role_id`, `create_time`) VALUES (1, 1, 1, NULL);
COMMIT;

-- ----------------------------
-- Table structure for dp_admin_user_workspace
-- ----------------------------
DROP TABLE IF EXISTS `dp_admin_user_workspace`;
CREATE TABLE `dp_admin_user_workspace` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '后台用户ID',
  `type` varchar(40) NOT NULL DEFAULT 'quick_links' COMMENT '配置类型',
  `items_json` longtext COMMENT '工作台配置(JSON)',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_type` (`user_id`,`type`),
  KEY `idx_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='后台用户工作台配置表';

-- ----------------------------
-- Records of dp_admin_user_workspace
-- ----------------------------
BEGIN;
COMMIT;

SET FOREIGN_KEY_CHECKS = 1;
