<?php
/**
 * 系统设置模块
 * 管理网站基本信息、SEO设置、联系信息等
 */

session_start();

// 检查管理员登录
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.html');
    exit;
}

// 引入数据库配置 - 优先使用安装程序创建的配置
$dbConfig = null;

if (file_exists(__DIR__ . '/../backend/config/database.php')) {
    // 使用安装程序创建的配置
    $dbConfig = require __DIR__ . '/../backend/config/database.php';
} elseif (file_exists(__DIR__ . '/config.php')) {
    // 使用admin目录下的配置
    require_once __DIR__ . '/config.php';
    $dbConfig = [
        'host' => DB_HOST,
        'port' => DB_PORT,
        'database' => DB_NAME,
        'username' => DB_USER,
        'password' => DB_PASS,
        'charset' => DB_CHARSET
    ];
}

if (!$dbConfig) {
    die("错误：数据库配置文件不存在。请先运行安装程序 /install/");
}

// 数据库连接
try {
    $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}";
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    // 显示友好的错误页面
    ?>
    <!DOCTYPE html>
    <html lang="zh-CN">
    <head>
        <meta charset="UTF-8">
        <title>数据库连接错误</title>
        <style>
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0;
            }
            .error-box {
                background: white;
                padding: 40px;
                border-radius: 16px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                max-width: 500px;
                text-align: center;
            }
            .error-icon {
                font-size: 64px;
                margin-bottom: 20px;
            }
            .error-title {
                font-size: 24px;
                color: #dc3545;
                margin-bottom: 15px;
            }
            .error-message {
                color: #6c757d;
                margin-bottom: 25px;
                line-height: 1.6;
            }
            .btn {
                display: inline-block;
                padding: 12px 30px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                text-decoration: none;
                border-radius: 8px;
                transition: transform 0.3s;
            }
            .btn:hover {
                transform: translateY(-2px);
            }
            .config-info {
                background: #f8f9fa;
                padding: 15px;
                border-radius: 8px;
                margin: 20px 0;
                text-align: left;
                font-size: 14px;
            }
            .config-info code {
                background: #e9ecef;
                padding: 2px 6px;
                border-radius: 4px;
            }
        </style>
    </head>
    <body>
        <div class="error-box">
            <div class="error-icon">⚠️</div>
            <div class="error-title">数据库连接失败</div>
            <div class="error-message">
                无法连接到数据库服务器，请检查以下配置：
            </div>
            <div class="config-info">
                <strong>当前配置：</strong><br>
                主机: <code><?php echo DB_HOST; ?></code><br>
                数据库: <code><?php echo DB_NAME; ?></code><br>
                用户: <code><?php echo DB_USER; ?></code><br>
                <br>
                <strong>错误信息：</strong><br>
                <?php echo htmlspecialchars($e->getMessage()); ?>
            </div>
            <div class="error-message">
                请修改 <code>backend/config/database.php</code> 文件中的数据库配置
            </div>
            <a href="dashboard.html" class="btn">返回后台</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// 创建系统设置表（如果不存在）
$createTableSql = "
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
";
$pdo->exec($createTableSql);

