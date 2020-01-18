<?php
/**
 * Oca e-Pak Module for Prestashop  https://github.com/kazeno/Oca-ePak
 *
 * @author    Rinku Kazeno
 * @license   MIT License  https://opensource.org/licenses/mit-license.php
 * @file-version 0.1
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
        $query = OcaCarrierTools::interpolateSql(
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