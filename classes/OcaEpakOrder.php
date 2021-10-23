<?php
/**
 * Oca e-Pak Module for Prestashop  https://github.com/kazeno/Oca-ePak
 *
 * @author    Rinku Kazeno
 * @license   MIT License  https://opensource.org/licenses/mit-license.php
 * @file-version 1.5
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
    public $operation_code;
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
            'operation_code' => array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => true),
            'tracking' => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => FALSE),
            //'id_shop' => array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => true),
        )
    );

    /**
     * @throws PrestaShopException
     * @throws PrestaShopDatabaseException
     */
    public static function getByFieldId($field, $id_field)
    {
        if (!in_array(
                $field,
                array('id_order', 'reference', 'tracking', 'operation_code', /*'status'*/)
        )) {
            return false;
        }
        $query = OcaCarrierTools::interpolateSql(
            "SELECT `{ID}`
            FROM `{TABLE}`
            WHERE `{FIELD}` = '{IDFIELD}'
            ORDER BY `{FIELD}` DESC",
            array(
                '{TABLE}' => _DB_PREFIX_.OcaEpak::ORDERS_TABLE,
                '{ID}' => OcaEpak::ORDERS_ID,
                '{FIELD}' => $field,
                '{IDFIELD}' => $id_field,
            )
        );
        $id = Db::getInstance()->getValue($query);
        return $id ? new OcaEpakOrder($id) : false;
    }
    

    public static function generateOrderXml($data)
    {
        $costCenter = isset($data['cost_center_id']) ? (string)$data['cost_center_id'] : '0';
        $idci = isset($data['imposition_center_id']) ? (string)$data['imposition_center_id'] : '0';
        $config = array();
        $fields = array(
            'account', 'street', 'number', 'floor', 'apartment', 'postcode', 'locality', 'province', 'contact', 'email', 'requestor', 'observations'
        );
        $admissionFields = array(
            'account', 'postcode'
        );
        foreach ($fields as $conf) {
            $config[$conf] = (Configuration::get(OcaEpak::CONFIG_PREFIX.'PICKUPS_ENABLED') || in_array($conf, $admissionFields)) ? self::cleanOcaAttribute(Configuration::get(OcaEpak::CONFIG_PREFIX.Tools::strtoupper($conf)), constant('self::OCA_'.Tools::strtoupper($conf).'_LENGTH')) : '';
        }
        $config['timeslot'] = (
            in_array($data['operative']->type, array('PaP', 'PaS'))
            ? Configuration::get(OcaEpak::CONFIG_PREFIX.'TIMESLOT')
            : '1'
        );
        $address = array();
        foreach (
            array('street', 'number', 'floor', 'apartment', 'locality', 'province', 'observations') as $conf
        ) {
            $address[$conf] = self::cleanOcaAttribute($data[$conf], constant('self::OCA_'.Tools::strtoupper($conf).'_LENGTH'));
        }
        $address['firstname'] = self::cleanOcaAttribute($data['customer']->firstname, self::OCA_NAME_LENGTH);
        $address['lastname'] = self::cleanOcaAttribute($data['customer']->lastname, self::OCA_NAME_LENGTH);
        $address['email'] = self::cleanOcaAttribute($data['customer']->email, self::OCA_EMAIL_LENGTH);
        $address['phone'] = self::cleanOcaAttribute($data['address']->phone, self::OCA_PHONE_LENGTH);
        $address['mobile'] = self::cleanOcaAttribute($data['address']->phone_mobile, self::OCA_MOBILE_LENGTH);
        $reference = self::cleanOcaAttribute((Tools::strlen(trim($data['address']->dni)) ? $data['address']->dni : $data['order']->reference), self::OCA_REMIT_LENGTH);

        ob_start();
        ?>
        <ROWS>
            <cabecera ver="2.0" nrocuenta="<?php  echo $config['account'];  ?>"/>
            <origenes>
                <origen calle="<?php  echo $config['street'];  ?>" nro="<?php  echo $config['number'];  ?>" piso="<?php  echo $config['floor'];  ?>" depto="<?php  echo $config['apartment'];  ?>" cp="<?php  echo $config['postcode'];  ?>" localidad="<?php  echo $config['locality'];  ?>" provincia="<?php  echo $config['province'];  ?>" contacto="<?php  echo $config['contact'];  ?>" email="<?php  echo $config['email'];  ?>" solicitante="<?php  echo $config['requestor'];  ?>" observaciones="<?php  echo $config['observations'];  ?>" centrocosto="<?php  echo $costCenter;  ?>" idfranjahoraria="<?php  echo $config['timeslot'];  ?>" <?php  if ($data['origin_imposition_center_id']):  ?>idcentroimposicionorigen="<?php  echo $data['origin_imposition_center_id'];  ?>" <?php  endif;  ?>fecha="<?php  echo $data['date'];  ?>">
                    <envios>
                        <envio idoperativa="<?php  echo self::cleanOcaAttribute($data['operative']->reference, self::OCA_OPERATIVE_LENGTH);  ?>" nroremito="<?php  echo $reference;  ?>">
                            <destinatario apellido="<?php  echo $address['lastname'];  ?>" nombre="<?php  echo $address['firstname'];  ?>" calle="<?php  echo $address['street'];  ?>" nro="<?php  echo $address['number'];  ?>" piso="<?php  echo $address['floor'];  ?>" depto="<?php  echo $address['apartment'];  ?>" localidad="<?php  echo $address['locality'];  ?>" provincia="<?php  echo $address['province'];  ?>" cp="<?php  echo $data['postcode'];  ?>" telefono="<?php  echo $address['phone'];  ?>" email="<?php  echo $address['email'];  ?>" idci="<?php  echo $idci;  ?>" celular="<?php  echo $address['mobile'];  ?>" observaciones="<?php  echo $address['observations'];  ?>"/>
                            <paquetes><?php  foreach ($data['boxes'] as $box) :  ?>
                                <paquete alto="<?php  echo self::cleanOcaAttribute($box['h'], self::OCA_ATTR_LENGTH);  ?>" ancho="<?php  echo self::cleanOcaAttribute($box['d'], self::OCA_ATTR_LENGTH);  ?>" largo="<?php  echo self::cleanOcaAttribute($box['l'], self::OCA_ATTR_LENGTH);  ?>" peso="<?php  echo self::cleanOcaAttribute($box['w'], self::OCA_ATTR_LENGTH);  ?>" valor="<?php  echo self::cleanOcaAttribute($box['v'], self::OCA_ATTR_LENGTH);  ?>" cant="<?php  echo self::cleanOcaAttribute($box['q'], self::OCA_ATTR_LENGTH);  ?>" />
                            <?php  endforeach;  ?></paquetes>
                        </envio>
                    </envios>
                </origen>
            </origenes>
        </ROWS>
        <?php
        return '<?xml version="1.0" encoding="iso-8859-1" standalone="yes"?>'.ob_get_clean();
    }


    public static function cleanOcaAttribute($text, $maxLength, $fromEnd = false)
    {
        $clean = trim(htmlspecialchars(iconv('utf-8','ascii//TRANSLIT', str_replace('"', '', $text))));
        if (strpos($clean, '?') !== false) {
            @setlocale(LC_TIME, 'es_ES');
            $clean = trim(htmlspecialchars(iconv('utf-8','ascii//TRANSLIT', str_replace('"', '', $text))));
        }

        if ($fromEnd) {
            return Tools::strlen($clean) > $maxLength ? Tools::substr($clean, -$maxLength) : $clean;
        } else {
            return Tools::strlen($clean) > $maxLength ? Tools::substr($clean, 0, $maxLength) : $clean;
        }
    }

    /**
     * @param $address Address
     *
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function parseOcaAddress($address)
    {
        $remainingAddress =  trim(str_replace(array("\n", "\r"), ' ', trim($address->address1.' '.$address->address2)), "\t\n\r");
        $shortAddress = trim($address->address1).' '.trim($address->city);
        $mediumAddress = trim($address->address1).', '.(Tools::strlen(trim($address->address1)) ? trim($address->address1).', ' : '').trim($address->city);
        $ocaAddress = array(
            'floor' => '',
            'apartment' => '',
            'other' => $address->other,
            'geocoded' => false
        );

        $remainingAddress = str_replace(array('Nº', 'Nª', 'N°', 'nº', 'nª', 'n°'), '', $remainingAddress);
        if (preg_match('/,?\s+(piso)\s*(\d+)(o|º|°|ª)?/i', trim($remainingAddress), $matches)) {
            $ocaAddress['floor'] = $matches[2];
            $remainingAddress = str_replace($matches[0], '', $remainingAddress);
        } elseif (preg_match('/,?\s+((\d+)(o|er|ero|do|to|º|°)|(primer|segundo|tercero|cuarto|quinto|sexto))\.?\s+(piso)/i', trim($remainingAddress), $matches)) {
            $floors = array(
                'primer' => 1,
                'segundo' => 2,
                'tercero' => 3,
                'cuarto' => 4,
                'quinto' => 5,
                'sexto' => 6
            );
            if (!Tools::strlen($matches[2])) {
                $ocaAddress['floor'] = $floors[$matches[1]];
            } else {
                $ocaAddress['floor'] = $matches[2];
            }
            $remainingAddress = str_replace($matches[0], '', $remainingAddress);
        }
        if (preg_match('/,?\s+(departamento|depto|dpto|dto)\.?\s*((\d+)(o|º|°|ª)?|"?[a-z]"?)/i', trim($remainingAddress), $matches)) {
            $ocaAddress['apartment'] = $matches[2];
            $remainingAddress = str_replace($matches[0], '', $remainingAddress);
        }

        $fullPostCode = '';
        if ($address->id_state) {
            $state = new State($address->id_state);
            $stateCode = trim($state->iso_code);
            if (Tools::strlen($stateCode) === 1) {
                $fullPostCode = $stateCode . OcaCarrierTools::cleanPostcode($address->postcode);
            }
        }
        $json = self::geocodeAddress($shortAddress, $fullPostCode);
        if (!is_array($json) || !count($json['results']) || !isset($json['results'][0]['address_components'])) {
            $json = self::geocodeAddress($mediumAddress, $fullPostCode);
        }

        if (count($json['results']) && isset($json['results'][0]) && isset($json['results'][0]['address_components'])) {
            foreach ($json['results'][0]['address_components'] as $component) {
                if (!isset($component['types']) || !count($component['types'])) {
                    continue;
                }
                switch ($component['types'][0]) {
                    case 'street_number':
                        $ocaAddress['geocoded number'] = $component['long_name'];
                        $ocaAddress['geocoded'] = true;
                        break;
                    case 'route':
                        $ocaAddress['geocoded street'] = $component['long_name'];
                        $ocaAddress['geocoded'] = true;
                        break;
                    case 'locality':
                        $ocaAddress['geocoded city'] = $component['long_name'];
                        $ocaAddress['geocoded'] = true;
                        break;
                    case 'administrative_area_level_1':
                        $ocaAddress['geocoded state'] = $component['long_name'];
                        //$ocaAddress['geocoded'] = true;
                        break;
                    case 'country':
                        if (trim($component['long_name']) != 'Argentina') {
                            $ocaAddress['geocoded country'] = $component['long_name'];
                        }
                        break;
                }
            }
            if (isset($ocaAddress['geocoded country'])) {   //not Argentina
                $ocaAddress = array(
                    'floor' => $ocaAddress['floor'],
                    'apartment' => $ocaAddress['apartment'],
                    'other' => $ocaAddress['other'],
                    'geocoded' => true
                );
            }
        } else {
            $ocaAddress['geocoded'] = false;
        }
        $matches = array();
        if (preg_match(
            '/^((\d*)(\s*[-\/]\s*)?([^0-9,]+)?)(\d+)?\s*,?-?\s*(.*)?/',
            trim($remainingAddress),
            $matches)
        ) {
            $ocaAddress['street'] = Tools::strlen(trim($matches[3])) ? $matches[2] : $matches[1];
            $ocaAddress['number'] = Tools::strlen(trim($matches[5])) ? trim($matches[5]) : '0';
            $ocaAddress['remainder'] = (isset($matches[6]) && Tools::strlen(trim($matches[6]))) ? $matches[6] : '';
        } else {
            $ocaAddress['remainder'] = $remainingAddress;
        }
        if (
            Tools::strlen($ocaAddress['remainder'])
            && preg_match(
                '/^((\d*)(o|mo|no|vo|to|do|er|ero|ro|º|°|ª)?\.?(\z|\s+))?(\d*"?[a-z]?"?(\z|\s+))?(.*)?/i',
                trim($ocaAddress['remainder']), $matches
            )
        ) {
            if (!Tools::strlen($ocaAddress['floor']) && Tools::strlen(trim($matches[2]))) {
                $ocaAddress['floor'] = $matches[2];
            }
            if (!Tools::strlen($ocaAddress['apartment']) && Tools::strlen(trim($matches[5]))) {
                $ocaAddress['apartment'] = $matches[5];
            }
            if (Tools::strlen(trim($matches[5])) || Tools::strlen(trim($matches[2]))) {
                $ocaAddress['remainder'] = (isset($matches[7]) && Tools::strlen(trim($matches[7]))) ? $matches[7] : '';
            }
        }

        if (isset($ocaAddress['street']) && isset($ocaAddress['geocoded street'])) {
            $ocaAddress['discrepancy street'] = self::stringDistance($ocaAddress['street'],
                $ocaAddress['geocoded street']);
        }
        if (isset($ocaAddress['number']) && isset($ocaAddress['geocoded number'])) {
            $ocaAddress['discrepancy number'] = self::stringDistance($ocaAddress['number'],
                $ocaAddress['geocoded number']);
        }
        if (isset($ocaAddress['city'])) {
            $ocaAddress['discrepancy city'] = self::stringDistance($address->city, $ocaAddress['geocoded city']);
        }

        if (
            (isset($ocaAddress['discrepancy number']) && $ocaAddress['discrepancy number'])
            || (isset($ocaAddress['discrepancy street']) && ($ocaAddress['discrepancy street'] > 12))
            || (isset($ocaAddress['discrepancy city']) && ($ocaAddress['discrepancy city'] > 4))
            || Tools::strlen(trim($ocaAddress['remainder']))
        ) {
            $ocaAddress['discrepancy'] = true;
            $ocaAddress['other'] = (Tools::strlen(trim($ocaAddress['remainder'])) ? trim($ocaAddress['remainder']).' ' : '').$ocaAddress['other'];
        } else {
            $ocaAddress['discrepancy'] = false;
        }
        $ocaAddress['city'] = $address->city;
        $ocaAddress['state'] = $address->id_state > 0 ? State::getNameById($address->id_state) : '';
        
        return $ocaAddress;
    }

    public static function geocodeAddress($address, $fullPostcode = '')
    {
        $postcodeParams = $fullPostcode ? ('|postal_code:'.$fullPostcode) : '';
        $url = parse_url('http://maps.googleapis.com/maps/api/geocode/json?region=ar&key='.Configuration::get(OcaEpak::CONFIG_PREFIX.'GMAPS_API_KEY').'&language=es&address=' . urlencode($address) . '&components=country:AR'.$postcodeParams);
        $query = isset($url['query']) ? "?{$url['query']}" : '';
        if ($fp = fsockopen('tls://'.$url['host'], 443)) {
            fwrite($fp, "GET {$url['path']}{$query} HTTP/1.0\r\n");
            fwrite($fp, "Host: {$url['host']}\r\n");
            fwrite($fp, "Accept: application/json\r\n");
            fwrite($fp, "Connection: close\r\n\r\n");
            $result = '';
            while(!feof($fp)) {
                $result .= fgets($fp);
            }
            fclose($fp);
            $response = explode("\r\n\r\n", trim($result));
            if ($data = @Tools::jsonDecode($response[1], true)) {
                return $data;
            } else {
                return $response[1];
            }
        } else {
            return false;
        }
    }
    

    public static function stringDistance($str1, $str2)
    {
        return levenshtein(
            trim(Tools::strtolower(Tools::replaceAccentedChars($str1))),
            trim(Tools::strtolower(Tools::replaceAccentedChars($str2))),
            1,
            3,
            4
        );
    }
}