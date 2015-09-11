CREATE TABLE IF NOT EXISTS `{$DB_PREFIX}{$TABLE_NAME}` (
  `{$TABLE_ID}` int(11) NOT NULL AUTO_INCREMENT,
  `description` varchar(60) NOT NULL,
  `street` varchar(60) NOT NULL,
  `number` varchar(15) NOT NULL,
  `locality` varchar(60) NOT NULL,
  `province` varchar(60) NOT NULL,
  `latitude` varchar(15) NOT NULL,
  `longitude` varchar(15) NOT NULL,
  PRIMARY KEY (`{$TABLE_ID}`)
);
