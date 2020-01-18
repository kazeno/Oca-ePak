<?php
/**
 * Oca e-Pak Module for Prestashop  https://github.com/kazeno/Oca-ePak
 *
 * @author    Rinku Kazeno
 * @license   MIT License  https://opensource.org/licenses/mit-license.php
 * @version 2.0
 * @file-version 1.3
 */

if (!defined('_PS_VERSION_'))
    exit;

function upgrade_module_1_3_0($module) {
    // Process Module upgrade for ajax transaction retrieval and use of carrier->id_reference
    if (!class_exists('KznCarrier'))
        include_once _PS_MODULE_DIR_."{$module->name}/classes/OcaCarrierTools.php";
    $tab = new Tab();
    $tab->active = 1;
    $tab->class_name = 'AdminOcaEpak';
    $tab->name = array();
    foreach (Language::getLanguages(true) as $lang)
        $tab->name[$lang['id_lang']] = 'Oca ePak';
    $tab->id_parent = -1;
    $tab->module = $module->name;
    return (
        Db::getInstance()->execute(
            OcaCarrierTools::interpolateSql('ALTER TABLE `{$DB_PREFIX}ocae_operatives` ADD COLUMN `carrier_reference` INT NULL AFTER `id_carrier`', array(
                '{$DB_PREFIX}' => _DB_PREFIX_
            ))
        ) AND
        Db::getInstance()->execute(
            OcaCarrierTools::interpolateSql('UPDATE `{$DB_PREFIX}ocae_operatives`  AS o SET `carrier_reference` = (SELECT `id_reference` FROM `{$DB_PREFIX}carrier` AS c WHERE c.id_carrier = o.id_carrier)', array(
                '{$DB_PREFIX}' => _DB_PREFIX_
            ))
        ) AND
        Db::getInstance()->execute(
            OcaCarrierTools::interpolateSql('ALTER TABLE `{$DB_PREFIX}ocae_operatives` DROP COLUMN `id_carrier`, DROP COLUMN `old_carriers`', array(
                '{$DB_PREFIX}' => _DB_PREFIX_
            ))
        ) AND
        Db::getInstance()->execute(
            OcaCarrierTools::interpolateSql('ALTER TABLE `{$DB_PREFIX}ocae_relays` ADD COLUMN `auto` INT NULL DEFAULT 0', array(
                '{$DB_PREFIX}' => _DB_PREFIX_
            ))
        ) AND
        $tab->add() AND
        $module->unregisterHook('updateCarrier')
    );
}

?>