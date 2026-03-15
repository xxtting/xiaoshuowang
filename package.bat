@echo off
chcp 65001 >nul
echo ====================================
echo     小说网系统打包工具
echo ====================================
echo.

cd /d "%~dp0"

echo 正在打包 app 目录...
echo.

set "zipFile=xiaoshuowang_app.zip"

:: 删除旧的zip文件
if exist "%zipFile%" del "%zipFile%" /f /q

:: 使用PowerShell压缩
powershell -Command "Compress-Archive -Path 'app\*' -DestinationPath '%zipFile%' -Force"

if exist "%zipFile%" (
    echo.
    echo ✅ 打包完成！
    echo.
    for %%F in ("%zipFile%") do (
        echo 文件名: %%~nxF
        echo 大小: %%~zF 字节
        echo 路径: %%~fF
    )
) else (
    echo.
    echo ❌ 打包失败
)

echo.
echo ====================================
pause
