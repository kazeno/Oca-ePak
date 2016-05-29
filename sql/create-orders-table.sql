CREATE TABLE IF NOT EXISTS `{$DB_PREFIX}{$TABLE_NAME}` (
  `{$TABLE_ID}` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reference` INT UNSIGNED NOT NULL,
  `id_order` INT UNSIGNED NULL,
  `operation_code` INT NOT NULL,
  `tracking` VARCHAR(24) NOT NULL,
PRIMARY KEY (`{$TABLE_ID}`)
);