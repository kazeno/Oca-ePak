CREATE TABLE IF NOT EXISTS `{$DB_PREFIX}{$TABLE_NAME}` (
  `{$TABLE_ID}` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_carrier` INT UNSIGNED NULL,
  `reference` INT UNSIGNED NOT NULL,
  `description` text NULL,
  `addfee` varchar(10) NULL,
  `id_shop` INT UNSIGNED NOT NULL,
  `type` CHAR(3) NOT NULL,
  `old_carriers` VARCHAR(250) NULL,
  `insured` INT UNSIGNED NULL,
PRIMARY KEY (`{$TABLE_ID}`)
);