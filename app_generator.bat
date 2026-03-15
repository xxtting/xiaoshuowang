@echo off
chcp 65001 >nul
echo ============================================
echo 小说网系统 - APP目录生成工具
echo ============================================
echo.

set SOURCE_DIR=%~dp0
set TARGET_DIR=%SOURCE_DIR%app

echo [1/6] 清理旧的app目录...
if exist "%TARGET_DIR%" (
    rmdir /s /q "%TARGET_DIR%"
)
mkdir "%TARGET_DIR%"

echo [2/6] 复制根目录文件...
copy "%SOURCE_DIR%index.php" "%TARGET_DIR%\index.php" >nul
copy "%SOURCE_DIR%.htaccess" "%TARGET_DIR%\.htaccess" >nul
copy "%SOURCE_DIR%README.md" "%TARGET_DIR%\README.md" >nul

echo [3/6] 复制后端文件...
xcopy "%SOURCE_DIR%backend" "%TARGET_DIR%\backend" /E /I /Q >nul

echo [4/6] 复制管理后台文件...
xcopy "%SOURCE_DIR%admin" "%TARGET_DIR%\admin" /E /I /Q >nul

echo [5/6] 复制前端文件...
xcopy "%SOURCE_DIR%public" "%TARGET_DIR%\public" /E /I /Q >nul

echo [6/6] 复制其他必要目录...
xcopy "%SOURCE_DIR%install" "%TARGET_DIR%\install" /E /I /Q >nul
xcopy "%SOURCE_DIR%database" "%TARGET_DIR%\database" /E /I /Q >nul

echo 创建运行时目录...
mkdir "%TARGET_DIR%\runtime" >nul 2>&1
mkdir "%TARGET_DIR%\runtime\cache" >nul 2>&1
mkdir "%TARGET_DIR%\runtime\logs" >nul 2>&1
mkdir "%TARGET_DIR%\runtime\backups" >nul 2>&1
mkdir "%TARGET_DIR%\public\uploads" >nul 2>&1

echo.
echo ✅ 生成完成！
echo.
echo 文件结构：
echo app/
echo ├── index.php
echo ├── .htaccess
echo ├── README.md
echo ├── backend/      (PHP后端API)
echo ├── admin/        (管理后台)
echo ├── public/       (前端页面)
echo ├── install/      (安装系统)
echo ├── database/     (数据库文件)
echo └── runtime/      (运行时目录)
echo.
pause
