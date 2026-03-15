<?php
/**
 * 小说网系统安装程序
 */

session_start();

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
            
            // 验证密码
            if ($admin['admin_password'] !== $admin['confirm_password']) {
                die("<script>alert('两次输入的密码不一致！');history.back();</script>");
            }
            
            // 验证账号
            if (empty($admin['admin_username'])) {
                $admin['admin_username'] = 'admin';
            }
            
            $result = setupAdmin($admin);
            if ($result['success']) {
                // 保存管理员账号到session
                $_SESSION['admin_username'] = $admin['admin_username'];
                // 创建安装锁文件
                file_put_contents(INSTALL_LOCK_FILE, '安装完成时间: ' . date('Y-m-d H:i:s'));
                header('Location: ?step=4');
                exit;
            } else {
                die("<script>alert('安装失败：" . addslashes($result['message']) . "');history.back();</script>");
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
        $configContent = "<?php\nreturn [\n";
        $configContent .= "    'host' => '{$config['db_host']}',\n";
        $configContent .= "    'port' => '{$config['db_port']}',\n";
        $configContent .= "    'database' => '{$config['db_name']}',\n";
        $configContent .= "    'username' => '{$config['db_user']}',\n";
        $configContent .= "    'password' => '{$config['db_password']}',\n";
        $configContent .= "    'charset' => 'utf8mb4'\n";
        $configContent .= "];\n";
        
        file_put_contents(ROOT_PATH . '/backend/config/database.php', $configContent);
        
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
        
        // 更新管理员账号
        $passwordHash = password_hash($admin['admin_password'], PASSWORD_DEFAULT);
        $adminUsername = $admin['admin_username'] ?? 'admin';
        
        // 先删除默认的admin账号（如果存在）
        $stmt = $pdo->prepare("DELETE FROM admin_user WHERE username = 'admin'");
        $stmt->execute();
        
        // 插入新的管理员账号
        $stmt = $pdo->prepare("INSERT INTO admin_user (username, password, realname, status, create_time) VALUES (?, ?, ?, 1, NOW())");
        $stmt->execute([$adminUsername, $passwordHash, $admin['admin_name']]);
        
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
                        <label>管理员账号</label>
                        <input type="text" name="admin_username" value="admin" required placeholder="请输入管理员登录账号">
                    </div>
                    <div class="form-group">
                        <label>管理员姓名</label>
                        <input type="text" name="admin_name" value="系统管理员" required placeholder="请输入管理员显示名称">
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
                    <p><strong>管理员账号：</strong> <?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'admin'); ?></p>
                    <p><strong>后台地址：</strong> <a href="../admin/">/admin/</a></p>
                    <p><strong>前端地址：</strong> <a href="../">/</a></p>
                    <p style="margin-top: 30px;">
                        <a href="../admin/" class="btn">进入后台</a>
                        <a href="../" class="btn" style="background: #666;">访问网站</a>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>