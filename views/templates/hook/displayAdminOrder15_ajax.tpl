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
 *  @file-version 1.3
 *}

<div class="row">
    <div class="col-xs-6">
        {if !empty($quoteError)}
            <div class="alert alert-danger">
                {$quoteError|trim|escape:'htmlall':'UTF-8'}
            </div>
        {/if}
        {l s='Operative' mod='ocaepak'}: <b>{$operative->reference|trim|escape:'htmlall':'UTF-8'} ({$operative->type|trim|escape:'htmlall':'UTF-8'}{if $operative->insured} {l s='Insured' mod='ocaepak'}{/if})</b><br/>
        {l s='Calculated Order Weight' mod='ocaepak'}: <b>{$cartData['weight']|trim|escape:'htmlall':'UTF-8'} kg</b><br/>
        {l s='Calculated Order Volume (with padding)' mod='ocaepak'}: <b>{$cartData['volume']|trim|escape:'htmlall':'UTF-8'} m³</b><br/>
        {if !empty($quoteData)}
            {l s='Delivery time estimate' mod='ocaepak'}: <b>{$quoteData->PlazoEntrega|trim|escape:'htmlall':'UTF-8'} {l s='working days' mod='ocaepak'}</b><br/>
                {/if}
        {if ($paidFee != 0)}
            {l s='Additional fee' mod='ocaepak'}: <b>{$currencySign|trim|escape:'htmlall':'UTF-8'}{$paidFee|trim|escape:'htmlall':'UTF-8'}</b><br/>
            {/if}
        {if $quote}
            {l s='Live quote' mod='ocaepak'}: <b>{$currencySign|trim|escape:'htmlall':'UTF-8'}{$quote|trim|escape:'htmlall':'UTF-8'}</b><br/><br/>
            {/if}
    </div>
    <div class="col-xs-6">
        {if !empty($distributionCenter)}
            {l s='Delivery branch selected by customer' mod='ocaepak'}: <b>{$distributionCenter['Descripcion']|trim|lower|capitalize|escape:'htmlall':'UTF-8'}</b><br/>
            {l s='Branch ID' mod='ocaepak'}: <b>{$distributionCenter['idCentroImposicion']|trim|escape:'htmlall':'UTF-8'}</b><br/>
        {l s='Branch Code' mod='ocaepak'}: <b>{$distributionCenter['Sigla']|trim|escape:'htmlall':'UTF-8'}</b><br/>
        {l s='Branch Address' mod='ocaepak'}: <br/>
            <b>
                {$distributionCenter['Calle']|trim|lower|capitalize|escape:'htmlall':'UTF-8'} {$distributionCenter['Numero']|trim|lower|capitalize|escape:'htmlall':'UTF-8'}<br/>
                {if ($distributionCenter['Piso']|trim) != ''}
                    {l s='Floor' mod='ocaepak'} :
                    {$distributionCenter['Piso']|trim|lower|capitalize|escape:'htmlall':'UTF-8'}<br/>
                {/if}
                {$distributionCenter['Localidad']|trim|lower|capitalize|escape:'htmlall':'UTF-8'}
        </b><br/>
        {l s='Branch Post Code' mod='ocaepak'}: <b>{$distributionCenter['CodigoPostal']|trim|escape:'htmlall':'UTF-8'}</b><br/>
        {/if}
    </div>
</div>