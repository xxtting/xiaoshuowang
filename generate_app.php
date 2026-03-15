<?php
/**
 * 小说网系统 - APP目录生成工具（PHP版本）
 * 更智能的文件复制和过滤
 */

// 启用错误显示
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 配置
define('SOURCE_DIR', __DIR__);
define('TARGET_DIR', SOURCE_DIR . '/app');

// 需要复制的目录和文件
$copyItems = [
    // 根目录文件
    'index.php',
    '.htaccess',
    'README.md',
    
    // 目录
    'backend' => [
        'type' => 'dir',
        'exclude' => ['*.log', 'cache/*', 'tmp/*']
    ],
    'install' => [
        'type' => 'dir',
        'exclude' => []
    ],
    'admin' => [
        'type' => 'dir',
        'exclude' => []
    ],
    'public' => [
        'type' => 'dir',
        'exclude' => []
    ],
    'database' => [
        'type' => 'dir',
        'exclude' => []
    ],
];

// 需要排除的文件和模式
$excludePatterns = [
    // 部署和开发文件
    'deploy.*',
    '*.bat',
    '*.sh',
    'SETUP_*',
    'GITHUB_*',
    'DEPLOYMENT_*',
    'UPLOAD_*',
    'PACKAGE_*',
    'INSTALL.md',
    
    // 临时和缓存文件
    '*.tmp',
    '*.log',
    '*.cache',
    'Thumbs.db',
    '.DS_Store',
    
    // 版本控制
    '.git',
    '.gitignore',
    '.svn',
    
    // 开发工具
    'generate_*.php',
    'test_*.php',
    'fix_*.php',
    'reset_*.php',
    'update_*.php',
];

// 需要创建的目录
$createDirs = [
    'runtime',
    'runtime/cache',
    'public/uploads',
    'backend/runtime', // 如果不存在
];

// 统计信息
$stats = [
    'files_copied' => 0,
    'dirs_created' => 0,
    'errors' => [],
    'warnings' => [],
];

/**
 * 主生成函数
 */
function generateApp() {
    global $copyItems, $excludePatterns, $createDirs, $stats;
    
    echo "============================================\n";
    echo "小说网系统 - APP目录生成工具（PHP版本）\n";
    echo "============================================\n\n";
    
    // 步骤1：检查源目录
    echo "[1/8] 检查源目录...\n";
    if (!file_exists(SOURCE_DIR . '/index.php')) {
        die("❌ 错误：源目录不是有效的小说网项目\n");
    }
    
    // 步骤2：清理目标目录
    echo "[2/8] 清理目标目录...\n";
    if (file_exists(TARGET_DIR)) {
        echo "正在删除旧的app目录...\n";
        deleteDirectory(TARGET_DIR);
    }
    
    if (!mkdir(TARGET_DIR, 0755, true)) {
        die("❌ 无法创建目标目录: " . TARGET_DIR . "\n");
    }
    $stats['dirs_created']++;
    
    // 步骤3：复制文件
    echo "[3/8] 复制网站核心文件...\n";
    foreach ($copyItems as $key => $value) {
        if (is_int($key)) {
            // 单个文件
            copyFile($value);
        } else {
            // 目录
            copyDirectory($key, $value);
        }
    }
    
    // 步骤4：创建必要的运行时目录
    echo "[4/8] 创建运行时目录...\n";
    foreach ($createDirs as $dir) {
        $fullPath = TARGET_DIR . '/' . $dir;
        if (!file_exists($fullPath)) {
            if (mkdir($fullPath, 0755, true)) {
                $stats['dirs_created']++;
                echo "  创建目录: $dir\n";
            } else {
                $stats['warnings'][] = "无法创建目录: $dir";
            }
        }
    }
    
    // 步骤5：设置文件权限
    echo "[5/8] 设置文件权限...\n";
    setPermissions();
    
    // 步骤6：生成清单文件
    echo "[6/8] 生成文件清单...\n";
    generateFileList();
    
    // 步骤7：验证生成结果
    echo "[7/8] 验证生成结果...\n";
    validateGeneration();
    
    // 步骤8：显示统计信息
    echo "[8/8] 生成完成！\n\n";
    showStatistics();
}

