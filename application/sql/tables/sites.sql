CREATE TABLE `sites` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `avatar_image_id` int(11) unsigned DEFAULT NULL,
  `name` varchar(1024) NOT NULL,
  `blurb` text,
  `slug` varchar(255) NOT NULL,
  `url` varchar(1024) NOT NULL,
  `rss_url` varchar(1024) NOT NULL,
  `status` enum('QUEUE','ACTIVE','REMOVED') DEFAULT NULL,
  `post_count` int(11) unsigned NOT NULL DEFAULT '0',
  `poll_enabled` tinyint(1) DEFAULT '1',
  `last_poll` datetime DEFAULT NULL,
  `score` float DEFAULT NULL,
  `is_subdomain` tinyint(1) NOT NULL,
  `created` datetime DEFAULT NULL,
  `daily_freq_percentile` float DEFAULT NULL,
  `poll_freq_secs` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8