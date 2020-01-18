<?php
/**
 * Oca e-Pak Module for Prestashop  https://github.com/kazeno/Oca-ePak
 *
 * @author    Rinku Kazeno
 * @license   MIT License  https://opensource.org/licenses/mit-license.php
 * @version 2.0
 * @file-version 0.2
 */

if (!defined('_PS_VERSION_'))
    exit;

function upgrade_module_1_0_4() {
    // Process Module upgrade for generate OCA order functionality
    return (
        Db::getInstance()->execute(
            'ALTER TABLE `' . pSQL(_DB_PREFIX_ . OcaEpak::OPERATIVES_TABLE) . '`
            ADD COLUMN `type` CHAR(3) NOT NULL,
            ADD COLUMN `old_carriers` VARCHAR(250) NULL,
            ADD COLUMN `insured` INT UNSIGNED NULL'
        ) AND
        Db::getInstance()->execute(
            'UPDATE `' . pSQL(_DB_PREFIX_ . OcaEpak::OPERATIVES_TABLE) . '` SET type = "PaP", insured = "0" WHERE 1'
        ) AND
        Db::getInstance()->Execute(
            'CREATE TABLE IF NOT EXISTS `' . pSQL(_DB_PREFIX_ . OcaEpak::RELAYS_TABLE) . '` (
                    `'.pSQL(OcaEpak::RELAYS_ID).'` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `id_cart` INT UNSIGNED NOT NULL,
                    `distribution_center_id` INT UNSIGNED NOT NULL,
                    PRIMARY KEY (`'.pSQL(OcaEpak::RELAYS_ID).'`)
                )'
        )
    );
}

?>