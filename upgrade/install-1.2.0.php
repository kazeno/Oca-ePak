<?php
/**
 * Oca e-Pak Module for Prestashop
 *
 * @author    Rinku Kazeno <development@kazeno.co>
 *
 * @copyright Copyright (c) 2012-2015, Rinku Kazeno
 * @license   This module is licensed to the user, upon purchase
 *  from either Prestashop Addons or directly from the author,
 *  for use on a single commercial Prestashop install, plus an
 *  optional separate non-commercial install (for development/testing
 *  purposes only). This license is non-assignable and non-transferable.
 *  To use in additional Prestashop installations an additional
 *  license of the module must be purchased for each one.
 *
 *  The user may modify the source of this module to suit their
 *  own business needs, as long as no distribution of either the
 *  original module or the user-modified version is made.
 *
 * @file-version 1.2
 */

if (!defined('_PS_VERSION_'))
    exit;

function upgrade_module_1_2_0($module) {
    // Process Module upgrade for new api quote cache
    return (
        Db::getInstance()->execute(
            'DROP TABLE `'.pSQL(_DB_PREFIX_.OcaEpak::QUOTES_TABLE).'`'
        ) AND
        Db::getInstance()->Execute(
        /*'CREATE TABLE IF NOT EXISTS `' . pSQL(_DB_PREFIX_ . OcaEpak::QUOTES_TABLE) . '` (
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
        )'*/
            $module->interpolateSqlFile('create-quotes-table', array(
                '{$DB_PREFIX}' => _DB_PREFIX_,
                '{$TABLE_NAME}' => OcaEpak::QUOTES_TABLE,
            ))
        ) AND
        Db::getInstance()->Execute(
            $module->interpolateSqlFile('create-geocodes-table', array(
                '{$DB_PREFIX}' => _DB_PREFIX_,
                '{$TABLE_NAME}' => OcaEpak::GEOCODES_TABLE,
                '{$TABLE_ID}' => OcaEpak::GEOCODES_ID
            ))
        ) AND
        Db::getInstance()->Execute(
            $this->interpolateSqlFile('populate-geocodes-table', array(
                '{$DB_PREFIX}' => _DB_PREFIX_,
                '{$TABLE_NAME}' => OcaEpak::GEOCODES_TABLE,
                '{$TABLE_ID}' => OcaEpak::GEOCODES_ID
            ))

        )
    );
}

?>