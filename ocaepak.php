<?php
/**
 * Oca e-Pak Module for Prestashop
 * Tested in Prestashop v1.5.0.17, 1.5.6.2, 1.6.0.5, 1.6.0.6
 *
 *  @author Rinku Kazeno <development@kazeno.co>
 *  @version 1.1.0 Pre
 */

if (!defined( '_PS_VERSION_'))
    exit;

class OcaEpak extends CarrierModule
{
    const MODULE_NAME = 'ocaepak';                  //DON'T CHANGE!!
    const CONFIG_PREFIX = 'OCAEPAK';                //prefix for all internal config constants
    const CARRIER_NAME = 'Oca ePak';                //Carrier name string
    const CARRIER_DELAY = '2 a 8 días hábiles';     //Carrier default delay string
    const OPERATIVES_TABLE = 'ocae_operatives';     //DB table for Operatives
    const OPERATIVES_ID = 'id_ocae_operatives';     //DB table id for Operatives
    const QUOTES_TABLE = 'ocae_quotes';             //DB table for quotes
    const QUOTES_ID = 'id_ocae_quotes';             //DB table id for quotes
    const ORDERS_TABLE = 'ocae_orders';             //DB table for orders
    const ORDERS_ID = 'id_ocae_orders';             //DB table id for orders
    const TRACKING_URL = 'https://www1.oca.com.ar/OEPTrackingWeb/trackingenvio.asp?numero1=@';
    const OCA_URL = 'http://webservice.oca.com.ar/oep_tracking/Oep_Track.asmx?WSDL';

    const PADDING = 1;          //space to add around each product for volume calculations, in cm

    public $id_carrier;
    private $soapClient = NULL;
    protected  $guiHeader = '';

    public function __construct()
    {
        $this->name = 'ocaepak';            //DON'T CHANGE!!
        $this->tab = 'shipping_logistics';
        $this->version = '1.0.3';
        $this->author = 'R. Kazeno';
        $this->need_instance = 1;
        $this->ps_versions_compliancy = array('min' => '1.5', 'max' => '1.6');
        $this->module_key = '8ba7bceea44707dc9d6043606694cea5';
        $this->bootstrap = true;
        include_once _PS_MODULE_DIR_."{$this->name}/classes/OcaEpakOperative.php";

        parent::__construct();
        $this->displayName = 'OCA e-Pak';
        $this->description = $this->l('Offer your customers automatic real-time quotes of their deliveries through OCA e-Pak');
        $this->confirmUninstall = $this->l('This will delete any configured settings for this module. Continue?');
        $warnings = array();
        if (!extension_loaded('soap'))
            array_push($warnings, $this->l('You have the Soap PHP extension disabled. This module requires it for connecting to the Oca webservice.'));
        if (!Tools::strlen(Configuration::get(self::CONFIG_PREFIX.'_CUIT')) || !Tools::strlen(Configuration::get(self::CONFIG_PREFIX.'_ACCOUNT')))
            array_push($warnings, $this->l('You need to configure your account settings.'));
        $this->warning = implode(' | ', $warnings);
    }

