<?php
/**
 * Oca e-Pak Module for Prestashop
 *
 * Tested in Prestashop v1.5.0.17, 1.5.6.2, 1.6.0.5, 1.6.0.6, 1.6.0.14, 1.6.1.0
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
 *  @version 1.2
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
    const RELAYS_TABLE = 'ocae_relays';             //DB table for relays
    const RELAYS_ID = 'id_ocae_relays';              //DB table id for quotes
    const GEOCODES_TABLE = 'ocae_geocodes';             //DB table for relays
    const GEOCODES_ID = 'id_ocae_geocodes';             //DB table id for relays
    const TRACKING_URL = 'https://www1.oca.com.ar/OEPTrackingWeb/trackingenvio.asp?numero1=@';
    const OCA_URL = 'http://webservice.oca.com.ar/epak_tracking/Oep_TrackEPak.asmx?wsdl';

    const PADDING = 1;          //space to add around each product for volume calculations, in cm

    public $id_carrier;
    private $soapClient = NULL;
    protected  $guiHeader = '';

    public function __construct()
    {
        $this->name = 'ocaepak';            //DON'T CHANGE!!
        $this->tab = 'shipping_logistics';
        $this->version = '1.2.0';
        $this->author = 'R. Kazeno';
        $this->need_instance = 1;
        $this->ps_versions_compliancy = array('min' => '1.5', 'max' => '1.6');
        $this->module_key = '8ba7bceea44707dc9d6043606694cea5';
        $this->bootstrap = true;
        include_once _PS_MODULE_DIR_."{$this->name}/classes/OcaEpakOperative.php";
        include_once _PS_MODULE_DIR_."{$this->name}/classes/OcaEpakQuote.php";
        include_once _PS_MODULE_DIR_."{$this->name}/classes/OcaEpakRelay.php";
        include_once _PS_MODULE_DIR_."{$this->name}/classes/OcaEpakGeocoding.php";

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
                /*'CREATE TABLE IF NOT EXISTS `' . pSQL(_DB_PREFIX_ . self::OPERATIVES_TABLE) . '` (
                    `'.pSQL(self::OPERATIVES_ID).'` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `id_carrier` INT UNSIGNED NULL,
                    `reference` INT UNSIGNED NOT NULL,
                    `description` text NULL,
                    `addfee` varchar(10) NULL,
                    `id_shop` INT UNSIGNED NOT NULL,
                    `type` CHAR(3) NOT NULL,
                    `old_carriers` VARCHAR(250) NULL,
                    `insured` INT UNSIGNED NULL,
                    PRIMARY KEY (`'.pSQL(self::OPERATIVES_ID).'`)
                )'*/
                $this->interpolateSqlFile('create-operatives-table', array(
                    '{$DB_PREFIX}' => _DB_PREFIX_,
                    '{$TABLE_NAME}' => self::OPERATIVES_TABLE,
                    '{$TABLE_ID}' => self::OPERATIVES_ID
                ))
            ) AND
            $db->Execute(
                /*'CREATE TABLE IF NOT EXISTS `' . pSQL(_DB_PREFIX_ . self::QUOTES_TABLE) . '` (
                    `reference` INT UNSIGNED NOT NULL,
                    `postcode` INT UNSIGNED NOT NULL,
                    `origin` INT UNSIGNED NOT NULL,
                    `volume` float unsigned NOT NULL,
                    `weight` float unsigned NOT NULL,
                    `value` float unsigned NOT NULL,
                    `price` float NOT NULL,
                    `date` datetime NOT NULL,
                    PRIMARY KEY (`reference`,`postcode`,`origin`,`volume`,`weight`,`value`),
                    UNIQUE KEY `quote` (`reference`,`postcode`,`origin`,`volume`,`weight`,`value`)
                )'*/
                $this->interpolateSqlFile('create-quotes-table', array(
                    '{$DB_PREFIX}' => _DB_PREFIX_,
                    '{$TABLE_NAME}' => self::QUOTES_TABLE,
                ))
            ) AND
            $db->Execute(
                /*'CREATE TABLE IF NOT EXISTS `' . pSQL(_DB_PREFIX_ . self::RELAYS_TABLE) . '` (
                    `'.pSQL(self::RELAYS_ID).'` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `id_cart` INT UNSIGNED NOT NULL,
                    `distribution_center_id` INT UNSIGNED NOT NULL,
                    PRIMARY KEY (`'.pSQL(self::RELAYS_ID).'`)
                )'*/
                $this->interpolateSqlFile('create-relays-table', array(
                    '{$DB_PREFIX}' => _DB_PREFIX_,
                    '{$TABLE_NAME}' => self::RELAYS_TABLE,
                    '{$TABLE_ID}' => self::RELAYS_ID
                ))
            ) AND
            $db->Execute(
                $this->interpolateSqlFile('create-geocodes-table', array(
                    '{$DB_PREFIX}' => _DB_PREFIX_,
                    '{$TABLE_NAME}' => self::GEOCODES_TABLE,
                    '{$TABLE_ID}' => self::GEOCODES_ID
                ))
            ) AND
            $db->Execute(
                $this->interpolateSqlFile('populate-geocodes-table', array(
                    '{$DB_PREFIX}' => _DB_PREFIX_,
                    '{$TABLE_NAME}' => self::GEOCODES_TABLE,
                    '{$TABLE_ID}' => self::GEOCODES_ID
                ))
            ) AND
            parent::install() AND
            $this->registerHook(_PS_VERSION_ < '1.5' ? 'extraCarrier' : 'displayCarrierList') AND
            $this->registerHook('displayAdminOrder') AND
            $this->registerHook('updateCarrier') AND
            Configuration::updateValue(self::CONFIG_PREFIX.'_ACCOUNT', '') AND
            Configuration::updateValue(self::CONFIG_PREFIX.'_EMAIL', '') AND
            Configuration::updateValue(self::CONFIG_PREFIX.'_PASSWORD', '') AND
            Configuration::updateValue(self::CONFIG_PREFIX.'_CUIT', '') AND
            Configuration::updateValue(self::CONFIG_PREFIX.'_DEFWEIGHT', '0.25') AND
            Configuration::updateValue(self::CONFIG_PREFIX.'_DEFVOLUME', '0.125') AND
            Configuration::updateValue(self::CONFIG_PREFIX.'_FAILCOST', '63.37') AND
            Configuration::updateValue(self::CONFIG_PREFIX.'_POSTCODE', '')
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
            Configuration::deleteByName(self::CONFIG_PREFIX.'_ACCOUNT') AND
            Configuration::deleteByName(self::CONFIG_PREFIX.'_EMAIL') AND
            Configuration::deleteByName(self::CONFIG_PREFIX.'_PASSWORD') AND
            Configuration::deleteByName(self::CONFIG_PREFIX.'_CUIT') AND
            Configuration::deleteByName(self::CONFIG_PREFIX.'_DEFWEIGHT') AND
            Configuration::deleteByName(self::CONFIG_PREFIX.'_DEFVOLUME') AND
            Configuration::deleteByName(self::CONFIG_PREFIX.'_FAILCOST') AND
            Configuration::deleteByName(self::CONFIG_PREFIX.'_POSTCODE')
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
            $this->_addToHeader(
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
                return $this->displayError($this->_makeErrorFriendly($val)).$this->getAddOperativeContent();
            $op->save();
            $this->_addToHeader($this->displayConfirmation($confirm));
        } elseif (Tools::isSubmit('submitOcaepak'))
            $this->_addToHeader(($error = $this->_getErrors()) ? $error : $this->_saveConfig());
        if (Tools::strlen(Configuration::get(self::CONFIG_PREFIX.'_EMAIL')) && Tools::strlen(Configuration::get(self::CONFIG_PREFIX.'_PASSWORD'))&& Tools::strlen(Configuration::get(self::CONFIG_PREFIX.'_POSTCODE')) && Tools::strlen(Configuration::get(self::CONFIG_PREFIX.'_CUIT')) && count($ops = OcaEpakOperative::getOperativeIds(true))) {
            //Check API Access is working correctly
            foreach ($ops as $op) {
                try {
                    $response = $this->_getSoapClient()->Tarifar_Envio_Corporativo(array(
                        'PesoTotal' => '1',
                        'VolumenTotal' => '0.05',
                        'ValorDeclarado' => '100',
                        'CodigoPostalOrigen' => Configuration::get(self::CONFIG_PREFIX.'_POSTCODE'),
                        'CodigoPostalDestino' => Configuration::get(self::CONFIG_PREFIX.'_POSTCODE') == 9120 ? 1924 : 9120,
                        'CantidadPaquetes' => 1,
                        'Cuit' => Configuration::get(self::CONFIG_PREFIX.'_CUIT'),
                        'Operativa' => $op->reference
                    ));
                    $xml = new SimpleXMLElement($response->Tarifar_Envio_CorporativoResult->any);
                    if (!count($xml->children()))
                        $this->_addToHeader($this->displayError($this->l('There seems to be an error in the OCA operative with reference')." {$op->reference}"));
                } catch (Exception $e) {
                    $this->_addToHeader($this->displayError($e->getMessage()));
                }
            }
        }
        ob_start(); ?>
        <?php if (_PS_VERSION_ < '1.6') : ?><style>
            .panel {
                border: 1px solid #CCCED7;
                padding: 6px;
            }
            .panel-heading {
                color: #585a69;
                text-shadow: 0 1px 0 #fff;
                font-weight: bold;
                font-size: 14px;
            }
            .icon-trash-o:before, .icon-trash:before, #content .process-icon-delete:before, #content .process-icon-uninstall:before {
                content: "\24cd";
            }
            [class^="process-icon-"] {
                display: inline-block;
                position: relative;
                bottom: 6px;
                left: 4px;
                margin: 0 auto;
                font-size: 14px;
                color: #585a69;
                float: right;
                border-left: 1px solid #CCCED7;
                border-bottom: 1px solid #CCCED7;
            }
            [class^="process-icon-"]:hover {
                background-color: #F8F8F8;
                color: #000;
            }

    </style><?php endif; ?>
        <script>
            $(document).ready(function() {
              <?php if (_PS_VERSION_ < '1.6') : ?>
                var $form1 = $('#content>form').first().attr("id", "form1");
                var $form2 = $('#content>form').last().attr("id", "form2");
                var $table = $('#content>form').not($form1).not($form2).attr("id", 'ocae_operatives');
              <?php else : ?>
                var $form1 = $('#form1');
                var $form2 = $('#form2');
                var $table = $('form[id$="ocae_operatives"]');
              <?php endif; ?>
                function syncInputs(event) {
                    event.data.target.find("[name='"+$(this).attr("name")+"']").val($(this).val());
                    $table.find("[name='"+$(this).attr("name")+"']").val($(this).val());
                }
                $('#desc-ocae_operatives-refresh, #content>form a[href="javascript:location.reload();"]').hide();
                $('tr.filter').hide();
                $('#desc-ocae_operatives-new').bind('click', function() {
                    $table.attr('action', $(this).attr('href')).submit();
                    return !$table.length;
                });
                $form1.add($form2).find("input[type='hidden']").clone().appendTo($table);
                $form1.find("input[type='text']").bind('change', {target: $form2}, syncInputs);
                $form2.find("input[type='text']").bind('change', {target: $form1}, syncInputs);
            });
        </script>
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
                            'desc' => $this->l('You can find your account number by logging into your OCA account and going to the following URL').': <br /><a href="http://www4.oca.com.ar/ocaepak/Seguro/ListadoOperativas.asp" target="_blank" style="text-decoration: underline; font-weight: 700;" />http://www4.oca.com.ar/ocaepak/Seguro/ListadoOperativas.asp</a>',
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
                            'size' => 4,
                            'label' => $this->l('Origin Post Code'),
                            'name' => 'postcode',
                            'class' => 'fixed-width-lg',
                            'desc' => $this->l('The post code from where shipments will be made (only digits)'),
                            'required' => true

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

    public function getAddOperativeContent($operativeId = NULL)
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
        $fields_value = array_merge(
            array(
                //Preserve previously input data:
                'account' => Tools::getValue('account', Configuration::get(self::CONFIG_PREFIX.'_ACCOUNT')),
                'email' => Tools::getValue('email', Configuration::get(self::CONFIG_PREFIX.'_EMAIL')),
                'password' => Tools::getValue('password', Configuration::get(self::CONFIG_PREFIX.'_PASSWORD')),
                'cuit' => Tools::getValue('cuit', Configuration::get(self::CONFIG_PREFIX.'_CUIT')),
                'defweight' => Tools::getValue('defweight', Configuration::get(self::CONFIG_PREFIX.'_DEFWEIGHT')),
                'defvolume' => Tools::getValue('defvolume', Configuration::get(self::CONFIG_PREFIX.'_DEFVOLUME')),
                'postcode' => Tools::getValue('postcode', Configuration::get(self::CONFIG_PREFIX.'_POSTCODE')),
                'failcost' => Tools::getValue('failcost', Configuration::get(self::CONFIG_PREFIX.'_FAILCOST')),
            ), $fields
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
        $helper->actions = array('edit', 'delete');
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
        ## @todo: validate pickup address
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
        $op = OcaEpakOperative::getByCarrierId($params['cart']->id_carrier);
        if (!$op)
            return NULL;
        $address = new Address($params['cart']->id_address_delivery);
        $currency = new Currency($params['cart']->id_currency);
        $order = new Order($params['id_order']);
        //$customer = new Customer($order->id_customer);
        $cartData = $this->getCartPhysicalData($params['cart']);
        $shipping = $params['cart']->getTotalShippingCost(NULL, FALSE);
        $totalToPay = Tools::ps_round($this->getTotalWithFee($shipping, $op->addfee), 2);
        $paidFee = $totalToPay - $shipping;
        $relay = OcaEpakRelay::getByCartId($order->id_cart);
        try {
            $response = $this->_getSoapClient()->Tarifar_Envio_Corporativo(array(
                'PesoTotal' => $cartData['weight'],
                'VolumenTotal' => $cartData['volume'],
                'ValorDeclarado' => $cartData['cost'],
                'CodigoPostalOrigen' => Configuration::get(self::CONFIG_PREFIX.'_POSTCODE'),
                'CodigoPostalDestino' => self::cleanPostcode($address->postcode),
                'CantidadPaquetes' => 1,
                'Cuit' => Configuration::get(self::CONFIG_PREFIX.'_CUIT'),
                'Operativa' => $op->reference
            ));
            $xml = new SimpleXMLElement($response->Tarifar_Envio_CorporativoResult->any);
            if (!count($xml->children()))
                throw new Exception($this->l('No results received from OCA webservice'));
            $data = $xml->NewDataSet->Table;
            $quote = Tools::ps_round($this->convertCurrencyFromArs($data->Total, $params['cart']->id_currency), 2);
            $quoteError = null;
        } catch (Exception $e) {
            $quoteError = $e->getMessage();
            $data = null;
            $quote = null;
        }
        $distributionCenter = array();
        if (in_array($op->type, array('PaS','SaS')) && ($relay)) {
            $distributionCenter = $this->_retrieveOcaBranchData($relay->distribution_center_id);
            //**/Tools::dieObject($distributionCenter);
        }
        $this->context->smarty->assign(  array(
            'moduleName' => self::MODULE_NAME,
            'currencySign' => $currency->sign,
            'operative' => $op,
            'cartData' => $cartData,
            'quote' => $quote,
            'quoteData' => $data,
            'quoteError' => $quoteError,
            'paidFee' => $paidFee,
            'distributionCenter' => $distributionCenter,
        ) );
        return $this->display(__FILE__, _PS_VERSION_ < '1.6' ? 'displayAdminOrder15.tpl' : 'displayAdminOrder.tpl');
    }

    /**
     * Show pickup options
     * @param Array $params ['address']
     */
    public function hookDisplayCarrierList($params)
    {
        //return '<pre>'.print_r($this->getCartPhysicalData($params['cart']), TRUE).'</pre>';
        if (!$this->active OR $params['address']->id_country != Country::getByIso('AR'))
            return FALSE;
        $carrierIds = OcaEpakOperative::getRelayedCarrierIds();
        if (count($carrierIds)) {
            $this->context->controller->addJS('http'.((Configuration::get('PS_SSL_ENABLED') && Configuration::get('PS_SSL_ENABLED_EVERYWHERE')) ? 's' : '').'://maps.google.com/maps/api/js?sensor=true&region=AR');
            //$this->context->controller->addJS(_THEME_JS_DIR_.'stores.js');
            try {
                $response = $this->_getSoapClient()->GetCentrosImposicion/*PorCP*/(array(
                    'CodigoPostal' => self::cleanPostcode($params['address']->postcode)
                ));
                $xml = new SimpleXMLElement($response->GetCentrosImposicionResult->any);
                if (!count($xml->children()))
                    return NULL;
                $relays = array();
                foreach ($xml->NewDataSet->children() as $table) {
                    $relays[] = Tools::jsonDecode(str_replace('{}', '""', Tools::jsonEncode((array)$table)), TRUE);
                }
                $coordTree = OcaEpakGeocoding::getCoordinateTree();
                foreach ($relays as $idx => $rel) {
                    if (count($coords = OcaEpakGeocoding::getOcaBranchCoordinates($rel,  $coordTree))) {
                        $relays[$idx]['lat'] = $coords[0];
                        $relays[$idx]['lng'] = $coords[1];
                    }
                }
                $relay = OcaEpakRelay::getByCartId($this->context->cookie->id_cart);
                $this->context->smarty->assign(  array(
                    'customerAddress' => $params['address'],
                    'ocaepak_relays' => $relays,
                    'relayed_carriers' => Tools::jsonEncode($carrierIds),
                    'ocaepak_name' => $this->name,
                    'ocaepak_selected_relay' => $relay ? $relay->distribution_center_id : null,
                    'psver' => _PS_VERSION_,
                    'force_ssl' => Configuration::get('PS_SSL_ENABLED') || Configuration::get('PS_SSL_ENABLED_EVERYWHERE')
                ) );
                return $this->display(__FILE__, 'displayCarrierList.tpl');
            } catch (Exception $e) {
                /**/Tools::dieObject($e);
                Logger::AddLog('Ocaepak: '.$this->l('Error getting pickup centers for cp')." {$params['address']->postcode}");
                return FALSE;
            }
        }
        return NULL;
    }
    public function hookExtraCarrier($params) { return $this->hookDisplayCarrierList($params); }

    public function hookUpdateCarrier($params)
    {
        if ($op = OcaEpakOperative::getByFieldId('id_carrier', $params['id_carrier'])) {
            return $op->updateCarrier((int)$params['carrier']->id);
        }
        return true;
    }


    public function getOrderShippingCost($cart, $shipping_cost)
    {
        $address = new Address($cart->id_address_delivery);
        $op = OcaEpakOperative::getByFieldId('id_carrier', $this->id_carrier);
        if (!$this->active OR $address->id_country != Country::getByIso('AR') OR !$op)
            return FALSE;
        $carrier = new Carrier($this->id_carrier);
        if ($carrier->shipping_method == Carrier::SHIPPING_METHOD_PRICE)
            return $shipping_cost;
        try {
            $cartData = $this->getCartPhysicalData($cart);
            if ($cot = OcaEpakQuote::retrieve($op->reference, self::cleanPostcode($address->postcode), Configuration::get(self::CONFIG_PREFIX.'_POSTCODE'), $cartData['volume'], $cartData['weight'], $cartData['cost']))      //get cache
                return (float)Tools::ps_round($shipping_cost+$this->convertCurrencyFromArs($this->getTotalWithFee($cot, $op->addfee), $cart->id_currency), 2);
            $response = $this->_getSoapClient()->Tarifar_Envio_Corporativo(array(
                'PesoTotal' => $cartData['weight'],
                'VolumenTotal' => $cartData['volume'],
                'ValorDeclarado' => $cartData['cost'],
                'CodigoPostalOrigen' => Configuration::get(self::CONFIG_PREFIX.'_POSTCODE'),
                'CodigoPostalDestino' => self::cleanPostcode($address->postcode),
                'CantidadPaquetes' => 1,
                'Cuit' => Configuration::get(self::CONFIG_PREFIX.'_CUIT'),
                'Operativa' => $op->reference
            ));
            $xml = new SimpleXMLElement($response->Tarifar_Envio_CorporativoResult->any);
            if (!count($xml->children()))
                throw new Exception('No results from OCA webservice');
            $data = $xml->NewDataSet->Table;
            if ($data->Total > 0)       //set cache
                OcaEpakQuote::insert($op->reference, self::cleanPostcode($address->postcode), Configuration::get(self::CONFIG_PREFIX.'_POSTCODE'), $cartData['volume'], $cartData['weight'], $cartData['cost'], $data->Total);
        } catch (Exception $e) {
            Logger::AddLog('Ocaepak: '.$this->l('error getting online price for cart')." {$cart->id}");
            return (float)$this->convertCurrencyFromArs(Configuration::get(self::CONFIG_PREFIX.'_FAILCOST'), $cart->id_currency);
        }
        return (float)Tools::ps_round($shipping_cost+$this->convertCurrencyFromArs($this->getTotalWithFee($data->Total, $op->addfee), $cart->id_currency), 2);
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
        $cost = 0;
        switch (Configuration::get('PS_DIMENSION_UNIT')) {
            case 'm':
                $divider = 1;
                $padding = self::PADDING/100;
                break;
            case 'in':
                $divider = 39.37*39.37*39.37;  //39.37 in to 1 m
                $padding = self::PADDING*0.3937;
                break;
            case 'cm':
            default:
                $divider = 1000000;
                $padding = self::PADDING;
                break;
        }
        foreach ($products as $product) {
            $productObj = new Product($product['id_product']);
            $carriers = $productObj->getCarriers();
            $isProductCarrier = false;
            foreach ($carriers as $carrier) {
                if ($carrier['id_carrier'] == $this->id_carrier) {
                    $isProductCarrier = true;
                    continue;
                }
            }
            if ($product['is_virtual'] or (count($carriers) && !$isProductCarrier))
                continue;
            $weight += ($product['weight'] > 0 ? $product['weight'] : Configuration::get(self::CONFIG_PREFIX.'_DEFWEIGHT')) * $product['cart_quantity'];
            $volume += (
                $product['width']*$product['height']*$product['depth'] > 0 ?
                    (($product['width']+2*$padding)*($product['height']+2*$padding)*($product['depth']+2*$padding))/$divider :
                    Configuration::get(self::CONFIG_PREFIX.'_DEFVOLUME')
            )*$product['cart_quantity'];
            $cost += $product['total_wt'];
        }

        return array('weight' => $weight, 'volume' => $volume, 'cost' => $cost);
    }

    public static function cleanPostcode($postcode)
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

    protected function _retrieveOcaBranchData($id_branch)
    {
        try {
            $response = $this->_getSoapClient()->GetCentrosImposicion(array());
            $xml = new SimpleXMLElement($response->GetCentrosImposicionResult->any);

            if (!count($xml->children()))
                return NULL;
            foreach ($xml->NewDataSet->children() as $table) {
                if (trim((string)$table->idCentroImposicion) == $id_branch)
                    return Tools::jsonDecode(str_replace('{}', '""', Tools::jsonEncode((array)$table)), TRUE);
            }
        } catch (Exception $e) {
            Logger::AddLog('Ocaepak: '.$this->l('Error getting pickup centers for branch')." {$id_branch}");
        }
        return false;
    }

    protected function _makeErrorFriendly($error)
    {
        $replacements = array(
            'Property OcaEpakOperative->reference' => $this->l('Operative Reference'),
            'Property OcaEpakOperative->description' => $this->l('Description')
        );

        return str_replace(array_keys($replacements), array_values($replacements), $error);
    }

    protected function _cleanOcaAttribute($text, $maxLength, $fromEnd = false)
    {
        $clean = trim(htmlspecialchars(iconv('utf-8','ascii//TRANSLIT', $text)));
        if (strpos($clean, '?') !== false) {
            @setlocale(LC_TIME, 'es_ES');
            $clean = trim(htmlspecialchars(iconv('utf-8','ascii//TRANSLIT', $text)));
        }

        if ($fromEnd)
            return Tools::strlen($clean) > $maxLength ? Tools::substr($clean, -$maxLength) : $clean;
        else
            return Tools::strlen($clean) > $maxLength ? Tools::substr($clean, 0, $maxLength) : $clean;
    }

    public function interpolateSqlFile($fileName, $replacements)
    {
        $filePath = _PS_MODULE_DIR_."{$this->name}/sql/{$fileName}.sql";
        if (!file_exists($filePath))
            throw new Exception('Wrong SQL Interpolation File Name: '.$fileName);
        $file = file_get_contents($filePath);
        foreach ($replacements as $var => $repl) {
            $replacements[$var] = pSQL($repl);
        }
        return str_replace(array_keys($replacements), array_values($replacements), $file);

    }

}