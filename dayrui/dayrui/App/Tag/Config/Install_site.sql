CREATE TABLE IF NOT EXISTS `{dbprefix}tag` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) DEFAULT '0' COMMENT '父级id',
  `name` varchar(100) NOT NULL COMMENT '关键词名称',
  `code` varchar(200) NOT NULL COMMENT '关键词代码',
  `pcode` varchar(255) DEFAULT NULL COMMENT '关键词代码',
  `hits` int(10) unsigned NOT NULL COMMENT '点击量(废除)',
  `childids` varchar(255) NOT NULL COMMENT '子类集合',
  `content` text NOT NULL COMMENT '关键词描述',
  `displayorder` int(10) NOT NULL COMMENT '排序',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `letter` (`code`,`hits`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='关键词库表';
