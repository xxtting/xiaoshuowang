<?php
/**
 * 初始化数据库表
 * 用于创建系统设置、AI设置等必要的表
 */

session_start();

// 检查管理员登录
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.html');
    exit;
}

// 引入数据库配置
if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
} elseif (file_exists(__DIR__ . '/../backend/config/database.php')) {
    require_once __DIR__ . '/../backend/config/database.php';
} else {
    die("错误：数据库配置文件不存在。");
}

// 检查常量是否定义
if (!defined('DB_HOST') || !defined('DB_NAME') || !defined('DB_USER') || !defined('DB_PASS')) {
    die("错误：数据库配置不完整，请检查 config.php 文件。");
}

// 数据库连接
echo "<p>8. 正在连接数据库...</p>";
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "<p style='color:green'>✓ 数据库连接成功</p>";
} catch (PDOException $e) {
    die("<p style='color:red'>数据库连接失败: " . htmlspecialchars($e->getMessage()) . "</p>");
}

// 需要创建的表
$tables = [
    'system_settings' => "
        CREATE TABLE IF NOT EXISTS `system_settings` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `setting_key` varchar(100) NOT NULL COMMENT '设置键',
          `setting_value` text COMMENT '设置值',
          `setting_type` varchar(50) DEFAULT 'text' COMMENT '设置类型',
          `description` varchar(255) DEFAULT NULL COMMENT '设置描述',
          `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
          `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE KEY `uk_key` (`setting_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统设置表';
    ",
    'ai_settings' => "
        CREATE TABLE IF NOT EXISTS `ai_settings` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `setting_key` varchar(100) NOT NULL COMMENT '设置键',
          `setting_value` text COMMENT '设置值',
          `description` varchar(255) DEFAULT NULL COMMENT '设置描述',
          `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
          `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE KEY `uk_key` (`setting_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='AI设置表';
    ",
    'sys_log' => "
        CREATE TABLE IF NOT EXISTS `sys_log` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `user_id` int(11) DEFAULT NULL COMMENT '用户ID',
          `action` varchar(100) NOT NULL COMMENT '操作类型',
          `description` varchar(500) DEFAULT NULL COMMENT '操作描述',
          `ip` varchar(50) DEFAULT NULL COMMENT 'IP地址',
          `user_agent` text COMMENT '用户代理',
          `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `idx_user_id` (`user_id),
          KEY `idx_action` (`action`),
          KEY `idx_created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统操作日志表';
    ",
    'user_favorite' => "
        CREATE TABLE IF NOT EXISTS `user_favorite` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `user_id` int(11) NOT NULL COMMENT '用户ID',
          `novel_id` int(11) NOT NULL COMMENT '小说ID',
          `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE KEY `uk_user_novel` (`user_id`, `novel_id`),
          KEY `idx_user_id` (`user_id`),
          KEY `idx_novel_id` (`novel_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户收藏表';
    "
];

// 执行结果
echo "<hr>";
echo "<h3>创建数据库表</h3>";
$results = [];

foreach ($tables as $tableName => $sql) {
    echo "<p>创建表: $tableName ... ";
    try {
        $pdo->exec($sql);
        echo "<span style='color:green'>✓ 成功</span>";
        $results[$tableName] = true;
    } catch (PDOException $e) {
        echo "<span style='color:red'>✗ 失败: " . htmlspecialchars($e->getMessage()) . "</span>";
        $results[$tableName] = false;
    }
    echo "</p>";
}

echo "<hr>";
echo "<h3>初始化完成</h3>";
echo "<p><a href='dashboard.html' style='padding:10px 20px;background:#667eea;color:white;text-decoration:none;border-radius:5px;'>返回后台</a></p>";
