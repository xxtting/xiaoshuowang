<?php
/**
 * 操作日志页面
 */

session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.html');
    exit;
}

// 引入数据库配置 - 优先使用安装程序创建的配置
$dbConfig = null;
$dbError = null;
$logs = [];
$totalLogs = 0;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$pageSize = 20;
$offset = ($page - 1) * $pageSize;

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
    
    $actionType = $_GET['action_type'] ?? '';
    $search = $_GET['search'] ?? '';
    $dateFrom = $_GET['date_from'] ?? '';
    $dateTo = $_GET['date_to'] ?? '';
    
    $where = '1=1';
    $params = [];
    
    if ($actionType) { $where .= " AND action_type = ?"; $params[] = $actionType; }
    if ($search) { $where .= " AND (username LIKE ? OR action LIKE ? OR description LIKE ?)"; $searchParam = "%$search%"; $params = array_merge($params, [$searchParam, $searchParam, $searchParam]); }
    if ($dateFrom) { $where .= " AND create_time >= ?"; $params[] = $dateFrom . ' 00:00:00'; }
    if ($dateTo) { $where .= " AND create_time <= ?"; $params[] = $dateTo . ' 23:59:59'; }
    
    $countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM sys_log WHERE $where");
    $countStmt->execute($params);
    $totalLogs = $countStmt->fetch()['total'] ?? 0;
    
    $sql = "SELECT * FROM sys_log WHERE $where ORDER BY create_time DESC LIMIT $offset, $pageSize";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $dbError = $e->getMessage();
}

