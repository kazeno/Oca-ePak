<?php
/**
 * Oca e-Pak Module for Prestashop
 *
 * Tested in Prestashop v1.5.0.17, 1.5.6.2, 1.6.0.5, 1.6.0.6, 1.6.0.14
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
    const RELAYS_TABLE = 'ocae_relays';             //DB table for orders
    const RELAYS_ID = 'id_ocae_relays';             //DB table id for orders
    const TRACKING_URL = 'https://www1.oca.com.ar/OEPTrackingWeb/trackingenvio.asp?numero1=@';
    const OCA_URL = 'http://webservice.oca.com.ar/oep_tracking/Oep_Track.asmx?WSDL';

    const PADDING = 1;          //space to add around each product for volume calculations, in cm

    public $id_carrier;
    private $soapClient = NULL;
    protected  $guiHeader = '';
    private $boxes = array();

    public function __construct()
    {
        $this->name = 'ocaepak';            //DON'T CHANGE!!
        $this->tab = 'shipping_logistics';
        $this->version = '1.0.5.2';
        $this->author = 'R. Kazeno';
        $this->need_instance = 1;
        $this->ps_versions_compliancy = array('min' => '1.5', 'max' => '1.6');
        $this->module_key = '8ba7bceea44707dc9d6043606694cea5';
        $this->bootstrap = true;
        include_once _PS_MODULE_DIR_."{$this->name}/classes/OcaEpakOperative.php";
        include_once _PS_MODULE_DIR_."{$this->name}/classes/OcaEpakOrder.php";
        include_once _PS_MODULE_DIR_."{$this->name}/classes/OcaEpakRelay.php";

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
                    `old_carriers` VARCHAR(250) NULL,
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
            $db->Execute(
                'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . self::RELAYS_TABLE . '` (
                    `'.self::RELAYS_ID.'` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `id_cart` INT UNSIGNED NOT NULL,
                    `distribution_center_id` INT UNSIGNED NOT NULL,
                    PRIMARY KEY (`'.self::RELAYS_ID.'`)
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
            Configuration::updateValue(self::CONFIG_PREFIX.'_DEFVOLUME', '0.125') AND
            Configuration::updateValue(self::CONFIG_PREFIX.'_FAILCOST', '63.37') AND
            Configuration::updateValue(self::CONFIG_PREFIX.'_POSTCODE', '') AND
            Configuration::updateValue(self::CONFIG_PREFIX.'_ENABLE_PICKUP_ORDERS', 0) AND
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
            Configuration::updateValue(self::CONFIG_PREFIX.'_TIMESLOT', '') AND
            Configuration::updateValue(self::CONFIG_PREFIX.'_AUTO_ORDER', false)
        );
    }

    public function uninstall()
    {
        //**/return parent::uninstall();
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
            $db->Execute(
                'DROP TABLE IF EXISTS '._DB_PREFIX_.self::RELAYS_TABLE
            ) AND
            Configuration::deleteByName(self::CONFIG_PREFIX.'_ACCOUNT') AND
            Configuration::deleteByName(self::CONFIG_PREFIX.'_EMAIL') AND
            Configuration::deleteByName(self::CONFIG_PREFIX.'_PASSWORD') AND
            Configuration::deleteByName(self::CONFIG_PREFIX.'_CUIT') AND
            Configuration::deleteByName(self::CONFIG_PREFIX.'_DEFWEIGHT') AND
            Configuration::deleteByName(self::CONFIG_PREFIX.'_DEFVOLUME') AND
            Configuration::deleteByName(self::CONFIG_PREFIX.'_FAILCOST') AND
            Configuration::deleteByName(self::CONFIG_PREFIX.'_POSTCODE') AND
            Configuration::deleteByName(self::CONFIG_PREFIX.'_ENABLE_PICKUP_ORDERS') AND
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
            //**/Tools::dieObject($op);
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
                        'VolumenTotal' => '50',
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
                var $table = $('form[id$="ocae_operatives"');
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
        ob_start();  ?>
            <div class="panel" id="oca-box-1">
                <div class="panel-heading">
                    <?php echo $this->l('Box'); ?> <span class="num">1</span>
                    <span class="panel-heading-action">
                        <a class="list-toolbar-btn box-delete">
					        <span title="<?php  echo $this->l('Delete');  ?>">
						        <i class="process-icon-delete"></i>
					        </span>
                        </a>
                    </span>
                </div>
                <div class="form-group">
                    <label for="boxes-box" class="control-label col-lg-3 ">
                        <?php echo $this->l('Dimensions'); ?>
                    </label>
                    <div class="col-lg-9">
                        <input type="text" name="oca-box-l-1" id="oca-box-l-1" value="" class="fixed-width-sm" style="display: inline-block;" size="8"> cm ×
                        <input type="text" name="oca-box-d-1" id="oca-box-d-1" value="" class="fixed-width-sm" style="display: inline-block;" size="8"> cm ×
                        <input type="text" name="oca-box-h-1" id="oca-box-h-1" value="" class="fixed-width-sm" style="display: inline-block;" size="8"> cm
                    </div>
                </div>
                <div class="form-group">
                    <label for="boxes-box" class="control-label col-lg-3 ">
                        <?php echo $this->l('Box contents maximum weight'); ?>
                    </label>
                    <div class="col-lg-9">
                        <input type="text" name="oca-box-xw-1" id="oca-box-xw-1" value="" class="fixed-width-sm" style="display: inline-block;" size="8"> kg
                    </div>
                </div>
            </div>
            <div class="row-margin-bottom row-margin-top order_action margin-form">
                <button id="add_oca_box" class="btn btn-default" type="button">
                    <i class="icon-plus"></i>
                    <?php echo $this->l('Add a new box'); ?>
                </button>
            </div>
            <script>
                (function() {
                    var $box = $('#oca-box-1');
                    var boxnum = 1;
                    var boxesJson = [];
                    var container = $box.prop('outerHTML');
                    $('input[name=enable_pickup_orders]').change(togglePickup);
                    togglePickup();
                    $('#add_oca_box').click(function() {
                        boxnum += 1;
                        var $newbox = $('#oca-box-1').clone().attr('id', 'oca-box-'+boxnum);
                        $newbox.find('input').each(function(){
                            var split = $(this).attr('name').lastIndexOf('-')+1;
                            $(this).attr('name', $(this).attr('name').substr(0,split)+boxnum);
                            $(this).attr('id', $(this).attr('id').substr(0,split)+boxnum);
                        });
                        $newbox.find('.panel-heading>span.num').html(boxnum);
                        $('#add_oca_box').parent().before($newbox);
                        serializeBoxes();
                    });
                    $(document).on("change", '[id^="oca-box-"] input', function(event) {
                        serializeBoxes();
                    });
                    $(document).on("click", '.box-delete', function(event) {
                        var $div = $(this).closest('div[id^="oca-box-"]');
                        var split = $div.attr('id').lastIndexOf('-')+1;
                        var num = $div.attr('id').substr(split);
                        boxesJson.splice(num-1, 1);
                        $.grep(boxesJson,function(n){ return(n) });     //fix js null elements in array quirk
                        $('input[name="boxes"]').val(JSON.stringify(boxesJson, null, 2));
                        renderBoxes();
                    });

                    function togglePickup() {
                        if ($('input[name=enable_pickup_orders]:checked').val() == 1) {
                            $('.pickup_orders').prop('required', true).parents('.form-group').show();
                            $('.pickup_orders_required').parents('.form-group').find('label').addClass('required');
                        } else {
                            $('.pickup_orders').prop('required', false).parents('.form-group').hide();
                            $('.pickup_orders_required').parents('.form-group').find('label').removeClass('required');
                        }
                    }

                    function serializeBoxes() {
                        boxesJson = [];
                        $('[id^="oca-box-"]').each(function () {
                            var split = $(this).attr('id').lastIndexOf('-')+1;
                            var num = $(this).attr('id').substr(split);
                            var dimensions = [
                                $('input[name="oca-box-l-'+num+'"]').val() || 0,
                                $('input[name="oca-box-d-'+num+'"]').val() || 0,
                                $('input[name="oca-box-h-'+num+'"]').val() || 0
                            ];
                            dimensions.sort(function(a, b){ return b-a; });
                            boxesJson[num-1] = {
                                l: dimensions.shift(),
                                d: dimensions.shift(),
                                h: dimensions.shift(),
                                xw: $('input[name="oca-box-xw-'+num+'"]').val() || 0
                            };
                        });
                        $('input[name="boxes"]').val(JSON.stringify(boxesJson, null, 2));
                    }

                    function renderBoxes() {
                        $('[id^="oca-box-"]').remove();
                        boxesJson = JSON.parse($('input[name="boxes"]').val());
                        $.each(boxesJson, function (index, value) {
                            var $newbox = $(container);
                            $newbox.attr('id', 'oca-box-'+(1+index));
                            $newbox.find('input[name="oca-box-l-1"]').attr('name', 'oca-box-l-'+(1+index)).val(value.l);
                            $newbox.find('input[name="oca-box-d-1"]').attr('name', 'oca-box-d-'+(1+index)).val(value.d);
                            $newbox.find('input[name="oca-box-h-1"]').attr('name', 'oca-box-h-'+(1+index)).val(value.h);
                            $newbox.find('input[name="oca-box-xw-1"]').attr('name', 'oca-box-xw-'+(1+index)).val(value.xw);
                            $newbox.find('.panel-heading>span.num').html(1+index);
                            $('#add_oca_box').parent().before($newbox);
                        });
                        boxnum = boxesJson.length || 1;
                        if (boxesJson.length == 0)
                            $('#add_oca_box').parent().before($(container));
                    }
                    renderBoxes();
                })();
            </script>
        <?php  $boxBox = ob_get_clean();

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
                            'name' => 'street',
                        ),
                        array(
                            'type' => 'hidden',
                            'name' => 'number',
                        ),
                        array(
                            'type' => 'hidden',
                            'name' => 'floor',
                        ),
                        array(
                            'type' => 'hidden',
                            'name' => 'apartment',
                        ),
                        array(
                            'type' => 'hidden',
                            'name' => 'locality',
                        ),
                        array(
                            'type' => 'hidden',
                            'name' => 'province',
                        ),
                        array(
                            'type' => 'hidden',
                            'name' => 'contact',
                        ),
                        array(
                            'type' => 'hidden',
                            'name' => 'requestor',
                        ),
                        array(
                            'type' => 'hidden',
                            'name' => 'observations',
                        ),
                        array(
                            'type' => 'hidden',
                            'name' => 'failcost',
                        ),
                        array(
                            'type' => 'hidden',
                            'name' => 'timeslot',
                        ),
                        array(
                            'type' => 'hidden',
                            'name' => 'boxes',
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
                        array(
                            'type' => _PS_VERSION_ < 1.6 ? 'radio' : 'switch',
                            'label' => $this->l('Enable Pickup Order Generator'),
                            'name' => 'enable_pickup_orders',
                            'hint' => $this->l('Experimental feature'),
                            'class' => 't',
                            'is_bool' => TRUE,
                            'values' => array(
                                array(
                                    'id' => 'enable_pickup_orders_on',
                                    'value' => '1',
                                    'label' => 'on',
                                ),
                                array(
                                    'id' => 'enable_pickup_orders_off',
                                    'value' => '0',
                                    'label' => 'off',
                                ),
                            ),
                        ),
                        array(
                            'type' => 'text',
                            'maxlength' => 30,
                            'label' => $this->l('Street'),
                            'name' => 'street',
                            'class' => 'fixed-width-lg pickup_orders pickup_orders_required',
                        ),
                        array(
                            'type' => 'text',
                            'maxlength' => 5,
                            'label' => $this->l('Number'),
                            'name' => 'number',
                            'class' => 'fixed-width-lg pickup_orders pickup_orders_required',
                        ),
                        array(
                            'type' => 'text',
                            'maxlength' => 2,
                            'label' => $this->l('Floor'),
                            'name' => 'floor',
                            'class' => 'fixed-width-lg pickup_orders',
                        ),
                        array(
                            'type' => 'text',
                            'maxlength' => 4,
                            'label' => $this->l('Apartment'),
                            'name' => 'apartment',
                            'class' => 'fixed-width-lg pickup_orders',
                        ),
                        array(
                            'type' => 'text',
                            'maxlength' => 30,
                            'label' => $this->l('Locality'),
                            'name' => 'locality',
                            'class' => 'fixed-width-lg pickup_orders pickup_orders_required',
                        ),
                        array(
                            'type' => 'text',
                            'maxlength' => 30,
                            'label' => $this->l('Province'),
                            'name' => 'province',
                            'class' => 'fixed-width-lg pickup_orders',
                        ),
                        array(
                            'type' => 'text',
                            'maxlength' => 30,
                            'label' => $this->l('Contact'),
                            'name' => 'contact',
                            'class' => 'fixed-width-lg pickup_orders',
                        ),
                        array(
                            'type' => 'text',
                            'maxlength' => 30,
                            'label' => $this->l('Requestor'),
                            'name' => 'requestor',
                            'class' => 'fixed-width-lg pickup_orders',
                        ),
                        array(
                            'type' => 'text',
                            'maxlength' => 100,
                            'label' => $this->l('Observations'),
                            'name' => 'observations',
                            'class' => 'fixed-width-xxxxl pickup_orders',
                        ),
                        array(
                            'type' => 'select',
                            'label' => $this->l('Time slot'),
                            'name' => 'timeslot',
                            'class' =>  'pickup_orders',
                            'options' => array(
                                'query' => array(
                                    array('value' =>'1', 'text' =>'8-17'),
                                    array('value' =>'2', 'text' =>'8-12'),
                                    array('value' =>'3', 'text' =>'14-17'),
                                ),
                                'id' => 'value',
                                'name' => 'text'
                            ),
                            'desc' => $this->l('OCA collections will be made during this time slot')
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
                        'title' => '4. '.$this->l('Packaging')
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
                            //'label' => $this->l('Packaging'),
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
                    )
                )
            ),
            array(
                'form' => array(
                    'form' => array(
                        'id_form' => 'form2',
                    ),
                    'legend' => array(
                        'title' => '5. '.$this->l('Failsafes')
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
            'street' => Tools::getValue('street', Configuration::get(self::CONFIG_PREFIX.'_STREET')),
            'number' => Tools::getValue('number', Configuration::get(self::CONFIG_PREFIX.'_NUMBER')),
            'floor' => Tools::getValue('floor', Configuration::get(self::CONFIG_PREFIX.'_FLOOR')),
            'apartment' => Tools::getValue('apartment', Configuration::get(self::CONFIG_PREFIX.'_APARTMENT')),
            'locality' => Tools::getValue('locality', Configuration::get(self::CONFIG_PREFIX.'_LOCALITY')),
            'province' => Tools::getValue('province', Configuration::get(self::CONFIG_PREFIX.'_PROVINCE')),
            'contact' => Tools::getValue('contact', Configuration::get(self::CONFIG_PREFIX.'_CONTACT')),
            'requestor' => Tools::getValue('requestor', Configuration::get(self::CONFIG_PREFIX.'_REQUESTOR')),
            'observations' => Tools::getValue('observations', Configuration::get(self::CONFIG_PREFIX.'_OBSERVATIONS')),
            'failcost' => Tools::getValue('failcost', Configuration::get(self::CONFIG_PREFIX.'_FAILCOST')),
            'timeslot' => Tools::getValue('timeslot', Configuration::get(self::CONFIG_PREFIX.'_TIMESLOT') ? Configuration::get(self::CONFIG_PREFIX.'_TIMESLOT') : 1),
            'boxes' => Tools::getValue('boxes', Configuration::get(self::CONFIG_PREFIX.'_BOXES') ? Configuration::get(self::CONFIG_PREFIX.'_BOXES') : '[]'),
            'boxes-box' => $boxBox,
            'enable_pickup_orders' => Tools::getValue('enable_pickup_orders', Configuration::get(self::CONFIG_PREFIX.'_ENABLE_PICKUP_ORDERS'))
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
                            'name' => 'street',
                        ),
                        array(
                            'type' => 'hidden',
                            'name' => 'number',
                        ),
                        array(
                            'type' => 'hidden',
                            'name' => 'floor',
                        ),
                        array(
                            'type' => 'hidden',
                            'name' => 'apartment',
                        ),
                        array(
                            'type' => 'hidden',
                            'name' => 'locality',
                        ),
                        array(
                            'type' => 'hidden',
                            'name' => 'province',
                        ),
                        array(
                            'type' => 'hidden',
                            'name' => 'contact',
                        ),
                        array(
                            'type' => 'hidden',
                            'name' => 'requestor',
                        ),
                        array(
                            'type' => 'hidden',
                            'name' => 'observations',
                        ),
                        array(
                            'type' => 'hidden',
                            'name' => 'failcost',
                        ),
                        array(
                            'type' => 'hidden',
                            'name' => 'timeslot',
                        ),
                        array(
                            'type' => 'hidden',
                            'name' => 'boxes',
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
                'street' => Tools::getValue('street', Configuration::get(self::CONFIG_PREFIX.'_STREET')),
                'number' => Tools::getValue('number', Configuration::get(self::CONFIG_PREFIX.'_NUMBER')),
                'floor' => Tools::getValue('floor', Configuration::get(self::CONFIG_PREFIX.'_FLOOR')),
                'apartment' => Tools::getValue('apartment', Configuration::get(self::CONFIG_PREFIX.'_APARTMENT')),
                'locality' => Tools::getValue('locality', Configuration::get(self::CONFIG_PREFIX.'_LOCALITY')),
                'province' => Tools::getValue('province', Configuration::get(self::CONFIG_PREFIX.'_PROVINCE')),
                'contact' => Tools::getValue('contact', Configuration::get(self::CONFIG_PREFIX.'_CONTACT')),
                'requestor' => Tools::getValue('requestor', Configuration::get(self::CONFIG_PREFIX.'_REQUESTOR')),
                'observations' => Tools::getValue('observations', Configuration::get(self::CONFIG_PREFIX.'_OBSERVATIONS')),
                'failcost' => Tools::getValue('failcost', Configuration::get(self::CONFIG_PREFIX.'_FAILCOST')),
                'timeslot' => Tools::getValue('timeslot', Configuration::get(self::CONFIG_PREFIX.'_TIMESLOT') ? Configuration::get(self::CONFIG_PREFIX.'_TIMESLOT') : 1),
                'boxes' => Tools::getValue('boxes', Configuration::get(self::CONFIG_PREFIX.'_BOXES') ? Configuration::get(self::CONFIG_PREFIX.'_BOXES') : '[]'),
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
        //**/Tools::dieObject($_POST);
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
        Configuration::updateValue(self::CONFIG_PREFIX.'_ENABLE_PICKUP_ORDERS', Tools::getValue('enable_pickup_orders'));
        Configuration::updateValue(self::CONFIG_PREFIX.'_STREET', Tools::getValue('street'));
        Configuration::updateValue(self::CONFIG_PREFIX.'_NUMBER', Tools::getValue('number'));
        Configuration::updateValue(self::CONFIG_PREFIX.'_FLOOR', Tools::getValue('floor'));
        Configuration::updateValue(self::CONFIG_PREFIX.'_APARTMENT', Tools::getValue('apartment'));
        Configuration::updateValue(self::CONFIG_PREFIX.'_LOCALITY', Tools::getValue('locality'));
        Configuration::updateValue(self::CONFIG_PREFIX.'_PROVINCE', Tools::getValue('province'));
        Configuration::updateValue(self::CONFIG_PREFIX.'_CONTACT', Tools::getValue('contact'));
        Configuration::updateValue(self::CONFIG_PREFIX.'_REQUESTOR', Tools::getValue('requestor'));
        Configuration::updateValue(self::CONFIG_PREFIX.'_OBSERVATIONS', Tools::getValue('observations'));
        Configuration::updateValue(self::CONFIG_PREFIX.'_TIMESLOT', Tools::getValue('timeslot'));
        Configuration::updateValue(self::CONFIG_PREFIX.'_BOXES', Tools::jsonEncode($this->boxes));

        return $this->displayConfirmation($this->l('Configuration saved'));
    }

    public function renderOrderGeneratorForm($type)
    {
        $boxes = Tools::jsonDecode(Configuration::get(self::CONFIG_PREFIX.'_BOXES'), true);
        ob_start();  ?>
        <div class="panel">
            <?php  foreach ($boxes as $ind => $box) :  ?>
                <div class="form-group">
                    <h4 style="display: inline-block; margin-right: 16px;"><?php echo $this->l('Box').": {$box['l']}cm×{$box['d']}cm×{$box['h']}cm"; ?></h4>
                    <?php echo $this->l('Quantity'); ?>: <input type="text" name="oca-box-q-<?php echo $ind; ?>" id="oca-box-q-<?php echo $ind; ?>" value="0" class="fixed-width-sm" style="display: inline-block;  margin-right: 16px;">

                    <!--<?php /**/echo $this->l('Weight'); ?>: <input type="text" name="oca-box-w-<?php echo $ind; ?>" id="oca-box-w-<?php echo $ind; ?>" value="0" class="fixed-width-sm" style="display: inline-block;  margin-right: 16px;">
                    <?php echo $this->l('Declared value'); ?>: $<input type="text" name="oca-box-v-<?php echo $ind; ?>" id="oca-box-v-<?php echo $ind; ?>" value="0" class="fixed-width-sm" style="display: inline-block;">-->
                </div>
            <?php  endforeach;  ?>
        </div>
        <?php  $boxBox = ob_get_clean();
        $fields_form = array(
            'form' => array(
                'form' => array(
                    'id_form' => 'oca-form',
                    'legend' => array(
                        'title' => $this->l('OCA Pick-up Order Generator')
                    ),
                    'input' => array(
                        /*array(
                            'type' => 'select',
                            'label' => $this->l('Time slot'),
                            'name' => 'oca-time',
                            'options' => array(
                                'query' => array(
                                    array('value' =>'1', 'text' =>'8-17'),
                                    array('value' =>'2', 'text' =>'8-12'),
                                    array('value' =>'3', 'text' =>'14-17'),
                                ),
                                'id' => 'value',
                                'name' => 'text'
                            ),
                        ),*/
                        /*array(
                            'type' => 'textarea',
                            'label' => $this->l('Observations'),
                            'name' => 'oca-observations',
                            'cols' => '50',
                            'rows' => '2',
                        ),*/
                        array(
                            'type' => 'text',
                            'label' => $this->l('Days for pick-up'),
                            'name' => 'oca-days',
                            'class' => 'fixed-width-lg',
                        ),
                        array(
                            'type' => 'free',
                            'name' => 'boxes',
                            'label' => $this->l('Packaging'),
                            'desc' =>  $this->l('If all boxes remain with 0 quantity, a single package with the total volume and weight of the cart will be used instead')
                        ),
                    ),
                    'submit' => array(
                        'title' => $this->l('Generate OCA Pick-up Order'),
                        'name' => 'oca-order-submit'
                    ),
                )
            ),
        );
        $fields_value = array(
            //'oca-observations' => Tools::getValue('oca-observations', ''),
            'oca-days' => Tools::getValue('oca-days', 1),
            'boxes' => $boxBox,
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

        return $this->guiHeader.$helper->generateForm($fields_form);
    }

    public function hookDisplayAdminOrder($params)
    {
        $op = OcaEpakOperative::getByCarrierId($params['cart']->id_carrier);
        if (!$op)
            return NULL;
        $address = new Address($params['cart']->id_address_delivery);
        $currency = new Currency($params['cart']->id_currency);
        $order = new Order($params['id_order']);
        $customer = new Customer($order->id_customer);
        $cartData = $this->getCartPhysicalData($params['cart']);
        $shipping = $params['cart']->getTotalShippingCost(NULL, FALSE);
        $totalToPay = Tools::ps_round($this->getTotalWithFee($shipping, $op->addfee), 2);
        $paidFee = $totalToPay - $shipping;
        $relay = OcaEpakRelay::getByCartId($order->id_cart);
        try {
            $response = $this->_getSoapClient()->Tarifar_Envio_Corporativo(array(
                'PesoTotal' => $cartData['weight'],
                'VolumenTotal' => $cartData['volume'],
                'CodigoPostalOrigen' => Configuration::get(self::CONFIG_PREFIX.'_POSTCODE'),
                'CodigoPostalDestino' => self::cleanPostcode($address->postcode),
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

        if (Tools::isSubmit('oca-order-submit')) {
            if ($preOrder = $this->_getValidateOcaForm($cartData)) {
                $costCenter = 0;
                $xmlRetiro = OcaEpakOrder::generateOrderXml(array(
                    'address' => $address,
                    'operative' => $op,
                    'order' => $order,
                    'customer' => $customer,
                    'boxes' => $preOrder['boxes'],
                    'cost_center_id' => in_array($op->type, array('PaP', 'PaS')) ? '0' : '1',
                    'imposition_center_id' => $relay->distribution_center_id
                ));
                //**/Tools::dieObject($_POST);
                /**Tools::dieObject([$xmlRetiro, array(
                    'usr' => Configuration::get(self::CONFIG_PREFIX.'_EMAIL'),
                    'psw' => Configuration::get(self::CONFIG_PREFIX.'_PASSWORD'))]);/**/
                try {
                    $response = $this->_getSoapClient()->IngresoOR(array(
                        'usr' => Configuration::get(self::CONFIG_PREFIX.'_EMAIL'),
                        'psw' => Configuration::get(self::CONFIG_PREFIX.'_PASSWORD'),
                        'XML_Retiro' => $xmlRetiro,
                        'ConfirmarRetiro' => 0,
                        'DiasRetiro' => $preOrder['days'],
                        'FranjaHoraria' => Configuration::get(self::CONFIG_PREFIX.'_TIMESLOT'),
                    ));
                    //**/Tools::dieObject($response);
                    /*$response = '<diffgr:diffgram xmlns:msdata="urn:schemas-microsoft-com:xml-msdata" xmlns:diffgr="urn:schemas-microsoft-com:xml-diffgram-v1"><Resultado xmlns=""><Resumen diffgr:id="Resumen1" msdata:rowOrder="0"><CodigoOperacion>2458171</CodigoOperacion><FechaIngreso>2014-08-19T16:41:25.18-03:00</FechaIngreso><mailUsuario>info@abundance-store.com</mailUsuario><origen/><CantidadRegistros>1</CantidadRegistros><CantidadIngresados>1</CantidadIngresados><CantidadRechazados>0</CantidadRechazados></Resumen></Resultado></diffgr:diffgram>';
                    //$xml = new SimpleXMLElement($response->IngresoORResult->any);
                    $response = '<diffgr:diffgram xmlns:msdata="urn:schemas-microsoft-com:xml-msdata" xmlns:diffgr="urn:schemas-microsoft-com:xml-diffgram-v1"/>';*/
                    $xml = new SimpleXMLElement($response->IngresoORResult->any);
                    //**/$xml = new SimpleXMLElement($response);
                    if (!$xml->count())
                        throw new Exception($this->l('Error generating OCA order'));
                    if (count($xml->Errores))
                        throw new Exception($this->l('Error generating OCA order').': '.$xml->Errores->Error->Descripcion);
                    $data = $xml->Resultado->Resumen;
                    //**/Tools::dieObject($xml->count());
                    //**/Tools::dieObject($data);
                    $ocaOrder = new OcaEpakOrder();
                    $ocaOrder->id_order = $order->id;
                    $ocaOrder->reference = $data->CodigoOperacion;
                    $ocaOrder->tracking = '';
                    $ocaOrder->status = $this->l('Added to cart').": {$data->CantidadIngresados}; ".$this->l('Rejected').": {$data->CantidadRechazados}; ";
                    $ocaOrder->save();
                    unset($ocaOrder);
                } catch (Exception $e) {
                    //**/Tools::dieObject($response);
                    $this->_addToHeader($this->displayError($e->getMessage()));
                    //**/Tools::dieObject($e->getMessage());
                    //**/Tools::dieObject('Error with generated xml: '.$xmlRetiro);
                    //Logger::AddLog('Ocaepak: '.$this->l('error getting online price for cart')." {$cart->id}");
                }
            } else
                $this->_addToHeader($this->l('There is an error in the OCA order generator form'));
        }
        if ($ocaOrder = OcaEpakOrder::getByFieldId('id_order', $order->id)) {
            ob_start();
            ?>
            <div>
                <?php echo $this->l('OCA Order Id') . ": {$ocaOrder->reference}"; ?><br />
                <?php echo $this->l('Status') . ": {$ocaOrder->status}"; ?><br />
                <?php echo $this->l('Tracking') . ": {$ocaOrder->tracking}"; ?><br />
                Sticker Button goes here<br />
                Cancel Order Button goes here<br />
            </div>
            <?php
            $form = ob_get_clean();
        } elseif (Configuration::get(self::CONFIG_PREFIX.'_ENABLE_PICKUP_ORDERS') && (in_array($op->type, array('PaS', 'SaS')) && $relay && $relay->distribution_center_id) || in_array($op->type, array('PaP', 'SaP')))
            $form = $this->renderOrderGeneratorForm($op->type);
        elseif (Configuration::get(self::CONFIG_PREFIX.'_ENABLE_PICKUP_ORDERS'))
            $form = $this->l('OCA order generator unavailable for this Prestashop order');
        else
            $form = '';
        //Tools::dieObject($op->type);
        if (in_array($op->type, array('PaS','SaS')) && ($relay)) {
            $distributionCenter = $this->_retrieveOcaBranchData($relay->distribution_center_id);
        } else
            $distributionCenter = false;
        //**/Tools::dieObject($distributionCenter);

        ob_start();
        ?>
        <fieldset class="panel" >
            <legend><img src="../modules/<?php echo self::MODULE_NAME; ?>/logo.gif" alt="logo" /><?php echo $this->l('OCA ePak Information'); ?></legend>
            <div class="form-group">
                <?php echo $this->l('Operative') . ": {$op->reference}"; ?><br />
                <?php echo $this->l('Operative Type') . ": {$op->type} ".($op->insured ? $this->l('Insured') : ''); ?><br />
                <?php echo $this->l('Calculated Order Weight') . ": {$cartData['weight']} kg"; ?><br />
                <?php echo $this->l('Calculated Order Volume (with padding)') . ": {$cartData['volume']} m³"; ?><br />
                <?php if ($paidFee != 0):
                    echo $this->l('Additional fee') . ": {$currency->sign}{$paidFee}";
                endif; ?><br />
                <?php if (count($distributionCenter)): ?>
                    <?php echo $this->l('Delivery branch selected by customer').': "'.trim($distributionCenter['Descripcion'])."\", {$distributionCenter['Calle']} {$distributionCenter['Numero']} {$distributionCenter['Piso']}, {$distributionCenter['Localidad']} {$distributionCenter['codigopostal']}"; ?><br />
                <!--<div class="panel">
                    <div class="panel-heading">

                    </div>
                    <div class="panel-body">
                        <?php /* echo $distributionCenter['Descripcion'];  */?><br/>
                        <?php /* echo "{$distributionCenter['Calle']} {$distributionCenter['Numero']} {$distributionCenter['Piso']}, {$distributionCenter['Localidad']} {$distributionCenter['codigopostal']}";  */?>
                    </div>
                </div>-->
                <?php endif; ?>
                <?php if (is_string($quote)): ?>
                    <div class="warn">
                         <?php  echo $quote;  ?>
                    </div>
                <?php  else: ?>
                    <?php echo $this->l('Live quote') . ": {$currency->sign}{$quote}"; ?><br />
                <?php endif; ?>
            </div>
            <div class="form-group">
                <?php  echo $form;  ?>
            </div>
        </fieldset>
        <?php
        return ob_get_clean();
    }

    public function hookActionCartSave($params) { return NULL; }    ## @todo async price check

    /**
     * Show pickup options
     * @param Array $params ['address']
     */
    public function hookDisplayCarrierList($params)
    {
        //return '<pre>'.print_r($this->getCartPhysicalData($params['cart']), TRUE).'</pre>';
        if (!$this->active OR $params['address']->id_country != Country::getByIso('AR'))
            return FALSE;
        //$ops = array_merge(OcaEpakOperative::getOperativeIds(false, 'type', 'PaS'), OcaEpakOperative::getOperativeIds(false, 'type', 'SaS'));
        $carrierIds = OcaEpakOperative::getRelayedCarrierIds();
        if (count($carrierIds)) {
            try {
                $response = $this->_getSoapClient()->GetCentrosImposicionPorCP(array(
                    'CodigoPostal' => self::cleanPostcode($params['address']->postcode)
                ));
                $xml = new SimpleXMLElement($response->GetCentrosImposicionPorCPResult->any);

                if (!count($xml->children()))
                    return NULL;
                $relays = array();
                foreach ($xml->NewDataSet->children() as $table) {
                    $relays[] = Tools::jsonDecode(str_replace('{}', '""', Tools::jsonEncode((array)$table)), TRUE);
                }
                $this->context->smarty->assign(  array(
                    'ocaepak_relays' => $relays,
                    'relayed_carriers' => Tools::jsonEncode($carrierIds),
                    'ocaepak_name' => $this->name,
                    'ocaepak_selected_relay' => OcaEpakRelay::getByCartId($this->context->cookie->id_cart)->distribution_center_id
                    //'ocaepak_relay_url' => $this->context->link->getModuleLink($this->name, 'relay',$params)
                ) );
                return $this->display(__FILE__, 'displayCarrierList.tpl');
            } catch (Exception $e) {
                //**/Tools::dieObject($e);
                Logger::AddLog('Ocaepak: '.$this->l('error getting pickup centers for cp')." {$params['address']->postcode}");
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
            $response = $this->_getSoapClient()->Tarifar_Envio_Corporativo(array(
                'PesoTotal' => $cartData['weight'],
                'VolumenTotal' => $cartData['volume'],
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
        } catch (Exception $e) {
            Logger::AddLog('Ocaepak: '.$this->l('error getting online price for cart')." {$cart->id}");
            return (float)$this->convertCurrencyFromArs(Configuration::get(self::CONFIG_PREFIX.'_FAILCOST'), $cart->id_currency);
        }
        return (float)Tools::ps_round($shipping_cost+$this->convertCurrencyFromArs($this->getTotalWithFee($data->Precio, $op->addfee), $cart->id_currency), 2);
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
                    (($product['width']+2*self::PADDING)*($product['height']+2*self::PADDING)*($product['depth']+2*self::PADDING))/1000000 :
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

    protected function _getValidateOcaForm($cartData)
    {
        if (is_numeric(Tools::getValue('oca-days', false))) {
            //$cartData = $this->getCartPhysicalData($cart);
            $form = array(
                //'time' => (int)Tools::getValue('oca-time'),
                'days' => (int)Tools::getValue('oca-days', 0),
                //'observations' => trim(Tools::getValue('oca-observations'), ''),
                'boxes' => array()
            );
            $boxes = Tools::jsonDecode(Configuration::get(self::CONFIG_PREFIX.'_BOXES'), true);
            $boxVolume = 0;
            //$boxNum = 0;
            /*while (Tools::getIsset('oca-box-q-'.($boxNum))) {
                if (!is_numeric(Tools::getValue('oca-box-q-'.$boxNum, 0)))
                    return false;
                if (Tools::getValue('oca-box-q-'.$boxNum, 0) == 0)
                    continue;
                $form['boxes'][$boxNum] = array(
                    'l' => number_format((float)Tools::getValue('oca-box-l-'.$boxNum), 2),
                    'd' => number_format((float)Tools::getValue('oca-box-d-'.$boxNum), 2),
                    'h' => number_format((float)Tools::getValue('oca-box-h-'.$boxNum), 2),
                    'w' => number_format((float)Tools::getValue('oca-box-w-'.$boxNum), 2),
                    'v' => number_format((float)Tools::getValue('oca-box-v-'.$boxNum), 2),
                );
                $boxNum++;
            }*/
            foreach ($boxes as $ind => $box) {
                if (!is_numeric(Tools::getValue('oca-box-q-'.$ind, 0)))
                    return false;
                if (Tools::getValue('oca-box-q-'.$ind, 0) <= 0)
                    continue;
                $form['boxes'][] = array(
                    'l' => number_format((float)$box['l'], 0),
                    'd' => number_format((float)$box['d'], 0),
                    'h' => number_format((float)$box['h'], 0),
                    'q' => Tools::getValue('oca-box-q-'.$ind),
                    /*'w' => number_format((float)$box['w'], 2),
                    'v' => number_format((float)Tools::getValue('oca-box-v-'.$boxNum), 2),*/
                );
                $boxVolume = $boxVolume+($box['l']*$box['d']*$box['h'])*Tools::getValue('oca-box-q-'.$ind);
            }
            if (count($form['boxes']) == 0) {
                $side = pow($cartData['volume'], 1/3);
                $form['boxes'][] = array(
                    'l' => number_format($side, 0),
                    'd' => number_format($side, 0),
                    'h' => number_format($side, 0),
                    'w' => number_format((float)$cartData['weight'], 2),
                    'v' => number_format((float)$cartData['cost'], 2),
                    'q' => 1,
                );
            } else {
                foreach ($form['boxes'] as &$box) {      //split cost and weight proportionally
                    $vol = ($box['l']*$box['d']*$box['h']);
                    $volumePercentage = $vol/$boxVolume;
                    $box['v'] = number_format((float)$volumePercentage*$cartData['cost'], 2);
                    $box['w'] = number_format((float)$volumePercentage*$cartData['weight'], 2);
                }
            }
            //**/Tools::dieObject(array($form, $volumePercentage, $vol));
            return $form;
        }

        ### @todo add error display
        return false;
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

    protected function _getCostCenter($operativeReference)
    {
        try {
            $response = $this->_getSoapClient()->GetCentroCostoPorOperativa(array(
                'Cuit' => Configuration::get(self::CONFIG_PREFIX.'_CUIT'),
                'Operativa' => $operativeReference
            ));
            $xml = new SimpleXMLElement($response->DataSet);
            if (!count($xml->children()))
                throw new Exception('No results from OCA webservice');
            $data = $xml->NewDataSet->Table;
        } catch (Exception $e) {
            //Logger::AddLog('Ocaepak: '.$this->l('error getting online price for cart')." {$cart->id}");
            //return (float)$this->convertCurrencyFromArs(Configuration::get(self::CONFIG_PREFIX.'_FAILCOST'), $cart->id_currency);
        }
    }

    protected function _retrieveOcaBranchData($id_branch)
    {
        try {
            $response = $this->_getSoapClient()->GetCentrosImposicion(array());
            $xml = new SimpleXMLElement($response->GetCentrosImposicionResult->any);

            if (!count($xml->children()))
                return NULL;
            //$relays = array();
            //**/Tools::dieObject($xml);
            foreach ($xml->NewDataSet->children() as $table) {
                //**/Tools::dieObject([$id_branch, (int)$table->idCentroImposicion]);
                if (trim((string)$table->idCentroImposicion) == $id_branch)
                    return Tools::jsonDecode(str_replace('{}', '""', Tools::jsonEncode((array)$table)), TRUE);
            }
        } catch (Exception $e) {
            //**/Tools::dieObject($e);
            Logger::AddLog('Ocaepak: '.$this->l('error getting pickup centers'));
            //return FALSE;
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

    /**
     * @param $address Address
     */
    protected function _parseOcaAddress($address)
    {
        $other = Tools::strlen($address->other) ? '('.$address->other.')' : '';
        $fullAddress =  trim(str_replace(array("\n", "\r"), ' ', ($address->address1.' '.$address->address2.' '.$other)), "\t\n\r");
        $matches = array();
        if (preg_match('/^(\d*\D+)$/x', $fullAddress, $matches)) {      //if no numbers after street
            $ocaAddress = array(
                'street' => $this->_cleanOcaAttribute($matches[1], self::OCA_STREET_LENGTH),
                'number' => 0,
                'observations' => $this->_cleanOcaAttribute($fullAddress, self::OCA_OBSERVATIONS_LENGTH, true)
            );
        } elseif (preg_match('/^(\d+)[-\/]*(\d+)$/', $fullAddress, $matches)) {      //if 2 numbers
            $ocaAddress = array(
                'street' => $this->_cleanOcaAttribute($matches[1], self::OCA_STREET_LENGTH),
                'number' => $this->_cleanOcaAttribute($matches[2], self::OCA_STREET_LENGTH),
                'observations' => $this->_cleanOcaAttribute($fullAddress, self::OCA_OBSERVATIONS_LENGTH, true)
            );
        } elseif (preg_match('/^(\d*[^0-9]+)(\d+)(\D+)/', $fullAddress, $matches)) {
            $ocaAddress = array(
                'street' => $this->_cleanOcaAttribute($matches[1], self::OCA_STREET_LENGTH),
                'number' => $this->_cleanOcaAttribute($matches[2], self::OCA_STREET_LENGTH),
                'observations' => $this->_cleanOcaAttribute($fullAddress, self::OCA_OBSERVATIONS_LENGTH, true)
            );
        } else
            throw new Exception('Unable to parse address');

        return $ocaAddress;
    }

}