CREATE TABLE IF NOT EXISTS `rex_slice` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `namespace` varchar(255) NOT NULL,
  `fk_id` bigint(20) NOT NULL,
  `module_id` bigint(20) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `namespace_fk_id` (`namespace`,`fk_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3 ;

CREATE TABLE IF NOT EXISTS `rex_slice_value` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `slice_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `finder` varchar(50) NOT NULL,
  `value` text,
  PRIMARY KEY (`id`),
  KEY `slice_id` (`slice_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3 ;