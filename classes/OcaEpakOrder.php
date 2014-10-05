<?php
/**
 * Oca e-Pak Module for Prestashop
 *
 *  @author Rinku Kazeno <development@kazeno.co>
 *  @file-version 1.1
 */

class OcaEpakOrder extends ObjectModel
{
    const OCA_ACCOUNT_LENGTH = 10;
    const OCA_NAME_LENGTH = 30;
    const OCA_STREET_LENGTH = 30;
    const OCA_NUMBER_LENGTH = 5;
    const OCA_FLOOR_LENGTH = 6;
    const OCA_APARTMENT_LENGTH = 4;
    const OCA_POSTCODE_LENGTH = 4;
    const OCA_LOCALITY_LENGTH = 30;
    const OCA_PROVINCE_LENGTH = 30;
    const OCA_CONTACT_LENGTH = 30;
    const OCA_EMAIL_LENGTH = 100;
    const OCA_REQUESTOR_LENGTH = 30;
    const OCA_PHONE_LENGTH = 30;
    const OCA_MOBILE_LENGTH = 15;
    const OCA_OBSERVATIONS_LENGTH = 100;
    const OCA_OPERATIVE_LENGTH = 6;
    const OCA_REMIT_LENGTH = 30;
    const OCA_ATTR_LENGTH = 11;

