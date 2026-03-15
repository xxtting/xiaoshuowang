#!/bin/bash

echo "============================================"
echo "小说网系统 - 自动生成APP目录工具 (Linux/Mac)"
echo "============================================"
echo

SOURCE_DIR="$(cd "$(dirname "$0")" && pwd)"
TARGET_DIR="$SOURCE_DIR/app"

echo "[1/6] 检查源目录..."
if [ ! -f "$SOURCE_DIR/index.php" ]; then
    echo "❌ 错误：源目录不是有效的小说网项目"
    exit 1
fi

echo "[2/6] 清理目标目录..."
if [ -d "$TARGET_DIR" ]; then
    echo "正在删除旧的app目录..."
    rm -rf "$TARGET_DIR"
fi
mkdir -p "$TARGET_DIR"

echo "[3/6] 复制网站核心文件..."
echo "复制根目录文件..."
cp "$SOURCE_DIR/index.php" "$TARGET_DIR/index.php"
cp "$SOURCE_DIR/.htaccess" "$TARGET_DIR/.htaccess" 2>/dev/null || true

echo "复制README.md..."
cp "$SOURCE_DIR/README.md" "$TARGET_DIR/README.md"

echo "[4/6] 复制后端文件..."
cp -r "$SOURCE_DIR/backend" "$TARGET_DIR/"

echo "[5/6] 复制其他必要目录..."
cp -r "$SOURCE_DIR/install" "$TARGET_DIR/"
cp -r "$SOURCE_DIR/admin" "$TARGET_DIR/"
cp -r "$SOURCE_DIR/public" "$TARGET_DIR/"
cp -r "$SOURCE_DIR/database" "$TARGET_DIR/"

echo "[6/6] 创建必要的运行时目录..."
mkdir -p "$TARGET_DIR/runtime/cache"
mkdir -p "$TARGET_DIR/public/uploads"

echo
echo "✅ 生成完成！"
echo
echo "统计信息："
echo "- app目录已创建在：$TARGET_DIR"
echo "- 包含所有网站运行必需的文件"
echo "- 不包含：部署脚本、文档、临时文件"
echo
echo "文件结构："
echo "app/"
echo "├── index.php"
echo "├── .htaccess"
echo "├── README.md"
echo "├── backend/"
echo "├── install/"
echo "├── admin/"
echo "├── public/"
echo "├── database/"
echo "└── runtime/"
echo
echo "注意事项："
echo "1. app目录是纯净的网站文件，可直接上传服务器"
echo "2. 已排除部署脚本和开发文档"
echo "3. 请确保数据库配置文件 backend/config/database.php 正确"
echo
echo "文件数量统计："
find "$TARGET_DIR" -type f | wc -l | xargs echo "总文件数："
echo

# 设置文件权限（可选）
echo "设置文件权限..."
chmod 755 "$TARGET_DIR/.htaccess" 2>/dev/null || true
chmod 755 "$TARGET_DIR/index.php"
find "$TARGET_DIR/backend" -type f -name "*.php" -exec chmod 644 {} \;
find "$TARGET_DIR/install" -type f -name "*.php" -exec chmod 644 {} \;
find "$TARGET_DIR/admin" -type f -name "*.php" -exec chmod 644 {} \;
chmod 777 "$TARGET_DIR/public/uploads" 2>/dev/null || true
chmod 777 "$TARGET_DIR/runtime" 2>/dev/null || true

echo "✅ 权限设置完成"