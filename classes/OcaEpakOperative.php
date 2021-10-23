<?php
/**
 * Oca e-Pak Module for Prestashop  https://github.com/kazeno/Oca-ePak
 *
 * @author    Rinku Kazeno
 * @license   MIT License  https://opensource.org/licenses/mit-license.php
 * @file-version 1.3
 */

class OcaEpakOperative extends ObjectModel
{
    public $carrier_reference;
    public $reference;
    public $description;
    public $addfee;
    public $type;
    public $insured;
    public $id_shop;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => OcaEpak::OPERATIVES_TABLE,
        'primary' => OcaEpak::OPERATIVES_ID,
        'multishop' => TRUE,
        'fields' => array(
            'carrier_reference' =>	array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => FALSE),
            'reference' =>	array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => TRUE),
            'description' => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => TRUE),
            'addfee' => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => FALSE),
            'type' => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true),
            'insured' => array('type' => self::TYPE_STRING, 'validate' => 'isBool', 'required' => true),
            'id_shop' => array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => true),
        )
    );

    /**
     * @throws PrestaShopException
     * @throws Exception
     */
    public function validateFields($die = true, $error_return = false)
    {
        $message = parent::validateFields($die, TRUE);
        if ($message !== TRUE) {
            return $error_return ? $message : false;
        }
        $message = (
            (!preg_match('/^[\d]*[\.]?[\d]*%?$/', $this->addfee) OR $this->addfee == '%')
            ? Translate::getModuleTranslation('OcaEpak','Optional fee format is incorrect. Should be either an amount, such as 7.50, or a percentage, such as 6.99%','OcaEpak')
            : TRUE
        );
        if ($message !== true) {
            if ($die) {
                throw new PrestaShopException($message);
            }
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
        $carrier->url = OcaEpak::TRACKING_URL;
        $carrier->delay = array();
        //$carrier->delay[Language::getIsoById(Configuration::get('PS_LANG_DEFAULT'))] = OcaEpak::CARRIER_DELAY;
        $carrier->shipping_handling = false;
        $carrier->range_behavior = 0;
        $carrier->is_module = true;
        $carrier->shipping_external = true;
        $carrier->external_module_name = OcaEpak::MODULE_NAME;
        $carrier->need_range = true;
        $languages = Language::getLanguages(true);
        foreach ($languages as $language) {
            $carrier->delay[(int)$language['id_lang']] = $this->description;
        }
        $preGroups = Group::getGroups(Configuration::get('PS_LANG_DEFAULT'));
        $groups = array();
        foreach ($preGroups as $pre) {
            $groups[] = $pre['id_group'];
        }
        $rangePrice = new RangePrice();
        $rangePrice->delimiter1 = '0';
        $rangePrice->delimiter2 = '10000';
        $rangeWeight = new RangeWeight();
        $rangeWeight->delimiter1 = '0';
        $rangeWeight->delimiter2 = '10000';
        if (!$carrier->add()) {
            return false;
        }
        $carrier = new Carrier($carrier->id);   //reload carrier to get reference
        return (
            (method_exists('Carrier', 'setGroups') ? $carrier->setGroups($groups) : $this->setCarrierGroups($carrier, $groups)) AND
            $carrier->addZone(Country::getIdZone(Country::getByIso('AR'))) AND
            ($rangePrice->id_carrier = $rangeWeight->id_carrier = (int)$carrier->id) AND
            ($this->carrier_reference = (int)$carrier->id_reference) AND
            $rangePrice->add() AND
            $rangeWeight->add() AND
            copy(_PS_MODULE_DIR_.OcaEpak::MODULE_NAME.'/views/img/logo.jpg', _PS_SHIP_IMG_DIR_.'/'.(int)$carrier->id.'.jpg') AND
            parent::add($autodate, $null_values)
        );
    }

    public function update($null_values = false)
    {
        $carrier = Carrier::getCarrierByReference($this->carrier_reference);
        $languages = Language::getLanguages(true);
        foreach ($languages as $language) {
            $carrier->delay[(int)$language['id_lang']] = $this->description;
        }
        return (
            $carrier->update() AND
            parent::update($null_values)
        );
    }

    public function delete()
    {
        $carrier = Carrier::getCarrierByReference($this->carrier_reference);
        $carrier->deleted = true;
        return (
            $carrier->update() AND
            parent::delete()
        );
    }


    /**
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getByFieldId($field, $id_field)
    {
        if (!in_array(
            $field,
            array('carrier_reference', 'reference', 'description', /*'addfee', 'id_shop'*/)
        )) {
            return false;
        }
        $query = OcaCarrierTools::interpolateSql(
            "SELECT `{ID}`
            FROM `{TABLE}`
            WHERE `{FIELD}` = '{IDFIELD}'
            ORDER BY `{FIELD}` DESC",
            array(
                '{TABLE}' => _DB_PREFIX_.OcaEpak::OPERATIVES_TABLE,
                '{ID}' => OcaEpak::OPERATIVES_ID,
                '{FIELD}' => $field,
                '{IDFIELD}' => $id_field,
            )
        );
        $id = Db::getInstance()->getValue($query);
        return $id ? new OcaEpakOperative($id) : false;
    }

    /**
     * @throws PrestaShopException
     * @throws PrestaShopDatabaseException
     */
    public static function getOperativeIds($returnObjects=false, $filter_column=NULL, $filter_value=NULL)
    {
        if (
            !is_null($filter_column)
            && !in_array(
                $filter_column,
                array(OcaEpak::OPERATIVES_ID, 'carrier_reference', 'description', 'addfee', 'id_shop', 'type')
            )
        ) {
            return false;
        }
        if ($filter_column) {
            $query = OcaCarrierTools::interpolateSql(
                "SELECT `{ID}`
                FROM `{TABLE}`
                WHERE `{COLUMN}` = '{VALUE}'
                ORDER BY `{COLUMN}` DESC",
                array(
                    '{TABLE}' => _DB_PREFIX_.OcaEpak::OPERATIVES_TABLE,
                    '{ID}' => OcaEpak::OPERATIVES_ID,
                    '{COLUMN}' => $filter_column,
                    '{VALUE}' => $filter_value,
                )
            );
        } else {
            $query = OcaCarrierTools::interpolateSql(
                "SELECT `{ID}`
                FROM `{TABLE}`",
                array(
                    '{TABLE}' => _DB_PREFIX_.OcaEpak::OPERATIVES_TABLE,
                    '{ID}' => OcaEpak::OPERATIVES_ID,
                )
            );
        }
        $res = Db::getInstance()->executeS($query);
        $ops = array();
        foreach ($res as $re) {
            $ops[$re[OcaEpak::OPERATIVES_ID]] = $returnObjects ? (new OcaEpakOperative($re[OcaEpak::OPERATIVES_ID])) : $re[OcaEpak::OPERATIVES_ID];
        }
        return $ops;
    }

    /**
     * @throws PrestaShopDatabaseException
     */
    public static function getRelayedCarrierIds($returnObjects=false)
    {
        $query = OcaCarrierTools::interpolateSql(
            "SELECT `id_carrier`
            FROM `{PREFIX}carrier` AS c
            LEFT JOIN `{TABLE}` AS o
            ON (o.`carrier_reference` = c.`id_reference`)
            WHERE o.`type` IN ('PaS', 'SaS') AND c.`deleted` = 0",
            array(
                '{TABLE}' => _DB_PREFIX_.OcaEpak::OPERATIVES_TABLE,
                '{PREFIX}' => _DB_PREFIX_,
            )
        );
        $res = Db::getInstance()->executeS($query);
        $crs = array();
        foreach ($res as $re) {
            $crs[] = $returnObjects ? (new Carrier($re['id_carrier'])) : $re['id_carrier'];
        }
        return $crs;
    }



    public static function purgeCarriers()
    {
        $query = OcaCarrierTools::interpolateSql(
            "UPDATE `{PREFIX}carrier`
            SET deleted = 1
            WHERE external_module_name = '{MODULE}'",
            array(
                '{MODULE}' => OcaEpak::MODULE_NAME,
                '{PREFIX}' => _DB_PREFIX_,
            )
        );
        return Db::getInstance()->execute($query);
    }


    /**
     * Shim for old PS 1.5 versions without Carrier::setGroups()
     *
     * @param $carrier
     * @param $groups
     * @param bool $delete
     * @return bool
     */
    protected function setCarrierGroups($carrier, $groups, $delete = true)
    {
        if ($delete) {
            Db::getInstance()->execute('DELETE FROM ' . pSQL(_DB_PREFIX_) . 'carrier_group WHERE id_carrier = ' . (int)$carrier->id);
        }
        if (!is_array($groups) || !count($groups)) {
            return true;
        }
        $sql = 'INSERT INTO '.pSQL(_DB_PREFIX_).'carrier_group (id_carrier, id_group) VALUES ';
        foreach ($groups as $id_group) {
            $sql .= '(' . (int)$carrier->id . ', ' . (int)$id_group . '),';
        }

        return Db::getInstance()->execute(rtrim($sql, ','));
    }
}