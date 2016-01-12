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

{if !empty($ocaepak_relays)}
    <div id="oca-delivery-options">
         {if $psver < 1.6}
            <h3 class="carrier_title">{l s='Please select your preferred pick-up location:' mod='ocaepak'}</h3>
        {else}
            <p class="carrier_title">{l s='Please select your preferred pick-up location:' mod='ocaepak'}</p>
        {/if}
        <div id="oca-map"></div>
        <div class="radius-input">
            <label for="ocaBranchSelect">{l s='Selected Branch' mod='ocaepak'}:</label>
            <select name="branch" id="ocaBranchSelect" class="form-control">
                {foreach from=$ocaepak_relays key=id_relay_point item=relay}
                    <option value="{$id_relay_point|escape:'htmlall':'UTF-8'}">
                        {$relay.Sucursal|trim|lower|capitalize|escape:'htmlall':'UTF-8'} - {$relay.Calle|trim|lower|capitalize|escape:'htmlall':'UTF-8'} {if !$relay.Numero|is_array}{$relay.Numero|trim|escape:'htmlall':'UTF-8'}{/if}{if $relay.Piso && !$relay.Piso|is_array} {$relay.Piso|trim|lower|capitalize|escape:'htmlall':'UTF-8'}{/if}, {$relay.Localidad|trim|lower|capitalize|escape:'htmlall':'UTF-8'}, {$relay.Provincia|trim|lower|capitalize|escape:'htmlall':'UTF-8'}
                    </option>
                {/foreach}
            </select>
        </div>
    </div>
    <hr />
{/if}

<style type="text/css">
    #oca-map {
        width: 100%;
        height: 320px;
        margin-bottom: 26px;
         }
    #uniform-ocaBranchSelect > span {
        background-color: white;
        border: 1px solid #d6d4d4;
        border-left: 0;
        border-right: 0;
    }
    ..mapInfowindow {
        overflow: hidden;
    }
    .mapInfowindowHeader {
        font-size: 20px;
        margin-bottom: 10px;
    }
    .mapInfowindowBody {
        font-size: 16px;
        overflow: hidden;
    }
    .mapInfowindowFooter {
        text-align: center;
        font-size: 14px;
        margin-top: 10px;
        font-style: italic;
        overflow: hidden;
    }
</style>

