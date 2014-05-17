<?php
/**
 *
 */

class OcaEpakOperative extends ObjectModel
{
    //public $id;
    public $reference;
    public $description;
    public $addfee;
    public $id_shop;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => OcaEpak::OPERATIVES_TABLE,
        'primary' => OcaEpak::OPERATIVES_ID,
        'multishop' => TRUE,
        'fields' => array(
            'reference' =>	array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => TRUE),
            'description' => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => TRUE),
            'addfee' => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => FALSE),
            'id_shop' => array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => true),
        )
    );

    public function validateFields($die = true, $error_return = false)
    {
        $message = parent::validateFields($die, TRUE);
        if ($message !== TRUE)
            return $error_return ? $message : false;
        $message = (!preg_match('/^[\d]*[\.]?[\d]*%?$/', $this->addfee) OR $this->addfee == '%')
            ? Translate::getModuleTranslation('OcaEpak','Optional fee format is incorrect. Should be either an amount, such as 7.50, or a percentage, such as 6.99%','OcaEpak')
        : TRUE;
        if ($message !== true)
        {
            if ($die)
                throw new PrestaShopException($message);
            return $error_return ? $message : false;
        }

        return TRUE;
    }
}