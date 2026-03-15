# 📦 APP目录生成指南

## 🎯 目的

每次修改项目代码后，自动生成纯净的网站文件到 `app` 目录，便于部署到服务器。

## 📁 生成的APP目录内容

### 包含的文件
```
app/
├── index.php              # 网站主入口
├── .htaccess             # URL重写规则
├── README.md             # 使用说明
├── FILES.md              # 文件清单
│
├── backend/              # PHP后端API
│   ├── index.php        # API入口
│   ├── app/             # 应用核心
│   ├── config/          # 配置文件
│   └── composer.json    # PHP依赖
│
├── install/             # 安装系统
│   └── index.php       # 安装向导
│
├── admin/              # 管理后台
│   ├── index.php      # 后台入口
│   ├── login.php      # 登录处理
│   └── login.html     # 登录页面
│
├── public/             # 前端资源
│   ├── index.html     # 前端主页
│   ├── .htaccess      # 资源访问控制
│   └── uploads/       # 上传目录
│
├── database/           # 数据库文件
│   └── structure.sql  # 数据库结构
│
└── runtime/            # 运行时文件
    └── cache/         # 缓存目录
```

### 排除的文件
- 部署脚本 (`deploy.bat`, `deploy.sh`)
- 开发文档 (`*.md` 除了README.md)
- 临时文件 (`*.tmp`, `*.log`)
- 版本控制 (`.git`, `.gitignore`)
- 开发工具 (`generate_*.php`, `test_*.php`)
- 前端开发文件 (`frontend/`)

## 🛠️ 生成工具

### 1. 快速生成脚本 (推荐)
📄 **文件**: `快速生成app.bat`
```bash
# Windows
双击 快速生成app.bat

# 或命令行
快速生成app.bat
```

### 2. 平台专用脚本
```bash
# Windows
generate_app.bat

# Linux/Mac
bash generate_app.sh

# PHP (跨平台)
php generate_app.php
```

### 3. 高级PHP脚本
📄 **文件**: `generate_app.php`
- ✅ 智能文件过滤
- ✅ 权限自动设置
- ✅ 文件清单生成
- ✅ 生成结果验证

### 4. 文件监视工具
📄 **文件**: `watch_and_generate.php`
```bash
# 自动监视文件变化并生成
php watch_and_generate.php
```

## 🚀 使用流程

### 常规流程
```mermaid
graph LR
    A[修改代码] --> B[运行生成脚本]
    B --> C[检查app目录]
    C --> D[上传到服务器]
    D --> E[测试部署]
```

### 详细步骤
1. **开发修改**
   - 在项目根目录修改代码
   - 测试功能是否正常

2. **生成APP目录**
   ```bash
   # 方法1：双击批处理文件
   快速生成app.bat
   
   # 方法2：命令行
   php generate_app.php
   ```

3. **验证生成结果**
   - 检查 `app/` 目录是否存在
   - 查看 `app/FILES.md` 文件清单
   - 验证必需文件是否完整

4. **部署使用**
   - 上传 `app/` 目录到服务器
   - 配置数据库连接
   - 访问网站完成安装

## ⚙️ 配置自定义

### 配置文件
📄 **文件**: `app_generator_config.json`
```json
{
  "include": {
    "files": ["index.php", ".htaccess"],
    "directories": {
      "backend": {"exclude": ["*.log", "cache/*"]}
    }
  },
  "exclude": {
    "patterns": ["*.bat", "*.sh", "deploy.*"]
  }
}
```

### 自定义规则
1. **修改配置文件**：编辑 `app_generator_config.json`
2. **添加排除模式**：在 `exclude.patterns` 中添加
3. **调整包含项目**：修改 `include.files` 和 `include.directories`
4. **重新生成**：运行生成脚本应用更改

## 🔧 高级功能

