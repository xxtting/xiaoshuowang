<?php
/**
 * 分类管理
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
            getCategoryList($pdo);
            break;
        case 'add':
            addCategory($pdo);
            break;
        case 'edit':
            editCategory($pdo);
            break;
        case 'delete':
            deleteCategory($pdo);
            break;
        case 'get':
            getCategory($pdo);
            break;
        default:
            echo json_encode(['success' => false, 'message' => '未知操作']);
    }
    exit;
}

// 获取分类列表
function getCategoryList($pdo) {
    try {
        // 查询分类及小说数量
        $sql = "SELECT c.*, COUNT(n.id) as novel_count 
                FROM novel_category c 
                LEFT JOIN novel n ON c.id = n.category_id 
                GROUP BY c.id 
                ORDER BY c.sort_order, c.id";
        $stmt = $pdo->query($sql);
        $list = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $list
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// 添加分类
function addCategory($pdo) {
    try {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $sortOrder = intval($_POST['sort_order'] ?? 0);
        
        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => '分类名称不能为空']);
            return;
        }
        
        // 检查名称是否已存在
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM novel_category WHERE name = ?");
        $stmt->execute([$name]);
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'message' => '分类名称已存在']);
            return;
        }
        
        $sql = "INSERT INTO novel_category (name, description, sort_order, create_time) VALUES (?, ?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name, $description, $sortOrder]);
        
        $categoryId = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'message' => '添加成功',
            'data' => ['category_id' => $categoryId]
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// 编辑分类
function editCategory($pdo) {
    try {
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $sortOrder = intval($_POST['sort_order'] ?? 0);
        
        if ($id <= 0 || empty($name)) {
            echo json_encode(['success' => false, 'message' => '参数错误']);
            return;
        }
        
        // 检查名称是否已存在（排除自己）
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM novel_category WHERE name = ? AND id != ?");
        $stmt->execute([$name, $id]);
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'message' => '分类名称已存在']);
            return;
        }
        
        $sql = "UPDATE novel_category SET name=?, description=?, sort_order=? WHERE id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name, $description, $sortOrder, $id]);
        
        echo json_encode(['success' => true, 'message' => '更新成功']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// 删除分类
function deleteCategory($pdo) {
    try {
        $id = intval($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => '参数错误']);
            return;
        }
        
        // 检查是否有小说使用此分类
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM novel WHERE category_id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'message' => '该分类下有小说，无法删除']);
            return;
        }
        
        $stmt = $pdo->prepare("DELETE FROM novel_category WHERE id = ?");
        $stmt->execute([$id]);
        
        echo json_encode(['success' => true, 'message' => '删除成功']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// 获取单个分类详情
function getCategory($pdo) {
    try {
        $id = intval($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => '参数错误']);
            return;
        }
        
        $stmt = $pdo->prepare("SELECT * FROM novel_category WHERE id = ?");
        $stmt->execute([$id]);
        $category = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($category) {
            echo json_encode(['success' => true, 'data' => $category]);
        } else {
            echo json_encode(['success' => false, 'message' => '分类不存在']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>分类管理 - 小说网管理系统</title>
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
            max-width: 1200px;
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
        
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .btn-primary:hover { opacity: 0.9; }
        .btn-success { background: #38a169; color: white; }
        .btn-danger { background: #e53e3e; color: white; }
        .btn-secondary { background: #e2e8f0; color: #333; }
        .btn-small { padding: 6px 12px; font-size: 12px; }
        
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
        
        tr:hover { background: #f8f9fa; }
        
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
            max-width: 500px;
            padding: 30px;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .modal-title { font-size: 22px; font-weight: bold; }
        .modal-close {
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: #666;
        }
        
        .form-group { margin-bottom: 20px; }
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }
        
        .form-input, .form-textarea {
            width: 100%;
            padding: 10px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .form-input:focus, .form-textarea:focus {
            border-color: #667eea;
            outline: none;
        }
        
        .form-textarea { min-height: 80px; resize: vertical; }
        
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
        
        .alert-success { background: #c6f6d5; color: #276749; }
        .alert-error { background: #fed7d7; color: #c53030; }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #667eea;
            text-decoration: none;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .action-buttons { display: flex; gap: 5px; }
        
        .loading { text-align: center; padding: 40px; color: #888; }
        
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            background: #e2e8f0;
            color: #555;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-brand">
            <i class="fas fa-list"></i> 小说网系统 - 分类管理
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
                <h1 class="page-title">📂 分类管理</h1>
            </div>
            <button class="btn btn-success" onclick="showAddModal()">
                <i class="fas fa-plus"></i> 添加分类
            </button>
        </div>
        
        <div class="content-card">
            <div id="categoryList">
                <div class="loading">
                    <i class="fas fa-spinner fa-spin"></i> 加载中...
                </div>
            </div>
        </div>
    </div>
    
    <!-- 添加/编辑模态框 -->
    <div class="modal" id="categoryModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle">添加分类</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div id="modalAlert"></div>
            <form id="categoryForm">
                <input type="hidden" id="categoryId" name="id">
                <input type="hidden" name="action" id="formAction" value="add">
                
                <div class="form-group">
                    <label class="form-label">分类名称 *</label>
                    <input type="text" class="form-input" name="name" id="categoryName" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">分类描述</label>
                    <textarea class="form-textarea" name="description" id="categoryDescription"></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">排序序号</label>
                    <input type="number" class="form-input" name="sort_order" id="categorySortOrder" value="0">
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">取消</button>
                    <button type="submit" class="btn btn-primary">保存</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // 页面加载时获取列表
        document.addEventListener('DOMContentLoaded', function() {
            loadCategoryList();
        });
        
        // 加载分类列表
        function loadCategoryList() {
            const formData = new FormData();
            formData.append('action', 'list');
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderCategoryList(data.data);
                } else {
                    document.getElementById('categoryList').innerHTML = 
                        '<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> ' + data.message + '</div>';
                }
            })
            .catch(error => {
                document.getElementById('categoryList').innerHTML = 
                    '<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> 加载失败：' + error + '</div>';
            });
        }
        
        // 渲染分类列表
        function renderCategoryList(list) {
            if (list.length === 0) {
                document.getElementById('categoryList').innerHTML = '<div class="loading">暂无分类数据</div>';
                return;
            }
            
            let html = '<table><thead><tr>';
            html += '<th width="60">ID</th>';
            html += '<th>分类名称</th>';
            html += '<th>描述</th>';
            html += '<th width="100">小说数量</th>';
            html += '<th width="80">排序</th>';
            html += '<th width="150">创建时间</th>';
            html += '<th width="120">操作</th>';
            html += '</tr></thead><tbody>';
            
            list.forEach(item => {
                html += '<tr>';
                html += '<td>' + item.id + '</td>';
                html += '<td><strong>' + escapeHtml(item.name) + '</strong></td>';
                html += '<td>' + escapeHtml(item.description || '-') + '</td>';
                html += '<td><span class="badge">' + item.novel_count + '</span></td>';
                html += '<td>' + item.sort_order + '</td>';
                html += '<td>' + item.create_time + '</td>';
                html += '<td class="action-buttons">';
                html += '<button class="btn btn-primary btn-small" onclick="editCategory(' + item.id + ')"><i class="fas fa-edit"></i></button>';
                html += '<button class="btn btn-danger btn-small" onclick="deleteCategory(' + item.id + ')"><i class="fas fa-trash"></i></button>';
                html += '</td>';
                html += '</tr>';
            });
            
            html += '</tbody></table>';
            document.getElementById('categoryList').innerHTML = html;
        }
        
        // 显示添加模态框
        function showAddModal() {
            document.getElementById('modalTitle').textContent = '添加分类';
            document.getElementById('formAction').value = 'add';
            document.getElementById('categoryForm').reset();
            document.getElementById('modalAlert').innerHTML = '';
            document.getElementById('categoryModal').classList.add('open');
        }
        
        // 编辑分类
        function editCategory(id) {
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
                    const category = data.data;
                    document.getElementById('modalTitle').textContent = '编辑分类';
                    document.getElementById('formAction').value = 'edit';
                    document.getElementById('categoryId').value = category.id;
                    document.getElementById('categoryName').value = category.name;
                    document.getElementById('categoryDescription').value = category.description || '';
                    document.getElementById('categorySortOrder').value = category.sort_order || 0;
                    document.getElementById('modalAlert').innerHTML = '';
                    document.getElementById('categoryModal').classList.add('open');
                } else {
                    alert('获取分类信息失败：' + data.message);
                }
            });
        }
        
        // 删除分类
        function deleteCategory(id) {
            if (!confirm('确定要删除这个分类吗？')) {
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
                    loadCategoryList();
                } else {
                    alert('删除失败：' + data.message);
                }
            });
        }
        
        // 关闭模态框
        function closeModal() {
            document.getElementById('categoryModal').classList.remove('open');
        }
        
        // 表单提交
        document.getElementById('categoryForm').addEventListener('submit', function(e) {
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
                    loadCategoryList();
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