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
 *  @file-version 1.5
 */

class OcaEpakBranches
{
    public static $expiry = 24; //hours

    public static function retrieve($postcode)
    {
        $query = KznCarrier::interpolateSql(
            "SELECT *
            FROM `{TABLE}`
            WHERE postcode = '{POSTCODE}'
            AND `date` > DATE_SUB(NOW(), INTERVAL {EXPIRY} HOUR)",
            array(
                '{TABLE}' => _DB_PREFIX_.OcaEpak::BRANCHES_TABLE,
                '{POSTCODE}' => $postcode,
                '{EXPIRY}' => self::$expiry,
            )
        );
        $result = Db::getInstance()->executeS($query);
        if (!is_array($result))
            return array();
        $branches = array();
        foreach ($result as $branch) {
            $branches[$branch['IdCentroImposicion']] = $branch;
        }
        return $branches;
    }

    public static function insert($postcode, $branches)
    {
        $res = true;
        foreach ($branches as $branch) {
            $query = KznCarrier::interpolateSql(
                "REPLACE INTO `{TABLE}`
                (`IdCentroImposicion`, `Sucursal`, `Calle`, `Numero`, `Piso`, `Localidad`, `Provincia`, `Latitud`, `Longitud`, `CodigoPostal`, `postcode`, `date`)
                VALUES
                ('{IdCentroImposicion}',
                '{Sucursal}',
                '{Calle}',
                '{Numero}',
                '{Piso}',
                '{Localidad}',
                '{Provincia}',
                '{Latitud}',
                '{Longitud}',
                '{CodigoPostal}',
                '{POSTCODE}',
                NOW())",
                array(
                    '{TABLE}' => _DB_PREFIX_.OcaEpak::BRANCHES_TABLE,
                    '{POSTCODE}' => $postcode,
                    '{IdCentroImposicion}' => trim($branch['IdCentroImposicion']),
                    '{Sucursal}' => trim($branch['Sucursal']),
                    '{Calle}' => trim($branch['Calle']),
                    '{Numero}' => trim($branch['Numero']),
                    '{Piso}' => trim($branch['Piso']),
                    '{Localidad}' => trim($branch['Localidad']),
                    '{Provincia}' => trim($branch['Provincia']),
                    '{Latitud}' => trim($branch['Latitud']),
                    '{Longitud}' => trim($branch['Longitud']),
                    '{CodigoPostal}' => trim($branch['CodigoPostal']),
                )
            );
            $res &= Db::getInstance()->execute($query);
        }
        return $res;
    }

    public static function clear()
    {
        $query = KznCarrier::interpolateSql(
            "DELETE FROM `{TABLE}` WHERE 1",
            array(
                '{TABLE}' => _DB_PREFIX_.OcaEpak::BRANCHES_TABLE,
            )
        );
        return Db::getInstance()->execute($query);
    }
}