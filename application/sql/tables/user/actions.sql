CREATE TABLE `user_actions` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `target_type` varchar(50) DEFAULT NULL,
  `target_id` int(11) unsigned NOT NULL,
  `action` varchar(50) NOT NULL,
  `rating` int(10) unsigned DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8