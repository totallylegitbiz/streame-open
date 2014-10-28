CREATE TABLE `topics` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `topic` varchar(200) DEFAULT NULL,
  `wiki_entity` varchar(200) DEFAULT NULL,
  `wiki_url` varchar(400) DEFAULT NULL,
  `meta` text,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `topic` (`topic`,`wiki_entity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8