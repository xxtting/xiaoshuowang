<?php
/**
 * 安装系统修复工具 - 第二版
 * 专门修复管理员账号写入问题
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>安装系统修复工具</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Microsoft YaHei', sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden; }
        .header { background: #1890ff; color: white; padding: 20px; }
        .header h1 { font-size: 24px; margin: 0; }
        .content { padding: 30px; }
        .section { margin-bottom: 30px; padding: 20px; border: 1px solid #eee; border-radius: 4px; }
        .section h3 { margin-bottom: 15px; color: #1890ff; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .btn { background: #1890ff; color: white; border: none; padding: 12px 24px; border-radius: 4px; cursor: pointer; font-size: 16px; text-decoration: none; display: inline-block; }
        .btn:hover { background: #40a9ff; }
        .btn-danger { background: #f5222d; }
        .btn-danger:hover { background: #ff4d4f; }
        .btn-success { background: #52c41a; }
        .btn-success:hover { background: #73d13d; }
        .message { padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        .message.success { background: #f6ffed; border: 1px solid #b7eb8f; color: #52c41a; }
        .message.error { background: #fff2f0; border: 1px solid #ffccc7; color: #f5222d; }
        .message.info { background: #e6f7ff; border: 1px solid #91d5ff; color: #1890ff; }
        pre { background: #f6f8fa; padding: 15px; border-radius: 4px; overflow: auto; font-family: 'Courier New', monospace; }
        .comparison { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .comparison-item { padding: 15px; border: 1px solid #ddd; border-radius: 4px; }
        .bad { background: #fff2f0; border-color: #ffccc7; }
        .good { background: #f6ffed; border-color: #b7eb8f; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚨 安装系统修复工具 - 管理员账号问题</h1>
        </div>
        <div class="content">
            
            <?php
            $action = $_GET['action'] ?? '';
            
            if ($action == 'install') {
                // 备份原始安装系统
                $backupPath = __DIR__ . '/install/index.php.backup.' . date('YmdHis');
                $originalPath = __DIR__ . '/install/index.php';
                $fixedPath = __DIR__ . '/install_fixed.php';
                
                if (file_exists($originalPath)) {
                    if (copy($originalPath, $backupPath)) {
                        echo '<div class="message success">✅ 原始安装系统已备份到: ' . basename($backupPath) . '</div>';
                    } else {
                        echo '<div class="message error">❌ 备份原始安装系统失败</div>';
                    }
                }
                
                // 复制修复版安装系统
                if (copy($fixedPath, $originalPath)) {
                    echo '<div class="message success">✅ 修复版安装系统已成功安装</div>';
                    
                    // 检查修复后的文件
                    $newContent = file_get_contents($originalPath);
                    $issuesFixed = [
                        '表单字段' => [
                            '原始' => '管理员姓名 (name="admin_name")',
                            '修复后' => '管理员账号 (name="admin_username") + 真实姓名 (name="admin_realname")'
                        ],
                        '密码验证' => [
                            '原始' => '无验证',
                            '修复后' => '密码一致性 + 长度检查'
                        ],
                        '数据库写入' => [
                            '原始' => '固定UPDATE username="admin"',
                            '修复后' => '智能INSERT/UPDATE + 用户名检查'
                        ],
                        '错误处理' => [
                            '原始' => '基本错误处理',
                            '修复后' => '详细表单验证 + 错误提示'
                        ]
                    ];
                    
                    echo '<div class="section">';
                    echo '<h3>✅ 已修复的问题对比</h3>';
                    echo '<div class="comparison">';
                    foreach ($issuesFixed as $issue => $details) {
                        echo '<div class="comparison-item bad">';
                        echo '<h4>❌ 原始：' . $issue . '</h4>';
                        echo '<p>' . $details['原始'] . '</p>';
                        echo '</div>';
                        
                        echo '<div class="comparison-item good">';
                        echo '<h4>✅ 修复后：' . $issue . '</h4>';
                        echo '<p>' . $details['修复后'] . '</p>';
                        echo '</div>';
                    }
                    echo '</div>';
                    echo '</div>';
                    
                } else {
                    echo '<div class="message error">❌ 安装修复版失败，请检查文件权限</div>';
                }
                
                echo '<div class="section">';
                echo '<h3>🚀 下一步操作</h3>';
                echo '<p>修复已完成！请按以下步骤操作：</p>';
                echo '<ol style="margin-left: 20px; margin-top: 10px;">';
                echo '<li>删除 <code>install.lock</code> 文件（如果存在）</li>';
                echo '<li>访问安装页面：<a href="../install/" target="_blank">/install/</a></li>';
                echo '<li>重新进行安装流程</li>';
                echo '<li>在"管理员设置"步骤中，注意区分"管理员账号"和"真实姓名"</li>';
                echo '<li>设置您想要的用户名和密码</li>';
                echo '</ol>';
                echo '<p style="margin-top: 15px;"><a href="' . basename(__FILE__) . '" class="btn">返回工具首页</a></p>';
                echo '</div>';
                
            } else {
                // 显示工具首页
                echo '<div class="section">';
                echo '<h3>🔍 问题诊断</h3>';
                echo '<p><strong>检测到的问题：</strong></p>';
                
                // 检查原始安装系统
                $installPath = __DIR__ . '/install/index.php';
                $issues = [];
                
                if (file_exists($installPath)) {
                    $content = file_get_contents($installPath);
                    
                    // 检查表单字段
                    if (strpos($content, 'name="admin_name"') !== false && strpos($content, 'name="admin_username"') === false) {
                        $issues[] = '❌ 表单字段错误：只有"管理员姓名"，没有"管理员账号"字段';
                    }
                    
                    // 检查密码验证
                    if (strpos($content, 'admin_password') !== false && strpos($content, 'confirm_password') === false) {
                        $issues[] = '❌ 缺少密码确认验证';
                    }
                    
                    // 检查SQL更新逻辑
                    if (strpos($content, "WHERE username = 'admin'") !== false) {
                        $issues[] = '❌ 数据库写入问题：固定更新 username="admin"，不支持自定义用户名';
                    }
                    
                    if (empty($issues)) {
                        echo '<div class="message success">✅ 安装系统似乎已修复</div>';
                    } else {
                        echo '<div class="message error">';
                        echo '<strong>发现以下问题：</strong><br>';
                        foreach ($issues as $issue) {
                            echo '- ' . $issue . '<br>';
                        }
                        echo '</div>';
                    }
                } else {
                    echo '<div class="message error">❌ 未找到安装系统</div>';
                }
                echo '</div>';
                
                echo '<div class="section">';
                echo '<h3>📋 问题分析</h3>';
                echo '<div class="comparison">';
                echo '<div class="comparison-item bad">';
                echo '<h4>❌ 原始安装系统的问题</h4>';
                echo '<ul style="margin-left: 20px;">';
                echo '<li>表单字段名为"管理员姓名"(admin_name)</li>';
                echo '<li>用户以为输入的是账号，实际被用作真实姓名</li>';
                echo '<li>SQL语句固定更新 username="admin"</li>';
                echo '<li>无法创建自定义用户名的管理员</li>';
                echo '<li>缺少密码一致性验证</li>';
                echo '</ul>';
                echo '</div>';
                
                echo '<div class="comparison-item good">';
                echo '<h4>✅ 修复后的改进</h4>';
                echo '<ul style="margin-left: 20px;">';
                echo '<li>明确的"管理员账号"字段 (admin_username)</li>';
                echo '<li>可选的"真实姓名"字段 (admin_realname)</li>';
                echo '<li>密码一致性验证 + 长度检查</li>';
                echo '<li>用户名合法性检查 (字母、数字、下划线)</li>';
                echo '<li>智能INSERT/UPDATE逻辑</li>';
                echo '<li>详细的错误提示</li>';
                echo '</ul>';
                echo '</div>';
                echo '</div>';
                echo '</div>';
                
                echo '<div class="section">';
                echo '<h3>🛠️ 修复操作</h3>';
                echo '<p>此工具将自动备份原始安装系统，并用修复版替换。</p>';
                echo '<div style="background: #f6f8fa; padding: 15px; border-radius: 4px; margin: 15px 0;">';
                echo '<strong>操作步骤：</strong><br>';
                echo '1. 备份原始 <code>install/index.php</code><br>';
                echo '2. 用修复版替换<br>';
                echo '3. 删除 <code>install.lock</code> 文件<br>';
                echo '4. 重新访问安装页面进行安装<br>';
                echo '</div>';
                echo '<p><strong>注意：</strong>请确保当前目录有写入权限。</p>';
                echo '<a href="?action=install" class="btn btn-success" onclick="return confirm(\'确定要更新安装系统吗？原始文件将自动备份。\')">🚀 立即修复安装系统</a>';
                echo '</div>';
                
                echo '<div class="section">';
                echo '<h3>📝 修复内容预览</h3>';
                echo '<h4>原始表单 (有问题)：</h4>';
                echo '<pre>';
                echo htmlspecialchars('<div class="form-group">
    <label>管理员姓名</label>
    <input type="text" name="admin_name" value="系统管理员" required>
</div>');
                echo '</pre>';
                
                echo '<h4>修复后表单 (正确)：</h4>';
                echo '<pre>';
                echo htmlspecialchars('<div class="form-group">
    <label>管理员账号</label>
    <input type="text" name="admin_username" value="admin" required>
</div>
<div class="form-group">
    <label>管理员真实姓名（可选）</label>
    <input type="text" name="admin_realname" value="系统管理员">
</div>');
                echo '</pre>';
                echo '</div>';
            }
            ?>
            
        </div>
    </div>
</body>
</html>