<?php
/**
 * 数据库配置检测工具
 */

echo "<h2>数据库配置检测</h2>";
echo "<hr>";

// 检查配置文件是否存在
$configFile = __DIR__ . '/../backend/config/database.php';
echo "<p>1. 检查配置文件: ";
if (file_exists($configFile)) {
    echo "<span style='color:green'>✓ 存在</span>";
} else {
    echo "<span style='color:red'>✗ 不存在</span>";
    exit;
}
echo "</p>";

// 引入配置文件
echo "<p>2. 加载配置文件: ";
try {
    $config = require $configFile;
    echo "<span style='color:green'>✓ 成功</span>";
} catch (Exception $e) {
    echo "<span style='color:red'>✗ 失败: " . $e->getMessage() . "</span>";
    exit;
}
echo "</p>";

// 检查常量定义
echo "<p>3. 检查常量定义:</p>";
$constants = ['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASS', 'DB_CHARSET'];
echo "<ul>";
foreach ($constants as $const) {
    echo "<li>" . $const . ": ";
    if (defined($const)) {
        $value = constant($const);
        if ($const === 'DB_PASS') {
            echo "<span style='color:green'>✓ 已定义 (******)</span>";
        } else {
            echo "<span style='color:green'>✓ 已定义 (" . htmlspecialchars($value) . ")</span>";
        }
    } else {
        echo "<span style='color:red'>✗ 未定义</span>";
    }
    echo "</li>";
}
echo "</ul>";

// 检查配置数组
echo "<p>4. 检查配置数组:</p>";
$keys = ['host', 'port', 'database', 'username', 'password', 'charset'];
echo "<ul>";
foreach ($keys as $key) {
    echo "<li>" . $key . ": ";
    if (isset($config[$key])) {
        if ($key === 'password') {
            echo "<span style='color:green'>✓ 已设置 (******)</span>";
        } else {
            echo "<span style='color:green'>✓ 已设置 (" . htmlspecialchars($config[$key]) . ")</span>";
        }
    } else {
        echo "<span style='color:red'>✗ 未设置</span>";
    }
    echo "</li>";
}
echo "</ul>";

// 测试数据库连接
echo "<p>5. 测试数据库连接: ";
try {
    if (defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER') && defined('DB_PASS')) {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        echo "<span style='color:green'>✓ 连接成功</span>";
    } else {
        echo "<span style='color:red'>✗ 常量未定义，无法连接</span>";
    }
} catch (PDOException $e) {
    echo "<span style='color:red'>✗ 连接失败: " . htmlspecialchars($e->getMessage()) . "</span>";
}
echo "</p>";

echo "<hr>";
echo "<p><a href='dashboard.html' style='padding:10px 20px;background:#667eea;color:white;text-decoration:none;border-radius:5px;'>返回后台</a></p>";
