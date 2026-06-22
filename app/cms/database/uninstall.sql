-- CMS 应用卸载 SQL 示例
-- 仅在卸载时显式选择“清理数据”后执行

DROP TABLE IF EXISTS `dp_cms_article`;
DROP TABLE IF EXISTS `dp_cms_category`;
DROP TABLE IF EXISTS `dp_cms_demo_lifecycle`;
