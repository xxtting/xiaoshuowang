# 小说网系统直接上传使用指南

## 🚀 快速部署步骤

### 第一步：上传文件到服务器

1. 通过FTP、SSH或控制面板文件管理器
2. 将 **所有文件** 上传到网站根目录
3. 确保文件结构完整

### 第二步：设置文件权限

```bash
# Linux服务器建议权限
chmod 755 .htaccess
chmod 755 index.php
chmod 755 -R backend/
chmod 755 -R install/
chmod 755 -R admin/
chmod 777 -R public/uploads/
chmod 777 install.lock
chmod 777 backend/config/
```

### 第三步：访问网站进行安装

1. 在浏览器中访问您的网站域名
2. 系统会自动跳转到安装页面
3. 按照安装向导完成配置

## 📁 项目文件结构说明

```
xiaoshuowang/                 # 网站根目录
├── index.php                # 主入口文件
├── .htaccess               # URL重写规则
├── .gitignore              # Git忽略文件
│
├── backend/                # PHP后端API
│   ├── app/               # 应用核心代码
│   │   ├── Core/          # 核心类库
│   │   ├── Controllers/   # 控制器
│   │   ├── Models/        # 数据模型
│   │   └── routes.php     # 路由配置
│   ├── config/            # 配置文件
│   │   ├── app.php        # 应用配置
│   │   └── database.example.php  # 数据库配置模板
│   └── index.php          # API入口
│
├── frontend/              # uni-app前端（可选）
│   └── package.json       # 前端依赖配置
│
├── install/               # 安装系统
│   └── index.php          # 安装向导
│
├── admin/                 # 管理后台
│   ├── index.php          # 后台入口
│   ├── login.php          # 登录处理
│   └── login.html         # 登录页面
│
├── public/                # 公共资源
│   ├── index.html         # 前端主页
│   ├── .htaccess          # 资源访问控制
│   └── uploads/           # 上传文件目录（可自动创建）
│
├── database/              # 数据库文件
│   └── structure.sql      # 数据库结构
│
├── runtime/               # 运行时文件（可自动创建）
│   └── cache/             # 缓存目录
│
└── 各种文档文件           # 安装和部署指南
```

## ⚙️ 服务器要求

### PHP要求
- PHP 7.4 或更高版本
- 必需扩展：PDO MySQL, JSON, CURL
- 推荐扩展：GD, Fileinfo, OpenSSL

### MySQL要求
- MySQL 5.7 或更高版本
- 支持utf8mb4字符集
- 至少100MB存储空间

### Web服务器
- Apache（推荐）：启用mod_rewrite模块
- Nginx：需要配置URL重写规则
- 支持.htaccess文件

### 磁盘空间
- 程序文件：约50MB
- 数据库：初始约10MB，随内容增长
- 上传文件：根据需求分配

## 🔧 环境配置检查

### 1. PHP配置检查
```php
<?php
phpinfo();
?>
```

需要确认以下设置：
- `upload_max_filesize >= 10M`
- `post_max_size >= 10M`
- `memory_limit >= 128M`
- `max_execution_time >= 300`

### 2. 文件权限检查
需要写入权限的目录：
- `public/uploads/` - 用户上传文件
- `backend/config/` - 配置文件
- `runtime/cache/` - 缓存文件
- `install.lock` - 安装锁定文件

## 🚦 安装过程

### 安装步骤
1. **环境检查** - 系统自动检测PHP版本和扩展
2. **数据库配置** - 输入MySQL连接信息
3. **管理员设置** - 设置网站名称和管理员密码
4. **完成安装** - 自动创建数据库和配置文件

### 安装完成后的操作
1. **访问网站**：`http://您的域名/`
2. **登录后台**：`http://您的域名/admin/`
   - 默认账号：`admin`
   - 密码：安装时设置的密码
3. **配置AI功能**：在后台设置AI接口密钥
4. **配置微信公众号**：填写公众号信息

## 🛠️ 常见问题解决

### 1. 安装页面无法访问
- 检查`.htaccess`文件是否存在
- 检查Apache的`mod_rewrite`是否启用
- 检查PHP版本是否达标

### 2. 数据库连接失败
- 检查MySQL服务是否运行
- 确认数据库用户密码正确
- 确认数据库用户有创建表的权限
- 检查主机地址和端口

### 3. 文件上传失败
- 检查`public/uploads/`目录权限
- 检查PHP上传限制配置
- 检查磁盘空间是否充足

### 4. 页面显示404错误
- 检查URL重写规则
- 检查文件路径是否正确
- 检查文件权限设置

### 5. 后台无法登录
- 检查数据库是否成功创建
- 检查管理员账号是否正确
- 检查session是否启用

## 🔐 安全配置建议

### 1. 文件权限安全
```bash
# 保护配置文件
chmod 640 backend/config/database.php
chmod 640 backend/config/app.php

# 保护关键目录
chmod 750 admin/
chmod 750 backend/app/
```

### 2. 数据库安全
- 使用强密码
- 定期备份数据库
- 限制数据库用户权限

### 3. Web服务器安全
- 启用HTTPS
- 配置防火墙
- 定期更新系统和软件

### 4. 应用安全
- 定期更新应用版本
- 监控访问日志
- 设置强密码策略

## 📈 性能优化建议

### 1. PHP优化
```ini
; php.ini配置建议
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
```

### 2. MySQL优化
```sql
-- 创建索引优化查询速度
CREATE INDEX idx_novel_category ON novel(category_id);
CREATE INDEX idx_chapter_novel ON novel_chapter(novel_id);
```

### 3. 缓存配置
- 启用文件缓存
- 配置CDN加速静态资源
- 使用浏览器缓存

### 4. 前端优化
- 压缩CSS和JavaScript
- 优化图片大小
- 启用GZIP压缩

## 📞 技术支持

### 文档位置
- `INSTALL.md` - 详细安装指南
- `GITHUB_DEPLOYMENT.md` - GitHub部署指南
- `DEPLOYMENT_SUMMARY.md` - 部署总结

### 问题反馈
1. 查看错误日志：`runtime/logs/`（如果启用）
2. 检查PHP错误日志
3. 联系技术支持：xxtting@qq.com

### 版本信息
- 当前版本：1.0.0
- 发布日期：2025年3月13日
- 开发者：xxtting
- 邮箱：xxtting@qq.com

## 🎉 开始使用

安装完成后，您可以：
1. 添加小说分类和内容
2. 配置AI小说生成功能
3. 对接微信公众号
4. 管理用户和权限
5. 查看系统统计信息

祝您使用愉快！📚✨