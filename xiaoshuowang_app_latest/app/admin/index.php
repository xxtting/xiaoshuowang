<?php
/**
 * 管理员后台入口
 */

session_start();

// 检查是否已安装
if (!file_exists('../install.lock')) {
    header('Location: ../install/');
    exit;
}

// 检查是否登录
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // 显示登录页面
    include 'login.html';
    exit;
}

// 显示管理后台
include 'dashboard.html';
?>