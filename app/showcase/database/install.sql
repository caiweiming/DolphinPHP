-- Showcase 应用安装 SQL
-- 用于安装表格渲染器真实演示依赖的三张演示数据表
-- 关系说明：dp_showcase_table_media.table_id 逻辑关联 dp_showcase_table.id；dp_showcase_table_complex.parent_id 表示同表父子层级关系

CREATE TABLE IF NOT EXISTS `dp_showcase_table` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(150) NOT NULL DEFAULT '' COMMENT '标题',
  `code` varchar(64) NOT NULL DEFAULT '' COMMENT '业务编号',
  `category` varchar(100) NOT NULL DEFAULT '' COMMENT '分类名称',
  `owner_name` varchar(60) NOT NULL DEFAULT '' COMMENT '负责人姓名',
  `status` tinyint NOT NULL DEFAULT '0' COMMENT '状态值',
  `is_enabled` tinyint(1) NOT NULL DEFAULT '1' COMMENT '启用状态',
  `is_system` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否系统项',
  `priority` tinyint NOT NULL DEFAULT '0' COMMENT '优先级',
  `score` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '评分/数值',
  `color_tag` varchar(20) NOT NULL DEFAULT '' COMMENT '颜色标签',
  `department_code` varchar(40) NOT NULL DEFAULT '' COMMENT '部门编码',
  `expire_date` date DEFAULT NULL COMMENT '到期日期',
  `publish_time` datetime DEFAULT NULL COMMENT '发布时间',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
  `sort` int NOT NULL DEFAULT '0' COMMENT '排序值',
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_category` (`category`),
  KEY `idx_sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='表格示例主表';

