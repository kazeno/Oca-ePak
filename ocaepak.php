<?php
/**
 * Oca Module for Prestashop
 * Tested in Prestashop v1.6.0.5
 *
 *  @author Rinku Kazeno <development@kazeno.co>
 *  @version 0.1
 */

if (!defined( '_PS_VERSION_') OR !defined('_CAN_LOAD_FILES_'))
    exit;

class OcaEpak extends CarrierModule
{
    const CONFIG_PREFIX = 'OCAEPAK';    //prefix for all internal config constants
    const OPERATIVES_TABLE = 'okae_operatives';    //DB table for Operatives

    protected $ocaUrl = 'http://webservice.oca.com.ar/oep_tracking/Oep_Track.asmx?WSDL';
    private $soapClient = NULL;

    public function __construct()
    {
        $this->name = 'ocaepak';     //DON'T CHANGE!!
        $this->tab = 'shipping_logistics';
        $this->version = '0.1';
        $this->author = 'R. Kazeno';
        $this->need_instance = 1;
        $this->ps_versions_compliancy = array('min' => '1.5', 'max' => '1.6');
        $this->module_key = '';
        $this->bootstrap = true;

        parent::__construct();
        $this->displayName = 'Oca e-Pak';
        $this->description = $this->l('##TK');
        $this->confirmUninstall = $this->l('This will delete any configured settings for this module. Continue?');
        $warnings = array();
        if (!extension_loaded('soap'))
            array_push($warnings, $this->l('You have the Soap PHP extension disabled. This module requires it for ##TK.'));
        /*if (Configuration::get(self::CONFIG_PREFIX.'_CLIENT_ID')=='0' OR !Tools::strlen(Configuration::get(self::CONFIG_PREFIX.'_CLIENT_ID')))
            array_push($warnings, $this->l('You need to configure your account settings.'));*/
        /*if (!extension_loaded('json'))
            array_push($warnings, $this->l('JSON extension not installed. This module requires it for handling JSON-encoded data.'));*/
        $this->warning = implode(' | ', $warnings);
        /*if (_PS_VERSION_ < '1.5') {         //Backwards compatibility for Prestashop versions < 1.5
            require(_PS_MODULE_DIR_.$this->name.'/backward_compatibility/backward.php');
            if (!in_array('HelperForm', get_declared_classes()))
                require(_PS_MODULE_DIR_.$this->name.'/backward_compatibility/HelperForm.php');
        }*/
    }