/**
 * 复制单个文件
 */
function copyFile($filename) {
    global $stats;
    
    $source = SOURCE_DIR . '/' . $filename;
    $target = TARGET_DIR . '/' . $filename;
    
    if (!file_exists($source)) {
        $stats['warnings'][] = "源文件不存在: $filename";
        return;
    }
    
    // 检查是否在排除模式中
    if (isExcluded($filename)) {
        echo "  跳过排除文件: $filename\n";
        return;
    }
    
    // 确保目标目录存在
    $targetDir = dirname($target);
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    
    if (copy($source, $target)) {
        $stats['files_copied']++;
        echo "  复制文件: $filename\n";
    } else {
        $stats['errors'][] = "无法复制文件: $filename";
    }
}

/**
 * 复制目录
 */
function copyDirectory($dirName, $config) {
    global $stats;
    
    $source = SOURCE_DIR . '/' . $dirName;
    $target = TARGET_DIR . '/' . $dirName;
    
    if (!is_dir($source)) {
        $stats['warnings'][] = "源目录不存在: $dirName";
        return;
    }
    
    echo "  复制目录: $dirName\n";
    
    // 创建目标目录
    if (!file_exists($target)) {
        mkdir($target, 0755, true);
    }
    
    // 遍历目录
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    foreach ($iterator as $item) {
        $relativePath = substr($item->getPathname(), strlen($source) + 1);
        $targetPath = $target . '/' . $relativePath;
        
        // 检查是否排除
        if (isExcluded($dirName . '/' . $relativePath)) {
            continue;
        }
        
        // 检查目录特定的排除规则
        if (isset($config['exclude']) && isExcludedByPattern($relativePath, $config['exclude'])) {
            continue;
        }
        
        if ($item->isDir()) {
            if (!file_exists($targetPath)) {
                mkdir($targetPath, 0755, true);
            }
        } else {
            if (copy($item->getPathname(), $targetPath)) {
                $stats['files_copied']++;
            } else {
                $stats['errors'][] = "无法复制文件: {$dirName}/{$relativePath}";
            }
        }
    }
}

/**
 * 检查文件是否应该被排除
 */
function isExcluded($path) {
    global $excludePatterns;
    
    foreach ($excludePatterns as $pattern) {
        if (fnmatch($pattern, basename($path)) || fnmatch($pattern, $path)) {
            return true;
        }
    }
    
    return false;
}

/**
 * 检查是否被特定模式排除
 */
function isExcludedByPattern($path, $patterns) {
    foreach ($patterns as $pattern) {
        if (fnmatch($pattern, $path)) {
            return true;
        }
    }
    return false;
}

/**
 * 删除目录
 */
function deleteDirectory($dir) {
    if (!is_dir($dir)) {
        return;
    }
    
    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        if (is_dir($path)) {
            deleteDirectory($path);
        } else {
            unlink($path);
        }
    }
    rmdir($dir);
}

/**
 * 设置文件权限
 */
function setPermissions() {
    $permissions = [
        TARGET_DIR . '/.htaccess' => 0644,
        TARGET_DIR . '/index.php' => 0644,
        TARGET_DIR . '/backend/.htaccess' => 0644,
        TARGET_DIR . '/public/.htaccess' => 0644,
        TARGET_DIR . '/public/uploads' => 0777,
        TARGET_DIR . '/runtime' => 0777,
        TARGET_DIR . '/runtime/cache' => 0777,
    ];
    
    foreach ($permissions as $file => $perm) {
        if (file_exists($file)) {
            if (chmod($file, $perm)) {
                echo "  设置权限: " . substr($file, strlen(TARGET_DIR) + 1) . " -> " . decoct($perm) . "\n";
            }
        }
    }
}

/**
 * 生成文件清单
 */