CREATE TABLE IF NOT EXISTS `dp_showcase_table_media` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `table_id` int unsigned NOT NULL DEFAULT '0' COMMENT '关联主表 ID',
  `asset_name` varchar(150) NOT NULL DEFAULT '' COMMENT '素材名称',
  `cover` varchar(255) NOT NULL DEFAULT '' COMMENT '封面图地址',
  `gallery` text COMMENT '图集地址列表',
  `url` varchar(255) NOT NULL DEFAULT '' COMMENT '默认预览源地址',
  `preview_url` varchar(255) NOT NULL DEFAULT '' COMMENT '默认预览地址',
  `download_url` varchar(255) NOT NULL DEFAULT '' COMMENT '默认下载地址',
  `ext` varchar(20) NOT NULL DEFAULT '' COMMENT '默认扩展名',
  `mime` varchar(100) NOT NULL DEFAULT '' COMMENT '默认 MIME 类型',
  `name` varchar(150) NOT NULL DEFAULT '' COMMENT '默认文件名',
  `file_url` varchar(255) NOT NULL DEFAULT '' COMMENT '原始文件地址',
  `file_preview_url` varchar(255) NOT NULL DEFAULT '' COMMENT '文件预览地址',
  `file_download_url` varchar(255) NOT NULL DEFAULT '' COMMENT '文件下载地址',
  `file_ext` varchar(20) NOT NULL DEFAULT '' COMMENT '扩展名',
  `file_mime` varchar(100) NOT NULL DEFAULT '' COMMENT 'MIME 类型',
  `file_name` varchar(150) NOT NULL DEFAULT '' COMMENT '文件名',
  `icon_class` varchar(80) NOT NULL DEFAULT '' COMMENT '图标类名',
  `status` tinyint NOT NULL DEFAULT '0' COMMENT '状态值',
  PRIMARY KEY (`id`),
  KEY `idx_table_id` (`table_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='表格示例素材表';

CREATE TABLE IF NOT EXISTS `dp_showcase_table_complex` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int unsigned NOT NULL DEFAULT '0' COMMENT '父级节点 ID',
  `node_name` varchar(150) NOT NULL DEFAULT '' COMMENT '节点名称',
  `node_type` varchar(50) NOT NULL DEFAULT '' COMMENT '节点类型',
  `region` varchar(80) NOT NULL DEFAULT '' COMMENT '区域',
  `department` varchar(100) NOT NULL DEFAULT '' COMMENT '部门',
  `amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '金额',
  `order_count` int NOT NULL DEFAULT '0' COMMENT '订单数',
  `status` tinyint NOT NULL DEFAULT '0' COMMENT '状态值',
  `sort` int NOT NULL DEFAULT '0' COMMENT '排序值',
  PRIMARY KEY (`id`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_node_type` (`node_type`),
  KEY `idx_sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='表格示例复杂结构表';

INSERT INTO `dp_showcase_table` (
  `id`, `title`, `code`, `category`, `owner_name`, `status`, `is_enabled`, `is_system`,
  `priority`, `score`, `color_tag`, `department_code`, `expire_date`, `publish_time`,
  `created_at`, `updated_at`, `sort`
) VALUES
  (1, '华东渠道增长看板', 'SC-TABLE-001', '渠道运营', '陈晓雨', 1, 1, 1, 9, 95.80, '#10B981', 'OPS-EAST', '2026-12-31', '2026-06-01 09:00:00', '2026-06-01 09:00:00', '2026-06-10 18:30:00', 10),
  (2, '企业客户续约清单', 'SC-TABLE-002', '销售管理', '周明哲', 0, 1, 0, 7, 88.40, '#F59E0B', 'SALES-B2B', '2026-09-30', '2026-05-28 14:15:00', '2026-05-28 14:15:00', '2026-06-09 10:00:00', 20),
  (3, '内容资产巡检任务', 'SC-TABLE-003', '内容中台', '林书宁', 2, 0, 0, 5, 76.00, '#EF4444', 'CONTENT-QA', '2026-08-15', '2026-05-20 11:00:00', '2026-05-20 11:00:00', '2026-06-08 16:45:00', 30)
ON DUPLICATE KEY UPDATE
  `title` = VALUES(`title`),
  `code` = VALUES(`code`),
  `category` = VALUES(`category`),
  `owner_name` = VALUES(`owner_name`),
  `status` = VALUES(`status`),
  `is_enabled` = VALUES(`is_enabled`),
  `is_system` = VALUES(`is_system`),
  `priority` = VALUES(`priority`),
  `score` = VALUES(`score`),
  `color_tag` = VALUES(`color_tag`),
  `department_code` = VALUES(`department_code`),
  `expire_date` = VALUES(`expire_date`),
  `publish_time` = VALUES(`publish_time`),
  `created_at` = VALUES(`created_at`),
  `updated_at` = VALUES(`updated_at`),
  `sort` = VALUES(`sort`);

INSERT INTO `dp_showcase_table_media` (
  `id`, `table_id`, `asset_name`, `cover`, `gallery`, `url`, `preview_url`, `download_url`,
  `ext`, `mime`, `name`, `file_url`, `file_preview_url`, `file_download_url`,
  `file_ext`, `file_mime`, `file_name`, `icon_class`, `status`
) VALUES
  (1, 1, '渠道战报封面', '/static/showcase/table/cover-east-board.png', '/static/showcase/table/cover-east-board.png,/static/showcase/table/cover-east-board-2.png', '/static/showcase/table/east-board.png', '/static/showcase/table/east-board.png', '/static/showcase/table/east-board-download', 'png', 'image/png', 'east-board.png', '/static/showcase/table/east-board.xlsx', '/static/showcase/table/east-board-preview', '/static/showcase/table/east-board-download', 'xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'east-board.xlsx', 'ti ti-file-type-xls', 1),
  (2, 2, '续约客户资料包', '/static/showcase/table/cover-renewal.png', '/static/showcase/table/cover-renewal.png', '/static/showcase/table/renewal-pack.pdf', '/static/showcase/table/renewal-pack-preview', '/static/showcase/table/renewal-pack-download', 'pdf', 'application/pdf', 'renewal-pack.pdf', '/static/showcase/table/renewal-pack.pdf', '/static/showcase/table/renewal-pack-preview', '/static/showcase/table/renewal-pack-download', 'pdf', 'application/pdf', 'renewal-pack.pdf', 'ti ti-file-type-pdf', 0),
  (3, 3, '巡检素材截图', '/static/showcase/table/cover-content-audit.png', '/static/showcase/table/cover-content-audit.png,/static/showcase/table/cover-content-audit-2.png,/static/showcase/table/cover-content-audit-3.png', '/static/showcase/table/content-audit.zip', '/static/showcase/table/content-audit-preview', '/static/showcase/table/content-audit-download', 'zip', 'application/zip', 'content-audit.zip', '/static/showcase/table/content-audit.zip', '/static/showcase/table/content-audit-preview', '/static/showcase/table/content-audit-download', 'zip', 'application/zip', 'content-audit.zip', 'ti ti-file-zip', 2)
ON DUPLICATE KEY UPDATE
  `table_id` = VALUES(`table_id`),
  `asset_name` = VALUES(`asset_name`),
  `cover` = VALUES(`cover`),
  `gallery` = VALUES(`gallery`),
  `url` = VALUES(`url`),
  `preview_url` = VALUES(`preview_url`),
  `download_url` = VALUES(`download_url`),
  `ext` = VALUES(`ext`),
  `mime` = VALUES(`mime`),
  `name` = VALUES(`name`),
  `file_url` = VALUES(`file_url`),
  `file_preview_url` = VALUES(`file_preview_url`),
  `file_download_url` = VALUES(`file_download_url`),
  `file_ext` = VALUES(`file_ext`),
  `file_mime` = VALUES(`file_mime`),
  `file_name` = VALUES(`file_name`),
  `icon_class` = VALUES(`icon_class`),
  `status` = VALUES(`status`);

INSERT INTO `dp_showcase_table_complex` (
  `id`, `parent_id`, `node_name`, `node_type`, `region`, `department`, `amount`, `order_count`, `status`, `sort`
) VALUES
  (1, 0, '华北运营中心', 'region', '华北', '运营中心', 1280000.00, 186, 1, 10),
  (2, 1, '北京渠道一部', 'department', '华北', '渠道一部', 760000.00, 104, 1, 20),
  (3, 1, '天津渠道二部', 'department', '华北', '渠道二部', 520000.00, 82, 0, 30)
ON DUPLICATE KEY UPDATE
  `parent_id` = VALUES(`parent_id`),
  `node_name` = VALUES(`node_name`),
  `node_type` = VALUES(`node_type`),
  `region` = VALUES(`region`),
  `department` = VALUES(`department`),
  `amount` = VALUES(`amount`),
  `order_count` = VALUES(`order_count`),
  `status` = VALUES(`status`),
  `sort` = VALUES(`sort`);