    public $id_order;
    public $reference;
    public $status;
    public $tracking;
    //public $id_shop;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => OcaEpak::ORDERS_TABLE,
        'primary' => OcaEpak::ORDERS_ID,
        'multishop' => false,
        'fields' => array(
            'id_order' =>	array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => true),
            'reference' =>	array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => TRUE),
            'status' => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => false),
            'tracking' => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => FALSE),
            //'id_shop' => array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => true),
        )
    );

    public static function getByFieldId($field, $id_field)
    {
        if (!in_array($field, array('id_order', 'reference', 'tracking', /*'status', 'id_shop'*/)))
            return false;
        ob_start(); ?>
            SELECT `<?php echo OcaEpak::ORDERS_ID; ?>`
            FROM `<?php echo _DB_PREFIX_.OcaEpak::ORDERS_TABLE;?>`
            WHERE `<?php echo $field; ?>` = '<?php echo (int)$id_field; ?>'
            ORDER BY `<?php echo $field; ?>` DESC
        <?php $query = ob_get_clean();
        $id = Db::getInstance()->getValue($query);
        return $id ? new OcaEpakOrder($id) : false;
    }

    /**
     * @param array $data [address, operative, order, customer, boxes]
     * @param bool $testmode
     *
     * @return string
     */
    public static function generateOrderXml($data, $testmode=false)
    {
        $config = array();
        foreach (array(
            'account', 'street', 'number', 'floor', 'apartment', 'postcode', 'locality', 'province', 'contact', 'email', 'requestor', 'observations'
        ) as $conf) {
            $config[$conf] = self::cleanOcaAttribute(Configuration::get(OcaEpak::CONFIG_PREFIX.'_'.strtoupper($conf)), constant('self::OCA_'.strtoupper($conf).'_LENGTH'));
        }
        $address = self::parseOcaAddress($data['address']);

        ob_start();
        ?>
        <ROWS>
            <cabecera ver="1.0" nrocuenta="<?php  echo $config['account'];  ?>" />
            <retiro calle="<?php  echo $config['street'];  ?>" nro="<?php  echo $config['number'];  ?>" piso="<?php  echo $config['floor'];  ?>" depto="<?php  echo $config['apartment'];  ?>" cp="<?php  echo $config['postcode'];  ?>" localidad="<?php  echo $config['locality'];  ?>" provincia="<?php  echo $config['province'];  ?>" contacto="<?php  echo $config['contact'];  ?>" email="<?php  echo $config['email'];  ?>" solicitante="<?php  echo $config['requestor'];  ?>" observaciones="<?php  echo $config['observations'];  ?>" centrocosto="0" />
            <envios>
                <envio idoperativa="<?php  echo self::cleanOcaAttribute($data['operative']->reference, self::OCA_OPERATIVE_LENGTH);  ?>" nroremito="<?php  echo self::cleanOcaAttribute($data['order']->id, self::OCA_REMIT_LENGTH);  ?>">
                    <destinatario apellido="<?php  echo $address['lastname'];  ?>" nombre="<?php  echo $address['firstname'];  ?>" calle="<?php  echo $address['street'];  ?>" nro="<?php  echo $address['number'];  ?>" piso="-" depto="-" cp="<?php  echo $address['postcode'];  ?>" localidad="<?php  echo $address['city'];  ?>" provincia="<?php  echo $address['state'];  ?>" telefono="<?php  echo $address['phone'];  ?>" email="<?php  echo self::cleanOcaAttribute($data['customer']->email, self::OCA_EMAIL_LENGTH);  ?>" idci="0" celular="<?php  echo $address['mobile'];  ?>" observaciones="<?php  echo $address['observations'];  ?>" />
                    <paquetes>
                        <?php  foreach ($data['boxes'] as $box) :  ?>
                            <paquete alto="<?php  echo self::cleanOcaAttribute($box['h'], self::OCA_ATTR_LENGTH);  ?>" ancho="<?php  echo self::cleanOcaAttribute($box['d'], self::OCA_ATTR_LENGTH);  ?>" largo="<?php  echo self::cleanOcaAttribute($box['l'], self::OCA_ATTR_LENGTH);  ?>" peso="<?php  echo self::cleanOcaAttribute($box['w'], self::OCA_ATTR_LENGTH);  ?>" valor="<?php  echo self::cleanOcaAttribute($box['v'], self::OCA_ATTR_LENGTH);  ?>" cant="<?php  echo self::cleanOcaAttribute($box['q'], self::OCA_ATTR_LENGTH);  ?>" />
                        <?php  endforeach;  ?>
                    </paquetes>
                </envio>
            </envios>
        </ROWS>
        <?php
        return str_replace('> <', '><', $testmode ? '<?xml version="1.0"?>'.ob_get_clean() : preg_replace('~\s+~',' ','<?xml version="1.0"?>'.ob_get_clean()));
    }


    public static function cleanOcaAttribute($text, $maxLength, $fromEnd = false)
    {
        $clean = trim(htmlspecialchars(iconv('utf-8','ascii//TRANSLIT', $text)));
        if (strpos($clean, '?') !== false) {
            @setlocale(LC_TIME, 'es_ES');
            $clean = trim(htmlspecialchars(iconv('utf-8','ascii//TRANSLIT', $text)));
        }

        if ($fromEnd)
            return strlen($clean) > $maxLength ? substr($clean, -$maxLength) : $clean;
        else
            return strlen($clean) > $maxLength ? substr($clean, 0, $maxLength) : $clean;
    }

    /**
     * @param $address Address
     *
     * @return array
     */
    public static  function parseOcaAddress($address)
    {
        $other = strlen($address->other) ? '('.$address->other.')' : '';
        $fullAddress =  trim(str_replace(array("\n", "\r"), ' ', ($address->address1.' '.$address->address2.' '.$other)), "\t\n\r");
        $matches = array();
        if (preg_match('/^(\d*\D+)$/x', $fullAddress, $matches)) {      //if no numbers after street
            $ocaAddress = array(
                'street' => self::cleanOcaAttribute($matches[1], self::OCA_STREET_LENGTH),
                'number' => 0,
                'observations' => self::cleanOcaAttribute($fullAddress, self::OCA_OBSERVATIONS_LENGTH, true)
            );
        } elseif (preg_match('/^(\d+)[-\/]*(\d+)$/', $fullAddress, $matches)) {      //if 2 numbers
            $ocaAddress = array(
                'street' => self::cleanOcaAttribute($matches[1], self::OCA_STREET_LENGTH),
                'number' => self::cleanOcaAttribute($matches[2], self::OCA_STREET_LENGTH),
                'observations' => self::cleanOcaAttribute($fullAddress, self::OCA_OBSERVATIONS_LENGTH, true)
            );
        } elseif (preg_match('/^(\d*[^0-9]+)(\d+)(\D+)/', $fullAddress, $matches)) {
            $ocaAddress = array(
                'street' => self::cleanOcaAttribute($matches[1], self::OCA_STREET_LENGTH),
                'number' => self::cleanOcaAttribute($matches[2], self::OCA_STREET_LENGTH),
                'observations' => self::cleanOcaAttribute($fullAddress, self::OCA_OBSERVATIONS_LENGTH, true)
            );
        } else
            throw new Exception('Unable to parse address');

        $ocaAddress['firstname'] = self::cleanOcaAttribute($address->firstname, self::OCA_NAME_LENGTH);
        $ocaAddress['lastname'] = self::cleanOcaAttribute($address->lastname, self::OCA_NAME_LENGTH);
        $ocaAddress['postcode'] = OcaEpak::cleanPostcode($address->postcode);
        $ocaAddress['city'] = self::cleanOcaAttribute($address->city, self::OCA_LOCALITY_LENGTH);
        $ocaAddress['state'] = self::cleanOcaAttribute(($address->id_state > 0 ? State::getNameById($address->id_state) : ''), self::OCA_PROVINCE_LENGTH);
        $ocaAddress['phone'] = self::cleanOcaAttribute($address->phone, self::OCA_PHONE_LENGTH);
        $ocaAddress['mobile'] = self::cleanOcaAttribute($address->phone_mobile, self::OCA_MOBILE_LENGTH);
        return $ocaAddress;
    }
}