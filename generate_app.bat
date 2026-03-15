@echo off
chcp 65001 >nul
echo ============================================
echo 小说网系统 - 自动生成APP目录工具 (Windows)
echo ============================================
echo.

set SOURCE_DIR=%~dp0
set TARGET_DIR=%SOURCE_DIR%app

echo [1/6] 检查源目录...
if not exist "%SOURCE_DIR%index.php" (
    echo ❌ 错误：源目录不是有效的小说网项目
    pause
    exit /b 1
)

echo [2/6] 清理目标目录...
if exist "%TARGET_DIR%" (
    echo 正在删除旧的app目录...
    rmdir /s /q "%TARGET_DIR%"
)
mkdir "%TARGET_DIR%"

echo [3/6] 复制网站核心文件...
echo 复制根目录文件...
copy "%SOURCE_DIR%index.php" "%TARGET_DIR%\index.php" >nul
copy "%SOURCE_DIR%.htaccess" "%TARGET_DIR%\.htaccess" >nul

echo 复制README.md...
copy "%SOURCE_DIR%README.md" "%TARGET_DIR%\README.md" >nul

echo [4/6] 复制后端文件...
xcopy "%SOURCE_DIR%backend" "%TARGET_DIR%\backend" /E /I /Q >nul

echo [5/6] 复制其他必要目录...
xcopy "%SOURCE_DIR%install" "%TARGET_DIR%\install" /E /I /Q >nul
xcopy "%SOURCE_DIR%admin" "%TARGET_DIR%\admin" /E /I /Q >nul
xcopy "%SOURCE_DIR%public" "%TARGET_DIR%\public" /E /I /Q >nul
xcopy "%SOURCE_DIR%database" "%TARGET_DIR%\database" /E /I /Q >nul

echo [6/6] 创建必要的运行时目录...
mkdir "%TARGET_DIR%\runtime" >nul 2>&1
mkdir "%TARGET_DIR%\runtime\cache" >nul 2>&1
mkdir "%TARGET_DIR%\public\uploads" >nul 2>&1

echo.
echo ✅ 生成完成！
echo.
echo 统计信息：
dir "%TARGET_DIR%" /b | find /c /v "" >nul
echo - app目录已创建在：%TARGET_DIR%
echo - 包含所有网站运行必需的文件
echo - 不包含：部署脚本、文档、临时文件
echo.
echo 文件结构：
echo app/
echo ├── index.php
echo ├── .htaccess
echo ├── README.md
echo ├── backend/
echo ├── install/
echo ├── admin/
echo ├── public/
echo ├── database/
echo └── runtime/
echo.
echo 注意事项：
echo 1. app目录是纯净的网站文件，可直接上传服务器
echo 2. 已排除部署脚本和开发文档
echo 3. 请确保数据库配置文件 backend/config/database.php 正确
echo.
pause