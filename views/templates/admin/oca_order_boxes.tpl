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

<div class="panel">
    {if empty($oca_boxes)}
        <div class="alert alert-danger">{l s='It is necessary to add at least one box type in the module\'s configuration to use the order generator' mod='ocaepak'}</div>
    {/if}
    {foreach $oca_boxes as $ind=>$box}
        <div class="form-group">
            <h4 style="display: inline-block; margin-right: 16px;">{l s='Box' mod='ocaepak'}: {$box['l']|escape:'htmlall':'UTF-8'}cm×{$box['d']|escape:'htmlall':'UTF-8'}cm×{$box['h']|escape:'htmlall':'UTF-8'}cm</h4>
            {l s='Quantity' mod='ocaepak'}: <input type="number" name="oca-box-q-{$ind|escape:'htmlall':'UTF-8'}" id="oca-box-q-{$ind|escape:'htmlall':'UTF-8'}" min="0" step="1" value="0" class="fixed-width-sm" style="display: inline-block;  margin-right: 16px;">
        </div>
    {/foreach}
</div>