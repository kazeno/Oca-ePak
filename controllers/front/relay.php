<?php
/**
 * Oca e-Pak Module for Prestashop  https://github.com/kazeno/Oca-ePak
 *
 * @author    Rinku Kazeno
 * @license   MIT License  https://opensource.org/licenses/mit-license.php
 * @file-version 2.1.1
 */

class OcaepakRelayModuleFrontController extends ModuleFrontController
{
    public $display_header = FALSE;
	public $display_footer = FALSE;
    public $display_column_left = FALSE;
    public $display_column_right = FALSE;

    public function initContent()
    {
        parent::initContent();

        $id_cart = $this->context->cookie->id_cart;
        $relay = OcaEpakRelay::getByCartId($id_cart);
        if (!$relay) {
            $relay = new OcaEpakRelay();
            $relay->id_cart = $id_cart;
        }
        $relay->distribution_center_id = (int)Tools::getValue('distribution_center_id');
        $relay->auto = (int)Tools::getValue('auto');
        $relay->save();

        echo json_encode(array('status' => 'Success'));
        exit;
    }
}

?>