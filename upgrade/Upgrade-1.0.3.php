<?php
/*
 * File: /upgrade/Upgrade-1.0.3.php
 * @file-version 0.1
 */

function upgrade_module_1_0_3($module) {
    // Process Module upgrade for generate OCA order functionality
    return (
        Db::getInstance()->execute(
            'ALTER TABLE `' . _DB_PREFIX_ . OcaEpak::OPERATIVES_TABLE . '`
            ADD COLUMN `type` CHAR(3) NOT NULL,
            ADD COLUMN `insured` INT UNSIGNED NULL'
        ) AND
        Db::getInstance()->execute(
            'UPDATE `' . _DB_PREFIX_ . OcaEpak::OPERATIVES_TABLE . '` SET type = "PaP" WHERE 1'
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
    );
}

?>