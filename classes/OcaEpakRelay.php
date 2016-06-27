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
 *  @file-version 0.1
 */

class OcaEpakRelay extends ObjectModel
{
    public $id_cart;
    public $distribution_center_id;
    public $auto;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => OcaEpak::RELAYS_TABLE,
        'primary' => OcaEpak::RELAYS_ID,
        'multishop' => TRUE,
        'fields' => array(
            'id_cart' => array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => TRUE),
            'distribution_center_id' =>	array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => TRUE),
            'auto' => array('type' => self::TYPE_INT, 'validate' => 'isBool', 'required' => false),
        )
    );

    public static function getByCartId($id_cart)
    {
        $query = KznCarrier::interpolateSql(
            "SELECT `{ID}`
            FROM `{TABLE}`
            WHERE `id_cart` = '{CART}'",
            array(
                '{TABLE}' => _DB_PREFIX_.OcaEpak::RELAYS_TABLE,
                '{ID}' => OcaEpak::RELAYS_ID,
                '{CART}' => $id_cart,
            )
        );
        $id = Db::getInstance()->getValue($query);

        return $id ? (new OcaEpakRelay($id)) : NULL;
    }

}