<?php
/**
 * 文件监视和自动生成工具
 * 监视项目文件变化，自动生成app目录
 */

// 启用错误显示
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 配置
define('PROJECT_DIR', __DIR__);
define('WATCH_INTERVAL', 2); // 检查间隔（秒）
define('LOG_FILE', PROJECT_DIR . '/watch.log');

// 需要监视的目录和文件
$watchPatterns = [
    '*.php',
    '*.html',
    '*.js',
    '*.css',
    '*.sql',
    '*.json',
    '*.md'
];

// 需要排除的目录
$excludeDirs = [
    'app',
    'node_modules',
    'vendor',
    '.git',
    'runtime',
    'cache',
    'tmp',
    'temp',
    'logs'
];

// 记录上次检查的文件状态
$lastFileStates = [];
$lastGenerationTime = 0;
$generationCount = 0;

/**
 * 主函数
 */
function main() {
    global $lastFileStates, $lastGenerationTime, $generationCount;
    
    echo "============================================\n";
    echo "小说网系统 - 文件监视和自动生成工具\n";
    echo "============================================\n";
    echo "监视目录: " . PROJECT_DIR . "\n";
    echo "检查间隔: " . WATCH_INTERVAL . "秒\n";
    echo "日志文件: " . LOG_FILE . "\n";
    echo "按 Ctrl+C 停止监视\n";
    echo "============================================\n\n";
    
    logMessage("监视工具启动");
    
    // 初始生成
    echo "[初始] 执行首次生成...\n";
    generateApp();
    $lastGenerationTime = time();
    $generationCount = 1;
    
    // 开始监视循环
    while (true) {
        echo "\n[" . date('H:i:s') . "] 检查文件变化...\n";
        
        $currentFileStates = getFileStates();
        $changes = detectChanges($lastFileStates, $currentFileStates);
        
        if (!empty($changes)) {
            echo "检测到 " . count($changes) . " 个文件变化：\n";
            foreach ($changes as $change) {
                echo "  " . $change['type'] . ": " . $change['file'] . "\n";
            }
            
            // 等待一小段时间，确保所有修改完成
            echo "等待修改完成...\n";
            sleep(1);
            
            // 执行生成
            echo "执行自动生成...\n";
            generateApp();
            $lastGenerationTime = time();
            $generationCount++;
            
            logMessage("自动生成完成（第{$generationCount}次）");
        } else {
            echo "没有检测到文件变化\n";
        }
        
        // 更新文件状态
        $lastFileStates = $currentFileStates;
        
        // 等待下一次检查
        sleep(WATCH_INTERVAL);
    }
}

/**
 * 获取文件状态
 */
