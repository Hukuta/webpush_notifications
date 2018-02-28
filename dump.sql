SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `pushtask`;
CREATE TABLE `pushtask` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `count_ok` int(10) unsigned NOT NULL DEFAULT '0',
  `count_fail` int(10) unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `title` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `icon` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL,
  `to_new` tinyint(1) NOT NULL DEFAULT '2',
  `host` text NOT NULL,
  `lang` text NOT NULL,
  `mobile` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `tasks_users`;
CREATE TABLE `tasks_users` (
  `push_id` bigint(15) unsigned zerofill NOT NULL,
  `task_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`push_id`,`task_id`),
  KEY `task_id` (`task_id`),
  KEY `push_id` (`push_id`),
  CONSTRAINT `tasks_users_ibfk_1` FOREIGN KEY (`push_id`) REFERENCES `webpush` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `tasks_users_ibfk_2` FOREIGN KEY (`task_id`) REFERENCES `pushtask` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `webpush`;
CREATE TABLE `webpush` (
  `id` bigint(15) unsigned zerofill NOT NULL AUTO_INCREMENT,
  `host` varchar(100) NOT NULL,
  `ip` int(15) unsigned NOT NULL,
  `mobile` tinyint(1) unsigned DEFAULT NULL,
  `valid` tinyint(1) unsigned DEFAULT NULL,
  `endpoint` varchar(255) NOT NULL,
  `key` varchar(255) DEFAULT NULL,
  `token` varchar(255) DEFAULT NULL,
  `useragent` varchar(255) DEFAULT NULL,
  `lang` char(2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `endpoint` (`endpoint`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



