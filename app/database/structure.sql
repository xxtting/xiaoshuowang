-- 小说网系统数据库结构
-- 创建时间：2025-03-14
-- 版本：v2.0

-- ============================================
-- 系统配置表
-- ============================================
CREATE TABLE `sys_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `config_key` varchar(100) NOT NULL COMMENT '配置键名',
  `config_value` text COMMENT '配置值',
  `config_desc` varchar(255) DEFAULT NULL COMMENT '配置描述',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_config_key` (`config_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统配置表';

-- ============================================
-- 系统设置表（新增）
-- ============================================
CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL COMMENT '设置键',
  `setting_value` text COMMENT '设置值',
  `setting_type` varchar(50) DEFAULT 'text' COMMENT '设置类型：text/textarea/image/switch',
  `description` varchar(255) DEFAULT NULL COMMENT '设置描述',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统设置表';

-- ============================================
-- AI设置表（新增）
-- ============================================
CREATE TABLE `ai_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL COMMENT '设置键',
  `setting_value` text COMMENT '设置值',
  `description` varchar(255) DEFAULT NULL COMMENT '设置描述',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='AI设置表';

-- ============================================
-- 小说分类表
-- ============================================
CREATE TABLE `novel_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL COMMENT '分类名称',
  `description` varchar(255) DEFAULT NULL COMMENT '分类描述',
  `sort_order` int(11) DEFAULT 0 COMMENT '排序',
  `status` tinyint(1) DEFAULT 1 COMMENT '状态：1启用 0禁用',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='小说分类表';

-- ============================================
-- 小说表
-- ============================================
CREATE TABLE `novel` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL COMMENT '小说标题',
  `author` varchar(100) NOT NULL COMMENT '作者',
  `cover_image` varchar(255) DEFAULT NULL COMMENT '封面图片',
  `category_id` int(11) NOT NULL COMMENT '分类ID',
  `description` text COMMENT '小说简介',
  `status` tinyint(1) DEFAULT 1 COMMENT '状态：1连载 2完结 0下架',
  `is_vip` tinyint(1) DEFAULT 0 COMMENT '是否VIP：1是 0否',
  `word_count` int(11) DEFAULT 0 COMMENT '字数',
  `chapter_count` int(11) DEFAULT 0 COMMENT '章节数',
  `view_count` int(11) DEFAULT 0 COMMENT '阅读量',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category_id`),
  KEY `idx_author` (`author`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='小说表';

-- ============================================
-- 小说章节表
-- ============================================
CREATE TABLE `novel_chapter` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `novel_id` int(11) NOT NULL COMMENT '小说ID',
  `chapter_title` varchar(200) NOT NULL COMMENT '章节标题',
  `chapter_content` text NOT NULL COMMENT '章节内容',
  `chapter_number` int(11) NOT NULL COMMENT '章节序号',
  `word_count` int(11) DEFAULT 0 COMMENT '字数',
  `is_free` tinyint(1) DEFAULT 1 COMMENT '是否免费：1是 0否',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_novel` (`novel_id`),
  KEY `idx_chapter_num` (`novel_id`, `chapter_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='小说章节表';

-- ============================================
-- 用户表
-- ============================================
CREATE TABLE `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL COMMENT '用户名',
  `password` varchar(255) NOT NULL COMMENT '密码',
  `nickname` varchar(50) DEFAULT NULL COMMENT '昵称',
  `avatar` varchar(255) DEFAULT NULL COMMENT '头像',
  `phone` varchar(20) DEFAULT NULL COMMENT '手机号',
  `email` varchar(100) DEFAULT NULL COMMENT '邮箱',
  `openid` varchar(100) DEFAULT NULL COMMENT '微信公众号openid',
  `user_type` tinyint(1) DEFAULT 1 COMMENT '用户类型：1普通用户 2作者 3管理员',
  `vip_level` tinyint(1) DEFAULT 0 COMMENT 'VIP等级',
  `vip_expire_time` datetime DEFAULT NULL COMMENT 'VIP过期时间',
  `status` tinyint(1) DEFAULT 1 COMMENT '状态：1正常 0禁用',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`),
  UNIQUE KEY `uk_phone` (`phone`),
  UNIQUE KEY `uk_email` (`email`),
  UNIQUE KEY `uk_openid` (`openid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户表';

-- ============================================
-- 用户收藏表（新增）
-- ============================================
CREATE TABLE `user_favorite` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT '用户ID',
  `novel_id` int(11) NOT NULL COMMENT '小说ID',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_novel` (`user_id`, `novel_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_novel_id` (`novel_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户收藏表';

-- ============================================
-- 管理员表
-- ============================================
CREATE TABLE `admin_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL COMMENT '管理员账号',
  `password` varchar(255) NOT NULL COMMENT '密码',
  `realname` varchar(50) DEFAULT NULL COMMENT '真实姓名',
  `role` varchar(50) DEFAULT 'admin' COMMENT '角色',
  `status` tinyint(1) DEFAULT 1 COMMENT '状态：1正常 0禁用',
  `last_login_time` datetime DEFAULT NULL COMMENT '最后登录时间',
  `last_login_ip` varchar(50) DEFAULT NULL COMMENT '最后登录IP',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='管理员表';

-- ============================================
-- AI小说生成记录表
-- ============================================
CREATE TABLE `ai_novel_generate` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL COMMENT '用户ID',
  `prompt` text NOT NULL COMMENT '生成提示词',
  `generated_content` text COMMENT '生成的内容',
  `novel_id` int(11) DEFAULT NULL COMMENT '生成的小说ID',
  `status` tinyint(1) DEFAULT 0 COMMENT '状态：0生成中 1成功 2失败',
  `cost_tokens` int(11) DEFAULT 0 COMMENT '消耗的token数',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_novel` (`novel_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='AI小说生成记录表';

-- ============================================
-- 微信公众号配置表
-- ============================================
CREATE TABLE `wechat_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `app_id` varchar(100) NOT NULL COMMENT '公众号AppID',
  `app_secret` varchar(255) NOT NULL COMMENT '公众号AppSecret',
  `token` varchar(100) DEFAULT NULL COMMENT 'Token',
  `encoding_aes_key` varchar(255) DEFAULT NULL COMMENT 'EncodingAESKey',
  `is_enabled` tinyint(1) DEFAULT 0 COMMENT '是否启用：1是 0否',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='微信公众号配置表';

-- ============================================
-- 用户阅读记录表
-- ============================================
CREATE TABLE `user_read_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT '用户ID',
  `novel_id` int(11) NOT NULL COMMENT '小说ID',
  `chapter_id` int(11) DEFAULT NULL COMMENT '章节ID',
  `read_progress` int(11) DEFAULT 0 COMMENT '阅读进度',
  `last_read_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_novel` (`user_id`, `novel_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_novel` (`novel_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户阅读记录表';

-- ============================================
-- 系统操作日志表
-- ============================================
CREATE TABLE `sys_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL COMMENT '用户ID',
  `username` varchar(50) DEFAULT NULL COMMENT '操作用户名',
  `action` varchar(100) NOT NULL COMMENT '操作类型',
  `action_type` varchar(50) DEFAULT 'other' COMMENT '操作分类：login/content/system/error/other',
  `description` varchar(500) DEFAULT NULL COMMENT '操作描述',
  `ip` varchar(50) DEFAULT NULL COMMENT 'IP地址',
  `user_agent` text COMMENT '用户代理',
  `target_type` varchar(50) DEFAULT NULL COMMENT '操作对象类型',
  `target_id` int(11) DEFAULT NULL COMMENT '操作对象ID',
  `before_data` json DEFAULT NULL COMMENT '操作前数据',
  `after_data` json DEFAULT NULL COMMENT '操作后数据',
  `result` tinyint(1) DEFAULT 1 COMMENT '操作结果：1成功 0失败',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_username` (`username`),
  KEY `idx_action` (`action`),
  KEY `idx_action_type` (`action_type`),
  KEY `idx_target` (`target_type`, `target_id`),
  KEY `idx_result` (`result`),
  KEY `idx_created_at` (`create_time`),
  KEY `idx_user_time` (`user_id`, `create_time`),
  KEY `idx_action_time` (`action_type`, `create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统操作日志表';

-- ============================================
-- 初始化数据
-- ============================================

-- 系统配置
INSERT INTO `sys_config` (`config_key`, `config_value`, `config_desc`) VALUES
('site_name', 'AI小说网', '网站名称'),
('site_logo', '/static/images/logo.png', '网站LOGO'),
('site_description', '智能小说创作与阅读平台', '网站描述'),
('ai_api_key', '', 'AI接口密钥'),
('ai_api_url', 'https://api.openai.com/v1/chat/completions', 'AI接口地址');

-- 系统设置默认值
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
('site_name', '小说网', 'text', '网站名称'),
('site_title', '小说网 - 免费小说阅读', 'text', '网站标题'),
('site_keywords', '小说,免费小说,在线阅读', 'text', 'SEO关键词'),
('site_description', '提供海量免费小说在线阅读，更新及时，阅读体验优秀。', 'textarea', 'SEO描述'),
('site_logo', '', 'image', '网站Logo'),
('site_favicon', '', 'image', '网站图标'),
('contact_email', 'admin@example.com', 'text', '联系邮箱'),
('contact_phone', '', 'text', '联系电话'),
('contact_qq', '', 'text', '联系QQ'),
('contact_wechat', '', 'text', '微信号'),
('footer_text', '© 2024 小说网 版权所有', 'text', '页脚文字'),
('footer_icp', '', 'text', 'ICP备案号'),
('footer_police', '', 'text', '公安备案号'),
('site_status', '1', 'switch', '网站状态（1开启 0关闭）'),
('close_reason', '', 'textarea', '关闭原因'),
('register_open', '1', 'switch', '开放注册（1开启 0关闭）'),
('comment_open', '1', 'switch', '开放评论（1开启 0关闭）');

-- AI设置默认值
INSERT INTO `ai_settings` (`setting_key`, `setting_value`, `description`) VALUES
('ai_provider', 'openai', 'AI服务商'),
('api_key', '', 'API密钥'),
('api_url', 'https://api.openai.com/v1', 'API地址'),
('model', 'gpt-3.5-turbo', '使用的模型'),
('max_tokens', '2000', '最大生成token数'),
('temperature', '0.7', '生成温度(0-2)'),
('top_p', '1', 'Top P值(0-1)'),
('frequency_penalty', '0', '频率惩罚(0-2)'),
('presence_penalty', '0', '存在惩罚(0-2)'),
('enable_novel_gen', '1', '启用小说生成'),
('enable_chapter_gen', '1', '启用章节生成'),
('enable_outline_gen', '1', '启用大纲生成'),
('daily_limit', '100', '每日生成次数限制'),
('content_filter', '1', '启用内容过滤');

-- 小说分类
INSERT INTO `novel_category` (`name`, `description`, `sort_order`) VALUES
('玄幻', '奇幻魔法，仙侠修真', 1),
('都市', '都市生活，职场商战', 2),
('言情', '爱情故事，浪漫小说', 3),
('科幻', '科幻未来，星际文明', 4),
('悬疑', '悬疑推理，恐怖惊悚', 5),
('历史', '历史军事，古代言情', 6),
('武侠', '武侠江湖，英雄豪杰', 7);

-- 默认管理员账号（密码：password）
INSERT INTO `admin_user` (`username`, `password`, `realname`, `role`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '系统管理员', 'super_admin');
