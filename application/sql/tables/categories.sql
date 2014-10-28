CREATE TABLE `categories` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `parent_id` int(11) unsigned DEFAULT NULL,
  `display_order` int(10) unsigned DEFAULT '0',
  `is_public` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `parent_id` (`parent_id`,`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8