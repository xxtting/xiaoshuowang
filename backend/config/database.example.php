<?php
/**
 * 数据库配置文件示例
 * 安装程序会自动将此文件复制为 database.php
 * 请根据实际环境修改以下配置
 */

return [
    // 数据库主机地址
    'host' => 'localhost',
    
    // 数据库端口
    'port' => '3306',
    
    // 数据库名称
    'database' => 'xiaoshuowang',
    
    // 数据库用户名
    'username' => 'root',
    
    // 数据库密码
    'password' => 'your_password_here',
    
    // 数据库字符集
    'charset' => 'utf8mb4',
    
    // 数据库表前缀
    'prefix' => '',
    
    // PDO选项
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ],
];

// ==============================================
// 配置说明：
// 1. 请将 'your_password_here' 替换为实际的数据库密码
// 2. 确保数据库用户有创建表的权限
// 3. 如果使用远程数据库，请修改 'host' 地址
// 4. 安装完成后，此文件会自动被复制为 database.php
// ==============================================