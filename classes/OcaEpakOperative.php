<?php
/**
 *
 */

class OcaEpakOperative extends ObjectModel
{
    public $id_carrier;
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
            'id_carrier' =>	array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => FALSE),
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

    public function add($autodate = true, $null_values = false)
    {
        $carrier = new Carrier();
        $carrier->name = OcaEpak::CARRIER_NAME;
        $carrier->id_tax_rules_group = 0;
        //$carrier->id_zone = Country::getIdZone(Country::getByIso('AR'));
        $carrier->active = true;
        $carrier->deleted = false;
        $carrier->delay = array();
        //$carrier->delay[Language::getIsoById(Configuration::get('PS_LANG_DEFAULT'))] = OcaEpak::CARRIER_DELAY;
        $carrier->shipping_handling = false;
        $carrier->range_behavior = 0;
        $carrier->is_module = true;
        $carrier->shipping_external = true;
        $carrier->external_module_name = OcaEpak::MODULE_NAME;
        $carrier->need_range = false;
        $languages = Language::getLanguages(true);
        foreach ($languages as $language)
        {
            $carrier->delay[(int)$language['id_lang']] = OcaEpak::CARRIER_DELAY;
        }

        return (
            $carrier->add() AND
            copy(_PS_MODULE_DIR_.OcaEpak::MODULE_NAME.'/img/logo.jpg', _PS_SHIP_IMG_DIR_.'/'.(int)$carrier->id.'.jpg') AND
            $carrier->addZone(Country::getIdZone(Country::getByIso('AR'))) AND
            ($this->id_carrier = (int)$carrier->id) AND
            parent::add($autodate, $null_values)
        );
    }

    public function delete()
    {
        $carrier = new Carrier($this->id_carrier);
        //$carrier = new Carrier(13);
        //**/die($carrier->name);
        //**/ $carrier->name = OcaEpak::CARRIER_NAME;
        $carrier->deleted = true;
        return (
            $carrier->update() AND
            parent::delete()
        );
    }

    public static function getByFieldId($field, $id_field)
    {
        if (!in_array($field, array('id_carrier', 'reference', 'description', /*'addfee', 'id_shop'*/)))
            return false;
        $id = Db::getInstance()->getValue('
            SELECT `'.$field.'`
            FROM `'._DB_PREFIX_.OcaEpak::OPERATIVES_TABLE.'`
			WHERE `'.$field.'` = '.(int)$id_field.'
			ORDER BY `'.$field.'` DESC'
        );
        return $id ? new OcaEpakOperative($id) : false;
    }
}