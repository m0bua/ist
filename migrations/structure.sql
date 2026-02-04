SET NAMES utf8mb4;
SET foreign_key_checks = 1;
SET sql_mode = REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY','');

CREATE TABLE `auth` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL,
  `hash` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `auth` tinyint(1) DEFAULT '0',
  `cli_id` int(11) DEFAULT NULL,
  `create_ip` varchar(16) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_login_ip` varchar(16) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created` datetime DEFAULT CURRENT_TIMESTAMP,
  `last_login` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `login` (`login`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `points` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL,
  `class` enum('Request','Income','Tuya') COLLATE utf8mb4_unicode_ci NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `address` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tg_id` int(11) DEFAULT NULL,
  `updated` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_class` (`name`,`class`),
  KEY `tg_id` (`tg_id`),
  CONSTRAINT `points_ibfk_1` FOREIGN KEY (`tg_id`) REFERENCES `tg` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `points_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `point_id` int(11) NOT NULL,
  `status` tinyint(4) NOT NULL,
  `date` datetime NOT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `point_id` (`point_id`),
  CONSTRAINT `points_log_ibfk_1` FOREIGN KEY (`point_id`) REFERENCES `points` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `points_params` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `point_id` int(11) NOT NULL,
  `name` enum('name','msgPattern','msgHeaderPattern','msgIpPattern','msgTextPattern','dateFormat','dateDiffFormat','wait','tries','timeout','statuses','showIp','cli','voltage') COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `point_id_name` (`point_id`,`name`),
  CONSTRAINT `points_params_ibfk_1` FOREIGN KEY (`point_id`) REFERENCES `points` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `tg` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `chat` bigint(20) NOT NULL,
  `botId` bigint(20) NOT NULL,
  `botKey` varchar(42) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `tuya` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `device` varchar(22) COLLATE utf8mb4_unicode_ci NOT NULL,
  `baseUrl` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `accessKey` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `secretKey` varchar(34) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dev_id` (`device`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `tuya_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `t_id` int(11) NOT NULL,
  `data` json NOT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `t_id` (`t_id`),
  CONSTRAINT `tuya_log_ibfk_1` FOREIGN KEY (`t_id`) REFERENCES `tuya` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `user_points` (
  `user_id` int(11) NOT NULL,
  `point_id` int(11) NOT NULL,
  `admin` tinyint(1) NOT NULL DEFAULT '0',
  UNIQUE KEY `user_point` (`user_id`,`point_id`),
  KEY `user_id` (`user_id`),
  KEY `point_id` (`point_id`),
  CONSTRAINT `user_points_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `auth` (`id`),
  CONSTRAINT `user_points_ibfk_2` FOREIGN KEY (`point_id`) REFERENCES `points` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `points_view` AS select `p`.`id` AS `id`,`p`.`name` AS `name`,`p`.`class` AS `class`,`p`.`active` AS `active`,`p`.`status` AS `status`,`p`.`address` AS `address`,`p`.`updated` AS `updated`,if(isnull(`l`.`point_id`),json_object(),json_objectagg(coalesce(`l`.`status`,''),`l`.`date`)) AS `dates`,if(isnull(`pp`.`point_id`),json_object(),json_objectagg(coalesce(`pp`.`name`,''),`pp`.`value`)) AS `params`,if(isnull(`up`.`point_id`),json_object(),json_objectagg(coalesce(`ist`.`auth`.`id`,''),`up`.`admin`)) AS `users`,json_object('id',`ist`.`tg`.`botId`,'key',`ist`.`tg`.`botKey`,'chat',`ist`.`tg`.`chat`) AS `tg` from (((((`ist`.`points` `p` left join (select `ist`.`points_log`.`point_id` AS `point_id`,`ist`.`points_log`.`status` AS `status`,max(`ist`.`points_log`.`date`) AS `date` from `ist`.`points_log` group by `ist`.`points_log`.`point_id`,`ist`.`points_log`.`status` order by `ist`.`points_log`.`status`) `l` on((`l`.`point_id` = `p`.`id`))) left join `ist`.`points_params` `pp` on((`p`.`id` = `pp`.`point_id`))) left join `ist`.`user_points` `up` on((`up`.`point_id` = `p`.`id`))) left join `ist`.`auth` on((`ist`.`auth`.`id` = `up`.`user_id`))) left join `ist`.`tg` on((`ist`.`tg`.`id` = `p`.`tg_id`))) group by `p`.`id` order by `p`.`name`,`p`.`class`;

DROP VIEW IF EXISTS `points_view`;
CREATE TABLE `points_view` (`id` int(11), `name` varchar(16), `class` enum('Request','Income','Tuya'), `active` tinyint(1), `status` tinyint(1), `address` varchar(191), `updated` datetime, `dates` json, `params` json, `users` json, `tg` json);