// 默认设置
$defaultSettings = [
    'site_name' => ['value' => '小说网', 'type' => 'text', 'desc' => '网站名称'],
    'site_title' => ['value' => '小说网 - 免费小说阅读', 'type' => 'text', 'desc' => '网站标题'],
    'site_keywords' => ['value' => '小说,免费小说,在线阅读', 'type' => 'text', 'desc' => 'SEO关键词'],
    'site_description' => ['value' => '提供海量免费小说在线阅读，更新及时，阅读体验优秀。', 'type' => 'textarea', 'desc' => 'SEO描述'],
    'site_logo' => ['value' => '', 'type' => 'image', 'desc' => '网站Logo'],
    'site_favicon' => ['value' => '', 'type' => 'image', 'desc' => '网站图标'],
    'contact_email' => ['value' => 'admin@example.com', 'type' => 'text', 'desc' => '联系邮箱'],
    'contact_phone' => ['value' => '', 'type' => 'text', 'desc' => '联系电话'],
    'contact_qq' => ['value' => '', 'type' => 'text', 'desc' => '联系QQ'],
    'contact_wechat' => ['value' => '', 'type' => 'text', 'desc' => '微信号'],
    'footer_text' => ['value' => '© 2024 小说网 版权所有', 'type' => 'text', 'desc' => '页脚文字'],
    'footer_icp' => ['value' => '', 'type' => 'text', 'desc' => 'ICP备案号'],
    'footer_police' => ['value' => '', 'type' => 'text', 'desc' => '公安备案号'],
    'site_status' => ['value' => '1', 'type' => 'switch', 'desc' => '网站状态（1开启 0关闭）'],
    'close_reason' => ['value' => '', 'type' => 'textarea', 'desc' => '关闭原因'],
    'register_open' => ['value' => '1', 'type' => 'switch', 'desc' => '开放注册（1开启 0关闭）'],
    'comment_open' => ['value' => '1', 'type' => 'switch', 'desc' => '开放评论（1开启 0关闭）'],
];

// 初始化默认设置
foreach ($defaultSettings as $key => $setting) {
    $stmt = $pdo->prepare("SELECT id FROM system_settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    if (!$stmt->fetch()) {
        $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value, setting_type, description) VALUES (?, ?, ?, ?)");
        $stmt->execute([$key, $setting['value'], $setting['type'], $setting['desc']]);
    }
}

// 处理AJAX请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'get_settings':
            // 获取所有设置
            $stmt = $pdo->query("SELECT setting_key, setting_value, setting_type, description FROM system_settings ORDER BY id");
            $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            
            echo json_encode(['code' => 0, 'msg' => 'success', 'data' => $settings]);
            break;
            
        case 'save_settings':
            // 保存设置
            $settings = $_POST['settings'] ?? [];
            
            try {
                $pdo->beginTransaction();
                
                foreach ($settings as $key => $value) {
                    $stmt = $pdo->prepare("UPDATE system_settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?");
                    $stmt->execute([$value, $key]);
                }
                
                // 记录操作日志
                $logSql = "INSERT INTO sys_log (user_id, action, description, ip, user_agent) 
                           VALUES (?, 'update_settings', ?, ?, ?)";
                $logStmt = $pdo->prepare($logSql);
                $logStmt->execute([
                    $_SESSION['admin_id'] ?? 0,
                    '更新系统设置',
                    $_SERVER['REMOTE_ADDR'] ?? '',
                    $_SERVER['HTTP_USER_AGENT'] ?? ''
                ]);
                
                $pdo->commit();
                echo json_encode(['code' => 0, 'msg' => '保存成功']);
            } catch (Exception $e) {
                $pdo->rollBack();
                echo json_encode(['code' => 1, 'msg' => '保存失败: ' . $e->getMessage()]);
            }
            break;
            
        case 'upload_image':
            // 上传图片
            if (!isset($_FILES['image'])) {
                echo json_encode(['code' => 1, 'msg' => '没有接收到文件']);
                break;
            }
            
            if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                $errorMsg = '上传错误: ';
                switch ($_FILES['image']['error']) {
                    case UPLOAD_ERR_INI_SIZE: $errorMsg .= '文件大小超过服务器限制'; break;
                    case UPLOAD_ERR_FORM_SIZE: $errorMsg .= '文件大小超过表单限制'; break;
                    case UPLOAD_ERR_PARTIAL: $errorMsg .= '文件上传不完整'; break;
                    case UPLOAD_ERR_NO_FILE: $errorMsg .= '没有选择文件'; break;
                    case UPLOAD_ERR_NO_TMP_DIR: $errorMsg .= '服务器临时目录不存在'; break;
                    case UPLOAD_ERR_CANT_WRITE: $errorMsg .= '文件写入失败'; break;
                    default: $errorMsg .= '错误代码 ' . $_FILES['image']['error'];
                }
                echo json_encode(['code' => 1, 'msg' => $errorMsg]);
                break;
            }
            
            $file = $_FILES['image'];
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            
            if (!in_array($file['type'], $allowedTypes)) {
                echo json_encode(['code' => 1, 'msg' => '只支持JPG、PNG、GIF、WEBP格式，当前类型: ' . $file['type']]);
                break;
            }
            
            if ($file['size'] > 2 * 1024 * 1024) {
                echo json_encode(['code' => 1, 'msg' => '图片大小不能超过2MB']);
                break;
            }
            
            $uploadDir = __DIR__ . '/../public/uploads/settings/';
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0755, true)) {
                    echo json_encode(['code' => 1, 'msg' => '创建上传目录失败: ' . $uploadDir]);
                    break;
                }
            }
            
            if (!is_writable($uploadDir)) {
                echo json_encode(['code' => 1, 'msg' => '上传目录没有写入权限: ' . $uploadDir]);
                break;
            }
            
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'setting_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
            $filepath = $uploadDir . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                $url = '/public/uploads/settings/' . $filename;
                echo json_encode(['code' => 0, 'msg' => '上传成功', 'data' => ['url' => $url]]);
            } else {
                echo json_encode(['code' => 1, 'msg' => '文件保存失败，请检查目录权限']);
            }
            break;
            
        default:
            echo json_encode(['code' => 1, 'msg' => '未知操作']);
    }
    
    exit;
}

