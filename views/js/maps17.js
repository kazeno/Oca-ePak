/**
 * Oca e-Pak Module for Prestashop
 * @copyright Copyright (c) 2012-2017, Rinku Kazeno
 * @file-version 1.5
 */

if ((typeof ocaRelayCarriers !== 'undefined') && (typeof customerAddress !== 'undefined')) {
    $.each(ocaRelayCarriers, function (idx, id_carrier) {
        var map, home, currentMarkerIndex, previousRelay, relayAddressIndex, $dataContainer, showPerState;
        var markers = [];
        var iconSelected = '//maps.google.com/mapfiles/kml/pal3/icon20.png';
        var iconUnselected = '//maps.google.com/mapfiles/kml/pal3/icon28.png';
        var windowVisible = [];
        var ocaStateRelays = [];

        $(document).ready(function() {
            $dataContainer = $('#content');
            if (typeof relayAddressIndex === 'undefined') {
                if ((typeof ocaBranchSelType !== 'undefined') && (ocaBranchSelType == '1')) {
                    relayAddressIndex = customerAddress.postcode;
                    showPerState = false;
                } else {
                    relayAddressIndex = 0;
                    showPerState = true;
                }
            }
            ocaStateRelays[0] = ocaRelays[relayAddressIndex];
            $('input[id^="delivery_option_"]').on('change.ocaepak', carrier_selection);
            if (!$('#oca-map-'+id_carrier).html().length && ((idx in windowVisible) && windowVisible[idx]))
                initialize();
            else
                relay_selection($dataContainer.data('ocaBranch') || ocaSelectedRelay);
            if (!$('#ocaBranchSelect-'+id_carrier+' option').length && (typeof customerAddress !== 'undefined') && (relayAddressIndex in ocaRelays)) {
                loadRelayList();
            }
            carrier_selection();
        });

        function loadRelayList() {
            var $select = $('#ocaBranchSelect-'+id_carrier);
            $select.find('option').remove();
            $.each(ocaStateRelays[0], function (i, relay) {
                $select.append($("<option>").attr('value', ocaStateRelays[0][i]['IdCentroImposicion']).text(
                    toTitleCase(relay.Sucursal)+' - '+toTitleCase(relay.Calle)+' '+toTitleCase(relay.Numero)+' '+toTitleCase(relay.Piso)+', '+toTitleCase(relay.Localidad)
                ));
            });

            $('#oca-delivery-options-'+id_carrier+' .chosen').trigger('chosen:updated');
        }

        function initialize() {
            var latlng = new google.maps.LatLng('-34.6033', '-58.3817');
            map = new google.maps.Map(document.getElementById('oca-map-' + id_carrier), {
                center: latlng,
                zoom: 10,
                minZoom: 4,
                maxZoom: 15,
                mapTypeId: 'roadmap',
                mapTypeControlOptions: {style: google.maps.MapTypeControlStyle.DROPDOWN_MENU}
            });
            infoWindow = new google.maps.InfoWindow();
            initMarkers();
            if (showPerState) {
                var $stateSelect = $('#ocaStateSelect-' + id_carrier);
                var $branchSelect = $('#ocaBranchSelect-' + id_carrier);
                if ($dataContainer.data('ocaBranch')) {
                    $stateSelect.val(translateStateName(ocaRelays[relayAddressIndex][$dataContainer.data('ocaBranch')].Provincia));
                    $branchSelect.val($dataContainer.data('ocaBranch'));
                } else if (ocaSelectedRelay) {
                    $stateSelect.val(translateStateName(ocaRelays[relayAddressIndex][ocaSelectedRelay].Provincia));
                }
                $branchSelect.chosen({width: '100%'}).on('change.ocaepak', function (e, params) {
                    var idx = $(this).val();
                    assignBranch(idx);
                    var latlng = new google.maps.LatLng(ocaRelays[relayAddressIndex][idx]['Latitud'], ocaRelays[relayAddressIndex][idx]['Longitud']);
                    map.setCenter(latlng);
                });
                $stateSelect.chosen({width: '100%'}).change(changeState).trigger('change', {
                    selected: $stateSelect.val(),
                    load: true
                });
            } else {
                $('#ocaBranchSelect-' + id_carrier).change(function (event) {
                    var idx = $(this).val();
                    assignBranch(idx);
                    var latlng = new google.maps.LatLng(ocaRelays[relayAddressIndex][idx]['Latitud'], ocaRelays[relayAddressIndex][idx]['Longitud']);
                    map.setCenter(latlng);
                });
            }
        }

        function changeState(e, params) {
            params = params || {selected: $('#ocaStateSelect-'+id_carrier).val()};
            if (!params.selected || !params.selected.length)
                return false;
            ocaStateRelays[0] = [];
            var bounds = new google.maps.LatLngBounds();
            $.each(ocaRelays[relayAddressIndex], function (i, relay) {
                if (translateStateName(ocaRelays[relayAddressIndex][i].Provincia) === params.selected) {
                    markers[i].setMap(map);
                    bounds.extend(markers[i].getPosition());
                    ocaStateRelays[0].push(ocaRelays[relayAddressIndex][i]);
                } else {
                    markers[i].setMap(null);
                }
            });
            map.fitBounds(bounds);
            if (!params.load && translateStateName(ocaRelays[relayAddressIndex][$dataContainer.data('ocaBranch')].Provincia) !== params.selected.toLowerCase())
                assignBranch(ocaStateRelays[0][0]['IdCentroImposicion']);

            ocaStateRelays[0].sort(function (a, b) {
                if ((a.Localidad || '').toLowerCase() === (b.Localidad.toLowerCase() || '').toLowerCase()) {
                    return (a.Provincia || '').toLowerCase().localeCompare(b.Provincia.toLowerCase() || '')
                } else
                    return (a.Localidad || '').toLowerCase().localeCompare(b.Localidad.toLowerCase() || '');
            });
            $dataContainer.data('ocaState', params.selected);
            loadRelayList();
        }

        function carrier_selection(e)
        {
            var $elem = e ? $(e.target) : $('#delivery_option_'+id_carrier).first();
            if (!$('#oca-delivery-options-'+id_carrier).length)
                return setTimeout(function () {
                    carrier_selection(e);
                }, 150);
            //var $elem = $(this);
            windowVisible[idx] = 0;
            if ($elem.attr('id') !== 'delivery_option_'+id_carrier) {
                return null;
            } else
                $('.delivery-options .carrier-extra-content').hide();
            if ($elem.prop('checked')) {
                $elem.closest('.delivery-options').find('.carrier-extra-content').show();
                windowVisible[idx] = 1;
                //relay_selection(markers[currentMarkerIndex].ocaRelayId);
                if (map) {
                    google.maps.event.trigger(map, 'resize');
                    var bounds = new google.maps.LatLngBounds();
                    bounds.extend(new google.maps.LatLng(home.lat, home.lng));
                    bounds.extend(markers[currentMarkerIndex].getPosition());
                    map.fitBounds(bounds);
                } else {
                    initialize();
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
            })/*.success(function () {
                if (!id || ($dataContainer.data('ocaBranch') == id))
                    updateAddressSelection();
                });*/
            if (ocaRelayAuto)
                ocaRelayAuto = 0;
        }

        function initMarkers() {
            var addressText = customerAddress.address1 + /*' ' + customerAddress.address2 + */' ' + customerAddress.city;
            function requestFail () {
                if (postcodeParam.length > 0) {
                    postcodeParam = '';
                    $.getJSON('https://maps.googleapis.com/maps/api/geocode/json?region=ar&key='+ocaGmapsKey+'&address=' + encodeURIComponent(addressText) + '&components=country:AR', null, requestSuccess).fail(requestFail);
                } else {
                    var assigned = previousRelay ? previousRelay : 0;
                    assignBranch(assigned, previousRelay);
                    var latlng0 = new google.maps.LatLng(ocaRelays[relayAddressIndex][assigned]['Latitud'], ocaRelays[relayAddressIndex][assigned]['Longitud']);
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
                    //map.setCenter(latlng);
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
            $.each(ocaRelays[relayAddressIndex], function (i, relay) {
                if (!ocaRelays[relayAddressIndex][i]['Latitud'] || !ocaRelays[relayAddressIndex][i]['Longitud'])
                    return true;
                var latlng = new google.maps.LatLng(ocaRelays[relayAddressIndex][i]['Latitud'], ocaRelays[relayAddressIndex][i]['Longitud']);
                markers[i] = new google.maps.Marker({
                    position: latlng,
                    map: map,
                    icon: iconUnselected
                });
                if (ocaSelectedRelay && (ocaSelectedRelay == ocaRelays[relayAddressIndex][i]['IdCentroImposicion'])) {
                    previousRelay = ocaRelays[relayAddressIndex][i]['IdCentroImposicion'];
                }
                markers[i].ocaRelayIndex = ocaRelays[relayAddressIndex][i]['IdCentroImposicion'];
                markers[i].ocaRelayId = ocaRelays[relayAddressIndex][i]['IdCentroImposicion'];
                markers[i].ocaRelayAddress = toTitleCase(ocaRelays[relayAddressIndex][i]['Calle'].trim()+' '+((ocaRelays[relayAddressIndex][i]['Numero'] && (typeof ocaRelays[relayAddressIndex][i]['Numero'] === 'string' || ocaRelays[relayAddressIndex][i]['Numero'] instanceof String)) ? ocaRelays[relayAddressIndex][i]['Numero'].trim() : '')+((ocaRelays[relayAddressIndex][i]['Piso'] && (typeof ocaRelays[relayAddressIndex][i]['Piso'] === 'string' || ocaRelays[relayAddressIndex][i]['Piso'] instanceof String)) ? ' '+ocaRelays[relayAddressIndex][i]['Piso'].trim() : '')+', '+ocaRelays[relayAddressIndex][i]['Localidad'].trim()+', '+ocaRelays[relayAddressIndex][i]['Provincia'].trim());
                markers[i].ocaRelayDescription = toTitleCase(ocaRelays[relayAddressIndex][i]['Sucursal']);
                markers[i].assignedInfowindow = new google.maps.InfoWindow({
                    content: formatInfowindow(ocaRelays[relayAddressIndex][i]['Sucursal'], markers[i].ocaRelayAddress, (previousRelay == ocaRelays[relayAddressIndex][i]['IdCentroImposicion']))
                });

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

        });
            $.getJSON('https://maps.googleapis.com/maps/api/geocode/json?region=ar&key='+ocaGmapsKey+'&address=' + encodeURIComponent(addressText) + '&components=country:AR' + postcodeParam, null, requestSuccess).fail(requestFail);
        }

        function assignClosestBranch() {
            function rad(x) {return x*Math.PI/180;}
            var closest = 0;
            if (home) {
                var R = 6371; // radius of earth in km
            var distances = {0:R*4};
            $.each(ocaRelays[relayAddressIndex], function (i, relay) {
                var mlat = ocaRelays[relayAddressIndex][i]['Latitud'];
                var mlng = ocaRelays[relayAddressIndex][i]['Longitud'];
                    var dLat  = rad(mlat - home.lat);
                    var dLong = rad(mlng - home.lng);
                    var a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                        Math.cos(rad(home.lat)) * Math.cos(rad(home.lat)) * Math.sin(dLong/2) * Math.sin(dLong/2);
                    var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
                    var d = R * c;
                    distances[i] = d;
                    if (d < distances[closest])
                        closest = i;
                });
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
            if (showPerState) {
                var $state = $('#ocaStateSelect-'+id_carrier);
                if (translateStateName(ocaRelays[relayAddressIndex][index].Provincia) !== $state.val()) {
                    $state.val(translateStateName(ocaRelays[relayAddressIndex][index].Provincia)).trigger('change', {
                        selected: translateStateName(ocaRelays[relayAddressIndex][index].Provincia),
                        load: true
                    });
                }
            }

            $('#ocaBranchSelect-'+id_carrier).val(index).trigger('chosen:updated');
            $('#uniform-ocaBranchSelect-'+id_carrier+'>span').text($("#ocaBranchSelect-"+id_carrier+" option[value='"+index+"']").text());
            $dataContainer.data('ocaBranch', index);
            if (!previous)
                relay_selection(markers[index].ocaRelayId);
            var bounds = new google.maps.LatLngBounds();
            if ((typeof home !== 'undefined') && home.lat && home.lng) {
                bounds.extend(new google.maps.LatLng(home.lat, home.lng));
                bounds.extend(markers[index].getPosition());
                map.fitBounds(bounds);
            }
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
            if ($.isArray(str))
                str = str[0];
            return str.replace(/\w\S*/g, function(txt){return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();});
        }

        function translateStateName(name) {
            var state = $.grep(ocaStates, function(item){
                return item.alias === name.trim().toLowerCase();
            });
            return state[0].name;
        }

    })
}