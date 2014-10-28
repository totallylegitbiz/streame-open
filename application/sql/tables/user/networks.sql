CREATE TABLE `user_networks` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `network` enum('FACEBOOK','FOURSQUARE','TWITTER') NOT NULL,
  `network_token` varchar(255) DEFAULT NULL,
  `network_user_id` varchar(255) DEFAULT NULL,
  `meta` text,
  `expires` datetime DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `network_token` (`network_token`,`network`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8