// 获取当前设置
$stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
$currentSettings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系统设置 - 小说网后台</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1000px;
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
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 28px;
            font-weight: 600;
        }
        
        .btn {
            padding: 10px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: white;
            color: #667eea;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        
        .btn-secondary {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .content {
            padding: 30px;
        }
        
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 10px;
        }
        
        .tab-btn {
            padding: 12px 24px;
            border: none;
            background: #f8f9fa;
            border-radius: 8px 8px 0 0;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            color: #6c757d;
            transition: all 0.3s;
        }
        
        .tab-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .form-section {
            margin-bottom: 30px;
        }
        
        .form-section h3 {
            font-size: 18px;
            color: #495057;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
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
        
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="url"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .switch-wrapper {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 26px;
        }
        
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 26px;
        }
        
        .slider:before {
            position: absolute;
            content: "";
            height: 20px;
            width: 20px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .slider {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        input:checked + .slider:before {
            transform: translateX(24px);
        }
        
        .image-upload {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .image-preview {
            width: 100px;
            height: 100px;
            border: 2px dashed #ced4da;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background: #f8f9fa;
        }
        
        .image-preview img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        
        .image-preview-placeholder {
            color: #6c757d;
            font-size: 12px;
            text-align: center;
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }
        
        .tip {
            font-size: 12px;
            color: #6c757d;
            margin-top: 5px;
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: none;
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
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .tabs {
                flex-wrap: wrap;
            }
            
            .tab-btn {
                flex: 1;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚙️ 系统设置</h1>
            <button class="btn btn-primary" onclick="goBack()">← 返回后台</button>
        </div>
        
        <div class="content">
            <div id="alertBox" class="alert"></div>
            
            <div class="tabs">
                <button class="tab-btn active" onclick="switchTab('basic')">基本信息</button>
                <button class="tab-btn" onclick="switchTab('seo')">SEO设置</button>
                <button class="tab-btn" onclick="switchTab('contact')">联系方式</button>
                <button class="tab-btn" onclick="switchTab('footer')">页脚设置</button>
                <button class="tab-btn" onclick="switchTab('system')">系统状态</button>
            </div>
            
            <form id="settingsForm">
                <!-- 基本信息 -->
                <div class="tab-content active" id="basic">
                    <div class="form-section">
                        <h3>网站信息</h3>
                        
                        <div class="form-group">
                            <label>网站名称</label>
                            <input type="text" name="site_name" value="<?php echo htmlspecialchars($currentSettings['site_name'] ?? ''); ?>">
                            <p class="tip">显示在浏览器标签和网站顶部</p>
                        </div>
                        
                        <div class="form-group">
                            <label>网站标题</label>
                            <input type="text" name="site_title" value="<?php echo htmlspecialchars($currentSettings['site_title'] ?? ''); ?>">
                            <p class="tip">SEO标题，显示在搜索引擎结果中</p>
                        </div>
                        
                        <div class="form-group">
                            <label>网站Logo</label>
                            <div class="image-upload">
                                <div class="image-preview" id="logoPreview">
                                    <?php if (!empty($currentSettings['site_logo'])): ?>
                                        <img src="<?php echo htmlspecialchars($currentSettings['site_logo']); ?>" alt="Logo">
                                    <?php else: ?>
                                        <div class="image-preview-placeholder">暂无图片</div>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <input type="hidden" name="site_logo" id="logoInput" value="<?php echo htmlspecialchars($currentSettings['site_logo'] ?? ''); ?>">
                                    <input type="file" id="logoFile" accept="image/*" style="display: none;" onchange="uploadImage('logo', this)">
                                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('logoFile').click()">上传Logo</button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>网站图标 (Favicon)</label>
                            <div class="image-upload">
                                <div class="image-preview" id="faviconPreview">
                                    <?php if (!empty($currentSettings['site_favicon'])): ?>
                                        <img src="<?php echo htmlspecialchars($currentSettings['site_favicon']); ?>" alt="Favicon">
                                    <?php else: ?>
                                        <div class="image-preview-placeholder">暂无图片</div>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <input type="hidden" name="site_favicon" id="faviconInput" value="<?php echo htmlspecialchars($currentSettings['site_favicon'] ?? ''); ?>">
                                    <input type="file" id="faviconFile" accept="image/*" style="display: none;" onchange="uploadImage('favicon', this)">
                                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('faviconFile').click()">上传图标</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- SEO设置 -->
                <div class="tab-content" id="seo">
                    <div class="form-section">
                        <h3>SEO优化</h3>
                        
                        <div class="form-group">
                            <label>SEO关键词</label>
                            <input type="text" name="site_keywords" value="<?php echo htmlspecialchars($currentSettings['site_keywords'] ?? ''); ?>">
                            <p class="tip">多个关键词用逗号分隔</p>
                        </div>
                        
                        <div class="form-group">
                            <label>SEO描述</label>
                            <textarea name="site_description"><?php echo htmlspecialchars($currentSettings['site_description'] ?? ''); ?></textarea>
                            <p class="tip">显示在搜索引擎结果中的描述文字，建议150字以内</p>
                        </div>
                    </div>
                </div>
                
                <!-- 联系方式 -->
                <div class="tab-content" id="contact">
                    <div class="form-section">
                        <h3>联系信息</h3>
                        
                        <div class="form-group">
                            <label>联系邮箱</label>
                            <input type="email" name="contact_email" value="<?php echo htmlspecialchars($currentSettings['contact_email'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>联系电话</label>
                            <input type="text" name="contact_phone" value="<?php echo htmlspecialchars($currentSettings['contact_phone'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>联系QQ</label>
                            <input type="text" name="contact_qq" value="<?php echo htmlspecialchars($currentSettings['contact_qq'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>微信号</label>
                            <input type="text" name="contact_wechat" value="<?php echo htmlspecialchars($currentSettings['contact_wechat'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
                
                <!-- 页脚设置 -->
                <div class="tab-content" id="footer">
                    <div class="form-section">
                        <h3>页脚信息</h3>
                        
                        <div class="form-group">
                            <label>页脚文字</label>
                            <input type="text" name="footer_text" value="<?php echo htmlspecialchars($currentSettings['footer_text'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>ICP备案号</label>
                            <input type="text" name="footer_icp" value="<?php echo htmlspecialchars($currentSettings['footer_icp'] ?? ''); ?>">
                            <p class="tip">如：京ICP备12345678号</p>
                        </div>
                        
                        <div class="form-group">
                            <label>公安备案号</label>
                            <input type="text" name="footer_police" value="<?php echo htmlspecialchars($currentSettings['footer_police'] ?? ''); ?>">
                            <p class="tip">如：京公网安备 11010502000000号</p>
                        </div>
                    </div>
                </div>
                
                <!-- 系统状态 -->
                <div class="tab-content" id="system">
                    <div class="form-section">
                        <h3>系统开关</h3>
                        
                        <div class="form-group">
                            <label>网站状态</label>
                            <div class="switch-wrapper">
                                <label class="switch">
                                    <input type="checkbox" name="site_status" <?php echo ($currentSettings['site_status'] ?? '1') == '1' ? 'checked' : ''; ?> value="1">
                                    <span class="slider"></span>
                                </label>
                                <span>开启网站</span>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>关闭原因</label>
                            <textarea name="close_reason" placeholder="网站关闭时显示的原因"><?php echo htmlspecialchars($currentSettings['close_reason'] ?? ''); ?></textarea>
                            <p class="tip">仅在网站关闭时显示</p>
                        </div>
                        
                        <div class="form-group">
                            <label>用户注册</label>
                            <div class="switch-wrapper">
                                <label class="switch">
                                    <input type="checkbox" name="register_open" <?php echo ($currentSettings['register_open'] ?? '1') == '1' ? 'checked' : ''; ?> value="1">
                                    <span class="slider"></span>
                                </label>
                                <span>开放注册</span>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>评论功能</label>
                            <div class="switch-wrapper">
                                <label class="switch">
                                    <input type="checkbox" name="comment_open" <?php echo ($currentSettings['comment_open'] ?? '1') == '1' ? 'checked' : ''; ?> value="1">
                                    <span class="slider"></span>
                                </label>
                                <span>开放评论</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-success" onclick="saveSettings()">💾 保存设置</button>
                    <button type="button" class="btn btn-secondary" onclick="resetForm()">重置</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // 切换标签
        function switchTab(tabId) {
            // 移除所有活动状态
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            
            // 添加当前活动状态
            event.target.classList.add('active');
            document.getElementById(tabId).classList.add('active');
        }
        
        // 上传图片
        function uploadImage(type, input) {
            if (!input.files || !input.files[0]) return;
            
            const formData = new FormData();
            formData.append('action', 'upload_image');
            formData.append('image', input.files[0]);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.code === 0) {
                    document.getElementById(type + 'Input').value = data.data.url;
                    document.getElementById(type + 'Preview').innerHTML = '<img src="' + data.data.url + '" alt="">';
                    showAlert('上传成功', 'success');
                } else {
                    showAlert(data.msg, 'error');
                }
            })
            .catch(error => {
                showAlert('上传失败', 'error');
            });
        }
        
        // 保存设置
        function saveSettings() {
            const form = document.getElementById('settingsForm');
            const formData = new FormData();
            
            // 收集所有设置
            const settings = {};
            form.querySelectorAll('input[name], textarea[name], select[name]').forEach(input => {
                if (input.type === 'checkbox') {
                    settings[input.name] = input.checked ? '1' : '0';
                } else {
                    settings[input.name] = input.value;
                }
            });
            
            formData.append('action', 'save_settings');
            formData.append('settings', JSON.stringify(settings));
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.code === 0) {
                    showAlert('保存成功', 'success');
                } else {
                    showAlert(data.msg, 'error');
                }
            })
            .catch(error => {
                showAlert('保存失败', 'error');
            });
        }
        
        // 显示提示
        function showAlert(message, type) {
            const alertBox = document.getElementById('alertBox');
            alertBox.className = 'alert alert-' + type;
            alertBox.textContent = message;
            alertBox.style.display = 'block';
            
            setTimeout(() => {
                alertBox.style.display = 'none';
            }, 3000);
        }
        
        // 重置表单
        function resetForm() {
            if (confirm('确定要重置所有设置吗？')) {
                location.reload();
            }
        }
        
        // 返回后台
        function goBack() {
            window.location.href = 'dashboard.html';
        }
    </script>
</body>
</html>
