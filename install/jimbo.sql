
CREATE TABLE IF NOT EXISTS `dbdrive_menu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `caption` varchar(64) NOT NULL,
  `url` varchar(64) NOT NULL,
  `id_parent` int(11) NOT NULL,
  `order_n` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC AUTO_INCREMENT=103 ;

INSERT INTO `dbdrive_menu` (`id`, `caption`, `url`, `id_parent`, `order_n`) VALUES
(2, 'Администратор', '', 0, 10),
(101, 'Меню', '/jimbo/dbdrive_menu/', 2, 0),
(102, 'Таблицы', '/jimbo/dbdrive_tables/', 2, 0);

CREATE TABLE IF NOT EXISTS `dbdrive_menu_perms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_role` int(11) NOT NULL,
  `id_menu` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=7 ;

CREATE TABLE IF NOT EXISTS `dbdrive_perms` (
  `id_table` int(11) NOT NULL,
  `id_role` int(11) NOT NULL,
  `value` int(11) NOT NULL,
  KEY `id_table` (`id_table`,`id_role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `dbdrive_tables` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `caption` varchar(64) NOT NULL,
  `comment` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `caption` (`caption`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=61 ;

INSERT INTO `dbdrive_tables` (`id`, `caption`, `comment`) VALUES
(1, 'dbdrive_tables', 'Системная таблица'),
(60, 'dbdrive_menu', 'Меню');
