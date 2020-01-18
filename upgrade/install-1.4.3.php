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

function upgrade_module_1_4_3($module) {
    return (
        Db::getInstance()->Execute(
            OcaCarrierTools::interpolateSqlFile($module->name, 'create-branches-table', array(
                '{$DB_PREFIX}' => _DB_PREFIX_,
                '{$TABLE_NAME}' => OcaEpak::BRANCHES_TABLE,
            ))
        ) AND
        $module->registerHook('displayHeader')
    );
}

?>