function generateFileList() {
    $listFile = TARGET_DIR . '/FILES.md';
    $content = "# 网站文件清单\n\n";
    $content .= "生成时间: " . date('Y-m-d H:i:s') . "\n\n";
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(TARGET_DIR, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    $fileCount = 0;
    $dirCount = 0;
    
    foreach ($iterator as $item) {
        $relativePath = substr($item->getPathname(), strlen(TARGET_DIR) + 1);
        
        if ($item->isDir()) {
            $content .= "📁 `$relativePath/`\n";
            $dirCount++;
        } else {
            $size = filesize($item->getPathname());
            $content .= "📄 `$relativePath` (" . formatSize($size) . ")\n";
            $fileCount++;
        }
    }
    
    $content .= "\n## 统计信息\n";
    $content .= "- 文件总数: $fileCount\n";
    $content .= "- 目录总数: $dirCount\n";
    $content .= "- 总大小: " . formatSize(getDirectorySize(TARGET_DIR)) . "\n";
    
    file_put_contents($listFile, $content);
    echo "  生成文件清单: FILES.md\n";
}

/**
 * 验证生成结果
 */
function validateGeneration() {
    $requiredFiles = [
        'index.php',
        'backend/index.php',
        'backend/app/Core/Database.php',
        'install/index.php',
        'admin/index.php',
        'public/index.html',
        'database/structure.sql',
    ];
    
    echo "  验证必需文件...\n";
    foreach ($requiredFiles as $file) {
        $path = TARGET_DIR . '/' . $file;
        if (file_exists($path)) {
            echo "    ✅ $file\n";
        } else {
            echo "    ❌ $file (缺失)\n";
        }
    }
}

/**
 * 显示统计信息
 */
function showStatistics() {
    global $stats;
    
    echo "✅ 生成完成！\n\n";
    echo "统计信息：\n";
    echo "- 复制文件数: " . $stats['files_copied'] . "\n";
    echo "- 创建目录数: " . $stats['dirs_created'] . "\n";
    echo "- 总文件数: " . countFiles(TARGET_DIR) . "\n";
    echo "- 总大小: " . formatSize(getDirectorySize(TARGET_DIR)) . "\n";
    echo "- 目标目录: " . TARGET_DIR . "\n\n";
    
    echo "文件结构概览：\n";
    echo "app/\n";
    echo "├── index.php              # 网站主入口\n";
    echo "├── .htaccess             # URL重写规则\n";
    echo "├── README.md             # 使用说明\n";
    echo "├── FILES.md              # 文件清单\n";
    echo "├── backend/              # PHP后端API\n";
    echo "├── install/              # 安装系统\n";
    echo "├── admin/                # 管理后台\n";
    echo "├── public/               # 前端资源\n";
    echo "├── database/             # 数据库文件\n";
    echo "└── runtime/              # 运行时文件\n\n";
    
    if (!empty($stats['warnings'])) {
        echo "警告：\n";
        foreach ($stats['warnings'] as $warning) {
            echo "  ⚠️ $warning\n";
        }
        echo "\n";
    }
    
    if (!empty($stats['errors'])) {
        echo "错误：\n";
        foreach ($stats['errors'] as $error) {
            echo "  ❌ $error\n";
        }
        echo "\n";
    }
    
    echo "使用说明：\n";
    echo "1. app目录是纯净的网站文件，可直接上传服务器\n";
    echo "2. 已排除开发文件和部署脚本\n";
    echo "3. 包含完整的运行环境\n";
    echo "4. 查看 FILES.md 获取详细文件列表\n";
}

/**
 * 辅助函数：格式化文件大小
 */
function formatSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, 2) . ' ' . $units[$pow];
}

/**
 * 辅助函数：计算目录大小
 */
function getDirectorySize($path) {
    $size = 0;
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($iterator as $file) {
        $size += $file->getSize();
    }
    
    return $size;
}

/**
 * 辅助函数：统计文件数量
 */
function countFiles($path) {
    $count = 0;
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $count++;
        }
    }
    
    return $count;
}

// 运行生成
generateApp();