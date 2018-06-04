DROP TABLE IF EXISTS `cms_account`;

CREATE TABLE `cms_account` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(20) NOT NULL,
  `money` float(6,2) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

INSERT INTO `cms_account` ( `name`, `money`)
VALUES
	('jimmy2',2000.00);
