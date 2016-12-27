/**
 * Oca e-Pak Module for Prestashop
 * @copyright Copyright (c) 2012-2017, Rinku Kazeno
 * @file-version 1.4.3
 */

if ((typeof ocaRelayCarriers !== 'undefined') && (typeof customerAddress !== 'undefined')) {
    $.each(ocaRelayCarriers, function (idx, id_carrier) {
        var map, home, currentMarkerIndex, previousRelay;
        var markers = [];
        var iconSelected = '//maps.google.com/mapfiles/kml/pal3/icon20.png';
        var iconUnselected = '//maps.google.com/mapfiles/kml/pal3/icon28.png';

        $(document).ready(function() {
            $('input[id^="delivery_option_"]').change(carrier_selection);
            if (!$('#ocaBranchSelect-'+id_carrier+' option').length) {
                var $select = $('#ocaBranchSelect-'+id_carrier);
                $.each(ocaRelays[customerAddress.postcode], function (ind, relay) {
                    $select.append($("<option>").attr('value',ind).text(
                        toTitleCase(relay.Sucursal)+' - '+toTitleCase(relay.Calle)+' '+toTitleCase(relay.Numero)+' '+toTitleCase(relay.Piso)+', '+toTitleCase(relay.Localidad)+', '+toTitleCase(relay.Provincia)
                    ));
                });
            }
            if (!$('#oca-map-'+id_carrier).html().length)
                initialize();
        });

        function initialize() {
            var latlng = new google.maps.LatLng('-34.6033', '-58.3817');
            map = new google.maps.Map(document.getElementById('oca-map-'+id_carrier), {
                center: latlng,
                zoom: 10,
                minZoom: 4,
                maxZoom: 15,
                mapTypeId: 'roadmap',
                mapTypeControlOptions: {style: google.maps.MapTypeControlStyle.DROPDOWN_MENU}
            });
            infoWindow = new google.maps.InfoWindow();
            initMarkers();
            $('#ocaBranchSelect-'+id_carrier).change(function (event) {
                var idx = $(this).val();
                assignBranch(idx);
                var latlng = new google.maps.LatLng(ocaRelays[customerAddress.postcode][idx]['Latitud'], ocaRelays[customerAddress.postcode][idx]['Longitud']);
                map.setCenter(latlng);
            });

        }

        function carrier_selection(e)
        {
            var $elem = $(this);
            if ($elem.attr('id') !== 'delivery_option_'+id_carrier) {
                return null;
            } else
                $('.delivery-option .carrier-extra-content').hide();
            if ($elem.prop('checked')) {
                $elem.closest('.delivery-option').find('.carrier-extra-content').show();
                relay_selection(markers[currentMarkerIndex].ocaRelayId);
                if (map) {
                    google.maps.event.trigger(map, 'resize');
                    var bounds = new google.maps.LatLngBounds();
                    bounds.extend(new google.maps.LatLng(home.lat, home.lng));
                    bounds.extend(markers[currentMarkerIndex].getPosition());
                    map.fitBounds(bounds);
                }
            }
            return null;
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
                    $.getJSON('//maps.googleapis.com/maps/api/geocode/json?region=ar&key='+ocaGmapsKey+'&address=' + encodeURIComponent(addressText) + '&components=country:AR', null, requestSuccess).fail(requestFail);
                } else {
                    var assigned = previousRelay ? previousRelay : 0;
                    assignBranch(assigned, previousRelay);
                    var latlng0 = new google.maps.LatLng(ocaRelays[customerAddress.postcode][assigned]['Latitud'], ocaRelays[customerAddress.postcode][assigned]['Longitud']);
                    map.setCenter(latlng0);
                }
            }
            function requestSuccess (data) {
                if (!data.results || !data.results.length) {
                    requestFail();
                } else {
                    var image = new google.maps.MarkerImage('//maps.google.com/mapfiles/ms/icons/homegardenbusiness.png');
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
                    if ((typeof previousRelay !== 'undefined') && !ocaRelayAuto)
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
            for (var i=0; i<ocaRelays[customerAddress.postcode].length; i++) {
                if (!ocaRelays[customerAddress.postcode][i]['Latitud'] || !ocaRelays[customerAddress.postcode][i]['Longitud'])
                    continue;
                var latlng = new google.maps.LatLng(ocaRelays[customerAddress.postcode][i]['Latitud'], ocaRelays[customerAddress.postcode][i]['Longitud']);
                markers[i] = new google.maps.Marker({
                    position: latlng,
                    map: map,
                    icon: iconUnselected
                });
                if (ocaSelectedRelay && (ocaSelectedRelay == ocaRelays[customerAddress.postcode][i]['IdCentroImposicion'])) {
                    previousRelay = i;
                }
                markers[i].ocaRelayIndex = i;
                markers[i].ocaRelayId = ocaRelays[customerAddress.postcode][i]['IdCentroImposicion'];
                markers[i].ocaRelayAddress = toTitleCase(ocaRelays[customerAddress.postcode][i]['Calle'].trim()+' '+((ocaRelays[customerAddress.postcode][i]['Numero'] && (typeof ocaRelays[customerAddress.postcode][i]['Numero'] === 'string' || ocaRelays[customerAddress.postcode][i]['Numero'] instanceof String)) ? ocaRelays[customerAddress.postcode][i]['Numero'].trim() : '')+((ocaRelays[customerAddress.postcode][i]['Piso'] && (typeof ocaRelays[customerAddress.postcode][i]['Piso'] === 'string' || ocaRelays[customerAddress.postcode][i]['Piso'] instanceof String)) ? ' '+ocaRelays[customerAddress.postcode][i]['Piso'].trim() : '')+', '+ocaRelays[customerAddress.postcode][i]['Localidad'].trim()+', '+ocaRelays[customerAddress.postcode][i]['Provincia'].trim());
                markers[i].ocaRelayDescription = toTitleCase(ocaRelays[customerAddress.postcode][i]['Sucursal']);
                markers[i].assignedInfowindow = new google.maps.InfoWindow({
                    content: formatInfowindow(ocaRelays[customerAddress.postcode][i]['Sucursal'], markers[i].ocaRelayAddress, (previousRelay == i))
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
            $.getJSON('//maps.googleapis.com/maps/api/geocode/json?region=ar&key='+ocaGmapsKey+'&address=' + encodeURIComponent(addressText) + '&components=country:AR' + postcodeParam, null, requestSuccess).fail(requestFail);
        }

        function assignClosestBranch() {
            function rad(x) {return x*Math.PI/180;}
            var closest = 0;
            if (home) {
                var R = 6371; // radius of earth in km
                var distances = [R*4];
                for (var i=0; i<ocaRelays[customerAddress.postcode].length; i++) {
                    var mlat = ocaRelays[customerAddress.postcode][i]['Latitud'];
                    var mlng = ocaRelays[customerAddress.postcode][i]['Longitud'];
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
            $('#ocaBranchSelect-'+id_carrier).val(index);
            $('#uniform-ocaBranchSelect-'+id_carrier+'>span').text($("#ocaBranchSelect-"+id_carrier+" option[value='"+index+"']").text());
            if (!previous)
                relay_selection(markers[index].ocaRelayId);
            var bounds = new google.maps.LatLngBounds();
            bounds.extend(new google.maps.LatLng(home.lat, home.lng));
            bounds.extend(markers[index].getPosition());
            map.fitBounds(bounds);
        }

        function formatInfowindow(desc, address, selected) {
            var selectionText = (selected ? 'seleccionada' : 'click para seleccionar');
            return '<div class="mapInfowindow">'+
                '<h1 class="mapInfowindowHeader">OCA Sucursal: '+desc+'</h1>'+
                '<div class="mapInfowindowBody">'+address+'</div>' +
                '<div class="mapInfowindowFooter">('+selectionText+')</div>'+
                '</div>';
        }

        function toTitleCase(str) {
            return str.replace(/\w\S*/g, function(txt){return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();});
        }

    });
}