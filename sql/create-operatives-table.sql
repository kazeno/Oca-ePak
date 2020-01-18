CREATE TABLE IF NOT EXISTS `{$DB_PREFIX}{$TABLE_NAME}` (
  `{$TABLE_ID}` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `carrier_reference` INT UNSIGNED NULL,
  `reference` INT UNSIGNED NOT NULL,
  `description` text NULL,
  `addfee` varchar(10) NULL,
  `id_shop` INT UNSIGNED NOT NULL,
  `type` CHAR(3) NOT NULL,
  `insured` INT UNSIGNED NULL,
PRIMARY KEY (`{$TABLE_ID}`)
);