<?php
/**
 * Oca e-Pak Module for Prestashop  https://github.com/kazeno/Oca-ePak
 *
 * @author    Rinku Kazeno
 * @license   MIT License  https://opensource.org/licenses/mit-license.php
 * @file-version 1.5
 */

class OcaEpakBranches
{
    public static $expiry = 24; //hours

    public static function retrieve($postcode)
    {
        $query = OcaCarrierTools::interpolateSql(
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
            $query = OcaCarrierTools::interpolateSql(
                "REPLACE INTO `{TABLE}`
                (`IdCentroImposicion`, `Sucursal`, `Calle`, `Numero`, `Localidad`, `Provincia`, `Latitud`, `Longitud`, `CodigoPostal`, `postcode`, `date`)
                VALUES
                ('{IdCentroImposicion}',
                '{Sucursal}',
                '{Calle}',
                '{Numero}',
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
                    //'{Piso}' => trim($branch['Piso']),
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
        $query = OcaCarrierTools::interpolateSql(
            "DELETE FROM `{TABLE}` WHERE 1",
            array(
                '{TABLE}' => _DB_PREFIX_.OcaEpak::BRANCHES_TABLE,
            )
        );
        return Db::getInstance()->execute($query);
    }
}