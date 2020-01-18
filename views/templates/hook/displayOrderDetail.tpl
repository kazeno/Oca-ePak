{**
 * Oca e-Pak Module for Prestashop  https://github.com/kazeno/Oca-ePak
 * @author    Rinku Kazeno
 * @license   MIT License  https://opensource.org/licenses/mit-license.php
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