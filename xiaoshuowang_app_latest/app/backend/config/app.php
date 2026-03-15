<?php
/**
 * 应用配置文件
 */

return [
    // 应用设置
    'app' => [
        'name' => 'AI小说网',
        'version' => '1.0.0',
        'debug' => false,
        'timezone' => 'Asia/Shanghai',
    ],
    
    // 数据库配置模板
    'database' => [
        'host' => 'localhost',
        'port' => '3306',
        'database' => 'xiaoshuowang',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
    ],
    
    // 文件上传设置
    'upload' => [
        'max_size' => 10485760, // 10MB
        'allowed_types' => ['jpg', 'jpeg', 'png', 'gif'],
        'upload_path' => ROOT_PATH . '/public/uploads/',
        'url_path' => '/uploads/',
    ],
    
    // 缓存设置
    'cache' => [
        'driver' => 'file',
        'path' => ROOT_PATH . '/runtime/cache/',
        'prefix' => 'novel_',
        'ttl' => 3600,
    ],
    
    // 安全设置
    'security' => [
        'csrf_protection' => true,
        'session_httponly' => true,
        'session_secure' => false,
        'session_lifetime' => 7200, // 2小时
    ],
    
    // AI设置
    'ai' => [
        'api_key' => '',
        'api_url' => 'https://api.openai.com/v1/chat/completions',
        'model' => 'gpt-3.5-turbo',
        'max_tokens' => 2000,
        'temperature' => 0.7,
    ],
    
    // 微信设置
    'wechat' => [
        'app_id' => '',
        'app_secret' => '',
        'token' => '',
        'encoding_aes_key' => '',
        'enabled' => false,
    ],
];