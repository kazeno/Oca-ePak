{**
 * Oca e-Pak Module for Prestashop  https://github.com/kazeno/Oca-ePak
 * @author    Rinku Kazeno
 * @license   MIT License  https://opensource.org/licenses/mit-license.php
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