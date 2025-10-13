CREATE TABLE IF NOT EXISTS `{dbprefix}form` (
    `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(50) NOT NULL COMMENT '名称',
    `table` varchar(50) NOT NULL COMMENT '表名',
    `setting` text DEFAULT NULL COMMENT '配置信息',
    PRIMARY KEY (`id`),
    UNIQUE KEY `table` (`table`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='表单模型表';