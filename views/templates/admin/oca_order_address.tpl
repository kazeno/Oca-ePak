{**
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
 *  @file-version 1.4
 *}

<div class="form-group">
    <h4 style="display: inline-block; margin-right: 16px;">{l s='Full Address' mod='ocaepak'}
    {if $oca_geocoded}<abbr title="{l s='Address geocoded successfully' mod='ocaepak'}">*</abbr>{/if}:
    </h4>
    <br>{$oca_order_address->address1|escape:'htmlall':'UTF-8'}
    <br>{$oca_order_address->address2|escape:'htmlall':'UTF-8'}
    <br>{$oca_order_address->city|escape:'htmlall':'UTF-8'}
    <br>{$oca_order_address->other|escape:'htmlall':'UTF-8'}
</div>