
CREATE TABLE IF NOT EXISTS `{dbprefix}member_login` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` mediumint(8) unsigned DEFAULT NULL COMMENT '会员uid',
  `type` varchar(30) NOT NULL COMMENT '登录方式',
  `loginip` varchar(50) NOT NULL COMMENT '登录Ip',
  `logintime` int(10) unsigned NOT NULL COMMENT '登录时间',
  `useragent` varchar(255) NOT NULL COMMENT '客户端信息',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `loginip` (`loginip`),
  KEY `logintime` (`logintime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT='登录日志记录';

CREATE TABLE IF NOT EXISTS `{dbprefix}member_group` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL COMMENT '用户组名称',
  `price` decimal(10,2) NOT NULL COMMENT '售价',
  `unit` tinyint(1) unsigned NOT NULL COMMENT '价格单位:1积分，0金钱',
  `days` int(10) unsigned NOT NULL COMMENT '生效时长',
  `apply` tinyint(1) unsigned NOT NULL COMMENT '是否申请',
  `register` tinyint(1) unsigned NOT NULL COMMENT '是否允许注册',
  `setting` text NOT NULL COMMENT '用户组配置',
  `displayorder` smallint(5) NOT NULL COMMENT '排序',
  PRIMARY KEY (`id`),
  KEY `displayorder` (`displayorder`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT='用户组表';

CREATE TABLE IF NOT EXISTS `{dbprefix}member_level` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `gid` smallint(5) unsigned NOT NULL COMMENT '用户id',
  `name` varchar(255) NOT NULL COMMENT '名称',
  `stars` int(10) NOT NULL COMMENT '图标',
  `value` int(10) unsigned NOT NULL COMMENT '升级值要求',
  `note` text NOT NULL COMMENT '备注',
  `apply` tinyint(1) unsigned NOT NULL COMMENT '是否允许升级',
  `setting` text NOT NULL COMMENT '等级配置',
  `displayorder` smallint(5) NOT NULL COMMENT '排序',
  PRIMARY KEY (`id`),
  KEY `value` (`value`),
  KEY `apply` (`apply`),
  KEY `gid` (`gid`),
  KEY `displayorder` (`displayorder`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT='用户组级别表';

CREATE TABLE IF NOT EXISTS `{dbprefix}member_group_index` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned NOT NULL COMMENT '用户id',
  `gid` smallint(5) unsigned NOT NULL COMMENT '用户组id',
  `lid` smallint(5) unsigned NOT NULL COMMENT '用户组等级id',
  `stime` int(10) unsigned NOT NULL COMMENT '开通时间',
  `etime` int(10) unsigned NOT NULL COMMENT '结束时间',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `lid` (`lid`),
  KEY `gid` (`gid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT='用户组归属表';

CREATE TABLE IF NOT EXISTS `{dbprefix}member_group_verify` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `username` varchar(50) NOT NULL COMMENT '用户名',
  `gid` smallint(5) unsigned NOT NULL COMMENT '用户组id',
  `lid` smallint(5) unsigned NOT NULL COMMENT '用户组等级id',
  `status` tinyint(1) unsigned NOT NULL COMMENT '状态',
  `price` decimal(10,2) DEFAULT NULL COMMENT '已费用',
  `content` text NOT NULL COMMENT '自定义字段信息',
  `inputtime` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `status` (`status`),
  KEY `inputtime` (`inputtime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT='用户组申请审核';

DROP TABLE IF EXISTS `{dbprefix}member_setting`;
CREATE TABLE IF NOT EXISTS `{dbprefix}member_setting` (
  `name` varchar(50) NOT NULL,
  `value` mediumtext NOT NULL,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT='用户属性参数表';

CREATE TABLE IF NOT EXISTS `{dbprefix}member_menu` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `pid` smallint(5) unsigned NOT NULL COMMENT '上级菜单id',
  `name` text NOT NULL COMMENT '菜单语言名称',
  `uri` varchar(255) DEFAULT NULL COMMENT 'uri字符串',
  `url` varchar(255) DEFAULT NULL COMMENT '外链地址',
  `mark` varchar(255) DEFAULT NULL COMMENT '菜单标识',
  `hidden` tinyint(1) unsigned DEFAULT NULL COMMENT '是否隐藏',
  `icon` varchar(255) DEFAULT NULL COMMENT '图标标示',
  `group` text DEFAULT NULL COMMENT '用户组划分',
  `site` text DEFAULT NULL COMMENT '站点划分',
  `client` text DEFAULT NULL COMMENT '终端划分',
  `displayorder` int(5) DEFAULT NULL COMMENT '排序值',
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`),
  KEY `mark` (`mark`),
  KEY `hidden` (`hidden`),
  KEY `uri` (`uri`),
  KEY `displayorder` (`displayorder`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT='用户组菜单表';

CREATE TABLE IF NOT EXISTS `{dbprefix}member_oauth` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `uid` mediumint(8) unsigned NOT NULL COMMENT '会员uid',
  `oid` varchar(255) NOT NULL COMMENT 'OAuth返回id',
  `oauth` varchar(255) NOT NULL COMMENT '运营商',
  `avatar` varchar(255) NOT NULL COMMENT '头像',
  `unionid` varchar(255) DEFAULT NULL COMMENT 'unionId',
  `nickname` varchar(255) NOT NULL COMMENT '昵称',
  `expire_at` int(10) unsigned NOT NULL COMMENT '绑定时间',
  `access_token` varchar(255) DEFAULT NULL COMMENT '保留',
  `refresh_token` varchar(255) DEFAULT NULL COMMENT '保留',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT='快捷登录用户OAuth授权表';

CREATE TABLE IF NOT EXISTS `{dbprefix}admin_verify` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL COMMENT '名称',
  `verify` text NOT NULL COMMENT '审核部署',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT='审核管理表';

CREATE TABLE IF NOT EXISTS `{dbprefix}member_verify` (
    `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
    `uid` mediumint(8) unsigned NOT NULL COMMENT '会员uid',
    `tid` tinyint(1) unsigned NOT NULL COMMENT '类别',
    `status` tinyint(1) unsigned NOT NULL COMMENT '状态',
    `content` mediumtext NOT NULL COMMENT '自定义字段信息',
    `result` text NOT NULL COMMENT '处理结果',
    `updatetime` int(10) unsigned NOT NULL COMMENT '处理时间',
    `inputtime` int(10) unsigned NOT NULL COMMENT '提交时间',
    PRIMARY KEY (`id`),
    KEY `tid` (`tid`),
    KEY `uid` (`uid`),
    KEY `status` (`status`),
    KEY `inputtime` (`inputtime`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT='会员审核表';

REPLACE INTO `{dbprefix}admin_verify` VALUES(1, '默认审核', '{"edit":"1","role":{"1":"2"}}');

REPLACE INTO `{dbprefix}member_group` VALUES(1, '注册用户', 0.00, 0, 0, 1, 1, '{\"level\":{\"auto\":\"0\",\"unit\":\"0\",\"price\":\"0\"},\"verify\":\"0\"}', 0);
REPLACE INTO `{dbprefix}member_group_index` VALUES(1, 1, 1, 0, 0, 0);


REPLACE INTO `{dbprefix}member_setting` VALUES('config', '{\"edit_name\":\"1\",\"edit_mobile\":\"1\",\"logintime\":\"\",\"verify_msg\":\"\",\"pagesize\":\"\",\"pagesize_mobile\":\"\",\"pagesize_api\":\"\"}');
REPLACE INTO `{dbprefix}member_setting` VALUES('login', '{\"code\":\"1\"}');
REPLACE INTO `{dbprefix}member_setting` VALUES('register', '{\"close\":\"0\",\"groupid\":\"1\",\"field\":[\"username\",\"email\"],\"cutname\":\"0\",\"unprefix\":\"\",\"code\":\"1\",\"verify\":\"\",\"preg\":\"\",\"notallow\":\"\"}');
REPLACE INTO `{dbprefix}member_setting` VALUES('auth2', '{"1":{"public":{"home":{"show":"0","is_category":"0"},"form_public":[],"share_category_public":{"show":"1","add":"1","edit":"1","code":"1","verify":"1","exp":"","score":"","money":"","day_post":"","total_post":""},"category_public":[],"mform_public":"","form":null,"share_category":null,"category":null,"mform":null}}}');
