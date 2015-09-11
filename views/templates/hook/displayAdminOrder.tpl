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
 *  @file-version 1.2
 *}

<div class="panel">
    <div class="panel-heading">
        <img src="../modules/{$moduleName|trim|escape:'htmlall':'UTF-8'}/logo.gif" alt="logo" /> {l s='OCA ePak Information' mod='ocaepak'}
    </div>
    <div class="row">
        <div class="col-xs-6">
            {if !empty($quoteError)}
                <p class="warn alert alert-danger">
                    {$quoteError|trim|escape:'htmlall':'UTF-8'}
                </p>
            {/if}
            <dl class="well list-detail">
                <dt>{l s='Operative' mod='ocaepak'}</dt>
                <dd>{$operative->reference|trim|escape:'htmlall':'UTF-8'} ({$operative->type|trim|escape:'htmlall':'UTF-8'}{if $operative->insured} {l s='Insured' mod='ocaepak'}{/if})</dd>
                <dt>{l s='Calculated Order Weight' mod='ocaepak'}</dt>
                <dd>{$cartData['weight']|trim|escape:'htmlall':'UTF-8'} kg</dd>
                <dt>{l s='Calculated Order Volume (with padding)' mod='ocaepak'}</dt>
                <dd>{$cartData['volume']|trim|escape:'htmlall':'UTF-8'} m³</dd>
                {if !empty($quoteData)}
                    <dt>{l s='Delivery time estimate' mod='ocaepak'}</dt>
                    <dd>{$quoteData->PlazoEntrega|trim|escape:'htmlall':'UTF-8'} {l s='working days' mod='ocaepak'}</dd>
                {/if}
                {if ($paidFee != 0)}
                    <dt>{l s='Additional fee' mod='ocaepak'}</dt>
                    <dd>{$currencySign|trim|escape:'htmlall':'UTF-8'}{$paidFee|trim|escape:'htmlall':'UTF-8'}</dd>
                {/if}
                {if $quote}
                    <dt>{l s='Live quote' mod='ocaepak'}</dt>
                    <dd>{$currencySign|trim|escape:'htmlall':'UTF-8'}{$quote|trim|escape:'htmlall':'UTF-8'}</dd>
                {/if}

            </dl>
        </div>
        <div class="col-xs-6">
            {if !empty($distributionCenter)}
                <dl class="well list-detail">
                    <dt>{l s='Delivery branch selected by customer' mod='ocaepak'}</dt>
                    <dd>{$distributionCenter['Descripcion']|trim|lower|capitalize|escape:'htmlall':'UTF-8'}</dd>
                    <dt>{l s='Branch ID' mod='ocaepak'}</dt>
                    <dd>{$distributionCenter['idCentroImposicion']|trim|escape:'htmlall':'UTF-8'}</dd>
                    <dt>{l s='Branch Code' mod='ocaepak'}</dt>
                    <dd>{$distributionCenter['Sigla']|trim|escape:'htmlall':'UTF-8'}</dd>
                    <dt>{l s='Branch Address' mod='ocaepak'}</dt>
                    <dd>
                        {$distributionCenter['Calle']|trim|lower|capitalize|escape:'htmlall':'UTF-8'} {$distributionCenter['Numero']|trim|lower|capitalize|escape:'htmlall':'UTF-8'}<br/>
                        {if ($distributionCenter['Piso']|trim) != ''}
                            {l s='Floor' mod='ocaepak'} :
                            {$distributionCenter['Piso']|trim|lower|capitalize|escape:'htmlall':'UTF-8'}<br/>
                        {/if}
                        {$distributionCenter['Localidad']|trim|lower|capitalize|escape:'htmlall':'UTF-8'}
                    </dd>
                    <dt>{l s='Branch Post Code' mod='ocaepak'}</dt>
                    <dd>{$distributionCenter['CodigoPostal']|trim|escape:'htmlall':'UTF-8'}</dd>
                </dl>
            {/if}
        </div>
    </div>
</div>