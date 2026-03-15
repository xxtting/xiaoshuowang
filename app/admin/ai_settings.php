<?php
/**
 * AI设置模块
 * 管理AI内容生成的API配置和参数
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
    $dbConfig = require __DIR__ . '/../backend/config/database.php';
} elseif (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
    $dbConfig = [
        'host' => DB_HOST, 'port' => DB_PORT, 'database' => DB_NAME,
        'username' => DB_USER, 'password' => DB_PASS, 'charset' => DB_CHARSET
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
    die("数据库连接失败: " . $e->getMessage() . "<br><br>请检查 backend/config/database.php 文件中的配置。");
}

// 创建AI设置表
$createTableSql = "
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
";
$pdo->exec($createTableSql);

// 默认AI设置
$defaultSettings = [
    'ai_provider' => ['value' => 'openai', 'desc' => 'AI服务商'],
    'api_key' => ['value' => '', 'desc' => 'API密钥'],
    'api_url' => ['value' => 'https://api.openai.com/v1', 'desc' => 'API地址'],
    'model' => ['value' => 'gpt-3.5-turbo', 'desc' => '使用的模型'],
    'max_tokens' => ['value' => '2000', 'desc' => '最大生成token数'],
    'temperature' => ['value' => '0.7', 'desc' => '生成温度(0-2)'],
    'top_p' => ['value' => '1', 'desc' => 'Top P值(0-1)'],
    'frequency_penalty' => ['value' => '0', 'desc' => '频率惩罚(0-2)'],
    'presence_penalty' => ['value' => '0', 'desc' => '存在惩罚(0-2)'],
    'enable_novel_gen' => ['value' => '1', 'desc' => '启用小说生成'],
    'enable_chapter_gen' => ['value' => '1', 'desc' => '启用章节生成'],
    'enable_outline_gen' => ['value' => '1', 'desc' => '启用大纲生成'],
    'daily_limit' => ['value' => '100', 'desc' => '每日生成次数限制'],
    'content_filter' => ['value' => '1', 'desc' => '启用内容过滤'],
];

// 初始化默认设置
foreach ($defaultSettings as $key => $setting) {
    $stmt = $pdo->prepare("SELECT id FROM ai_settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    if (!$stmt->fetch()) {
        $stmt = $pdo->prepare("INSERT INTO ai_settings (setting_key, setting_value, description) VALUES (?, ?, ?)");
        $stmt->execute([$key, $setting['value'], $setting['desc']]);
    }
}

// 处理AJAX请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'get_settings':
            // 获取所有设置
            $stmt = $pdo->query("SELECT setting_key, setting_value FROM ai_settings");
            $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            
            echo json_encode(['code' => 0, 'msg' => 'success', 'data' => $settings]);
            break;
            
        case 'save_settings':
            // 保存设置
            $settings = $_POST['settings'] ?? [];
            
            try {
                $pdo->beginTransaction();
                
                foreach ($settings as $key => $value) {
                    $stmt = $pdo->prepare("UPDATE ai_settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?");
                    $stmt->execute([$value, $key]);
                }
                
                // 记录操作日志
                $logSql = "INSERT INTO sys_log (user_id, action, description, ip, user_agent) 
                           VALUES (?, 'update_ai_settings', ?, ?, ?)";
                $logStmt = $pdo->prepare($logSql);
                $logStmt->execute([
                    $_SESSION['admin_id'] ?? 0,
                    '更新AI设置',
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
            
        case 'test_connection':
            // 测试API连接
            $apiKey = $_POST['api_key'] ?? '';
            $apiUrl = $_POST['api_url'] ?? '';
            $model = $_POST['model'] ?? 'gpt-3.5-turbo';
            
            if (!$apiKey) {
                echo json_encode(['code' => 1, 'msg' => '请输入API密钥']);
                break;
            }
            
            // 发送测试请求
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $apiUrl . '/chat/completions',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode([
                    'model' => $model,
                    'messages' => [
                        ['role' => 'user', 'content' => '测试连接，请回复"连接成功"']
                    ],
                    'max_tokens' => 50
                ]),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $apiKey
                ],
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => false
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                echo json_encode(['code' => 1, 'msg' => '连接失败: ' . $error]);
            } else if ($httpCode == 200) {
                $data = json_decode($response, true);
                $reply = $data['choices'][0]['message']['content'] ?? '连接成功';
                echo json_encode(['code' => 0, 'msg' => '连接成功', 'data' => ['reply' => $reply]]);
            } else {
                $errorData = json_decode($response, true);
                $errorMsg = $errorData['error']['message'] ?? '未知错误';
                echo json_encode(['code' => 1, 'msg' => 'API错误: ' . $errorMsg]);
            }
            break;
            
        case 'test_generate':
            // 测试内容生成
            $apiKey = $_POST['api_key'] ?? '';
            $apiUrl = $_POST['api_url'] ?? '';
            $model = $_POST['model'] ?? 'gpt-3.5-turbo';
            $prompt = $_POST['prompt'] ?? '请生成一段简短的小说开头';
            
            if (!$apiKey) {
                echo json_encode(['code' => 1, 'msg' => '请输入API密钥']);
                break;
            }
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $apiUrl . '/chat/completions',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode([
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => '你是一位专业的小说作家，擅长创作引人入胜的故事。'],
                        ['role' => 'user', 'content' => $prompt]
                    ],
                    'max_tokens' => 500,
                    'temperature' => 0.7
                ]),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $apiKey
                ],
                CURLOPT_TIMEOUT => 60,
                CURLOPT_SSL_VERIFYPEER => false
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                echo json_encode(['code' => 1, 'msg' => '生成失败: ' . $error]);
            } else if ($httpCode == 200) {
                $data = json_decode($response, true);
                $content = $data['choices'][0]['message']['content'] ?? '';
                echo json_encode(['code' => 0, 'msg' => '生成成功', 'data' => ['content' => $content]]);
            } else {
                $errorData = json_decode($response, true);
                $errorMsg = $errorData['error']['message'] ?? '未知错误';
                echo json_encode(['code' => 1, 'msg' => '生成失败: ' . $errorMsg]);
            }
            break;
            
        default:
            echo json_encode(['code' => 1, 'msg' => '未知操作']);
    }
    
    exit;
}

// 获取当前设置
$stmt = $pdo->query("SELECT setting_key, setting_value FROM ai_settings");
$currentSettings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI设置 - 小说网后台</title>
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
        
        .btn-info {
            background: #17a2b8;
            color: white;
        }
        
        .btn-warning {
            background: #ffc107;
            color: #212529;
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
        .form-group input[type="password"],
        .form-group input[type="number"],
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
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
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
        
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        .test-result {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin-top: 15px;
            border-left: 4px solid #667eea;
        }
        
        .test-result h4 {
            font-size: 14px;
            color: #495057;
            margin-bottom: 10px;
        }
        
        .test-result pre {
            background: white;
            padding: 12px;
            border-radius: 4px;
            overflow-x: auto;
            font-size: 13px;
            line-height: 1.6;
        }
        
        .provider-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .provider-option {
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            cursor: pointer;
            text-align: center;
            transition: all 0.3s;
        }
        
        .provider-option:hover {
            border-color: #667eea;
        }
        
        .provider-option.active {
            border-color: #667eea;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .provider-option img {
            height: 30px;
            margin-bottom: 8px;
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
            
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🤖 AI设置</h1>
            <button class="btn btn-primary" onclick="goBack()">← 返回后台</button>
        </div>
        
        <div class="content">
            <div id="alertBox" class="alert"></div>
            
            <div class="tabs">
                <button class="tab-btn active" onclick="switchTab('api')">API配置</button>
                <button class="tab-btn" onclick="switchTab('model')">模型参数</button>
                <button class="tab-btn" onclick="switchTab('features')">功能开关</button>
                <button class="tab-btn" onclick="switchTab('test')">测试工具</button>
            </div>
            
            <form id="settingsForm">
                <!-- API配置 -->
                <div class="tab-content active" id="api">
                    <div class="form-section">
                        <h3>选择AI服务商</h3>
                        
                        <div class="provider-options">
                            <div class="provider-option <?php echo ($currentSettings['ai_provider'] ?? '') == 'openai' ? 'active' : ''; ?>" onclick="selectProvider('openai')">
                                <div>🤖</div>
                                <div>OpenAI</div>
                            </div>
                            <div class="provider-option <?php echo ($currentSettings['ai_provider'] ?? '') == 'claude' ? 'active' : ''; ?>" onclick="selectProvider('claude')">
                                <div>🧠</div>
                                <div>Claude</div>
                            </div>
                            <div class="provider-option <?php echo ($currentSettings['ai_provider'] ?? '') == 'tongyi' ? 'active' : ''; ?>" onclick="selectProvider('tongyi')">
                                <div>🎯</div>
                                <div>通义千问</div>
                            </div>
                            <div class="provider-option <?php echo ($currentSettings['ai_provider'] ?? '') == 'wenxin' ? 'active' : ''; ?>" onclick="selectProvider('wenxin')">
                                <div>📝</div>
                                <div>文心一言</div>
                            </div>
                            <div class="provider-option <?php echo ($currentSettings['ai_provider'] ?? '') == 'custom' ? 'active' : ''; ?>" onclick="selectProvider('custom')">
                                <div>⚙️</div>
                                <div>自定义</div>
                            </div>
                        </div>
                        
                        <input type="hidden" name="ai_provider" id="aiProvider" value="<?php echo htmlspecialchars($currentSettings['ai_provider'] ?? 'openai'); ?>">
                    </div>
                    
                    <div class="form-section">
                        <h3>API配置</h3>
                        
                        <div class="form-group">
                            <label>API密钥</label>
                            <input type="password" name="api_key" id="apiKey" value="<?php echo htmlspecialchars($currentSettings['api_key'] ?? ''); ?>" placeholder="sk-...">
                            <p class="tip">您的API密钥将安全保存，不会泄露</p>
                        </div>
                        
                        <div class="form-group">
                            <label>API地址</label>
                            <input type="text" name="api_url" id="apiUrl" value="<?php echo htmlspecialchars($currentSettings['api_url'] ?? 'https://api.openai.com/v1'); ?>" placeholder="https://api.openai.com/v1">
                            <p class="tip">API端点地址，可使用代理地址</p>
                        </div>
                        
                        <div class="form-group">
                            <label>使用模型</label>
                            <select name="model" id="model">
                                <option value="gpt-3.5-turbo" <?php echo ($currentSettings['model'] ?? '') == 'gpt-3.5-turbo' ? 'selected' : ''; ?>>GPT-3.5 Turbo</option>
                                <option value="gpt-4" <?php echo ($currentSettings['model'] ?? '') == 'gpt-4' ? 'selected' : ''; ?>>GPT-4</option>
                                <option value="gpt-4-turbo-preview" <?php echo ($currentSettings['model'] ?? '') == 'gpt-4-turbo-preview' ? 'selected' : ''; ?>>GPT-4 Turbo</option>
                                <option value="claude-3-opus-20240229" <?php echo ($currentSettings['model'] ?? '') == 'claude-3-opus-20240229' ? 'selected' : ''; ?>>Claude 3 Opus</option>
                                <option value="claude-3-sonnet-20240229" <?php echo ($currentSettings['model'] ?? '') == 'claude-3-sonnet-20240229' ? 'selected' : ''; ?>>Claude 3 Sonnet</option>
                                <option value="qwen-turbo" <?php echo ($currentSettings['model'] ?? '') == 'qwen-turbo' ? 'selected' : ''; ?>>通义千问 Turbo</option>
                                <option value="qwen-plus" <?php echo ($currentSettings['model'] ?? '') == 'qwen-plus' ? 'selected' : ''; ?>>通义千问 Plus</option>
                                <option value="qwen-max" <?php echo ($currentSettings['model'] ?? '') == 'qwen-max' ? 'selected' : ''; ?>>通义千问 Max</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- 模型参数 -->
                <div class="tab-content" id="model">
                    <div class="form-section">
                        <h3>生成参数</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>最大Token数</label>
                                <input type="number" name="max_tokens" value="<?php echo htmlspecialchars($currentSettings['max_tokens'] ?? '2000'); ?>" min="100" max="4000">
                                <p class="tip">生成内容的最大长度（100-4000）</p>
                            </div>
                            
                            <div class="form-group">
                                <label>温度 (Temperature)</label>
                                <input type="number" name="temperature" value="<?php echo htmlspecialchars($currentSettings['temperature'] ?? '0.7'); ?>" min="0" max="2" step="0.1">
                                <p class="tip">控制随机性，0-2之间，值越大越随机</p>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Top P</label>
                                <input type="number" name="top_p" value="<?php echo htmlspecialchars($currentSettings['top_p'] ?? '1'); ?>" min="0" max="1" step="0.1">
                                <p class="tip">核采样参数，0-1之间</p>
                            </div>
                            
                            <div class="form-group">
                                <label>频率惩罚</label>
                                <input type="number" name="frequency_penalty" value="<?php echo htmlspecialchars($currentSettings['frequency_penalty'] ?? '0'); ?>" min="0" max="2" step="0.1">
                                <p class="tip">降低重复内容的概率，0-2之间</p>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>存在惩罚</label>
                                <input type="number" name="presence_penalty" value="<?php echo htmlspecialchars($currentSettings['presence_penalty'] ?? '0'); ?>" min="0" max="2" step="0.1">
                                <p class="tip">鼓励谈论新话题，0-2之间</p>
                            </div>
                            
                            <div class="form-group">
                                <label>每日生成限制</label>
                                <input type="number" name="daily_limit" value="<?php echo htmlspecialchars($currentSettings['daily_limit'] ?? '100'); ?>" min="1" max="1000">
                                <p class="tip">每个用户每日可生成次数</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 功能开关 -->
                <div class="tab-content" id="features">
                    <div class="form-section">
                        <h3>AI功能开关</h3>
                        
                        <div class="form-group">
                            <label>小说生成</label>
                            <div class="switch-wrapper">
                                <label class="switch">
                                    <input type="checkbox" name="enable_novel_gen" <?php echo ($currentSettings['enable_novel_gen'] ?? '1') == '1' ? 'checked' : ''; ?> value="1">
                                    <span class="slider"></span>
                                </label>
                                <span>启用AI小说生成功能</span>
                            </div>
                            <p class="tip">用户可以使用AI生成完整小说</p>
                        </div>
                        
                        <div class="form-group">
                            <label>章节生成</label>
                            <div class="switch-wrapper">
                                <label class="switch">
                                    <input type="checkbox" name="enable_chapter_gen" <?php echo ($currentSettings['enable_chapter_gen'] ?? '1') == '1' ? 'checked' : ''; ?> value="1">
                                    <span class="slider"></span>
                                </label>
                                <span>启用AI章节生成功能</span>
                            </div>
                            <p class="tip">用户可以使用AI生成小说章节</p>
                        </div>
                        
                        <div class="form-group">
                            <label>大纲生成</label>
                            <div class="switch-wrapper">
                                <label class="switch">
                                    <input type="checkbox" name="enable_outline_gen" <?php echo ($currentSettings['enable_outline_gen'] ?? '1') == '1' ? 'checked' : ''; ?> value="1">
                                    <span class="slider"></span>
                                </label>
                                <span>启用AI大纲生成功能</span>
                            </div>
                            <p class="tip">用户可以使用AI生成小说大纲</p>
                        </div>
                        
                        <div class="form-group">
                            <label>内容过滤</label>
                            <div class="switch-wrapper">
                                <label class="switch">
                                    <input type="checkbox" name="content_filter" <?php echo ($currentSettings['content_filter'] ?? '1') == '1' ? 'checked' : ''; ?> value="1">
                                    <span class="slider"></span>
                                </label>
                                <span>启用内容过滤</span>
                            </div>
                            <p class="tip">自动过滤生成内容中的敏感信息</p>
                        </div>
                    </div>
                </div>
                
                <!-- 测试工具 -->
                <div class="tab-content" id="test">
                    <div class="form-section">
                        <h3>API连接测试</h3>
                        
                        <button type="button" class="btn btn-info" onclick="testConnection()">🔗 测试连接</button>
                        
                        <div id="connectionResult" class="test-result" style="display: none;">
                            <h4>测试结果</h4>
                            <pre id="connectionContent"></pre>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3>内容生成测试</h3>
                        
                        <div class="form-group">
                            <label>测试提示词</label>
                            <textarea id="testPrompt" placeholder="请输入测试提示词...">请生成一段简短的小说开头，主题是"一个普通的程序员意外获得了超能力"</textarea>
                        </div>
                        
                        <button type="button" class="btn btn-warning" onclick="testGenerate()">✨ 测试生成</button>
                        
                        <div id="generateResult" class="test-result" style="display: none;">
                            <h4>生成结果</h4>
                            <pre id="generateContent"></pre>
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
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            
            event.target.classList.add('active');
            document.getElementById(tabId).classList.add('active');
        }
        
        // 选择服务商
        function selectProvider(provider) {
            document.querySelectorAll('.provider-option').forEach(opt => opt.classList.remove('active'));
            event.currentTarget.classList.add('active');
            document.getElementById('aiProvider').value = provider;
            
            // 自动填充默认配置
            const configs = {
                'openai': {
                    url: 'https://api.openai.com/v1',
                    model: 'gpt-3.5-turbo'
                },
                'claude': {
                    url: 'https://api.anthropic.com/v1',
                    model: 'claude-3-sonnet-20240229'
                },
                'tongyi': {
                    url: 'https://dashscope.aliyuncs.com/api/v1',
                    model: 'qwen-turbo'
                },
                'wenxin': {
                    url: 'https://aip.baidubce.com/rpc/2.0/ai_custom/v1',
                    model: 'ernie-bot-4'
                }
            };
            
            if (configs[provider]) {
                document.getElementById('apiUrl').value = configs[provider].url;
                document.getElementById('model').value = configs[provider].model;
            }
        }
        
        // 测试连接
        function testConnection() {
            const formData = new FormData();
            formData.append('action', 'test_connection');
            formData.append('api_key', document.getElementById('apiKey').value);
            formData.append('api_url', document.getElementById('apiUrl').value);
            formData.append('model', document.getElementById('model').value);
            
            showAlert('正在测试连接...', 'info');
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.code === 0) {
                    document.getElementById('connectionResult').style.display = 'block';
                    document.getElementById('connectionContent').textContent = '✅ ' + data.msg + '\n\n回复: ' + data.data.reply;
                    showAlert('连接成功', 'success');
                } else {
                    document.getElementById('connectionResult').style.display = 'block';
                    document.getElementById('connectionContent').textContent = '❌ ' + data.msg;
                    showAlert(data.msg, 'error');
                }
            })
            .catch(error => {
                showAlert('测试失败', 'error');
            });
        }
        
        // 测试生成
        function testGenerate() {
            const formData = new FormData();
            formData.append('action', 'test_generate');
            formData.append('api_key', document.getElementById('apiKey').value);
            formData.append('api_url', document.getElementById('apiUrl').value);
            formData.append('model', document.getElementById('model').value);
            formData.append('prompt', document.getElementById('testPrompt').value);
            
            showAlert('正在生成内容...', 'info');
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.code === 0) {
                    document.getElementById('generateResult').style.display = 'block';
                    document.getElementById('generateContent').textContent = data.data.content;
                    showAlert('生成成功', 'success');
                } else {
                    document.getElementById('generateResult').style.display = 'block';
                    document.getElementById('generateContent').textContent = '❌ ' + data.msg;
                    showAlert(data.msg, 'error');
                }
            })
            .catch(error => {
                showAlert('生成失败', 'error');
            });
        }
        
        // 保存设置
        function saveSettings() {
            const form = document.getElementById('settingsForm');
            const formData = new FormData();
            
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
