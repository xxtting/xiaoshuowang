@echo off
chcp 65001 >nul
cd /d "%~dp0"
echo 正在打包...
if exist "xiaoshuowang_app.zip" del "xiaoshuowang_app.zip" /f /q
powershell -Command "Compress-Archive -Path 'app\*' -DestinationPath 'xiaoshuowang_app.zip' -Force"
if exist "xiaoshuowang_app.zip" (
    echo 打包成功！
    for %%F in ("xiaoshuowang_app.zip") do echo 大小: %%~zF 字节
) else (
    echo 打包失败
)
pause
