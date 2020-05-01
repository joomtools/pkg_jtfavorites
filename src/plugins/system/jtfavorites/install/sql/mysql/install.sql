CREATE TABLE IF NOT EXISTS `#__jtfavorites` (
  `extension_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(100),
  PRIMARY KEY (`extension_id`, `user_id`),
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
