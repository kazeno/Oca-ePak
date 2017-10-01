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
 *  @file-version 1.5
 *}

<div id="oca-delivery-options">
     {if $psver < 1.6}
        <h3 class="carrier_title">{l s='Please select your preferred pick-up location:' mod='ocaepak'}</h3>
    {else}
        <p class="carrier_title">{l s='Please select your preferred pick-up location:' mod='ocaepak'}</p>
    {/if}

    <div id="oca-dropdown">
        {if $ocaepak_branch_sel_type == 0}
            <div class="row">
                <div class="col-xs-12 col-sm-4 radius-input">
                    <label for="ocaStateSelect" class="col-xs-3 col-sm-4">{l s='State' mod='ocaepak'}:</label>
                    <select name="oca_state" id="ocaStateSelect" class="col-xs-9 col-sm-8 chosen">
                        {foreach $ocaepak_states as $state}
                            <option value="{$state['name']}">{$state['name']}</option>
                        {/foreach}
                    </select>
                </div>
                <div class="col-xs-12 col-sm-8 radius-input">
                    <label for="ocaBranchSelect" class="col-xs-3">{l s='Branch' mod='ocaepak'}:</label>
                    <select name="oca_branch" id="ocaBranchSelect" class="col-xs-9 chosen"></select>
                </div>
            </div>
        {/if}
        <div id="oca-map"></div>
        {if $ocaepak_branch_sel_type == 1}
            <div class="radius-input">
                <label for="ocaBranchSelect">{l s='Selected Branch' mod='ocaepak'}:</label>
                <select name="branch" id="ocaBranchSelect" class="form-control"></select>
            </div>
        {/if}
    </div>
</div>
<hr />

<script>
var customerAddress = JSON.parse('{$customerAddress|@json_encode|escape:'quotes':'UTF-8' nofilter}');
var customerStateCode = '{$customerStateCode|escape:'quotes':'UTF-8'}';
var ocaSelectedRelay = {if $ocaepak_selected_relay}{$ocaepak_selected_relay|escape:'quotes':'UTF-8'}{else}null{/if};
var ocaRelayAuto = {if $ocaepak_relay_auto}{$ocaepak_relay_auto|escape:'quotes':'UTF-8'}{else}null{/if};
if (typeof ocaEpakCallback !== 'undefined')
    ocaEpakCallback();
</script>