{if !empty($ocaepak_relays)}
    <div>
        <p class="carrier_title">{l s='Please select your preferred pick-up location:' mod='ocaepak'}</p>
        <table id="delivery-options" class="resume table table-bordered">
            <thead>
            <tr>
                <th></th>
                <th>{l s='OCA Branch' mod='ocaepak'}</th>
                <th>{l s='Address' mod='ocaepak'}</th>
                <th>{l s='Telephone' mod='ocaepak'}</th>
            </tr>
            </thead>
            <tbody>
            {foreach from=$ocaepak_relays key=id_relay_point item=relay}
                <tr>
                    <td class="delivery_option_radio">
                        <div class="radio"><span><input class="relay_option_radio" type="radio" name="relay_point" value="{$relay.idCentroImposicion|trim|urlencode}" {if ($relay.idCentroImposicion|trim eq $ocaepak_selected_relay) or (!$ocaepak_selected_relay and ($id_relay_point eq 0))}checked="checked"{/if}/></span></div>
                    </td>
                    <td>
                        {$relay.Descripcion|trim|htmlentities}
                    </td>
                    <td>
                        {$relay.Calle|trim|htmlentities} {$relay.Numero|trim|htmlentities} {$relay.Torre|trim|htmlentities} {$relay.Piso|trim|htmlentities} {$relay.Depto|trim|htmlentities}, {$relay.Localidad|trim|htmlentities}, {$relay.Provincia|trim|htmlentities} {$relay.CodigoPostal|trim|htmlentities}
                    </td>
                    <td>
                        {$relay.Telefono|trim|htmlentities}
                    </td>
                </tr>
            {/foreach}
            </tbody>
        </table>
    </div>
{/if}

<script>
    (function() {
        var relayed_carriers = {$relayed_carriers};
        var relay_url = '{$link->getModuleLink($ocaepak_name, 'relay')}';
        $(document).ready(function() {
            //mymodcarrier_load();
            $('.hook_extracarrier').addClass('delivery_option');    {* inherit table style of carrier options *}
            $('input.relay_option_radio').change(relay_selection);
            //$('input.relay_option_radio:checked').change();
            relay_selection();
            $('input.delivery_option_radio').change(carrier_selection);
            //$('input.delivery_option_radio:checked').change();
            carrier_selection();
        });

        function carrier_selection(e)
        {
            //**/console.log(e);
            var $elem = e ? $(e.target) : $('input.delivery_option_radio:checked').first();
            $('#delivery-options').hide();
            if ($elem.prop('checked')) {
                var intersects = $elem.val().split(',').filter(function(n) {
                    return relayed_carriers.indexOf(n) !== -1
                });
                if (intersects.length)
                    $('#delivery-options').show();
            }
        }

        function relay_selection(e)
        {
            //**/console.log(e);
            var $elem = e ? $(e.target) : $('input.relay_option_radio:checked').first();
            if ($elem.prop('checked')) {
                //**/console.log($elem.val());
                //return true;
                $.ajax({
                    type: "POST",
                    url: relay_url,
                    data: { distribution_center_id: $elem.val() },
                    context: document.body
                });
            }
        }
    })();
</script>