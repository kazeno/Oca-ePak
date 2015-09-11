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
        ob_start(); ?>
            SELECT `price`
            FROM `<?php echo pSQL(_DB_PREFIX_.OcaEpak::QUOTES_TABLE);?>`
            WHERE reference = '<?php echo pSQL($reference); ?>'
            AND postcode = '<?php echo pSQL($postcode); ?>'
            AND origin = '<?php echo pSQL($origin); ?>'
            AND ABS(volume - '<?php echo pSQL(round($volume, self::$volumePrecision)); ?>') < 0.000001
            AND ABS(weight - '<?php echo pSQL($weight); ?>') < 0.000001
            AND ABS(`value` - '<?php echo pSQL($value); ?>') < 1
            AND `date` > DATE_SUB(NOW(), INTERVAL <?php echo pSQL(self::$expiry); ?> HOUR)
        <?php return Db::getInstance()->getValue(ob_get_clean());
    }

    public static function insert($reference, $postcode, $origin, $volume, $weight, $value, $price)
    {
        ob_start(); ?>
            REPLACE INTO `<?php echo pSQL(_DB_PREFIX_.OcaEpak::QUOTES_TABLE);?>`
            (reference, postcode, origin, volume, weight, `value`, price, `date`)
            VALUES
            ('<?php echo pSQL($reference); ?>',
            '<?php echo pSQL($postcode); ?>',
            '<?php echo pSQL($origin); ?>',
            '<?php echo pSQL(round($volume, self::$volumePrecision)); ?>',
            '<?php echo pSQL($weight); ?>',
            '<?php echo pSQL($value); ?>',
            '<?php echo pSQL($price); ?>',
            NOW())
        <?php return Db::getInstance()->execute(ob_get_clean());
    }

    public static function clear()
    {
        ob_start(); ?>
            DELETE FROM `<?php echo pSQL(_DB_PREFIX_.OcaEpak::QUOTES_TABLE);?>` WHERE 1
        <?php $query = ob_get_clean();
        return Db::getInstance()->execute($query);
    }
}