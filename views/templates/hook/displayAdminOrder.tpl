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