$totalPages = ceil($totalLogs / $pageSize);
$actionTypeLabels = [
    'login' => ['label' => '登录', 'class' => 'bg-primary'],
    'content' => ['label' => '内容', 'class' => 'bg-success'],
    'system' => ['label' => '系统', 'class' => 'bg-warning'],
    'error' => ['label' => '错误', 'class' => 'bg-danger'],
    'other' => ['label' => '其他', 'class' => 'bg-secondary']
];
$stats = ['today' => 0, 'week' => 0, 'month' => 0, 'error' => 0];
if (!$dbError) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as today FROM sys_log WHERE DATE(create_time) = CURDATE()");
        $stats['today'] = $stmt->fetch()['today'] ?? 0;
        $stmt = $pdo->query("SELECT COUNT(*) as week FROM sys_log WHERE create_time >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
        $stats['week'] = $stmt->fetch()['week'] ?? 0;
        $stmt = $pdo->query("SELECT COUNT(*) as month FROM sys_log WHERE create_time >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
        $stats['month'] = $stmt->fetch()['month'] ?? 0;
        $stmt = $pdo->query("SELECT COUNT(*) as error FROM sys_log WHERE action_type = 'error' OR result = 0");
        $stats['error'] = $stmt->fetch()['error'] ?? 0;
    } catch (PDOException $e) {}
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>操作日志 - 小说网后台管理</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary-color: #667eea; --secondary-color: #764ba2; }
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
        .main-container { background: rgba(255,255,255,0.95); border-radius: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); padding: 30px; margin: 30px auto; max-width: 1400px; }
        .page-title { color: #333; font-weight: 700; margin-bottom: 30px; display: flex; align-items: center; gap: 10px; }
        .stat-card { background: white; border-radius: 15px; padding: 20px; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1); transition: transform 0.3s; }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-number { font-size: 2rem; font-weight: 700; color: var(--primary-color); }
        .stat-label { color: #666; font-size: 0.9rem; margin-top: 5px; }
        .filter-section { background: #f8f9fa; border-radius: 15px; padding: 20px; margin-bottom: 20px; }
        .log-table { background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .log-table th { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; font-weight: 600; border: none; }
        .log-table td { vertical-align: middle; }
        .action-badge { font-size: 0.75rem; padding: 5px 10px; border-radius: 20px; }
        .result-success { color: #28a745; }
        .result-fail { color: #dc3545; }
        .back-btn { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 10px 20px; border-radius: 25px; transition: all 0.3s; }
        .back-btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102,126,234,0.4); color: white; }
        .error-alert { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 10px; margin-bottom: 20px; }
        .empty-state { text-align: center; padding: 60px 20px; color: #666; }
        .empty-state i { font-size: 4rem; color: #ddd; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="main-container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="page-title"><i class="fas fa-history"></i> 操作日志</h1>
                <a href="dashboard.html" class="back-btn"><i class="fas fa-arrow-left"></i> 返回仪表板</a>
            </div>
            <?php if ($dbError): ?>
            <div class="error-alert"><i class="fas fa-exclamation-triangle"></i> <strong>数据库连接失败：</strong><?php echo htmlspecialchars($dbError); ?><br>请检查 config.php 文件中的数据库配置。</div>
            <?php endif; ?>
            <div class="row mb-4">
                <div class="col-md-3"><div class="stat-card"><div class="stat-number"><?php echo number_format($stats['today']); ?></div><div class="stat-label"><i class="fas fa-calendar-day"></i> 今日日志</div></div></div>
                <div class="col-md-3"><div class="stat-card"><div class="stat-number"><?php echo number_format($stats['week']); ?></div><div class="stat-label"><i class="fas fa-calendar-week"></i> 本周日志</div></div></div>
                <div class="col-md-3"><div class="stat-card"><div class="stat-number"><?php echo number_format($stats['month']); ?></div><div class="stat-label"><i class="fas fa-calendar-alt"></i> 本月日志</div></div></div>
                <div class="col-md-3"><div class="stat-card"><div class="stat-number" style="color:#dc3545;"><?php echo number_format($stats['error']); ?></div><div class="stat-label"><i class="fas fa-exclamation-circle"></i> 错误记录</div></div></div>
            </div>
            <div class="filter-section">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">操作类型</label>
                        <select name="action_type" class="form-select">
                            <option value="">全部类型</option>
                            <option value="login" <?php echo $actionType=='login'?'selected':''; ?>>登录</option>
                            <option value="content" <?php echo $actionType=='content'?'selected':''; ?>>内容</option>
                            <option value="system" <?php echo $actionType=='system'?'selected':''; ?>>系统</option>
                            <option value="error" <?php echo $actionType=='error'?'selected':''; ?>>错误</option>
                        </select>
                    </div>
                    <div class="col-md-3"><label class="form-label">搜索</label><input type="text" name="search" class="form-control" placeholder="用户名/操作/描述" value="<?php echo htmlspecialchars($search); ?>"></div>
                    <div class="col-md-2"><label class="form-label">开始日期</label><input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($dateFrom); ?>"></div>
                    <div class="col-md-2"><label class="form-label">结束日期</label><input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($dateTo); ?>"></div>
                    <div class="col-md-2 d-flex align-items-end"><button type="submit" class="btn btn-primary w-100"><i class="fas fa-search"></i> 筛选</button></div>
                </form>
            </div>
            <div class="log-table">
                <table class="table table-hover mb-0">
                    <thead><tr><th>ID</th><th>类型</th><th>用户</th><th>操作</th><th>描述</th><th>IP</th><th>结果</th><th>时间</th></tr></thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                        <tr><td colspan="8"><div class="empty-state"><i class="fas fa-inbox"></i><h5>暂无日志记录</h5></div></td></tr>
                        <?php else: foreach ($logs as $log): $typeInfo = $actionTypeLabels[$log['action_type']] ?? $actionTypeLabels['other']; ?>
                        <tr><td><?php echo $log['id']; ?></td><td><span class="badge <?php echo $typeInfo['class']; ?> action-badge"><?php echo $typeInfo['label']; ?></span></td><td><?php echo htmlspecialchars($log['username'] ?? '未知'); ?></td><td><?php echo htmlspecialchars($log['action']); ?></td><td><?php echo htmlspecialchars($log['description'] ?? '-'); ?></td><td><?php echo htmlspecialchars($log['ip'] ?? '-'); ?></td><td><?php if ($log['result']==1): ?><span class="result-success"><i class="fas fa-check-circle"></i> 成功</span><?php else: ?><span class="result-fail"><i class="fas fa-times-circle"></i> 失败</span><?php endif; ?></td><td><?php echo $log['create_time']; ?></td></tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($totalPages > 1): ?>
            <nav class="mt-4"><ul class="pagination justify-content-center">
                <?php if ($page > 1): ?><li class="page-item"><a class="page-link" href="?page=<?php echo $page-1; ?>&action_type=<?php echo urlencode($actionType); ?>&search=<?php echo urlencode($search); ?>"><i class="fas fa-chevron-left"></i></a></li><?php endif; ?>
                <?php for ($i = max(1, $page-2); $i <= min($totalPages, $page+2); $i++): ?><li class="page-item <?php echo $i==$page?'active':''; ?>"><a class="page-link" href="?page=<?php echo $i; ?>&action_type=<?php echo urlencode($actionType); ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a></li><?php endfor; ?>
                <?php if ($page < $totalPages): ?><li class="page-item"><a class="page-link" href="?page=<?php echo $page+1; ?>&action_type=<?php echo urlencode($actionType); ?>&search=<?php echo urlencode($search); ?>"><i class="fas fa-chevron-right"></i></a></li><?php endif; ?>
            </ul></nav>
            <?php endif; ?>
            <div class="text-center text-muted mt-3">共 <?php echo $totalLogs; ?> 条记录</div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
