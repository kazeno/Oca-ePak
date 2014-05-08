<?php
/**
 *
 */

class OcaEpakOperative extends ObjectModel
{
    public $id;
    public $description;
    public $addfee;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => OcaEpak::OPERATIVES_TABLE,
        'primary' => 'id',
        'multishop' => TRUE,
        'fields' => array(
            'id' =>	array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => TRUE),
            'description' => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => TRUE),
            'addfee' => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => FALSE),
            'id_shop' => array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => true),
        )
    );
}