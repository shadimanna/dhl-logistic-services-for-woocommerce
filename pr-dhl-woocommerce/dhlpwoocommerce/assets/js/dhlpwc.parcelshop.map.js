var dhlpwc_parcelshop_maps_loaded = false;
function dhlpwc_parcelshop_maps_loaded_callback() {
    dhlpwc_parcelshop_maps_loaded = true;
}

jQuery(document).ready(function($) {

    var dhlpwc_marker_image = null;
    var dhlpwc_marker_image_shape = null;
    var dhlpwc_marker_droplet_image = null;
    var dhlpwc_marker_droplet_image_shape = null;

    var dhlpwc_markers = [];
    var dhlpwc_parcelshop_map = null;

    var dhlpwc_infowindow = null;

    var dhlpwc_parcelshop_maps_initiated = false;

    $(document.body).on('dhlpwc:init_parcelshop_map', function(e) {
        if (dhlpwc_parcelshop_maps_loaded !== true) {
            return;
        }

        if (dhlpwc_parcelshop_maps_initiated === true) {
            return;
        }

        dhlpwc_marker_image = {
            url: dhlpwc_frontend_ps_map.image_mini,
            size: new google.maps.Size(34,50),
            origin: new google.maps.Point(0, 0),
            anchor: new google.maps.Point(17, 50)
        };

        dhlpwc_marker_image_shape = {
            coord: [17,50, 2,24, 3,8, 7,5, 17,2, 28,5, 32,8, 33,24],
            type: 'poly'
        };

        dhlpwc_marker_droplet_image = {
            url: dhlpwc_frontend_ps_map.image_droplet,
            size: new google.maps.Size(17,25),
            origin: new google.maps.Point(0, 0),
            anchor: new google.maps.Point(8, 25)
        };

        dhlpwc_marker_droplet_image_shape = {
            coord: [8,25, 0,14, 0,5, 7,0, 10,0, 17,5, 17,14, 9,25],
            type: 'poly'
        };

        dhlpwc_parcelshop_map = new google.maps.Map(document.getElementById('dhlpwc-parcelshop-map'), {
            gestureHandling: 'greedy',
            zoom: 13,
            disableDefaultUI: true,
            zoomControl: true,
            styles: [ { "featureType": "road.arterial", "elementType": "geometry.fill", "stylers": [ { "color": "#f7da82" } ] }, { "featureType": "road.highway", "elementType": "geometry.fill", "stylers": [ { "color": "#be7be6" } ] }, { "featureType": "road.highway", "elementType": "geometry.stroke", "stylers": [ { "color": "#9c5eb0" } ] } ]
        });

        dhlpwc_infowindow = new google.maps.InfoWindow();

        dhlpwc_parcelshop_maps_initiated = true;

    }).on('click', '#dhlpwc_parcelshop_postcode_search_button', function(e) {
        e.preventDefault();
        $(document.body).trigger('dhlpwc:check_parcelshop_postcode');

    }).on('keypress', 'input#dhlpwc_parcelshop_postcode', function(e) {
        if (e.keyCode !== 13) {
            return;
        }

        e.preventDefault();
        $(document.body).trigger('dhlpwc:check_parcelshop_postcode');
        return false;

    }).on('dhlpwc:check_parcelshop_postcode', function() {
        var input =  $('input#dhlpwc_parcelshop_postcode');
        var value = input.val().toUpperCase();
        var country = $('#dhlpwc-parcelshop-option-country-select').val();

        // Auto trim
        input.val($.trim(value));

        var data = {
            'action': 'dhlpwc_parcelshop_list',
            'postcode': value,
            'country': country
        };

        $(document.body).trigger('dhlpwc:start_searching_parcelshop');

        $.post(dhlpwc_frontend_ps_map.ajax_url, data, function(response) {

            $(document.body).trigger('dhlpwc:stop_searching_parcelshop');

            try {
                geo_locations = response.data.geo_locations;
            } catch (error) {
                alert('Error');
                return;
            }

            // Do nothing if geo_locations is empty.
            if ($.isEmptyObject(geo_locations) === true) {
                return;
            }

            $(document.body).trigger('dhlpwc:update_parcelshops', [geo_locations]);
        }, 'json');

    }).on('dhlpwc:update_parcelshops', function(e, geo_locations) {
        $(document.body).trigger('dhlpwc:clear_parcelshop_select');
        $(document.body).trigger('dhlpwc:clear_parcelshop_map');

        $(document.body).trigger('dhlpwc:add_parcelshops_select', [geo_locations]);
        $(document.body).trigger('dhlpwc:show_parcelshops_select');

        $(document.body).trigger('dhlpwc:add_parcelshops_map', [geo_locations]);
        $(document.body).trigger('dhlpwc:show_parcelshops_map');
        $('.dhlpwc-checkout-subsection-map').css('visibility', 'visible');

        $(document.body).trigger('dhlpwc:refresh_parcelshops_stylishselect');

        $.each(geo_locations, function(key, geo_location) {
            $(document.body).trigger('dhlpwc:default_parcelshop_selection', [geo_location]);
            return false;
        });

    }).on('dhlpwc:add_parcelshops_map', function(e, geo_locations) {
        var map = $('#dhlpwc-parcelshop-map');
        var tmp_marker = null;
        $.each(geo_locations, function (key, geo_location) {
            tmp_marker = new google.maps.Marker({
                raiseOnDrag: false,
                icon: dhlpwc_marker_droplet_image,
                shape: dhlpwc_marker_droplet_image_shape,
                zIndex: 1,
                position: {lat: geo_location.latitude, lng: geo_location.longitude},
                map: dhlpwc_parcelshop_map,
                parcelshop_id: geo_location.id
            });

            tmp_marker.addListener('click', function () {
                $(document.body).trigger('dhlpwc:select_parcelshop', [this.parcelshop_id]);
            });

            dhlpwc_markers.push(tmp_marker);
        });

    }).on('dhlpwc:display_parcelshop_map', function(e, parcelshop_id) {
        for (var i = 0; i < dhlpwc_markers.length; i++) {
            if (dhlpwc_markers[i].parcelshop_id == parcelshop_id) {
                // Select marker
                for (var p = 0; p < dhlpwc_markers.length; p++) {
                    dhlpwc_markers[p].setIcon(dhlpwc_marker_droplet_image);
                    dhlpwc_markers[p].setShape(dhlpwc_marker_droplet_image_shape);
                    dhlpwc_markers[p].setZIndex(google.maps.Marker.MAX_ZINDEX);
                }

                dhlpwc_markers[i].map.panTo(dhlpwc_markers[i].getPosition());
                dhlpwc_markers[i].map.panBy(0, -dhlpwc_markers[i].map.getDiv().offsetHeight/5);

                dhlpwc_markers[i].setIcon(dhlpwc_marker_image);
                dhlpwc_markers[i].setShape(dhlpwc_marker_image_shape);
                dhlpwc_markers[i].setZIndex(google.maps.Marker.MAX_ZINDEX + 1);

                dhlpwc_infowindow.close();
                dhlpwc_infowindow.setContent(dhlpwc_frontend_ps_map.info_loader_view);
                dhlpwc_infowindow.open(dhlpwc_markers[i].map, dhlpwc_markers[i]);


                $('body #dhlpwc_parcelshop_select').val(dhlpwc_markers[i].parcelshop_id);
            }
        }

    }).on('dhlpwc:clear_parcelshop_map', function() {
        for (var i = 0; i < dhlpwc_markers.length; i++) {
            dhlpwc_markers[i].setMap(null);
        }
        dhlpwc_markers = [];

    }).on('dhlpwc:default_parcelshop_selection', function(e, geo_location) {
        $( 'div.dhlpwc-checkout-subsection-map' ).show();
        google.maps.event.trigger(dhlpwc_parcelshop_map, 'resize');
        $(document.body).trigger('dhlpwc:select_parcelshop', [geo_location.id]);

    }).on('dhlpwc:show_parcelshop_map_section', function() {
        $( 'div.dhlpwc-checkout-subsection-map' ).show();
        google.maps.event.trigger(dhlpwc_parcelshop_map, 'resize');

    }).on('dhlpwc:hide_parcelshop_map_section', function() {
        $( 'div.dhlpwc-checkout-subsection-map' ).css('visibility', 'hidden');

    }).on('dhlpwc:display_parcelshop_info', function(e, parcelshop_id) {
        var country = $('#dhlpwc-parcelshop-option-country-select').val();

        var data = {
            'action': 'dhlpwc_parcelshop_info',
            parcelshop_id: parcelshop_id,
            country: country
        };

        $('#dhlpwc-parcelshop-info').animate({opacity: 0});

        $.post(dhlpwc_frontend_select.ajax_url, data, function(response) {
            try {
                view =  response.data.view;
            } catch(error) {
                alert('Error');
                return;
            }

            dhlpwc_infowindow.setContent(view);

        }, 'json');

    });

    // $(document.body).trigger('dhlpwc:check_parcelshop_postcode');

});