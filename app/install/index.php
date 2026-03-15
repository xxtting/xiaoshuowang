<?php
/**
 * 小说网系统安装程序
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

define('ROOT_PATH', dirname(__DIR__));

define('INSTALL_LOCK_FILE', ROOT_PATH . '/install.lock');

// 检查是否已安装
if (file_exists(INSTALL_LOCK_FILE)) {
    header('Location: ../index.php');
    exit;
}

$step = isset($_GET['step']) ? intval($_GET['step']) : 1;

// 处理安装步骤
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($step) {
        case 1:
            // 环境检查
            $result = checkEnvironment();
            if ($result['success']) {
                header('Location: ?step=2');
                exit;
            }
            break;
            
        case 2:
            // 数据库配置
            $config = $_POST;
            $result = setupDatabase($config);
            if ($result['success']) {
                header('Location: ?step=3');
                exit;
            }
            break;
            
        case 3:
            // 管理员配置
            $admin = $_POST;
            $result = setupAdmin($admin);
            if ($result['success']) {
                // 创建安装锁文件
                file_put_contents(INSTALL_LOCK_FILE, '安装完成时间: ' . date('Y-m-d H:i:s'));
                header('Location: ?step=4');
                exit;
            }
            break;
    }
}

function checkEnvironment() {
    $checks = [
        'php_version' => version_compare(PHP_VERSION, '7.4.0', '>='),
        'pdo_mysql' => extension_loaded('pdo_mysql'),
        'json' => extension_loaded('json'),
        'curl' => extension_loaded('curl'),
        'file_write' => is_writable(ROOT_PATH . '/backend/config'),
    ];
    
    $success = !in_array(false, $checks, true);
    
    return [
        'success' => $success,
        'checks' => $checks
    ];
}

function setupDatabase($config) {
    try {
        // 测试数据库连接
        $dsn = "mysql:host={$config['db_host']};port={$config['db_port']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['db_user'], $config['db_password']);
        
        // 创建数据库
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$config['db_name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        // 选择数据库
        $pdo->exec("USE `{$config['db_name']}`");
        
        // 导入数据库结构
        $sqlFile = ROOT_PATH . '/database/structure.sql';
        $sql = file_get_contents($sqlFile);
        
        // 分割SQL语句
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                $pdo->exec($statement);
            }
        }
        
        // 创建数据库配置文件
        $configContent = "<?php\n";
        $configContent .= "/**\n";
        $configContent .= " * 数据库配置文件\n";
        $configContent .= " * 生成时间: " . date('Y-m-d H:i:s') . "\n";
        $configContent .= " */\n\n";
        $configContent .= "// 定义数据库连接常量\n";
        $configContent .= "define('DB_HOST', '{$config['db_host']}');\n";
        $configContent .= "define('DB_PORT', '{$config['db_port']}');\n";
        $configContent .= "define('DB_NAME', '{$config['db_name']}');\n";
        $configContent .= "define('DB_USER', '{$config['db_user']}');\n";
        $configContent .= "define('DB_PASS', '{$config['db_password']}');\n";
        $configContent .= "define('DB_CHARSET', 'utf8mb4');\n";
        $configContent .= "define('DB_PREFIX', '');\n\n";
        $configContent .= "// 返回配置数组（兼容使用数组的代码）\n";
        $configContent .= "return [\n";
        $configContent .= "    'host' => DB_HOST,\n";
        $configContent .= "    'port' => DB_PORT,\n";
        $configContent .= "    'database' => DB_NAME,\n";
        $configContent .= "    'username' => DB_USER,\n";
        $configContent .= "    'password' => DB_PASS,\n";
        $configContent .= "    'charset' => DB_CHARSET,\n";
        $configContent .= "    'prefix' => DB_PREFIX,\n";
        $configContent .= "    'options' => [\n";
        $configContent .= "        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,\n";
        $configContent .= "        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,\n";
        $configContent .= "        PDO::ATTR_EMULATE_PREPARES => false,\n";
        $configContent .= "    ],\n";
        $configContent .= "];\n";
        
        file_put_contents(ROOT_PATH . '/backend/config/database.php', $configContent);
        
        // 同时创建 admin/config.php
        $adminConfigContent = "<?php\n";
        $adminConfigContent .= "/**\n";
        $adminConfigContent .= " * 数据库配置文件\n";
        $adminConfigContent .= " * 生成时间: " . date('Y-m-d H:i:s') . "\n";
        $adminConfigContent .= " */\n\n";
        $adminConfigContent .= "define('DB_HOST', '{$config['db_host']}');\n";
        $adminConfigContent .= "define('DB_PORT', '{$config['db_port']}');\n";
        $adminConfigContent .= "define('DB_NAME', '{$config['db_name']}');\n";
        $adminConfigContent .= "define('DB_USER', '{$config['db_user']}');\n";
        $adminConfigContent .= "define('DB_PASS', '{$config['db_password']}');\n";
        $adminConfigContent .= "define('DB_CHARSET', 'utf8mb4');\n";
        $adminConfigContent .= "define('DB_PREFIX', '');\n";
        
        file_put_contents(ROOT_PATH . '/admin/config.php', $adminConfigContent);
        
        return ['success' => true];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function setupAdmin($admin) {
    try {
        require_once ROOT_PATH . '/backend/config/database.php';
        $dbConfig = require ROOT_PATH . '/backend/config/database.php';
        
        $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']};charset=utf8mb4";
        $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password']);
        
        // 更新管理员密码
        $passwordHash = password_hash($admin['admin_password'], PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("UPDATE admin_user SET password = ?, realname = ? WHERE username = 'admin'");
        $stmt->execute([$passwordHash, $admin['admin_name']]);
        
        // 更新网站配置
        $stmt = $pdo->prepare("UPDATE sys_config SET config_value = ? WHERE config_key = 'site_name'");
        $stmt->execute([$admin['site_name']]);
        
        return ['success' => true];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>小说网系统安装</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Microsoft YaHei', sans-serif; background: #f5f5f5; color: #333; }
        .container { max-width: 800px; margin: 50px auto; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: #1890ff; color: white; padding: 20px; border-radius: 8px 8px 0 0; }
        .header h1 { font-size: 24px; margin: 0; }
        .content { padding: 30px; }
        .step-nav { display: flex; margin-bottom: 30px; border-bottom: 1px solid #eee; }
        .step { flex: 1; text-align: center; padding: 10px; position: relative; }
        .step.active { color: #1890ff; font-weight: bold; }
        .step.active:after { content: ''; position: absolute; bottom: -1px; left: 50%; transform: translateX(-50%); width: 80%; height: 2px; background: #1890ff; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
        .form-group input:focus { border-color: #1890ff; outline: none; }
        .btn { background: #1890ff; color: white; border: none; padding: 12px 30px; border-radius: 4px; cursor: pointer; font-size: 16px; }
        .btn:hover { background: #40a9ff; }
        .btn-next { float: right; }
        .btn-prev { float: left; background: #666; }
        .btn-prev:hover { background: #888; }
        .clearfix:after { content: ''; display: table; clear: both; }
        .check-list { list-style: none; }
        .check-item { padding: 10px; border-bottom: 1px solid #eee; }
        .check-item.success:before { content: '✓'; color: #52c41a; margin-right: 10px; }
        .check-item.error:before { content: '✗'; color: #f5222d; margin-right: 10px; }
        .success-message { text-align: center; padding: 50px 0; }
        .success-message h2 { color: #52c41a; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>小说网系统安装向导</h1>
        </div>
        
        <div class="step-nav">
            <div class="step <?= $step >= 1 ? 'active' : '' ?>">1. 环境检查</div>
            <div class="step <?= $step >= 2 ? 'active' : '' ?>">2. 数据库配置</div>
            <div class="step <?= $step >= 3 ? 'active' : '' ?>">3. 管理员设置</div>
            <div class="step <?= $step >= 4 ? 'active' : '' ?>">4. 安装完成</div>
        </div>
        
        <div class="content">
            <?php if ($step == 1): ?>
                <form method="post">
                    <h3>环境检查</h3>
                    <?php $result = checkEnvironment(); ?>
                    <ul class="check-list">
                        <li class="check-item <?= $result['checks']['php_version'] ? 'success' : 'error' ?>">
                            PHP版本 >= 7.4.0 (当前: <?= PHP_VERSION ?>)
                        </li>
                        <li class="check-item <?= $result['checks']['pdo_mysql'] ? 'success' : 'error' ?>">
                            PDO MySQL扩展
                        </li>
                        <li class="check-item <?= $result['checks']['json'] ? 'success' : 'error' ?>">
                            JSON扩展
                        </li>
                        <li class="check-item <?= $result['checks']['curl'] ? 'success' : 'error' ?>">
                            CURL扩展
                        </li>
                        <li class="check-item <?= $result['checks']['file_write'] ? 'success' : 'error' ?>">
                            配置文件写入权限
                        </li>
                    </ul>
                    
                    <?php if ($result['success']): ?>
                        <div class="clearfix">
                            <button type="submit" class="btn btn-next">下一步</button>
                        </div>
                    <?php else: ?>
                        <p style="color: #f5222d; margin-top: 20px;">请先解决环境检查中的问题，然后刷新页面重试。</p>
                    <?php endif; ?>
                </form>
                
            <?php elseif ($step == 2): ?>
                <form method="post">
                    <h3>数据库配置</h3>
                    <div class="form-group">
                        <label>数据库主机</label>
                        <input type="text" name="db_host" value="localhost" required>
                    </div>
                    <div class="form-group">
                        <label>数据库端口</label>
                        <input type="text" name="db_port" value="3306" required>
                    </div>
                    <div class="form-group">
                        <label>数据库名称</label>
                        <input type="text" name="db_name" value="xiaoshuowang" required>
                    </div>
                    <div class="form-group">
                        <label>数据库用户名</label>
                        <input type="text" name="db_user" value="root" required>
                    </div>
                    <div class="form-group">
                        <label>数据库密码</label>
                        <input type="password" name="db_password" required>
                    </div>
                    
                    <div class="clearfix">
                        <a href="?step=1" class="btn btn-prev">上一步</a>
                        <button type="submit" class="btn btn-next">下一步</button>
                    </div>
                </form>
                
            <?php elseif ($step == 3): ?>
                <form method="post">
                    <h3>管理员设置</h3>
                    <div class="form-group">
                        <label>网站名称</label>
                        <input type="text" name="site_name" value="AI小说网" required>
                    </div>
                    <div class="form-group">
                        <label>管理员姓名</label>
                        <input type="text" name="admin_name" value="系统管理员" required>
                    </div>
                    <div class="form-group">
                        <label>管理员密码</label>
                        <input type="password" name="admin_password" required>
                    </div>
                    <div class="form-group">
                        <label>确认密码</label>
                        <input type="password" name="confirm_password" required>
                    </div>
                    
                    <div class="clearfix">
                        <a href="?step=2" class="btn btn-prev">上一步</a>
                        <button type="submit" class="btn btn-next">完成安装</button>
                    </div>
                </form>
                
            <?php elseif ($step == 4): ?>
                <div class="success-message">
                    <h2>安装完成！</h2>
                    <p>小说网系统已成功安装。</p>
                    <p><strong>管理员账号：</strong> admin</p>
                    <p><strong>后台地址：</strong> <a href="../admin/">/admin/</a></p>
                    <p><strong>前端地址：</strong> <a href="../">/</a></p>
                    <p style="margin-top: 30px;">
                        <a href="../" class="btn">访问网站</a>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>