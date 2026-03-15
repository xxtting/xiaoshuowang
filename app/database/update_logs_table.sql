-- ============================================
-- 系统操作日志表升级脚本
-- 用于优化现有数据库的日志表结构
-- ============================================

-- 检查并添加新列
ALTER TABLE `sys_log` 
ADD COLUMN IF NOT EXISTS `username` varchar(50) DEFAULT NULL COMMENT '操作用户名' AFTER `user_id`,
ADD COLUMN IF NOT EXISTS `action_type` varchar(50) DEFAULT 'other' COMMENT '操作分类：login/content/system/error/other' AFTER `action`,
ADD COLUMN IF NOT EXISTS `target_type` varchar(50) DEFAULT NULL COMMENT '操作对象类型' AFTER `user_agent`,
ADD COLUMN IF NOT EXISTS `target_id` int(11) DEFAULT NULL COMMENT '操作对象ID' AFTER `target_type`,
ADD COLUMN IF NOT EXISTS `before_data` json DEFAULT NULL COMMENT '操作前数据' AFTER `target_id`,
ADD COLUMN IF NOT EXISTS `after_data` json DEFAULT NULL COMMENT '操作后数据' AFTER `before_data`,
ADD COLUMN IF NOT EXISTS `result` tinyint(1) DEFAULT 1 COMMENT '操作结果：1成功 0失败' AFTER `after_data`;

-- 添加索引优化
ALTER TABLE `sys_log`
ADD INDEX IF NOT EXISTS `idx_username` (`username`),
ADD INDEX IF NOT EXISTS `idx_action_type` (`action_type`),
ADD INDEX IF NOT EXISTS `idx_target` (`target_type`, `target_id`),
ADD INDEX IF NOT EXISTS `idx_result` (`result`),
ADD INDEX IF NOT EXISTS `idx_user_time` (`user_id`, `create_time`),
ADD INDEX IF NOT EXISTS `idx_action_time` (`action_type`, `create_time`);

-- 添加注释
ALTER TABLE `sys_log` COMMENT='系统操作日志表 - 优化版';

-- ============================================
-- 日志归档表（用于存储旧日志）
-- ============================================
CREATE TABLE IF NOT EXISTS `sys_log_archive` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL COMMENT '用户ID',
  `username` varchar(50) DEFAULT NULL COMMENT '操作用户名',
  `action` varchar(100) NOT NULL COMMENT '操作类型',
  `action_type` varchar(50) DEFAULT 'other' COMMENT '操作分类',
  `description` varchar(500) DEFAULT NULL COMMENT '操作描述',
  `ip` varchar(50) DEFAULT NULL COMMENT 'IP地址',
  `user_agent` text COMMENT '用户代理',
  `target_type` varchar(50) DEFAULT NULL COMMENT '操作对象类型',
  `target_id` int(11) DEFAULT NULL COMMENT '操作对象ID',
  `before_data` json DEFAULT NULL COMMENT '操作前数据',
  `after_data` json DEFAULT NULL COMMENT '操作后数据',
  `result` tinyint(1) DEFAULT 1 COMMENT '操作结果',
  `create_time` datetime DEFAULT NULL COMMENT '原始创建时间',
  `archive_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '归档时间',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_username` (`username`),
  KEY `idx_action` (`action`),
  KEY `idx_action_type` (`action_type`),
  KEY `idx_created_at` (`create_time`),
  KEY `idx_archive_time` (`archive_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统操作日志归档表';

-- ============================================
-- 日志清理存储过程
-- ============================================
DELIMITER //

CREATE PROCEDURE IF NOT EXISTS `sp_archive_old_logs`(IN `days_to_keep` INT)
BEGIN
  DECLARE `cutoff_date` DATETIME;
  SET `cutoff_date` = DATE_SUB(NOW(), INTERVAL `days_to_keep` DAY);
  
  -- 将旧日志移动到归档表
  INSERT INTO `sys_log_archive` 
  (`id`, `user_id`, `username`, `action`, `action_type`, `description`, `ip`, `user_agent`, 
   `target_type`, `target_id`, `before_data`, `after_data`, `result`, `create_time`)
  SELECT `id`, `user_id`, `username`, `action`, `action_type`, `description`, `ip`, `user_agent`,
         `target_type`, `target_id`, `before_data`, `after_data`, `result`, `create_time`
  FROM `sys_log` 
  WHERE `create_time` < `cutoff_date`;
  
  -- 删除已归档的日志
  DELETE FROM `sys_log` WHERE `create_time` < `cutoff_date`;
  
  SELECT ROW_COUNT() AS `archived_count`;
END //

CREATE PROCEDURE IF NOT EXISTS `sp_clean_old_archive`(IN `days_to_keep` INT)
BEGIN
  DECLARE `cutoff_date` DATETIME;
  SET `cutoff_date` = DATE_SUB(NOW(), INTERVAL `days_to_keep` DAY);
  
  -- 删除过期的归档日志
  DELETE FROM `sys_log_archive` WHERE `archive_time` < `cutoff_date`;
  
  SELECT ROW_COUNT() AS `deleted_count`;
END //

DELIMITER ;

-- ============================================
-- 创建日志管理视图
-- ============================================
CREATE OR REPLACE VIEW `view_log_summary` AS
SELECT 
  DATE(`create_time`) AS `log_date`,
  `action_type`,
  COUNT(*) AS `log_count`,
  SUM(CASE WHEN `result` = 1 THEN 1 ELSE 0 END) AS `success_count`,
  SUM(CASE WHEN `result` = 0 THEN 1 ELSE 0 END) AS `fail_count`
FROM `sys_log`
WHERE `create_time` >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY DATE(`create_time`), `action_type`
ORDER BY `log_date` DESC, `action_type`;

-- ============================================
-- 创建用户操作统计视图
-- ============================================
CREATE OR REPLACE VIEW `view_user_action_stats` AS
SELECT 
  `user_id`,
  `username`,
  COUNT(*) AS `total_actions`,
  COUNT(DISTINCT DATE(`create_time`)) AS `active_days`,
  MAX(`create_time`) AS `last_action_time`
FROM `sys_log`
WHERE `create_time` >= DATE_SUB(NOW(), INTERVAL 30 DAY)
  AND `user_id` IS NOT NULL
GROUP BY `user_id`, `username`
ORDER BY `total_actions` DESC;

-- 更新完成提示
SELECT '日志表优化升级完成！' AS `message`;
