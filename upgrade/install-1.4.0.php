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
 * @file-version 1.4
 */

if (!defined('_PS_VERSION_'))
    exit;

function upgrade_module_1_4_0($module) {
    if (!class_exists('KznCarrier'))
        include_once _PS_MODULE_DIR_."{$module->name}/classes/KznCarrier.php";
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
            KznCarrier::interpolateSqlFile($module->name, 'create-orders-table', array(
                '{$DB_PREFIX}' => _DB_PREFIX_,
                '{$TABLE_NAME}' => OcaEpak::ORDERS_TABLE,
                '{$TABLE_ID}' => OcaEpak::ORDERS_ID
            ))
        ) AND
        Db::getInstance()->execute(
            KznCarrier::interpolateSql('DROP TABLE IF EXISTS `{$DB_PREFIX}ocae_geocodes`', array(
                '{$DB_PREFIX}' => _DB_PREFIX_
            ))
        ) AND
        $tab->add() AND
        $module->registerHook('actionAdminPerformanceControllerBefore')
    );
}

?>