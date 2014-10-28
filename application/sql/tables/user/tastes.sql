CREATE TABLE `user_tastes` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `feature` varchar(20) NOT NULL,
  `value` varchar(100) NOT NULL,
  `score` float NOT NULL,
  `matches` float unsigned NOT NULL DEFAULT '0',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `test_id_feature_value` (`user_id`,`feature`,`value`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8