<?php
/**
 * Oca e-Pak Module for Prestashop  https://github.com/kazeno/Oca-ePak
 *
 * @author    Rinku Kazeno
 * @license   MIT License  https://opensource.org/licenses/mit-license.php
 * @version 2.0
 * @file-version 1.4.3
 */

if (!defined('_PS_VERSION_'))
    exit;

function upgrade_module_1_2_0($module) {
    // Process Module upgrade for new api quote cache
    if (!class_exists('KznCarrier'))
        include_once _PS_MODULE_DIR_."{$module->name}/classes/OcaCarrierTools.php";
    return (
        Db::getInstance()->execute(
            'DROP TABLE IF EXISTS`'.pSQL(_DB_PREFIX_.OcaEpak::QUOTES_TABLE).'`'
        ) AND
        Db::getInstance()->Execute(
            OcaCarrierTools::interpolateSqlFile($module->name, 'create-quotes-table', array(
                '{$DB_PREFIX}' => _DB_PREFIX_,
                '{$TABLE_NAME}' => OcaEpak::QUOTES_TABLE,
            ))
        )/* AND
        Db::getInstance()->Execute(
            OcaCarrierTools::interpolateSqlFile($module->name, 'create-geocodes-table', array(
                '{$DB_PREFIX}' => _DB_PREFIX_,
                '{$TABLE_NAME}' => OcaEpak::GEOCODES_TABLE,
                '{$TABLE_ID}' => OcaEpak::GEOCODES_ID
            ))
        ) AND
        Db::getInstance()->Execute(
            OcaCarrierTools::interpolateSqlFile($module->name, 'populate-geocodes-table', array(
                '{$DB_PREFIX}' => _DB_PREFIX_,
                '{$TABLE_NAME}' => OcaEpak::GEOCODES_TABLE,
                '{$TABLE_ID}' => OcaEpak::GEOCODES_ID
            ))

        )*/
    );
}

?>