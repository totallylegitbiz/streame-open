CREATE TABLE `site_post_topics` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` int(11) unsigned NOT NULL,
  `topic_id` int(11) unsigned NOT NULL,
  `site_id` int(11) unsigned NOT NULL,
  `start_char` int(10) unsigned NOT NULL DEFAULT '0',
  `score` float unsigned NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `post_id_2` (`post_id`,`topic_id`),
  KEY `post_id` (`post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8