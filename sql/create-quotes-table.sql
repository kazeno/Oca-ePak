CREATE TABLE IF NOT EXISTS `{$DB_PREFIX}{$TABLE_NAME}` (
    `reference` INT UNSIGNED NOT NULL,
    `postcode` INT UNSIGNED NOT NULL,
    `origin` INT UNSIGNED NOT NULL,
    `volume` float unsigned NOT NULL,
    `weight` float unsigned NOT NULL,
    `value` float unsigned NOT NULL,
    `price` float NOT NULL,
    `date` datetime NOT NULL,
    PRIMARY KEY (`reference`,`postcode`,`origin`,`volume`,`weight`,`value`),
    UNIQUE KEY `quote` (`reference`,`postcode`,`origin`,`volume`,`weight`,`value`)
)