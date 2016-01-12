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
 *  @file-version 1.3
 */

class AdminOcaEpakController extends ModuleAdminController
{
    public function ajaxProcessCarrier()
    {
        $order = new Order((int)Tools::getValue('order_id'));
        $cart = new Cart($order->id_cart);
        $address = new Address($cart->id_address_delivery);
        $currency = new Currency($cart->id_currency);
        $carrier = new Carrier($cart->id_carrier);
        $op = OcaEpakOperative::getByFieldId('carrier_reference', $carrier->id_reference);
        if (!$op)
            return NULL;
        include_once _PS_MODULE_DIR_."{$this->module->name}/classes/KznCarrier.php";
        //$customer = new Customer($order->id_customer);
        $cartData = KznCarrier::getCartPhysicalData($cart, $cart->id_carrier, Configuration::get(OcaEpak::CONFIG_PREFIX.'DEFWEIGHT'), Configuration::get(OcaEpak::CONFIG_PREFIX.'DEFVOLUME'), OcaEpak::PADDING);
        $shipping = $cart->getTotalShippingCost(NULL, FALSE);
        $totalToPay = Tools::ps_round(KznCarrier::applyFee($shipping, $op->addfee), 2);
        $paidFee = $totalToPay - $shipping;
        $relay = OcaEpakRelay::getByCartId($order->id_cart);
        try {
            $data = $this->module->executeWebservice('Tarifar_Envio_Corporativo', array(
                'PesoTotal' => $cartData['weight'],
                'VolumenTotal' => $cartData['volume'],
                'ValorDeclarado' => $cartData['cost'],
                'CodigoPostalOrigen' => Configuration::get(OcaEpak::CONFIG_PREFIX.'POSTCODE'),
                'CodigoPostalDestino' => KznCarrier::cleanPostcode($address->postcode),
                'CantidadPaquetes' => 1,
                'Cuit' => Configuration::get(OcaEpak::CONFIG_PREFIX.'CUIT'),
                'Operativa' => $op->reference
            ));
            $quote = Tools::ps_round(KznCarrier::convertCurrencyFromIso($data->Total, 'ARS', $cart->id_currency), 2);
            $quoteError = null;
        } catch (Exception $e) {
            $quoteError = $e->getMessage();
            $data = null;
            $quote = null;
        }
        $distributionCenter = array();
        if (in_array($op->type, array('PaS','SaS')) && ($relay)) {
            $distributionCenter = $this->module->retrieveOcaBranchData($relay->distribution_center_id);
        }
        $this->context->smarty->assign(  array(
            'moduleName' => OcaEpak::MODULE_NAME,
            'currencySign' => $currency->sign,
            'operative' => $op,
            'cartData' => $cartData,
            'quote' => $quote,
            'quoteData' => $data,
            'quoteError' => $quoteError,
            'paidFee' => $paidFee,
            'distributionCenter' => $distributionCenter,
        ) );
        die($this->module->display(_PS_MODULE_DIR_.$this->module->name.DIRECTORY_SEPARATOR.$this->module->name.'.php', _PS_VERSION_ < '1.6' ? 'displayAdminOrder_ajax15.tpl' : 'displayAdminOrder_ajax.tpl'));
    }
}
