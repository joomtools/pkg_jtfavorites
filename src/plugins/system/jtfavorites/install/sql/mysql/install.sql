CREATE TABLE IF NOT EXISTS `#__jtfavorites` (
  `user_id` int(11) NOT NULL,
  `assets_name` varchar(50) NOT NULL,
  `client_id` int(11) NOT NULL,
  `favorite_title` varchar(100),
  `state` tinyint(1) NOT NULL,
  PRIMARY KEY (`user_id`, `assets_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
