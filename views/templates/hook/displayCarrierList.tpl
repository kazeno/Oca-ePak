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

{*
{if !empty($ocaepak_relays)}
    {if $psver < 1.6}
        <div id="delivery-options" class="delivery_options">
            <h3 class="carrier_title">{l s='Please select your preferred pick-up location:' mod='ocaepak'}</h3>
            <div>
                <div class="delivery_option item">
                    <span class="delivery_option_radio"></span>
                    <label>
                        <table class="resume">
                            <tr>
                                <td>
                                    <div class="delivery_option_title">{l s='OCA Branch' mod='ocaepak'}</div>
                                </td>
                                <td>
                                    <div class="delivery_option_title">{l s='Address' mod='ocaepak'}</div>
                                </td>
                                <td>
                                    <div class="delivery_option_title">{l s='Locality' mod='ocaepak'}</div>
                                </td>
                                <td>
                                    <div class="delivery_option_title">{l s='Province' mod='ocaepak'}</div>
                                </td>
                                <td>
                                    <div class="delivery_option_title">{l s='Telephone' mod='ocaepak'}</div>
                                </td>
                            </tr>
                        </table>
                    </label>
                </div>
                {foreach from=$ocaepak_relays key=id_relay_point item=relay}
                    <div class="delivery_option {cycle values="alternate_item,item"}">
                        <input class="relay_option_radio delivery_option_radio" type="radio" name="relay_point" value="{$relay.idCentroImposicion|trim|escape:'quotes':'UTF-8'}" {if ($relay.idCentroImposicion|trim eq $ocaepak_selected_relay) or (!$ocaepak_selected_relay and ($id_relay_point eq 0))}checked="checked"{/if}/>
                        <label>
                            <table class="resume">
                                <tr>
                                    <td class="delivery_option_logo">
                                        {$relay.Descripcion|trim|lower|capitalize|escape:'htmlall':'UTF-8'}
                                    </td>
                                    <td>
                                        {$relay.Calle|trim|lower|capitalize|escape:'htmlall':'UTF-8'} {$relay.Numero|trim|escape:'htmlall':'UTF-8'}
                                        {if !$relay.Piso|is_array}{$relay.Piso|trim|lower|capitalize|escape:'htmlall':'UTF-8'}{/if}
                                    </td>
                                    <td>
                                        {$relay.Localidad|trim|lower|capitalize|escape:'htmlall':'UTF-8'}
                                    </td>
                                    <td>
                                        {$relay.Provincia|trim|lower|capitalize|escape:'htmlall':'UTF-8'}
                                    </td>
                                    <td>
                                        {$relay.Telefono|trim|escape:'htmlall':'UTF-8'}
                                    </td>
                                </tr>
                            </table>
                        </label>
                    </div>
                {/foreach}
            </div>
        </div>
        <style type="text/css">
            .hook_extracarrier {
                padding: 0;
            }
        </style>
    {else}
        <div id="delivery-options">
            <p class="carrier_title">{l s='Please select your preferred pick-up location:' mod='ocaepak'}</p>
            <table class="resume table table-bordered">
                <thead>
                <tr>
                    <th></th>
                    <th>{l s='OCA Branch' mod='ocaepak'}</th>
                    <th>{l s='Address' mod='ocaepak'}</th>
                    <th>{l s='Locality' mod='ocaepak'}</th>
                    <th>{l s='Province' mod='ocaepak'}</th>
                    <th>{l s='Telephone' mod='ocaepak'}</th>
                </tr>
                </thead>
                <tbody>
                {foreach from=$ocaepak_relays key=id_relay_point item=relay}
                    <tr>
                        <td class="delivery_option_radio">
                            <div class="radio"><span><input class="relay_option_radio" type="radio" name="relay_point" value="{$relay.idCentroImposicion|trim|escape:'quotes':'UTF-8'}" {if ($relay.idCentroImposicion|trim eq $ocaepak_selected_relay) or (!$ocaepak_selected_relay and ($id_relay_point eq 0))}checked="checked"{/if}/></span></div>
                        </td>
                        <td>
                            {$relay.Descripcion|trim|lower|capitalize|escape:'htmlall':'UTF-8'}
                        </td>
                        <td>
                            {$relay.Calle|trim|lower|capitalize|escape:'htmlall':'UTF-8'} {$relay.Numero|trim|escape:'htmlall':'UTF-8'}
                            {if !$relay.Piso|is_array}{$relay.Piso|trim|lower|capitalize|escape:'htmlall':'UTF-8'}{/if}
                        </td>
                        <td>
                            {$relay.Localidad|trim|lower|capitalize|escape:'htmlall':'UTF-8'}
                        </td>
                        <td>
                            {$relay.Descripcion1|trim|lower|capitalize|escape:'htmlall':'UTF-8'}
                        </td>
                        <td>
                            {$relay.Telefono|trim|escape:'htmlall':'UTF-8'}
                        </td>
                    </tr>
                {/foreach}
                </tbody>
            </table>
        </div>
    {/if}
{/if}

<script>
    (function() {
        var relayed_carriers = {$relayed_carriers|escape:'quotes':'UTF-8'};
        var relay_url = '{$link->getModuleLink($ocaepak_name, 'relay', [], $force_ssl)|escape:'quotes':'UTF-8'}';
        $(document).ready(function() {
            $('.hook_extracarrier').addClass('delivery_option');    { * inherit table style of carrier options * }
            $('input.relay_option_radio').change(relay_selection);
            relay_selection();
            $('input.delivery_option_radio:not(.relay_option_radio)').change(carrier_selection);
            carrier_selection();
        });

        function carrier_selection(e)
        {
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
            var $elem = e ? $(e.target) : $('input.relay_option_radio:checked').first();
            if ($elem.prop('checked')) {
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
*}



{if !empty($ocaepak_relays)}
    <div id="delivery-options">
         {if $psver < 1.6}
            <h3 class="carrier_title">{l s='Please select your preferred pick-up location:' mod='ocaepak'}</h3>
        {else}
            <p class="carrier_title">{l s='Please select your preferred pick-up location:' mod='ocaepak'}</p>
        {/if}
        <div id="map"></div>
        <div class="radius-input">
            <label for="branchSelect">{l s='Sucursal Seleccionada'}:</label>
            <select name="branch" id="branchSelect" class="form-control">
                {foreach from=$ocaepak_relays key=id_relay_point item=relay}
                    <option value="{$id_relay_point|escape:'htmlall':'UTF-8'}">
                        {$relay.Descripcion|trim|lower|capitalize|escape:'htmlall':'UTF-8'} - {$relay.Calle|trim|lower|capitalize|escape:'htmlall':'UTF-8'} {$relay.Numero|trim|escape:'htmlall':'UTF-8'}{if $relay.Piso} {$relay.Piso|trim|lower|capitalize|escape:'htmlall':'UTF-8'}{/if}, {$relay.Localidad|trim|lower|capitalize|escape:'htmlall':'UTF-8'}, {$relay.Descripcion1|trim|lower|capitalize|escape:'htmlall':'UTF-8'}
                    </option>
                {/foreach}
            </select>
        </div>
    </div>
    <hr />
{/if}

<style type="text/css">
    #map {
        width: 100%;
        height: 320px;
        margin-bottom: 26px;
         }
    #uniform-branchSelect > span {
        background-color: white;
        border: 1px solid #d6d4d4;
        border-left: 0;
        border-right: 0;
    }
    .mapInfowindowBody {
        font-size: 16px;
    }
    .mapInfowindowFooter {
        text-align: center;
        font-size: 14px;
        margin-top: 10px;
        font-style: italic;
    }
</style>
{strip}
    {addJsDef ocaRelays=$ocaepak_relays}
    {addJsDef customerAddress=$customerAddress}
    {addJsDef ocaRelayUrl=$link->getModuleLink($ocaepak_name, 'relay', [], $force_ssl)}
    {addJsDef ocaRelayCarriers=$relayed_carriers}
    {addJsDef ocaSelectedRelay=$ocaepak_selected_relay}
{/strip}

{literal}
<script type="text/javascript">
(function () {
    var map, home, currentMarkerIndex, previousRelay;
    var initialized = false;
    var markers = [];
    var iconSelected = 'http://maps.google.com/mapfiles/kml/pal3/icon20.png';
    var iconUnselected = 'http://maps.google.com/mapfiles/kml/pal3/icon28.png';
    $(document).ready(function() {
        $('input.delivery_option_radio:not(.relay_option_radio)').change(carrier_selection);
        carrier_selection();
    });

    function initialize() {
        var latlng = new google.maps.LatLng('-34.6033', '-58.3817');
        map = new google.maps.Map(document.getElementById('map'), {
            center: latlng,
            zoom: 10,
            mapTypeId: 'roadmap',
            mapTypeControlOptions: {style: google.maps.MapTypeControlStyle.DROPDOWN_MENU}
        });
        infoWindow = new google.maps.InfoWindow();
        initMarkers();
        $('#branchSelect').change(function (event) {
            var idx = $(this).val();
            //**/console.log('selection change: '+idx)
            assignBranch(idx);
            var latlng = new google.maps.LatLng(ocaRelays[idx]['lat'], ocaRelays[idx]['lng']);
            map.setCenter(latlng);

        })
    }

    function carrier_selection(e)
    {
        var $elem = e ? $(e.target) : $('input.delivery_option_radio:checked').first();
        $('#delivery-options').hide();
        if ($elem.prop('checked')) {
            var intersects = $elem.val().split(',').filter(function(n) {
                //**/console.log(n)
                //**/console.log(ocaRelayCarriers.indexOf(n))
                return ocaRelayCarriers.indexOf(n) !== -1
            });
            if (intersects.length && intersects[0]) {
                $('#delivery-options').show();
                if (!initialized)
                    initialize();
            }
        }
    }

    function relay_selection(id)
    {
        //**/console.log('selected: '+id)
        $.ajax({
            type: "POST",
            url: ocaRelayUrl,
            data: { distribution_center_id: id },
            context: document.body
        });
    }

    function initMarkers() {
        var addressText = customerAddress.address1 + ' ' + customerAddress.address2 + ' ' + customerAddress.city;
        $.getJSON('http://maps.googleapis.com/maps/api/geocode/json?sensor=false&region=ar&address=' + encodeURIComponent(addressText), null, function (data) {
            //**/console.log(data)
            if (!data.results || !data.results.length) {
                var assigned = previousRelay ? previousRelay : 0;
                assignBranch(assigned, previousRelay);
                var latlng0 = new google.maps.LatLng(ocaRelays[assigned]['lat'], ocaRelays[assigned]['lng']);
                map.setCenter(latlng0);
            } else {
                var image = new google.maps.MarkerImage('http://maps.google.com/mapfiles/ms/icons/homegardenbusiness.png');
                home = data.results[0].geometry.location;
                var latlng = new google.maps.LatLng(home.lat, home.lng);
                var marker = new google.maps.Marker({
                    position: latlng,
                    map: map,
                    icon: image
                });
                map.setCenter(latlng);
                var addressText2 = customerAddress.address1+', '+(customerAddress.address2 ? customerAddress.address2+', ' : '')+customerAddress.city;
                var contentString = '<div id="content">'+
                        '<h1 class="mapInfowindowHeader">'+customerAddress.alias+'</h1>'+
                        '<div class="mapInfowindowBody">'+addressText2+'</div>'+
                        '</div>';

                var infowindow = new google.maps.InfoWindow({
                    content: contentString
                });

                google.maps.event.addListener(marker, 'mouseover', function () {
                    infowindow.open(map, marker);
                });
                google.maps.event.addListener(marker, 'mouseout', function () {
                    infowindow.close();
                });

                if (previousRelay)
                    assignBranch(previousRelay, true);
                else
                    assignClosestBranch();
            }
        });
        for (var i=0; i<ocaRelays.length; i++) {
            if (!ocaRelays[i]['lat'] || !ocaRelays[i]['lng'])
                continue;
            var latlng = new google.maps.LatLng(ocaRelays[i]['lat'], ocaRelays[i]['lng']);
            markers[i] = new google.maps.Marker({
                position: latlng,
                map: map,
                icon: iconUnselected
            });
            if (ocaSelectedRelay && (ocaSelectedRelay == ocaRelays[i]['idCentroImposicion'])) {
                previousRelay = i;
                //**/console.log('prevous relay: '+previousRelay)
            }

            markers[i].ocaRelayIndex = i;
            markers[i].ocaRelayId = ocaRelays[i]['idCentroImposicion'];
            markers[i].ocaRelayAddress = toTitleCase(ocaRelays[i]['Calle'].trim()+' '+ocaRelays[i]['Numero'].trim()+(ocaRelays[i]['Piso'] ? ' '+ocaRelays[i]['Piso'].trim() : '')+', '+ocaRelays[i]['Localidad'].trim()+', '+ocaRelays[i]['Descripcion1'].trim());
            markers[i].ocaRelayDescription = toTitleCase(ocaRelays[i]['Descripcion']);
            markers[i].assignedInfowindow = new google.maps.InfoWindow({
                content: formatInfowindow(ocaRelays[i]['Descripcion'], markers[i].ocaRelayAddress, (previousRelay == i))
            });
            (function (i) {
                google.maps.event.addListener(markers[i], 'mouseover', function () {
                    markers[i].assignedInfowindow.open(map, markers[i]);
                    markers[i].setIcon(iconSelected);
                });
                google.maps.event.addListener(markers[i], 'mouseout', function () {
                    markers[i].assignedInfowindow.close();
                    if (i != currentMarkerIndex)
                        markers[i].setIcon(iconUnselected);
                });
                google.maps.event.addListener(markers[i], 'click', function () {
                    assignBranch(i);
                });
            })(i);
        }
    }

    function assignClosestBranch() {
        function rad(x) {return x*Math.PI/180;}
        var closestMarker;
        var closest = 0;
        if (home) {
            var R = 6371; // radius of earth in km
            var distances = [R*4];
            for (var i=0; i<ocaRelays.length; i++) {
                var mlat = ocaRelays[i]['lat'];
                var mlng = ocaRelays[i]['lng'];
                var dLat  = rad(mlat - home.lat);
                var dLong = rad(mlng - home.lng);
                var a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                        Math.cos(rad(home.lat)) * Math.cos(rad(home.lat)) * Math.sin(dLong/2) * Math.sin(dLong/2);
                var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
                var d = R * c;
                distances[i] = d;
                if (d < distances[closest])
                    closest = i;
            }
            closestMarker = ocaRelays[closest];

        } else {
            closestMarker = ocaRelays[0];
        }
        //**/console.log(closestMarker);
        assignBranch(closest);
    }

    function assignBranch(index, previous) {
        //**/console.log(index);
        //**/console.log(currentMarkerIndex);
        if (currentMarkerIndex !== undefined) {
            markers[currentMarkerIndex].setIcon(iconUnselected);
            markers[currentMarkerIndex].assignedInfowindow.setContent(formatInfowindow(markers[currentMarkerIndex].ocaRelayDescription, markers[currentMarkerIndex].ocaRelayAddress, false));
        }
        markers[index].setIcon(iconSelected);
        //**/console.log(markers[index])
        markers[index].assignedInfowindow.setContent(formatInfowindow(markers[index].ocaRelayDescription, markers[index].ocaRelayAddress, true));
        currentMarkerIndex = index;
        $('#branchSelect').val(index);
        $('#uniform-branchSelect>span').text($("#branchSelect option[value='"+index+"']").text());
        if (!previous)
            relay_selection(markers[index].ocaRelayId);
    }

    function formatInfowindow(desc, address, selected) {
        var selectionText = (selected ? 'seleccionada' : 'click para seleccionar');
        return '<div id="content">'+
        '<h1 class="mapInfowindowHeader">OCA Sucursal: '+desc+'</h1>'+
        '<div class="mapInfowindowBody">'+address+'</div>' +
        '<div class="mapInfowindowFooter">('+selectionText+')</div>'+
        '</div>';
    }

    function toTitleCase(str)
    {
        return str.replace(/\w\S*/g, function(txt){return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();});
    }

})();
</script>
{/literal}