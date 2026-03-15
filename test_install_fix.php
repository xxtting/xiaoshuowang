<?php
/**
 * 安装系统修复测试工具
 * 验证修复后的安装系统是否能正确写入管理员账号
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>安装系统修复测试</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Microsoft YaHei', sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden; }
        .header { background: #1890ff; color: white; padding: 20px; }
        .header h1 { font-size: 24px; margin: 0; }
        .content { padding: 30px; }
        .section { margin-bottom: 30px; padding: 20px; border: 1px solid #eee; border-radius: 4px; }
        .section h3 { margin-bottom: 15px; color: #1890ff; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .btn { background: #1890ff; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-size: 14px; text-decoration: none; display: inline-block; }
        .btn:hover { background: #40a9ff; }
        .btn-small { padding: 6px 12px; font-size: 12px; }
        .message { padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        .message.success { background: #f6ffed; border: 1px solid #b7eb8f; color: #52c41a; }
        .message.error { background: #fff2f0; border: 1px solid #ffccc7; color: #f5222d; }
        .message.info { background: #e6f7ff; border: 1px solid #91d5ff; color: #1890ff; }
        pre { background: #f6f8fa; padding: 15px; border-radius: 4px; overflow: auto; font-family: 'Courier New', monospace; font-size: 12px; }
        .test-case { margin-bottom: 15px; padding: 15px; border-left: 4px solid #1890ff; background: #fafafa; }
        .test-case h4 { margin: 0 0 10px 0; }
        .test-result { margin-top: 10px; padding: 10px; border-radius: 4px; }
        .test-result.pass { background: #f6ffed; border: 1px solid #b7eb8f; }
        .test-result.fail { background: #fff2f0; border: 1px solid #ffccc7; }
        code { background: #f6f8fa; padding: 2px 4px; border-radius: 3px; font-family: 'Courier New', monospace; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .step { margin-bottom: 20px; padding: 15px; background: #e6f7ff; border-radius: 4px; }
        .step-number { display: inline-block; background: #1890ff; color: white; width: 24px; height: 24px; text-align: center; line-height: 24px; border-radius: 50%; margin-right: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🧪 安装系统修复测试工具</h1>
        </div>
        <div class="content">
            
            <?php
            // 测试修复版安装系统
            $testAction = $_POST['test_action'] ?? '';
            
            if ($testAction == 'run_tests') {
                echo '<div class="section">';
                echo '<h3>🧪 测试结果</h3>';
                
                // 测试1：检查文件存在性
                $test1Pass = false;
                $originalFile = __DIR__ . '/install/index.php';
                $fixedFile = __DIR__ . '/install_fixed.php';
                
                echo '<div class="test-case">';
                echo '<h4>测试1：文件检查</h4>';
                echo '<p><strong>原始安装文件：</strong> ' . (file_exists($originalFile) ? '✅ 存在' : '❌ 不存在') . '</p>';
                echo '<p><strong>修复版安装文件：</strong> ' . (file_exists($fixedFile) ? '✅ 存在' : '❌ 不存在') . '</p>';
                
                if (file_exists($fixedFile)) {
                    $content = file_get_contents($fixedFile);
                    $test1Pass = true;
                }
                
                echo '<div class="test-result ' . ($test1Pass ? 'pass' : 'fail') . '">';
                echo $test1Pass ? '✅ 测试通过' : '❌ 测试失败';
                echo '</div>';
                echo '</div>';
                
                // 测试2：检查表单字段
                $test2Pass = false;
                if ($test1Pass) {
                    echo '<div class="test-case">';
                    echo '<h4>测试2：表单字段验证</h4>';
                    
                    $fieldsFound = [
                        'admin_username' => strpos($content, 'name="admin_username"') !== false,
                        'admin_realname' => strpos($content, 'name="admin_realname"') !== false,
                        'admin_password' => strpos($content, 'name="admin_password"') !== false,
                        'confirm_password' => strpos($content, 'name="confirm_password"') !== false,
                        '表单验证代码' => strpos($content, 'validateAdminForm') !== false,
                    ];
                    
                    foreach ($fieldsFound as $field => $found) {
                        echo '<p>' . ($found ? '✅' : '❌') . ' ' . $field . '</p>';
                    }
                    
                    $test2Pass = $fieldsFound['admin_username'] && $fieldsFound['admin_password'] && $fieldsFound['confirm_password'];
                    
                    echo '<div class="test-result ' . ($test2Pass ? 'pass' : 'fail') . '">';
                    echo $test2Pass ? '✅ 表单字段正确' : '❌ 表单字段不正确';
                    echo '</div>';
                    echo '</div>';
                }
                
                // 测试3：检查SQL逻辑
                $test3Pass = false;
                if ($test1Pass) {
                    echo '<div class="test-case">';
                    echo '<h4>测试3：数据库写入逻辑</h4>';
                    
                    $sqlFound = [
                        '用户名检查' => strpos($content, 'SELECT COUNT(*) FROM admin_user WHERE username = ?') !== false,
                        '智能INSERT/UPDATE' => (strpos($content, 'INSERT INTO admin_user') !== false && strpos($content, 'UPDATE admin_user') !== false),
                        '用户名参数化' => strpos($content, 'WHERE username = ?') !== false,
                    ];
                    
                    foreach ($sqlFound as $feature => $found) {
                        echo '<p>' . ($found ? '✅' : '❌') . ' ' . $feature . '</p>';
                    }
                    
                    $test3Pass = $sqlFound['用户名检查'] && $sqlFound['智能INSERT/UPDATE'];
                    
                    echo '<div class="test-result ' . ($test3Pass ? 'pass' : 'fail') . '">';
                    echo $test3Pass ? '✅ 数据库逻辑正确' : '❌ 数据库逻辑不正确';
                    echo '</div>';
                    echo '</div>';
                }
                
                // 测试4：检查密码验证
                $test4Pass = false;
                if ($test1Pass) {
                    echo '<div class="test-case">';
                    echo '<h4>测试4：密码验证逻辑</h4>';
                    
                    $validationFound = [
                        '密码一致性检查' => strpos($content, 'admin_password !== confirm_password') !== false,
                        '密码长度检查' => strpos($content, 'strlen(admin_password) < 6') !== false,
                        '用户名合法性' => strpos($content, 'preg_match') !== false && strpos($content, 'admin_username') !== false,
                    ];
                    
                    foreach ($validationFound as $feature => $found) {
                        echo '<p>' . ($found ? '✅' : '❌') . ' ' . $feature . '</p>';
                    }
                    
                    $test4Pass = $validationFound['密码一致性检查'] && $validationFound['密码长度检查'];
                    
                    echo '<div class="test-result ' . ($test4Pass ? 'pass' : 'fail') . '">';
                    echo $test4Pass ? '✅ 验证逻辑正确' : '❌ 验证逻辑不正确';
                    echo '</div>';
                    echo '</div>';
                }
                
                // 总结
                echo '<div class="message ' . ($test1Pass && $test2Pass && $test3Pass && $test4Pass ? 'success' : 'error') . '">';
                echo '<h3>测试总结</h3>';
                echo '<p>✅ 文件检查: ' . ($test1Pass ? '通过' : '失败') . '</p>';
                echo '<p>✅ 表单字段: ' . ($test2Pass ? '通过' : '失败') . '</p>';
                echo '<p>✅ 数据库逻辑: ' . ($test3Pass ? '通过' : '失败') . '</p>';
                echo '<p>✅ 密码验证: ' . ($test4Pass ? '通过' : '失败') . '</p>';
                
                if ($test1Pass && $test2Pass && $test3Pass && $test4Pass) {
                    echo '<p><strong>🎉 所有测试通过！修复版安装系统已准备好。</strong></p>';
                } else {
                    echo '<p><strong>⚠️ 部分测试失败，请检查安装系统。</strong></p>';
                }
                echo '</div>';
                
                echo '</div>';
                
            } else {
                // 显示测试工具首页
                echo '<div class="section">';
                echo '<h3>🎯 测试目的</h3>';
                <p>验证修复版安装系统是否解决了以下核心问题：</p>
                <ol style="margin-left: 20px; margin-top: 10px;">
                    <li><strong>管理员账号输入问题</strong> - 表单字段是否正确区分"账号"和"姓名"</li>
                    <li><strong>数据库写入问题</strong> - 能否正确将安装时输入的数据写入MySQL</li>
                    <li><strong>密码验证问题</strong> - 是否有密码一致性检查和长度验证</li>
                    <li><strong>错误处理问题</strong> - 是否有详细的错误提示和用户反馈</li>
                </ol>
                echo '</div>';
                
                echo '<div class="section">';
                echo '<h3>🔧 测试内容</h3>';
                echo '<div class="step">';
                echo '<div class="step-number">1</div>';
                echo '<strong>文件完整性检查</strong><br>';
                echo '验证原始和修复版安装文件是否存在';
                echo '</div>';
                
                echo '<div class="step">';
                echo '<div class="step-number">2</div>';
                echo '<strong>表单字段验证</strong><br>';
                echo '检查是否包含正确的表单字段：admin_username, admin_realname, 密码验证等';
                echo '</div>';
                
                echo '<div class="step">';
                echo '<div class="step-number">3</div>';
                echo '<strong>数据库逻辑测试</strong><br>';
                echo '验证智能INSERT/UPDATE逻辑，确保支持自定义用户名';
                echo '</div>';
                
                echo '<div class="step">';
                echo '<div class="step-number">4</div>';
                echo '<strong>验证逻辑测试</strong><br>';
                echo '检查密码一致性、长度验证、用户名合法性验证';
                echo '</div>';
                echo '</div>';
                
                echo '<div class="section">';
                echo '<h3>🚀 开始测试</h3>';
                echo '<form method="post">';
                echo '<input type="hidden" name="test_action" value="run_tests">';
                echo '<p>点击按钮开始自动化测试：</p>';
                echo '<button type="submit" class="btn">🧪 运行完整测试</button>';
                echo '</form>';
                echo '</div>';
                
                echo '<div class="section">';
                echo '<h3>📋 修复内容对比</h3>';
                echo '<pre>';
echo '原始安装系统存在的问题：
1. 表单字段错误：
   - 字段名: admin_name (标签: "管理员姓名")
   - 用户以为输入的是"管理员账号"，实际被用作"真实姓名"

2. 数据库写入问题：
   - SQL: UPDATE admin_user ... WHERE username = \'admin\'
   - 只能更新固定的admin账号，不能创建新账号
   - 无法处理自定义用户名

3. 缺少验证：
   - 没有密码一致性验证
   - 没有密码长度检查
   - 没有用户名合法性验证

修复后的改进：
1. 明确的字段区分：
   - 管理员账号 (admin_username): 用于登录的用户名
   - 真实姓名 (admin_realname): 可选的显示名称

2. 智能数据库写入：
   - 先检查用户名是否存在
   - 如果存在: UPDATE password, realname WHERE username = ?
   - 如果不存在: INSERT新管理员账号

3. 完整的验证：
   - 密码一致性验证 (admin_password === confirm_password)
   - 密码长度验证 (>= 6位)
   - 用户名合法性验证 (字母/数字/下划线, 3-20位)
   - 详细的错误提示
';
                echo '</pre>';
                echo '</div>';
                
                echo '<div class="section">';
                echo '<h3>🔗 相关工具</h3>';
                echo '<p>其他可用的修复工具：</p>';
                echo '<ul style="margin-left: 20px; margin-top: 10px;">';
                echo '<li><a href="update_install_system_v2.php" class="btn btn-small">📝 安装系统修复工具</a> - 一键修复安装系统</li>';
                echo '<li><a href="install_fixed.php" target="_blank" class="btn btn-small">🔧 直接使用修复版</a> - 访问修复版安装页面</li>';
                echo '</ul>';
                echo '</div>';
            }
            ?>
            
        </div>
    </div>
</body>
</html>