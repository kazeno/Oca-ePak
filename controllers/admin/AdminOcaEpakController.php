<?php
/**
 * Oca e-Pak Module for Prestashop  https://github.com/kazeno/Oca-ePak
 *
 * @author    Rinku Kazeno
 * @license   MIT License  https://opensource.org/licenses/mit-license.php
 * @file-version 2.1.2
 */

class AdminOcaEpakController extends ModuleAdminController
{
    /**
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function ajaxProcessCarrier()
    {
        $order = new Order((int)Tools::getValue('order_id'));
        $cart = new Cart($order->id_cart);
        $address = new Address($cart->id_address_delivery);
        $currency = new Currency($cart->id_currency);
        $carrier = new Carrier($cart->id_carrier);
        $op = OcaEpakOperative::getByFieldId('carrier_reference', $carrier->id_reference);
        if (!$op) {
            return null;
        }

        include_once _PS_MODULE_DIR_."{$this->module->name}/classes/OcaCarrierTools.php";

        $cartData = OcaCarrierTools::getCartPhysicalData(
            $cart,
            $cart->id_carrier,
            Configuration::get(OcaEpak::CONFIG_PREFIX.'DEFWEIGHT'),
            Configuration::get(OcaEpak::CONFIG_PREFIX.'DEFVOLUME'),
            OcaEpak::PADDING
        );
        $shipping = $cart->getTotalShippingCost(NULL, FALSE);
        $totalToPay = Tools::ps_round(OcaCarrierTools::applyFee($shipping, $op->addfee), 2);
        $paidFee = $totalToPay - $shipping;
        $relay = OcaEpakRelay::getByCartId($order->id_cart);
        try {
            $data = $this->module->executeWebservice('Tarifar_Envio_Corporativo', array(
                'PesoTotal' => $cartData['weight'],
                'VolumenTotal' => ($cartData['volume'] > 0.0001) ? $cartData['volume'] : 0.0001,
                'ValorDeclarado' => $cartData['cost'],
                'CodigoPostalOrigen' => Configuration::get(OcaEpak::CONFIG_PREFIX.'POSTCODE'),
                'CodigoPostalDestino' => OcaCarrierTools::cleanPostcode($address->postcode),
                'CantidadPaquetes' => 1,
                'Cuit' => Configuration::get(OcaEpak::CONFIG_PREFIX.'CUIT'),
                'Operativa' => $op->reference
            ));
            $quote = Tools::ps_round(
                OcaCarrierTools::convertCurrencyFromIso((string)$data->Total, 'ARS', $cart->id_currency),
                2
            );
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
        die(
            $this->module->display(
                _PS_MODULE_DIR_ . $this->module->name . DIRECTORY_SEPARATOR . $this->module->name . '.php',
                (_PS_VERSION_ < '1.6') ? 'displayAdminOrder15_ajax.tpl' : 'displayAdminOrder_ajax.tpl'
            )
        );
    }
}
