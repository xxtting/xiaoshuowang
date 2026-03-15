#!/bin/bash

# 小说网系统部署脚本
# 适用于Linux服务器

set -e

echo "========================================"
echo "  小说网系统部署工具"
echo "========================================"
echo

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# 检查目录
echo -e "${YELLOW}[信息] 检查项目结构...${NC}"
if [ ! -d "backend" ]; then
    echo -e "${RED}[错误] 未找到backend目录！${NC}"
    echo "请确保在正确的项目目录中运行此脚本。"
    exit 1
fi

echo -e "${GREEN}[成功] 项目结构检查通过！${NC}"
echo

# 显示上传说明
echo "========================================"
echo "  上传到服务器说明"
echo "========================================"
echo
echo "上传方法："
echo "1. 使用SCP命令："
echo "   scp -r xiaoshuowang/ user@your-server:/var/www/html/"
echo
echo "2. 使用SFTP工具："
echo "   将整个xiaoshuowang文件夹上传到网站根目录"
echo
echo "3. 使用控制面板："
echo "   通过文件管理器上传所有文件"
echo

# 服务器配置检查
echo "========================================"
echo "  服务器环境要求"
echo "========================================"
echo
echo "PHP要求："
echo "  - PHP版本 >= 7.4"
echo "  - 必需扩展：PDO MySQL, JSON, CURL"
echo "  - upload_max_filesize >= 10M"
echo "  - post_max_size >= 10M"
echo
echo "MySQL要求："
echo "  - MySQL版本 >= 5.7"
echo "  - 支持utf8mb4字符集"
echo "  - 数据库用户有创建表权限"
echo
echo "Web服务器："
echo "  - Apache：启用mod_rewrite"
echo "  - Nginx：配置URL重写"
echo

# 文件权限设置
echo "========================================"
echo "  文件权限设置"
echo "========================================"
echo
echo "上传后建议执行以下权限设置："
echo
echo "# 基本权限"
echo "find . -type f -exec chmod 644 {} \;"
echo "find . -type d -exec chmod 755 {} \;"
echo
echo "# 特殊目录权限"
echo "chmod 777 public/uploads/"
echo "chmod 777 backend/config/"
echo "chmod 777 runtime/cache/"
echo "touch install.lock && chmod 666 install.lock"
echo
echo "# 保护配置文件"
echo "chmod 640 backend/config/database.php"
echo "chmod 640 backend/config/app.php"
echo

# 安装步骤
echo "========================================"
echo "  安装步骤"
echo "========================================"
echo
echo "1. 访问网站域名"
echo "   http://your-domain.com/"
echo
echo "2. 系统会自动跳转到安装页面"
echo
echo "3. 按照向导完成："
echo "   - 环境检查"
echo "   - 数据库配置"
echo "   - 管理员设置"
echo
echo "4. 安装完成后："
echo "   - 前台地址：http://your-domain.com/"
echo "   - 后台地址：http://your-domain.com/admin/"
echo "   - 默认账号：admin"
echo "   - 密码：安装时设置的密码"
echo

# 问题排查
echo "========================================"
echo "  常见问题解决"
echo "========================================"
echo
echo "1. 安装页面无法访问："
echo "   - 检查.htaccess文件"
echo "   - 检查mod_rewrite模块"
echo "   - 检查PHP版本"
echo
echo "2. 数据库连接失败："
echo "   - 检查MySQL服务状态"
echo "   - 检查数据库用户权限"
echo "   - 检查网络连接"
echo
echo "3. 文件上传失败："
echo "   - 检查uploads目录权限"
echo "   - 检查PHP上传限制"
echo "   - 检查磁盘空间"
echo

# 创建测试脚本
echo "========================================"
echo "  环境测试脚本"
echo "========================================"
echo
cat > test_env.php << 'EOF'
<?php
echo "<h2>PHP环境测试</h2>";
echo "<p>PHP版本: " . PHP_VERSION . "</p>";

$required_extensions = ['pdo_mysql', 'json', 'curl', 'mbstring'];
foreach ($required_extensions as $ext) {
    echo "<p>" . $ext . ": " . (extension_loaded($ext) ? "✓ 已安装" : "✗ 未安装") . "</p>";
}

echo "<h3>PHP配置检查</h3>";
echo "<p>upload_max_filesize: " . ini_get('upload_max_filesize') . "</p>";
echo "<p>post_max_size: " . ini_get('post_max_size') . "</p>";
echo "<p>memory_limit: " . ini_get('memory_limit') . "</p>";
echo "<p>max_execution_time: " . ini_get('max_execution_time') . "</p>";

if (function_exists('apache_get_modules')) {
    echo "<h3>Apache模块</h3>";
    $modules = apache_get_modules();
    echo "<p>mod_rewrite: " . (in_array('mod_rewrite', $modules) ? "✓ 已启用" : "✗ 未启用") . "</p>";
}
?>
EOF

echo "已创建 test_env.php 测试文件"
echo "上传此文件到服务器并访问以检查环境"
echo

# 完成信息
echo "========================================"
echo "  部署完成！"
echo "========================================"
echo
echo -e "${GREEN}现在您可以：${NC}"
echo "1. 上传所有文件到服务器"
echo "2. 访问网站进行安装"
echo "3. 开始使用小说系统"
echo
echo "文档位置："
echo "  README.md       - 项目说明"
echo "  INSTALL.md      - 详细安装指南"
echo "  UPLOAD_GUIDE.md - 上传使用指南"
echo
echo "技术支持："
echo "  开发者：xxtting"
echo "  邮箱：xxtting@qq.com"
echo "  GitHub：https://github.com/xxtting/xiaoshuowang"
echo
echo "祝您部署顺利！📚"
echo