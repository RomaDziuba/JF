CREATE TABLE IF NOT EXISTS `user_types` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `caption` varchar(128) NOT NULL,
  `ident` varchar(128) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ident` (`ident`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=5 ;

INSERT INTO `user_types` (`id`, `caption`, `ident`) VALUES
(1, 'User', 'user'),
(2, 'Admin', 'admin');

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `id_type` int(11) unsigned NOT NULL,
  `name` varchar(128) NOT NULL,
  `login` varchar(128) NOT NULL,
  `pass` varchar(32) NOT NULL,
  `email` varchar(128) NOT NULL,
  `status` enum('new','active','deleted') NOT NULL DEFAULT 'new',
  `activation_code` varchar(32) DEFAULT NULL,
  `registration_date` date NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `login` (`login`),
  UNIQUE KEY `email` (`email`),
  KEY `id_type` (`id_type`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;


INSERT INTO `users` (`id`, `id_type`, `name`, `login`, `pass`, `email`, `status`, `activation_code`, `registration_date`) VALUES
(1, 2, 'admin', 'admin', '21232f297a57a5a743894a0e4a801fc3', 'admin@admin.com', 'active', NULL, '0000-00-00');

ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`id_type`) REFERENCES `user_types` (`id`);