<?php
/**
 * 用户管理模块
 * 管理前端登录的用户信息
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

// 处理AJAX请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'list':
            // 获取用户列表
            $page = intval($_POST['page'] ?? 1);
            $pageSize = intval($_POST['pageSize'] ?? 20);
            $search = trim($_POST['search'] ?? '');
            $status = $_POST['status'] ?? '';
            $userType = $_POST['userType'] ?? '';
            
            $where = '1=1';
            $params = [];
            
            if ($search) {
                $where .= " AND (username LIKE ? OR nickname LIKE ? OR email LIKE ? OR phone LIKE ?)";
                $searchParam = "%$search%";
                $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
            }
            
            if ($status !== '') {
                $where .= " AND status = ?";
                $params[] = intval($status);
            }
            
            if ($userType !== '') {
                $where .= " AND user_type = ?";
                $params[] = intval($userType);
            }
            
            // 统计总数
            $countSql = "SELECT COUNT(*) FROM user WHERE $where";
            $stmt = $pdo->prepare($countSql);
            $stmt->execute($params);
            $total = $stmt->fetchColumn();
            
            // 分页查询
            $offset = ($page - 1) * $pageSize;
            $sql = "SELECT id, username, nickname, email, phone, avatar, user_type, 
                    vip_level, vip_expire_time, status, create_time, update_time
                    FROM user WHERE $where ORDER BY create_time DESC LIMIT $offset, $pageSize";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // 获取每个用户的阅读记录数
            foreach ($users as &$user) {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_read_history WHERE user_id = ?");
                $stmt->execute([$user['id']]);
                $user['read_count'] = $stmt->fetchColumn();
                
                // 获取最后阅读时间
                $stmt = $pdo->prepare("SELECT MAX(last_read_time) FROM user_read_history WHERE user_id = ?");
                $stmt->execute([$user['id']]);
                $user['last_read_time'] = $stmt->fetchColumn();
                
                // 用户类型文本
                $user['user_type_text'] = ['普通用户', '作者', '管理员'][$user['user_type']] ?? '未知';
                
                // VIP状态
                if ($user['vip_level'] > 0 && $user['vip_expire_time']) {
                    $expireTime = strtotime($user['vip_expire_time']);
                    $user['vip_status'] = $expireTime > time() ? '有效' : '已过期';
                } else {
                    $user['vip_status'] = '普通用户';
                }
            }
            
            echo json_encode([
                'code' => 0,
                'msg' => 'success',
                'data' => [
                    'list' => $users,
                    'total' => $total,
                    'page' => $page,
                    'pageSize' => $pageSize
                ]
            ]);
            break;
            
        case 'detail':
            // 获取用户详情
            $id = intval($_POST['id'] ?? 0);
            
            $stmt = $pdo->prepare("SELECT * FROM user WHERE id = ?");
            $stmt->execute([$id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                echo json_encode(['code' => 1, 'msg' => '用户不存在']);
                break;
            }
            
            // 移除密码字段
            unset($user['password']);
            
            // 获取用户阅读记录
            $stmt = $pdo->prepare("
                SELECT rh.*, n.title as novel_title 
                FROM user_read_history rh
                LEFT JOIN novel n ON rh.novel_id = n.id
                WHERE rh.user_id = ?
                ORDER BY rh.last_read_time DESC
                LIMIT 10
            ");
            $stmt->execute([$id]);
            $user['read_history'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // 获取用户收藏
            $stmt = $pdo->prepare("
                SELECT uf.*, n.title as novel_title 
                FROM user_favorite uf
                LEFT JOIN novel n ON uf.novel_id = n.id
                WHERE uf.user_id = ?
                ORDER BY uf.create_time DESC
                LIMIT 10
            ");
            $stmt->execute([$id]);
            $user['favorites'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['code' => 0, 'msg' => 'success', 'data' => $user]);
            break;
            
        case 'update':
            // 更新用户信息
            $id = intval($_POST['id'] ?? 0);
            $nickname = trim($_POST['nickname'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $userType = intval($_POST['user_type'] ?? 1);
            $vipLevel = intval($_POST['vip_level'] ?? 0);
            $vipExpireTime = $_POST['vip_expire_time'] ?? null;
            $status = intval($_POST['status'] ?? 1);
            
            if (!$id) {
                echo json_encode(['code' => 1, 'msg' => '用户ID不能为空']);
                break;
            }
            
            // 检查邮箱是否已被使用
            if ($email) {
                $stmt = $pdo->prepare("SELECT id FROM user WHERE email = ? AND id != ?");
                $stmt->execute([$email, $id]);
                if ($stmt->fetch()) {
                    echo json_encode(['code' => 1, 'msg' => '邮箱已被其他用户使用']);
                    break;
                }
            }
            
            // 检查手机号是否已被使用
            if ($phone) {
                $stmt = $pdo->prepare("SELECT id FROM user WHERE phone = ? AND id != ?");
                $stmt->execute([$phone, $id]);
                if ($stmt->fetch()) {
                    echo json_encode(['code' => 1, 'msg' => '手机号已被其他用户使用']);
                    break;
                }
            }
            
            $sql = "UPDATE user SET nickname = ?, email = ?, phone = ?, user_type = ?, 
                    vip_level = ?, vip_expire_time = ?, status = ?, update_time = NOW() 
                    WHERE id = ?";
            
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([
                $nickname ?: null,
                $email ?: null,
                $phone ?: null,
                $userType,
                $vipLevel,
                $vipExpireTime ?: null,
                $status,
                $id
            ]);
            
            if ($result) {
                // 记录操作日志
                $logSql = "INSERT INTO sys_log (user_id, action, description, ip, user_agent) 
                           VALUES (?, 'update_user', ?, ?, ?)";
                $logStmt = $pdo->prepare($logSql);
                $logStmt->execute([
                    $_SESSION['admin_id'] ?? 0,
                    "更新用户ID: $id 的信息",
                    $_SERVER['REMOTE_ADDR'] ?? '',
                    $_SERVER['HTTP_USER_AGENT'] ?? ''
                ]);
                
                echo json_encode(['code' => 0, 'msg' => '更新成功']);
            } else {
                echo json_encode(['code' => 1, 'msg' => '更新失败']);
            }
            break;
            
        case 'reset_password':
            // 重置密码
            $id = intval($_POST['id'] ?? 0);
            $newPassword = $_POST['new_password'] ?? '';
            
            if (!$id) {
                echo json_encode(['code' => 1, 'msg' => '用户ID不能为空']);
                break;
            }
            
            if (strlen($newPassword) < 6) {
                echo json_encode(['code' => 1, 'msg' => '密码长度至少6位']);
                break;
            }
            
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("UPDATE user SET password = ?, update_time = NOW() WHERE id = ?");
            $result = $stmt->execute([$passwordHash, $id]);
            
            if ($result) {
                // 记录操作日志
                $logSql = "INSERT INTO sys_log (user_id, action, description, ip, user_agent) 
                           VALUES (?, 'reset_password', ?, ?, ?)";
                $logStmt = $pdo->prepare($logSql);
                $logStmt->execute([
                    $_SESSION['admin_id'] ?? 0,
                    "重置用户ID: $id 的密码",
                    $_SERVER['REMOTE_ADDR'] ?? '',
                    $_SERVER['HTTP_USER_AGENT'] ?? ''
                ]);
                
                echo json_encode(['code' => 0, 'msg' => '密码重置成功']);
            } else {
                echo json_encode(['code' => 1, 'msg' => '密码重置失败']);
            }
            break;
            
        case 'update_status':
            // 更新用户状态
            $id = intval($_POST['id'] ?? 0);
            $status = intval($_POST['status'] ?? 0);
            
            if (!$id) {
                echo json_encode(['code' => 1, 'msg' => '用户ID不能为空']);
                break;
            }
            
            $stmt = $pdo->prepare("UPDATE user SET status = ?, update_time = NOW() WHERE id = ?");
            $result = $stmt->execute([$status, $id]);
            
            if ($result) {
                // 记录操作日志
                $logSql = "INSERT INTO sys_log (user_id, action, description, ip, user_agent) 
                           VALUES (?, 'update_user_status', ?, ?, ?)";
                $logStmt = $pdo->prepare($logSql);
                $logStmt->execute([
                    $_SESSION['admin_id'] ?? 0,
                    ($status == 1 ? '启用' : '禁用') . "用户ID: $id",
                    $_SERVER['REMOTE_ADDR'] ?? '',
                    $_SERVER['HTTP_USER_AGENT'] ?? ''
                ]);
                
                echo json_encode(['code' => 0, 'msg' => '状态更新成功']);
            } else {
                echo json_encode(['code' => 1, 'msg' => '状态更新失败']);
            }
            break;
            
        case 'delete':
            // 删除用户
            $id = intval($_POST['id'] ?? 0);
            
            if (!$id) {
                echo json_encode(['code' => 1, 'msg' => '用户ID不能为空']);
                break;
            }
            
            // 开启事务
            $pdo->beginTransaction();
            
            try {
                // 删除用户相关数据
                $pdo->prepare("DELETE FROM user_read_history WHERE user_id = ?")->execute([$id]);
                $pdo->prepare("DELETE FROM user_favorite WHERE user_id = ?")->execute([$id]);
                
                // 删除用户
                $stmt = $pdo->prepare("DELETE FROM user WHERE id = ?");
                $result = $stmt->execute([$id]);
                
                // 记录操作日志
                $logSql = "INSERT INTO sys_log (user_id, action, description, ip, user_agent) 
                           VALUES (?, 'delete_user', ?, ?, ?)";
                $logStmt = $pdo->prepare($logSql);
                $logStmt->execute([
                    $_SESSION['admin_id'] ?? 0,
                    "删除用户ID: $id",
                    $_SERVER['REMOTE_ADDR'] ?? '',
                    $_SERVER['HTTP_USER_AGENT'] ?? ''
                ]);
                
                $pdo->commit();
                echo json_encode(['code' => 0, 'msg' => '删除成功']);
            } catch (Exception $e) {
                $pdo->rollBack();
                echo json_encode(['code' => 1, 'msg' => '删除失败: ' . $e->getMessage()]);
            }
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
    <title>用户管理 - 小说网后台</title>
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
            max-width: 1400px;
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
        
        .header-actions {
            display: flex;
            gap: 12px;
        }
        
        .btn {
            padding: 10px 20px;
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
        
        .btn-secondary:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .filters {
            padding: 20px 30px;
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .filter-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .filter-item label {
            font-size: 14px;
            color: #495057;
            font-weight: 500;
        }
        
        .filter-item input,
        .filter-item select {
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-size: 14px;
            min-width: 150px;
        }
        
        .content {
            padding: 30px;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
        }
        
        .stat-card h3 {
            font-size: 32px;
            margin-bottom: 8px;
        }
        
        .stat-card p {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .table-wrapper {
            overflow-x: auto;
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
            font-size: 14px;
        }
        
        td {
            font-size: 14px;
            color: #212529;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 16px;
        }
        
        .avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }
        
        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .badge-vip {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }
        
        .actions {
            display: flex;
            gap: 8px;
        }
        
        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.2s;
        }
        
        .action-btn-view {
            background: #17a2b8;
            color: white;
        }
        
        .action-btn-edit {
            background: #ffc107;
            color: #212529;
        }
        
        .action-btn-reset {
            background: #6c757d;
            color: white;
        }
        
        .action-btn-delete {
            background: #dc3545;
            color: white;
        }
        
        .action-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }
        
        .pagination button {
            padding: 8px 16px;
            border: 1px solid #dee2e6;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .pagination button:hover:not(:disabled) {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .pagination button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .pagination span {
            font-size: 14px;
            color: #6c757d;
        }
        
        /* 模态框 */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 16px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            padding: 20px 30px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h2 {
            font-size: 20px;
            color: #212529;
        }
        
        .modal-close {
            width: 32px;
            height: 32px;
            border: none;
            background: #f8f9fa;
            border-radius: 50%;
            cursor: pointer;
            font-size: 20px;
            color: #6c757d;
        }
        
        .modal-body {
            padding: 30px;
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
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .modal-footer {
            padding: 20px 30px;
            border-top: 1px solid #e9ecef;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        /* 用户详情 */
        .user-detail-section {
            margin-bottom: 25px;
        }
        
        .user-detail-section h3 {
            font-size: 16px;
            color: #495057;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }
        
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        
        .detail-item {
            padding: 12px;
            background: #f8f9fa;
            border-radius: 6px;
        }
        
        .detail-item label {
            font-size: 12px;
            color: #6c757d;
            display: block;
            margin-bottom: 4px;
        }
        
        .detail-item p {
            font-size: 14px;
            color: #212529;
            font-weight: 500;
        }
        
        .loading {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .filters {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-item {
                width: 100%;
            }
            
            .filter-item input,
            .filter-item select {
                flex: 1;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .detail-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>👥 用户管理</h1>
            <div class="header-actions">
                <button class="btn btn-secondary" onclick="exportUsers()">📥 导出用户</button>
                <button class="btn btn-primary" onclick="goBack()">← 返回后台</button>
            </div>
        </div>
        
        <div class="filters">
            <div class="filter-item">
                <label>搜索:</label>
                <input type="text" id="searchInput" placeholder="用户名/昵称/邮箱/手机号">
            </div>
            <div class="filter-item">
                <label>状态:</label>
                <select id="statusFilter">
                    <option value="">全部</option>
                    <option value="1">正常</option>
                    <option value="0">禁用</option>
                </select>
            </div>
            <div class="filter-item">
                <label>用户类型:</label>
                <select id="userTypeFilter">
                    <option value="">全部</option>
                    <option value="1">普通用户</option>
                    <option value="2">作者</option>
                    <option value="3">管理员</option>
                </select>
            </div>
            <button class="btn btn-primary" onclick="searchUsers()">🔍 搜索</button>
            <button class="btn btn-secondary" onclick="resetFilters()">重置</button>
        </div>
        
        <div class="content">
            <div class="stats">
                <div class="stat-card">
                    <h3 id="totalUsers">0</h3>
                    <p>总用户数</p>
                </div>
                <div class="stat-card">
                    <h3 id="activeUsers">0</h3>
                    <p>活跃用户</p>
                </div>
                <div class="stat-card">
                    <h3 id="vipUsers">0</h3>
                    <p>VIP用户</p>
                </div>
                <div class="stat-card">
                    <h3 id="authorUsers">0</h3>
                    <p>作者用户</p>
                </div>
            </div>
            
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>用户信息</th>
                            <th>邮箱</th>
                            <th>手机号</th>
                            <th>用户类型</th>
                            <th>VIP</th>
                            <th>状态</th>
                            <th>注册时间</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody id="userTableBody">
                        <tr>
                            <td colspan="8" class="loading">加载中...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="pagination">
                <button onclick="prevPage()" id="prevBtn">上一页</button>
                <span id="pageInfo">第 1 页 / 共 1 页</span>
                <button onclick="nextPage()" id="nextBtn">下一页</button>
            </div>
        </div>
    </div>
    
    <!-- 用户详情模态框 -->
    <div class="modal" id="detailModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>👤 用户详情</h2>
                <button class="modal-close" onclick="closeDetailModal()">&times;</button>
            </div>
            <div class="modal-body" id="detailContent">
                <!-- 动态加载 -->
            </div>
        </div>
    </div>
    
    <!-- 编辑用户模态框 -->
    <div class="modal" id="editModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>✏️ 编辑用户</h2>
                <button class="modal-close" onclick="closeEditModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editForm">
                    <input type="hidden" id="editId">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>用户名 (不可修改)</label>
                            <input type="text" id="editUsername" disabled>
                        </div>
                        <div class="form-group">
                            <label>昵称</label>
                            <input type="text" id="editNickname">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>邮箱</label>
                            <input type="email" id="editEmail">
                        </div>
                        <div class="form-group">
                            <label>手机号</label>
                            <input type="text" id="editPhone">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>用户类型</label>
                            <select id="editUserType">
                                <option value="1">普通用户</option>
                                <option value="2">作者</option>
                                <option value="3">管理员</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>状态</label>
                            <select id="editStatus">
                                <option value="1">正常</option>
                                <option value="0">禁用</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>VIP等级</label>
                            <select id="editVipLevel">
                                <option value="0">普通用户</option>
                                <option value="1">VIP1</option>
                                <option value="2">VIP2</option>
                                <option value="3">VIP3</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>VIP过期时间</label>
                            <input type="datetime-local" id="editVipExpireTime">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeEditModal()">取消</button>
                <button class="btn btn-primary" onclick="saveEdit()">保存</button>
            </div>
        </div>
    </div>
    
    <!-- 重置密码模态框 -->
    <div class="modal" id="resetModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>🔐 重置密码</h2>
                <button class="modal-close" onclick="closeResetModal()">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="resetUserId">
                <div class="form-group">
                    <label>新密码 (至少6位)</label>
                    <input type="password" id="newPassword" placeholder="请输入新密码">
                </div>
                <div class="form-group">
                    <label>确认密码</label>
                    <input type="password" id="confirmPassword" placeholder="请再次输入密码">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeResetModal()">取消</button>
                <button class="btn btn-primary" onclick="confirmReset()">确认重置</button>
            </div>
        </div>
    </div>
    
    <script>
        // 全局变量
        let currentPage = 1;
        let pageSize = 20;
        let totalPages = 1;
        
        // 页面加载时获取数据
        document.addEventListener('DOMContentLoaded', function() {
            loadUsers();
            loadStats();
        });
        
        // 加载用户列表
        function loadUsers() {
            const search = document.getElementById('searchInput').value;
            const status = document.getElementById('statusFilter').value;
            const userType = document.getElementById('userTypeFilter').value;
            
            const formData = new FormData();
            formData.append('action', 'list');
            formData.append('page', currentPage);
            formData.append('pageSize', pageSize);
            if (search) formData.append('search', search);
            if (status) formData.append('status', status);
            if (userType) formData.append('userType', userType);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.code === 0) {
                    renderUserTable(data.data.list);
                    totalPages = Math.ceil(data.data.total / pageSize);
                    updatePagination();
                } else {
                    alert('加载失败: ' + data.msg);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('网络错误，请重试');
            });
        }
        
        // 加载统计数据
        function loadStats() {
            // 从用户列表中统计
            fetch('', {
                method: 'POST',
                body: new FormData().append('action', 'list') || createStatsFormData()
            })
            .then(response => response.json())
            .then(data => {
                if (data.code === 0) {
                    document.getElementById('totalUsers').textContent = data.data.total;
                }
            });
        }
        
        function createStatsFormData() {
            const fd = new FormData();
            fd.append('action', 'list');
            fd.append('pageSize', '1000');
            return fd;
        }
        
        // 渲染用户表格
        function renderUserTable(users) {
            const tbody = document.getElementById('userTableBody');
            
            if (users.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="loading">暂无数据</td></tr>';
                return;
            }
            
            tbody.innerHTML = users.map(user => `
                <tr>
                    <td>
                        <div class="user-info">
                            <div class="avatar">
                                ${user.avatar ? `<img src="${user.avatar}" alt="">` : user.username.charAt(0).toUpperCase()}
                            </div>
                            <div>
                                <div style="font-weight: 500;">${user.username}</div>
                                <div style="font-size: 12px; color: #6c757d;">${user.nickname || '未设置昵称'}</div>
                            </div>
                        </div>
                    </td>
                    <td>${user.email || '-'}</td>
                    <td>${user.phone || '-'}</td>
                    <td><span class="badge badge-info">${user.user_type_text}</span></td>
                    <td>
                        ${user.vip_level > 0 
                            ? `<span class="badge badge-vip">VIP${user.vip_level}</span>`
                            : '<span class="badge badge-secondary">普通</span>'
                        }
                    </td>
                    <td>
                        <span class="badge ${user.status == 1 ? 'badge-success' : 'badge-danger'}">
                            ${user.status == 1 ? '正常' : '禁用'}
                        </span>
                    </td>
                    <td>${user.create_time || '-'}</td>
                    <td>
                        <div class="actions">
                            <button class="action-btn action-btn-view" onclick="viewUser(${user.id})">查看</button>
                            <button class="action-btn action-btn-edit" onclick="editUser(${user.id})">编辑</button>
                            <button class="action-btn action-btn-reset" onclick="resetPassword(${user.id})">重置密码</button>
                            <button class="action-btn action-btn-delete" onclick="deleteUser(${user.id})">删除</button>
                        </div>
                    </td>
                </tr>
            `).join('');
        }
        
        // 更新分页
        function updatePagination() {
            document.getElementById('pageInfo').textContent = `第 ${currentPage} 页 / 共 ${totalPages} 页`;
            document.getElementById('prevBtn').disabled = currentPage <= 1;
            document.getElementById('nextBtn').disabled = currentPage >= totalPages;
        }
        
        // 分页操作
        function prevPage() {
            if (currentPage > 1) {
                currentPage--;
                loadUsers();
            }
        }
        
        function nextPage() {
            if (currentPage < totalPages) {
                currentPage++;
                loadUsers();
            }
        }
        
        // 搜索
        function searchUsers() {
            currentPage = 1;
            loadUsers();
        }
        
        // 重置筛选
        function resetFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('statusFilter').value = '';
            document.getElementById('userTypeFilter').value = '';
            currentPage = 1;
            loadUsers();
        }
        
        // 查看用户详情
        function viewUser(id) {
            const formData = new FormData();
            formData.append('action', 'detail');
            formData.append('id', id);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.code === 0) {
                    renderUserDetail(data.data);
                    document.getElementById('detailModal').classList.add('active');
                } else {
                    alert('加载失败: ' + data.msg);
                }
            });
        }
        
        // 渲染用户详情
        function renderUserDetail(user) {
            const content = `
                <div class="user-detail-section">
                    <h3>基本信息</h3>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <label>用户ID</label>
                            <p>${user.id}</p>
                        </div>
                        <div class="detail-item">
                            <label>用户名</label>
                            <p>${user.username}</p>
                        </div>
                        <div class="detail-item">
                            <label>昵称</label>
                            <p>${user.nickname || '未设置'}</p>
                        </div>
                        <div class="detail-item">
                            <label>邮箱</label>
                            <p>${user.email || '未设置'}</p>
                        </div>
                        <div class="detail-item">
                            <label>手机号</label>
                            <p>${user.phone || '未设置'}</p>
                        </div>
                        <div class="detail-item">
                            <label>用户类型</label>
                            <p>${['普通用户', '作者', '管理员'][user.user_type] || '未知'}</p>
                        </div>
                        <div class="detail-item">
                            <label>VIP等级</label>
                            <p>${user.vip_level > 0 ? 'VIP' + user.vip_level : '普通用户'}</p>
                        </div>
                        <div class="detail-item">
                            <label>VIP过期时间</label>
                            <p>${user.vip_expire_time || '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label>状态</label>
                            <p>${user.status == 1 ? '正常' : '禁用'}</p>
                        </div>
                        <div class="detail-item">
                            <label>注册时间</label>
                            <p>${user.create_time || '-'}</p>
                        </div>
                    </div>
                </div>
                
                <div class="user-detail-section">
                    <h3>阅读记录 (最近10条)</h3>
                    ${user.read_history && user.read_history.length > 0 
                        ? user.read_history.map(h => `
                            <div class="detail-item" style="margin-bottom: 10px;">
                                <label>${h.novel_title || '未知小说'}</label>
                                <p>最后阅读: ${h.last_read_time} | 进度: ${h.read_progress}%</p>
                            </div>
                        `).join('')
                        : '<p style="color: #6c757d;">暂无阅读记录</p>'
                    }
                </div>
                
                <div class="user-detail-section">
                    <h3>收藏记录 (最近10条)</h3>
                    ${user.favorites && user.favorites.length > 0 
                        ? user.favorites.map(f => `
                            <div class="detail-item" style="margin-bottom: 10px;">
                                <label>${f.novel_title || '未知小说'}</label>
                                <p>收藏时间: ${f.create_time || '-'}</p>
                            </div>
                        `).join('')
                        : '<p style="color: #6c757d;">暂无收藏记录</p>'
                    }
                </div>
            `;
            
            document.getElementById('detailContent').innerHTML = content;
        }
        
        // 关闭详情模态框
        function closeDetailModal() {
            document.getElementById('detailModal').classList.remove('active');
        }
        
        // 编辑用户
        function editUser(id) {
            const formData = new FormData();
            formData.append('action', 'detail');
            formData.append('id', id);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.code === 0) {
                    const user = data.data;
                    document.getElementById('editId').value = user.id;
                    document.getElementById('editUsername').value = user.username;
                    document.getElementById('editNickname').value = user.nickname || '';
                    document.getElementById('editEmail').value = user.email || '';
                    document.getElementById('editPhone').value = user.phone || '';
                    document.getElementById('editUserType').value = user.user_type;
                    document.getElementById('editStatus').value = user.status;
                    document.getElementById('editVipLevel').value = user.vip_level;
                    document.getElementById('editVipExpireTime').value = user.vip_expire_time || '';
                    
                    document.getElementById('editModal').classList.add('active');
                } else {
                    alert('加载失败: ' + data.msg);
                }
            });
        }
        
        // 关闭编辑模态框
        function closeEditModal() {
            document.getElementById('editModal').classList.remove('active');
        }
        
        // 保存编辑
        function saveEdit() {
            const formData = new FormData();
            formData.append('action', 'update');
            formData.append('id', document.getElementById('editId').value);
            formData.append('nickname', document.getElementById('editNickname').value);
            formData.append('email', document.getElementById('editEmail').value);
            formData.append('phone', document.getElementById('editPhone').value);
            formData.append('user_type', document.getElementById('editUserType').value);
            formData.append('status', document.getElementById('editStatus').value);
            formData.append('vip_level', document.getElementById('editVipLevel').value);
            formData.append('vip_expire_time', document.getElementById('editVipExpireTime').value);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.code === 0) {
                    alert('保存成功');
                    closeEditModal();
                    loadUsers();
                } else {
                    alert('保存失败: ' + data.msg);
                }
            });
        }
        
        // 重置密码
        function resetPassword(id) {
            document.getElementById('resetUserId').value = id;
            document.getElementById('newPassword').value = '';
            document.getElementById('confirmPassword').value = '';
            document.getElementById('resetModal').classList.add('active');
        }
        
        // 关闭重置密码模态框
        function closeResetModal() {
            document.getElementById('resetModal').classList.remove('active');
        }
        
        // 确认重置密码
        function confirmReset() {
            const newPass = document.getElementById('newPassword').value;
            const confirmPass = document.getElementById('confirmPassword').value;
            
            if (!newPass || newPass.length < 6) {
                alert('密码长度至少6位');
                return;
            }
            
            if (newPass !== confirmPass) {
                alert('两次输入的密码不一致');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'reset_password');
            formData.append('id', document.getElementById('resetUserId').value);
            formData.append('new_password', newPass);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.code === 0) {
                    alert('密码重置成功');
                    closeResetModal();
                } else {
                    alert('重置失败: ' + data.msg);
                }
            });
        }
        
        // 删除用户
        function deleteUser(id) {
            if (!confirm('确定要删除该用户吗？此操作不可恢复！')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', id);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.code === 0) {
                    alert('删除成功');
                    loadUsers();
                } else {
                    alert('删除失败: ' + data.msg);
                }
            });
        }
        
        // 导出用户
        function exportUsers() {
            alert('导出功能开发中...');
        }
        
        // 返回后台
        function goBack() {
            window.location.href = 'dashboard.html';
        }
        
        // 回车搜索
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchUsers();
            }
        });
    </script>
</body>
</html>
