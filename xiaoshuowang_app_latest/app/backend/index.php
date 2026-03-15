<?php
/**
 * 小说网后端API入口文件
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// 设置时区
date_default_timezone_set('Asia/Shanghai');

// 定义常量
define('ROOT_PATH', dirname(__FILE__));
define('APP_PATH', ROOT_PATH . '/app');
define('CONFIG_PATH', ROOT_PATH . '/config');

// 检查是否已安装
if (!file_exists(CONFIG_PATH . '/database.php') && !isset($_GET['install'])) {
    header('Location: ../install/');
    exit;
}

// 自动加载类
spl_autoload_register(function ($className) {
    $className = str_replace('\\', '/', $className);
    $filePath = APP_PATH . '/' . $className . '.php';
    
    if (file_exists($filePath)) {
        require_once $filePath;
    }
});

// 加载核心文件
require_once APP_PATH . '/Core/Config.php';
require_once APP_PATH . '/Core/Database.php';
require_once APP_PATH . '/Core/Request.php';
require_once APP_PATH . '/Core/Response.php';
require_once APP_PATH . '/Core/Router.php';

// 处理请求
try {
    $request = new App\Core\Request();
    $response = new App\Core\Response();
    $router = new App\Core\Router($request, $response);
    
    // 注册路由
    require_once APP_PATH . '/routes.php';
    
    // 执行路由
    $router->dispatch();
    
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    
    $errorResponse = [
        'code' => 500,
        'message' => '服务器内部错误',
        'data' => null
    ];
    
    if (App\Core\Config::get('app.debug')) {
        $errorResponse['debug'] = [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ];
    }
    
    echo json_encode($errorResponse, JSON_UNESCAPED_UNICODE);
}