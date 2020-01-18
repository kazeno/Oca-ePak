{**
 * Oca e-Pak Module for Prestashop  https://github.com/kazeno/Oca-ePak
 * @author    Rinku Kazeno
 * @license   MIT License  https://opensource.org/licenses/mit-license.php
 *  @file-version 1.3
 *}

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
                <dd>{$distributionCenter['Sucursal']|trim|lower|capitalize|escape:'htmlall':'UTF-8'}</dd>
                <dt>{l s='Branch ID' mod='ocaepak'}</dt>
                <dd>{$distributionCenter['IdCentroImposicion']|trim|escape:'htmlall':'UTF-8'}</dd>
                <dt>{l s='Branch Code' mod='ocaepak'}</dt>
                <dd>{$distributionCenter['Sigla']|trim|escape:'htmlall':'UTF-8'}</dd>
                <dt>{l s='Branch Address' mod='ocaepak'}</dt>
                <dd>
                    {$distributionCenter['Calle']|trim|lower|capitalize|escape:'htmlall':'UTF-8'} {if !$distributionCenter['Numero']|is_array}{$distributionCenter['Numero']|trim|lower|capitalize|escape:'htmlall':'UTF-8'}{/if}<br/>
                    {if (!$distributionCenter['Piso']|is_array && $distributionCenter['Piso']|trim) != ''}
                        {l s='Floor' mod='ocaepak'} :
                        {$distributionCenter['Piso']|trim|lower|capitalize|escape:'htmlall':'UTF-8'}<br/>
                    {/if}
                    {$distributionCenter['Localidad']|trim|lower|capitalize|escape:'htmlall':'UTF-8'},
                    {$distributionCenter['Provincia']|trim|lower|capitalize|escape:'htmlall':'UTF-8'}
                </dd>
                <dt>{l s='Branch Post Code' mod='ocaepak'}</dt>
                <dd>{$distributionCenter['CodigoPostal']|trim|escape:'htmlall':'UTF-8'}</dd>
            </dl>
        {/if}
    </div>
</div>