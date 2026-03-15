<?php
/**
 * 小说网系统主入口文件
 */

// 定义常量
define('ROOT_PATH', dirname(__FILE__));
define('INSTALL_LOCK_FILE', ROOT_PATH . '/install.lock');

// 检查是否已安装
if (!file_exists(INSTALL_LOCK_FILE)) {
    // 跳转到安装页面
    header('Location: /install/');
    exit;
}

// 检查数据库配置文件是否存在
$dbConfigFile = ROOT_PATH . '/backend/config/database.php';
if (!file_exists($dbConfigFile)) {
    // 数据库配置丢失，需要重新安装
    header('Location: /install/?error=db_config_missing');
    exit;
}

// 加载首页
readfile(ROOT_PATH . '/public/index.html');
?>