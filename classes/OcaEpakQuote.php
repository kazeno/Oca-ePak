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
 *  @file-version 1.2
 */

class OcaEpakQuote
{
    public static $expiry = 8; //hours
    public static $volumePrecision = 6; //decimal places

    public static function retrieve($reference, $postcode, $origin, $volume, $weight, $value)
    {
        $query = KznCarrier::interpolateSql(
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
        $query = KznCarrier::interpolateSql(
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
        $query = KznCarrier::interpolateSql(
            "DELETE FROM `{TABLE}` WHERE 1",
            array(
                '{TABLE}' => _DB_PREFIX_.OcaEpak::QUOTES_TABLE,
            )
        );
        return Db::getInstance()->execute($query);
    }
}