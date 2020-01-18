<?php
/**
 * Oca e-Pak Module for Prestashop  https://github.com/kazeno/Oca-ePak
 *
 * @author    Rinku Kazeno
 * @license   MIT License  https://opensource.org/licenses/mit-license.php
 * @version 2.0
 * @file-version 1.4.1
 */

if (!defined('_PS_VERSION_'))
    exit;

function upgrade_module_1_1_2($module) {
    // Process Module upgrade for quote cache
    return (
        Db::getInstance()->execute(
            'DROP TABLE IF EXISTS`' . pSQL(_DB_PREFIX_ . OcaEpak::QUOTES_TABLE) . '`'
        ) AND
        $module->unregisterHook('actionCartSave') AND
        Db::getInstance()->Execute(
            'CREATE TABLE IF NOT EXISTS `' . pSQL(_DB_PREFIX_ . OcaEpak::QUOTES_TABLE) . '` (
                 `reference` INT UNSIGNED NOT NULL,
                  `postcode` INT UNSIGNED NOT NULL,
                  `origin` INT UNSIGNED NOT NULL,
                  `volume` float unsigned NOT NULL,
                  `weight` float unsigned NOT NULL,
                  `price` float NOT NULL,
                  `date` datetime NOT NULL,
                  PRIMARY KEY (`reference`,`postcode`,`origin`,`volume`,`weight`),
                  UNIQUE KEY `quote` (`reference`,`postcode`,`origin`,`volume`,`weight`)
            )'
        )
    );
}

?>