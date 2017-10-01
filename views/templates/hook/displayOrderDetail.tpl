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
 *  @file-version 1.4.5
 *}

<div class="row">
    <div class="col-xs-12 col-md-6">
        <div class="address alternate_item box">
            <h3 class="page-subheading">{l s='OCA ePak Information' mod='ocaepak'}</h3>
            <dl class="list-detail">
                <dt>{l s='Selected Delivery Branch' mod='ocaepak'}</dt>
                <dd>{$distributionCenter['Sucursal']|trim|lower|capitalize|escape:'htmlall':'UTF-8'} - {$distributionCenter['Sigla']|trim|escape:'htmlall':'UTF-8'}</dd>
                <dt>{l s='Branch Address' mod='ocaepak'}</dt>
                <dd>
                    {$distributionCenter['Calle']|trim|lower|capitalize|escape:'htmlall':'UTF-8'} {if !$distributionCenter['Numero']|is_array}{$distributionCenter['Numero']|trim|lower|capitalize|escape:'htmlall':'UTF-8'}{/if}<br/>
                    {if (!$distributionCenter['Piso']|is_array && $distributionCenter['Piso']|trim) != ''}
                        {l s='Floor' mod='ocaepak'} :
                        {$distributionCenter['Piso']|trim|lower|capitalize|escape:'htmlall':'UTF-8'}<br/>
                    {/if}
                    {$distributionCenter['Localidad']|trim|lower|capitalize|escape:'htmlall':'UTF-8'},
                    {$distributionCenter['Provincia']|trim|lower|capitalize|escape:'htmlall':'UTF-8'}
                    <br>(<a href="http://maps.google.com/maps?z=18&q={$distributionCenter['Latitud']|trim|escape:'htmlall':'UTF-8'},{$distributionCenter['Longitud']|trim|escape:'htmlall':'UTF-8'}" target="_blank">{l s='How to get there' mod='ocaepak'}</a>)
                </dd>
                <dt>{l s='Branch Post Code' mod='ocaepak'}</dt>
                <dd>{$distributionCenter['CodigoPostal']|trim|escape:'htmlall':'UTF-8'}</dd>
                <dt>{l s='Phone Number' mod='ocaepak'}</dt>
                <dd>{$distributionCenter['Telefono']|trim|escape:'htmlall':'UTF-8'}</dd>
            </dl>
        </div>
    </div>
</div>