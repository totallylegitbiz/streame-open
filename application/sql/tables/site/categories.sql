CREATE TABLE `site_categories` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(11) unsigned DEFAULT NULL,
  `site_id` int(11) unsigned DEFAULT NULL,
  `is_primary` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `site_id` (`site_id`,`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8