### 1. 自动监视模式
```bash
# 启动监视
php watch_and_generate.php

# 功能特点
- 自动检测文件变化
- 变化后自动生成app目录
- 记录生成日志
- 按Ctrl+C停止
```

### 2. 生成统计
每次生成会显示：
- ✅ 复制文件数量
- 📁 创建目录数量
- 📊 总文件大小
- ⚠️ 警告和错误信息

### 3. 文件清单
生成 `app/FILES.md` 包含：
- 所有文件的完整列表
- 文件大小和路径
- 生成时间戳
- 目录结构树

## 📋 验证检查

### 必需文件检查
生成工具会自动验证：
- ✅ `index.php` - 网站入口
- ✅ `backend/index.php` - API入口
- ✅ `install/index.php` - 安装系统
- ✅ `admin/index.php` - 管理后台
- ✅ `database/structure.sql` - 数据库结构

### 权限设置
自动设置合理的文件权限：
- PHP文件：644（可读可写）
- 配置文件：644（可读可写）
- 上传目录：777（完全访问）
- 缓存目录：777（完全访问）

## 🐛 故障排除

### 问题1：生成脚本无法运行
**症状**：双击批处理文件没反应
**解决**：
1. 以管理员身份运行
2. 检查文件关联（.bat -> cmd.exe）
3. 使用命令行运行：`cmd /c generate_app.bat`

### 问题2：PHP脚本报错
**症状**：`php generate_app.php` 显示错误
**解决**：
1. 检查PHP版本（需要PHP 7.4+）
2. 启用错误显示
3. 检查文件权限

### 问题3：文件复制不完整
**症状**：app目录缺少某些文件
**解决**：
1. 检查配置文件排除规则
2. 查看生成日志
3. 手动复制缺失文件

### 问题4：权限问题
**症状**：上传后无法写入文件
**解决**：
1. 检查生成时的权限设置
2. 服务器上调整目录权限
3. 确保Web服务器用户有写入权限

## 📊 最佳实践

### 开发流程建议
1. **分支开发**：在feature分支修改代码
2. **本地测试**：修改后本地测试功能
3. **生成验证**：运行生成脚本验证输出
4. **部署测试**：上传到测试服务器验证
5. **生产部署**：确认无误后部署到生产环境

### 文件管理建议
1. **版本控制**：保持项目根目录在git中
2. **定期清理**：清理不需要的临时文件
3. **备份配置**：备份自定义的生成配置
4. **文档更新**：更新README和部署文档

### 自动化建议
1. **Git钩子**：在提交前自动生成app目录
2. **CI/CD集成**：在构建流程中加入生成步骤
3. **定时任务**：定期生成和备份
4. **监控报警**：监视生成失败情况

## 📁 文件清单

### 生成工具文件
1. `快速生成app.bat` - 入口脚本
2. `generate_app.bat` - Windows生成脚本
3. `generate_app.sh` - Linux/Mac生成脚本
4. `generate_app.php` - PHP生成脚本
5. `watch_and_generate.php` - 监视工具
6. `app_generator_config.json` - 配置文件
7. `APP目录生成指南.md` - 本文档

### 输出文件
1. `app/` - 生成的网站目录
2. `app/FILES.md` - 文件清单
3. `watch.log` - 监视日志（如果使用）

## 🎉 开始使用

### 第一次使用
1. 阅读本文档了解流程
2. 运行 `快速生成app.bat`
3. 检查生成的 `app/` 目录
4. 上传到服务器测试

### 日常使用
```bash
# 修改代码后
快速生成app.bat

# 或使用监视模式（开发时）
php watch_and_generate.php
```

### 自定义配置
1. 复制 `app_generator_config.json` 为 `app_generator_config.local.json`
2. 修改本地配置文件
3. 生成脚本会自动使用本地配置

---

**版本**: 1.0.0  
**更新日期**: 2025年3月13日  
**状态**: ✅ 完整可用的生成工具链  
**目标**: 自动化生成纯净的网站部署包