<?php
/**
 * Oca e-Pak Module for Prestashop
 *
 * Tested in Prestashop v1.5.0.17, 1.5.6.2, 1.6.0.5, 1.6.0.14, 1.6.1.2
 *
 * @author    Rinku Kazeno <development@kazeno.co>
 *
 * @copyright Copyright (c) 2012-2015, Rinku Kazeno
 * @license   This module is licensed to the user, upon purchase
 *   from either Prestashop Addons or directly from the author,
 *   for use on a single commercial Prestashop install, plus an
 *   optional separate non-commercial install (for development/testing
 *   purposes only). This license is non-assignable and non-transferable.
 *   To use in additional Prestashop installations an additional
 *   license of the module must be purchased for each one.

 *   The user may modify the source of this module to suit their
 *   own business needs, as long as no distribution of either the
 *   original module or the user-modified version is made.
 *
 *  @file-version 1.4
 */

if (!defined( '_PS_VERSION_'))
    exit;

class OcaEpak extends CarrierModule
{
    const MODULE_NAME = 'ocaepak';                  //DON'T CHANGE!!
    const CONFIG_PREFIX = 'OCAEPAK_';               //prefix for all internal config constants
    const CARRIER_NAME = 'Oca ePak';                //Carrier name string
    const CARRIER_DELAY = '2 a 8 días hábiles';     //Carrier default delay string
    const OPERATIVES_TABLE = 'ocae_operatives';     //DB table for Operatives
    const OPERATIVES_ID = 'id_ocae_operatives';     //DB table id for Operatives
    const ORDERS_TABLE = 'ocae_orders';             //DB table for orders
    const ORDERS_ID = 'id_ocae_orders';             //DB table id for orders
    const QUOTES_TABLE = 'ocae_quotes';             //DB table for quotes
    const RELAYS_TABLE = 'ocae_relays';             //DB table for relays
    const RELAYS_ID = 'id_ocae_relays';             //DB table id for relays
    const TRACKING_URL = 'https://www1.oca.com.ar/OEPTrackingWeb/trackingenvio.asp?numero1=@';
    const OCA_URL = 'http://webservice.oca.com.ar/epak_tracking/Oep_TrackEPak.asmx?wsdl';
    const OCA_PREVIOUS_URL = 'http://webservice.oca.com.ar/oep_tracking/Oep_Track.asmx?WSDL';
    const OCA_SERVICE_ADMISSION = 1;
    const OCA_SERVICE_DELIVERY = 2;

    const PADDING = 1;          //space to add around each product for volume calculations, in cm

    public $id_carrier;
    private $soapClients = array();
    protected  $guiHeader = '';
    private $boxes = array();

    public function __construct()
    {
        $this->name = 'ocaepak';            //DON'T CHANGE!!
        $this->tab = 'shipping_logistics';
        $this->version = '1.4.0';
        $this->author = 'R. Kazeno';
        $this->need_instance = 1;
        $this->ps_versions_compliancy = array('min' => '1.5', 'max' => '1.6');
        $this->module_key = '8ba7bceea44707dc9d6043606694cea5';
        $this->bootstrap = true;
        if (!class_exists('KznCarrier'))
            include_once _PS_MODULE_DIR_."{$this->name}/classes/KznCarrier.php";
        include_once _PS_MODULE_DIR_."{$this->name}/classes/OcaEpakOperative.php";
        include_once _PS_MODULE_DIR_."{$this->name}/classes/OcaEpakOrder.php";
        include_once _PS_MODULE_DIR_."{$this->name}/classes/OcaEpakQuote.php";
        include_once _PS_MODULE_DIR_."{$this->name}/classes/OcaEpakRelay.php";
        //include_once _PS_MODULE_DIR_."{$this->name}/classes/OcaEpakGeocoding.php";

        parent::__construct();
        $this->displayName = 'OCA e-Pak';
        $this->description = $this->l('Offer your customers automatic real-time quotes of their deliveries through OCA e-Pak');
        $this->confirmUninstall = $this->l('This will delete any configured settings for this module. Continue?');
        $warnings = array();
        if (!Tools::strlen(Configuration::get(self::CONFIG_PREFIX.'CUIT')) || !Tools::strlen(Configuration::get(self::CONFIG_PREFIX.'ACCOUNT')))
            array_push($warnings, $this->l('You need to configure your account settings.'));
        $this->warning = implode(' | ', $warnings);
    }

