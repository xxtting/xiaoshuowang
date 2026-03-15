<?php
/**
 * 数据库备份模块
 * 支持数据库备份、恢复、下载、删除等功能
 */

session_start();

// 检查管理员登录
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.html');
    exit;
}

// 引入数据库配置
if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
} elseif (file_exists(__DIR__ . '/../backend/config/database.php')) {
    require_once __DIR__ . '/../backend/config/database.php';
} else {
    die("错误：数据库配置文件不存在。");
}

// 检查常量是否定义
if (!defined('DB_HOST') || !defined('DB_NAME') || !defined('DB_USER') || !defined('DB_PASS')) {
    die("错误：数据库配置不完整，请检查 config.php 文件。");
}

// 数据库连接
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("数据库连接失败: " . $e->getMessage() . "<br><br>请检查 backend/config/database.php 文件中的配置。");
}

// 备份目录
$backupDir = __DIR__ . '/../runtime/backups/';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

// 处理AJAX请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create_backup':
            // 创建备份
            try {
                $filename = 'backup_' . date('Ymd_His') . '_' . mt_rand(1000, 9999) . '.sql';
                $filepath = $backupDir . $filename;
                
                // 使用mysqldump命令备份（如果可用）
                $mysqldump = 'mysqldump';
                $command = sprintf(
                    '%s -h%s -u%s -p%s %s > %s 2>&1',
                    $mysqldump,
                    escapeshellarg(DB_HOST),
                    escapeshellarg(DB_USER),
                    escapeshellarg(DB_PASS),
                    escapeshellarg(DB_NAME),
                    escapeshellarg($filepath)
                );
                
                @exec($command, $output, $returnVar);
                
                // 如果mysqldump不可用，使用PHP备份
                if ($returnVar !== 0 || !file_exists($filepath) || filesize($filepath) === 0) {
                    // PHP方式备份
                    $sql = "-- 小说网数据库备份\n";
                    $sql .= "-- 备份时间: " . date('Y-m-d H:i:s') . "\n";
                    $sql .= "-- 数据库: " . DB_NAME . "\n\n";
                    $sql .= "SET NAMES utf8mb4;\n";
                    $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";
                    
                    // 获取所有表
                    $stmt = $pdo->query("SHOW TABLES");
                    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    
                    foreach ($tables as $table) {
                        // 获取表结构
                        $stmt = $pdo->query("SHOW CREATE TABLE `$table`");
                        $row = $stmt->fetch(PDO::FETCH_ASSOC);
                        $sql .= "-- 表结构: $table\n";
                        $sql .= "DROP TABLE IF EXISTS `$table`;\n";
                        $sql .= $row['Create Table'] . ";\n\n";
                        
                        // 获取表数据
                        $stmt = $pdo->query("SELECT * FROM `$table`");
                        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        if (count($rows) > 0) {
                            $sql .= "-- 表数据: $table\n";
                            
                            foreach ($rows as $row) {
                                $values = array_map(function($value) use ($pdo) {
                                    if ($value === null) {
                                        return 'NULL';
                                    }
                                    return $pdo->quote($value);
                                }, array_values($row));
                                
                                $columns = array_keys($row);
                                $sql .= sprintf(
                                    "INSERT INTO `%s` (`%s`) VALUES (%s);\n",
                                    $table,
                                    implode('`, `', $columns),
                                    implode(', ', $values)
                                );
                            }
                            $sql .= "\n";
                        }
                    }
                    
                    $sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";
                    
                    file_put_contents($filepath, $sql);
                }
                
                // 记录操作日志
                $logSql = "INSERT INTO sys_log (user_id, action, description, ip, user_agent) 
                           VALUES (?, 'create_backup', ?, ?, ?)";
                $logStmt = $pdo->prepare($logSql);
                $logStmt->execute([
                    $_SESSION['admin_id'] ?? 0,
                    '创建数据库备份: ' . $filename,
                    $_SERVER['REMOTE_ADDR'] ?? '',
                    $_SERVER['HTTP_USER_AGENT'] ?? ''
                ]);
                
                echo json_encode([
                    'code' => 0, 
                    'msg' => '备份创建成功',
                    'data' => [
                        'filename' => $filename,
                        'size' => round(filesize($filepath) / 1024, 2) . ' KB',
                        'time' => date('Y-m-d H:i:s')
                    ]
                ]);
            } catch (Exception $e) {
                echo json_encode(['code' => 1, 'msg' => '备份失败: ' . $e->getMessage()]);
            }
            break;
            
        case 'list_backups':
            // 获取备份列表
            $backups = [];
            
            if (is_dir($backupDir)) {
                $files = glob($backupDir . '*.sql');
                
                foreach ($files as $file) {
                    $backups[] = [
                        'filename' => basename($file),
                        'size' => round(filesize($file) / 1024, 2),
                        'time' => date('Y-m-d H:i:s', filemtime($file)),
                        'timestamp' => filemtime($file)
                    ];
                }
                
                // 按时间排序
                usort($backups, function($a, $b) {
                    return $b['timestamp'] - $a['timestamp'];
                });
            }
            
            echo json_encode(['code' => 0, 'msg' => 'success', 'data' => $backups]);
            break;
            
        case 'download_backup':
            // 下载备份文件
            $filename = $_POST['filename'] ?? '';
            
            if (!$filename || !preg_match('/^backup_\d{8}_\d{6}_\d{4}\.sql$/', $filename)) {
                echo json_encode(['code' => 1, 'msg' => '无效的文件名']);
                break;
            }
            
            $filepath = $backupDir . $filename;
            
            if (!file_exists($filepath)) {
                echo json_encode(['code' => 1, 'msg' => '文件不存在']);
                break;
            }
            
            // 记录操作日志
            $logSql = "INSERT INTO sys_log (user_id, action, description, ip, user_agent) 
                       VALUES (?, 'download_backup', ?, ?, ?)";
            $logStmt = $pdo->prepare($logSql);
            $logStmt->execute([
                $_SESSION['admin_id'] ?? 0,
                '下载数据库备份: ' . $filename,
                $_SERVER['REMOTE_ADDR'] ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
            
            // 输出文件
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . filesize($filepath));
            readfile($filepath);
            exit;
            
        case 'restore_backup':
            // 恢复备份
            $filename = $_POST['filename'] ?? '';
            
            if (!$filename || !preg_match('/^backup_\d{8}_\d{6}_\d{4}\.sql$/', $filename)) {
                echo json_encode(['code' => 1, 'msg' => '无效的文件名']);
                break;
            }
            
            $filepath = $backupDir . $filename;
            
            if (!file_exists($filepath)) {
                echo json_encode(['code' => 1, 'msg' => '文件不存在']);
                break;
            }
            
            try {
                // 读取SQL文件
                $sql = file_get_contents($filepath);
                
                // 分割SQL语句
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
                
                // 执行多条SQL语句
                $statements = array_filter(array_map('trim', explode(";\n", $sql)));
                
                foreach ($statements as $statement) {
                    if (!empty($statement) && $statement !== 'SET FOREIGN_KEY_CHECKS = 0' && $statement !== 'SET FOREIGN_KEY_CHECKS = 1') {
                        try {
                            $pdo->exec($statement);
                        } catch (Exception $e) {
                            // 忽略单条语句错误，继续执行
                        }
                    }
                }
                
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
                
                // 记录操作日志
                $logSql = "INSERT INTO sys_log (user_id, action, description, ip, user_agent) 
                           VALUES (?, 'restore_backup', ?, ?, ?)";
                $logStmt = $pdo->prepare($logSql);
                $logStmt->execute([
                    $_SESSION['admin_id'] ?? 0,
                    '恢复数据库备份: ' . $filename,
                    $_SERVER['REMOTE_ADDR'] ?? '',
                    $_SERVER['HTTP_USER_AGENT'] ?? ''
                ]);
                
                echo json_encode(['code' => 0, 'msg' => '数据库恢复成功']);
            } catch (Exception $e) {
                echo json_encode(['code' => 1, 'msg' => '恢复失败: ' . $e->getMessage()]);
            }
            break;
            
        case 'delete_backup':
            // 删除备份
            $filename = $_POST['filename'] ?? '';
            
            if (!$filename || !preg_match('/^backup_\d{8}_\d{6}_\d{4}\.sql$/', $filename)) {
                echo json_encode(['code' => 1, 'msg' => '无效的文件名']);
                break;
            }
            
            $filepath = $backupDir . $filename;
            
            if (!file_exists($filepath)) {
                echo json_encode(['code' => 1, 'msg' => '文件不存在']);
                break;
            }
            
            if (unlink($filepath)) {
                // 记录操作日志
                $logSql = "INSERT INTO sys_log (user_id, action, description, ip, user_agent) 
                           VALUES (?, 'delete_backup', ?, ?, ?)";
                $logStmt = $pdo->prepare($logSql);
                $logStmt->execute([
                    $_SESSION['admin_id'] ?? 0,
                    '删除数据库备份: ' . $filename,
                    $_SERVER['REMOTE_ADDR'] ?? '',
                    $_SERVER['HTTP_USER_AGENT'] ?? ''
                ]);
                
                echo json_encode(['code' => 0, 'msg' => '删除成功']);
            } else {
                echo json_encode(['code' => 1, 'msg' => '删除失败']);
            }
            break;
            
        case 'upload_backup':
            // 上传备份文件
            if (!isset($_FILES['backup_file']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
                echo json_encode(['code' => 1, 'msg' => '请选择要上传的备份文件']);
                break;
            }
            
            $file = $_FILES['backup_file'];
            
            if ($file['type'] !== 'application/octet-stream' && !preg_match('/\.sql$/', $file['name'])) {
                echo json_encode(['code' => 1, 'msg' => '只支持.sql文件']);
                break;
            }
            
            if ($file['size'] > 50 * 1024 * 1024) {
                echo json_encode(['code' => 1, 'msg' => '文件大小不能超过50MB']);
                break;
            }
            
            $filename = 'backup_uploaded_' . date('Ymd_His') . '_' . mt_rand(1000, 9999) . '.sql';
            $filepath = $backupDir . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                // 记录操作日志
                $logSql = "INSERT INTO sys_log (user_id, action, description, ip, user_agent) 
                           VALUES (?, 'upload_backup', ?, ?, ?)";
                $logStmt = $pdo->prepare($logSql);
                $logStmt->execute([
                    $_SESSION['admin_id'] ?? 0,
                    '上传数据库备份: ' . $filename,
                    $_SERVER['REMOTE_ADDR'] ?? '',
                    $_SERVER['HTTP_USER_AGENT'] ?? ''
                ]);
                
                echo json_encode(['code' => 0, 'msg' => '上传成功', 'data' => ['filename' => $filename]]);
            } else {
                echo json_encode(['code' => 1, 'msg' => '上传失败']);
            }
            break;
            
        case 'get_db_info':
            // 获取数据库信息
            $info = [];
            
            // 数据库大小
            $stmt = $pdo->query("
                SELECT 
                    SUM(data_length + index_length) as size,
                    COUNT(*) as table_count
                FROM information_schema.tables 
                WHERE table_schema = '" . DB_NAME . "'
            ");
            $dbInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $info['size'] = round($dbInfo['size'] / 1024 / 1024, 2);
            $info['table_count'] = $dbInfo['table_count'];
            
            // 各表数据
            $stmt = $pdo->query("
                SELECT 
                    table_name as name,
                    table_rows as rows,
                    ROUND((data_length + index_length) / 1024, 2) as size
                FROM information_schema.tables 
                WHERE table_schema = '" . DB_NAME . "'
                ORDER BY (data_length + index_length) DESC
            ");
            $info['tables'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['code' => 0, 'msg' => 'success', 'data' => $info]);
            break;
            
        default:
            echo json_encode(['code' => 1, 'msg' => '未知操作']);
    }
    
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>数据库备份 - 小说网后台</title>
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
            max-width: 1200px;
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
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .content {
            padding: 30px;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 12px;
            text-align: center;
        }
        
        .stat-card h3 {
            font-size: 36px;
            margin-bottom: 8px;
        }
        
        .stat-card p {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .actions {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .table-wrapper {
            overflow-x: auto;
            border: 1px solid #e9ecef;
            border-radius: 8px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }
        
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }
        
        td {
            font-size: 14px;
            color: #212529;
        }
        
        .file-actions {
            display: flex;
            gap: 8px;
        }
        
        .file-actions button {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.2s;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }
        
        .empty-state .icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        
        .db-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .db-info h3 {
            margin-bottom: 15px;
            color: #495057;
        }
        
        .db-info table {
            margin-top: 15px;
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
        
        .loading {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }
        
        .upload-area {
            border: 2px dashed #ced4da;
            border-radius: 8px;
            padding: 40px;
            text-align: center;
            margin-bottom: 30px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .upload-area:hover {
            border-color: #667eea;
            background: #f8f9fa;
        }
        
        .upload-area.dragover {
            border-color: #667eea;
            background: #e7f1ff;
        }
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .actions {
                flex-direction: column;
            }
            
            .stats {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>💾 数据库备份</h1>
            <button class="btn btn-primary" onclick="goBack()">← 返回后台</button>
        </div>
        
        <div class="content">
            <div id="alertBox" class="alert"></div>
            
            <!-- 数据库信息 -->
            <div class="db-info">
                <h3>📊 数据库信息</h3>
                <div id="dbInfoContent">加载中...</div>
            </div>
            
            <!-- 统计卡片 -->
            <div class="stats">
                <div class="stat-card">
                    <h3 id="dbSize">0</h3>
                    <p>数据库大小 (MB)</p>
                </div>
                <div class="stat-card">
                    <h3 id="tableCount">0</h3>
                    <p>数据表数量</p>
                </div>
                <div class="stat-card">
                    <h3 id="backupCount">0</h3>
                    <p>备份文件数</p>
                </div>
                <div class="stat-card">
                    <h3 id="totalBackupSize">0</h3>
                    <p>备份总大小 (KB)</p>
                </div>
            </div>
            
            <!-- 操作按钮 -->
            <div class="actions">
                <button class="btn btn-success" onclick="createBackup()">✨ 创建备份</button>
                <button class="btn btn-info" onclick="showUploadArea()">📤 上传备份</button>
                <button class="btn btn-warning" onclick="refreshList()">🔄 刷新列表</button>
            </div>
            
            <!-- 上传区域 -->
            <div class="upload-area" id="uploadArea" style="display: none;" onclick="document.getElementById('backupFile').click()">
                <input type="file" id="backupFile" accept=".sql" style="display: none;" onchange="uploadBackup(this)">
                <div style="font-size: 48px; margin-bottom: 15px;">📤</div>
                <p style="font-size: 16px; color: #495057; margin-bottom: 10px;">点击或拖拽文件到此处上传</p>
                <p style="font-size: 14px; color: #6c757d;">支持 .sql 文件，最大 50MB</p>
            </div>
            
            <!-- 备份列表 -->
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>文件名</th>
                            <th>大小</th>
                            <th>创建时间</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody id="backupTableBody">
                        <tr>
                            <td colspan="4" class="loading">加载中...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script>
        // 页面加载时获取数据
        document.addEventListener('DOMContentLoaded', function() {
            loadDbInfo();
            loadBackups();
        });
        
        // 加载数据库信息
        function loadDbInfo() {
            const formData = new FormData();
            formData.append('action', 'get_db_info');
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.code === 0) {
                    document.getElementById('dbSize').textContent = data.data.size;
                    document.getElementById('tableCount').textContent = data.data.table_count;
                    
                    // 显示表信息
                    let html = '<table><thead><tr><th>表名</th><th>记录数</th><th>大小 (KB)</th></tr></thead><tbody>';
                    data.data.tables.forEach(table => {
                        html += '<tr>';
                        html += '<td>' + table.name + '</td>';
                        html += '<td>' + table.rows + '</td>';
                        html += '<td>' + table.size + '</td>';
                        html += '</tr>';
                    });
                    html += '</tbody></table>';
                    document.getElementById('dbInfoContent').innerHTML = html;
                }
            });
        }
        
        // 加载备份列表
        function loadBackups() {
            const formData = new FormData();
            formData.append('action', 'list_backups');
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.code === 0) {
                    renderBackupTable(data.data);
                    updateBackupStats(data.data);
                } else {
                    showAlert('加载失败', 'error');
                }
            });
        }
        
        // 渲染备份表格
        function renderBackupTable(backups) {
            const tbody = document.getElementById('backupTableBody');
            
            if (backups.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="empty-state"><div class="icon">📭</div><p>暂无备份文件</p></td></tr>';
                return;
            }
            
            tbody.innerHTML = backups.map(backup => `
                <tr>
                    <td>${backup.filename}</td>
                    <td>${backup.size} KB</td>
                    <td>${backup.time}</td>
                    <td>
                        <div class="file-actions">
                            <button class="btn-info" onclick="downloadBackup('${backup.filename}')">下载</button>
                            <button class="btn-warning" onclick="restoreBackup('${backup.filename}')">恢复</button>
                            <button class="btn-danger" onclick="deleteBackup('${backup.filename}')">删除</button>
                        </div>
                    </td>
                </tr>
            `).join('');
        }
        
        // 更新备份统计
        function updateBackupStats(backups) {
            document.getElementById('backupCount').textContent = backups.length;
            
            const totalSize = backups.reduce((sum, backup) => sum + backup.size, 0);
            document.getElementById('totalBackupSize').textContent = totalSize.toFixed(2);
        }
        
        // 创建备份
        function createBackup() {
            if (!confirm('确定要创建数据库备份吗？')) return;
            
            showAlert('正在创建备份...', 'info');
            
            const formData = new FormData();
            formData.append('action', 'create_backup');
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.code === 0) {
                    showAlert('备份创建成功！文件: ' + data.data.filename, 'success');
                    loadBackups();
                } else {
                    showAlert(data.msg, 'error');
                }
            });
        }
        
        // 下载备份
        function downloadBackup(filename) {
            const formData = new FormData();
            formData.append('action', 'download_backup');
            formData.append('filename', filename);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.blob())
            .then(blob => {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = filename;
                a.click();
                window.URL.revokeObjectURL(url);
            });
        }
        
        // 恢复备份
        function restoreBackup(filename) {
            if (!confirm('警告：恢复备份将覆盖当前数据库！\n\n确定要恢复备份: ' + filename + ' 吗？')) return;
            
            showAlert('正在恢复备份...', 'info');
            
            const formData = new FormData();
            formData.append('action', 'restore_backup');
            formData.append('filename', filename);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.code === 0) {
                    showAlert('数据库恢复成功！', 'success');
                } else {
                    showAlert(data.msg, 'error');
                }
            });
        }
        
        // 删除备份
        function deleteBackup(filename) {
            if (!confirm('确定要删除备份: ' + filename + ' 吗？')) return;
            
            const formData = new FormData();
            formData.append('action', 'delete_backup');
            formData.append('filename', filename);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.code === 0) {
                    showAlert('删除成功', 'success');
                    loadBackups();
                } else {
                    showAlert(data.msg, 'error');
                }
            });
        }
        
        // 显示上传区域
        function showUploadArea() {
            const uploadArea = document.getElementById('uploadArea');
            uploadArea.style.display = uploadArea.style.display === 'none' ? 'block' : 'none';
        }
        
        // 上传备份
        function uploadBackup(input) {
            if (!input.files || !input.files[0]) return;
            
            const formData = new FormData();
            formData.append('action', 'upload_backup');
            formData.append('backup_file', input.files[0]);
            
            showAlert('正在上传...', 'info');
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.code === 0) {
                    showAlert('上传成功', 'success');
                    loadBackups();
                    document.getElementById('uploadArea').style.display = 'none';
                } else {
                    showAlert(data.msg, 'error');
                }
            });
        }
        
        // 刷新列表
        function refreshList() {
            loadBackups();
            showAlert('列表已刷新', 'success');
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
        
        // 返回后台
        function goBack() {
            window.location.href = 'dashboard.html';
        }
        
        // 拖拽上传
        const uploadArea = document.getElementById('uploadArea');
        
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });
        
        uploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
        });
        
        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                const input = document.getElementById('backupFile');
                input.files = files;
                uploadBackup(input);
            }
        });
    </script>
</body>
</html>
