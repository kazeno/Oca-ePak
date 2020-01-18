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

function upgrade_module_1_4_1($module) {
    return (
        $module->registerHook('displayOrderDetail') AND
        Configuration::updateValue(OcaEpak::CONFIG_PREFIX.'GMAPS_API_KEY', '')
    );
}

?>