    public function install()
    {
        //make translator aware of strings used in models
        $this->l('Optional fee format is incorrect. Should be either an amount, such as 7.50, or a percentage, such as 6.99%','OcaEpak');
        if (!extension_loaded('soap'))
            $this->_errors[] = $this->l('You have the Soap PHP extension disabled. This module requires it for connecting to the Oca webservice.');
        if (count($this->_errors))
            return false;

        $db = Db::getInstance();
        $tab = new Tab();
        $tab2 = new Tab();
        $tab->active = 1;
        $tab2->active = 1;
        $tab->class_name = 'AdminOcaEpak';
        $tab2->class_name = 'AdminOcaOrder';
        $tab->name = array();
        $tab2->name = array();
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = 'Oca ePak';
            $tab2->name[$lang['id_lang']] = 'Oca ePak Orders';
        }
        $tab->id_parent = -1;
        $tab2->id_parent = -1;
        $tab->module = $this->name;
        $tab2->module = $this->name;
        return (
            $db->Execute(
                KznCarrier::interpolateSqlFile($this->name, 'create-operatives-table', array(
                    '{$DB_PREFIX}' => _DB_PREFIX_,
                    '{$TABLE_NAME}' => self::OPERATIVES_TABLE,
                    '{$TABLE_ID}' => self::OPERATIVES_ID
                ))
            ) AND
            $db->Execute(
                KznCarrier::interpolateSqlFile($this->name, 'create-quotes-table', array(
                    '{$DB_PREFIX}' => _DB_PREFIX_,
                    '{$TABLE_NAME}' => self::QUOTES_TABLE,
                ))
            ) AND
            $db->Execute(
                KznCarrier::interpolateSqlFile($this->name, 'create-relays-table', array(
                    '{$DB_PREFIX}' => _DB_PREFIX_,
                    '{$TABLE_NAME}' => self::RELAYS_TABLE,
                    '{$TABLE_ID}' => self::RELAYS_ID
                ))
            ) AND
            $db->Execute(
                KznCarrier::interpolateSqlFile($this->name, 'create-orders-table', array(
                    '{$DB_PREFIX}' => _DB_PREFIX_,
                    '{$TABLE_NAME}' => self::ORDERS_TABLE,
                    '{$TABLE_ID}' => self::ORDERS_ID
                ))
            ) AND
            parent::install() AND
            $tab->add() AND
            $tab2->add() AND
            $this->registerHook('displayCarrierList') AND
            $this->registerHook('displayAdminOrder') AND
            $this->registerHook('actionAdminPerformanceControllerBefore') AND
            Configuration::updateValue(self::CONFIG_PREFIX.'ACCOUNT', '') AND
            Configuration::updateValue(self::CONFIG_PREFIX.'EMAIL', '') AND
            Configuration::updateValue(self::CONFIG_PREFIX.'PASSWORD', '') AND
            Configuration::updateValue(self::CONFIG_PREFIX.'CUIT', '') AND
            Configuration::updateValue(self::CONFIG_PREFIX.'DEFWEIGHT', '0.25') AND
            Configuration::updateValue(self::CONFIG_PREFIX.'DEFVOLUME', '0.125') AND
            Configuration::updateValue(self::CONFIG_PREFIX.'FAILCOST', '63.37') AND
            Configuration::updateValue(self::CONFIG_PREFIX.'POSTCODE', '') AND
            Configuration::updateValue(self::CONFIG_PREFIX.'STREET', '') AND
            Configuration::updateValue(self::CONFIG_PREFIX.'NUMBER', '') AND
            Configuration::updateValue(self::CONFIG_PREFIX.'FLOOR', '') AND
            Configuration::updateValue(self::CONFIG_PREFIX.'APARTMENT', '') AND
            Configuration::updateValue(self::CONFIG_PREFIX.'LOCALITY', '') AND
            Configuration::updateValue(self::CONFIG_PREFIX.'PROVINCE', '') AND
            Configuration::updateValue(self::CONFIG_PREFIX.'CONTACT', '') AND
            Configuration::updateValue(self::CONFIG_PREFIX.'REQUESTOR', '') AND
            Configuration::updateValue(self::CONFIG_PREFIX.'OBSERVATIONS', '') AND
            Configuration::updateValue(self::CONFIG_PREFIX.'BOXES', '') AND
            Configuration::updateValue(self::CONFIG_PREFIX.'TIMESLOT', '') AND
            Configuration::updateValue(self::CONFIG_PREFIX.'ADMISSION_BRANCH', '') AND
            Configuration::updateValue(self::CONFIG_PREFIX.'ADMISSIONS_ENABLED', false) AND
            Configuration::updateValue(self::CONFIG_PREFIX.'PICKUPS_ENABLED', false)
        );
    }

    public function uninstall()
    {
        $db = Db::getInstance();
        OcaEpakOperative::purgeCarriers();
        return (
            parent::uninstall() AND
            $db->Execute(
                'DROP TABLE IF EXISTS '.pSQL(_DB_PREFIX_.self::OPERATIVES_TABLE)
            ) AND
            $db->Execute(
                'DROP TABLE IF EXISTS '.pSQL(_DB_PREFIX_.self::QUOTES_TABLE)
            ) AND
            $db->Execute(
                'DROP TABLE IF EXISTS '.pSQL(_DB_PREFIX_.self::RELAYS_TABLE)
            ) AND
            $db->Execute(
                'DROP TABLE IF EXISTS '.pSQL(_DB_PREFIX_.self::GEOCODES_TABLE)
            ) AND
            Configuration::deleteByName(self::CONFIG_PREFIX.'ACCOUNT') AND
            Configuration::deleteByName(self::CONFIG_PREFIX.'EMAIL') AND
            Configuration::deleteByName(self::CONFIG_PREFIX.'PASSWORD') AND
            Configuration::deleteByName(self::CONFIG_PREFIX.'CUIT') AND
            Configuration::deleteByName(self::CONFIG_PREFIX.'DEFWEIGHT') AND
            Configuration::deleteByName(self::CONFIG_PREFIX.'DEFVOLUME') AND
            Configuration::deleteByName(self::CONFIG_PREFIX.'FAILCOST') AND
            Configuration::deleteByName(self::CONFIG_PREFIX.'POSTCODE')
        );
    }

    public function getContent()
    {
        if (Tools::isSubmit('addOcaOperative'))
            return $this->getAddOperativeContent();
        elseif (Tools::isSubmit('updateocae_operatives'))
            return $this->getAddOperativeContent((int)Tools::getValue('id_ocae_operatives'));
        elseif (Tools::isSubmit('deleteocae_operatives'))        {
            $op = new OcaEpakOperative((int)Tools::getValue('id_ocae_operatives'));
            $ref = $op->reference;
            $this->guiAddToHeader(
                $op->delete()
                    ? $this->displayConfirmation($this->l('OCA Operative')." $ref ".$this->l('and its carrier have been successfully deleted'))
                : $this->displayError('Error deleting OCA Operative')
            );
        } elseif (Tools::isSubmit('saveOcaOperative')) {
            if (Tools::getIsset(self::OPERATIVES_ID)) {
                $op = new OcaEpakOperative(Tools::getValue(self::OPERATIVES_ID));
                $confirm = $this->l('OCA Operative has been successfully updated');
            } else {
                $op = new OcaEpakOperative();
                $confirm = $this->l('New OCA Operative and its carrier have been successfully created');
            }
            $op->reference = Tools::getValue('reference');
            $op->id_shop = $this->context->shop->id;
            $op->description = Tools::getValue('description');
            $op->addfee = Tools::getValue('addfee');
            $op->type = Tools::getValue('type');
            $op->insured = (bool)Tools::getValue('insured');
            $val = $op->validateFields(FALSE, TRUE);
            if ($val !== TRUE)
                return $this->displayError($this->guiMakeErrorFriendly($val)).$this->getAddOperativeContent();
            $op->save();
            $this->guiAddToHeader($this->displayConfirmation($confirm));
        } elseif (Tools::isSubmit('submitOcaepak'))
            $this->validateConfig();
        if (Tools::strlen(Configuration::get(self::CONFIG_PREFIX.'EMAIL')) && Tools::strlen(Configuration::get(self::CONFIG_PREFIX.'PASSWORD'))&& Tools::strlen(Configuration::get(self::CONFIG_PREFIX.'POSTCODE')) && Tools::strlen(Configuration::get(self::CONFIG_PREFIX.'CUIT')) && count($ops = OcaEpakOperative::getOperativeIds(true))) {
            //Check API Access is working correctly
            foreach ($ops as $op) {
                try {
                    $this->executeWebservice('Tarifar_Envio_Corporativo', array(
                        'PesoTotal' => '1',
                        'VolumenTotal' => '0.05',
                        'ValorDeclarado' => '100',
                        'CodigoPostalOrigen' => Configuration::get(self::CONFIG_PREFIX.'POSTCODE'),
                        'CodigoPostalDestino' => Configuration::get(self::CONFIG_PREFIX.'POSTCODE') == 9120 ? 1924 : 9120,
                        'CantidadPaquetes' => 1,
                        'Cuit' => Configuration::get(self::CONFIG_PREFIX.'CUIT'),
                        'Operativa' => $op->reference
                    ));
                } catch (Exception $e) {
                    if ($e->getMessage() == 'No results from OCA webservice')
                        $this->guiAddToHeader($this->displayError($this->l('There seems to be an error in the OCA operative with reference')." {$op->reference}"));
                    else
                        $this->guiAddToHeader($this->displayError($e->getMessage()));
                }
            }
        }
        $impositionCenters = $this->executeWebservice('GetCentrosImposicionConServicios');
        $icFields = array();
        foreach ($impositionCenters as $k => $obj) {
            $admits = false;
            foreach ($obj->Servicios->Servicio as $serv) {
                if ((string)$serv->IdTipoServicio === '1')
                    $admits = true;
            }
            if (!$admits)
                continue;
            $icFields[(string)$obj->CodigoPostal.'.'.$k] = array(
                'text' => (string)$obj->Sucursal.': '.(string)$obj->Calle.' '.(string)$obj->Numero.', '.(string)$obj->Localidad.', '.(string)$obj->Provincia.' | CP: '.(string)$obj->CodigoPostal,
                'value' => (string)$obj->IdCentroImposicion
            );
        }
        ksort($icFields);

        $this->context->smarty->assign(array('psver' => _PS_VERSION_));
        $this->guiAddToHeader($this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl'));
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
                            'class' => 'fixed-width-xxl',
                            'required' => true
                        ),
                        array(
                            'type' => 'text',
                            'label' => $this->l('Password'),
                            'name' => 'password',
                            'class' => 'fixed-width-xxl',
                            'required' => true
                        ),
                        array(
                            'type' => 'text',
                            'label' => $this->l('OCA Account Number'),
                            'name' => 'account',
                            'class' => 'fixed-width-xxl',
                            'desc' => $this->l('You can find your account number by logging into your OCA account and going to the following URL').': <br /><a href="http://www5.oca.com.ar/ocaepak/Seguro/ListadoOperativas.asp" target="_blank" style="text-decoration: underline; font-weight: 700;" />http://www5.oca.com.ar/ocaepak/Seguro/ListadoOperativas.asp</a>',
                            'required' => true
                        ),
                        array(
                            'type' => 'text',
                            'label' => $this->l('CUIT'),
                            'name' => 'cuit',
                            'class' => 'fixed-width-lg',
                            'desc' => $this->l('Must include all hyphens'),
                            'required' => true
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
            'shipments' => array(
                'form' => array(
                    'id_form' => 'form2',
                    'legend' => array(
                        'title' => '3. '.$this->l('Shipments')
                    ),
                    //'description' => $this->l('The address from where shipments will be made'),
                    'input' => array(
                        array(
                            'type' => 'text',
                            'size' => 4,
                            'label' => $this->l('Origin Post Code'),
                            'name' => 'postcode',
                            'class' => 'fixed-width-lg',
                            'desc' => $this->l('The post code from where shipments will be made (only digits)'),
                            'required' => true

                        ),
                        array(
                            'type' => _PS_VERSION_ < 1.6 ? 'radio' : 'switch',
                            'label' => $this->l('Enable OCA admission orders'),
                            'name' => 'oca_admissions',
                            'class' => 't',
                            'is_bool' => TRUE,
                            'values' => array(
                                array(
                                    'id' => 'admissions_on',
                                    'value' => '1',
                                    'label' => 'on',
                                ),
                                array(
                                    'id' => 'admissions_off',
                                    'value' => '0',
                                    'label' => 'off',
                                ),
                            ),
                            'desc' => $this->l('This will allow you to generate OCA order admissions and stickers for operatives sent from an OCA branch (SaP, SaS)')
                        ),
                        array(
                            'type' => _PS_VERSION_ < 1.6 ? 'radio' : 'switch',
                            'label' => $this->l('Enable OCA pickup orders'),
                            'name' => 'oca_pickups',
                            'class' => 't',
                            'is_bool' => TRUE,
                            'values' => array(
                                array(
                                    'id' => 'pickups_on',
                                    'value' => '1',
                                    'label' => 'on',
                                ),
                                array(
                                    'id' => 'pickups_off',
                                    'value' => '0',
                                    'label' => 'off',
                                ),
                            ),
                            'desc' => $this->l('This will allow you to generate OCA order collections and stickers for operatives collected at your premises (PaP, PaS)')
                        ),
                    ),
                )
            ),
            'admissions' => array(
                'form' => array(
                    'id_form' => 'admission_orders',
                    'legend' => array(
                        'title' => '3.1. '.$this->l('Branch Admission Settings')
                    ),
                    'description' => $this->l('SaP and SaS operative shipments will have to be made from this OCA branch'),
                    'input' => array(
                        array(
                            'type' => 'select',
                            'label' => $this->l('Admissions OCA Branch'),
                            'name' => 'branch',
                            'class' =>  'fixed-width-xxl',
                            'options' => array(
                                'query' => $icFields,
                                'id' => 'value',
                                'name' => 'text'
                            ),
                            'desc' => $this->l('The OCA branch where you will send your packages from')
                        ),
                    ),
                )
            ),
            'pickups' => array(
                'form' => array(
                    'id_form' => 'pickup_orders',
                    'legend' => array(
                        'title' => '3.2. '.$this->l('Pickup Settings')
                    ),
                    'description' => $this->l('PaP and PaS operative shipments will be picked up by OCA at this address'),
                    'input' => array(
                        array(
                            'type' => 'text',
                            'maxlength' => 30,
                            'label' => $this->l('Street'),
                            'name' => 'street',
                            'class' => 'fixed-width-xxl',
                            'required' => true
                        ),
                        array(
                            'type' => 'text',
                            'maxlength' => 5,
                            'label' => $this->l('Number'),
                            'name' => 'number',
                            'class' => 'fixed-width-md',
                            'required' => true
                        ),
                        array(
                            'type' => 'text',
                            'maxlength' => 2,
                            'label' => $this->l('Floor'),
                            'name' => 'floor',
                            'class' => 'fixed-width-md',
                        ),
                        array(
                            'type' => 'text',
                            'maxlength' => 4,
                            'label' => $this->l('Apartment'),
                            'name' => 'apartment',
                            'class' => 'fixed-width-md',
                        ),
                        array(
                            'type' => 'text',
                            'maxlength' => 30,
                            'label' => $this->l('Locality'),
                            'name' => 'locality',
                            'class' => 'fixed-width-xl',
                            'required' => true
                        ),
                        array(
                            'type' => 'text',
                            'maxlength' => 30,
                            'label' => $this->l('Province'),
                            'name' => 'province',
                            'class' => 'fixed-width-xl',
                            'required' => true
                        ),
                        array(
                            'type' => 'text',
                            'maxlength' => 30,
                            'label' => $this->l('Contact'),
                            'name' => 'contact',
                            'class' => 'fixed-width-xl',
                        ),
                        array(
                            'type' => 'text',
                            'maxlength' => 30,
                            'label' => $this->l('Requestor'),
                            'name' => 'requestor',
                            'class' => 'fixed-width-xl',
                        ),
                        array(
                            'type' => 'text',
                            'maxlength' => 100,
                            'label' => $this->l('Observations'),
                            'name' => 'observations',
                            'class' => 'fixed-width-xxl',
                        ),
                        array(
                            'type' => 'select',
                            'label' => $this->l('Time slot'),
                            'name' => 'timeslot',
                            'class' =>  'pickup_orders',
                            'options' => array(
                                'query' => array(
                                    array('value' =>'1', 'text' =>'8:00 - 17:00'),
                                    array('value' =>'2', 'text' =>'8:00 - 12:00'),
                                    array('value' =>'3', 'text' =>'14:00 - 17:00'),
                                ),
                                'id' => 'value',
                                'name' => 'text'
                            ),
                            'required' => true,
                            'desc' => $this->l('OCA collections will be made during this time slot')
                        ),
                    ),
                )
            ),
            'packaging' => array(
                'form' => array(
                    'form' => array(
                        'id_form' => 'form2',
                    ),
                    'legend' => array(
                        'title' => '3.3. '.$this->l('Packaging')
                    ),
                    'description' => $this->l('These are the types of boxes you commonly use for shipping'),
                    'input' => array(
                        array(
                            'type' => 'hidden',
                            'name' => 'boxes'
                        ),
                        array(
                            'type' => 'free',
                            'name' => 'boxes-box',
                        ),
                    )
                )
            ),
            'failsafes' => array(
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
                            'desc' => $this->l('Weight to use for products without registered weight data, in kg'),
                            'required' => true
                        ),
                        array(
                            'type' => 'text',
                            'label' => $this->l('Default Volume'),
                            'name' => 'defvolume',
                            'class' => 'fixed-width-xxl',
                            'desc' => $this->l('Volume to use for products without registered size data, in cubic m'),
                            'required' => true
                        ),
                        array(
                            'type' => 'text',
                            'label' => $this->l('Failsafe Shipping Cost'),
                            'name' => 'failcost',
                            'class' => 'fixed-width-xxl',
                            'desc' => $this->l('This is the shipping cost that will be used in the unlikely event the OCA server is down and we cannot get an online quote'),
                            'required' => true
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
                            'name' => 'account',
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
        $helper->fields_value = $this->getConfigFormValues();

        return $this->guiHeader.$helper->generateForm($fields_form1).$this->renderOperativesList().$helper->generateForm($fields_form2);
    }

    public function getAddOperativeContent($operativeId = NULL)
    {
        $fields_form = array(
            array(
                'form' => array(
                    'legend' => array(
                        'title' => $this->l('New OCA Operative')
                    ),
                    'description' => $this->l('You can find your OCA operatives by logging into your OCA account and going to the following URL').': <br /><a href="http://www5.oca.com.ar/ocaepak/Seguro/ListadoOperativas.asp" target="_blank" style="text-decoration: underline; font-weight: 700;" />http://www5.oca.com.ar/ocaepak/Seguro/ListadoOperativas.asp</a>',
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
        if ($operativeId) {
            $fields_form[0]['form']['input'][] = array('type' => 'hidden', 'name' => self::OPERATIVES_ID);
            $op = new OcaEpakOperative($operativeId);
            $fields = array(
                self::OPERATIVES_ID => Tools::getValue(self::OPERATIVES_ID, $operativeId),
                'reference' => Tools::getValue('reference', $op->reference),
                'description' => Tools::getValue('description', $op->description),
                'addfee' => Tools::getValue('addfee', $op->addfee),
                'type' => Tools::getValue('type', $op->type),
                'insured' => Tools::getValue('insured', $op->insured),
            );
        } else {
            $fields = array(
                'reference' => Tools::getValue('reference', ''),
                'description' => Tools::getValue('description', ''),
                'addfee' => Tools::getValue('addfee', '0.00%'),
                'type' => Tools::getValue('type', 'PaP'),
                'insured' => Tools::getValue('insured', false),
            );
        }
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
        $helper->fields_value = array_merge($this->getConfigFormValues(), $fields);

        return $helper->generateForm($fields_form);
    }

    public function renderOperativesList()
    {
        $content = Db::getInstance()->executeS('SELECT * FROM `'.pSQL(_DB_PREFIX_.self::OPERATIVES_TABLE).'` WHERE 1 '.Shop::addSqlRestriction().pSQL(' ORDER BY reference'));
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
                'icon' => array(
                    0 => 'disabled.gif',
                    1 => 'enabled.gif',
                    'default' => 'disabled.gif'
                ),
            ),
            'addfee' => array(
                'title' => $this->l('Charged Additional Fee'),
                'type' => 'text',
                'search' => false,
                'orderby' => false,
            ),
            /*'id_carrier' => array(
                'title' => $this->l('Carrier ID'),
                'type' => 'text',
                'search' => false,
                'orderby' => false,
            ),*/
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
        $helper->actions = array('edit', 'delete');
        $helper->show_toolbar = TRUE;
        $helper->no_link = TRUE;
        $helper->toolbar_btn['new'] =  array(
            'href' => AdminController::$currentIndex.'&configure='.$this->name.'&addOcaOperative&token='.Tools::getAdminTokenLite('AdminModules'),
            'desc' => $this->l('Add new operative')
        );

        return $helper->generateList($content, $fields_list);
    }

    protected function getConfigFormValues()
    {
        return array(
            'account' => Tools::getValue('account', Configuration::get(self::CONFIG_PREFIX.'ACCOUNT')),
            'email' => Tools::getValue('email', Configuration::get(self::CONFIG_PREFIX.'EMAIL')),
            'password' => Tools::getValue('password', Configuration::get(self::CONFIG_PREFIX.'PASSWORD')),
            'cuit' => Tools::getValue('cuit', Configuration::get(self::CONFIG_PREFIX.'CUIT')),
            'defweight' => Tools::getValue('defweight', Configuration::get(self::CONFIG_PREFIX.'DEFWEIGHT')),
            'defvolume' => Tools::getValue('defvolume', Configuration::get(self::CONFIG_PREFIX.'DEFVOLUME')),
            'postcode' => Tools::getValue('postcode', Configuration::get(self::CONFIG_PREFIX.'POSTCODE')),
            'failcost' => Tools::getValue('failcost', Configuration::get(self::CONFIG_PREFIX.'FAILCOST')),
            'oca_admissions' => Tools::getValue('oca_admissions', Configuration::get(self::CONFIG_PREFIX.'ADMISSIONS_ENABLED')),
            'oca_pickups' => Tools::getValue('oca_pickups', Configuration::get(self::CONFIG_PREFIX.'PICKUPS_ENABLED')),
            'street' => Tools::getValue('street', Configuration::get(self::CONFIG_PREFIX.'STREET')),
            'number' => Tools::getValue('number', Configuration::get(self::CONFIG_PREFIX.'NUMBER')),
            'floor' => Tools::getValue('floor', Configuration::get(self::CONFIG_PREFIX.'FLOOR')),
            'apartment' => Tools::getValue('apartment', Configuration::get(self::CONFIG_PREFIX.'APARTMENT')),
            'locality' => Tools::getValue('locality', Configuration::get(self::CONFIG_PREFIX.'LOCALITY')),
            'province' => Tools::getValue('province', Configuration::get(self::CONFIG_PREFIX.'PROVINCE')),
            'contact' => Tools::getValue('contact', Configuration::get(self::CONFIG_PREFIX.'CONTACT')),
            'requestor' => Tools::getValue('requestor', Configuration::get(self::CONFIG_PREFIX.'REQUESTOR')),
            'observations' => Tools::getValue('observations', Configuration::get(self::CONFIG_PREFIX.'OBSERVATIONS')),
            'timeslot' => Tools::getValue('timeslot', Configuration::get(self::CONFIG_PREFIX.'TIMESLOT') ? Configuration::get(self::CONFIG_PREFIX.'TIMESLOT') : 1),
            'costcenter' => Tools::getValue('costcenter', Configuration::get(self::CONFIG_PREFIX.'COSTCENTER') ? Configuration::get(self::CONFIG_PREFIX.'COSTCENTER') : 1),
            'branch' => Tools::getValue('branch', Configuration::get(self::CONFIG_PREFIX.'ADMISSION_BRANCH') ? Configuration::get(self::CONFIG_PREFIX.'ADMISSION_BRANCH') : 39),
            'boxes' => Tools::getValue('boxes', Configuration::get(self::CONFIG_PREFIX.'BOXES') ? Configuration::get(self::CONFIG_PREFIX.'BOXES') : '[]'),
            'boxes-box' => $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure_boxes.tpl'),
        );
    }

    protected function validateConfig()
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
        if (Tools::getValue('oca_admissions')) {
            if (!Validate::isUnsignedInt(Tools::getValue('branch')))
                $error .= $this->displayError($this->l('Invalid OCA admissions branch'));
        }
        if (Tools::getValue('oca_pickups')) {
            if (!Tools::strlen(trim(Tools::getValue('street'))) || !Validate::isAddress(Tools::getValue('street')))
                $error .= $this->displayError($this->l('Invalid pickup street'));
            if (!Tools::strlen(trim(Tools::getValue('number'))) || !Validate::isAddress(Tools::getValue('number')))
                $error .= $this->displayError($this->l('Invalid pickup number'));
            if (!Tools::strlen(trim(Tools::getValue('locality'))) || !Validate::isAddress(Tools::getValue('locality')))
                $error .= $this->displayError($this->l('Invalid pickup locality'));
            if (!Tools::strlen(trim(Tools::getValue('province'))) || !Validate::isAddress(Tools::getValue('province')))
                $error .= $this->displayError($this->l('Invalid pickup province'));
            if (!Validate::isUnsignedInt(Tools::getValue('timeslot')))
                $error .= $this->displayError($this->l('Invalid pickup timeslot'));
        }
        $boxes = Tools::jsonDecode(Tools::getValue('boxes'), true);
        foreach ($boxes as $box) {
            if (($box['l']+$box['d']+$box['h']+$box['xw']) > 0) {
                if (!is_numeric($box['l']) || !is_numeric($box['d']) || !is_numeric($box['h']) || !is_numeric($box['xw']))
                    $error .= $this->displayError($this->l('Some of the boxes have a non-numeric dimension'));
                elseif ($box['l'] == 0 || $box['d'] == 0 || $box['h'] == 0 || $box['xw'] == 0)
                    $error .= $this->displayError($this->l('Some of the boxes have a dimension of 0'));
                else
                    $this->boxes[] = $box;
            }
        }
        if (Tools::strlen($error)) {
            $this->guiAddToHeader($error);
            return false;
        }

        $form_values = $this->getConfigFormValues();
        foreach (array_keys($form_values) as $key) {
            if (in_array($key, array('oca_admissions', 'oca_pickups', 'branch', 'boxes', 'boxes-box')))
                continue;
            Configuration::updateValue(self::CONFIG_PREFIX.Tools::strtoupper($key), is_string(Tools::getValue($key)) ? trim(Tools::getValue($key)) : Tools::getValue($key));
        }
        if (Tools::getValue('oca_admissions')) {
            Configuration::updateValue(self::CONFIG_PREFIX.'ADMISSION_BRANCH', Tools::getValue('branch'));
        }
        Configuration::updateValue(self::CONFIG_PREFIX . 'ADMISSIONS_ENABLED', Tools::getValue('oca_admissions'));
        Configuration::updateValue(self::CONFIG_PREFIX . 'PICKUPS_ENABLED', Tools::getValue('oca_pickups'));
        Configuration::updateValue(self::CONFIG_PREFIX.'BOXES', Tools::jsonEncode($this->boxes));
        $this->guiAddToHeader($this->displayConfirmation($this->l('Configuration saved')));
        return true;
    }

    public function renderOrderGeneratorForm($address, $parsedAddress, $type)
    {
        $this->context->controller->addJqueryUI('ui.datepicker');
        $this->context->smarty->assign(array(
            'oca_boxes' => Tools::jsonDecode(Configuration::get(self::CONFIG_PREFIX.'BOXES'), true),
            'oca_order_address' => $address,
            'oca_geocoded' => $parsedAddress['geocoded']
        ));
        $boxBox = $this->context->smarty->fetch($this->local_path.'views/templates/admin/oca_order_boxes.tpl');
        $fullAddress = $this->context->smarty->fetch($this->local_path.'views/templates/admin/oca_order_address.tpl');

        $fields_form = array(
            array(
                'form' => array(
                    'id_form' => 'oca-form',
                    'legend' => array(
                        'title' => $this->l('OCA Address Parser')
                    ),
                    'description' => $parsedAddress['discrepancy'] ? $this->l('Please check the following customer address and correct any field that was incorrectly extracted from it') : $this->l('Customer address analyzed successfully'),
                    'input' => array(
                        array(
                            'type' => 'free',
                            'name' => 'oca-full-address',
                        ),
                        array(
                            'type' => 'text',
                            'name' => 'oca-street',
                            'label' => $this->l('Street'),
                            'required' => true
                        ),
                        array(
                            'type' => 'text',
                            'name' => 'oca-number',
                            'label' => $this->l('Number'),
                            'required' => true
                        ),
                        array(
                            'type' => 'text',
                            'name' => 'oca-floor',
                            'label' => $this->l('Floor'),
                        ),
                        array(
                            'type' => 'text',
                            'name' => 'oca-apartment',
                            'label' => $this->l('Apartment'),
                        ),
                        array(
                            'type' => 'text',
                            'name' => 'oca-city',
                            'label' => $this->l('Locality'),
                            'required' => true
                        ),
                        array(
                            'type' => 'text',
                            'name' => 'oca-state',
                            'label' => $this->l('State'),
                            'required' => true
                        ),
                        array(
                            'type' => 'textarea',
                            'label' => $this->l('Observations'),
                            'name' => 'oca-other',
                            'cols' => '50',
                            'rows' => '2',
                        ),
                    ),
                ),
            ),
            array(
                'form' => array(
                    'legend' => array(
                        'title' => $this->l('OCA Order Generator')
                    ),
                    'input' => array(
                        array(
                            'type' => 'date',
                            'label' => in_array($type, array('PaP', 'PaS')) ? $this->l('Date for pickup') : $this->l('Date for admission'),
                            'name' => 'oca-date',
                            'class' => 'col-xs-6 datepicker',
                            'size' => 6,
                            'required' => true,
                            'desc' => in_array($type, array('PaP', 'PaS')) ? $this->l('When should OCA come for the packages') : $this->l('When you will take the packages to OCA'),
                        ),
                        array(
                            'type' => 'free',
                            'name' => 'boxes',
                            'label' => $this->l('Packaging'),
                        ),
                    ),
                    'submit' => array(
                        'title' => $this->l('Generate OCA Admission Order'),
                        'name' => 'oca-order-submit'
                    ),
                )
            )
        );
        $fields_value = array(
            'oca-street' => Tools::getValue('oca-street', $parsedAddress['street']),
            'oca-number' => Tools::getValue('oca-number', $parsedAddress['number']),
            'oca-floor' => Tools::getValue('oca-floor', $parsedAddress['floor']),
            'oca-apartment' => Tools::getValue('oca-apartment', $parsedAddress['apartment']),
            'oca-city' => Tools::getValue('oca-city', $parsedAddress['city']),
            'oca-state' => Tools::getValue('oca-state', $parsedAddress['state']),
            'oca-other' => Tools::getValue('oca-other', $parsedAddress['other']),
            'oca-date' => Tools::getValue('oca-date', date('Y-m-d')),
            'boxes' => $boxBox,
            'oca-full-address' => $fullAddress,

        );
        $helper = new HelperForm();
        $helper->module = $this;
        $helper->title = $this->displayName;
        $helper->name_controller = $this->name;
        $helper->identifier = $this->identifier;
        $helper->token = Tools::getAdminTokenLite('AdminOrders');
        $helper->currentIndex = 'index.php?controller=AdminOrders&id_order='.Tools::getValue('id_order').'&vieworder';
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->show_toolbar = false;
        $helper->submit_action = '';
        $helper->fields_value = $fields_value;

        return $helper->generateForm($fields_form);
    }

    protected function getValidateOcaForm($cartData)
    {
        if (Tools::getValue('oca-date', false) && Tools::getValue('oca-street', false) && Tools::getValue('oca-number', false) && Tools::getValue('oca-city', false) && Tools::getValue('oca-state', false)) {
            $form = array(
                'street' => Tools::getValue('oca-street'),
                'number' => Tools::getValue('oca-number'),
                'floor' => Tools::getValue('oca-floor', ''),
                'apartment' => Tools::getValue('oca-apartment', ''),
                'locality' => Tools::getValue('oca-city'),
                'province' => Tools::getValue('oca-state'),
                'observations' => Tools::getValue('oca-other'),
                'date' => str_replace('-', '', Tools::getValue('oca-date')),
                'boxes' => array()
            );
            $boxes = Tools::jsonDecode(Configuration::get(self::CONFIG_PREFIX.'BOXES'), true);
            $boxVolume = 0;
            foreach ($boxes as $ind => $box) {
                if (!is_numeric(Tools::getValue('oca-box-q-'.$ind, 0))) {
                    $this->guiAddToHeader($this->displayError($this->l('One of the boxes has a non-numeric quantity')));
                    return false;
                }
                if (Tools::getValue('oca-box-q-'.$ind, 0) <= 0)
                    continue;
                $form['boxes'][] = array(
                    'l' => number_format((float)$box['l'], 0),
                    'd' => number_format((float)$box['d'], 0),
                    'h' => number_format((float)$box['h'], 0),
                    'q' => Tools::getValue('oca-box-q-'.$ind),
                );
                $boxVolume = $boxVolume+($box['l']*$box['d']*$box['h'])*Tools::getValue('oca-box-q-'.$ind);
            }
            if (count($form['boxes']) == 0) {
                $this->guiAddToHeader($this->displayError($this->l('You need to add at least one box')));
                return false;
            } else {
                foreach ($form['boxes'] as &$box) {      //split cost and weight proportionally
                    $vol = ($box['l']*$box['d']*$box['h']);
                    $volumePercentage = $vol/$boxVolume;
                    $box['v'] = number_format((float)$volumePercentage*$cartData['cost'], 2);
                    $box['w'] = number_format((float)$volumePercentage*$cartData['weight'], 2);
                }
            }
            return $form;
        } else {
            $this->guiAddToHeader($this->displayError($this->l('There is missing data in the OCA order generator form')));
            return false;
        }
    }

    public function hookDisplayAdminOrder($params)
    {
        $carrier = new Carrier($params['cart']->id_carrier);
        $op = OcaEpakOperative::getByFieldId('carrier_reference', $carrier->id_reference);
        if (!$op)
            return NULL;
        $address = new Address($params['cart']->id_address_delivery);
        $carrier = new Carrier($params['cart']->id_carrier);
        $order = new Order($params['id_order']);
        $customer = new Customer($order->id_customer);
        if (in_array($op->type, array('PaS', 'SaS'))) {
            $relayId = OcaEpakRelay::getByCartId($params['cart']->id)->distribution_center_id;
        } else
            $relayId = null;

        if (Tools::isSubmit('oca-order-cancel') && ($ocaOrder = OcaEpakOrder::getByFieldId('id_order', $order->id))) {
            try {
                $cancel = $this->executeWebservice('AnularOrdenGenerada', array(
                    'usr' => Configuration::get(OcaEpak::CONFIG_PREFIX.'EMAIL'),
                    'psw' => Configuration::get(OcaEpak::CONFIG_PREFIX.'PASSWORD'),
                    'IdOrdenRetiro' => $ocaOrder->reference,
                ));
                $result = (int)$cancel->IdResult;
                $message = (string)$cancel->Mensaje;
                switch ($result) {
                    case 100:   //Order Deleted
                        $this->guiAddToHeader($this->displayConfirmation($message));
                        $ocaOrder->delete();
                        break;
                    case 130:   //Error
                        $this->guiAddToHeader($this->displayError($message));
                        break;
                    default :
                        $this->guiAddToHeader($this->displayError($result.': '.$message));
                        break;
                }
            } catch (Exception $e) {
                //$this->logError($e->getMessage());
                $this->guiAddToHeader(Tools::displayError($this->l('OCA Order cancel error').': '.$e->getMessage()));
            }
        } elseif (Tools::isSubmit('oca-order-submit')) {
            $cartData = KznCarrier::getCartPhysicalData($params['cart'], $carrier->id, Configuration::get(self::CONFIG_PREFIX.'DEFWEIGHT'), Configuration::get(self::CONFIG_PREFIX.'DEFVOLUME'), self::PADDING);
            if ($preOrder = $this->getValidateOcaForm($cartData)) {
                $xmlRetiro = OcaEpakOrder::generateOrderXml(array_merge($preOrder, array(
                    'address' => $address,
                    'operative' => $op,
                    'order' => $order,
                    'customer' => $customer,
                    'cost_center_id' => in_array($op->type, array('PaP', 'PaS')) ? '0' : '1',
                    'imposition_center_id' => $relayId,
                    'origin_imposition_center_id' => in_array($op->type, array('SaP', 'SaS')) ? Configuration::get(OcaEpak::CONFIG_PREFIX.'ADMISSION_BRANCH') : false,
                    'postcode' => KznCarrier::cleanPostcode($address->postcode)
                )));
                try {
                    $data = $this->executeWebservice('IngresoORMultiplesRetiros', array(
                        'usr' => Configuration::get(OcaEpak::CONFIG_PREFIX.'EMAIL'),
                        'psw' => Configuration::get(OcaEpak::CONFIG_PREFIX.'PASSWORD'),
                        'ConfirmarRetiro' => true,
                        'xml_Datos' => $xmlRetiro
                    ));
                    if (!isset($data->Resumen))
                        throw new Exception($this->l('Error generating OCA order'));
                    if (isset($data->Errores))
                        throw new Exception($this->l('Error generating OCA order').': '.(string)$data->Errores->Error->Descripcion);
                    $ocaOrder = new OcaEpakOrder();
                    $ocaOrder->id_order = $order->id;
                    $ocaOrder->reference = (int)$data->DetalleIngresos->OrdenRetiro;
                    $ocaOrder->tracking = (string)$data->DetalleIngresos->NumeroEnvio;
                    $ocaOrder->operation_code = (int)$data->Resumen->CodigoOperacion;
                    $ocaOrder->save();
                    if (!$order->shipping_number && $ocaOrder->tracking) {
                        $id_order_carrier = Db::getInstance()->getValue('
						SELECT `id_order_carrier`
						FROM `'._DB_PREFIX_.'order_carrier`
						WHERE `id_order` = '.(int)$order->id);
                        if ($id_order_carrier) {
                            $_GET['tracking_number'] = $ocaOrder->tracking;
                            $_GET['submitShippingNumber'] = 1;
                            $_GET['id_order_carrier'] = $id_order_carrier;
                            $this->context->controller->postProcess();
                        }
                    }
                    unset($ocaOrder);
                } catch (Exception $e) {
                    //$this->logError($e->getMessage());
                    //$this->logError($data);
                    $this->guiAddToHeader($this->displayError($e->getMessage()));
                }
            }/* else
                $this->guiAddToHeader(Tools::displayError($this->l('There is an error in the OCA order generator form')));*/
        }

        $ajaxUrl = str_replace('index.php', 'ajax-tab.php', $this->context->link->getAdminLink('AdminOcaEpak', true));
        $this->context->smarty->assign(  array(
            'moduleName' => self::MODULE_NAME,
            'ocaImagePath'  => Tools::getShopDomainSsl(true, true).$this->_path.'views/img/',
            'ocaAjaxUrl' => $ajaxUrl,
            'ocaOrderId' => $order->id,
        ) );
        if ($ocaOrder = OcaEpakOrder::getByFieldId('id_order', $order->id)) {
            $stickerUrl = str_replace('index.php', 'ajax-tab.php', $this->context->link->getAdminLink('AdminOcaOrder', true)).'&action=sticker&id_oca_order='.$ocaOrder->id;
            try {
                $admission = $this->executeWebservice('GetORResult', array(
                    'idCabecera' => $ocaOrder->operation_code,
                    'Usr' => Configuration::get(OcaEpak::CONFIG_PREFIX.'EMAIL'),
                    'Psw' => Configuration::get(OcaEpak::CONFIG_PREFIX.'PASSWORD'),
                ));
                $status = (string)$admission->DetalleIngresos->Estado;
                $accepts = (int)$admission->Resumen->CantidadIngresados;
                $rejects = (int)$admission->Resumen->CantidadRechazados;
            } catch (Exception $e) {
                $this->logError($e->getMessage());
                $status = 'Error adquiriendo estado';
                $accepts = $rejects = 0;
            }
            $this->context->smarty->assign(  array(
                'ocaStatus' => $status,
                'ocaAccepts' => $accepts,
                'ocaRejects' => $rejects,
                'ocaOrder' => $ocaOrder,
                'stickerUrl' => $stickerUrl,
                'ocaGuiHeader' => $this->guiHeader,
                'ocaOrderStatus' => 'submitted'
            ) );
            $template = Tools::str_replace_once('%HEADER_GOES_HERE%', $this->guiHeader, $this->display(__FILE__, _PS_VERSION_ < '1.6' ? 'displayAdminOrder15.tpl' : 'displayAdminOrder.tpl'));
        } elseif (((Configuration::get(self::CONFIG_PREFIX.'ADMISSIONS_ENABLED') && (in_array($op->type, array('SaP', 'SaS')))) || (Configuration::get(self::CONFIG_PREFIX.'PICKUPS_ENABLED') && in_array($op->type, array('PaP', 'PaS')))) && !$order->shipping_number) {
            $parsedAddress = OcaEpakOrder::parseOcaAddress($address);
            //if ($parsedAddress['discrepancy'])
            //    $this->logError(array('Problematic address' => $address->address1.' | '.$address->address2.' | '.$address->city.' | '.$address->other.' | '.$address->postcode.' | '));
            $form = $this->renderOrderGeneratorForm($address, $parsedAddress, $op->type);
            $this->context->smarty->assign(  array(
                'ocaGuiHeader' => $this->guiHeader,
                'ocaOrderStatus' => 'unsubmitted'
            ) );
            $pretemplate = Tools::str_replace_once('%HEADER_GOES_HERE%', $this->guiHeader, $this->display(__FILE__, _PS_VERSION_ < '1.6' ? 'displayAdminOrder15.tpl' : 'displayAdminOrder.tpl'));
            $template = Tools::str_replace_once('%ORDER_GENERATOR_GOES_HERE%', $form, $pretemplate);
        } else {
            $this->context->smarty->assign(  array(
                'ocaGuiHeader' => $this->guiHeader,
                'ocaOrderStatus' => 'disabled'
            ) );
            $template = Tools::str_replace_once('%HEADER_GOES_HERE%', $this->guiHeader, $this->display(__FILE__, _PS_VERSION_ < '1.6' ? 'displayAdminOrder15.tpl' : 'displayAdminOrder.tpl'));
        }

        return $template;
    }

    /**
     * Show pickup options
     * @param Array $params ['address']
     */
    public function hookDisplayCarrierList($params)
    {
        if (!$this->active OR $params['address']->id_country != Country::getByIso('AR'))
            return FALSE;
        $carrierIds = OcaEpakOperative::getRelayedCarrierIds();
        if (count($carrierIds)) {
            $this->context->controller->addJS('http'.((Configuration::get('PS_SSL_ENABLED') && Configuration::get('PS_SSL_ENABLED_EVERYWHERE')) ? 's' : '').'://maps.google.com/maps/api/js?region=AR');
            try {
                $relay = OcaEpakRelay::getByCartId($this->context->cookie->id_cart);
                if ($params['address']->id_state) {
                    $state = new State($params['address']->id_state);
                    $stateCode = trim($state->iso_code);
                } else
                    $stateCode = '';
                $this->context->smarty->assign(  array(
                    'customerAddress' => $params['address'],
                    'ocaepak_relays' => $this->retrieveOcaBranches(self::OCA_SERVICE_DELIVERY),
                    'relayed_carriers' => Tools::jsonEncode($carrierIds),
                    'ocaepak_name' => $this->name,
                    'ocaepak_selected_relay' => $relay ? $relay->distribution_center_id : null,
                    'ocaepak_relay_auto' => $relay ? $relay->auto : null,
                    'customerStateCode' => Tools::strlen($stateCode) === 1 ? $stateCode : '',
                    'psver' => _PS_VERSION_,
                    'force_ssl' => Configuration::get('PS_SSL_ENABLED') || Configuration::get('PS_SSL_ENABLED_EVERYWHERE')
                ) );
                return $this->display(__FILE__, 'displayCarrierList.tpl');
            } catch (Exception $e) {
                //**/Tools::dieObject($e);
                Logger::AddLog('Ocaepak: '.$this->l('Error getting pickup centers for cp')." {$params['address']->postcode}");
                return FALSE;
            }
        }
        return NULL;
    }

    public function hookActionAdminPerformanceControllerBefore()
    {
        if ((bool)Tools::getValue('empty_smarty_cache')) {
            OcaEpakQuote::clear();
        }
    }


    public function getOrderShippingCost($cart, $shipping_cost)
    {
        $address = new Address($cart->id_address_delivery);
        if (!$address->id || !$address->postcode) {
            $cache_id = 'OcaEpak::cartPostCode_'.(int)$cart->id;
            if (Cache::isStored($cache_id)) {
                $postcode = KznCarrier::cleanPostcode(Cache::retrieve($cache_id));
            } elseif (@filemtime(_PS_GEOIP_DIR_.(defined(_PS_GEOIP_CITY_FILE_) ? _PS_GEOIP_CITY_FILE_ : 'GeoLiteCity.dat'))) {
                include_once(_PS_GEOIP_DIR_ . 'geoipcity.inc');
                include_once(_PS_GEOIP_DIR_.'geoipregionvars.php');
                $gi = geoip_open(realpath(_PS_GEOIP_DIR_.(defined(_PS_GEOIP_CITY_FILE_) ? _PS_GEOIP_CITY_FILE_ : 'GeoLiteCity.dat')), GEOIP_STANDARD);
                $record = geoip_record_by_addr($gi, Tools::getRemoteAddr());
                if (is_object($record) && $record->country_code === 'AR' && $record->postal_code) {
                    //**/Logger::addLog('postcode found by geocode: '.$record->postal_code);
                    Cache::store($cache_id, $record->postal_code);
                    $postcode = KznCarrier::cleanPostcode($record->postal_code);
                } else
                    return false;
            } else
                return false;
        } else {
            $postcode = KznCarrier::cleanPostcode($address->postcode);
        }
        $carrier = new Carrier($this->id_carrier);
        $op = OcaEpakOperative::getByFieldId('carrier_reference', $carrier->id_reference);
        if (!$this->active OR $address->id_country != Country::getByIso('AR') OR !$op)
            return FALSE;

        if ($carrier->shipping_method == Carrier::SHIPPING_METHOD_PRICE)
            return $shipping_cost;
        try {
            $relay = OcaEpakRelay::getByCartId($cart->id);
            if ((in_array($op->type, array('SaS', 'PaS')))) {
                if (!$relay) {
                    $branches = $this->executeWebservice('GetCentrosImposicionPorCP', array(
                        'CodigoPostal' => $postcode,
                    ));
                    if (!count($branches) || !isset($branches[0]->idCentroImposicion))
                        return false;
                    $relay = new OcaEpakRelay();
                    $relay->id_cart = $cart->id;
                    $relay->distribution_center_id = (int)$branches[0]->idCentroImposicion;
                    $relay->auto = 1;
                    $relay->save();
                    $postcode = (string)$branches[0]->CodigoPostal;
                    $cache_id = 'OcaEpak::relayPostCode_'.(int)$relay->distribution_center_id;
                    if (!Cache::isStored($cache_id))
                        Cache::store($cache_id, $postcode);
                } else {
                    $cache_id = 'OcaEpak::relayPostCode_'.(int)$relay->distribution_center_id;
                    if (Cache::isStored($cache_id)) {
                        $postcode = KznCarrier::cleanPostcode(Cache::retrieve($cache_id));
                    } else {
                        $branch = $this->retrieveOcaBranchData($relay->distribution_center_id);
                        $postcode = (string)$branch['CodigoPostal'];
                        Cache::store($cache_id, $postcode);
                    }
                }
            }
            $cartData = KznCarrier::getCartPhysicalData($cart, $carrier->id, Configuration::get(self::CONFIG_PREFIX.'DEFWEIGHT'), Configuration::get(self::CONFIG_PREFIX.'DEFVOLUME'), self::PADDING);
            if ($cot = OcaEpakQuote::retrieve($op->reference, $postcode, Configuration::get(self::CONFIG_PREFIX.'POSTCODE'), $cartData['volume'], $cartData['weight'], $cartData['cost']))      //get cache
                return (float)Tools::ps_round($shipping_cost+KznCarrier::convertCurrencyFromIso(KznCarrier::applyFee($cot, $op->addfee), 'ARS', $cart->id_currency), 2);
            $data = $this->executeWebservice('Tarifar_Envio_Corporativo', array(
                'PesoTotal' => $cartData['weight'],
                'VolumenTotal' => ($cartData['volume'] > 0.0001) ? $cartData['volume'] : 0.0001,
                'ValorDeclarado' => $cartData['cost'],
                'CodigoPostalOrigen' => Configuration::get(self::CONFIG_PREFIX.'POSTCODE'),
                'CodigoPostalDestino' => KznCarrier::cleanPostcode($address->postcode),
                'CantidadPaquetes' => 1,
                'Cuit' => Configuration::get(self::CONFIG_PREFIX.'CUIT'),
                'Operativa' => $op->reference
            ));
            if ($data->Total > 0)       //set cache
                OcaEpakQuote::insert($op->reference, KznCarrier::cleanPostcode($address->postcode), Configuration::get(self::CONFIG_PREFIX.'POSTCODE'), $cartData['volume'], $cartData['weight'], $cartData['cost'], $data->Total);
        } catch (Exception $e) {
            Logger::AddLog('Ocaepak: '.$this->l('error getting online price for cart')." {$cart->id}");
            return (float)KznCarrier::convertCurrencyFromIso(Configuration::get(self::CONFIG_PREFIX.'FAILCOST'), 'ARS', $cart->id_currency);
        }
        return (float)Tools::ps_round($shipping_cost+KznCarrier::convertCurrencyFromIso(KznCarrier::applyFee($data->Total, $op->addfee), 'ARS', $cart->id_currency), 2);
    }
    public function getOrderShippingCostExternal($params)
    {
        return $this->getOrderShippingCost($params, Configuration::get(self::CONFIG_PREFIX.'FAILCOST'));
    }

    public function retrieveOcaBranchData($id_branch)
    {
        try {
            $data = $this->executeWebservice('GetCentrosImposicion');
            foreach ($data as $table) {
                if (trim((string)$table->idCentroImposicion) === (string)$id_branch)
                    return Tools::jsonDecode(str_replace('{}', '""', Tools::jsonEncode((array)$table)), TRUE);
            }
        } catch (Exception $e) {
            Logger::AddLog('Ocaepak: '.$this->l('Error getting pickup centers for branch')." {$id_branch}");
        }
        return false;
    }

    public function retrieveOcaBranches($serviceId)
    {
        try {
            $data = $this->executeWebservice('GetCentrosImposicionConServicios');
            $relays = array();
            foreach ($data as $k => $table) {
                $rel = Tools::jsonDecode(str_replace('{}', '""', Tools::jsonEncode((array)$table)), TRUE);
                $servs = $rel['Servicios']['Servicio'];
                if (isset($servs['IdTipoServicio'])) {
                    if (trim((string)$servs['IdTipoServicio']) === (string)$serviceId) {
                        $relays[] = Tools::jsonDecode(str_replace('{}', '""', Tools::jsonEncode((array)$table)), TRUE);
                    }
                } else {
                    foreach ($servs as $serv) {
                        if (isset($serv['IdTipoServicio']) && trim((string)$serv['IdTipoServicio']) === (string)$serviceId) {
                            $relays[] = Tools::jsonDecode(str_replace('{}', '""', Tools::jsonEncode((array)$table)), TRUE);
                            break;
                        }
                    }
                }
            }
            return $relays;
        } catch (Exception $e) {
            Logger::AddLog('Ocaepak: '.$this->l('Error getting imposition centers'));
        }
        return false;
    }


    protected function guiAddToHeader($html)
    {
        $this->guiHeader .= "\n$html";

        return $this;
    }

    protected function guiMakeErrorFriendly($error)
    {
        $replacements = array(
            'Property OcaEpakOperative->reference' => $this->l('Operative Reference'),
            'Property OcaEpakOperative->description' => $this->l('Description')
        );

        return str_replace(array_keys($replacements), array_values($replacements), $error);
    }
    
    protected function logError($data)
    {
        $logger = new FileLogger();
        $logger->setFilename(_PS_MODULE_DIR_.$this->name.'/logs/'.date('Ymd').'.log');
        $logger->logError(print_r($data, true));
    }

    protected function _getSoapClient($url)
    {
        if (isset($this->soapClients[$url]))
            return $this->soapClients[$url];
        $this->soapClients[$url] = new SoapClient($url,
            array(
                "trace"      => _PS_MODE_DEV_,
                "exceptions" => 1,
                "cache_wsdl" => 0)
        );
        return $this->soapClients[$url];
    }

    public function executeWebservice($method, $params = array(), $forceUrl = null)
    {
        $services = array(
            self::OCA_PREVIOUS_URL => array(
                //'AnularOrdenGenerada',
                'GenerarConsolidacionDeOrdenesDeRetiro',
                'GenerateListQrPorEnvio',
                'GenerateQRParaPaquetes',
                'GenerateQrByOrdenDeRetiro',
                'GetCentroCostoPorOperativa',
                //'GetCentrosImposicion',
                'GetCentrosImposicionAdmision',
                'GetCentrosImposicionAdmisionPorCP',
                'GetCentrosImposicionPorCP',
                'GetDatosDeEtiquetasPorOrdenOrNumeroEnvio',
                'GetELockerOCA',
                'GetEnviosUltimoEstado',
                'GetHtmlDeEtiquetasLockersPorOrdenOrNumeroEnvio',
                'GetHtmlDeEtiquetasLockersPorOrdenOrNumeroEnvioParaEtiquetadora',
                'GetHtmlDeEtiquetasPorOrdenOrNumeroEnvio',
                'GetHtmlDeEtiquetasPorOrdenOrNumeroEnvioParaEtiquetadora',
                'GetLocalidadesByProvincia',
                'GetPdfDeEtiquetasPorOrdenOrNumeroEnvio',
                //'GetProvincias',
                'GetServiciosDeCentrosImposicion',
                'GetServiciosDeCentrosImposicion_xProvincia',
                //'IngresoOR',
                //'List_Envios',
                //'Tarifar_Envio_Corporativo',
                'TrackingEnvio_EstadoActual',
                'Tracking_OrdenRetiro',
                //'Tracking_Pieza',
            ),
            self::OCA_URL => array(
                'AnularOrdenGenerada',
                'DescripcionError',
                'GetCentrosImposicion',
                'GetCentrosImposicionConServicios',
                'GetCodigosPostalesXCentroImposicion',
                'GetEPackUserForMail',
                'GetEnvioEstadoActual',
                'GetEPackUser',
                'GetLoginData',
                'GetORResult',
                'GetOperativasByUsuario',
                'GetProvincias',
                'GetReporteRemTramXNumeroTracking',
                'GetSucursalByProvincia',
                'GetUserFromLoginData',
                'IngresoOR',
                'IngresoORMultiplesRetiros',
                'List_Envios',
                'Ordenretiro_CSV2XML',
                'Tarifar_Envio_Corporativo',
                'Tracking_Pieza',
                'Tracking_PiezaExtendido',
                'Tracking_PiezaNumeroEnvio'
            )
        );
        if ($forceUrl)
            $url = $forceUrl;
        elseif (in_array($method, $services[self::OCA_PREVIOUS_URL]))
            $url = self::OCA_PREVIOUS_URL;
        elseif (in_array($method, $services[self::OCA_URL]))
            $url = self::OCA_URL;
        else
            throw new Exception('Method not found in webservice: '.$method);
        try {
            $response = $this->_getSoapClient($url)->{$method}($params);
            $xml = new SimpleXMLElement($response->{$method.'Result'}->any);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
        if (!count($xml->children())) {
            throw new Exception('No results from OCA webservice');      //String compared in operatives test
        }
        if (property_exists($xml, 'NewDataSet'))
            return reset($xml->NewDataSet);
        else
            return reset($xml);

    }

}