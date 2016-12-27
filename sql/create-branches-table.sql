CREATE TABLE IF NOT EXISTS `{$DB_PREFIX}{$TABLE_NAME}` (
    `IdCentroImposicion` INT UNSIGNED NOT NULL,
    `Sucursal` VARCHAR(64),
    `Calle` VARCHAR(64),
    `Numero` VARCHAR(9),
    `Piso` VARCHAR(9),
    `Localidad` VARCHAR(64),
    `Provincia` VARCHAR(64),
    `Latitud` VARCHAR(64),
    `Longitud` VARCHAR(64),
    `CodigoPostal` VARCHAR(9),
    `postcode` VARCHAR(9),
    `date` datetime NOT NULL,
    PRIMARY KEY (`IdCentroImposicion`,`postcode`))