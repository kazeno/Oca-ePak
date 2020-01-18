{**
 * Oca e-Pak Module for Prestashop  https://github.com/kazeno/Oca-ePak
 * @author    Rinku Kazeno
 * @license   MIT License  https://opensource.org/licenses/mit-license.php
 *  @file-version 1.5.2
 *}

<div id="oca-delivery-options-{$currentOcaCarrier|escape:'htmlall':'UTF-8'}">
    <p class="carrier_title">{l s='Please select your preferred pick-up location:' mod='ocaepak'}</p>
    {if $ocaepak_branch_sel_type == 0}
        <div class="row">
            <label for="ocaStateSelect-{$currentOcaCarrier|escape:'htmlall':'UTF-8'}" class="col-xs-2">{l s='State' mod='ocaepak'}:</label>
            <div class="col-xs-10 radius-input">
                <select name="oca_state" id="ocaStateSelect-{$currentOcaCarrier|escape:'htmlall':'UTF-8'}" class="chosen">
                    {foreach $ocaepak_states as $state}
                        <option value="{$state['name']}">{$state['name']}</option>
                    {/foreach}
                </select>
            </div>
        </div>
        <div class="row">
            <label for="ocaBranchSelect-{$currentOcaCarrier|escape:'htmlall':'UTF-8'}" class="col-xs-2">{l s='Branch' mod='ocaepak'}:</label>
            <div class="col-xs-10 radius-input">
                <select name="oca_branch" id="ocaBranchSelect-{$currentOcaCarrier|escape:'htmlall':'UTF-8'}" class="chosen"></select>
            </div>
        </div>
    {/if}
    <div id="oca-map-{$currentOcaCarrier|escape:'htmlall':'UTF-8'}"></div>
    {if $ocaepak_branch_sel_type == 1}
        <div class="radius-input">
            <label for="ocaBranchSelect-{$currentOcaCarrier|escape:'htmlall':'UTF-8'}">{l s='Selected Branch' mod='ocaepak'}:</label>
            <select name="branch" id="ocaBranchSelect-{$currentOcaCarrier|escape:'htmlall':'UTF-8'}" class="form-control"></select>
        </div>
    {/if}
</div>
<hr />

<script>
{if !empty($ocaepak_relays2)}
    var ocaRelays = JSON.parse('{$ocaepak_relays2|@json_encode|escape:'quotes':'UTF-8' nofilter}');
    var ocaRelayCarriers = JSON.parse({$relayed_carriers|@json_encode|escape:'quotes':'UTF-8' nofilter});
    var ocaRelayUrl = '{$ocaepak_relay_url|escape:'quotes':'UTF-8' nofilter}';
    {if isset($ocaepak_states)}var ocaStates = JSON.parse('{$ocaepak_states|@json_encode|escape:'quotes':'UTF-8' nofilter}');{/if}
    var ocaGmapsKey = '{$gmaps_api_key|escape:'htmlall':'UTF-8'}';
    var ocaBranchSelType = '{$ocaepak_branch_sel_type|escape:'htmlall':'UTF-8'}';
{/if}
var customerAddress = JSON.parse('{$customerAddress|@json_encode|escape:'quotes':'UTF-8' nofilter}');
var customerStateCode = '{$customerStateCode|escape:'quotes':'UTF-8'}';
var ocaSelectedRelay = {if $ocaepak_selected_relay}{$ocaepak_selected_relay|escape:'quotes':'UTF-8'}{else}null{/if};
var ocaRelayAuto = {if $ocaepak_relay_auto}{$ocaepak_relay_auto|escape:'quotes':'UTF-8'}{else}null{/if};
if ((typeof ocaeInitialized !== 'undefined') && ocaeInitialized && ($('#oca-map-{$currentOcaCarrier|escape:'htmlall':'UTF-8'}').length && !$('#oca-map-{$currentOcaCarrier|escape:'htmlall':'UTF-8'}').children().length))
    ocaeInit();
</script>