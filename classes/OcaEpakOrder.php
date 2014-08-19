<?php
/**
 * Oca e-Pak Module for Prestashop
 *
 *  @author Rinku Kazeno <development@kazeno.co>
 *  @file-version 1.0
 */

class OcaEpakOrder extends ObjectModel
{
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

}