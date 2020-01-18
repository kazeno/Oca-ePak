{**
 * Oca e-Pak Module for Prestashop  https://github.com/kazeno/Oca-ePak
 * @author    Rinku Kazeno
 * @license   MIT License  https://opensource.org/licenses/mit-license.php
 *  @file-version 1.5
 *}

<script>
var ocaRelays = JSON.parse('{$ocaepak_relays|@json_encode|escape:'quotes':'UTF-8' nofilter}');
var ocaRelayUrl = '{$link->getModuleLink($ocaepak_name, 'relay', [], $force_ssl)|escape:'quotes':'UTF-8' nofilter}';
var ocaRelayCarriers = JSON.parse({$relayed_carriers|@json_encode|escape:'quotes':'UTF-8' nofilter});
{if isset($ocaepak_states)}var ocaStates = JSON.parse('{$ocaepak_states|@json_encode|escape:'quotes':'UTF-8' nofilter}');{/if}
var ocaGmapsKey = '{$gmaps_api_key|escape:'htmlall':'UTF-8'}';
var ocaBranchSelType = '{$ocaepak_branch_sel_type|escape:'htmlall':'UTF-8'}';
</script>