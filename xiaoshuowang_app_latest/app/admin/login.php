<?php
/**
 * 管理员登录处理
 */

session_start();

// 检查是否已安装
if (!file_exists('../install.lock')) {
    header('Location: ../install/');
    exit;
}

// 数据库配置
$configFile = '../backend/config/database.php';
if (!file_exists($configFile)) {
    header('Location: ../install/?error=db_config_missing');
    exit;
}

$config = require $configFile;

// 处理登录表单
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    try {
        // 连接数据库
        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['username'], $config['password']);
        
        // 查询管理员用户
        $stmt = $pdo->prepare("SELECT * FROM admin_user WHERE username = ? AND status = 1");
        $stmt->execute([$username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($admin && password_verify($password, $admin['password'])) {
            // 登录成功
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_realname'] = $admin['realname'];
            $_SESSION['admin_role'] = $admin['role'];
            
            // 更新登录信息
            $updateStmt = $pdo->prepare("UPDATE admin_user SET last_login_time = NOW(), last_login_ip = ? WHERE id = ?");
            $updateStmt->execute([$_SERVER['REMOTE_ADDR'], $admin['id']]);
            
            // 记录日志
            $logStmt = $pdo->prepare("INSERT INTO sys_log (user_id, action, description, ip, user_agent) VALUES (?, ?, ?, ?, ?)");
            $logStmt->execute([
                $admin['id'],
                'admin_login',
                '管理员登录系统',
                $_SERVER['REMOTE_ADDR'],
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
            
            // 跳转到后台
            header('Location: index.php');
            exit;
        } else {
            // 登录失败
            header('Location: index.php?error=login_failed');
            exit;
        }
        
    } catch (PDOException $e) {
        // 数据库连接失败
        header('Location: ../install/?error=db_connection');
        exit;
    }
}

// 如果不是POST请求，跳回登录页面
header('Location: index.php');
exit;