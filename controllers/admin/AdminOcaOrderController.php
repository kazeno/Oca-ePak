<?php
/**
 * Oca e-Pak Module for Prestashop  https://github.com/kazeno/Oca-ePak
 *
 * @author    Rinku Kazeno
 * @license   MIT License  https://opensource.org/licenses/mit-license.php
 * @version 2.0
 * @file-version 1.4
 */

class AdminOcaOrderController extends ModuleAdminController
{
    public function ajaxProcessSticker()
    {
        $ocaOrder = new OcaEpakOrder((int)Tools::getValue('id_oca_order'));
        $sticker = $this->module->executeWebservice('GetHtmlDeEtiquetasPorOrdenOrNumeroEnvio', array(
            'idOrdenRetiro' => $ocaOrder->reference,
        ), true);
        die(str_replace(array('<div id="etiquetas"><div style="page-break-before: always;">', "<div id='etiquetas'><div style='page-break-before: always;'>"),
            array('<div id="etiquetas"><div>', "<div id='etiquetas'><div>"), $sticker));
    }
}
