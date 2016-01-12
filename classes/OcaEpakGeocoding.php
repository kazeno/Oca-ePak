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

class OcaEpakGeocoding extends ObjectModel
{
    const GEOCODER_URL = 'http://maps.googleapis.com/maps/api/geocode/json?region=ar&address=';
    const SERVICE_ADMISSION = 1;
    const SERVICE_DELIVERY = 2;

    public $description;
    public $street;
    public $number;
    public $locality;
    public $province;
    public $latitude;
    public $longitude;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => OcaEpak::GEOCODES_TABLE,
        'primary' => OcaEpak::GEOCODES_ID,
        'fields' => array(
            'description' => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true),
            'street' => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true),
            'number' => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true),
            'locality' => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true),
            'province' => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true),
            'latitude' => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true),
            'longitude' => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true),
        )
    );

    public static function retrieveAll()
    {
        ob_start(); ?>
            SELECT * FROM `<?php echo pSQL(_DB_PREFIX_.OcaEpak::GEOCODES_TABLE);?>`
        <?php $query = ob_get_clean();
        return Db::getInstance()->executeS($query);
    }

    public static function getCoordinateTree()
    {
        $entries = self::retrieveAll();
        return self::treeBuilder($entries, array('province', 'locality', 'street', 'number', 'latitude', 'longitude'));
    }

    public static function getOcaBranchCoordinates($ocaBranch, $coordTree)
    {
        if (
            isset($coordTree[$ocaBranch['Provincia']])
            && isset($coordTree[$ocaBranch['Provincia']][$ocaBranch['Localidad']])
            && isset($coordTree[$ocaBranch['Provincia']][$ocaBranch['Localidad']][$ocaBranch['Calle']])
            && isset($coordTree[$ocaBranch['Provincia']][$ocaBranch['Localidad']][$ocaBranch['Calle']][(!is_array($ocaBranch['Numero']) ? $ocaBranch['Numero'] : ' ')])
        ) {
            return $coordTree[$ocaBranch['Provincia']][$ocaBranch['Localidad']][$ocaBranch['Calle']][(!is_array($ocaBranch['Numero']) ? $ocaBranch['Numero'] : ' ')];
        }
        if (isset($ocaBranch['Latitud']) && isset($ocaBranch['Longitud']) && Tools::strlen(trim($ocaBranch['Latitud'])) && Tools::strlen(trim($ocaBranch['Longitud']))) {
            $lat = trim($ocaBranch['Latitud']);
            $lng = trim($ocaBranch['Longitud']);
        } else {
            if (!($json_string = self::baseRequest(self::joinAddressString($ocaBranch))))
                return false;
            $json = Tools::jsonDecode($json_string, true);
            if (!count($json['results']) || !isset($json['results'][0]['geometry']))
                return false;
            $lat = (string)$json['results'][0]['geometry']['location']['lat'];
            $lng = (string)$json['results'][0]['geometry']['location']['lng'];
        }
        $_POST['forceIDs'] = 1;     //Force to save ids on PS < 1.6
        $prev = new OcaEpakGeocoding($ocaBranch['IdCentroImposicion']);
        $og = new OcaEpakGeocoding();
        $og->force_id = true;
        $og->id = $ocaBranch['IdCentroImposicion'];
        $og->description = $ocaBranch['Sucursal'];
        $og->street = $ocaBranch['Calle'];
        $og->number = (!is_array($ocaBranch['Numero']) && Tools::strlen($ocaBranch['Numero'])) ? $ocaBranch['Numero'] : ' ';
        $og->locality = $ocaBranch['Localidad'];
        $og->province = $ocaBranch['Provincia'];
        $og->latitude = $lat;
        $og->longitude = $lng;
        $prev->id ? $og->update() : $og->add();
        usleep(50000);  //Slow down API requests to avoid hitting rate limiter
        return array($lat, $lng);

    }

    public static function clear()
    {
        ob_start(); ?>
        DELETE FROM `<?php echo pSQL(_DB_PREFIX_.OcaEpak::GEOCODES_TABLE);?>` WHERE 1
        <?php $query = ob_get_clean();
        return Db::getInstance()->execute($query);
    }


    /**
     * @param Array $array ['keyN' => 'valueN', ...]
     * @param Array $order ['key1', 'key2' ...]
     */
    protected static function treeBuilder($array, $order)
    {
        $tree = array();
        foreach ($array as $row) {
            $current =& $tree;
            foreach ($order as $index => $key) {
                if (count($order) == ($index+2)) {
                    $current = array($row[$key], $row[$order[$index+1]]);
                    break;
                }
                if (!isset($current[$row[$key]]))
                    $current[$row[$key]] = array();
                $current =& $current[$row[$key]];
            }
        }
        return $tree;
    }

    protected static function baseRequest($address)
    {
        $url = parse_url(self::GEOCODER_URL . urlencode($address));
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
            return $response[1];
        } else return FALSE;
    }

    protected static function joinAddressString($ocaBranch)
    {
        return $ocaBranch['Calle'].' '.$ocaBranch['Numero'].' '.$ocaBranch['Localidad'].' '.$ocaBranch['Provincia'].' Argentina';
    }
}