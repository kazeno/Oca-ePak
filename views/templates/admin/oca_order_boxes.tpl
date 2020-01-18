{**
 * Oca e-Pak Module for Prestashop  https://github.com/kazeno/Oca-ePak
 * @author    Rinku Kazeno
 * @license   MIT License  https://opensource.org/licenses/mit-license.php
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