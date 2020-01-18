<?php
/**
 * Oca e-Pak Module for Prestashop  https://github.com/kazeno/Oca-ePak
 *
 * @author    Rinku Kazeno
 * @license   MIT License  https://opensource.org/licenses/mit-license.php
 * @version 2.0
 * @file-version 1.4
 */

if (!defined('_PS_VERSION_'))
    exit;

function upgrade_module_1_4_0($module) {
    if (!class_exists('KznCarrier'))
        include_once _PS_MODULE_DIR_."{$module->name}/classes/OcaCarrierTools.php";
    $tab = new Tab();
    $tab->active = 1;
    $tab->class_name = 'AdminOcaOrder';
    $tab->name = array();
    foreach (Language::getLanguages(true) as $lang)
        $tab->name[$lang['id_lang']] = 'Oca ePak Orders';
    $tab->id_parent = -1;
    $tab->module = $module->name;
    return (
        Db::getInstance()->Execute(
            OcaCarrierTools::interpolateSqlFile($module->name, 'create-orders-table', array(
                '{$DB_PREFIX}' => _DB_PREFIX_,
                '{$TABLE_NAME}' => OcaEpak::ORDERS_TABLE,
                '{$TABLE_ID}' => OcaEpak::ORDERS_ID
            ))
        ) AND
        Db::getInstance()->execute(
            OcaCarrierTools::interpolateSql('DROP TABLE IF EXISTS `{$DB_PREFIX}ocae_geocodes`', array(
                '{$DB_PREFIX}' => _DB_PREFIX_
            ))
        ) AND
        $tab->add() AND
        $module->registerHook('actionAdminPerformanceControllerBefore')
    );
}

?>