# GitHub Token 获取和使用指南

## 📍 GitHub Token 位置

**直接访问地址：**
```
https://github.com/settings/tokens
```

## 🖼️ 图文步骤

### 第一步：登录GitHub
1. 打开 https://github.com
2. 使用账号 `xxtting` 登录
3. 输入您在浏览器中保存的密码

### 第二步：进入Token设置页面

**路径1（推荐）：**
- 直接访问：https://github.com/settings/tokens

**路径2（手动导航）：**
1. 点击右上角您的头像
2. 选择 "Settings"
3. 左侧菜单找到 "Developer settings"
4. 点击 "Personal access tokens"
5. 选择 "Tokens (classic)"

### 第三步：生成Token

**界面截图指引：**
```
┌─────────────────────────────────────────┐
│  Personal access tokens                 │
├─────────────────────────────────────────┤
│  ◉ Tokens (classic)                     │
│  ○ Fine-grained tokens                  │
│                                         │
│  [Generate new token]                   │
│  [Generate new token (classic)]         │ ← 点击这个！
└─────────────────────────────────────────┘
```

**Token设置表单：**
```
Note: xiaoshuowang-project-push
Expiration: [90 days] ───┐
                         ↓
    No expiration    │
    30 days         │ ← 推荐选择
    60 days         │
    90 days         │
    6 months        │
    1 year          │
```

**选择权限：**
```
Select scopes
├─ ✓ repo
│   ├─ ✓ repo:status
│   ├─ ✓ repo_deployment
│   ├─ ✓ public_repo
│   ├─ ✓ repo:invite
│   └─ ✓ security_events
│
└─ ✓ workflow
```

**重要提醒：**
- ⚠️ 生成的Token **只会显示一次**
- ⚠️ 请立即复制并安全保存
- ⚠️ 丢失后无法找回，需要重新生成

## 🔧 Token使用方式

### 1. 使用Token推送代码

**命令行中：**
```bash
# 添加远程仓库
git remote add origin https://github.com/xxtting/xiaoshuowang.git

# 重命名分支
git branch -M main

# 推送代码（会提示输入密码）
git push -u origin main
```

**认证提示：**
```
Username: xxtting
Password: [粘贴您的GitHub Token]  ← 不是GitHub密码！
```

### 2. 配置Git使用Token（可选）

**配置Git凭据存储：**
```bash
# 设置Git使用Token
git config credential.helper store

# 或使用缓存
git config credential.helper "cache --timeout=3600"
```

## 📱 可能的问题和解决方案

### 问题1：找不到Token页面
**解决方案：**
- 确认已登录正确账号
- 检查URL：https://github.com/settings/tokens
- 检查是否在组织账号中，切换回个人账号

### 问题2：Token权限不足
**解决方案：**
- 重新生成Token，勾选所有 `repo` 权限
- 确保勾选 `workflow`（如果需要GitHub Actions）

### 问题3：推送时认证失败
**解决方案：**
```bash
# 清除旧的Git凭据
git credential reject
protocol=https
host=github.com

# 重新推送
git push origin main
```

## 🔐 Token安全注意事项

1. **不要分享Token** - Token等同于密码
2. **不要在公共代码中存储Token**
3. **定期更换Token** - 设置合理过期时间
4. **撤销不需要的Token** - 在Token页面可以撤销

## 🚀 快速验证Token是否有效

```bash
# 测试Token是否有效
curl -H "Authorization: token YOUR_TOKEN_HERE" https://api.github.com/user
```

如果返回您的用户信息，说明Token有效。

## 📞 获取帮助

如果遇到问题：
1. 检查GitHub帮助文档：https://docs.github.com/en
2. 搜索相关问题
3. 如果无法解决，可以重新生成Token

---
*最后更新：2025年3月13日*
*为 xxtting 账号配置*