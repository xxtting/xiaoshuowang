@echo off
echo ========================================
echo  小说网系统 GitHub 部署助手
echo ========================================
echo.
echo 第一步：在浏览器中生成GitHub Token
echo.
echo 1. 访问：https://github.com/settings/tokens
echo 2. 点击 "Generate new token"
echo 3. 选择 "Generate new token (classic)"
echo 4. 填写信息：
echo    - Note: xiaoshuowang-project-push
echo    - Expiration: 90 days
echo    - Scopes: 勾选 "repo"
echo 5. 点击 "Generate token"
echo 6. 复制生成的Token并保存在安全的地方
echo.
echo 第二步：在GitHub创建仓库
echo.
echo 1. 访问：https://github.com/new
echo 2. 填写仓库信息：
echo    - Repository name: xiaoshuowang
echo    - Description: 小说网系统 - PHP小说阅读创作平台
echo 3. 点击 "Create repository"
echo.
echo 第三步：执行推送命令
echo.
echo 请复制下面的命令，在命令行中执行：
echo.
echo git remote add origin https://github.com/xxtting/xiaoshuowang.git
echo git branch -M main
echo git push -u origin main
echo.
echo 第四步：输入GitHub用户名和Token
echo.
echo 当提示输入用户名时：输入 xxtting
echo 当提示输入密码时：粘贴您的GitHub Token
echo.
echo ========================================
echo  注意：Token只会显示一次，请务必保存好！
echo ========================================
echo.
pause