<?php
/**
 * 数据库配置文件
 * 
 * 请修改以下配置为您的实际数据库信息
 */

// 数据库主机地址
define('DB_HOST', 'localhost');

// 数据库端口
define('DB_PORT', '3306');

// 数据库名称
define('DB_NAME', 'xiao_72nh_com');

// 数据库用户名
define('DB_USER', 'xiao_72nh_com');

// 数据库密码 - 请修改为实际密码
define('DB_PASS', 'D33YHYsEeifncP4i');

// 数据库字符集
define('DB_CHARSET', 'utf8mb4');

// 数据库表前缀
define('DB_PREFIX', '');

// 返回配置数组（兼容使用数组的代码）
return [
    'host' => DB_HOST,
    'port' => DB_PORT,
    'database' => DB_NAME,
    'username' => DB_USER,
    'password' => DB_PASS,
    'charset' => DB_CHARSET,
    'prefix' => DB_PREFIX,
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ],
];
