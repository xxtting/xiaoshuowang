<?php
/**
 * 编辑数据库配置文件
 * 用于修改数据库连接信息
 */

session_start();

// 检查管理员登录
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.html');
    exit;
}

// 配置文件路径
$configFile = __DIR__ . '/config.php';
$backendConfigFile = __DIR__ . '/../backend/config/database.php';

// 读取当前配置
$currentConfig = [];
if (file_exists($configFile)) {
    include $configFile;
    $currentConfig = [
        'host' => defined('DB_HOST') ? DB_HOST : 'localhost',
        'port' => defined('DB_PORT') ? DB_PORT : '3306',
        'name' => defined('DB_NAME') ? DB_NAME : '',
        'user' => defined('DB_USER') ? DB_USER : '',
        'pass' => defined('DB_PASS') ? DB_PASS : ''
    ];
}

// 处理表单提交
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = $_POST['db_host'] ?? 'localhost';
    $port = $_POST['db_port'] ?? '3306';
    $name = $_POST['db_name'] ?? '';
    $user = $_POST['db_user'] ?? '';
    $pass = $_POST['db_pass'] ?? '';
    
    // 测试连接
    try {
        $pdo = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // 测试数据库是否存在
        $pdo->exec("USE `$name`");
        
        // 生成配置文件内容
        $configContent = "<?php\n";
        $configContent .= "/**\n";
        $configContent .= " * 数据库配置文件\n";
        $configContent .= " * 生成时间: " . date('Y-m-d H:i:s') . "\n";
        $configContent .= " */\n\n";
        $configContent .= "define('DB_HOST', '$host');\n";
        $configContent .= "define('DB_PORT', '$port');\n";
        $configContent .= "define('DB_NAME', '$name');\n";
        $configContent .= "define('DB_USER', '$user');\n";
        $configContent .= "define('DB_PASS', '$pass');\n";
        $configContent .= "define('DB_CHARSET', 'utf8mb4');\n";
        $configContent .= "define('DB_PREFIX', '');\n";
        
        // 写入 admin/config.php
        if (file_put_contents($configFile, $configContent)) {
            // 同时更新 backend/config/database.php
            $backendContent = "<?php\n";
            $backendContent .= "/**\n";
            $backendContent .= " * 数据库配置文件\n";
            $backendContent .= " * 生成时间: " . date('Y-m-d H:i:s') . "\n";
            $backendContent .= " */\n\n";
            $backendContent .= "// 定义数据库连接常量\n";
            $backendContent .= "define('DB_HOST', '$host');\n";
            $backendContent .= "define('DB_PORT', '$port');\n";
            $backendContent .= "define('DB_NAME', '$name');\n";
            $backendContent .= "define('DB_USER', '$user');\n";
            $backendContent .= "define('DB_PASS', '$pass');\n";
            $backendContent .= "define('DB_CHARSET', 'utf8mb4');\n";
            $backendContent .= "define('DB_PREFIX', '');\n\n";
            $backendContent .= "// 返回配置数组（兼容使用数组的代码）\n";
            $backendContent .= "return [\n";
            $backendContent .= "    'host' => DB_HOST,\n";
            $backendContent .= "    'port' => DB_PORT,\n";
            $backendContent .= "    'database' => DB_NAME,\n";
            $backendContent .= "    'username' => DB_USER,\n";
            $backendContent .= "    'password' => DB_PASS,\n";
            $backendContent .= "    'charset' => DB_CHARSET,\n";
            $backendContent .= "    'prefix' => DB_PREFIX,\n";
            $backendContent .= "    'options' => [\n";
            $backendContent .= "        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,\n";
            $backendContent .= "        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,\n";
            $backendContent .= "        PDO::ATTR_EMULATE_PREPARES => false,\n";
            $backendContent .= "    ],\n";
            $backendContent .= "];\n";
            
            file_put_contents($backendConfigFile, $backendContent);
            
            $message = '数据库配置保存成功！连接测试通过。';
            
            // 更新当前配置
            $currentConfig = [
                'host' => $host,
                'port' => $port,
                'name' => $name,
                'user' => $user,
                'pass' => $pass
            ];
        } else {
            $error = '配置文件写入失败，请检查目录权限。';
        }
    } catch (PDOException $e) {
        $error = '数据库连接失败: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>编辑数据库配置</title>
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
        
        .form-row {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 15px;
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
        
        .tip {
            font-size: 12px;
            color: #6c757d;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🗄️ 编辑数据库配置</h1>
        </div>
        
        <div class="content">
            <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label>数据库主机</label>
                        <input type="text" name="db_host" value="<?php echo htmlspecialchars($currentConfig['host'] ?? 'localhost'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>端口</label>
                        <input type="text" name="db_port" value="<?php echo htmlspecialchars($currentConfig['port'] ?? '3306'); ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>数据库名称</label>
                    <input type="text" name="db_name" value="<?php echo htmlspecialchars($currentConfig['name'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>数据库用户名</label>
                    <input type="text" name="db_user" value="<?php echo htmlspecialchars($currentConfig['user'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>数据库密码</label>
                    <input type="password" name="db_pass" value="<?php echo htmlspecialchars($currentConfig['pass'] ?? ''); ?>" required>
                    <p class="tip">如果密码为空，请留空或输入空格后删除</p>
                </div>
                
                <button type="submit" class="btn">💾 保存配置并测试连接</button>
            </form>
            
            <div class="actions">
                <p><a href="dashboard.html">返回后台</a></p>
            </div>
        </div>
    </div>
</body>
</html>
