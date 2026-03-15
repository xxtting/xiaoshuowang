<?php
/**
 * 数据统计页面
 */

session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.html');
    exit;
}

// 引入数据库配置 - 优先使用安装程序创建的配置
$dbConfig = null;
$dbError = null;

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
    $dbError = $e->getMessage();
}

// 获取统计数据
$stats = [
    'users' => ['total' => 0, 'today' => 0, 'month' => 0],
    'novels' => ['total' => 0, 'today' => 0, 'month' => 0],
    'chapters' => ['total' => 0],
    'comments' => ['total' => 0],
    'views' => ['total' => 0, 'today' => 0]
];

if (!$dbError) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM user");
        $stats['users']['total'] = $stmt->fetch()['total'] ?? 0;
        $stmt = $pdo->query("SELECT COUNT(*) as today FROM user WHERE DATE(create_time) = CURDATE()");
        $stats['users']['today'] = $stmt->fetch()['today'] ?? 0;
        $stmt = $pdo->query("SELECT COUNT(*) as month FROM user WHERE create_time >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
        $stats['users']['month'] = $stmt->fetch()['month'] ?? 0;
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM novel");
        $stats['novels']['total'] = $stmt->fetch()['total'] ?? 0;
        $stmt = $pdo->query("SELECT COUNT(*) as today FROM novel WHERE DATE(create_time) = CURDATE()");
        $stats['novels']['today'] = $stmt->fetch()['today'] ?? 0;
        $stmt = $pdo->query("SELECT COUNT(*) as month FROM novel WHERE create_time >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
        $stats['novels']['month'] = $stmt->fetch()['month'] ?? 0;
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM chapter");
        $stats['chapters']['total'] = $stmt->fetch()['total'] ?? 0;
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM comment");
        $stats['comments']['total'] = $stmt->fetch()['total'] ?? 0;
        $stmt = $pdo->query("SELECT SUM(view_count) as total FROM novel");
        $stats['views']['total'] = $stmt->fetch()['total'] ?? 0;
    } catch (PDOException $e) {}
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>数据统计 - 小说网后台管理</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root { --primary-color: #667eea; --secondary-color: #764ba2; }
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
        .main-container { background: rgba(255,255,255,0.95); border-radius: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); padding: 30px; margin: 30px auto; max-width: 1400px; }
        .page-title { color: #333; font-weight: 700; margin-bottom: 30px; display: flex; align-items: center; gap: 10px; }
        .stat-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 15px; padding: 25px; color: white; margin-bottom: 20px; transition: transform 0.3s; }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-card.secondary { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .stat-card.success { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .stat-card.warning { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
        .stat-number { font-size: 2.5rem; font-weight: 700; margin-bottom: 5px; }
        .stat-label { font-size: 0.9rem; opacity: 0.9; }
        .stat-trend { font-size: 0.85rem; margin-top: 10px; opacity: 0.9; }
        .chart-container { background: white; border-radius: 15px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .back-btn { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 10px 20px; border-radius: 25px; transition: all 0.3s; }
        .back-btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102,126,234,0.4); color: white; }
        .error-alert { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 10px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="main-container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="page-title"><i class="fas fa-chart-line"></i> 数据统计</h1>
                <a href="dashboard.html" class="back-btn"><i class="fas fa-arrow-left"></i> 返回仪表板</a>
            </div>
            <?php if ($dbError): ?>
            <div class="error-alert"><i class="fas fa-exclamation-triangle"></i> <strong>数据库连接失败：</strong><?php echo htmlspecialchars($dbError); ?><br>请检查 config.php 文件中的数据库配置。</div>
            <?php endif; ?>
            <div class="row">
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo number_format($stats['users']['total']); ?></div>
                        <div class="stat-label"><i class="fas fa-users"></i> 总用户数</div>
                        <div class="stat-trend"><i class="fas fa-arrow-up"></i> 今日 +<?php echo $stats['users']['today']; ?> | 本月 +<?php echo $stats['users']['month']; ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card secondary">
                        <div class="stat-number"><?php echo number_format($stats['novels']['total']); ?></div>
                        <div class="stat-label"><i class="fas fa-book"></i> 小说总数</div>
                        <div class="stat-trend"><i class="fas fa-arrow-up"></i> 今日 +<?php echo $stats['novels']['today']; ?> | 本月 +<?php echo $stats['novels']['month']; ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card success">
                        <div class="stat-number"><?php echo number_format($stats['chapters']['total']); ?></div>
                        <div class="stat-label"><i class="fas fa-file-alt"></i> 章节总数</div>
                        <div class="stat-trend">累计发布章节</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card warning">
                        <div class="stat-number"><?php echo number_format($stats['views']['total']); ?></div>
                        <div class="stat-label"><i class="fas fa-eye"></i> 总阅读量</div>
                        <div class="stat-trend">全站累计阅读</div>
                    </div>
                </div>
            </div>
            <div class="row mt-4">
                <div class="col-md-8">
                    <div class="chart-container">
                        <h5 class="mb-3"><i class="fas fa-chart-area"></i> 用户增长趋势</h5>
                        <canvas id="userGrowthChart" height="100"></canvas>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="chart-container">
                        <h5 class="mb-3"><i class="fas fa-chart-pie"></i> 用户分布</h5>
                        <canvas id="userDistChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        new Chart(document.getElementById('userGrowthChart'), {
            type: 'line',
            data: { labels: ['1月','2月','3月','4月','5月','6月','7月','8月','9月','10月','11月','12月'], datasets: [{ label: '新增用户', data: [120,190,300,500,200,300,450,400,520,600,750,800], borderColor: '#667eea', backgroundColor: 'rgba(102,126,234,0.1)', tension: 0.4, fill: true },{ label: '活跃用户', data: [80,150,250,400,180,280,400,350,480,550,680,720], borderColor: '#764ba2', backgroundColor: 'rgba(118,75,162,0.1)', tension: 0.4, fill: true }] },
            options: { responsive: true, plugins: { legend: { position: 'top' } }, scales: { y: { beginAtZero: true } } }
        });
        new Chart(document.getElementById('userDistChart'), {
            type: 'doughnut',
            data: { labels: ['普通用户','VIP用户','作者','管理员'], datasets: [{ data: [65,20,12,3], backgroundColor: ['#667eea','#f093fb','#4facfe','#43e97b'] }] },
            options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
        });
    </script>
</body>
</html>