function getFileStates() {
    global $watchPatterns, $excludeDirs;
    
    $states = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(PROJECT_DIR, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    foreach ($iterator as $file) {
        $relativePath = substr($file->getPathname(), strlen(PROJECT_DIR) + 1);
        
        // 检查是否在排除目录中
        $excluded = false;
        foreach ($excludeDirs as $excludeDir) {
            if (strpos($relativePath, $excludeDir . '/') === 0 || 
                $relativePath === $excludeDir) {
                $excluded = true;
                break;
            }
        }
        
        if ($excluded) {
            continue;
        }
        
        // 检查文件类型
        $matched = false;
        foreach ($watchPatterns as $pattern) {
            if (fnmatch($pattern, basename($relativePath))) {
                $matched = true;
                break;
            }
        }
        
        if (!$matched && $file->isFile()) {
            continue;
        }
        
        if ($file->isFile()) {
            $states[$relativePath] = [
                'mtime' => $file->getMTime(),
                'size' => $file->getSize(),
                'hash' => md5_file($file->getPathname())
            ];
        }
    }
    
    return $states;
}

/**
 * 检测文件变化
 */
function detectChanges($oldStates, $newStates) {
    $changes = [];
    
    // 检查修改和新增的文件
    foreach ($newStates as $file => $newState) {
        if (!isset($oldStates[$file])) {
            // 新增文件
            $changes[] = [
                'file' => $file,
                'type' => '新增',
                'old' => null,
                'new' => $newState
            ];
        } elseif ($oldStates[$file]['mtime'] != $newState['mtime'] || 
                  $oldStates[$file]['hash'] != $newState['hash']) {
            // 修改文件
            $changes[] = [
                'file' => $file,
                'type' => '修改',
                'old' => $oldStates[$file],
                'new' => $newState
            ];
        }
    }
    
    // 检查删除的文件
    foreach ($oldStates as $file => $oldState) {
        if (!isset($newStates[$file])) {
            // 删除文件
            $changes[] = [
                'file' => $file,
                'type' => '删除',
                'old' => $oldState,
                'new' => null
            ];
        }
    }
    
    return $changes;
}

/**
 * 生成app目录
 */
function generateApp() {
    echo "执行生成脚本...\n";
    
    // 使用PHP生成脚本
    $script = PROJECT_DIR . '/generate_app.php';
    if (file_exists($script)) {
        // 保存当前输出缓冲区
        ob_start();
        
        // 包含生成脚本
        include $script;
        
        // 获取输出
        $output = ob_get_clean();
        
        // 显示输出
        echo $output;
        
        // 记录到日志
        logMessage("生成脚本输出:\n" . $output);
        
        return true;
    }
    
    // 尝试使用批处理文件
    $batScript = PROJECT_DIR . '/generate_app.bat';
    if (file_exists($batScript)) {
        echo "使用批处理脚本生成...\n";
        exec($batScript, $output, $returnCode);
        
        echo implode("\n", $output) . "\n";
        logMessage("批处理脚本输出:\n" . implode("\n", $output));
        
        return $returnCode === 0;
    }
    
    // 尝试使用shell脚本
    $shellScript = PROJECT_DIR . '/generate_app.sh';
    if (file_exists($shellScript)) {
        echo "使用Shell脚本生成...\n";
        exec("bash " . escapeshellarg($shellScript), $output, $returnCode);
        
        echo implode("\n", $output) . "\n";
        logMessage("Shell脚本输出:\n" . implode("\n", $output));
        
        return $returnCode === 0;
    }
    
    echo "❌ 错误：没有找到生成脚本\n";
    logMessage("错误：没有找到生成脚本");
    return false;
}

/**
 * 记录日志
 */
function logMessage($message) {
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] $message\n";
    
    file_put_contents(LOG_FILE, $logEntry, FILE_APPEND);
    echo $logEntry;
}

/**
 * 信号处理函数
 */
function signalHandler($signal) {
    echo "\n\n收到信号 {$signal}，正在停止监视...\n";
    
    $totalTime = time() - $GLOBALS['lastGenerationTime'];
    $summary = "监视工具停止\n";
    $summary .= "运行时间: " . formatDuration($totalTime) . "\n";
    $summary .= "生成次数: " . $GLOBALS['generationCount'] . "\n";
    $summary .= "最后生成: " . date('Y-m-d H:i:s', $GLOBALS['lastGenerationTime']) . "\n";
    
    logMessage($summary);
    echo $summary;
    
    exit(0);
}

/**
 * 格式化时间间隔
 */
function formatDuration($seconds) {
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $seconds = $seconds % 60;
    
    $parts = [];
    if ($hours > 0) $parts[] = "{$hours}小时";
    if ($minutes > 0) $parts[] = "{$minutes}分钟";
    if ($seconds > 0 || empty($parts)) $parts[] = "{$seconds}秒";
    
    return implode(' ', $parts);
}

// 注册信号处理
declare(ticks = 1);
pcntl_signal(SIGINT, 'signalHandler');
pcntl_signal(SIGTERM, 'signalHandler');

// 检查是否支持pcntl
if (!function_exists('pcntl_signal')) {
    echo "⚠️ 警告：PCNTL扩展不可用，Ctrl+C可能无法正常工作\n";
}

// 运行主函数
try {
    main();
} catch (Exception $e) {
    echo "❌ 错误: " . $e->getMessage() . "\n";
    logMessage("错误: " . $e->getMessage());
    exit(1);
}