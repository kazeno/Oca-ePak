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

<script>
var ocaRelays = JSON.parse('{$ocaepak_relays|@json_encode|escape:'quotes':'UTF-8' nofilter}');
var ocaRelayUrl = '{$link->getModuleLink($ocaepak_name, 'relay', [], $force_ssl)|escape:'quotes':'UTF-8'}';
var ocaRelayCarriers = JSON.parse({$relayed_carriers|@json_encode|escape:'quotes':'UTF-8' nofilter});
var ocaGmapsKey = '{$gmaps_api_key|escape:'htmlall':'UTF-8'}';
</script>