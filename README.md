# AI小说网 - 智能小说创作与阅读平台

[![PHP](https://img.shields.io/badge/PHP-7.4+-777BB4.svg)](https://php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-5.7+-4479A1.svg)](https://mysql.com/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

一个基于PHP开发的智能小说创作与阅读平台，支持AI辅助创作、用户管理、内容管理等完整功能。

## ✨ 功能特性

### 🎨 前端功能
- 📚 **小说阅读** - 沉浸式阅读体验，支持多种主题
- 🔍 **智能搜索** - 按分类、状态、关键词搜索小说
- 🤖 **AI创作** - 基于AI的小说智能生成功能
- 👤 **用户系统** - 注册、登录、个人中心
- 📱 **响应式设计** - 完美适配PC、平板、手机

### ⚙️ 管理后台
- 📊 **数据统计** - 实时数据可视化展示
- 📝 **内容管理** - 小说、章节、分类管理
- 👥 **用户管理** - 用户信息管理、权限控制
- ⚙️ **系统设置** - 网站配置、SEO设置
- 🤖 **AI设置** - API配置、模型参数调整
- 💾 **数据备份** - 数据库备份与恢复
- 📜 **操作日志** - 完整的操作记录审计

### 🔧 技术特性
- **MVC架构** - 清晰的分层设计
- **RESTful API** - 标准化的API接口
- **PDO数据库** - 安全的MySQL操作
- **自动安装** - 一键安装向导
- **权限管理** - 基于角色的访问控制

## 🚀 快速开始

### 环境要求
- PHP >= 7.4
- MySQL >= 5.7
- Apache/Nginx
- PDO扩展
- cURL扩展（AI功能需要）

### 安装步骤

#### 方式一：自动安装（推荐）

1. **下载代码**
   ```bash
   git clone https://github.com/xxtting/xiaoshuowang.git
   cd xiaoshuowang
   ```

2. **配置Web服务器**
   - 将项目目录设置为网站根目录
   - 确保 `.htaccess` 文件可用

3. **运行安装向导**
   - 访问 `http://您的域名/install/`
   - 按向导提示完成数据库配置
   - 设置管理员账号

#### 方式二：手动安装

1. **创建数据库**
   ```sql
   CREATE DATABASE xiaoshuowang CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

2. **配置数据库**
   ```bash
   cp app/backend/config/database.example.php app/backend/config/database.php
   # 编辑 database.php 填写数据库信息
   ```

3. **导入数据库结构**
   ```bash
   mysql -u root -p xiaoshuowang < app/database/structure.sql
   ```

4. **设置目录权限**
   ```bash
   chmod -R 755 app/runtime/
   chmod -R 755 app/public/uploads/
   ```

5. **访问网站**
   - 前台: `http://您的域名/`
   - 后台: `http://您的域名/admin/`
   - 默认账号: `admin` / `password`

## 📁 目录结构

```
xiaoshuowang/
├── app/                    # 应用程序目录
│   ├── backend/           # 后端API
│   │   ├── app/
│   │   │   ├── Controllers/   # 控制器
│   │   │   ├── Models/        # 数据模型
│   │   │   └── Core/          # 核心类库
│   │   └── config/        # 配置文件
│   ├── admin/             # 管理后台
│   ├── public/            # 前端页面
│   ├── install/           # 安装系统
│   ├── database/          # 数据库文件
│   └── runtime/           # 运行时目录
├── admin/                 # 后台入口
├── public/                # 前端入口
├── index.php             # 网站入口
└── .htaccess             # URL重写规则
```

## 📖 使用指南

### 前端页面

| 页面 | 路径 | 说明 |
|------|------|------|
| 首页 | `/` | 网站首页，展示热门小说 |
| 小说库 | `/novels.html` | 浏览所有小说，支持筛选 |
| 小说详情 | `/novel-detail.html?id=1` | 小说信息和章节目录 |
| 阅读器 | `/reader.html` | 沉浸式阅读体验 |
| 登录 | `/login.html` | 用户登录 |
| 注册 | `/register.html` | 用户注册 |
| AI创作 | `/ai-create.html` | AI辅助创作 |

### 管理后台

| 功能 | 路径 | 说明 |
|------|------|------|
| 仪表板 | `/admin/` | 数据统计概览 |
| 用户管理 | `/admin/users.php` | 用户列表和管理 |
| 内容管理 | `/admin/content.php` | 小说和章节管理 |
| 分类管理 | `/admin/category.php` | 小说分类管理 |
| 系统设置 | `/admin/settings.php` | 网站配置 |
| AI设置 | `/admin/ai_settings.php` | AI参数配置 |
| 数据备份 | `/admin/backup.php` | 数据库备份恢复 |
| 数据统计 | `/admin/statistics.php` | 详细数据统计 |
| 操作日志 | `/admin/logs.php` | 操作记录审计 |

## 🔧 配置说明

### 数据库配置

编辑 `app/backend/config/database.php`:

```php
<?php
return [
    'host' => 'localhost',
    'port' => 3306,
    'database' => 'xiaoshuowang',
    'username' => 'root',
    'password' => 'your_password',
    'charset' => 'utf8mb4',
    'prefix' => ''
];
```

### AI配置

在后台 **AI设置** 页面配置:
- API提供商（OpenAI/自定义）
- API密钥
- 模型选择
- 生成参数

## 🛠️ 开发指南

### 本地开发

1. **克隆项目**
   ```bash
   git clone https://github.com/xxtting/xiaoshuowang.git
   cd xiaoshuowang
   ```

2. **安装依赖**（可选）
   ```bash
   cd app/backend
   composer install
   ```

3. **配置开发环境**
   - 配置本地Web服务器
   - 创建开发数据库
   - 复制配置文件

### 生成部署包

```bash
# Windows
双击 快速生成app.bat

# Linux/Mac
bash generate_app.sh

# PHP (跨平台)
php generate_app.php
```

生成的 `app/` 目录即为纯净的网站部署包。

## 🔒 安全建议

1. **修改默认密码** - 安装后立即修改管理员密码
2. **删除安装目录** - 生产环境删除 `app/install/` 目录
3. **设置文件权限** - 不要给不必要的目录777权限
4. **定期备份** - 使用后台备份功能定期备份数据库
5. **更新系统** - 及时更新PHP和MySQL到最新版本

## 🐛 常见问题

### 数据库连接失败
- 检查 `app/backend/config/database.php` 配置
- 确认数据库服务器可访问
- 确认数据库用户有相应权限

### 页面显示404
- 检查 `.htaccess` 文件是否存在
- 确认Apache/Nginx重写模块已启用
- 检查文件是否完整上传

### 图片上传失败
- 检查 `app/public/uploads/` 目录权限
- 检查PHP上传大小限制配置
- 确认磁盘空间充足

## 📞 技术支持

如有问题，请：
1. 查看服务器错误日志
2. 检查PHP错误日志
3. 使用浏览器开发者工具调试

## 📄 开源协议

本项目采用 [MIT License](LICENSE) 开源协议。

## 🙏 致谢

感谢以下开源项目：
- [Tailwind CSS](https://tailwindcss.com/) - UI框架
- [Chart.js](https://www.chartjs.org/) - 图表库
- [Font Awesome](https://fontawesome.com/) - 图标库

---

**版本**: 1.0.0  
**更新日期**: 2025年3月  
**作者**: xxtting
