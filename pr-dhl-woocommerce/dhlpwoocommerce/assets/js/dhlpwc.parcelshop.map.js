var dhlpwc_marker_image = null;
var dhlpwc_marker_image_shape = null;
var dhlpwc_marker_droplet_image = null;
var dhlpwc_marker_droplet_image_shape = null;

var dhlpwc_markers = [];
var dhlpwc_parcelshop_map = null;
var dhlpwc_last_postcode_check = null;

function dhlpwc_parcelshop_init_map()
{
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
        center: {lat: 51.694, lng: 5.3095},
        zoom: 12,
        disableDefaultUI: true,
        zoomControl: true,
        styles: [{"featureType":"all","elementType":"geometry.fill","stylers":[{"weight":"2.00"}]},{"featureType":"all","elementType":"geometry.stroke","stylers":[{"color":"#9c9c9c"}]},{"featureType":"all","elementType":"labels.text","stylers":[{"visibility":"on"}]},{"featureType":"landscape","elementType":"all","stylers":[{"color":"#f2f2f2"}]},{"featureType":"landscape","elementType":"geometry.fill","stylers":[{"color":"#ffffff"}]},{"featureType":"landscape.man_made","elementType":"geometry.fill","stylers":[{"color":"#ffffff"}]},{"featureType":"poi","elementType":"all","stylers":[{"visibility":"off"}]},{"featureType":"road","elementType":"all","stylers":[{"saturation":-100},{"lightness":45}]},{"featureType":"road","elementType":"geometry.fill","stylers":[{"color":"#eeeeee"}]},{"featureType":"road","elementType":"labels.text.fill","stylers":[{"color":"#7b7b7b"}]},{"featureType":"road","elementType":"labels.text.stroke","stylers":[{"color":"#ffffff"}]},{"featureType":"road.highway","elementType":"all","stylers":[{"visibility":"simplified"}]},{"featureType":"road.arterial","elementType":"labels.icon","stylers":[{"visibility":"off"}]},{"featureType":"transit","elementType":"all","stylers":[{"visibility":"off"}]},{"featureType":"water","elementType":"all","stylers":[{"color":"#46bcec"},{"visibility":"on"}]},{"featureType":"water","elementType":"geometry.fill","stylers":[{"color":"#c8d7d4"}]},{"featureType":"water","elementType":"labels.text.fill","stylers":[{"color":"#070707"}]},{"featureType":"water","elementType":"labels.text.stroke","stylers":[{"color":"#ffffff"}]}]
    });
}

jQuery(document).ready(function($) {

    $(document.body).on('keyup', 'input#dhlpwc_parcelshop_postcode', function(e) {
        $(document.body).trigger('dhlpwc:check_parcelshop_postcode');

    }).on('dhlpwc:check_parcelshop_postcode', function() {
        var input =  $('input#dhlpwc_parcelshop_postcode');

        // Only continue if input is not disabled (to avoid multiple requests)
        if (input.is(':disabled') == true) {
            return;
        }

        var value = input.val().toUpperCase();
        // Only continue if there are any changes (otherwise it would overtrigger on a keyup check)
        if ($.trim(value) == $.trim(dhlpwc_last_postcode_check)) {
            return;
        }

        input.val(value);
        dhlpwc_last_postcode_check = value;

        // Check if the postcode input matches Dutch postcodes
        if (value.toUpperCase().match(/^[1-9]{1}[0-9]{3} ?[A-Z]{2}$/) == null) {
            return;
        }

        var data = {
            'action': 'dhlpwc_parcelshop_list',
            'postcode': value
        };

        // Disable the input
        input.attr('disabled', 'disabled');

        $.post(dhlpwc_frontend_ps_map.ajax_url, data, function(response) {
            try {
                geo_locations = response.data.geo_locations;
            } catch (error) {
                alert('Error');
                return;
            }

            console.log(geo_locations);
            $(document.body).trigger('dhlpwc:update_parcelshops', [geo_locations]);

            // Enable the input
            $('input#dhlpwc_parcelshop_postcode').removeAttr('disabled');

        }, 'json');

    }).on('dhlpwc:update_parcelshops', function(e, geo_locations) {
        $(document.body).trigger('dhlpwc:clear_parcelshop_select');
        $(document.body).trigger('dhlpwc:clear_parcelshop_map');

        $(document.body).trigger('dhlpwc:add_parcelshops_select', [geo_locations]);
        $(document.body).trigger('dhlpwc:add_parcelshops_map', [geo_locations]);

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
                //        alert(marker.parcelshop_id);
                dhlpwc_markers[i].setIcon(dhlpwc_marker_image);
                dhlpwc_markers[i].setShape(dhlpwc_marker_image_shape);
                dhlpwc_markers[i].setZIndex(google.maps.Marker.MAX_ZINDEX + 1);

                $('body #dhlpwc_parcelshop_select').val(dhlpwc_markers[i].parcelshop_id);
            }
        }

    }).on('dhlpwc:clear_parcelshop_map', function() {
        for (var i = 0; i < dhlpwc_markers.length; i++) {
            dhlpwc_markers[i].setMap(null);
        }
        dhlpwc_markers = [];

    }).on('dhlpwc:default_parcelshop_selection', function(e, geo_location) {
        console.log(geo_location);
        $( 'div.dhlpwc-checkout-subsection-map' ).hide();
        $( 'div.dhlpwc-checkout-subsection-map' ).slideDown(400, function() {
            google.maps.event.trigger(dhlpwc_parcelshop_map, 'resize');
            $(document.body).trigger('dhlpwc:select_parcelshop', [geo_location.id]);
        });

    }).on('dhlpwc:show_parcelshop_map_section', function() {
        $( 'div.dhlpwc-checkout-subsection-map' ).hide();
        $( 'div.dhlpwc-checkout-subsection-map' ).slideDown(400, function() {
            google.maps.event.trigger(dhlpwc_parcelshop_map, 'resize');
        });

    }).on('dhlpwc:hide_parcelshop_map_section', function() {
        $('input#dhlpwc_parcelshop_postcode').val('');
        $('input#dhlpwc_parcelshop_postcode').trigger('keyup');
        $( 'div.dhlpwc-checkout-subsection-map' ).hide();

    });

    $(document.body).trigger('dhlpwc:check_parcelshop_postcode');

});