DROP TABLE IF EXISTS `{dbprefix}app_web_log`;
CREATE TABLE IF NOT EXISTS `{dbprefix}app_web_log` (
`id` BIGINT(18) unsigned NOT NULL AUTO_INCREMENT,
`uid` varchar(20) NOT NULL,
`domain` varchar(100) NOT NULL,
`url` varchar(255) NOT NULL,
`param` text NOT NULL,
`result` text NOT NULL,
`httpinfo` text NOT NULL,
`method` varchar(20) NOT NULL,
`useragent` text NOT NULL,
`mobile` tinyint(1) unsigned NOT NULL COMMENT '是否移动端',
`inputip` varchar(200) NOT NULL,
`inputtime` int(10) NOT NULL,
PRIMARY KEY (`id`),
KEY `inputtime` (`inputtime`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='网站访客日志';

