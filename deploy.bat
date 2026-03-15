@echo off
chcp 65001 >nul
echo.
echo ========================================
echo   小说网系统一键部署工具
echo ========================================
echo.

echo [信息] 正在检查当前目录结构...
echo.

if not exist "backend\" (
    echo [错误] 未找到backend目录！
    echo 请确保在正确的项目目录中运行此脚本。
    pause
    exit /b 1
)

echo [信息] 项目结构检查通过！
echo.
echo ========================================
echo   准备上传到服务器
echo ========================================
echo.
echo 上传步骤：
echo 1. 使用FTP工具或控制面板文件管理器
echo 2. 将整个xiaoshuowang文件夹上传到网站根目录
echo 3. 确保文件结构保持不变
echo.
echo 重要目录：
echo   backend/      - PHP后端API
echo   install/      - 安装系统
echo   admin/        - 管理后台
echo   public/       - 前端页面
echo   database/     - 数据库文件
echo.
echo 上传完成后：
echo 1. 访问您的网站域名
echo 2. 系统会自动跳转到安装页面
echo 3. 按照向导完成安装
echo.
echo ========================================
echo   服务器配置检查
echo ========================================
echo.
echo 请确保服务器满足以下要求：
echo.
echo [PHP要求]
echo   - PHP版本 >= 7.4
echo   - 扩展：PDO MySQL, JSON, CURL
echo   - upload_max_filesize >= 10M
echo   - post_max_size >= 10M
echo.
echo [MySQL要求]
echo   - MySQL版本 >= 5.7
echo   - 支持utf8mb4字符集
echo   - 数据库用户有创建表权限
echo.
echo [Web服务器]
echo   - Apache：启用mod_rewrite
echo   - Nginx：配置URL重写
echo   - 支持.htaccess文件
echo.
echo ========================================
echo   快速测试脚本
echo ========================================
echo.
echo 要测试PHP环境，可以创建以下测试文件：
echo.
echo 1. 创建 test.php：
echo    ^<?php phpinfo(); ?^>
echo.
echo 2. 上传到服务器并访问
echo.
echo 3. 检查PHP版本和扩展
echo.
echo ========================================
echo   常见问题解决方案
echo ========================================
echo.
echo [问题1] 安装页面无法访问
echo   解决方案：
echo   1. 检查.htaccess文件是否存在
echo   2. 检查mod_rewrite是否启用
echo   3. 检查PHP版本是否达标
echo.
echo [问题2] 数据库连接失败
echo   解决方案：
echo   1. 检查MySQL服务是否运行
echo   2. 确认数据库用户密码正确
echo   3. 确认数据库用户有创建表权限
echo.
echo [问题3] 文件上传失败
echo   解决方案：
echo   1. 检查public/uploads/目录权限
echo   2. 检查PHP上传限制配置
echo   3. 检查磁盘空间
echo.
echo ========================================
echo   技术支持
echo ========================================
echo.
echo 文档位置：
echo   README.md        - 项目说明
echo   INSTALL.md       - 详细安装指南
echo   UPLOAD_GUIDE.md  - 上传使用指南
echo.
echo 联系信息：
echo   开发者：xxtting
echo   邮箱：xxtting@qq.com
echo   GitHub：https://github.com/xxtting/xiaoshuowang
echo.
echo ========================================
echo   部署完成！
echo ========================================
echo.
echo 现在您可以：
echo 1. 上传所有文件到服务器
echo 2. 访问网站进行安装
echo 3. 开始使用小说系统
echo.
echo 按任意键继续...
pause >nul