<script>
var ocaEpakCallback;
var ocaRelays = JSON.parse('{$ocaepak_relays|@json_encode|escape:'quotes':'UTF-8'}');
var customerAddress = JSON.parse('{$customerAddress|@json_encode|escape:'quotes':'UTF-8'}');
var customerStateCode = '{$customerStateCode|escape:'quotes':'UTF-8'}';
var ocaRelayUrl = '{$link->getModuleLink($ocaepak_name, 'relay', [], $force_ssl)|escape:'quotes':'UTF-8'}';
var ocaRelayCarriers = JSON.parse({$relayed_carriers|@json_encode|escape:'quotes':'UTF-8'});
var ocaSelectedRelay = {if $ocaepak_selected_relay}{$ocaepak_selected_relay|escape:'quotes':'UTF-8'}{else}null{/if};
var ocaRelayAuto = {if $ocaepak_relay_auto}{$ocaepak_relay_auto|escape:'quotes':'UTF-8'}{else}null{/if};
{literal}
(function () {
    var map, home, currentMarkerIndex, previousRelay;
    var initialized = false;
    var markers = [];
    var iconSelected = '//maps.google.com/mapfiles/kml/pal3/icon20.png';
    var iconUnselected = '//maps.google.com/mapfiles/kml/pal3/icon28.png';

    ocaEpakCallback = function () {
        $('input.delivery_option_radio:not(.relay_option_radio)').change(carrier_selection);
        carrier_selection();
    };
    $(document).ready(function() {
        if (typeof google === 'undefined') {
            $.ajax({
                url: '//maps.google.com/maps/api/js?region=AR&callback=ocaEpakCallback',
                dataType: 'script',
                async: false
            });
        } else {
            ocaEpakCallback();
        }

    });

    function initialize() {
        var latlng = new google.maps.LatLng('-34.6033', '-58.3817');
        map = new google.maps.Map(document.getElementById('oca-map'), {
            center: latlng,
            zoom: 10,
            mapTypeId: 'roadmap',
            mapTypeControlOptions: {style: google.maps.MapTypeControlStyle.DROPDOWN_MENU}
        });
        infoWindow = new google.maps.InfoWindow();
        initMarkers();
        $('#ocaBranchSelect').change(function (event) {
            var idx = $(this).val();
            assignBranch(idx);
            var latlng = new google.maps.LatLng(ocaRelays[idx]['Latitud'], ocaRelays[idx]['Longitud']);
            map.setCenter(latlng);
        })
    }

    function carrier_selection(e)
    {
        var $elem = e ? $(e.target) : $('input.delivery_option_radio:checked').first();
        $('#oca-delivery-options').hide();
        if ($elem.prop('checked')) {
            var intersects = $elem.val().split(',').filter(function(n) {
                return ocaRelayCarriers.indexOf(n) !== -1
            });
            if (intersects.length && intersects[0]) {
                $('#oca-delivery-options').show();
                if (!initialized)
                    initialize();
            }
        }
    }

    function relay_selection(id)
    {
        $.ajax({
            type: "POST",
            url: ocaRelayUrl,
            data: { distribution_center_id: id, auto: ocaRelayAuto },
            context: document.body
        });
        if (ocaRelayAuto)
            ocaRelayAuto = 0;
    }

    function initMarkers() {
        var addressText = customerAddress.address1 + /*' ' + customerAddress.address2 + */' ' + customerAddress.city;
        function requestFail () {
            if (postcodeParam.length > 0) {
                postcodeParam = '';
                $.getJSON('//maps.googleapis.com/maps/api/geocode/json?region=ar&address=' + encodeURIComponent(addressText) + '&components=country:AR', null, requestSuccess).fail(requestFail);
            } else {
                var assigned = previousRelay ? previousRelay : 0;
                assignBranch(assigned, previousRelay);
                var latlng0 = new google.maps.LatLng(ocaRelays[assigned]['Latitud'], ocaRelays[assigned]['Longitud']);
                map.setCenter(latlng0);
            }
        }
        function requestSuccess (data) {
            if (!data.results || !data.results.length) {
                requestFail();
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
                var contentString = '<div class="mapInfowindow">'+
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
                if (previousRelay && !ocaRelayAuto)
                    assignBranch(previousRelay, true);
                else
                    assignClosestBranch();
            }
        }
        var postcodeParam = '';
        if (customerStateCode.length) {
            postcodeParam = '|postal_code:' + customerStateCode + customerAddress.postcode.replace( /^\D+/g, '');
        } else if (customerAddress.postcode.match(/^[A-Za-z]/) != null) {
            postcodeParam = '|postal_code:' +customerAddress.postcode;
        }
        $.getJSON('//maps.googleapis.com/maps/api/geocode/json?region=ar&address=' + encodeURIComponent(addressText) + '&components=country:AR' + postcodeParam, null, requestSuccess).fail(requestFail);
        for (var i=0; i<ocaRelays.length; i++) {
            if (!ocaRelays[i]['Latitud'] || !ocaRelays[i]['Longitud'])
                continue;
            var latlng = new google.maps.LatLng(ocaRelays[i]['Latitud'], ocaRelays[i]['Longitud']);
            markers[i] = new google.maps.Marker({
                position: latlng,
                map: map,
                icon: iconUnselected
            });
            if (ocaSelectedRelay && (ocaSelectedRelay == ocaRelays[i]['IdCentroImposicion'])) {
                previousRelay = i;
            }
            markers[i].ocaRelayIndex = i;
            markers[i].ocaRelayId = ocaRelays[i]['IdCentroImposicion'];
            markers[i].ocaRelayAddress = toTitleCase(ocaRelays[i]['Calle'].trim()+' '+((ocaRelays[i]['Numero'] && (typeof ocaRelays[i]['Numero'] === 'string' || ocaRelays[i]['Numero'] instanceof String)) ? ocaRelays[i]['Numero'].trim() : '')+((ocaRelays[i]['Piso'] && (typeof ocaRelays[i]['Piso'] === 'string' || ocaRelays[i]['Piso'] instanceof String)) ? ' '+ocaRelays[i]['Piso'].trim() : '')+', '+ocaRelays[i]['Localidad'].trim()+', '+ocaRelays[i]['Provincia'].trim());
            markers[i].ocaRelayDescription = toTitleCase(ocaRelays[i]['Sucursal']);
            markers[i].assignedInfowindow = new google.maps.InfoWindow({
                content: formatInfowindow(ocaRelays[i]['Sucursal'], markers[i].ocaRelayAddress, (previousRelay == i))
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
        var closest = 0;
        if (home) {
            var R = 6371; // radius of earth in km
            var distances = [R*4];
            for (var i=0; i<ocaRelays.length; i++) {
                var mlat = ocaRelays[i]['Latitud'];
                var mlng = ocaRelays[i]['Longitud'];
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
        }
        assignBranch(closest);
    }

    function assignBranch(index, previous) {
        if (currentMarkerIndex !== undefined) {
            markers[currentMarkerIndex].setIcon(iconUnselected);
            markers[currentMarkerIndex].assignedInfowindow.setContent(formatInfowindow(markers[currentMarkerIndex].ocaRelayDescription, markers[currentMarkerIndex].ocaRelayAddress, false));
        }
        markers[index].setIcon(iconSelected);
        markers[index].assignedInfowindow.setContent(formatInfowindow(markers[index].ocaRelayDescription, markers[index].ocaRelayAddress, true));
        currentMarkerIndex = index;
        $('#ocaBranchSelect').val(index);
        $('#uniform-ocaBranchSelect>span').text($("#ocaBranchSelect option[value='"+index+"']").text());
        if (!previous)
            relay_selection(markers[index].ocaRelayId);
    }

    function formatInfowindow(desc, address, selected) {
        var selectionText = (selected ? 'seleccionada' : 'click para seleccionar');
        return '<div class="mapInfowindow">'+
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