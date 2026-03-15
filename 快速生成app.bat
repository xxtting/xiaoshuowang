@echo off
chcp 65001 >nul
echo ============================================
echo 小说网系统 - 快速生成APP目录
echo ============================================
echo.

echo 正在生成纯净的网站文件到app目录...
echo.

REM 检查并运行生成脚本
if exist "generate_app.bat" (
    call generate_app.bat
) else if exist "generate_app.php" (
    php generate_app.php
) else (
    echo ❌ 错误：没有找到生成脚本
    echo.
    echo 请确保以下文件存在：
    echo - generate_app.bat (Windows)
    echo - generate_app.php (PHP)
    echo - generate_app.sh (Linux/Mac)
    pause
    exit /b 1
)

echo.
echo ============================================
echo 生成完成！app目录已准备好。
echo ============================================
echo.
echo 下一步操作：
echo 1. 检查 app/ 目录中的文件
echo 2. 上传到服务器进行部署
echo 3. 访问网站完成安装
echo.
pause