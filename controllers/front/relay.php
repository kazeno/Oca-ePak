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
 *  @file-version 0.3
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

        //$OcaEpak = new OcaEpak();
        $id_cart = $this->context->cookie->id_cart;
        $relay = OcaEpakRelay::getByCartId($id_cart);
        if (!$relay) {
            $relay = new OcaEpakRelay();
            $relay->id_cart = $id_cart;
        }
        $relay->distribution_center_id = (int)Tools::getValue('distribution_center_id');
        $relay->save();

        echo Tools::jsonEncode(array('status' => 'Success'));
        exit;
    }
}

?>