    public function install()
    {
        //make translator aware of strings used in models
        $this->l('Optional fee format is incorrect. Should be either an amount, such as 7.50, or a percentage, such as 6.99%','OcaEpak');

        $db = Db::getInstance();
        return (
            $db->Execute(
                'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . self::OPERATIVES_TABLE . '` (
                    `'.self::OPERATIVES_ID.'` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `id_carrier` INT UNSIGNED NULL,
                    `reference` INT UNSIGNED NOT NULL,
                    `description` text NULL,
                    `addfee` varchar(10) NULL,
                    `id_shop` INT UNSIGNED NOT NULL,
                    `type` CHAR(3) NOT NULL,
                    `insured` INT UNSIGNED NULL,
                    PRIMARY KEY (`'.self::OPERATIVES_ID.'`)
                )'
            ) AND
            $db->Execute(
                'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . self::QUOTES_TABLE . '` (
                    `'.self::QUOTES_ID.'` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `reference` INT UNSIGNED NOT NULL,
                    `postcode` INT UNSIGNED NULL,
                    `origin` INT UNSIGNED NULL,
                    `volume` INT UNSIGNED NOT NULL,
                    `weight` INT UNSIGNED NOT NULL,
                    `price` FLOAT NOT NULL,
                    PRIMARY KEY (`'.self::QUOTES_ID.'`)
                )'
            ) AND
            $db->Execute(
                'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . self::ORDERS_TABLE . '` (
                    `'.self::ORDERS_ID.'` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `reference` INT UNSIGNED NOT NULL,
                    `id_order` INT UNSIGNED NULL,
                    `status` VARCHAR(120) NULL,
                    `tracking` VARCHAR(24) NOT NULL,
                    PRIMARY KEY (`'.self::ORDERS_ID.'`)
                )'
            ) AND
            parent::install() AND
            $this->registerHook(_PS_VERSION_ < '1.5' ? 'extraCarrier' : 'displayCarrierList') AND
            $this->registerHook('displayAdminOrder') AND
            $this->registerHook('actionCartSave') AND
            $this->registerHook('updateCarrier') AND
            Configuration::updateValue(self::CONFIG_PREFIX.'_ACCOUNT', '') AND
            Configuration::updateValue(self::CONFIG_PREFIX.'_EMAIL', '') AND
            Configuration::updateValue(self::CONFIG_PREFIX.'_PASSWORD', '') AND
            Configuration::updateValue(self::CONFIG_PREFIX.'_CUIT', '') AND
            Configuration::updateValue(self::CONFIG_PREFIX.'_DEFWEIGHT', '0.25') AND
            Configuration::updateValue(self::CONFIG_PREFIX.'_DEFVOLUME', '5000') AND
            Configuration::updateValue(self::CONFIG_PREFIX.'_FAILCOST', '60') AND
            Configuration::updateValue(self::CONFIG_PREFIX.'_POSTCODE', '') AND
            Configuration::updateValue(self::CONFIG_PREFIX.'_STREET', '') AND
            Configuration::updateValue(self::CONFIG_PREFIX.'_NUMBER', '') AND
            Configuration::updateValue(self::CONFIG_PREFIX.'_FLOOR', '') AND
            Configuration::updateValue(self::CONFIG_PREFIX.'_APARTMENT', '') AND
            Configuration::updateValue(self::CONFIG_PREFIX.'_LOCALITY', '') AND
            Configuration::updateValue(self::CONFIG_PREFIX.'_PROVINCE', '') AND
            Configuration::updateValue(self::CONFIG_PREFIX.'_CONTACT', '') AND
            Configuration::updateValue(self::CONFIG_PREFIX.'_REQUESTOR', '') AND
            Configuration::updateValue(self::CONFIG_PREFIX.'_OBSERVATIONS', '') AND
            Configuration::updateValue(self::CONFIG_PREFIX.'_BOXES', '') AND
            Configuration::updateValue(self::CONFIG_PREFIX.'_AUTO_ORDER', false)
        );
    }

    public function uninstall()
    {
        $db = Db::getInstance();
        OcaEpakOperative::purgeCarriers();
        return (
            parent::uninstall() AND
            $db->Execute(
                'DROP TABLE IF EXISTS '._DB_PREFIX_.self::OPERATIVES_TABLE
            ) AND
            $db->Execute(
                'DROP TABLE IF EXISTS '._DB_PREFIX_.self::QUOTES_TABLE
            ) AND
            Configuration::deleteByName(self::CONFIG_PREFIX.'_ACCOUNT') AND
            Configuration::deleteByName(self::CONFIG_PREFIX.'_EMAIL') AND
            Configuration::deleteByName(self::CONFIG_PREFIX.'_PASSWORD') AND
            Configuration::deleteByName(self::CONFIG_PREFIX.'_CUIT') AND
            Configuration::deleteByName(self::CONFIG_PREFIX.'_DEFWEIGHT') AND
            Configuration::deleteByName(self::CONFIG_PREFIX.'_DEFVOLUME') AND
            Configuration::deleteByName(self::CONFIG_PREFIX.'_FAILCOST') AND
            Configuration::deleteByName(self::CONFIG_PREFIX.'_POSTCODE') AND
            Configuration::deleteByName(self::CONFIG_PREFIX.'_STREET') AND
            Configuration::deleteByName(self::CONFIG_PREFIX.'_NUMBER') AND
            Configuration::deleteByName(self::CONFIG_PREFIX.'_FLOOR') AND
            Configuration::deleteByName(self::CONFIG_PREFIX.'_APARTMENT') AND
            Configuration::deleteByName(self::CONFIG_PREFIX.'_LOCALITY') AND
            Configuration::deleteByName(self::CONFIG_PREFIX.'_PROVINCE') AND
            Configuration::deleteByName(self::CONFIG_PREFIX.'_CONTACT') AND
            Configuration::deleteByName(self::CONFIG_PREFIX.'_REQUESTOR') AND
            Configuration::deleteByName(self::CONFIG_PREFIX.'_OBSERVATIONS') AND
            Configuration::deleteByName(self::CONFIG_PREFIX.'_BOXES') AND
            Configuration::deleteByName(self::CONFIG_PREFIX.'_AUTO_ORDER')
        );
    }

    public function getContent()
    {
        if (Tools::isSubmit('addOcaOperative'))
            return $this->getAddOperativeContent();
        elseif (Tools::isSubmit('deleteocae_operatives'))        {
            $op = new OcaEpakOperative((int)Tools::getValue('id_ocae_operatives'));
            $ref = $op->reference;
            $this->_addToHeader(
                $op->delete()
                    ? $this->displayConfirmation($this->l('OCA Operative')." $ref ".$this->l('and its carrier have been successfully deleted'))
                : $this->displayError('Error deleting OCA Operative')
            );
        } elseif (Tools::isSubmit('saveOcaOperative')) {
            $op = new OcaEpakOperative();
            $op->reference = Tools::getValue('reference');
            $op->id_shop = $this->context->shop->id;
            $op->description = Tools::getValue('description');
            $op->addfee = Tools::getValue('addfee');
            $val = $op->validateFields(FALSE, TRUE);
            if ($val !== TRUE)
                return $this->displayError($this->_makeErrorFriendly($val)).$this->getAddOperativeContent();
            $op->save();
            $this->_addToHeader($this->displayConfirmation($this->l('New OCA Operative and its carrier have been successfully created')));
        } elseif (Tools::isSubmit('submitOcaepak'))
            $this->_addToHeader(($error = $this->_getErrors()) ? $error : $this->_saveConfig());
        if (Tools::strlen(Configuration::get(self::CONFIG_PREFIX.'_EMAIL')) && Tools::strlen(Configuration::get(self::CONFIG_PREFIX.'_PASSWORD'))&& Tools::strlen(Configuration::get(self::CONFIG_PREFIX.'_POSTCODE')) && Tools::strlen(Configuration::get(self::CONFIG_PREFIX.'_CUIT')) && ($ops = OcaEpakOperative::getOperativeIds())) {
            //Check API Access is working correctly
            foreach ($ops as $op) {
                try {
                    $response = $this->_getSoapClient()->Tarifar_Envio_Corporativo(array(
                        'PesoTotal' => '1',
                        'VolumenTotal' => '50',
                        'CodigoPostalOrigen' => Configuration::get(self::CONFIG_PREFIX.'_POSTCODE'),
                        'CodigoPostalDestino' => Configuration::get(self::CONFIG_PREFIX.'_POSTCODE') == 9120 ? 1924 : 9120,
                        'CantidadPaquetes' => 1,
                        'Cuit' => Configuration::get(self::CONFIG_PREFIX.'_CUIT'),
                        'Operativa' => $op->reference
                    ));
                    $xml = new SimpleXMLElement($response->Tarifar_Envio_CorporativoResult->any);
                    if (!$xml->count())
                        $this->_addToHeader($this->displayError($this->l('There seems to be an error in the OCA operative with reference')." {$op->reference}"));
                } catch (Exception $e) {
                    $this->_addToHeader($this->displayError($e->getMessage()));
                }
            }
        }
        ob_start(); ?>
        <script>//<!--
            $(document).ready(function() {
              <?php if (_PS_VERSION_ < '1.6') : ?>
                var $form1 = $('#content>form').first().attr("id", "form1");
                var $form2 = $('#content>form').last().attr("id", "form2");
                var $table = $('#content>form').not($form1).not($form2).attr("id", 'ocae_operatives');
              <?php else : ?>
                var $form1 = $('#form1');
                var $form2 = $('#form2');
                var $table = $('#ocae_operatives');
              <?php endif; ?>
                function syncInputs(event) {
                    event.data.target.find("[name='"+$(this).attr("name")+"']").val($(this).val());
                    $table.find("[name='"+$(this).attr("name")+"']").val($(this).val());
                }
                $('#desc-ocae_operatives-refresh').hide();
                $('tr.filter').hide();
                $('#desc-ocae_operatives-new').bind('click', function() {
                    $table.attr('action', $(this).attr('href')).submit();
                    return false;
                });
                $form1.add($form2).find("input[type='hidden']").clone().appendTo($table);
                $form1.find("input[type='text']").bind('change', {target: $form2}, syncInputs);
                $form2.find("input[type='text']").bind('change', {target: $form1}, syncInputs);
            });
        //--></script>
        <?php $this->_addToHeader(ob_get_clean());

        $fields_form1 = array(
            'form' => array(
                'form' => array(
                    'id_form' => 'form1',
                    'legend' => array(
                        'title' => '1. '.$this->l('OCA User Account')
                    ),
                    'input' => array(
                        array(
                            'type' => 'text',
                            'label' => $this->l('Email'),
                            'name' => 'email',
                            'class' => 'fixed-width-xxl'
                        ),
                        array(
                            'type' => 'text',
                            'label' => $this->l('Password'),
                            'name' => 'password',
                            'class' => 'fixed-width-xxl',
                        ),
                        array(
                            'type' => 'text',
                            'label' => $this->l('OCA Account Number'),
                            'name' => 'account',
                            'class' => 'fixed-width-xxl',
                            'desc' => $this->l('You can find your account number by logging into your OCA account and going to the following URL').': <br /><a href="http://www4.oca.com.ar/ocaepak/Seguro/ListadoOperativas.asp" target="_blank" style="text-decoration: underline; font-weight: 700;" />http://www4.oca.com.ar/ocaepak/Seguro/ListadoOperativas.asp</a>'
                        ),
                        array(
                            'type' => 'text',
                            'label' => $this->l('CUIT'),
                            'name' => 'cuit',
                            'class' => 'fixed-width-lg'
                        ),
                        array(
                            'type' => 'hidden',
                            'name' => 'defweight',
                        ),
                        array(
                            'type' => 'hidden',
                            'name' => 'defvolume',
                        ),
                        array(
                            'type' => 'hidden',
                            'name' => 'postcode',
                        ),
                        array(
                            'type' => 'hidden',
                            'name' => 'failcost',
                        ),
                    ),
                    'desc' =>  $this->l('To add your OCA operatives, click on the "add" button at the top of the following table'),
                )
            ),
        );
        $fields_form2 = array(
            'form' => array(
                'form' => array(
                    'id_form' => 'form2',
                    'legend' => array(
                        'title' => '3. '.$this->l('Shipments')
                    ),
                    'description' => $this->l('The address from where shipments will be made'),
                    'input' => array(
                        array(
                            'type' => 'text',
                            'maxlength' => 30,
                            'label' => $this->l('Street'),
                            'name' => 'street',
                            'class' => 'fixed-width-lg',
                        ),
                        array(
                            'type' => 'text',
                            'maxlength' => 5,
                            'label' => $this->l('Number'),
                            'name' => 'number',
                            'class' => 'fixed-width-lg',
                        ),
                        array(
                            'type' => 'text',
                            'maxlength' => 2,
                            'label' => $this->l('Floor'),
                            'name' => 'floor',
                            'class' => 'fixed-width-lg',
                        ),
                        array(
                            'type' => 'text',
                            'maxlength' => 4,
                            'label' => $this->l('Apartment'),
                            'name' => 'apartment',
                            'class' => 'fixed-width-lg',
                        ),
                        array(
                            'type' => 'text',
                            'size' => 4,
                            'label' => $this->l('Origin Post Code'),
                            'name' => 'postcode',
                            'class' => 'fixed-width-lg',
                            'desc' => $this->l('The post code from where shipments will be made (only digits)')

                        ),
                        array(
                            'type' => 'text',
                            'maxlength' => 30,
                            'label' => $this->l('Locality'),
                            'name' => 'locality',
                            'class' => 'fixed-width-lg',
                        ),
                        array(
                            'type' => 'text',
                            'maxlength' => 30,
                            'label' => $this->l('Province'),
                            'name' => 'province',
                            'class' => 'fixed-width-lg',
                        ),
                        array(
                            'type' => 'text',
                            'maxlength' => 30,
                            'label' => $this->l('Contact'),
                            'name' => 'contact',
                            'class' => 'fixed-width-lg',
                        ),
                        array(
                            'type' => 'text',
                            'maxlength' => 30,
                            'label' => $this->l('Requestor'),
                            'name' => 'requestor',
                            'class' => 'fixed-width-lg',
                        ),
                        array(
                            'type' => 'text',
                            'maxlength' => 100,
                            'label' => $this->l('Observations'),
                            'name' => 'observations',
                            'class' => 'fixed-width-xl',
                        ),
                    ),
                )
            ),
            array(
                'form' => array(
                    'form' => array(
                        'id_form' => 'form2',
                    ),
                    'legend' => array(
                        'title' => '4. '.$this->l('Failsafes')
                    ),
                    'description' => $this->l('These settings will be used in case of missing data in your products'),
                    'input' => array(
                        array(
                            'type' => 'text',
                            'label' => $this->l('Default Weight'),
                            'name' => 'defweight',
                            'class' => 'fixed-width-xxl',
                            'desc' => $this->l('Weight to use for products without registered weight data, in kg')
                        ),
                        array(
                            'type' => 'text',
                            'label' => $this->l('Default Volume'),
                            'name' => 'defvolume',
                            'class' => 'fixed-width-xxl',
                            'desc' => $this->l('Volume to use for products without registered size data, in cubic cm')
                        ),
                        array(
                            'type' => 'text',
                            'label' => $this->l('Failsafe Shipping Cost'),
                            'name' => 'failcost',
                            'class' => 'fixed-width-xxl',
                            'desc' => $this->l('This is the shipping cost that will be used in the unlikely event the OCA server is down and we cannot get an online quote')
                        ),
                        array(
                            'type' => 'hidden',
                            'name' => 'email',
                        ),
                        array(
                            'type' => 'hidden',
                            'name' => 'password',
                        ),
                        array(
                            'type' => 'hidden',
                            'name' => 'cuit',
                        ),
                    ),
                    'submit' => array(
                        'name' => 'submitOcaepak',
                        'title' => $this->l('Save'),
                        'class' => _PS_VERSION_ < 1.6 ? 'button' : NULL
                    )
                )
            )
        );
        $fields_value = array(
            'account' => Tools::getValue('account', Configuration::get(self::CONFIG_PREFIX.'_ACCOUNT')),
            'email' => Tools::getValue('email', Configuration::get(self::CONFIG_PREFIX.'_EMAIL')),
            'password' => Tools::getValue('password', Configuration::get(self::CONFIG_PREFIX.'_PASSWORD')),
            'cuit' => Tools::getValue('cuit', Configuration::get(self::CONFIG_PREFIX.'_CUIT')),
            'defweight' => Tools::getValue('defweight', Configuration::get(self::CONFIG_PREFIX.'_DEFWEIGHT')),
            'defvolume' => Tools::getValue('defvolume', Configuration::get(self::CONFIG_PREFIX.'_DEFVOLUME')),
            'postcode' => Tools::getValue('postcode', Configuration::get(self::CONFIG_PREFIX.'_POSTCODE')),
            'failcost' => Tools::getValue('failcost', Configuration::get(self::CONFIG_PREFIX.'_FAILCOST')),
        );

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->title = $this->displayName;
        $helper->name_controller = $this->name;
        $helper->identifier = $this->identifier;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = _PS_VERSION_ < '1.5' ? "index.php?tab=AdminModules&configure={$this->name}" : $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->show_toolbar = false;
        $helper->submit_action = '';
        $helper->fields_value = $fields_value;

        return $this->guiHeader.$helper->generateForm($fields_form1).$this->renderOperativesList().$helper->generateForm($fields_form2);
    }

    public function getAddOperativeContent()
    {
        $fields_form = array(
            array(
                'form' => array(
                    'legend' => array(
                        'title' => $this->l('New OCA Operative')
                    ),
                    'description' => $this->l('You can find your OCA operatives by logging into your OCA account and going to the following URL').': <br /><a href="http://www4.oca.com.ar/ocaepak/Seguro/ListadoOperativas.asp" target="_blank" style="text-decoration: underline; font-weight: 700;" />http://www4.oca.com.ar/ocaepak/Seguro/ListadoOperativas.asp</a>',
                    'input' => array(
                        array(
                            'type' => 'text',
                            'label' => $this->l('Operative Reference'),
                            'name' => 'reference',
                            'class' => 'fixed-width-lg',
                            'desc' =>  $this->l('This is the number id of the operative')
                        ),
                        array(
                            'type' => 'text',
                            'label' => $this->l('Description'),
                            'name' => 'description',
                            'class' => 'fixed-width-xxl',
                            'desc' =>  $this->l('This will be displayed at checkout as the description of the shipping carrier corresponding to this operative')
                        ),
                        array(
                            'type' => 'select',
                            'label' => $this->l('Type'),
                            'name' => 'type',
                            'options' => array(
                                'query' => array(
                                    array('value' =>'PaP', 'text' =>'Puerta a Puerta (PaP)'),
                                    array('value' =>'SaP', 'text' =>'Sucursal a Puerta (SaP)'),
                                    array('value' =>'PaS', 'text' =>'Puerta a Sucursal (PaS)'),
                                    array('value' =>'SaS', 'text' =>'Sucursal a Sucursal (SaS)'),
                                ),
                                'id' => 'value',
                                'name' => 'text'
                            ),
                        ),
                        array(
                            'type' => _PS_VERSION_ < 1.6 ? 'radio' : 'switch',
                            'label' => $this->l('Insured by OCA'),
                            'name' => 'insured',
                            'class' => 't',
                            'is_bool' => TRUE,
                            'values' => array(
                                array(
                                    'id' => 'insured_on',
                                    'value' => '1',
                                    'label' => 'on',
                                ),
                                array(
                                    'id' => 'insured_off',
                                    'value' => '0',
                                    'label' => 'off',
                                ),
                            ),
                        ),
                        array(
                            'type' => 'text',
                            'label' => $this->l('Optional Fee'),
                            'name' => 'addfee',
                            'class' => 'fixed-width-lg',
                            'desc' =>  $this->l('Additional fee you wish to charge customers who choose this shipping option. Can be either a fixed amount (without the percent sign) or a percentage on top of the estimated shipping cost (ending with the percent sign). Set to either 0.00 or 0.00% to disable.')
                        ),
                        array(
                            'type' => 'hidden',
                            'name' => 'account',
                        ),
                        array(
                            'type' => 'hidden',
                            'name' => 'email',
                        ),
                        array(
                            'type' => 'hidden',
                            'name' => 'password',
                        ),
                        array(
                            'type' => 'hidden',
                            'name' => 'cuit',
                        ),
                        array(
                            'type' => 'hidden',
                            'name' => 'defweight',
                        ),
                        array(
                            'type' => 'hidden',
                            'name' => 'defvolume',
                        ),
                        array(
                            'type' => 'hidden',
                            'name' => 'postcode',
                        ),
                        array(
                            'type' => 'hidden',
                            'name' => 'failcost',
                        ),
                    ),
                    'submit' => array(
                        'title' => $this->l('Save'),
                    ),
                    'buttons' => array(
                        array(
                            'js' => "javascript: $('input[name=\'saveOcaOperative\']').remove(); $('#configuration_form').submit()",
                            'title' => $this->l('Back to main page'),
                            'icon' => 'process-icon-back'
                        )
                    )
                )
            ),
        );
        $fields_value = array(
            'reference' => Tools::getValue('reference', ''),
            'description' => Tools::getValue('description', ''),
            'addfee' => Tools::getValue('addfee', '0.00%'),
            'type' => Tools::getValue('type', 'PaP'),
            'insured' => Tools::getValue('insured', false),

            //Preserve previously input data:
            'account' => Tools::getValue('account', Configuration::get(self::CONFIG_PREFIX.'_ACCOUNT')),
            'email' => Tools::getValue('email', Configuration::get(self::CONFIG_PREFIX.'_EMAIL')),
            'password' => Tools::getValue('password', Configuration::get(self::CONFIG_PREFIX.'_PASSWORD')),
            'cuit' => Tools::getValue('cuit', Configuration::get(self::CONFIG_PREFIX.'_CUIT')),
            'defweight' => Tools::getValue('defweight', Configuration::get(self::CONFIG_PREFIX.'_DEFWEIGHT')),
            'defvolume' => Tools::getValue('defvolume', Configuration::get(self::CONFIG_PREFIX.'_DEFVOLUME')),
            'postcode' => Tools::getValue('postcode', Configuration::get(self::CONFIG_PREFIX.'_POSTCODE')),
            'failcost' => Tools::getValue('failcost', Configuration::get(self::CONFIG_PREFIX.'_FAILCOST')),
        );

        $helper = new HelperForm();

        $helper->module = $this;
        $helper->title = $this->displayName;
        $helper->name_controller = $this->name;
        $helper->identifier = $this->identifier;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = _PS_VERSION_ < '1.5' ? "index.php?tab=AdminModules&configure={$this->name}" : $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->submit_action = 'saveOcaOperative';
        $helper->fields_value = $fields_value;

        return $helper->generateForm($fields_form);
    }

    public function renderOperativesList()
    {
        $content = Db::getInstance()->executeS('SELECT * FROM `'._DB_PREFIX_.self::OPERATIVES_TABLE.'` WHERE 1 '.Shop::addSqlRestriction().' ORDER BY reference');
        $fields_list = array(
            'reference' => array(
                'title' => $this->l('Operative'),
                'type' => 'text',
                'search' => false,
                'orderby' => false,
            ),
            'description' => array(
                'title' => $this->l('Description'),
                'type' => 'text',
                'search' => false,
                'orderby' => false,
            ),
            'type' => array(
                'title' => $this->l('Type'),
                'type' => 'text',
                'search' => false,
                'orderby' => false,
            ),
            'insured' => array(
                'title' => $this->l('Insured'),
                'type' => 'bool',
                'search' => false,
                'orderby' => false,
            ),
            'addfee' => array(
                'title' => $this->l('Charged Additional Fee'),
                'type' => 'text',
                'search' => false,
                'orderby' => false,
            ),
            'id_carrier' => array(
                'title' => $this->l('Carrier ID'),
                'type' => 'text',
                'search' => false,
                'orderby' => false,
            ),
        );

        $helper = new HelperList();
        $helper->module = $this;
        $helper->title = '2. '.$this->l('OCA Operatives');
        $helper->shopLinkType = '';
        $helper->simple_header = FALSE;
        $helper->identifier = self::OPERATIVES_ID;
        $helper->table = self::OPERATIVES_TABLE;
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->actions = array('delete');
        $helper->show_toolbar = TRUE;
        $helper->no_link = TRUE;
        $helper->toolbar_btn['new'] =  array(
            'href' => AdminController::$currentIndex.'&configure='.$this->name.'&addOcaOperative&token='.Tools::getAdminTokenLite('AdminModules'),
            'desc' => $this->l('Add new operative')
        );

        return $helper->generateList($content, $fields_list);
    }

    protected function _addToHeader($html)
    {
        $this->guiHeader .= "\n$html";

        return $this;
    }

    protected function _getErrors()
    {
        $error = '';
        if (!Tools::strlen(trim(Tools::getValue('account'))))
            $error .= $this->displayError($this->l('Invalid account number'));
        if (!Validate::isEmail(Tools::getValue('email')))
            $error .= $this->displayError($this->l('Invalid email'));
        if (!Validate::isPasswd(Tools::getValue('password'), 3))
            $error .= $this->displayError($this->l('Invalid password'));
        if (!Tools::strlen(trim(Tools::getValue('cuit'))))
            $error .= $this->displayError($this->l('Invalid CUIT'));
        if (!is_numeric(Tools::getValue('defweight')))
            $error .= $this->displayError($this->l('Invalid failsafe default weight'));
        if (!is_numeric(Tools::getValue('defvolume')))
            $error .= $this->displayError($this->l('Invalid failsafe default volume'));
        if (!is_numeric(Tools::getValue('failcost')))
            $error .= $this->displayError($this->l('Invalid failsafe shipping cost'));
        if (!Validate::isUnsignedInt(Tools::getValue('postcode')))
            $error .= $this->displayError($this->l('Invalid postcode'));
        if (!OcaEpakOperative::isCurrentlyUsed(OcaEpak::OPERATIVES_TABLE))
            $error .= $this->displayError($this->l('You need to add at least one operative'));

        return $error;
    }

    protected function _saveConfig()
    {
        Configuration::updateValue(self::CONFIG_PREFIX.'_ACCOUNT', trim(Tools::getValue('account')));
        Configuration::updateValue(self::CONFIG_PREFIX.'_EMAIL', trim(Tools::getValue('email')));
        Configuration::updateValue(self::CONFIG_PREFIX.'_PASSWORD', Tools::getValue('password'));
        Configuration::updateValue(self::CONFIG_PREFIX.'_CUIT', trim(Tools::getValue('cuit')));
        Configuration::updateValue(self::CONFIG_PREFIX.'_DEFWEIGHT', Tools::getValue('defweight'));
        Configuration::updateValue(self::CONFIG_PREFIX.'_DEFVOLUME', Tools::getValue('defvolume'));
        Configuration::updateValue(self::CONFIG_PREFIX.'_FAILCOST', Tools::getValue('failcost'));
        Configuration::updateValue(self::CONFIG_PREFIX.'_POSTCODE', Tools::getValue('postcode'));

        return $this->displayConfirmation($this->l('Configuration saved'));
    }

    public function hookDisplayAdminOrder($params)
    {
        //**/unset($params['smarty']);
        //**/Tools::dieObject($params, false);
        //**/Tools::dieObject($params['cart']->id_carrier, false);
        $op = OcaEpakOperative::getByFieldId('id_carrier', $params['cart']->id_carrier);
        if (!$op)
            return NULL;
        $address = new Address($params['cart']->id_address_delivery);
        $currency = new Currency($params['cart']->id_currency);
        $order = new Order($params['id_order']);
        $cartData = $this->getCartPhysicalData($params['cart']);
        $shipping = $params['cart']->getTotalShippingCost(NULL, FALSE);
        $totalToPay = Tools::ps_round($this->getTotalWithFee($shipping, $op->addfee), 2);
        $paidFee = $totalToPay - $shipping;
        try {
            $response = $this->_getSoapClient()->Tarifar_Envio_Corporativo(array(
                'PesoTotal' => $cartData['weight'],
                'VolumenTotal' => $cartData['volume'],
                'CodigoPostalOrigen' => Configuration::get(self::CONFIG_PREFIX.'_POSTCODE'),
                'CodigoPostalDestino' => $this->cleanPostcode($address->postcode),
                'CantidadPaquetes' => 1,
                'Cuit' => Configuration::get(self::CONFIG_PREFIX.'_CUIT'),
                'Operativa' => $op->reference
            ));
            $xml = new SimpleXMLElement($response->Tarifar_Envio_CorporativoResult->any);
            if (!$xml->count())
                throw new Exception($this->l('No results received from OCA webservice'));
            $data = $xml->NewDataSet->Table;
            $quote = Tools::ps_round($this->convertCurrencyFromArs($data->Precio, $params['cart']->id_currency), 2);
        } catch (Exception $e) {
            $quote = $e->getMessage();
        }

        ob_start();
        ?>
<!--        <fieldset class="panel" style="width: 400px; position: relative; left: 10px; margin-top: 26px;">-->
        <fieldset class="panel" >
            <legend><img src="../modules/ocaepak/logo.gif" alt="logo" /><?php echo $this->l('OCA ePak Information'); ?></legend>
            <?php echo $this->l('Operative') . ": {$op->reference}"; ?><br />
            <?php echo $this->l('Calculated Order Weight') . ": {$cartData['weight']} kg"; ?><br />
            <?php echo $this->l('Calculated Order Volume (with padding)') . ": {$cartData['volume']} cm³"; ?><br />
            <?php if ($paidFee != 0):
                echo $this->l('Additional fee') . ": {$currency->sign}{$paidFee}";
            endif; ?><br />
            <?php if (is_string($quote)): ?>
                <div class="warn">
                     <?php  echo $quote;  ?>
                </div>
            <?php  else: ?>
                <?php echo $this->l('Live quote') . ": {$currency->sign}{$quote}"; ?><br />
            <?php endif; ?>
        </fieldset>
        <?php
        return ob_get_clean();
    }

    public function hookActionCartSave($params) { return NULL; }    ## @todo async price check

    /**
     * Placeholder for future functionality
     * @param Array $params ['address', 'cart', 'cookie']
     */
    public function hookDisplayCarrierList($params)
    {
        /**if (!$this->active OR $params['address']->id_country != Country::getByIso('AR'))
            return FALSE;
        return '<pre>'.print_r($this->getCartPhysicalData($params['cart']), TRUE).'</pre>';/**/
        return NULL;
    }
    public function hookExtraCarrier($params) { return $this->hookDisplayCarrierList($params); }

    public function hookUpdateCarrier($params)
    {
        if ($op = OcaEpakOperative::getByFieldId('id_carrier', $params['id_carrier'])) {
            $op->id_carrier = (int)($params['carrier']->id);
            return $op->save();
        }
        return true;
    }


    public function getOrderShippingCost($cart, $shipping_cost)
    {
        $address = new Address($cart->id_address_delivery);
        $op = OcaEpakOperative::getByFieldId('id_carrier', $this->id_carrier);
        if (!$this->active OR $address->id_country != Country::getByIso('AR') OR !$op)
            return FALSE;
        try {
            $cartData = $this->getCartPhysicalData($cart);
            $response = $this->_getSoapClient()->Tarifar_Envio_Corporativo(array(
                'PesoTotal' => $cartData['weight'],
                'VolumenTotal' => $cartData['volume'],
                'CodigoPostalOrigen' => Configuration::get(self::CONFIG_PREFIX.'_POSTCODE'),
                'CodigoPostalDestino' => $this->cleanPostcode($address->postcode),
                'CantidadPaquetes' => 1,
                'Cuit' => Configuration::get(self::CONFIG_PREFIX.'_CUIT'),
                'Operativa' => $op->reference
            ));
            $xml = new SimpleXMLElement($response->Tarifar_Envio_CorporativoResult->any);
            if (!$xml->count())
                throw new Exception('No results from OCA webservice');
            $data = $xml->NewDataSet->Table;
        } catch (Exception $e) {
            Logger::AddLog('Ocaepak: '.$this->l('error getting online price for cart')." {$cart->id}");
            return (float)$this->convertCurrencyFromArs(Configuration::get(self::CONFIG_PREFIX.'_FAILCOST'), $cart->id_currency);
        }
        return (float)Tools::ps_round($this->convertCurrencyFromArs($this->getTotalWithFee($data->Precio, $op->addfee), $cart->id_currency), 2);
    }
    public function getOrderShippingCostExternal($params)
    {
        return $this->getOrderShippingCost($params, Configuration::get(self::CONFIG_PREFIX.'_FAILCOST'));
    }

    public function getCartPhysicalData($cart)
    {
        $products = $cart->getProducts();
        $weight = 0;
        $volume = 0;
        foreach ($products as $product) {
            if ($product['is_virtual'])
                continue;
            $weight += ($product['weight'] > 0 ? $product['weight'] : Configuration::get(self::CONFIG_PREFIX.'_DEFWEIGHT')) * $product['cart_quantity'];
            $volume += (
                $product['width']*$product['height']*$product['depth'] > 0 ?
                    ($product['width']+2*self::PADDING)*($product['height']+2*self::PADDING)*($product['depth']+2*self::PADDING) :
                    Configuration::get(self::CONFIG_PREFIX.'_DEFVOLUME')
            )*$product['cart_quantity'];
        }

        return array('weight' => $weight, 'volume' => $volume);
    }

    public function cleanPostcode($postcode)
    {
        return preg_replace("/[^0-9]/", "", $postcode);
    }

    public function convertCurrencyFromArs($quantity, $currency_id)
    {
        if (($id_ars = Currency::getIdByIsoCode('ARS')) != $currency_id) {
            $currentCurrency = new Currency($currency_id);
            $ars = new Currency($id_ars);
            $quantity = $quantity*$currentCurrency->conversion_rate/$ars->conversion_rate;
        }

        return $quantity;
    }

    /**
     * Apply a fee to a payment amount
     * @param float $netAmount
     * @param string $fee (float with optional percent sign at the end)
     */
    public function getTotalWithFee($netAmount, $fee)
    {
        $fee = Tools::strlen($fee) ? $fee : '0';
        return strpos($fee, '%') ? $netAmount*(1+(float)Tools::substr($fee, 0, -1)/100) : $netAmount+(float)$fee;
    }

    protected function _getSoapClient()
    {
        if (!is_null($this->soapClient))
            return $this->soapClient;
        $this->soapClient = new SoapClient(self::OCA_URL,
            array(
            "trace"      => _PS_MODE_DEV_,
            "exceptions" => 1,
            "cache_wsdl" => 0)
        );
        return $this->soapClient;
    }

    protected function _makeErrorFriendly($error)
    {
        $replacements = array(
            'Property OcaEpakOperative->reference' => $this->l('Operative Reference'),
            'Property OcaEpakOperative->description' => $this->l('Description')
        );

        return str_replace(array_keys($replacements), array_values($replacements), $error);
    }

}