CREATE TABLE `site_post_images` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `image_id` int(11) unsigned NOT NULL,
  `site_post_id` int(11) unsigned NOT NULL,
  `site_id` int(11) unsigned NOT NULL,
  `display_order` int(11) unsigned NOT NULL,
  `src` varchar(400) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `site_post_id` (`site_post_id`),
  KEY `site_post_id_2` (`site_post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8