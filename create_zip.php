<?php
/**
 * 创建网站文件压缩包
 */

$sourceDir = __DIR__ . '/app';
$zipFile = __DIR__ . '/xiaoshuowang_app.zip';

// 检查源目录
if (!is_dir($sourceDir)) {
    die("错误：app目录不存在\n");
}

// 删除旧的压缩包
if (file_exists($zipFile)) {
    unlink($zipFile);
}

// 创建压缩包
$zip = new ZipArchive();
if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
    die("错误：无法创建压缩包\n");
}

// 递归添加文件
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::LEAVES_ONLY
);

$count = 0;
foreach ($files as $file) {
    if (!$file->isDir()) {
        $filePath = $file->getRealPath();
        $relativePath = substr($filePath, strlen($sourceDir) + 1);
        $zip->addFile($filePath, $relativePath);
        $count++;
    }
}

$zip->close();

// 显示结果
if (file_exists($zipFile)) {
    $size = round(filesize($zipFile) / 1024, 2);
    echo "========================================\n";
    echo "打包完成！\n";
    echo "========================================\n";
    echo "文件名: xiaoshuowang_app.zip\n";
    echo "大小: {$size} KB\n";
    echo "文件数: {$count}\n";
    echo "路径: {$zipFile}\n";
    echo "========================================\n";
} else {
    echo "打包失败\n";
}
?>