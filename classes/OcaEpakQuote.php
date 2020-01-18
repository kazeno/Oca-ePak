<?php
/**
 * Oca e-Pak Module for Prestashop  https://github.com/kazeno/Oca-ePak
 *
 * @author    Rinku Kazeno
 * @license   MIT License  https://opensource.org/licenses/mit-license.php
 * @file-version 1.2
 */

class OcaEpakQuote
{
    public static $expiry = 8; //hours
    public static $volumePrecision = 6; //decimal places

    public static function retrieve($reference, $postcode, $origin, $volume, $weight, $value)
    {
        $query = OcaCarrierTools::interpolateSql(
            "SELECT `price`
            FROM `{TABLE}`
            WHERE reference = '{REFERENCE}'
            AND postcode = '{POSTCODE}'
            AND origin = '{ORIGIN}'
            AND ABS(volume - '{VOLUME}') < 0.000001
            AND ABS(weight - '{WEIGHT}') < 0.000001
            AND ABS(`value` - '{VALUE}') < 1
            AND `date` > DATE_SUB(NOW(), INTERVAL {EXPIRY} HOUR)",
            array(
                '{TABLE}' => _DB_PREFIX_.OcaEpak::QUOTES_TABLE,
                '{REFERENCE}' => $reference,
                '{POSTCODE}' => $postcode,
                '{ORIGIN}' => $origin,
                '{VOLUME}' => round($volume, self::$volumePrecision),
                '{WEIGHT}' => $weight,
                '{VALUE}' => $value,
                '{EXPIRY}' => self::$expiry,
            )
        );
        return Db::getInstance()->getValue($query);
    }

    public static function insert($reference, $postcode, $origin, $volume, $weight, $value, $price)
    {
        $query = OcaCarrierTools::interpolateSql(
            "REPLACE INTO `{TABLE}`
            (reference, postcode, origin, volume, weight, `value`, price, `date`)
            VALUES
            ('{REFERENCE}',
            '{POSTCODE}',
            '{ORIGIN}',
            '{VOLUME}',
            '{WEIGHT}',
            '{VALUE}',
            '{PRICE}',
            NOW())",
            array(
                '{TABLE}' => _DB_PREFIX_.OcaEpak::QUOTES_TABLE,
                '{REFERENCE}' => $reference,
                '{POSTCODE}' => $postcode,
                '{ORIGIN}' => $origin,
                '{VOLUME}' => round($volume, self::$volumePrecision),
                '{WEIGHT}' => $weight,
                '{VALUE}' => $value,
                '{PRICE}' => $price,
            )
        );
        return Db::getInstance()->execute($query);
    }

    public static function clear()
    {
        $query = OcaCarrierTools::interpolateSql(
            "DELETE FROM `{TABLE}` WHERE 1",
            array(
                '{TABLE}' => _DB_PREFIX_.OcaEpak::QUOTES_TABLE,
            )
        );
        return Db::getInstance()->execute($query);
    }
}