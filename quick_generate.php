<?php
/**
 * 快速生成APP目录脚本
 */

echo "========================================\n";
echo "开始生成APP目录...\n";
echo "========================================\n\n";

$sourceDir = __DIR__;
$targetDir = $sourceDir . '/app';

// 创建app目录
if (!file_exists($targetDir)) {
    mkdir($targetDir, 0755, true);
    echo "✓ 创建app目录\n";
}

// 复制根目录文件
$rootFiles = ['index.php', '.htaccess', 'README.md'];
foreach ($rootFiles as $file) {
    if (file_exists($sourceDir . '/' . $file)) {
        copy($sourceDir . '/' . $file, $targetDir . '/' . $file);
        echo "✓ 复制: $file\n";
    }
}

// 复制目录
$dirs = ['backend', 'install', 'admin', 'public', 'database'];
foreach ($dirs as $dir) {
    if (file_exists($sourceDir . '/' . $dir)) {
        copyDirectory($sourceDir . '/' . $dir, $targetDir . '/' . $dir);
        echo "✓ 复制目录: $dir\n";
    }
}

// 创建运行时目录
$runtimeDirs = ['runtime', 'runtime/cache', 'public/uploads'];
foreach ($runtimeDirs as $dir) {
    $fullPath = $targetDir . '/' . $dir;
    if (!file_exists($fullPath)) {
        mkdir($fullPath, 0755, true);
        echo "✓ 创建目录: $dir\n";
    }
}

echo "\n========================================\n";
echo "✅ APP目录生成完成！\n";
echo "========================================\n";

/**
 * 递归复制目录
 */
function copyDirectory($src, $dst) {
    if (!file_exists($dst)) {
        mkdir($dst, 0755, true);
    }
    
    $dir = opendir($src);
    while (($file = readdir($dir)) !== false) {
        if ($file != '.' && $file != '..') {
            $srcPath = $src . '/' . $file;
            $dstPath = $dst . '/' . $file;
            
            if (is_dir($srcPath)) {
                copyDirectory($srcPath, $dstPath);
            } else {
                copy($srcPath, $dstPath);
            }
        }
    }
    closedir($dir);
}
?>