    public function install()
    {
        return (
            parent::install() AND
            $this->db->Execute(
                'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . self::OPERATIVES_TABLE . '` (
                    `id` int(20) NOT NULL UNIQUE,
                    `description` text NULL,
                    `addfee` varchar(10) NULL,
                    PRIMARY KEY (`id`)
                )'
            ) AND
            $this->registerHook('displayCarrierList') AND
            $this->registerHook('displayAdminOrder') AND
            Configuration::updateValue(self::CONFIG_PREFIX.'_EMAIL', '') AND
            Configuration::updateValue(self::CONFIG_PREFIX.'_PASSWORD', '') AND
            Configuration::updateValue(self::CONFIG_PREFIX.'_CUIT', '') AND
            Configuration::updateValue(self::CONFIG_PREFIX.'_DEFWEIGHT', '0') AND
            Configuration::updateValue(self::CONFIG_PREFIX.'_DEFVOLUME', '0') AND
            Configuration::updateValue(self::CONFIG_PREFIX.'_POSTCODE', '')
        );
    }

    public function uninstall()
    {
        return (
            parent::uninstall() AND
            $this->db->Execute(
                'DROP TABLE IF EXISTS '._DB_PREFIX_.self::OPERATIVES_TABLE
            ) AND
            Configuration::deleteByName(self::CONFIG_PREFIX.'_EMAIL') AND
            Configuration::deleteByName(self::CONFIG_PREFIX.'_PASSWORD') AND
            Configuration::deleteByName(self::CONFIG_PREFIX.'_CUIT') AND
            Configuration::deleteByName(self::CONFIG_PREFIX.'_DEFWEIGHT') AND
            Configuration::deleteByName(self::CONFIG_PREFIX.'_DEFVOLUME') AND
            Configuration::deleteByName(self::CONFIG_PREFIX.'_POSTCODE')
        );
    }

    public function getContent()
    {
        ob_start();
        if (Tools::isSubmit('submitOcaepak'))
            echo ($error = $this->_getErrors()) ? $error : $this->_saveConfig();
        if (Tools::strlen(Configuration::get(self::CONFIG_PREFIX.'_EMAIL')) && Tools::strlen(Configuration::get(self::CONFIG_PREFIX.'_PASSWORD'))) {
            //Check API Access is working correctly
            try {
                //$this->_getSoapClient()->;
            } catch (Exception $e) {
                echo $this->displayError($e->getMessage());
            }
        }
        $header = ob_get_clean();

        $fields_form = array(
            array(
                'form' => array(
                    'legend' => array(
                        'title' => '1. '.$this->l('Account')
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
                            'label' => $this->l('CUIT'),
                            'name' => 'cuit',
                            'class' => 'fixed-width-lg'
                        ),
                    ),
                )
            ),
            array(
                'form' => array(
                    'legend' => array(
                        'title' => '2. '.$this->l('Operatives')
                    ),
                    ### @todo: Operatives list
                )
            ),
            array(
                'form' => array(
                    'legend' => array(
                        'title' => '3. '.$this->l('Shipments')
                    ),
                    'input' => array(
                        array(
                            'type' => 'text',
                            'label' => $this->l('Origin Post Code'),
                            'name' => 'postcode',
                            'class' => 'fixed-width-lg'
                        ),
                    )
                )
            ),
            array(
                'form' => array(
                    'legend' => array(
                        'title' => '4. '.$this->l('Failsafes')
                    ),
                    'description' => $this->l('This settings will be used in case of missing data'),
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
                            'desc' => $this->l('This is the shipping cost that will be used in the unlikely event the Oca server is down and we cannot get an online quote')
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
        $fields_value = array_merge(
            array(
                'client_id' => Tools::getValue('client_id', Configuration::get(self::CONFIG_PREFIX.'_CLIENT_ID')),
                'client_secret' => Tools::getValue('client_secret', Configuration::get(self::CONFIG_PREFIX.'_CLIENT_SECRET')),
                'fee' => Tools::getValue('fee', Configuration::get(self::CONFIG_PREFIX.'_FEE')),
                'checkout_type' => Tools::getValue('checkout_type', Configuration::get(self::CONFIG_PREFIX.'_CHECKOUT_TYPE')),
                'sandbox' => Tools::getValue('sandbox', Configuration::get(self::CONFIG_PREFIX.'_SANDBOX_MODE')),
                'wait_status_name' => '',
                'color' => '#ffff00',
            ),
            array()
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
        $helper->submit_action = '';
        $helper->fields_value = $fields_value;

        return $header.$helper->generateForm($fields_form);
    }

    protected function _getErrors()
    {
        ### check duplicate references
        $error = '';
        if (!Validate::isEmail(Tools::getValue('email')))
            $error .= $this->displayError($this->l('Invalid email'));
        if (!Validate::isPasswd(Tools::getValue('password'), 3))
            $error .= $this->displayError($this->l('Invalid password'));
        //if (!count($this->toolGetPayments()))
        //    $error .= $this->displayError($this->l('You must select at least one payment type'));
        if (!Tools::strlen(trim(Tools::getValue('cuit'))))
            $error .= $this->displayError($this->l('Invalid CUIT'));
        if (!is_numeric(Tools::getValue('defweight')))
            $error .= $this->displayError($this->l('Invalid failsafe default weight'));
        if (!is_numeric(Tools::getValue('defvolume')))
            $error .= $this->displayError($this->l('Invalid failsafe default volume'));
        if (!Validate::isUnsignedInt(Tools::getValue('postcode')))
            $error .= $this->displayError($this->l('Invalid postcode'));
        return $error;
    }

    protected function _saveConfig()
    {
        ###process operatives data
        Configuration::updateValue(self::CONFIG_PREFIX.'_EMAIL', trim(Tools::getValue('email')));
        Configuration::updateValue(self::CONFIG_PREFIX.'_PASSWORD', Tools::getValue('password'));
        Configuration::updateValue(self::CONFIG_PREFIX.'_CUIT', trim(Tools::getValue('cuit')));
        Configuration::updateValue(self::CONFIG_PREFIX.'_DEFWEIGHT', Tools::getValue('defweight'));
        Configuration::updateValue(self::CONFIG_PREFIX.'_DEFVOLUME', Tools::getValue('defvolume'));
        Configuration::updateValue(self::CONFIG_PREFIX.'_POSTCODE', Tools::getValue('postcode'));
        return $this->displayConfirmation($this->l('Configuration saved'));
    }

    protected function _saveOperatives($operatives)
    {
        Db::getInstance()->Execute('
            DELETE FROM '._DB_PREFIX_.self::OPERATIVES_TABLE
        );
        return Db::getInstance()->Execute('
            INSERT INTO '._DB_PREFIX_.self::OPERATIVES_TABLE."
            (`id`, `description`, `fee`)
            VALUES ({$operatives['id']}, {$operatives['description']}, {$operatives['fee']})"
        );
    }


    public function getOrderShippingCost($params, $shipping_cost)
    {

    }

    public function getOrderShippingCostExternal($params)
    {

    }


    protected function _getSoapClient()
    {
        if (!is_null($this->soapClient))
            return $this->soapClient;
        $this->soapClient = new SoapClient($this->ocaUrl,
            array(
            "trace"      => _PS_MODE_DEV_,
            "exceptions" => 1,
            "cache_wsdl" => 0)
        );
        return $this->soapClient;
    }

}