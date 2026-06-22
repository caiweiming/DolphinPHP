-- CMS 应用安装 SQL 示例
-- 演示 install.sql 创建并初始化 cms 自己的业务表示例

CREATE TABLE IF NOT EXISTS `dp_cms_demo_lifecycle` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `event_code` varchar(32) NOT NULL DEFAULT '' COMMENT '生命周期事件编码',
  `event_title` varchar(100) NOT NULL DEFAULT '' COMMENT '生命周期事件标题',
  `details` varchar(255) NOT NULL DEFAULT '' COMMENT '演示说明',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_event_code` (`event_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='CMS 生命周期演示表';

CREATE TABLE IF NOT EXISTS `dp_cms_category` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `name` varchar(120) NOT NULL DEFAULT '' COMMENT '分类名称',
  `slug` varchar(120) NOT NULL DEFAULT '' COMMENT '分类别名',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '状态：1启用 0禁用',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='CMS 分类表';

CREATE TABLE IF NOT EXISTS `dp_cms_article` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `category_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '分类ID',
  `title` varchar(150) NOT NULL DEFAULT '' COMMENT '文章标题',
  `slug` varchar(150) NOT NULL DEFAULT '' COMMENT '文章别名',
  `summary` text COMMENT '文章摘要',
  `content` mediumtext COMMENT '文章正文',
  `cover` varchar(255) NOT NULL DEFAULT '' COMMENT '封面地址',
  `source` varchar(120) NOT NULL DEFAULT '' COMMENT '文章来源',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '状态：1发布 0草稿',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `view_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '浏览次数',
  `published_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '发布时间',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_category_id` (`category_id`),
  KEY `idx_status_publish` (`status`, `published_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='CMS 文章表';

INSERT INTO `dp_cms_category` (`name`, `slug`, `status`, `sort`, `remark`, `create_time`, `update_time`)
SELECT '产品动态', 'product-news', 1, 10, '用于演示文章分类管理。', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `dp_cms_category` WHERE `slug` = 'product-news'
);

INSERT INTO `dp_cms_category` (`name`, `slug`, `status`, `sort`, `remark`, `create_time`, `update_time`)
SELECT '使用指南', 'guides', 1, 20, '用于演示前台列表和详情。', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `dp_cms_category` WHERE `slug` = 'guides'
);

INSERT INTO `dp_cms_article`
(`category_id`, `title`, `slug`, `summary`, `content`, `cover`, `source`, `status`, `sort`, `view_count`, `published_time`, `create_time`, `update_time`)
SELECT
  `id`,
  '欢迎使用 CMS 示例应用',
  'welcome-to-cms-demo',
  '这是一篇安装时写入的演示文章，用于说明最小应用如何提供后台 CRUD 和前台详情页。',
  'CMS 示例应用安装完成后，会自动创建分类表、文章表与生命周期表，并写入最小演示数据，便于开发者直接参考应用结构。',
  '',
  'install.sql',
  1,
  10,
  0,
  UNIX_TIMESTAMP(),
  UNIX_TIMESTAMP(),
  UNIX_TIMESTAMP()
FROM `dp_cms_category`
WHERE `slug` = 'guides'
  AND NOT EXISTS (
    SELECT 1 FROM `dp_cms_article` WHERE `slug` = 'welcome-to-cms-demo'
  );

INSERT INTO `dp_cms_demo_lifecycle` (`event_code`, `event_title`, `details`, `create_time`)
SELECT 'installed', '应用已安装', 'install.sql 已执行：创建 cms 生命周期演示表并写入安装记录。', UNIX_TIMESTAMP()
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `dp_cms_demo_lifecycle` WHERE `event_code` = 'installed'
);
