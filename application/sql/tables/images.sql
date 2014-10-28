CREATE TABLE `images` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `format` enum('JPG','GIF','PNG') NOT NULL,
  `height` int(10) unsigned NOT NULL,
  `width` int(10) unsigned NOT NULL,
  `meta` text,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `hash` varchar(50) NOT NULL,
  `bytes` int(11) unsigned NOT NULL,
  `is_animated` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `hash` (`hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8