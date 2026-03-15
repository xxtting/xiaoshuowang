<?php
/**
 * 小说网系统 - 修复版安装程序
 * 修复问题：
 * 1. 添加管理员账号字段（不仅仅是真实姓名）
 * 2. 添加密码一致性验证
 * 3. 修复数据库写入逻辑
 * 4. 添加用户名唯一性检查
 */

define('ROOT_PATH', dirname(__DIR__));

$step = isset($_GET['step']) ? intval($_GET['step']) : 1;
$result = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    switch ($step) {
        case 1:
            $result = checkEnvironment();
            break;
        case 2:
            $config = [
                'db_host' => $_POST['db_host'] ?? '',
                'db_port' => $_POST['db_port'] ?? '3306',
                'db_name' => $_POST['db_name'] ?? '',
                'db_user' => $_POST['db_user'] ?? '',
                'db_password' => $_POST['db_password'] ?? '',
            ];
            $result = setupDatabase($config);
            break;
        case 3:
            // 获取表单数据
            $admin = [
                'site_name' => $_POST['site_name'] ?? '',
                'admin_username' => $_POST['admin_username'] ?? '',
                'admin_realname' => $_POST['admin_realname'] ?? '',
                'admin_password' => $_POST['admin_password'] ?? '',
                'confirm_password' => $_POST['confirm_password'] ?? '',
            ];
            
            // 验证表单数据
            $validation = validateAdminForm($admin);
            if (!$validation['success']) {
                $result = $validation;
                break;
            }
            
            // 设置管理员
            $result = setupAdmin($admin);
            
            if ($result['success']) {
                // 创建安装锁定文件
                file_put_contents(ROOT_PATH . '/install.lock', '安装完成时间: ' . date('Y-m-d H:i:s'));
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
    
    $success = array_reduce($checks, function($carry, $item) {
        return $carry && $item;
    }, true);
    
    return ['success' => $success, 'checks' => $checks];
}

function setupDatabase($config) {
    try {
        // 测试数据库连接
        $dsn = "mysql:host={$config['db_host']};port={$config['db_port']}";
        $pdo = new PDO($dsn, $config['db_user'], $config['db_password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // 创建数据库（如果不存在）
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$config['db_name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$config['db_name']}`");
        
        // 导入数据库结构
        $sqlFile = ROOT_PATH . '/database/structure.sql';
        $sqlContent = file_get_contents($sqlFile);
        
        $statements = explode(';', $sqlContent);
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                $pdo->exec($statement);
            }
        }
        
        // 生成数据库配置文件
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

function validateAdminForm($admin) {
    // 检查必填字段
    $requiredFields = ['site_name', 'admin_username', 'admin_password', 'confirm_password'];
    foreach ($requiredFields as $field) {
        if (empty($admin[$field])) {
            return ['success' => false, 'message' => "{$field}字段不能为空"];
        }
    }
    
    // 检查密码一致性
    if ($admin['admin_password'] !== $admin['confirm_password']) {
        return ['success' => false, 'message' => '两次输入的密码不一致'];
    }
    
    // 检查密码长度
    if (strlen($admin['admin_password']) < 6) {
        return ['success' => false, 'message' => '密码长度至少为6位'];
    }
    
    // 检查用户名合法性
    if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $admin['admin_username'])) {
        return ['success' => false, 'message' => '用户名只能包含字母、数字和下划线，长度为3-20位'];
    }
    
    return ['success' => true];
}

function setupAdmin($admin) {
    try {
        require_once ROOT_PATH . '/backend/config/database.php';
        $dbConfig = require ROOT_PATH . '/backend/config/database.php';
        
        $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']};charset=utf8mb4";
        $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // 检查用户名是否已存在
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin_user WHERE username = ?");
        $stmt->execute([$admin['admin_username']]);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            // 更新现有管理员账号
            $passwordHash = password_hash($admin['admin_password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE admin_user SET password = ?, realname = ? WHERE username = ?");
            $stmt->execute([$passwordHash, $admin['admin_realname'], $admin['admin_username']]);
        } else {
            // 创建新管理员账号
            $passwordHash = password_hash($admin['admin_password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO admin_user (username, password, realname, role) VALUES (?, ?, ?, 'super_admin')");
            $stmt->execute([$admin['admin_username'], $passwordHash, $admin['admin_realname']]);
        }
        
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
    <title>小说网系统安装 - 修复版</title>
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
        .error-message { background: #fff2f0; border: 1px solid #ffccc7; padding: 15px; border-radius: 4px; margin-bottom: 20px; color: #f5222d; }
        .info-message { background: #f6ffed; border: 1px solid #b7eb8f; padding: 15px; border-radius: 4px; margin-bottom: 20px; color: #52c41a; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>小说网系统安装向导 - 修复版</h1>
        </div>
        <div class="content">
            <div class="step-nav">
                <div class="step <?= $step == 1 ? 'active' : '' ?>">环境检测</div>
                <div class="step <?= $step == 2 ? 'active' : '' ?>">数据库配置</div>
                <div class="step <?= $step == 3 ? 'active' : '' ?>">管理员设置</div>
                <div class="step <?= $step == 4 ? 'active' : '' ?>">安装完成</div>
            </div>
            
            <?php if (isset($result['message']) && !$result['success']): ?>
                <div class="error-message">
                    <strong>错误：</strong> <?= htmlspecialchars($result['message']) ?>
                </div>
            <?php elseif (isset($result['message']) && $result['success']): ?>
                <div class="info-message">
                    <strong>成功：</strong> <?= htmlspecialchars($result['message']) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($step == 1): ?>
                <form method="post">
                    <h3>环境检测</h3>
                    <p>请确保以下环境要求都已满足：</p>
                    <ul class="check-list">
                        <?php $result = checkEnvironment(); ?>
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
                        <input type="text" name="admin_username" value="admin" required>
                        <small style="color: #666;">只能包含字母、数字和下划线，长度为3-20位</small>
                    </div>
                    <div class="form-group">
                        <label>管理员真实姓名（可选）</label>
                        <input type="text" name="admin_realname" value="系统管理员">
                        <small style="color: #666;">显示用的姓名，可以为空</small>
                    </div>
                    <div class="form-group">
                        <label>管理员密码</label>
                        <input type="password" name="admin_password" required>
                        <small style="color: #666;">密码长度至少6位</small>
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
                    <h2>🎉 安装完成！</h2>
                    <p>小说网系统已成功安装。</p>
                    <p><strong>管理员账号：</strong> <?= htmlspecialchars($_POST['admin_username'] ?? 'admin') ?></p>
                    <p><strong>后台地址：</strong> <a href="../admin/">/admin/</a></p>
                    <p><strong>前端地址：</strong> <a href="../">/</a></p>
                    <p style="margin-top: 30px;">
                        <a href="../" class="btn">访问网站</a>
                        <a href="../admin/" class="btn" style="margin-left: 10px;">登录后台</a>
                    </p>
                    <div style="margin-top: 30px; padding: 15px; background: #f6ffed; border-radius: 4px; text-align: left;">
                        <h4>📝 重要提示：</h4>
                        <ul style="margin-left: 20px;">
                            <li>请记住您的管理员账号和密码</li>
                            <li>首次登录后，请立即修改密码</li>
                            <li>安装完成后会自动创建 <code>install.lock</code> 文件</li>
                            <li>如需重新安装，请先删除 <code>install.lock</code> 文件</li>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>