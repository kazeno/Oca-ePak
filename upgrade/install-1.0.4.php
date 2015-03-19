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
 * @file-version 0.2
 */

if (!defined('_PS_VERSION_'))
    exit;

function upgrade_module_1_0_4() {
    // Process Module upgrade for generate OCA order functionality
    return (
        Db::getInstance()->execute(
            'ALTER TABLE `' . _DB_PREFIX_ . OcaEpak::OPERATIVES_TABLE . '`
            ADD COLUMN `type` CHAR(3) NOT NULL,
            ADD COLUMN `insured` INT UNSIGNED NULL'
        ) AND
        Db::getInstance()->execute(
            'UPDATE `' . _DB_PREFIX_ . OcaEpak::OPERATIVES_TABLE . '` SET type = "PaP", insured = "0" WHERE 1'
        ) AND
        Db::getInstance()->execute(
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . OcaEpak::ORDERS_TABLE . '` (
                    `'.OcaEpak::ORDERS_ID.'` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `reference` INT UNSIGNED NOT NULL,
                    `id_order` INT UNSIGNED NULL,
                    `status` VARCHAR(120) NULL,
                    `tracking` VARCHAR(24) NOT NULL,
                    PRIMARY KEY (`'.OcaEpak::ORDERS_ID.'`)
                )'
        ) AND
        Db::getInstance()->Execute(
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . OcaEpak::RELAYS_TABLE . '` (
                    `'.OcaEpak::RELAYS_ID.'` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `id_cart` INT UNSIGNED NOT NULL,
                    `distribution_center_id` INT UNSIGNED NOT NULL,
                    PRIMARY KEY (`'.OcaEpak::RELAYS_ID.'`)
                )'
        ) /* AND
        Configuration::updateValue(OcaEpak::CONFIG_PREFIX.'_ACCOUNT', '') AND
        Configuration::updateValue(OcaEpak::CONFIG_PREFIX.'_STREET', '') AND
        Configuration::updateValue(OcaEpak::CONFIG_PREFIX.'_NUMBER', '') AND
        Configuration::updateValue(OcaEpak::CONFIG_PREFIX.'_FLOOR', '') AND
        Configuration::updateValue(OcaEpak::CONFIG_PREFIX.'_APARTMENT', '') AND
        Configuration::updateValue(OcaEpak::CONFIG_PREFIX.'_LOCALITY', '') AND
        Configuration::updateValue(OcaEpak::CONFIG_PREFIX.'_PROVINCE', '') AND
        Configuration::updateValue(OcaEpak::CONFIG_PREFIX.'_CONTACT', '') AND
        Configuration::updateValue(OcaEpak::CONFIG_PREFIX.'_REQUESTOR', '') AND
        Configuration::updateValue(OcaEpak::CONFIG_PREFIX.'_OBSERVATIONS', '')
    */);
}

?>