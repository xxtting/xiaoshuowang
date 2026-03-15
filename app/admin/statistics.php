<?php
/**
 * 数据统计模块
 * 展示网站各项数据统计信息
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
    die("数据库连接失败: " . $e->getMessage());
}

// 处理AJAX请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'overview':
            // 获取概览统计数据
            $stats = [];
            
            // 用户统计
            $stats['total_users'] = $pdo->query("SELECT COUNT(*) FROM user")->fetchColumn();
            $stats['today_users'] = $pdo->query("SELECT COUNT(*) FROM user WHERE DATE(create_time) = CURDATE()")->fetchColumn();
            $stats['week_users'] = $pdo->query("SELECT COUNT(*) FROM user WHERE create_time >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)")->fetchColumn();
            $stats['month_users'] = $pdo->query("SELECT COUNT(*) FROM user WHERE create_time >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)")->fetchColumn();
            
            // 小说统计
            $stats['total_novels'] = $pdo->query("SELECT COUNT(*) FROM novel")->fetchColumn();
            $stats['today_novels'] = $pdo->query("SELECT COUNT(*) FROM novel WHERE DATE(create_time) = CURDATE()")->fetchColumn();
            $stats['total_chapters'] = $pdo->query("SELECT COUNT(*) FROM novel_chapter")->fetchColumn();
            $stats['total_words'] = $pdo->query("SELECT SUM(word_count) FROM novel")->fetchColumn() ?: 0;
            
            // 阅读统计
            $stats['total_reads'] = $pdo->query("SELECT COUNT(*) FROM user_read_history")->fetchColumn();
            $stats['today_reads'] = $pdo->query("SELECT COUNT(*) FROM user_read_history WHERE DATE(last_read_time) = CURDATE()")->fetchColumn();
            $stats['total_favorites'] = $pdo->query("SELECT COUNT(*) FROM user_favorite")->fetchColumn();
            
            // AI生成统计
            $stats['total_ai_generate'] = $pdo->query("SELECT COUNT(*) FROM ai_novel_generate")->fetchColumn();
            $stats['today_ai_generate'] = $pdo->query("SELECT COUNT(*) FROM ai_novel_generate WHERE DATE(create_time) = CURDATE()")->fetchColumn();
            $stats['total_tokens'] = $pdo->query("SELECT SUM(cost_tokens) FROM ai_novel_generate")->fetchColumn() ?: 0;
            
            // 分类统计
            $stmt = $pdo->query("
                SELECT c.name, COUNT(n.id) as count 
                FROM novel_category c 
                LEFT JOIN novel n ON c.id = n.category_id 
                GROUP BY c.id 
                ORDER BY count DESC
            ");
            $stats['category_stats'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['code' => 0, 'msg' => 'success', 'data' => $stats]);
            break;
            
        case 'trend':
            // 获取趋势数据
            $days = intval($_POST['days'] ?? 7);
            $type = $_POST['type'] ?? 'user';
            
            $data = [];
            $labels = [];
            
            for ($i = $days - 1; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $labels[] = $date;
                
                switch ($type) {
                    case 'user':
                        $sql = "SELECT COUNT(*) FROM user WHERE DATE(create_time) = ?";
                        break;
                    case 'novel':
                        $sql = "SELECT COUNT(*) FROM novel WHERE DATE(create_time) = ?";
                        break;
                    case 'chapter':
                        $sql = "SELECT COUNT(*) FROM novel_chapter WHERE DATE(create_time) = ?";
                        break;
                    case 'read':
                        $sql = "SELECT COUNT(*) FROM user_read_history WHERE DATE(last_read_time) = ?";
                        break;
                    case 'ai':
                        $sql = "SELECT COUNT(*) FROM ai_novel_generate WHERE DATE(create_time) = ?";
                        break;
                    default:
                        $sql = "SELECT COUNT(*) FROM user WHERE DATE(create_time) = ?";
                }
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$date]);
                $data[] = intval($stmt->fetchColumn());
            }
            
            echo json_encode([
                'code' => 0, 
                'msg' => 'success', 
                'data' => ['labels' => $labels, 'values' => $data]
            ]);
            break;
            
        case 'top_novels':
            // 获取热门小说排行
            $limit = intval($_POST['limit'] ?? 10);
            
            $stmt = $pdo->prepare("
                SELECT n.id, n.title, n.author, n.view_count, n.word_count, n.chapter_count,
                       COUNT(DISTINCT rh.user_id) as reader_count,
                       COUNT(DISTINCT uf.user_id) as favorite_count
                FROM novel n
                LEFT JOIN user_read_history rh ON n.id = rh.novel_id
                LEFT JOIN user_favorite uf ON n.id = uf.novel_id
                GROUP BY n.id
                ORDER BY n.view_count DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            $novels = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['code' => 0, 'msg' => 'success', 'data' => $novels]);
            break;
            
        case 'top_users':
            // 获取活跃用户排行
            $limit = intval($_POST['limit'] ?? 10);
            
            $stmt = $pdo->prepare("
                SELECT u.id, u.username, u.nickname, u.create_time,
                       COUNT(DISTINCT rh.novel_id) as read_count,
                       COUNT(DISTINCT uf.novel_id) as favorite_count
                FROM user u
                LEFT JOIN user_read_history rh ON u.id = rh.user_id
                LEFT JOIN user_favorite uf ON u.id = uf.user_id
                GROUP BY u.id
                ORDER BY read_count DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['code' => 0, 'msg' => 'success', 'data' => $users]);
            break;
            
        case 'device_stats':
            // 设备统计（从用户代理分析）
            $stmt = $pdo->query("
                SELECT 
                    CASE 
                        WHEN user_agent LIKE '%Mobile%' THEN '移动端'
                        WHEN user_agent LIKE '%Tablet%' THEN '平板'
                        ELSE '桌面端'
                    END as device_type,
                    COUNT(*) as count
                FROM sys_log
                WHERE user_agent IS NOT NULL
                GROUP BY device_type
            ");
            $deviceStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['code' => 0, 'msg' => 'success', 'data' => $deviceStats]);
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
    <title>数据统计 - 小说网后台</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 28px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .header h1 i {
            color: #667eea;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .stat-title {
            font-size: 14px;
            color: #666;
            font-weight: 500;
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }
        
        .icon-blue { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .icon-green { background: linear-gradient(135deg, #38a169 0%, #68d391 100%); }
        .icon-orange { background: linear-gradient(135deg, #ed8936 0%, #fbbf24 100%); }
        .icon-purple { background: linear-gradient(135deg, #9f7aea 0%, #d6bcfa 100%); }
        .icon-red { background: linear-gradient(135deg, #f56565 0%, #fc8181 100%); }
        .icon-cyan { background: linear-gradient(135deg, #00bcd4 0%, #4dd0e1 100%); }
        
        .stat-value {
            font-size: 36px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        
        .stat-change {
            font-size: 14px;
            color: #38a169;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .stat-change.negative {
            color: #e53e3e;
        }
        
        .chart-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .chart-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        
        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .chart-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }
        
        .chart-filters {
            display: flex;
            gap: 10px;
        }
        
        .filter-btn {
            padding: 6px 12px;
            border: 1px solid #e2e8f0;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.3s;
        }
        
        .filter-btn:hover, .filter-btn.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
        }
        
        .ranking-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
        }
        
        .ranking-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        
        .ranking-list {
            list-style: none;
        }
        
        .ranking-item {
            display: flex;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .ranking-item:last-child {
            border-bottom: none;
        }
        
        .ranking-number {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
            margin-right: 15px;
        }
        
        .ranking-number.top3 {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .ranking-number.normal {
            background: #f0f0f0;
            color: #666;
        }
        
        .ranking-info {
            flex: 1;
        }
        
        .ranking-title {
            font-weight: 500;
            color: #333;
            margin-bottom: 4px;
        }
        
        .ranking-subtitle {
            font-size: 12px;
            color: #999;
        }
        
        .ranking-value {
            font-weight: 600;
            color: #667eea;
        }
        
        .category-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .category-item {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
        }
        
        .category-name {
            font-size: 14px;
            color: #666;
            margin-bottom: 8px;
        }
        
        .category-count {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
        }
        
        .loading {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        
        @media (max-width: 768px) {
            .chart-section {
                grid-template-columns: 1fr;
            }
            .ranking-section {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-chart-line"></i> 数据统计中心</h1>
            <button class="btn btn-primary" onclick="goBack()">
                <i class="fas fa-arrow-left"></i> 返回后台
            </button>
        </div>
        
        <!-- 概览统计卡片 -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">总用户数</span>
                    <div class="stat-icon icon-blue"><i class="fas fa-users"></i></div>
                </div>
                <div class="stat-value" id="totalUsers">-</div>
                <div class="stat-change">
                    <i class="fas fa-arrow-up"></i>
                    <span>今日新增 <strong id="todayUsers">-</strong> 人</span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">小说总数</span>
                    <div class="stat-icon icon-green"><i class="fas fa-book"></i></div>
                </div>
                <div class="stat-value" id="totalNovels">-</div>
                <div class="stat-change">
                    <i class="fas fa-file-alt"></i>
                    <span>共 <strong id="totalChapters">-</strong> 章节</span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">总阅读量</span>
                    <div class="stat-icon icon-orange"><i class="fas fa-eye"></i></div>
                </div>
                <div class="stat-value" id="totalReads">-</div>
                <div class="stat-change">
                    <i class="fas fa-heart"></i>
                    <span>收藏 <strong id="totalFavorites">-</strong> 次</span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">AI生成次数</span>
                    <div class="stat-icon icon-purple"><i class="fas fa-robot"></i></div>
                </div>
                <div class="stat-value" id="totalAi">-</div>
                <div class="stat-change">
                    <i class="fas fa-coins"></i>
                    <span>消耗 <strong id="totalTokens">-</strong> tokens</span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">本周新增用户</span>
                    <div class="stat-icon icon-red"><i class="fas fa-user-plus"></i></div>
                </div>
                <div class="stat-value" id="weekUsers">-</div>
                <div class="stat-change">
                    <span>近7天注册用户</span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">总字数</span>
                    <div class="stat-icon icon-cyan"><i class="fas fa-font"></i></div>
                </div>
                <div class="stat-value" id="totalWords">-</div>
                <div class="stat-change">
                    <span>累计创作字数</span>
                </div>
            </div>
        </div>
        
        <!-- 趋势图表 -->
        <div class="chart-section">
            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title">📈 增长趋势</h3>
                    <div class="chart-filters">
                        <button class="filter-btn active" onclick="changeTrend('user', 7, this)">用户(7天)</button>
                        <button class="filter-btn" onclick="changeTrend('novel', 7, this)">小说(7天)</button>
                        <button class="filter-btn" onclick="changeTrend('read', 7, this)">阅读(7天)</button>
                        <button class="filter-btn" onclick="changeTrend('ai', 7, this)">AI(7天)</button>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>
            
            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title">📊 分类分布</h3>
                </div>
                <div class="chart-container">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- 排行榜 -->
        <div class="ranking-section">
            <div class="ranking-card">
                <div class="chart-header">
                    <h3 class="chart-title">🔥 热门小说TOP10</h3>
                </div>
                <ul class="ranking-list" id="topNovels">
                    <li class="loading">加载中...</li>
                </ul>
            </div>
            
            <div class="ranking-card">
                <div class="chart-header">
                    <h3 class="chart-title">⭐ 活跃用户TOP10</h3>
                </div>
                <ul class="ranking-list" id="topUsers">
                    <li class="loading">加载中...</li>
                </ul>
            </div>
        </div>
    </div>
    
    <script>
        let trendChart = null;
        let categoryChart = null;
        
        // 页面加载时获取数据
        document.addEventListener('DOMContentLoaded', function() {
            loadOverview();
            loadTrend('user', 7);
            loadCategoryStats();
            loadTopNovels();
            loadTopUsers();
        });
        
        // 加载概览数据
        function loadOverview() {
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=overview'
            })
            .then(response => response.json())
            .then(data => {
                if (data.code === 0) {
                    const stats = data.data;
                    document.getElementById('totalUsers').textContent = formatNumber(stats.total_users);
                    document.getElementById('todayUsers').textContent = stats.today_users;
                    document.getElementById('weekUsers').textContent = stats.week_users;
                    document.getElementById('totalNovels').textContent = formatNumber(stats.total_novels);
                    document.getElementById('totalChapters').textContent = formatNumber(stats.total_chapters);
                    document.getElementById('totalReads').textContent = formatNumber(stats.total_reads);
                    document.getElementById('todayReads').textContent = stats.today_reads;
                    document.getElementById('totalFavorites').textContent = formatNumber(stats.total_favorites);
                    document.getElementById('totalAi').textContent = formatNumber(stats.total_ai_generate);
                    document.getElementById('totalTokens').textContent = formatNumber(stats.total_tokens);
                    document.getElementById('totalWords').textContent = formatNumber(stats.total_words);
                }
            });
        }
        
        // 加载趋势数据
        function loadTrend(type, days) {
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=trend&type=${type}&days=${days}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.code === 0) {
                    renderTrendChart(data.data);
                }
            });
        }
        
        // 切换趋势类型
        function changeTrend(type, days, btn) {
            document.querySelectorAll('.chart-filters .filter-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            loadTrend(type, days);
        }
        
        // 渲染趋势图表
        function renderTrendChart(data) {
            const ctx = document.getElementById('trendChart').getContext('2d');
            
            if (trendChart) {
                trendChart.destroy();
            }
            
            trendChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels.map(d => d.substring(5)), // 去掉年份
                    datasets: [{
                        label: '数量',
                        data: data.values,
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#667eea',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(0,0,0,0.05)' }
                        },
                        x: {
                            grid: { display: false }
                        }
                    }
                }
            });
        }
        
        // 加载分类统计
        function loadCategoryStats() {
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=overview'
            })
            .then(response => response.json())
            .then(data => {
                if (data.code === 0 && data.data.category_stats) {
                    renderCategoryChart(data.data.category_stats);
                }
            });
        }
        
        // 渲染分类图表
        function renderCategoryChart(categories) {
            const ctx = document.getElementById('categoryChart').getContext('2d');
            
            const labels = categories.map(c => c.name);
            const values = categories.map(c => parseInt(c.count));
            const colors = [
                '#667eea', '#38a169', '#ed8936', '#9f7aea',
                '#f56565', '#00bcd4', '#f093fb', '#4facfe'
            ];
            
            if (categoryChart) {
                categoryChart.destroy();
            }
            
            categoryChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: values,
                        backgroundColor: colors,
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                padding: 15,
                                font: { size: 12 }
                            }
                        }
                    }
                }
            });
        }
        
        // 加载热门小说
        function loadTopNovels() {
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=top_novels&limit=10'
            })
            .then(response => response.json())
            .then(data => {
                if (data.code === 0) {
                    renderTopNovels(data.data);
                }
            });
        }
        
        // 渲染热门小说
        function renderTopNovels(novels) {
            const html = novels.map((novel, index) => `
                <li class="ranking-item">
                    <div class="ranking-number ${index < 3 ? 'top3' : 'normal'}">${index + 1}</div>
                    <div class="ranking-info">
                        <div class="ranking-title">${novel.title}</div>
                        <div class="ranking-subtitle">${novel.author} · ${formatNumber(novel.view_count)} 阅读</div>
                    </div>
                    <div class="ranking-value">${formatNumber(novel.view_count)}</div>
                </li>
            `).join('');
            
            document.getElementById('topNovels').innerHTML = html;
        }
        
        // 加载活跃用户
        function loadTopUsers() {
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=top_users&limit=10'
            })
            .then(response => response.json())
            .then(data => {
                if (data.code === 0) {
                    renderTopUsers(data.data);
                }
            });
        }
        
        // 渲染活跃用户
        function renderTopUsers(users) {
            const html = users.map((user, index) => `
                <li class="ranking-item">
                    <div class="ranking-number ${index < 3 ? 'top3' : 'normal'}">${index + 1}</div>
                    <div class="ranking-info">
                        <div class="ranking-title">${user.nickname || user.username}</div>
                        <div class="ranking-subtitle">阅读 ${user.read_count} 本 · 收藏 ${user.favorite_count} 本</div>
                    </div>
                    <div class="ranking-value">${user.read_count}</div>
                </li>
            `).join('');
            
            document.getElementById('topUsers').innerHTML = html;
        }
        
        // 格式化数字
        function formatNumber(num) {
            if (num >= 10000) {
                return (num / 10000).toFixed(1) + '万';
            }
            return num.toLocaleString();
        }
        
        // 返回后台
        function goBack() {
            window.location.href = 'dashboard.html';
        }
    </script>
</body>
</html>
