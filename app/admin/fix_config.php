<?php
/**
 * 数据库配置修复工具
 * 用于创建正确的 database.php 文件
 */

session_start();

// 检查管理员登录
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.html');
    exit;
}

$configFile = __DIR__ . '/../backend/config/database.php';

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = $_POST['host'] ?? 'localhost';
    $port = $_POST['port'] ?? '3306';
    $database = $_POST['database'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // 生成配置文件内容
    $configContent = "<?php
/**
 * 数据库配置文件
 * 
 * 生成时间: " . date('Y-m-d H:i:s') . "
 */

// 定义数据库连接常量
define('DB_HOST', '$host');
define('DB_PORT', '$port');
define('DB_NAME', '$database');
define('DB_USER', '$username');
define('DB_PASS', '$password');
define('DB_CHARSET', 'utf8mb4');
define('DB_PREFIX', '');

// 返回配置数组（兼容使用数组的代码）
return [
    'host' => DB_HOST,
    'port' => DB_PORT,
    'database' => DB_NAME,
    'username' => DB_USER,
    'password' => DB_PASS,
    'charset' => DB_CHARSET,
    'prefix' => DB_PREFIX,
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ],
];
";
    
    // 写入文件
    if (file_put_contents($configFile, $configContent)) {
        $message = "配置文件创建成功！";
        $success = true;
    } else {
        $message = "配置文件写入失败，请检查目录权限";
        $success = false;
    }
}

// 读取当前配置（如果存在）
$currentConfig = [];
if (file_exists($configFile)) {
    $currentConfig = @require $configFile;
    if (!is_array($currentConfig)) {
        $currentConfig = [];
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>数据库配置修复</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 24px;
            font-weight: 600;
        }
        
        .content {
            padding: 30px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 500;
            color: #495057;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
        }
        
        .btn:hover {
            opacity: 0.9;
        }
        
        .actions {
            margin-top: 20px;
            text-align: center;
        }
        
        .actions a {
            color: #667eea;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔧 数据库配置修复</h1>
        </div>
        
        <div class="content">
            <?php if (isset($message)): ?>
            <div class="alert <?php echo $success ? 'alert-success' : 'alert-error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>数据库主机</label>
                    <input type="text" name="host" value="<?php echo htmlspecialchars($currentConfig['host'] ?? 'localhost'); ?>" placeholder="localhost">
                </div>
                
                <div class="form-group">
                    <label>数据库端口</label>
                    <input type="text" name="port" value="<?php echo htmlspecialchars($currentConfig['port'] ?? '3306'); ?>" placeholder="3306">
                </div>
                
                <div class="form-group">
                    <label>数据库名称</label>
                    <input type="text" name="database" value="<?php echo htmlspecialchars($currentConfig['database'] ?? ''); ?>" placeholder="xiaoshuowang">
                </div>
                
                <div class="form-group">
                    <label>数据库用户名</label>
                    <input type="text" name="username" value="<?php echo htmlspecialchars($currentConfig['username'] ?? ''); ?>" placeholder="root">
                </div>
                
                <div class="form-group">
                    <label>数据库密码</label>
                    <input type="password" name="password" value="<?php echo htmlspecialchars($currentConfig['password'] ?? ''); ?>" placeholder="请输入密码">
                </div>
                
                <button type="submit" class="btn">💾 保存配置</button>
            </form>
            
            <div class="actions">
                <p><a href="check_config.php">检测配置</a> | <a href="init_tables.php">初始化表</a> | <a href="dashboard.html">返回后台</a></p>
            </div>
        </div>
    </div>
</body>
</html>
