<?php
/**
 * 内容管理 - 小说管理
 */

session_start();

// 检查是否已登录
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

// 数据库配置
$configFile = '../backend/config/database.php';
if (!file_exists($configFile)) {
    die('数据库配置文件不存在');
}

$config = require $configFile;

// 连接数据库
try {
    $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset=utf8mb4";
    $pdo = new PDO($dsn, $config['username'], $config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('数据库连接失败: ' . $e->getMessage());
}

// 处理AJAX请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $action = $_POST['action'];
    
    switch ($action) {
        case 'list':
            getNovelList($pdo);
            break;
        case 'add':
            addNovel($pdo);
            break;
        case 'edit':
            editNovel($pdo);
            break;
        case 'delete':
            deleteNovel($pdo);
            break;
        case 'get':
            getNovel($pdo);
            break;
        default:
            echo json_encode(['success' => false, 'message' => '未知操作']);
    }
    exit;
}

// 获取小说列表
function getNovelList($pdo) {
    try {
        $page = intval($_POST['page'] ?? 1);
        $pageSize = intval($_POST['pageSize'] ?? 20);
        $keyword = $_POST['keyword'] ?? '';
        $category = $_POST['category'] ?? '';
        $status = $_POST['status'] ?? '';
        
        $offset = ($page - 1) * $pageSize;
        
        // 构建查询条件
        $where = '1=1';
        $params = [];
        
        if (!empty($keyword)) {
            $where .= ' AND (n.title LIKE ? OR n.author LIKE ?)';
            $params[] = "%$keyword%";
            $params[] = "%$keyword%";
        }
        
        if (!empty($category)) {
            $where .= ' AND n.category_id = ?';
            $params[] = $category;
        }
        
        if ($status !== '') {
            $where .= ' AND n.status = ?';
            $params[] = $status;
        }
        
        // 查询总数
        $sql = "SELECT COUNT(*) FROM novel n WHERE $where";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $total = $stmt->fetchColumn();
        
        // 查询数据
        $sql = "SELECT n.*, c.name as category_name 
                FROM novel n 
                LEFT JOIN novel_category c ON n.category_id = c.id 
                WHERE $where 
                ORDER BY n.create_time DESC 
                LIMIT $offset, $pageSize";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $list = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'list' => $list,
                'total' => intval($total),
                'page' => $page,
                'pageSize' => $pageSize
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// 添加小说
function addNovel($pdo) {
    try {
        $title = trim($_POST['title'] ?? '');
        $author = trim($_POST['author'] ?? '');
        $categoryId = intval($_POST['category_id'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $cover = trim($_POST['cover'] ?? '');
        $status = intval($_POST['status'] ?? 1);
        
        if (empty($title) || empty($author) || $categoryId <= 0) {
            echo json_encode(['success' => false, 'message' => '请填写必填项']);
            return;
        }
        
        $sql = "INSERT INTO novel (title, author, category_id, description, cover, status, create_time, update_time) 
                VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$title, $author, $categoryId, $description, $cover, $status]);
        
        $novelId = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'message' => '添加成功',
            'data' => ['novel_id' => $novelId]
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// 编辑小说
function editNovel($pdo) {
    try {
        $id = intval($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $author = trim($_POST['author'] ?? '');
        $categoryId = intval($_POST['category_id'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $cover = trim($_POST['cover'] ?? '');
        $status = intval($_POST['status'] ?? 1);
        
        if ($id <= 0 || empty($title) || empty($author) || $categoryId <= 0) {
            echo json_encode(['success' => false, 'message' => '参数错误']);
            return;
        }
        
        $sql = "UPDATE novel SET title=?, author=?, category_id=?, description=?, cover=?, status=?, update_time=NOW() WHERE id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$title, $author, $categoryId, $description, $cover, $status, $id]);
        
        echo json_encode(['success' => true, 'message' => '更新成功']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// 删除小说
function deleteNovel($pdo) {
    try {
        $id = intval($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => '参数错误']);
            return;
        }
        
        // 删除小说
        $stmt = $pdo->prepare("DELETE FROM novel WHERE id = ?");
        $stmt->execute([$id]);
        
        // 删除相关章节
        $stmt = $pdo->prepare("DELETE FROM novel_chapter WHERE novel_id = ?");
        $stmt->execute([$id]);
        
        echo json_encode(['success' => true, 'message' => '删除成功']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// 获取单个小说详情
function getNovel($pdo) {
    try {
        $id = intval($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => '参数错误']);
            return;
        }
        
        $stmt = $pdo->prepare("SELECT * FROM novel WHERE id = ?");
        $stmt->execute([$id]);
        $novel = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($novel) {
            echo json_encode(['success' => true, 'data' => $novel]);
        } else {
            echo json_encode(['success' => false, 'message' => '小说不存在']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// 获取分类列表（用于下拉选择）
$stmt = $pdo->query("SELECT id, name FROM novel_category ORDER BY sort_order");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>内容管理 - 小说网管理系统</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Microsoft YaHei', sans-serif; background: #f5f7fa; color: #333; }
        
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0 20px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }
        
        .navbar-brand { font-size: 22px; font-weight: bold; }
        
        .container {
            max-width: 1400px;
            margin: 80px auto 30px;
            padding: 0 20px;
        }
        
        .page-header {
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .page-title {
            font-size: 28px;
            font-weight: bold;
            color: #333;
        }
        
        .content-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        
        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .search-box {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .search-input {
            padding: 8px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 6px;
            font-size: 14px;
            min-width: 200px;
        }
        
        .search-input:focus {
            border-color: #667eea;
            outline: none;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 6px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover { opacity: 0.9; }
        
        .btn-success {
            background: #38a169;
            color: white;
        }
        
        .btn-danger {
            background: #e53e3e;
            color: white;
        }
        
        .btn-secondary {
            background: #e2e8f0;
            color: #333;
        }
        
        .btn-small {
            padding: 6px 12px;
            font-size: 12px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #555;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-1 { background: #c6f6d5; color: #276749; }
        .status-0 { background: #fed7d7; color: #c53030; }
        .status-2 { background: #feebc8; color: #975a16; }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
        }
        
        .pagination button {
            padding: 8px 12px;
            border: 1px solid #e2e8f0;
            background: white;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .pagination button.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.open { display: flex; }
        
        .modal-content {
            background: white;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            padding: 30px;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .modal-title {
            font-size: 22px;
            font-weight: bold;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: #666;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }
        
        .form-input, .form-textarea, .form-select {
            width: 100%;
            padding: 10px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .form-input:focus, .form-textarea:focus, .form-select:focus {
            border-color: #667eea;
            outline: none;
        }
        
        .form-textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success { background: #c6f6d5; color: #276749; border: 1px solid #9ae6b4; }
        .alert-error { background: #fed7d7; color: #c53030; border: 1px solid #feb2b2; }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #667eea;
            text-decoration: none;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .novel-cover {
            width: 60px;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .loading {
            text-align: center;
            padding: 40px;
            color: #888;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-brand">
            <i class="fas fa-book"></i> 小说网系统 - 内容管理
        </div>
        <div>
            <span style="margin-right: 15px;">欢迎，<?= htmlspecialchars($_SESSION['admin_realname'] ?? '管理员') ?></span>
            <a href="index.php" class="btn btn-secondary btn-small">
                <i class="fas fa-arrow-left"></i> 返回后台
            </a>
        </div>
    </nav>
    
    <div class="container">
        <div class="page-header">
            <div>
                <a href="index.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> 返回后台首页
                </a>
                <h1 class="page-title">📚 内容管理</h1>
            </div>
            <button class="btn btn-success" onclick="showAddModal()">
                <i class="fas fa-plus"></i> 添加小说
            </button>
        </div>
        
        <div class="content-card">
            <div class="toolbar">
                <div class="search-box">
                    <input type="text" class="search-input" id="searchKeyword" placeholder="搜索小说标题或作者...">
                    <select class="search-input" id="searchCategory" style="min-width: 150px;">
                        <option value="">所有分类</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select class="search-input" id="searchStatus" style="min-width: 120px;">
                        <option value="">所有状态</option>
                        <option value="1">连载中</option>
                        <option value="2">已完结</option>
                        <option value="0">已下架</option>
                    </select>
                    <button class="btn btn-primary" onclick="searchNovels()">
                        <i class="fas fa-search"></i> 搜索
                    </button>
                </div>
            </div>
            
            <div id="novelList">
                <div class="loading">
                    <i class="fas fa-spinner fa-spin"></i> 加载中...
                </div>
            </div>
            
            <div id="pagination" class="pagination"></div>
        </div>
    </div>
    
    <!-- 添加/编辑模态框 -->
    <div class="modal" id="novelModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle">添加小说</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div id="modalAlert"></div>
            <form id="novelForm">
                <input type="hidden" id="novelId" name="id">
                <input type="hidden" name="action" id="formAction" value="add">
                
                <div class="form-group">
                    <label class="form-label">小说标题 *</label>
                    <input type="text" class="form-input" name="title" id="novelTitle" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">作者 *</label>
                    <input type="text" class="form-input" name="author" id="novelAuthor" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">分类 *</label>
                    <select class="form-select" name="category_id" id="novelCategory" required>
                        <option value="">请选择分类</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">封面图片</label>
                    <input type="text" class="form-input" name="cover" id="novelCover" placeholder="封面图片URL">
                </div>
                
                <div class="form-group">
                    <label class="form-label">简介</label>
                    <textarea class="form-textarea" name="description" id="novelDescription" rows="4"></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">状态</label>
                    <select class="form-select" name="status" id="novelStatus">
                        <option value="1">连载中</option>
                        <option value="2">已完结</option>
                        <option value="0">已下架</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">取消</button>
                    <button type="submit" class="btn btn-primary">保存</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        let currentPage = 1;
        const pageSize = 20;
        
        // 页面加载时获取列表
        document.addEventListener('DOMContentLoaded', function() {
            loadNovelList();
        });
        
        // 加载小说列表
        function loadNovelList(page = 1) {
            currentPage = page;
            const keyword = document.getElementById('searchKeyword').value;
            const category = document.getElementById('searchCategory').value;
            const status = document.getElementById('searchStatus').value;
            
            const formData = new FormData();
            formData.append('action', 'list');
            formData.append('page', page);
            formData.append('pageSize', pageSize);
            formData.append('keyword', keyword);
            formData.append('category', category);
            formData.append('status', status);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderNovelList(data.data.list);
                    renderPagination(data.data.total, data.data.page);
                } else {
                    document.getElementById('novelList').innerHTML = 
                        '<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> ' + data.message + '</div>';
                }
            })
            .catch(error => {
                document.getElementById('novelList').innerHTML = 
                    '<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> 加载失败：' + error + '</div>';
            });
        }
        
        // 渲染小说列表
        function renderNovelList(list) {
            if (list.length === 0) {
                document.getElementById('novelList').innerHTML = '<div class="loading">暂无数据</div>';
                return;
            }
            
            let html = '<table><thead><tr>';
            html += '<th width="60">ID</th>';
            html += '<th width="80">封面</th>';
            html += '<th>标题</th>';
            html += '<th>作者</th>';
            html += '<th>分类</th>';
            html += '<th>状态</th>';
            html += '<th>创建时间</th>';
            html += '<th width="150">操作</th>';
            html += '</tr></thead><tbody>';
            
            list.forEach(item => {
                const statusClass = item.status == 1 ? 'status-1' : (item.status == 2 ? 'status-2' : 'status-0');
                const statusText = item.status == 1 ? '连载中' : (item.status == 2 ? '已完结' : '已下架');
                
                html += '<tr>';
                html += '<td>' + item.id + '</td>';
                html += '<td>' + (item.cover ? '<img src="' + item.cover + '" class="novel-cover">' : '-') + '</td>';
                html += '<td><strong>' + escapeHtml(item.title) + '</strong></td>';
                html += '<td>' + escapeHtml(item.author) + '</td>';
                html += '<td>' + escapeHtml(item.category_name || '-') + '</td>';
                html += '<td><span class="status-badge ' + statusClass + '">' + statusText + '</span></td>';
                html += '<td>' + item.create_time + '</td>';
                html += '<td class="action-buttons">';
                html += '<button class="btn btn-primary btn-small" onclick="editNovel(' + item.id + ')"><i class="fas fa-edit"></i></button>';
                html += '<button class="btn btn-danger btn-small" onclick="deleteNovel(' + item.id + ')"><i class="fas fa-trash"></i></button>';
                html += '</td>';
                html += '</tr>';
            });
            
            html += '</tbody></table>';
            document.getElementById('novelList').innerHTML = html;
        }
        
        // 渲染分页
        function renderPagination(total, currentPage) {
            const totalPages = Math.ceil(total / pageSize);
            let html = '';
            
            if (totalPages > 1) {
                if (currentPage > 1) {
                    html += '<button onclick="loadNovelList(' + (currentPage - 1) + ')"><i class="fas fa-chevron-left"></i></button>';
                }
                
                for (let i = 1; i <= totalPages; i++) {
                    if (i === currentPage) {
                        html += '<button class="active">' + i + '</button>';
                    } else if (i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
                        html += '<button onclick="loadNovelList(' + i + ')">' + i + '</button>';
                    } else if (i === currentPage - 3 || i === currentPage + 3) {
                        html += '<button disabled>...</button>';
                    }
                }
                
                if (currentPage < totalPages) {
                    html += '<button onclick="loadNovelList(' + (currentPage + 1) + ')"><i class="fas fa-chevron-right"></i></button>';
                }
            }
            
            document.getElementById('pagination').innerHTML = html;
        }
        
        // 搜索
        function searchNovels() {
            loadNovelList(1);
        }
        
        // 显示添加模态框
        function showAddModal() {
            document.getElementById('modalTitle').textContent = '添加小说';
            document.getElementById('formAction').value = 'add';
            document.getElementById('novelForm').reset();
            document.getElementById('modalAlert').innerHTML = '';
            document.getElementById('novelModal').classList.add('open');
        }
        
        // 编辑小说
        function editNovel(id) {
            const formData = new FormData();
            formData.append('action', 'get');
            formData.append('id', id);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const novel = data.data;
                    document.getElementById('modalTitle').textContent = '编辑小说';
                    document.getElementById('formAction').value = 'edit';
                    document.getElementById('novelId').value = novel.id;
                    document.getElementById('novelTitle').value = novel.title;
                    document.getElementById('novelAuthor').value = novel.author;
                    document.getElementById('novelCategory').value = novel.category_id;
                    document.getElementById('novelCover').value = novel.cover || '';
                    document.getElementById('novelDescription').value = novel.description || '';
                    document.getElementById('novelStatus').value = novel.status;
                    document.getElementById('modalAlert').innerHTML = '';
                    document.getElementById('novelModal').classList.add('open');
                } else {
                    alert('获取小说信息失败：' + data.message);
                }
            });
        }
        
        // 删除小说
        function deleteNovel(id) {
            if (!confirm('确定要删除这本小说吗？此操作不可恢复！')) {
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
                if (data.success) {
                    loadNovelList(currentPage);
                } else {
                    alert('删除失败：' + data.message);
                }
            });
        }
        
        // 关闭模态框
        function closeModal() {
            document.getElementById('novelModal').classList.remove('open');
        }
        
        // 表单提交
        document.getElementById('novelForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeModal();
                    loadNovelList(currentPage);
                } else {
                    document.getElementById('modalAlert').innerHTML = 
                        '<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> ' + data.message + '</div>';
                }
            });
        });
        
        // HTML转义
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>