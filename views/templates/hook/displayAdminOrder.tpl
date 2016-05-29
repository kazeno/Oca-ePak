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
    <div class="panel-heading">
        <img src="../modules/{$moduleName|trim|escape:'htmlall':'UTF-8'}/logo.gif" alt="logo" /> {l s='OCA ePak Information' mod='ocaepak'}
    </div>
    <div id="oca-ajax-container" class="text-center">
        <h2>{l s='Loading' mod='ocaepak'}...</h2>
        <img src="../img/loadingAnimation.gif" alt="{l s='Loading' mod='ocaepak'}">
    </div>
</div>
<script>{literal}
    $(document).ready(function() {
        $.ajax({
            url: {/literal}'{$ocaAjaxUrl|escape:'quotes':'UTF-8'}'{literal},
            data: {
                ajax: true,
                action: 'carrier',
                order_id: {/literal}'{$ocaOrderId|escape:'htmlall':'UTF-8'}'{literal}
            },
            success : function(result){
                $('#oca-ajax-container').replaceWith(result);
            }
        });
    });
</script>{/literal}
<div class="panel">
    <div class="panel-heading">
        <img src="../modules/{$moduleName|trim|escape:'htmlall':'UTF-8'}/logo.gif" alt="logo" /> {l s='OCA ePak Orders' mod='ocaepak'}
    </div>
    {if $ocaGuiHeader}
        <div class="form-group">
            %HEADER_GOES_HERE%
        </div>
    {/if}
    {if $ocaOrderStatus === 'submitted'}
        <div class="form-group">
            <div>
                {l s='OCA Order Id' mod='ocaepak'}: {$ocaOrder->reference|escape:'htmlall':'UTF-8'}<br />
                {l s='Status' mod='ocaepak'}: {$ocaStatus|escape:'htmlall':'UTF-8'}<br />
                <{if $accepts}
                    {l s='Ingressed Packages' mod='ocaepak'}: {$accepts|escape:'htmlall':'UTF-8'}<br />
                {/if}
                {if $rejects}
                    {l s='Rejected Packages' mod='ocaepak'}: {$rejects|escape:'htmlall':'UTF-8'}<br />
                {/if}
                {l s='Tracking' mod='ocaepak'}: <a href="https://www1.oca.com.ar/OEPTrackingWeb/trackingenvio.asp?numero1={$ocaOrder->tracking|escape:'htmlall':'UTF-8'}" target="_blank">{$ocaOrder->tracking|escape:'htmlall':'UTF-8'}</a><br />
                <button id="oca-cancel-button" class="btn btn-danger" onclick="cancelOcaOrder()">{l s='Cancel OCA Order' mod='ocaepak'}</button>
                <button id="oca-print-button" class="btn btn-primary" onclick="printIframe()">{l s='Print Package Stickers' mod='ocaepak'}</button><br />
                <iframe src="{$stickerUrl|escape:'htmlall':'UTF-8'}" id="oca-sticker" frameborder="0" style="margin: 18px; width: 0; height: 0; max-width: 100%;"></iframe>
                {literal}<script>
                    $('#oca-print-button').hide();
                    $('#oca-sticker').load(function () {
                        var $tables = $('#etiquetas > table', $(this).contents());
                        if ($tables.length > 0) {
                            $(this).height($(this).contents().height());
                            $(this).width($(this).contents().width());
                            $('#oca-print-button').show();
                        } else {
                            $('#oca-sticker').hide().after('{/literal}{l s='No stickers available' mod='ocaepak'}{literal}');
                        }
                    });
                    function printIframe() {
                        var ua = window.navigator.userAgent;
                        var msie = ua.indexOf ("MSIE ");
                        var iframe = document.getElementById('oca-sticker');
                        if (msie > 0) {
                            iframe.contentWindow.document.execCommand('print', false, null);
                        } else {
                            iframe.contentWindow.print();
                        }
                    }
                    function cancelOcaOrder() {
                        //@todo: only show cancel butten if order cancellable
                        if (confirm('{/literal}{l s='This will cancel the current Oca Order' mod='ocaepak'}{literal}')) {
                            window.location.href = 'index.php?controller=AdminOrders&id_order={/literal}{$ocaOrderId|escape:'htmlall':'UTF-8'}{literal}&vieworder&oca-order-cancel=1&token={/literal}{$smarty.get.token|escape:'htmlall':'UTF-8'} {literal}#oca-epak-orders';
                        }
                    }
                </script>{/literal}
            </div>
        </div>
    {elseif $ocaOrderStatus === 'unsubmitted'}
        %ORDER_GENERATOR_GOES_HERE%
    {/if}

</div>