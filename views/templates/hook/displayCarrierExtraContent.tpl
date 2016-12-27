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
 *  @file-version 1.4.3
 *}

<div id="oca-delivery-options-{$currentOcaCarrier|escape:'htmlall':'UTF-8'}">
    <p class="carrier_title">{l s='Please select your preferred pick-up location:' mod='ocaepak'}</p>
    <div id="oca-map-{$currentOcaCarrier|escape:'htmlall':'UTF-8'}"></div>
    <div class="radius-input">
        <label for="ocaBranchSelect-{$currentOcaCarrier|escape:'htmlall':'UTF-8'}">{l s='Selected Branch' mod='ocaepak'}:</label>
        <select name="branch" id="ocaBranchSelect-{$currentOcaCarrier|escape:'htmlall':'UTF-8'}" class="form-control"></select>
    </div>
</div>
<hr />

<script>
var customerAddress = JSON.parse('{$customerAddress|@json_encode|escape:'quotes':'UTF-8' nofilter}');
var customerStateCode = '{$customerStateCode|escape:'quotes':'UTF-8'}';
var ocaSelectedRelay = {if $ocaepak_selected_relay}{$ocaepak_selected_relay|escape:'quotes':'UTF-8'}{else}null{/if};
var ocaRelayAuto = {if $ocaepak_relay_auto}{$ocaepak_relay_auto|escape:'quotes':'UTF-8'}{else}null{/if};
</script>