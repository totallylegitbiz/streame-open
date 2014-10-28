CREATE TABLE `users` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(100) DEFAULT NULL,
  `password_hash` varchar(50) NOT NULL,
  `password_salt` varchar(50) NOT NULL,
  `display_name` varchar(40) DEFAULT NULL,
  `permission` enum('NORMAL','ADMIN') DEFAULT 'NORMAL',
  `avatar_image_id` int(11) unsigned DEFAULT NULL,
  `profile` text,
  `verified` tinyint(1) DEFAULT '0',
  `status` enum('QUEUE','ACTIVE','BANNED','REMOVED') DEFAULT 'QUEUE',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_uk` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8