-- 小说网系统数据库结构
-- 创建时间：2025-03-13

-- 系统配置表
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

-- 小说分类表
CREATE TABLE `novel_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL COMMENT '分类名称',
  `description` varchar(255) DEFAULT NULL COMMENT '分类描述',
  `sort_order` int(11) DEFAULT 0 COMMENT '排序',
  `status` tinyint(1) DEFAULT 1 COMMENT '状态：1启用 0禁用',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='小说分类表';

-- 小说表
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

-- 小说章节表
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

-- 用户表
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

-- 管理员表
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

-- AI小说生成记录表
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

-- 微信公众号配置表
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

-- 用户阅读记录表
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

-- 系统操作日志表
CREATE TABLE `sys_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL COMMENT '用户ID',
  `action` varchar(100) NOT NULL COMMENT '操作类型',
  `description` varchar(500) DEFAULT NULL COMMENT '操作描述',
  `ip` varchar(50) DEFAULT NULL COMMENT 'IP地址',
  `user_agent` text COMMENT '用户代理',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统操作日志表';

-- 初始化数据
INSERT INTO `sys_config` (`config_key`, `config_value`, `config_desc`) VALUES
('site_name', 'AI小说网', '网站名称'),
('site_logo', '/static/images/logo.png', '网站LOGO'),
('site_description', '智能小说创作与阅读平台', '网站描述'),
('ai_api_key', '', 'AI接口密钥'),
('ai_api_url', 'https://api.openai.com/v1/chat/completions', 'AI接口地址');

INSERT INTO `novel_category` (`name`, `description`, `sort_order`) VALUES
('玄幻', '奇幻魔法，仙侠修真', 1),
('都市', '都市生活，职场商战', 2),
('言情', '爱情故事，浪漫小说', 3),
('科幻', '科幻未来，星际文明', 4),
('悬疑', '悬疑推理，恐怖惊悚', 5),
('历史', '历史军事，古代言情', 6),
('武侠', '武侠江湖，英雄豪杰', 7);

INSERT INTO `admin_user` (`username`, `password`, `realname`, `role`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '系统管理员', 'super_admin');